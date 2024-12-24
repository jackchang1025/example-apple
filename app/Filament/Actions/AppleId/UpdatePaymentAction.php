<?php

namespace App\Filament\Actions\AppleId;

use App\Models\Account;
use App\Models\Payment;
use Exception;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Modules\AppleClient\Service\AppleBuilder;

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
            ->action(function () {

                try {

                    /**
                     * @var Account $account
                     */
                    $account = $this->getRecord();

                    $this->handle($account);

                    Notification::make()
                        ->title('更新支付方式成功')
                        ->success()
                        ->send();

                } catch (Exception $e) {

                    Notification::make()
                        ->title($e->getMessage())
                        ->warning()
                        ->send();
                }
            });
    }

    protected function handle(Account $account): void
    {

        $apple = app(AppleBuilder::class)->build($account->toAccount());

        $payment = $apple->getWebResource()
            ->getAppleIdResource()
            ->getPaymentResource()
            ->getPayment();


        $primaryPaymentMethod = $payment->primaryPaymentMethod;

        Payment::updateOrCreate([
            'account_id' => $account->id,
            'payment_id' => $primaryPaymentMethod->paymentId,
        ],
            array_merge($primaryPaymentMethod->toArray(), [
                'default_shipping_address' => $payment->defaultShippingAddress,
            ]));

    }
}
