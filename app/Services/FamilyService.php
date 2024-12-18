<?php

namespace App\Services;

use App\Exceptions\Family\FamilyException;
use App\Models\Account;
use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Support\Facades\DB;
use Modules\AppleClient\Service\Apple;
use Modules\AppleClient\Service\AppleBuilder;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\CreateFamily\CreateFamily;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\FamilyDetails\FamilyDetails;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\FamilyInfo\FamilyInfo;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Request\VerifyCVV\VerifyCVV;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\Response\ITunesAccountPaymentInfo\ITunesAccountPaymentInfo;

readonly class FamilyService
{


    /**
     *
     * @param Apple $apple
     */
    public function __construct(
        protected Apple $apple
    ) {

    }

    /**
     * @param Account $apple
     * @return self
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public static function make(Account $apple): self
    {
        return new self(app(AppleBuilder::class)->build($apple->toAccount()));
    }

    public function getFamilyDetails(): FamilyDetails
    {
        return $this->apple->getApiResources()->getIcloudResource()->getFamilyResources()->getFamilyDetails();
    }

    public function getFamilyInfo(): FamilyInfo
    {
        return $this->apple->getApiResources()->getIcloudResource()->getFamilyResources()->getFamilyInfo();
    }

    /**
     * 创建家庭共享
     */
    public function createFamily(Account $account, string $payAccount, string $payPassword): Family
    {

        $familyInfo = $this->apple->getApiResources()->getIcloudResource()->getFamilyResources()->createFamily(
            CreateFamily::from([
                'organizerAppleId'                     => $account->account,
                'organizerAppleIdForPurchases'         => $payAccount,
                'organizerAppleIdForPurchasesPassword' => $payPassword,
            ])
        );

        return DB::transaction(fn() => $this->updateFamilyData($familyInfo));
    }

    /**
     * 添加家庭成员
     * @param string $addAccount
     * @param string $addPassword
     * @param VerifyCVV $data
     * @return null|Family
     * @throws FamilyException
     * @throws \Throwable
     * @throws FamilyException
     */
    public function addFamilyMember(string $addAccount, string $addPassword, VerifyCVV $data): ?Family
    {

        $familyInfo = $this->apple
            ->getApiResources()
            ->getIcloudResource()
            ->getFamilyResources()
            ->addFamilyMember($addAccount, $addPassword, $data);

        return DB::transaction(fn() => $this->updateFamilyData($familyInfo));
    }

    /**
     * 移除家庭成员
     */
    public function removeFamilyMember(FamilyMember $member): ?Family
    {

        $familyInfo = $this->apple->getApiResources()->getIcloudResource()->getFamilyResources()->removeFamilyMember(
            $member->dsid
        );

        return DB::transaction(function () use ($familyInfo) {

            $this->deleteFamilyData();

            return $this->updateFamilyData($familyInfo);
        });
    }

    /**
     * 退出家庭共享
     */
    public function leaveFamily(): void
    {
        $this->apple->getApiResources()->getIcloudResource()->getFamilyResources()->leaveFamily();

        DB::transaction(fn() => $this->deleteFamilyData());
    }

    /**
     * 获取家庭成员支付信息
     */
    public function getITunesAccountPaymentInfo(): ITunesAccountPaymentInfo
    {
        return $this->apple->getApiResources()->getIcloudResource()->getFamilyResources()->getITunesAccountPaymentInfo(
        );
    }

    public function updateFamilyData(FamilyInfo $familyInfo): ?Family
    {
        $family = $familyInfo->updateOrCreate();
        if ($family) {
            $familyInfo->updateOrCreateFamilyMembers($family->id);
        }

        return $family;
    }

    public function deleteFamilyData(): ?bool
    {
        return $this->apple->getAccount()->model()?->belongToFamily?->delete();
    }
}
