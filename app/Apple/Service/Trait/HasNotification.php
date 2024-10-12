<?php


namespace App\Apple\Service\Trait;

use App\Events\AccountBindPhoneFailEvent;
use App\Events\AccountBindPhoneSuccessEvent;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Event;

trait HasNotification
{
    /**
     * 发送成功通知至数据库并记录日志。
     *
     * @param string $title 通知的标题
     * @param string $message 通知的具体内容
     *
     * 此方法用于在用户绑定手机成功等类似操作后，触发一个成功通知。它通过记录日志、分发事件以及创建并发送一个成功类型的通知到数据库来完成这一过程。
     * 日志信息用于追踪操作记录，事件分发有助于解耦各业务模块，而数据库通知则确保用户能在前端界面收到实时提醒。
     */
    public function successNotification(string $title, string $message): void
    {

        $this->logger->info($message);

        Event::dispatch(
            new AccountBindPhoneSuccessEvent(account: $this->getAccount(), description:$message)
        );

        Notification::make()
            ->title($title)
            ->body($message)
            ->success()
            ->sendToDatabase(User::get());
    }


    /**
     * 发送错误通知至数据库并记录日志。
     *
     * @param string $title 通知的标题
     * @param string $message 通知的详细内容
     *
     * 此方法用于在绑定手机号失败或其他错误情况时触发通知。它会记录一条错误级别的日志，
     * 根据当前账户是否有效分发一个 `AccountBindPhoneFailEvent` 事件，并向所有用户发送一条警告通知到数据库。
     */
    public function errorNotification(string $title,string $message): void
    {
        $this->logger->error($message);

        $this->getAccount() && Event::dispatch(
            new AccountBindPhoneFailEvent(account: $this->account, description: $message)
        );

        Notification::make()
            ->title($title)
            ->body($message)
            ->warning()
            ->sendToDatabase(User::get());
    }
}
