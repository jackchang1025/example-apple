<?php

namespace App\Models;

use App\Apple\Enums\AccountStatus;
use App\Apple\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Weijiajia\SaloonphpAppleClient\Trait\ProvidesAppleIdCapabilities;
use Weijiajia\HttpProxyManager\ProxyManager;
use GuzzleHttp\Cookie\CookieJarInterface;
use GuzzleHttp\Cookie\FileCookieJar;
use Weijiajia\SaloonphpHeaderSynchronizePlugin\Contracts\HeaderSynchronizeDriver;
use Weijiajia\SaloonphpHeaderSynchronizePlugin\Driver\FileHeaderSynchronize;
use Weijiajia\SaloonphpHttpProxyPlugin\ProxySplQueue;
use Illuminate\Support\Collection;
use Weijiajia\SaloonphpAppleClient\Browser\Browser;
use Weijiajia\SaloonphpAppleClient\Contracts\AppleId as AppleIdContract;
use Psr\Log\LoggerInterface;
use Weijiajia\SaloonphpAppleClient\Country;
use Weijiajia\HttpProxyManager\Contracts\ProxyInterface;
use Illuminate\Support\Facades\Cache;
use Saloon\CachePlugin\Drivers\LaravelCacheDriver;
use Saloon\Http\PendingRequest;
use GuzzleHttp\RequestOptions;
use Psr\EventDispatcher\EventDispatcherInterface;
use Saloon\Helpers\MiddlewarePipeline;
use App\Services\Trait\HasLog;
use App\Services\Trait\IpInfo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Saloon\Http\Response;
use Weijiajia\SaloonphpAppleClient\Exception\ProxyConnectEstablishedException;
use Illuminate\Support\Facades\Log;

/**
 *
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $appleid 账号 (Apple ID)
 * @property string $password 密码
 * @property string $bind_phone 绑定的手机号码
 * @property string $bind_phone_address 绑定的手机号码所在地址
 * @property string|null $country_code 国家代码
 * @method static \Illuminate\Database\Eloquent\Builder|Account newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Account query()
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereAppleid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereBindPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereBindPhoneAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCountryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereUpdatedAt($value)
 * @method static \Database\Factories\AccountFactory factory($count = null, $state = [])
 * @property AccountStatus $status
 * @property AccountStatus $type
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereStatus($value)
 * @property-read string $status_description
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccountLogs> $logs
 * @property-read int|null $logs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Devices> $devices
 * @property-read int|null $devices_count
 * @property-read \App\Models\Payment|null $payment
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereType($value)
 * @property-read \App\Models\FamilyMember|null $familyMember
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\FamilyMember> $familyMembers
 * @property-read int|null $family_members_count
 * @property-read \App\Models\Family|null $family
 * @property string|null $dsid dsid
 * @method static \Illuminate\Database\Eloquent\Builder|Account whereDsid($value)
 * @method getApiResources()
 * @property-read \App\Models\AccountManager|null $accountManager
 * @property-read \App\Models\FamilyMember|null $asFamilyMember
 * @property-read \App\Models\Family|null $belongToFamily
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PhoneNumber> $phoneNumbers
 * @property-read int|null $phone_numbers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\IcloudDevice> $IcloudDevice
 * @property-read int|null $icloud_device_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseHistory> $purchaseHistory
 * @property-read int|null $purchase_history_count
 * @mixin \Eloquent
 */
class Account extends Model implements AppleIdContract
{
    use HasFactory;
    use ProvidesAppleIdCapabilities;
    use HasLog;
    use IpInfo;
    use SoftDeletes;
    protected $table = 'account';

    protected ?string $cookieFilePath = null;

    protected $fillable = ['appleid', 'password', 'bind_phone', 'bind_phone_address', 'country_code', 'id', 'status', 'type', 'dsid'];

    protected $casts = [
        'status' => AccountStatus::class,
        'type' => AccountType::class,
    ];

    protected static function booted()
    {
        static::retrieved(function (Account $account) {

            $account->config()->add('apple_auth_url', config('apple.apple_auth_url'));
        });
    }

    public function proxyManager(): ProxyManager
    {
        return app()->make(ProxyManager::class);
    }

