<?php

namespace Modules\AppleClient\Service\Integrations\Icloud\Resources;

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

    public function addFamilyMemberRequest(
        string $appleId,
        string $password,
        string $verificationToken,
        bool $shareMyLocationEnabledDefault = true,
        bool $shareMyPurchasesEnabledDefault = true
    ): Response {
        return $this->getConnector()
            ->send(
                new AddFamilyMemberRequest(
                    appleId: $appleId,
                    password: $password,
                    appleIdForPurchases: $appleId,
                    verificationToken: $verificationToken,
                    preferredAppleId: $appleId,
                    shareMyLocationEnabledDefault: $shareMyLocationEnabledDefault,
                    shareMyPurchasesEnabledDefault: $shareMyPurchasesEnabledDefault
                )
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
