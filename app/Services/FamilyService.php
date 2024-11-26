<?php

namespace App\Services;

use App\Exceptions\Family\FamilyException;
use App\Models\Account;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Services\Traits\HasFamilyMemberValidation;
use Illuminate\Support\Facades\DB;
use Modules\AppleClient\Service\AppleAccountManagerFactory;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\FamilyInfo;
use Modules\AppleClient\Service\DataConstruct\Icloud\ITunesAccountPaymentInfo\ITunesAccountPaymentInfo;
use Modules\AppleClient\Service\Integrations\Icloud\Dto\VerifyCVVRequestDto;


class FamilyService
{
    use HasFamilyMemberValidation;

    /**
     *
     * @param AppleAccountManagerFactory $accountManagerFactory
     * @param Account $account
     * @throws FamilyException
     */
    public function __construct(
        protected readonly AppleAccountManagerFactory $accountManagerFactory,
        protected readonly Account $account,
    ) {
        $this->accountManager = $this->accountManagerFactory->create($this->account);
        $this->initAppleAccountManager();
    }

    public static function make(Account $account): self
    {
        return new self(
            app(AppleAccountManagerFactory::class),
            $account,
        );
    }

    /**
     * 创建家庭共享
     */
    public function createFamily(Account $account, string $payAccount, string $payPassword): Family
    {
        $this->validateNotMember();

        return DB::transaction(function () use ($account, $payAccount, $payPassword) {
            $familyInfo = $this->accountManager->createFamily(
                $account->account,
                $payAccount,
                $payPassword
            );

            return $this->updateFamilyData($familyInfo);
        });
    }

    /**
     * 添加家庭成员
     */
    public function addFamilyMember(
        string $addAccount,
        string $addPassword,
        VerifyCVVRequestDto $dto
    ): Family {

        $this->validateIsMemberOfFamilyAndIsOrganizer();

        return DB::transaction(function () use ($addAccount, $addPassword, $dto) {
            $familyInfo = $this->accountManager->addFamilyMember(
                $addAccount,
                $addPassword,
                $dto
            );

            return $this->updateFamilyData($familyInfo);
        });
    }

    /**
     * 移除家庭成员
     */
    public function removeFamilyMember(FamilyMember $member): ?Family
    {
        $this->validateIsMemberOfFamilyAndIsOrganizer();

        return DB::transaction(function () use ($member) {

            $familyInfo = $this->accountManager->removeFamilyMember($member->dsid);

            $this->deleteFamilyData();

            return $this->updateFamilyData($familyInfo);
        });
    }

    /**
     * 退出家庭共享
     */
    public function leaveFamily(): void
    {
        $this->validateIsMember();
        $familyDetails = $this->getFamilyDetails();

        DB::transaction(function () use ($familyDetails) {
            if ($familyDetails->isFamilyOrganizer($this->accountManager->getAccount()->dsid)) {
                $this->handleOrganizerLeave();

                return;
            }

            FamilyMember::where('dsid', $this->accountManager->getAccount()->dsid)->delete();
        });
    }

    /**
     * 获取家庭成员支付信息
     */
    public function getITunesAccountPaymentInfo(): ITunesAccountPaymentInfo
    {
        $this->validateIsMemberOfFamilyAndIsOrganizer();

        $paymentInfo = $this->accountManager->getITunesAccountPaymentInfo();
        if (!$paymentInfo->isSuccess()) {
            throw new FamilyException($paymentInfo->statusMessage);
        }

        return $paymentInfo;
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
        return $this->accountManager->getAccount()->belongToFamily?->delete();
    }

    private function handleOrganizerLeave(): void
    {
        if (!$this->canOrganizerLeave()) {
            throw FamilyException::organizerCannotLeave();
        }

        $this->accountManager->leaveFamily();
        $this->deleteFamilyData();
    }
}
