<?php

namespace Modules\AppleClient\Service\Trait;

use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyDetails\FamilyDetails;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\FamilyInfo;
use Modules\AppleClient\Service\DataConstruct\Icloud\ITunesAccountPaymentInfo\ITunesAccountPaymentInfo;
use Modules\AppleClient\Service\DataConstruct\Icloud\VerifyCVV\VerifyCVV;
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

    public function addFamilyMember(
        string $appleId,
        string $password,
        VerifyCVVRequestDto $dto
    ): FamilyInfo {
        /**
         * @var VerifyCVV $verifyCvv
         */
        $verifyCvv = $this->getIcloudConnector()
            ->getFamilyResources()
            ->verifyCVVRequest($dto)
            ->dto();

        if (!$verifyCvv->isSuccess()) {
            throw new RuntimeException($verifyCvv->statusMessage);
        }

        $addFamilyMember = $this->getIcloudConnector()
            ->getFamilyResources()
            ->addFamilyMemberRequest($appleId, $password, $verifyCvv->verificationToken)
            ->dto();

        if (!$addFamilyMember->isSuccess()) {
            throw new RuntimeException($addFamilyMember->statusMessage);
        }

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
