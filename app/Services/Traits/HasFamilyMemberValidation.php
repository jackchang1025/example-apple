<?php

namespace App\Services\Traits;

use App\Exceptions\Family\FamilyException;
use Modules\AppleClient\Service\AppleAccountManager;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyInfo\FamilyInfo;

trait HasFamilyMemberValidation
{
    protected AppleAccountManager $accountManager;
    protected ?FamilyInfo $familyDetails = null;

    /**
     * 初始化账号管理器
     */
    protected function initAppleAccountManager(): void
    {
        if (!$this->accountManager->isLoginValid()) {
            throw FamilyException::loginInvalid();
        }
        $this->accountManager->refreshLoginState();
    }

    /**
     * 获取家庭详情
     */
    public function getFamilyDetails(): FamilyInfo
    {
        return $this->familyDetails ??= $this->accountManager->getMaxFamilyDetailsRequest();
    }

    public function refreshFamilyDetails(): void
    {
        $this->familyDetails = null;
        $this->getFamilyDetails();
    }

    /**
     * 验证是否为家庭成员
     */
    public function validateIsMember(): void
    {
        if (!$this->getFamilyDetails()->isMemberOfFamily) {
            throw FamilyException::notFamilyMember();
        }
    }

    /**
     * 验证不是家庭成员
     */
    public function validateNotMember(): void
    {
        if ($this->getFamilyDetails()->isMemberOfFamily) {
            throw FamilyException::alreadyMember();
        }
    }

    /**
     * 验证是否为组织者
     */
    public function validateIsOrganizer(): void
    {
        if (!$this->getFamilyDetails()->isFamilyOrganizer($this->accountManager->getAccount()->dsid)) {
            throw FamilyException::notOrganizer();
        }
    }

    /**
     * @return bool
     * @throws FamilyException
     */
    public function validateIsMemberOfFamilyAndIsOrganizer(): bool
    {
        $this->validateIsMember();
        $this->validateIsOrganizer();

        return true;
    }

    /**
     * 检查组织者是否可以退出
     */
    protected function canOrganizerLeave(): bool
    {
        return $this->getFamilyDetails()->familyMembers->count() < 2;
    }
}