    public function middleware(): MiddlewarePipeline
    {
        if (!isset($this->middlewarePipeline)) {
            $this->middlewarePipeline = new MiddlewarePipeline;
            $this->middlewarePipeline->onRequest($this->debugRequest());
            $this->middlewarePipeline->onResponse($this->debugResponse());

            $this->middlewarePipeline->onResponse(function (Response $response) {
                if (ProxyConnectEstablishedException::isProxyConnectResponse($response)) {
                    throw new ProxyConnectEstablishedException($response);
                }
            });
        }
        return $this->middlewarePipeline;
    }


    public function log(string $message, array $data = []): void
    {
        $this->logs()->create(['action' => $message, 'request' => $data]);
    }

    public function logger(): ?LoggerInterface
    {
        return $this->logger ??= app()->make(LoggerInterface::class);
    }


    public function country(): ?Country
    {
        return $this->country ??= Country::make($this->ipInfo()->getCountryCode());
    }

    public function city(): ?string
    {
        $city = $this->ipInfo()->getCity();
        if ($city === null) {
            return null;
        }

        if (str_contains($city, ' ')) {
            return explode(separator: ' ', string: $city)[0];
        }

        return $city;
    }

    /**
     * 强制同步 cookie 到文件和缓存
     */
    public function syncCookies(): void
    {
        $cookieJar = $this->cookieJar();

        if ($cookieJar instanceof FileCookieJar) {
            $cookieJar->save($this->getCookieFilename());
        }
    }

    public function cookieJar(): ?CookieJarInterface
    {
        if ($this->cookieJar !== null) {
            return $this->cookieJar;
        }

        return $this->cookieJar ??= new FileCookieJar($this->getCookieFilename(), true);
    }

    public function getCookieFilename(): string
    {
        $this->cookieFilePath ??= storage_path("/app/cookies/{$this->appleId()}.json");

        if (file_exists($this->cookieFilePath)) {
            return $this->cookieFilePath;
        }


        //判断目录是否存在
        if (
            !file_exists(dirname($this->cookieFilePath))
            && !mkdir($concurrentDirectory = dirname($this->cookieFilePath), 0777, true)
            && !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        return $this->cookieFilePath;
    }

    public function headerSynchronizeDriver(): HeaderSynchronizeDriver
    {
        if ($this->headerSynchronizeDriver !== null) {
            return $this->headerSynchronizeDriver;
        }

        $path = storage_path("/app/headers/{$this->appleId()}.json");
        //判断目录是否存在
        if (!file_exists(dirname($path)) && !mkdir($concurrentDirectory = dirname($path), 0777, true) && !is_dir(
            $concurrentDirectory
        )) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
        return $this->headerSynchronizeDriver ??= new FileHeaderSynchronize($path);
    }


    public function makeLaravelPhonePhoneNumber(): \Propaganistas\LaravelPhone\PhoneNumber
    {
        return $this->makePhoneNumber(
            $this->appleid,
        );
    }

    public function appleId(): string
    {
        return $this->getAttribute('appleid');
    }

    public function password(): string
    {
        return $this->getAttribute('password');
    }

    public function getSessionId(): string
    {
        return base64_encode($this->appleId());
    }

    public function proxySplQueue(): ?ProxySplQueue
    {
        $proxyManager = $this->proxyManager();

        if (!config('http-proxy-manager.proxy_enabled')) {
            return null;
        }


        if ($this->proxySplQueue === null) {


            $proxyConnector = $proxyManager->forgetDrivers()
                ->driver()
                ->withLogger($this->logger());

            if (config('http-proxy-manager.ipaddress_enabled') && $this->country()) {
                $proxyConnector->withCountry(
                    $this->country()->getAlpha2Code()
                )
                    ->withCity($this->city());
            }

            if ($this->debug()) {
                $proxyConnector->debug();
            }

            $proxyConnector->middleware()->merge($this->middleware());

            $proxy = $proxyConnector->defaultModelIp();

            if ($proxy instanceof Collection) {
                $proxies = $proxy->map(fn(ProxyInterface $item) => $item->getUrl())->toArray();
                return  $this->proxySplQueue = new ProxySplQueue(roundRobinEnabled: true, proxies: $proxies);
            }

            if ($proxy instanceof ProxyInterface) {
                return $this->proxySplQueue = new ProxySplQueue(roundRobinEnabled: true, proxies: [$proxy->getUrl()]);
            }

            throw new \InvalidArgumentException('提供的代理不是有效的代理对象');
        }
        return $this->proxySplQueue;
    }

    public function browser(): Browser
    {
        if ($this->browser === null) {

            $this->browser = new Browser();

            if ($this->country() !== null) {
                $this->browser->withLanguageForCountry($this->country()->getAlpha2Code());
            }

            return $this->browser;

            $proxyQueue = $this->proxySplQueue(); // 获取代理队列
            if (!$proxyQueue || $proxyQueue->isEmpty()) {
                throw new \RuntimeException('Cannot create browser info without a proxy.');
            }

            $ipAddressRequest = $this->ipAddressManager()
                ->forgetDrivers()
                ->driver()
                ->withCacheDriver(new LaravelCacheDriver(Cache::store('redis')))
                ->withCacheExpiry(60 * 30)
                ->withCacheKey(function (PendingRequest $pendingRequest) {

                    //获取代理配置
                    $proxyConfig = $pendingRequest->config()->get(RequestOptions::PROXY);
                })
                ->withLogger($this->logger())
                ->withForceProxy(true)
                ->withProxyEnabled(true)
                ->withProxyQueue($proxyQueue);

            if ($this->debug()) {
                $ipAddressRequest->debug();
            }

            $ipAddressRequest->middleware()->merge($this->middleware());

            $ipInfo = $ipAddressRequest->request();

            $this->browser = new Browser();
            $this->browser->timezone = $ipInfo->getTimezone();
            $this->browser->withLanguageForCountry($ipInfo->getCountryCode());
        }
        return $this->browser;
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AccountLogs::class, 'account_id', 'id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Devices::class);
    }

    public function IcloudDevice(): HasMany
    {
        return $this->hasMany(IcloudDevice::class);
    }

    //    public function payment(): HasMany
    //    {
    //        return $this->hasMany(Payment::class);
    //    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function purchaseHistory(): HasMany
    {
        return $this->HasMany(PurchaseHistory::class);
    }

    /**
     * 获取账号所属的家庭组（无论是否为组织者）
     */
    public function belongToFamily(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'dsid', 'organizer')
            ->orWhereHas('members', function ($query) {
                $query->where('dsid', $this->dsid)
                    ->orWhere('apple_id', $this->appleid);
            });
    }

