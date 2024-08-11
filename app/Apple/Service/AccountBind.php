<?php

declare(strict_types=1);

namespace App\Apple\Service;

use App\Events\AccountBindPhoneFailEvent;
use App\Events\AccountBindPhoneSuccessEvent;
use App\Apple\Service\Exception\{
    AttemptBindPhoneCodeException,
    MaxRetryAttemptsException,
    BindPhoneCodeException,
    UnauthorizedException
};
use App\Models\{Account, Phone, User};
use Filament\Notifications\Notification;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Psr\Log\LoggerInterface;

class AccountBind
{
    protected const int MAX_RETRY_ATTEMPTS = 10;
    protected const int PHONE_CODE_RETRY_ATTEMPTS = 10;
    protected const int PHONE_CODE_RETRY_DELAY = 10;
    protected const int|float PHONE_CODE_WAIT_TIME = 120; // 2 minutes

    protected array $usedPhoneIds = [];

    public function __construct(
        protected readonly Apple $apple,
        protected readonly LoggerInterface $logger,
        protected readonly int $maxRetryAttempts = self::MAX_RETRY_ATTEMPTS,
        protected readonly int $phoneCodeRetryAttempts = self::PHONE_CODE_RETRY_ATTEMPTS,
        protected readonly int $phoneCodeRetryDelay = self::PHONE_CODE_RETRY_DELAY,
        protected readonly int $phoneCodeWaitTime = self::PHONE_CODE_WAIT_TIME
    ) {}

    public function getMaxRetryAttempts(): int
    {
        return $this->maxRetryAttempts;
    }

    public function getPhoneCodeRetryAttempts(): int
    {
        return $this->phoneCodeRetryAttempts;
    }

    public function getPhoneCodeRetryDelay(): int
    {
        return $this->phoneCodeRetryDelay;
    }

    public function getPhoneCodeWaitTime(): int
    {
        return $this->phoneCodeWaitTime;
    }

    public function getUsedPhoneIds(): array
    {
        return $this->usedPhoneIds;
    }

    /**
     * @param int $id
     * @return void
     * @throws BindPhoneCodeException
     * @throws GuzzleException
     * @throws MaxRetryAttemptsException
     * @throws UnauthorizedException
     * @throws \Throwable
     */
    public function handle(int $id): void
    {
        try {
            $account = $this->getAccount($id);
            $this->validateAccount($account);
            $this->bindPhone($account);
        } catch (\Exception $e) {
            $this->handleException($e, $account ?? null);
            throw $e;
        }
    }

    private function getAccount(int $id): Account
    {
        return Account::findOrFail($id);
    }

    /**
     * @param Account $account
     * @return void
     */
    private function validateAccount(Account $account): void
    {
        if (!$account->password) {
            throw new \InvalidArgumentException("Account {$account->account} has no password set");
        }

        if ($account->bind_phone) {
            throw new \InvalidArgumentException("Account {$account->account} is already bound to a phone number");
        }
    }

    /**
     * @param Account $account
     * @return void
     * @throws GuzzleException
     * @throws MaxRetryAttemptsException
     * @throws UnauthorizedException|BindPhoneCodeException|\Throwable
     */
    private function bindPhone(Account $account): void
    {
        $this->apple->appleId->accountManageToken();
        $this->apple->appleId->password($account->password);
        $this->attemptBind($account);
    }

    /**
     * @param Account $account
     * @return void
     * @throws BindPhoneCodeException
     * @throws MaxRetryAttemptsException|\Throwable
     */
    private function attemptBind(Account $account): void
    {
        for ($attempt = 1; $attempt <= $this->maxRetryAttempts; $attempt++) {
            try {
                $phone = $this->getAvailablePhone();
                $this->bindPhoneToAccount($account, $phone);
                return;
            } catch (BindPhoneCodeException $e) {
                $this->handleBindPhoneCodeException($e, $account, $phone ?? null, $attempt);
            } catch (\Exception $e) {
                $this->handleBindFailure($phone ?? null);
                throw $e;
            }
        }

        throw new MaxRetryAttemptsException(
            sprintf("Account %s failed to bind phone: Exceeded maximum retry attempts %d", $account->account, $this->maxRetryAttempts)
        );
    }

    /**
     * @return Phone
     * @throws \Throwable
     */
    private function getAvailablePhone(): Phone
    {
        return DB::transaction(function () {
            $phone = Phone::query()
                ->where('status', Phone::STATUS_NORMAL)
                ->whereNotNull('phone_address')
                ->whereNotNull('phone')
                ->whereNotIn('id', $this->usedPhoneIds)
                ->lockForUpdate()
                ->firstOrFail();

            $phone->update(['status' => Phone::STATUS_BINDING]);

            return $phone;
        });
    }

