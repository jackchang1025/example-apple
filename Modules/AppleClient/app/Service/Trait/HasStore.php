<?php

namespace Modules\AppleClient\Service\Trait;

use App\Models\Account;
use Illuminate\Support\Collection;
use Modules\AppleClient\Service\DataConstruct\Phone;
use Modules\AppleClient\Service\Exception\UnauthorizedException;
use Modules\AppleClient\Service\Response\Response;
use Modules\AppleClient\Service\Store\CacheStore;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait HasStore
{

    protected const string CACHE_KEY_ACCOUNT = 'account';
    protected const string CACHE_KEY_TRUSTED_PHONE_NUMBER = 'trustedPhoneNumber';
    protected const string CACHE_KEY_PHONE_LIST = 'phone_list';

    protected ?Account $account = null;

    public function getAccount(): Account
    {
        return $this->account ??= $this->getAccountByCache();
    }

    /**
     * 验证账号
     *
     * @param Account $account
     * @throws \InvalidArgumentException
     */
    protected function validateAccount(Account $account): void
    {
        if (!$account->password) {
            throw new \InvalidArgumentException("密码为空");
        }

        if ($account->bind_phone) {
            throw new \InvalidArgumentException("已绑定手机号");
        }
    }

    /**
     * 通过缓存获取账户信息
     *
     * 当前用户必须已登录，否则抛出异常。此方法利用缓存系统加速账户信息的获取。
     *
     * @return Account|null 若获取成功则返回账户对象，否则返回 null。
     *
     * @throws UnauthorizedException
     */
    public function getAccountByCache():?Account
    {
        if (!$this->hasLogin()){
            throw new UnauthorizedException("is not login");
        }
        return  $this->appleClient->getCacheStore()?->get(static::CACHE_KEY_ACCOUNT);
    }

    /**
     * 根据ID获取账户信息。
     *
     * @param int $id 账户ID
     *
     * @return Account 查询到的账户实例，如未找到则抛出异常
     */
    public function getAccountById(int $id): Account
    {
        return Account::findOrFail($id);
    }

    /**
     * 设置账户信息到缓存存储中。
     *
     * @param Account $account 要设置的账户对象实例。
     *
     * @return CacheStore|null 如果账户成功添加到缓存中则返回缓存存储实例，否则返回 null。
     */
    public function setAccount(Account $account): ?CacheStore
    {
       return $this->getCacheStore()?->add(static::CACHE_KEY_ACCOUNT, $account);
    }

    /**
     * 获取可信的电话号码。
     *
     * 如果用户未登录，将抛出运行时异常。
     * 首先尝试从缓存中获取可信的电话号码，如果不存在，则通过Apple客户端验证并缓存该电话号码。
     *
     * @return Phone|null 可信的电话号码对象，如果无法获取则返回null。
     * @throws UnauthorizedException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getTrustedPhoneNumber():?Phone
    {
        if (!$this->hasLogin()){
            throw new UnauthorizedException("is not login");
        }

        return $this->getCacheStore()?->get(static::CACHE_KEY_TRUSTED_PHONE_NUMBER)
            ?? $this->cacheTrustedPhoneNumber($this->getAppleClient()->auth());
    }

    /**
     * 获取电话号码列表
     *
     * 当前用户需已登录，否则抛出运行时异常。
     * 首先尝试从缓存中获取电话号码列表，如果缓存中没有，则通过 Apple 客户端认证后存储并返回数据。
     *
     * @return Collection 返回电话号码列表的集合
     * @throws FatalRequestException
     * @throws RequestException|UnauthorizedException
     */
    public function getPhoneLists():Collection
    {
        if (!$this->hasLogin()){
            throw new UnauthorizedException("is not login");
        }

        return $this->getCacheStore()?->get(static::CACHE_KEY_PHONE_LIST)
            ?? $this->storePhoneData($this->getAppleClient()->auth());
    }

    /**
     * 存储电话数据到缓存。
     *
     * 接收一个响应对象，从中提取可信的电话号码列表，
     * 如果列表不为空，则将其添加到缓存中。
     *
     * @param Response $response 响应对象，包含电话号码数据。
     *
     * @return Collection 包含可信电话号码的集合。
     * @throws \JsonException
     */
    public function storePhoneData(Response $response): Collection
    {
        $phoneList = $response->getTrustedPhoneNumbers();
        if ($phoneList->isNotEmpty()) {
            $this->getCacheStore()?->add(static::CACHE_KEY_PHONE_LIST, $phoneList);
        }

        return $phoneList;
    }

    /**
     * 缓存可信电话号码。
     *
     * 根据提供的响应对象提取可信电话号码，并将其存储在缓存中。
     * 如果成功缓存，则返回该电话号码；否则，返回 null。
     *
     * @param Response $response 响应对象，包含可信电话号码信息。
     *
     * @return Phone|null 可信电话号码对象，如果响应中不包含可信号码则返回 null。
     * @throws \JsonException
     */
    public function cacheTrustedPhoneNumber(Response $response): ?Phone
    {
        $trustedPhoneNumber = $response->getTrustedPhoneNumber();
        if ($trustedPhoneNumber) {
            $this->getCacheStore()?->add(static::CACHE_KEY_TRUSTED_PHONE_NUMBER, $trustedPhoneNumber);
        }
        return $trustedPhoneNumber;
    }
}
