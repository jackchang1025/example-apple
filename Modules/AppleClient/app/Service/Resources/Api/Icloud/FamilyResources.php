<?php

namespace Modules\AppleClient\Service\Resources\Api\Icloud;

use Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\CreateFamily\CreateFamily;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\FamilyDetails\FamilyDetails;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\FamilyInfo\FamilyInfo;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;
use App\Exceptions\Family\FamilyException;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\ITunesAccountPaymentInfo\ITunesAccountPaymentInfo;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\VerifyCVV\VerifyCVV;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\AddFamilyMember\AddFamilyMember;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\leaveFamily\leaveFamily;
use Modules\AppleClient\Events\Family\FamilyCreatedEvent;
use Modules\AppleClient\Events\Family\FamilyMemberAddedEvent;
use Modules\AppleClient\Events\Family\FamilyMemberRemovedEvent;
use Modules\AppleClient\Events\Family\FamilyLeftEvent;

class FamilyResources
{
    protected ?FamilyInfo $familyInfo = null;

    protected ?FamilyDetails $familyDetails = null;

    public function __construct(protected IcloudResource $resource)
    {
    }

    public function getResource(): IcloudResource
    {
        return $this->resource;
    }

    public function getFamilyInfo(): FamilyInfo
    {
        return $this->familyInfo ??= $this->fetchFamilyInfo();
    }

    public function getFamilyDetails(): FamilyDetails
    {
        return $this->familyDetails ??= $this->fetchFamilyDetails();
    }

    /**
     * @return FamilyDetails
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function fetchFamilyDetails(): FamilyDetails
    {
        return $this->getResource()->getIcloudConnector()
            ->getFamilyResources()
            ->getFamilyDetailsRequest();
    }

    /**
     * 创建家庭
     *
     * 此方法首先验证家庭是否已存在，然后创建家庭
     * 它依赖于iCloud连接器和家庭资源服务来执行操作
     *
     * @param CreateFamily $data 创建家庭的请求数据传输对象
     * @return FamilyInfo 成功创建家庭后的信息
     * @throws FatalRequestException
     * @throws RequestException
     * @throws FamilyException
     */
    public function createFamily(CreateFamily $data): FamilyInfo
    {
        if ($this->getFamilyInfo()->isMemberOfFamily) {
            throw FamilyException::alreadyMember();
        }

        $familyInfo = $this->getResource()->getIcloudConnector()
            ->getFamilyResources()
            ->createFamilyRequest($data);

        if (!$familyInfo->isSuccess()) {
            throw new FamilyException($familyInfo->statusMessage);
        }

        $this->getResource()->getResource()->getApple()->getDispatcher()?->dispatch(
            new FamilyCreatedEvent($familyInfo)
        );

        return $familyInfo;
    }

    /**
     * 获取家庭信息
     * @return FamilyInfo
     * @throws FatalRequestException
     * @throws RequestException|FamilyException
     */
    public function fetchFamilyInfo(): FamilyInfo
    {
        $familyInfo = $this->getResource()->getIcloudConnector()
            ->getFamilyResources()
            ->getMaxFamilyDetailsRequest();

        if (!$familyInfo->isSuccess()) {
            throw new FamilyException($familyInfo->statusMessage);
        }

        return $familyInfo;
    }

    /**
     * 获取iTunes账户支付信息
     * @return ITunesAccountPaymentInfo
     * @throws FatalRequestException
     * @throws RequestException
     * @throws FamilyException
     */
    public function getITunesAccountPaymentInfo(): ITunesAccountPaymentInfo
    {
        $authenticate = $this->getResource()->getAuthenticationResource()->getAuthenticate();

        if (!$authenticate) {
            throw FamilyException::loginInvalid();
        }

        if (!$this->getFamilyInfo()->isMemberOfFamily) {
            throw FamilyException::notFamilyMember();
        }

        if (!$this->getFamilyInfo()->isFamilyOrganizer($authenticate->appleAccountInfo->dsid)) {
            throw FamilyException::notOrganizer();
        }

        $paymentInfo = $this->getResource()->getIcloudConnector()
            ->getFamilyResources()
            ->getITunesAccountPaymentInfoRequest($authenticate->appleAccountInfo->dsid);

        if (!$paymentInfo->isSuccess()) {
            throw new FamilyException($paymentInfo->statusMessage);
        }

        return $paymentInfo;
    }