    /**
     * @param Account $account
     * @param Phone $phone
     * @return void
     * @throws AttemptBindPhoneCodeException
     * @throws BindPhoneCodeException
     * @throws GuzzleException
     * @throws \Throwable
     */
    private function bindPhoneToAccount(Account $account, Phone $phone): void
    {
        $response = $this->apple->appleId->bindPhoneSecurityVerify(
            $phone->national_number,
            $phone->country_code,
            (string) $phone->country_dial_code
        );

        $id = $response->phoneNumberVerification()['phoneNumber']['id'] ?? null;
        if (empty($id)) {
            throw new BindPhoneCodeException("Failed to get ID for binding phone number {$phone->phone}");
        }

        $code = $this->getPhoneCode($phone);

        $this->apple->appleId->manageVerifyPhoneSecurityCode(
            id: $id,
            phoneNumber: $phone->national_number,
            countryCode: $phone->country_code,
             countryDialCode: (string) $phone->country_dial_code,
            code: $code
        );

        $this->updateAccountAndPhone($account, $phone);

        $this->logSuccess($account, $phone);
    }

    /**
     * @param Phone $phone
     * @return string
     * @throws AttemptBindPhoneCodeException
     * @throws GuzzleException
     * @throws \Exception
     */
    public function getPhoneCode(Phone $phone): string
    {
        sleep($this->phoneCodeWaitTime);
        $response = $this->apple->phoneCode->attemptGetPhoneCode(
            $phone->phone_address,
            $phone->phoneCodeParser(),
            $this->phoneCodeRetryAttempts,
            $this->phoneCodeRetryDelay
        );

        $code = $response->getData('code');
        if (empty($code)) {
            throw new AttemptBindPhoneCodeException("Failed to get phone code for {$phone->phone}");
        }

        return $code;
    }

    /**
     * @param Account $account
     * @param Phone $phone
     * @return void
     * @throws \Throwable
     */
    private function updateAccountAndPhone(Account $account, Phone $phone): void
    {
        DB::transaction(function () use ($account, $phone) {
            $account->update([
                'bind_phone'         => $phone->phone,
                'bind_phone_address' => $phone->phone_address,
            ]);
            $phone->update(['status' => Phone::STATUS_BOUND]);
        });
    }

    /**
     * @param BindPhoneCodeException $e
     * @param Account $account
     * @param Phone|null $phone
     * @param int $attempt
     * @return void
     */
    private function handleBindPhoneCodeException(BindPhoneCodeException $e, Account $account, ?Phone $phone, int $attempt): void
    {
        $this->logger->warning('Account {account} failed to bind phone {phone} on attempt {attempt}: {message}', [
            'attempt' => $attempt,
            'account' => $account->account,
            'phone'   => $phone?->phone ?? 'unknown',
            'message' => $e->getMessage(),
        ]);

        if ($phone) {
            $this->usedPhoneIds[] = $phone->id;
            $this->handleBindFailure($phone);
        }
    }

    private function handleBindFailure(?Phone $phone): void
    {
        if ($phone) {
            try {
                $phone->update(['status' => Phone::STATUS_NORMAL]);
            } catch (\Exception $e) {
                $this->logger->error("Failed to update phone {$phone->phone} status: {$e->getMessage()}", [
                    'phone'    => $phone->phone,
                    'phone_id' => $phone->id,
                ]);
            }
        }
    }

    private function logSuccess(Account $account, Phone $phone): void
    {
        $this->logger->info("Account {$account->account} successfully bound to phone number {$phone->phone}");

        Event::dispatch(new AccountBindPhoneSuccessEvent($account));

        Notification::make()
            ->title("Account {$account->account} successfully bound to phone number {$phone->phone}")
            ->success()
            ->sendToDatabase(User::get());
    }

    private function handleException(\Exception $e, ?Account $account): void
    {
        $accountId = $account ? $account->account : 'unknown';
        $this->logger->error("Account {$accountId} binding failed: {$e->getMessage()}");

        if ($account){
            Event::dispatch(new AccountBindPhoneFailEvent($account));
        }
        Notification::make()
            ->title("Account {$accountId} binding failed")
            ->body($e->getMessage())
            ->warning()
            ->sendToDatabase(User::get());
    }
}
