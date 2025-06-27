<?php
namespace App\Services\Trait;
use Weijiajia\IpAddress\IpResponse;
use Illuminate\Support\Facades\Cache;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;
use Weijiajia\IpAddress\IpAddressManager;

trait IpInfo
{
    protected ?IpResponse $ipInfo = null;

    public function ipAddressManager(): IpAddressManager
    {
        return app()->make(IpAddressManager::class);
    }

    public function ipInfo(): IpResponse
    {
        return $this->ipInfo ??= $this->ipAddressManager()
        ->forgetDrivers()
        ->driver()
        ->withLogger($this->logger())
        ->withProxyEnabled(false)
        ->withCacheDriver(new LaravelCacheDriver(store: Cache::store('redis')))
        ->withCacheExpiry(99999999)
        ->withCacheKey('ip_info_' . $this->ip())
        ->request(['ip' => $this->ip()]);
    }

    public function cacheAccountIp(string $account): void
    {
        $ip = request()->ip();
        
        // 验证IP是否为有效的公网IP
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $cacheKey = "account_ip:" . md5(strtolower($account));
            
            // 缓存7天
            Cache::put($cacheKey, $ip, now()->addDays(7));
        }
    }

    public function getCachedAccountIp(): ?string
    {
        // 如果当前对象是Account模型，获取appleid
        if (method_exists($this, 'appleId')) {
            $account = $this->appleId();
            $cacheKey = "account_ip:" . md5(strtolower($account));
            
            $cachedIp = Cache::get($cacheKey);
            
            return $cachedIp;
        }
        
        return null;
    }

    
    /**
     * 检测是否在命令行界面中运行
     */
    protected function isCommandLineInterface(): bool
    {
        return php_sapi_name() === 'cli' || 
               app()->runningInConsole() ||
               !isset($_SERVER['HTTP_HOST']);
    }

    public function ip(): ?string
    {
        if(env('APP_ENV') === 'local'){
            $ip = '120.85.97.28';
        }else if($this->isCommandLineInterface()){
            $ip = $this->getCachedAccountIp();
        }else{
            $ip = request()->ip();
        }

        // 判断 IP 地址是否为私有或保留地址 (常见的内网/本地地址)
        $isPrivateOrReservedIp = false;
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            // 如果IP有效，但 filter_var 认为它在私有或保留范围内，则以下会返回 false
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $isPrivateOrReservedIp = true;
            }
        } else {
            // IP 地址本身无效，也视为一种特殊情况
            $isPrivateOrReservedIp = true;
        }

        if ($isPrivateOrReservedIp) {
            // 如果没有配置默认国家，并且是内网IP，则抛出异常或返回一个预定义的“未知”国家
            // 避免查询一个无法确定地理位置的IP
            // 或者，你可以根据业务需求决定是否要记录日志并尝试用如 '1.1.1.1' 这样的公网IP去查询
            // 这里我们选择优先使用配置的默认国家，如果存在
            throw new \RuntimeException("Cannot determine country for private/reserved IP address: {$ip} and no default country is configured.");
        }

        return $ip;
    }
}
