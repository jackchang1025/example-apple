<?php

namespace App\Services\Trait;

trait TruncatesNotificationMessage
{
    /**
     * 截断过长的消息以避免数据库错误
     * 
     * @param string $message
     * @param int $maxLength 最大长度，默认500字符
     * @return string
     */
    protected function truncateMessage(string $message, int $maxLength = 500): string
    {
        if (mb_strlen($message) <= $maxLength) {
            return $message;
        }

        return mb_substr($message, 0, $maxLength - 3) . '...';
    }

    /**
     * 获取安全的异常消息用于通知
     * 
     * @param \Throwable $exception
     * @param int $maxLength 最大长度，默认500字符
     * @return string
     */
    protected function getSafeExceptionMessage(\Throwable $exception, int $maxLength = 500): string
    {
        return $this->truncateMessage($exception->getMessage(), $maxLength);
    }
}