    /**
     * 添加家庭成员
     *
     * 此方法首先验证CVV信息，然后使用提供的Apple ID和密码添加新成员到家庭中
     * 它依赖于iCloud连接器和家庭资源服务来执行操作
     *
     * @param string $appleId 新成员的Apple ID
     * @param string $password 新成员的密码
     * @param VerifyCVV $data
     * @return FamilyInfo 成功添加家庭成员后的信息
     * @throws FamilyException
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function addFamilyMember(string $appleId, string $password, VerifyCVV $data): FamilyInfo
    {

        if (!$this->getFamilyInfo()->isMemberOfFamily) {
            throw FamilyException::notFamilyMember();
        }

        if (!$this->getFamilyInfo()->isFamilyOrganizer(
            $this->getResource()->getAuthenticationResource()->getAuthenticate()->appleAccountInfo->dsid
        )) {
            throw FamilyException::notOrganizer();
        }

        /**
         * 验证CVV信息
         *
         * 通过iCloud连接器的FamilyResources服务发送CVV验证请求，并获取验证结果
         *
         */
        $verifyCvv = $this->getResource()->getIcloudConnector()
            ->getFamilyResources()
            ->verifyCVVRequest($data);

        // 如果CVV验证失败，抛出异常
        if (!$verifyCvv->isSuccess()) {
            throw new FamilyException($verifyCvv->statusMessage);
        }

        /**
         * 准备添加家庭成员所需的数据
         * @param string $appleId 家庭成员的Apple ID
         * @param string $password 家庭成员的密码
         * @param string $verificationToken 验证令牌，用于确认添加请求的合法性
         * @param string $appleIdForPurchases 这是用于购买内容的 Apple ID。通常情况下，这个值与 $appleId 相同，表示家庭成员使用同一个 Apple ID 进行购买
         * @param string $preferredAppleId 这是首选的 Apple ID。在某些情况下，用户可能会有多个 Apple ID，这个参数指定了在家庭设置中优先使用的 Apple ID
         * @param bool $shareMyLocationEnabledDefault 是否默认启用位置共享，默认为true
         * @param bool $shareMyPurchasesEnabledDefault 是否默认启用购买内容共享，默认为true
         */
        $data = AddFamilyMember::from([
            'appleId'                        => $appleId,
            'password'                       => $password,
            'verificationToken'              => $verifyCvv->verificationToken,
            'appleIdForPurchases'            => $appleId,
            'preferredAppleId'               => $appleId,
            'shareMyLocationEnabledDefault'  => true,
            'shareMyPurchasesEnabledDefault' => true,
        ]);

        // 发送添加家庭成员请求，并获取响应数据传输对象
        $addFamilyMember = $this->getResource()->getIcloudConnector()
            ->getFamilyResources()
            ->addFamilyMemberRequest($data);

        // 如果添加家庭成员请求失败，抛出异常
        if (!$addFamilyMember->isSuccess()) {
            throw new FamilyException($addFamilyMember->statusMessage);
        }

        $this->getResource()->getResource()->getApple()->getDispatcher()?->dispatch(
            new FamilyMemberAddedEvent($addFamilyMember)
        );

        // 返回成功添加的家庭成员信息
        return $addFamilyMember;
    }

    /**
     * 移除家庭成员
     * @param string $appleId 要移除的家庭成员的Apple ID
     * @return FamilyInfo 移除家庭成员后的信息
     * @throws FatalRequestException
     * @throws RequestException
     * @throws FamilyException
     */
    public function removeFamilyMember(string $appleId): FamilyInfo
    {
        if (!$this->getFamilyInfo()->isFamilyOrganizer(
            $this->getResource()->getAuthenticationResource()->getAuthenticate()->appleAccountInfo->dsid
        )) {
            throw FamilyException::notOrganizer();
        }

        $response = $this->getResource()->getIcloudConnector()
            ->getFamilyResources()
            ->removeFamilyMemberRequest($appleId);

        if (!$response->isSuccess()) {
            throw new FamilyException($response->statusMessage);
        }

        $this->getResource()->getResource()->getApple()->getDispatcher()?->dispatch(
            new FamilyMemberRemovedEvent($response)
        );

        return $response;
    }

    /**
     * 退出家庭
     * @return leaveFamily
     * @throws FatalRequestException
     * @throws RequestException
     * @throws FamilyException
     */
    public function leaveFamily(): leaveFamily
    {
        if (!$this->getFamilyInfo()->isMemberOfFamily) {
            throw FamilyException::notFamilyMember();
        }

        if ($this->getFamilyInfo()->isFamilyOrganizer(
            $this->getResource()->getAuthenticationResource()->getAuthenticate()->appleAccountInfo->dsid
        )) {
            throw FamilyException::organizerCannotLeave();
        }

        $leaveFamily = $this->getResource()->getIcloudConnector()
            ->getFamilyResources()
            ->leaveFamilyRequest();

        if (!$leaveFamily->isSuccess()) {
            throw new FamilyException($leaveFamily->statusMessage);
        }

        $this->getResource()->getResource()->getApple()->getDispatcher()?->dispatch(new FamilyLeftEvent($leaveFamily));

        return $leaveFamily;
    }
}