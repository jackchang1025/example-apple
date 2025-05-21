<?php

use Illuminate\Foundation\Testing\TestCase;
use Spatie\LaravelData\DataCollection;
use Weijiajia\SaloonphpAppleClient\Helpers\PlistXmlParser;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\FamilyDetails\FamilyDetails;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\FamilyDetails\PendingMember;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Dto\Response\FamilyDetails\FamilyMember;



it('can parse plist xml', function () {

    $xmlContent = getFixturesFile("getFamilyDetails.xml");
    $parser     = new PlistXmlParser();
    $result     = $parser->xmlParse(simplexml_load_string($xmlContent));

    $familyDetails = FamilyDetails::from($result);

    expect($familyDetails)
        ->toBeInstanceOf(FamilyDetails::class)
        ->and($familyDetails->pendingMembers)
        ->toBeInstanceOf(DataCollection::class)
        ->and($familyDetails->pendingMembers->toCollection()->first())
        ->toBeInstanceOf(PendingMember::class)
        ->and($familyDetails->familyMembers)
        ->toBeInstanceOf(DataCollection::class)
        ->and($familyDetails->familyMembers->toCollection()->first())
        ->toBeInstanceOf(FamilyMember::class);
});


