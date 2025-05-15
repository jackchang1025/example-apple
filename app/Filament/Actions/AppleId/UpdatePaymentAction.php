<?php

namespace App\Filament\Actions\AppleId;

use App\Models\Account;
use App\Models\Payment;
use Exception;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;

class UpdatePaymentAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'UpdatePaymentAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('更新支付方式')
            ->icon('heroicon-o-user-group')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->successNotificationTitle('更新支付方式成功')
            ->action(function () {

                try {

                    /**
                     * @var Account $account
                     */
                    $account = $this->getRecord();

                    $this->handle($account);

                    $this->success();

                } catch (Exception $e) {

                    Log::error($e);
                    $this->failureNotificationTitle($e->getMessage());
                    $this->failure();
                }
            });
    }

    /**
     * @param Account $account
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \Illuminate\Contracts\Container\CircularDependencyException
     * @throws \Saloon\Exceptions\Request\FatalRequestException
     * @throws \Saloon\Exceptions\Request\RequestException
     */
    protected function handle(Account $apple): void
    {

        $payment = $apple->appleIdResource()
            ->getPaymentResource()
            ->getPayment();


        $primaryPaymentMethod = $payment->primaryPaymentMethod;

        Payment::updateOrCreate([
            'account_id' => $apple->id,
            'payment_id' => $primaryPaymentMethod->paymentId,
        ],
            array_merge($primaryPaymentMethod->toArray(), [
                'default_shipping_address' => $payment->defaultShippingAddress,
            ]));

    }
}