    /**
     * 获取同一家庭组的所有成员（无论是否为组织者）
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(FamilyMember::class, 'family_id', 'id')
            ->whereHas('family', function ($query) {
                $query->where('organizer', $this->dsid);
            })
            ->orWhereExists(function ($query) {
                $query->from('family_members as fm')
                    ->whereColumn('fm.family_id', 'family_members.family_id')
                    ->where(function ($q) {
                        $q->where('fm.dsid', $this->dsid)
                            ->orWhere('fm.apple_id', $this->appleid);
                    });
            });
    }

    /**
     * 获取当前账号的家庭成员记录
     */
    public function asFamilyMember(): HasOne
    {
        return $this->hasOne(FamilyMember::class, 'dsid', 'dsid')
            ->orWhere('apple_id', $this->appleid);
    }

    /**
     * 判断是否为家庭组织者
     */
    public function isFamilyOrganizer(): bool
    {
        return $this->family()->exists();
    }

    /**
     * 获取账号关联的所有电话号码
     */
    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public function accountManager(): HasOne
    {
        return $this->hasOne(AccountManager::class);
    }

    public function dispatcher(): ?EventDispatcherInterface
    {
        return $this->eventDispatcher ??= app()->make(EventDispatcherInterface::class);
    }

    /**
     * 获取所有相关的家庭成员ID（用于调试）
     */
    public function getAllFamilyMemberIds(): array
    {
        // 作为组织者的家庭成员
        $asOrganizerMembers = FamilyMember::query()
            ->whereHas('family', function ($query) {
                $query->where('organizer', $this->dsid);
            })
            ->pluck('id')
            ->toArray();

        // 作为成员的同组成员
        $asMemberFamilyIds = FamilyMember::query()
            ->where(function ($query) {
                $query->where('dsid', $this->dsid)
                    ->orWhere('apple_id', $this->appleid);
            })
            ->pluck('family_id')
            ->toArray();

        $asMemberMembers = FamilyMember::query()
            ->whereIn('family_id', $asMemberFamilyIds)
            ->pluck('id')
            ->toArray();

        return array_unique(array_merge($asOrganizerMembers, $asMemberMembers));
    }
}
