<?php

use Modules\AppleClient\Service\Exception\BindPhoneException;
use Modules\AppleClient\Service\Exception\PhoneException;
use Modules\AppleClient\Service\Exception\PhoneNumberAlreadyExistsException;
use Modules\AppleClient\Service\Exception\StolenDeviceProtectionException;
use Modules\AppleClient\Service\Exception\VerificationCodeSentTooManyTimesException;
use Modules\AppleClient\Service\Integrations\AppleId\Dto\Response\SecurityVerifyPhone\SecurityVerifyPhone;
use Modules\AppleClient\Service\Integrations\AppleId\Resources\SecurityPhoneResources;
use Modules\AppleClient\Service\Response\Response;
use Saloon\Exceptions\Request\ClientException;
use Saloon\Exceptions\Request\FatalRequestException;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->connector = Mockery::mock(SecurityPhoneResources::class)->makePartial();
});

// 测试正常流程
test('securityVerifyPhone 正常流程', function () {
    $mockResponse = Mockery::mock(Response::class);
    $mockDto      = Mockery::mock(SecurityVerifyPhone::class);
    $mockResponse->shouldReceive('dto')->andReturn($mockDto);

    $this->connector->shouldReceive('getConnector->send')
        ->once()
        ->andReturn($mockResponse);

    $result = $this->connector->securityVerifyPhone('CN', '13800138000', '+86');

    expect($result)->toBeInstanceOf(SecurityVerifyPhone::class);
});

// 测试验证码发送次数过多异常（状态码423）
test('securityVerifyPhone 抛出 VerificationCodeSentTooManyTimesException 当状态码为423', function () {
    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('status')->andReturn(423);
    $mockResponse->shouldReceive('body')->andReturn(
        '{"serviceErrors" : [ {
      "code" : "-22981",
      "message" : "发送验证码的次数过多。输入你最后收到的验证码，或稍后再试。",
      "suppressDismissal" : false
    } ]}'
    );
    $mockResponse->shouldReceive('getFirstServiceError->getMessage')->andReturn('验证码发送次数过多');

    $mockException = Mockery::mock(ClientException::class);
    $mockException->shouldReceive('getResponse')->andReturn($mockResponse);

    $this->connector->shouldReceive('getConnector->send')
        ->once()
        ->andThrow($mockException);

    $this->connector->securityVerifyPhone('CN', '13800138000', '+86');
})->throws(VerificationCodeSentTooManyTimesException::class);

// 测试失窃设备保护异常（状态码467）
test('securityVerifyPhone 抛出 StolenDeviceProtectionException 当状态码为467', function () {
    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('status')->andReturn(467);
    $mockResponse->shouldReceive('body')->andReturn('{"error": "stolen device protection"}');
    $mockResponse->shouldReceive('getFirstServiceError->getMessage')->andReturn('错误信息');

    $mockException = Mockery::mock(ClientException::class);
    $mockException->shouldReceive('getResponse')->andReturn($mockResponse);

    $this->connector->shouldReceive('getConnector->send')
        ->once()
        ->andThrow($mockException);

    $this->connector->securityVerifyPhone('CN', '13800138000', '+86');
})->throws(StolenDeviceProtectionException::class);

// 测试手机号异常（错误码-28248）
test('securityVerifyPhone 抛出 PhoneException 当错误码为-28248', function () {
    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('status')->andReturn(400);
    $mockResponse->shouldReceive('body')->andReturn(
        '{"service_errors" : [ {
    "code" : "-28248",
    "message" : "验证码无法发送至该电话号码。请稍后重试。",
    "suppressDismissal" : false
  } ],
  "hasError" : true
}"}'
    );
    $mockResponse->shouldReceive('getFirstServiceError->getCode')->andReturn(-28248);

    $mockException = Mockery::mock(ClientException::class);
    $mockException->shouldReceive('getResponse')->andReturn($mockResponse);

    $this->connector->shouldReceive('getConnector->send')
        ->once()
        ->andThrow($mockException);

    $this->connector->securityVerifyPhone('CN', '13800138000', '+86');
})->throws(PhoneException::class);

// 测试手机号已存在异常（错误码phone.number.already.exists）
test('securityVerifyPhone 抛出 PhoneNumberAlreadyExistsException 当错误码为phone.number.already.exists', function () {
    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('status')->andReturn(400);
    $mockResponse->shouldReceive('body')->andReturn('{"error": {"code": "phone.number.already.exists"}}');
    $mockResponse->shouldReceive('getFirstServiceError->getCode')->andReturn('phone.number.already.exists');

    $mockException = Mockery::mock(ClientException::class);
    $mockException->shouldReceive('getResponse')->andReturn($mockResponse);

    $this->connector->shouldReceive('getConnector->send')
        ->once()
        ->andThrow($mockException);

    $this->connector->securityVerifyPhone('CN', '13800138000', '+86');
})->throws(PhoneNumberAlreadyExistsException::class);

// 测试其他绑定异常
test('securityVerifyPhone 抛出 BindPhoneException 当遇到未处理的异常', function () {
    $mockResponse = Mockery::mock(Response::class);
    $mockResponse->shouldReceive('status')->andReturn(400);
    $mockResponse->shouldReceive('body')->andReturn('{"error": {"code": "unknown.error"}}');
    $mockResponse->shouldReceive('getFirstServiceError->getCode')->andReturn('unknown.error');

    $mockException = Mockery::mock(ClientException::class);
    $mockException->shouldReceive('getResponse')->andReturn($mockResponse);

    $this->connector->shouldReceive('getConnector->send')
        ->once()
        ->andThrow($mockException);

    $this->connector->securityVerifyPhone('CN', '13800138000', '+86');
})->throws(BindPhoneException::class);

// 测试FatalRequestException异常
test('securityVerifyPhone 抛出 FatalRequestException 当遇到致命请求错误', function () {
    $mockException = Mockery::mock(FatalRequestException::class);

    $this->connector->shouldReceive('getConnector->send')
        ->once()
        ->andThrow($mockException);

    $this->connector->securityVerifyPhone('CN', '13800138000', '+86');
})->throws(FatalRequestException::class);
