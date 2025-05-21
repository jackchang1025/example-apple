<?php

use Spatie\LaravelData\DataCollection;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\AccountManager\Account;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\AccountManager\AccountManager;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\AccountManager\AppleID;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\AccountManager\AlternateEmail;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\AccountManager\PageFeatures\PageFeatures; // Assuming this DTO exists
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\AccountManager\Person\Person; // Assuming this DTO exists
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\AccountManager\Security\Device;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\AccountManager\Security\PhoneNumber;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\AccountManager\Security\Security; // Assuming this DTO exists
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\AccountManager\Preferences\Preferences; // Assuming this DTO exists

beforeEach(function () {
    $data = getFixturesFile('account-manager.json');
    $this->accountManagerData = AccountManager::from($data);
});

test('AccountManager DTO is correctly hydrated from JSON', function () {
    $accountManager = $this->accountManagerData;

    expect($accountManager)->toBeInstanceOf(AccountManager::class)
        ->and($accountManager->apiKey)->toBe('cbf64fd6843ee630b463f358ea0b707b')
        ->and($accountManager->isHsaEligible)->toBeTrue()
        ->and($accountManager->loginHandleAvailable)->toBeTrue()
        ->and($accountManager->isAppleIdAndPrimaryEmailSame)->toBeTrue()
        ->and($accountManager->appleIDDisplay)->toBe('xxxxxxxxxxx')
        ->and($accountManager->nameOrder)->toBe('firstName/lastName')
        ->and($accountManager->appleID)->toBeInstanceOf(AppleID::class)
        ->and($accountManager->appleID->editable)->toBeTrue()
        ->and($accountManager->appleID->domain)->toBe('163.com')
        ->and($accountManager->pageFeatures)->toBeInstanceOf(PageFeatures::class)
        ->and($accountManager->pageFeatures->shouldEnableAppleIDRebrand)->toBeTrue()
        ->and($accountManager->pageFeatures->defaultCountry)->toBe('USA')
        ->and($accountManager->alternateEmailAddresses)->toBeArray()->toBeEmpty()
        ->and($accountManager->addAlternateEmail)->toBeInstanceOf(AlternateEmail::class)
        ->and($accountManager->addAlternateEmail->emailAddress)->toBe('')
        ->and($accountManager->addAlternateEmail->verified)->toBeFalse()
        ->and($accountManager->appleID)->toBeInstanceOf(AppleID::class)
        ->and($accountManager->appleID->nonFTEUEnabled)->toBeFalse()
        ->and($accountManager->appleID->editable)->toBeTrue()
        ->and($accountManager->appleID->appleOwnedDomain)->toBeFalse()
        ->and($accountManager->appleID->domain)->toBe('163.com');

    expect($accountManager->account)->toBeInstanceOf(Account::class)
        ->and($accountManager->account->makoNumberRetainable)->toBeFalse()
        ->and($accountManager->account->localizedLastPasswordChangedDate)->toBe('August 26, 2024')
        ->and($accountManager->account->recycled)->toBeFalse()
        ->and($accountManager->account->beneficiaryCount)->toBe(0)
        ->and($accountManager->account->custodianCount)->toBe(0)
        ->and($accountManager->account->recoveryKeyEnabled)->toBeFalse()
        ->and($accountManager->account->dataRecoveryServiceStatusReadableOnUI)->toBeTrue()
        ->and($accountManager->account->lastPasswordChangedDate)->toBe('2024-08-26')
        ->and($accountManager->account->appleIDEditable)->toBeTrue()
        ->and($accountManager->account->lastPasswordChangedDatetime)->toBe('2024-08-26 05:32:00')
        ->and($accountManager->account->paymentMethodStatus)->toBe('NOT_LOADED')
        ->and($accountManager->account->type)->toBe('hsa2')
        ->and($accountManager->account->person)->toBeInstanceOf(Person::class)
        ->and($accountManager->account->person->accountName)->toBe('xxxxxxxxxxx')
        ->and($accountManager->account->security)->toBeInstanceOf(Security::class)
        ->and($accountManager->account->security->hsa2Eligible)->toBeFalse()
        ->and($accountManager->account->preferences)->toBeInstanceOf(Preferences::class)
        ->and($accountManager->account->preferences->preferredLanguage)->toBe('zh_CN')
        ->and($accountManager->account->security)->toBeInstanceOf(Security::class)
        ->and($accountManager->account->security->birthday)->toBe('1996-10-25')
        ->and($accountManager->account->security->devices)->toBeInstanceOf(DataCollection::class)
        ->and($accountManager->account->security->devices->toCollection()->first())->toBeInstanceOf(Device::class)
        ->and($accountManager->account->security->phoneNumbers)->toBeInstanceOf(DataCollection::class)
        ->and($accountManager->account->security->phoneNumbers->toCollection()->first())->toBeInstanceOf(PhoneNumber::class)
    ;

});
