<?php

namespace App\Http\Middleware;

use App\Exceptions\BrowserFingerprintException;
use App\Services\FingerprintAnalysisService;
use App\Services\FingerprintDecryptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BrowserFingerprintMiddleware
{
    public function __construct(
        protected FingerprintAnalysisService $analysisService,
        protected FingerprintDecryptionService $decryptionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @throws BrowserFingerprintException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $payloadJson = $request->input('fingerprint');

        if (empty($payloadJson)) {
            throw new BrowserFingerprintException('缺少浏览器指纹数据。', [], 400);
        }

        // 核心逻辑被简化：解密和分析都在一个 try 块中完成。
        // 任何失败（解密、验证、分析）都会抛出 BrowserFingerprintException 或其子类异常，
        // 然后被 Laravel 的全局异常处理器捕获。
        $fingerprintResult = $this->decryptionService->decrypt($payloadJson);

        $this->analysisService->ensureNotSuspicious($fingerprintResult);

        return $next($request);
    }
}
