<?php

namespace Modules\AppleClient\Service\Trait;

use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyDetails\FamilyDetails;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\FamilyInfo;
use Modules\AppleClient\Service\DataConstruct\Icloud\ITunesAccountPaymentInfo\ITunesAccountPaymentInfo;
use Modules\AppleClient\Service\DataConstruct\Icloud\VerifyCVV\VerifyCVV;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\AddFamilyMemberData;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\VerifyCVVRequestDto;
use RuntimeException;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

trait HasFamily
{
    /**
     * @return FamilyDetails
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getFamilyDetails(): FamilyDetails
    {
        return $this->getIcloudConnector()
            ->getFamilyResources()
            ->getFamilyDetailsRequest()
            ->dto();
    }


    public function createFamily(
        string $organizerAppleId,
        string $organizerAppleIdForPurchases,
        string $organizerAppleIdForPurchasesPassword,
    ): FamilyInfo {
        $familyInfo = $this->getIcloudConnector()
            ->getFamilyResources()
            ->createFamilyRequest(
                $organizerAppleId,
                $organizerAppleIdForPurchases,
                $organizerAppleIdForPurchasesPassword
            )
            ->dto();

        if (!$familyInfo->isSuccess()) {
            throw new RuntimeException($familyInfo->statusMessage);
        }

        return $familyInfo;
    }

    public function getMaxFamilyDetailsRequest(): FamilyInfo
    {
        $familyInfo = $this->getIcloudConnector()
            ->getFamilyResources()
                ->getMaxFamilyDetailsRequest()
                ->dto();

        if (!$familyInfo->isSuccess()) {
            throw new RuntimeException($familyInfo->statusMessage);
        }

        return $familyInfo;
    }

    public function getITunesAccountPaymentInfo(): ITunesAccountPaymentInfo
    {
        return $this->getIcloudConnector()
            ->getFamilyResources()
            ->getITunesAccountPaymentInfoRequest($this->getAuthenticate()->appleAccountInfo->dsid)
            ->dto();
    }

    /**
     * 添加家庭成员
     *
     * 此方法首先验证CVV信息，然后使用提供的Apple ID和密码添加新成员到家庭中
     * 它依赖于iCloud连接器和家庭资源服务来执行操作
     *
     * @param string $appleId 新成员的Apple ID
     * @param string $password 新成员的密码
     * @param VerifyCVVRequestDto $dto CVV验证请求数据传输对象
     * @return FamilyInfo 成功添加家庭成员后的信息
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function addFamilyMember(
        string $appleId,
        string $password,
        VerifyCVVRequestDto $dto
    ): FamilyInfo {
        /**
         * 验证CVV信息
         *
         * 通过iCloud连接器的FamilyResources服务发送CVV验证请求，并获取验证结果
         *
         * @var VerifyCVV $verifyCvv
         */
        $verifyCvv = $this->getIcloudConnector()
            ->getFamilyResources()
            ->verifyCVVRequest($dto)
            ->dto();

        // 如果CVV验证失败，抛出异常
        if (!$verifyCvv->isSuccess()) {
            throw new RuntimeException($verifyCvv->statusMessage);
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
        $data = AddFamilyMemberData::from([
            'appleId'                        => $appleId,
            'password'                       => $password,
            'verificationToken'              => $verifyCvv->verificationToken,
            'appleIdForPurchases'            => $appleId,
            'preferredAppleId'               => $appleId,
            'shareMyLocationEnabledDefault'  => true,
            'shareMyPurchasesEnabledDefault' => true,
        ]);

        // 发送添加家庭成员请求，并获取响应数据传输对象
        $addFamilyMember = $this->getIcloudConnector()
            ->getFamilyResources()
            ->addFamilyMemberRequest($data)
            ->dto();

        // 如果添加家庭成员请求失败，抛出异常
        if (!$addFamilyMember->isSuccess()) {
            throw new RuntimeException($addFamilyMember->statusMessage);
        }

        // 返回成功添加的家庭成员信息
        return $addFamilyMember;
    }

    public function removeFamilyMember(string $appleId): FamilyInfo
    {
        /**
         * @var FamilyInfo $response
         */
        $response = $this->getIcloudConnector()
            ->getFamilyResources()
            ->removeFamilyMemberRequest($appleId)
            ->dto();

        if (!$response->isSuccess()) {
            throw new RuntimeException($response->statusMessage);
        }

        return $response;
    }

    public function leaveFamily(): FamilyInfo
    {
        $familyInfo = $this->getIcloudConnector()
            ->getFamilyResources()
            ->leaveFamilyRequest()
            ->dto();
        if (!$familyInfo->isSuccess()) {
            throw new RuntimeException($familyInfo->statusMessage);
        }

        return $familyInfo;
    }
}
