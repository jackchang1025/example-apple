<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrowserFingerprintException extends Exception
{
    /**
     * The exception context for logging.
     *
     * @var array
     */
    protected array $context;

    /**
     * Create a new exception instance.
     *
     * @param string $message
     * @param array $context
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "", array $context = [], int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * 报告异常。
     *
     * @return void
     */
    public function report(): void
    {
        // 自动将请求IP添加到日志上下文中，以便更好地追踪
        $this->context['ip'] = $this->context['ip'] ?? request()->ip();

        Log::warning('Browser Fingerprint Validation Failed: ' . $this->getMessage(), $this->context);
    }

    /**
     * 将异常渲染到 HTTP 响应中。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'server error',
            'error_code' => 'FINGERPRINT_VALIDATION_FAILED'
        ], $this->getCode());
    }
}
