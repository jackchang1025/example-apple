<?php

namespace App\Services;

use App\Exceptions\Family\FamilyException;
use App\Models\Account;
use App\Models\Family;
use App\Models\FamilyMember;
use Illuminate\Support\Facades\DB;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Request\CreateFamily\CreateFamily;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Request\VerifyCVV\VerifyCVV;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\FamilyDetails\FamilyDetails;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\FamilyInfo\FamilyInfo;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\ITunesAccountPaymentInfo\ITunesAccountPaymentInfo;

readonly class FamilyService
{

    public function __construct(
        protected Account $apple
    ) {

    }

    /**
     * @param Account $apple
     * @return self
     */
    public static function make(Account $apple): self
    {
        return new self($apple);
    }

    public function getFamilyDetails(): FamilyDetails
    {
        return $this->apple->getFamilyResources()->getFamilyDetails();
    }

    public function getFamilyInfo(): FamilyInfo
    {
        return $this->apple->getFamilyResources()->getFamilyInfo();
    }

    /**
     * 创建家庭共享
     */
    public function createFamily(Account $account, string $payAccount, string $payPassword): Family
    {

        $familyInfo = $this->apple->getFamilyResources()->createFamily(
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
            ->getFamilyResources()
            ->addFamilyMember($addAccount, $addPassword, $data);

        return DB::transaction(fn() => $this->updateFamilyData($familyInfo));
    }

    /**
     * 移除家庭成员
     */
    public function removeFamilyMember(FamilyMember $member): ?Family
    {

        $familyInfo = $this->apple->getFamilyResources()->removeFamilyMember(
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
        $this->apple->getFamilyResources()->leaveFamily();

        DB::transaction(fn() => $this->deleteFamilyData());
    }

    /**
     * 获取家庭成员支付信息
     */
    public function getITunesAccountPaymentInfo(): ITunesAccountPaymentInfo
    {
        return $this->apple->getFamilyResources()->getITunesAccountPaymentInfo(
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
        return $this->apple->belongToFamily?->delete();
    }
}
