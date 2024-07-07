<?php

namespace Tests\Unit;

use App\Apple\Service\AccountBind;
use App\Apple\Service\HttpClient;
use PHPUnit\Framework\TestCase;

class AccountBindTest extends TestCase
{

    protected AccountBind $accountBind;

    public function setUp(): void
    {
        $this->accountBind = \Mockery::mock(AccountBind::class)->makePartial();
    }


    public function testExtractSixDigitNumber()
    {
        $result = $this->accountBind->extractSixDigitNumber("Your Apple ID Code is: 157640. Don't share it with anyone.");

        $this->assertEquals('157640',$result);
    }
}
