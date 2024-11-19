<?php

use Illuminate\Foundation\Testing\TestCase;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyDetails\FamilyDetails;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyDetails\FamilyMember;
use Modules\AppleClient\Service\DataConstruct\Icloud\FamilyDetails\PendingMember;
use Modules\AppleClient\Service\Helpers\PlistXmlParser;
use Spatie\LaravelData\DataCollection;

uses(TestCase::class);


it('can parse plist xml', function () {

    $xmlContent = (file_get_contents(base_path('/Modules/AppleClient/tests/Unit/Files/getFamilyDetails.xml')));
    $parser     = new PlistXmlParser();
    $result     = $parser->xmlParse(simplexml_load_string($xmlContent));

    $familyDetails = FamilyDetails::from($result);

    expect($familyDetails)
        ->toBeInstanceOf(FamilyDetails::class)
        ->and($familyDetails->pendingMembers)
        ->toBeInstanceOf(DataCollection::class)
        ->and($familyDetails->pendingMembers->first())
        ->toBeInstanceOf(PendingMember::class)
        ->and($familyDetails->familyMembers)
        ->toBeInstanceOf(DataCollection::class)
        ->and($familyDetails->familyMembers->first())
        ->toBeInstanceOf(FamilyMember::class);
});


