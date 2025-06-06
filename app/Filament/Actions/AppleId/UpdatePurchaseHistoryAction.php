<?php

namespace App\Filament\Actions\AppleId;

use App\Models\Account;
use App\Models\PurchaseHistory;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Log;

class UpdatePurchaseHistoryAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'update purchase history';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('更新购买历史记录')
            ->icon('heroicon-o-user-group')
            ->successNotificationTitle('更新购买历史记录成功')
            ->modalSubmitActionLabel('确认')
            ->modalCancelActionLabel('取消')
            ->action(function () {

                try {

                    /**
                     * @var Account $account
                     */
                    $account = $this->getRecord();

                    $this->handle($account);

                    $this->success();

                } catch (\Exception $e) {

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
        $purchaseHistory = $apple->appleIdResource()
            ->getReportProblemResource()
            ->searchCollection();

        foreach ($purchaseHistory as $searchResponseItem) {

            /**
             * @var SearchResponse $searchResponseItem
             */
            foreach ($searchResponseItem->purchases as $purchaseItem) {
                // 先创建或更新 purchase_history 记录

                /**
                 * @var Purchase $purchaseItem
                 */
                $purchase = PurchaseHistory::updateOrCreate(
                    [
                        'purchase_id' => $purchaseItem->purchaseId,
                        'account_id'  => $apple->getAccount()->model()->id,
                    ],
                    [
                        'dsid'                   => $purchaseItem->dsid,
                        'invoice_amount'         => $purchaseItem->invoiceAmount,
                        'weborder'               => $purchaseItem->weborder,
                        'invoice_date'           => $purchaseItem->invoiceDate,
                        'purchase_date'          => $purchaseItem->purchaseDate,
                        'is_pending_purchase'    => $purchaseItem->isPendingPurchase,
                        'estimated_total_amount' => $purchaseItem->estimatedTotalAmount,
                    ]
                );

                // 处理 Pli 数据
                foreach ($purchaseItem->plis as $pliItem) {

                    /**
                     * @var Pli $pliItem
                     */
                    \App\Models\Pli::updateOrCreate(
                        [
                            'item_id'     => $pliItem->itemId,
                            'purchase_id' => $purchaseItem->purchaseId,
                        ],
                        [
                            'purchase_history_id' => $purchase->id, // 使用 purchase_history 的 id
                            'storefront_id'       => $pliItem->storefrontId,
                            'adam_id'             => $pliItem->adamId,
                            'guid'                => $pliItem->guid,
                            'amount_paid'         => $pliItem->amountPaid,
                            'pli_date'            => $pliItem->pliDate,
                            'is_free_purchase'    => $pliItem->isFreePurchase,
                            'is_credit'           => $pliItem->isCredit,
                            'line_item_type'      => $pliItem->lineItemType,
                            'title'               => $pliItem->title,
                            'localized_content'   => $pliItem?->localizedContent?->toArray(),
                            'subscription_info'   => $pliItem?->subscriptionInfo?->toArray(),
                        ]
                    );
                }
            }
        }
    }
}
