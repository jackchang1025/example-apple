<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Resources;

use Modules\AppleClient\Service\Integrations\Icloud\Dto\AddFamilyMemberData;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\VerifyCVVRequestDto;
use Modules\AppleClient\Service\Integrations\Icloud\Request\AddFamilyMemberRequest;
use Modules\AppleClient\Service\Integrations\Icloud\Request\CreateFamilyRequest;
use Modules\AppleClient\Service\Integrations\Icloud\Request\GetFamilyDetailsRequest;
use Modules\AppleClient\Service\Integrations\Icloud\Request\GetITunesAccountPaymentInfoRequest;
use Modules\AppleClient\Service\Integrations\Icloud\Request\GetMaxFamilyDetailsRequest;
use Modules\AppleClient\Service\Integrations\Icloud\Request\LeaveFamilyRequest;
use Modules\AppleClient\Service\Integrations\Icloud\Request\RemoveFamilyMemberRequest;
use Modules\AppleClient\Service\Integrations\Icloud\Request\VerifyCVVRequest;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

class FamilyResources extends Resources
{


    /**
     * @return Response
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function getFamilyDetailsRequest(): Response
    {
        return $this->getConnector()
            ->send(new GetFamilyDetailsRequest());
    }


    public function createFamilyRequest(
        string $organizerAppleId,
        string $organizerAppleIdForPurchases,
        string $organizerAppleIdForPurchasesPassword,
        bool $organizerShareMyLocationEnabledDefault = true,
        int $iTunesTosVersion = 284005
    ): Response {
        return $this->getConnector()
            ->send(
                new CreateFamilyRequest(
                    $organizerAppleId,
                    $organizerAppleIdForPurchases,
                    $organizerAppleIdForPurchasesPassword,
                    $organizerShareMyLocationEnabledDefault,
                    $iTunesTosVersion
                )
            );
    }

    public function leaveFamilyRequest(): Response
    {
        return $this->getConnector()
            ->send(new LeaveFamilyRequest());
    }

    /**
     * 发起添加家庭成员的请求
     *
     * 此方法用于向Apple账户服务发送一个添加新家庭成员的请求它需要家庭成员的Apple ID、密码和验证令牌，
     * 以及两个可选的布尔参数，用于控制新成员是否默认启用位置共享和购买内容共享
     *
     * @param AddFamilyMemberData $data 包含家庭成员Apple ID、密码和验证令牌的AddFamilyMemberData对象
     * @return Response 返回发送请求后的响应对象
     * @throws FatalRequestException
     * @throws RequestException
     */
    public function addFamilyMemberRequest(AddFamilyMemberData $data): Response
    {
        return $this->getConnector()
            ->send(
                new AddFamilyMemberRequest($data)
            );
    }

    public function removeFamilyMemberRequest(string|int $dsid): Response
    {
        return $this->getConnector()
            ->send(
                new RemoveFamilyMemberRequest($dsid)
            );
    }

    public function verifyCVVRequest(
        VerifyCVVRequestDto $dto
    ): Response {
        return $this->getConnector()
            ->send(
                new VerifyCVVRequest($dto)
            );
    }

    public function getMaxFamilyDetailsRequest(): Response
    {
        return $this->getConnector()
            ->send(
                new GetMaxFamilyDetailsRequest()
            );
    }

    public function getITunesAccountPaymentInfoRequest(
        string $organizerDSID,
        string $userAction = "ADDING_FAMILY_MEMBER",
        bool $sendSMS = true
    ): Response {
        return $this->getConnector()->send(
            new GetITunesAccountPaymentInfoRequest($organizerDSID, $userAction, $sendSMS)
        );
    }
}
