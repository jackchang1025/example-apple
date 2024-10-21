<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Modules\AppleClient\Service;

use App\Models\Account;
use Modules\IpProxyManager\Service\ProxyService;
use Modules\PhoneCode\Service\PhoneConnector;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class AppleAccountManagerFactory
{
    private array $instances = [];

    public function __construct(
        protected CacheInterface $cache,
        protected LoggerInterface $logger,
        protected ProxyService $proxyService,
        protected PhoneConnector $phoneConnector,
        protected ClientFactory $clientFactory,
    ) {
    }

    /**
     * @param Account|array $account
     * @param array<string, mixed> $config
     *
     * @return AppleAccountManager
     */
    public function create(Account|array $account, ?array $config = null): AppleAccountManager
    {
        $account = $this->resolveAccount($account);

        $sessionId = $account->getSessionId();

        // 检查内存缓存
        if (isset($this->instances[$sessionId]) && $this->instances[$sessionId] instanceof AppleAccountManager) {
            return $this->instances[$sessionId];
        }

        // 创建新实例
        $manager = $this->build($account, $config);

        // 存储到内存缓存
        $this->instances[$sessionId] = $manager;

        return $manager;
    }

    private function resolveAccount(Account|array $account): Account
    {
        if ($account instanceof Account) {
            return $account;
        }

        return Account::firstOrNew(['account' => $account['account']], $account);
    }


    public function createFromSessionId(string $sessionId): AppleAccountManager
    {
        $account = $this->cache->get($sessionId);
        if (!$account) {
            throw new \RuntimeException('Session expired or invalid');
        }

        return $this->create($account);
    }

    public function builderClient(string $sessionId, ?array $config = null): AppleClient
    {
        return $this->clientFactory->getClient($sessionId, $config);
    }

    protected function build(Account $account, ?array $config = null): AppleAccountManager
    {
        return (new AppleAccountManager($account, $this->builderClient($account->getSessionId(), $config)))
            ->withPhoneConnector($this->phoneConnector)
            ->withLogger($this->logger)
            ->withTries(5)
            ->withRetryInterval(5)
            ->withUseExponentialBackoff(true);
    }
}
