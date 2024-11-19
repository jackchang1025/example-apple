<?php


use Illuminate\Support\Collection;
use Modules\AppleClient\Service\Helpers\PlistXmlParser;

it('correctly parses all fields from login.xml', function () {

    $xmlContent = file_get_contents(base_path('/Modules/AppleClient/tests/Unit/Files/login.xml'));
    $parser     = new PlistXmlParser();
    $result     = $parser->xmlParse(simplexml_load_string($xmlContent));

    // 测试基本字段
    expect($result->get('dsid'))->toBe('21905965912')
        ->and($result->get('status'))->toBe(0);

    // 测试 mobileme 服务
    $mobileme = $result->get('delegates')['com.apple.mobileme'];
    expect($mobileme['status'])->toBe(0)
        ->and($mobileme['service-data']['protocolVersion'])->toBe('3')
        ->and($mobileme['service-data']['tokens'])->toHaveKeys([
            'mmeFMFAppToken',
            'mapsToken',
            'mmeFMIPToken',
            'cloudKitToken',
            'mmeAuthToken',
            'mmeFMFToken',
        ]);

    // 测试 gamecenter 服务
    $gamecenter = $result->get('delegates')['com.apple.gamecenter'];
    expect($gamecenter['service-data'])->toHaveKeys([
        'allow-contact-lookup',
        'lastName',
        'last-updated',
        'alias',
        'auth-token',
        'player-id',
        'dsid',
        'firstName',
        'env',
        'apple-id',
    ])
        ->and($gamecenter['service-data']['firstName'])->toBe('chang')
        ->and($gamecenter['service-data']['lastName'])->toBe('jack')
        ->and($gamecenter['account-exists'])->toBeTrue();

    // 测试 private.ids 服务
    $privateIds = $result->get('delegates')['com.apple.private.ids'];
    expect($privateIds['service-data']['handles'])->toBeArray()
        ->and($privateIds['service-data']['handles'][0]['status'])->toBe(5051)
        ->and($privateIds['service-data']['handles'][0]['is-user-visible'])->toBeTrue()
        ->and($privateIds['service-data']['invitation-context']['base-phone-number'])->toBe('+860000000000')
        ->and($privateIds['service-data']['invitation-context']['region-id'])->toBe('R:CN');
});

describe('PlistParser', function () {
    beforeEach(function () {

        $this->xmlContent = file_get_contents(base_path('/Modules/AppleClient/tests/Unit/Files/getFamilyDetails.xml'));

        $this->parser = new PlistXmlParser();
        $this->result = $this->parser->xmlParse(simplexml_load_string($this->xmlContent));
    });

    it('returns a Collection instance', function () {
        expect($this->result)->toBeInstanceOf(Collection::class);
    });

    it('correctly parses basic fields', function () {
        expect($this->result)
            ->and($this->result->get('status-message'))->toBe('Success')
            ->and($this->result->get('dsid'))->toBe(21905965912)
            ->and($this->result->get('status'))->toBe(0)
            ->and($this->result->get('is-member-of-family'))->toBeTrue();
    });

    it('correctly parses pending members array', function () {
        $pendingMembers = $this->result->get('pending-members');

        expect($pendingMembers)
            ->toBeArray()
            ->toHaveCount(1);

        $pendingMember = $pendingMembers[0];
        expect($pendingMember)
            ->toHaveKey('member-invite-email', '674648134@qq.com')
            ->toHaveKey('member-status', 'Pending')
            ->toHaveKey('member-display-label', '邀请已发送');
    });

    it('correctly parses family members array', function () {
        $familyMembers = $this->result->get('family-members');

        expect($familyMembers)
            ->toBeArray()
            ->toHaveCount(2);

        // 测试第一个成员（组织者）
        $organizer = $familyMembers[0];
        expect($organizer)
            ->toHaveKey('member-first-name', 'chang')
            ->toHaveKey('member-last-name', 'jack')
            ->toHaveKey('member-sort-order', 1)
            ->toHaveKey('member-dsid', 21905965912)
            ->toHaveKey('member-apple-id', 'licade_2015@163.com')
            ->toHaveKey('member-status', 'Accepted')
            ->toHaveKey('member-display-label', '组织者')
            ->toHaveKey('member-type', 'ADULT')
            ->and($organizer['member-is-organizer'])->toBeTrue()
            ->and($organizer['member-is-parent-account'])->toBeTrue()
            ->and($organizer['is-me'])->toBeTrue()
            ->and($organizer['member-is-child-account'])->toBeFalse()
            ->and($organizer['is-ask-to-buy-enabled'])->toBeFalse();

        // 测试第二个成员（普通成员）
        $member = $familyMembers[1];
        expect($member)
            ->toHaveKey('member-first-name', 'chang')
            ->toHaveKey('member-last-name', 'jack')
            ->toHaveKey('member-sort-order', 301)
            ->toHaveKey('member-dsid', 20186096743)
            ->toHaveKey('member-apple-id', 'jackchang2021@163.com')
            ->toHaveKey('member-status', 'Accepted')
            ->toHaveKey('member-display-label', '成人')
            ->toHaveKey('member-type', 'ADULT')
            ->and($member['member-is-organizer'])->toBeFalse()
            ->and($member['member-is-parent-account'])->toBeFalse()
            ->and($member['is-me'])->toBeFalse()
            ->and($member['member-is-child-account'])->toBeFalse()
            ->and($member['is-ask-to-buy-enabled'])->toBeFalse();
    });

    it('correctly handles all data types', function () {
        $member = $this->result->get('family-members')[0];

        // 测试字符串类型
        expect($member['member-apple-id'])->toBeString();

        // 测试整数类型
        expect($member['member-sort-order'])->toBeInt();
        expect($member['member-dsid'])->toBeInt();

        // 测试布尔类型
        expect($member['member-is-organizer'])->toBeBool();
        expect($member['is-me'])->toBeBool();
        expect($member['member-is-child-account'])->toBeBool();
    });

    it('preserves all fields from XML', function () {
        $member = $this->result->get('family-members')[0];

        // 验证是否包含所有必要字段
        $requiredFields = [
            'member-first-name',
            'member-sort-order',
            'member-altDSID',
            'member-is-parent-account',
            'member-is-organizer',
            'member-dsid-hash',
            'is-me',
            'member-is-child-account',
            'member-type-enum',
            'is-itunes-linked',
            'member-dsid',
            'member-join-date-epoch',
            'member-status',
            'member-display-label',
            'member-invite-email',
            'member-type',
            'linked-itunes-account-dsid',
            'member-apple-id',
            'member-last-name',
            'is-ask-to-buy-enabled',
            'linked-itunes-account-appleid',
        ];

        foreach ($requiredFields as $field) {
            expect($member)->toHaveKey($field);
        }
    });

    it('correctly handles empty dict in XML', function () {
        // 确保解析器能正确处理空的dict元素
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
        <!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
        <plist version="1.0"><dict><key>empty</key><dict/></dict></plist>
XML;

        $result = $this->parser->xmlParse(simplexml_load_string($xml));

        expect($result->get('empty'))->toBeArray()->toBeEmpty();
    });

    it('maintains the correct structure of nested arrays and dicts', function () {
        // 验证嵌套结构的完整性
        expect($this->result->has('family-members'))->toBeTrue();
        expect($this->result->get('family-members'))->toBeArray();
        expect($this->result->get('pending-members'))->toBeArray();

        $familyMember = $this->result->get('family-members')[0];
        expect($familyMember)->toBeArray();
        expect($familyMember)->toHaveKey('member-first-name');
        expect($familyMember)->toHaveKey('member-last-name');
    });
});
