<?php

use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Dto\Response\Device\Devices;

beforeEach(function () {

    $this->json = '{
      "devices" : [ {
    "supportsVerificationCodes" : false,
    "currentDevice" : false,
    "hasApplePayCards" : false,
    "hasActiveSurfAccount" : false,
    "removalPending" : false,
    "deviceDetailUri" : "https://appleid.apple.com/account/manage/security/devices/15026dece2d407310d19af2e9178a6d7",
    "deviceDetailHttpMethod" : "GET",
    "qualifiedDeviceClass" : "iPhone",
    "deviceClass" : "iPhone",
    "os" : "iPhone OS",
    "modelName" : "iPhone XR",
    "osAndVersion" : "iOS 15.6.1",
    "listImageLocation" : "https://appleid.cdn-apple.com/static/deviceImages-11.0/iPhone/iPhone11,8-3b3b3c-dcdede/online-sourcelist.png",
    "listImageLocation2x" : "https://appleid.cdn-apple.com/static/deviceImages-11.0/iPhone/iPhone11,8-3b3b3c-dcdede/online-sourcelist__2x.png",
    "listImageLocation3x" : "https://appleid.cdn-apple.com/static/deviceImages-11.0/iPhone/iPhone11,8-3b3b3c-dcdede/online-sourcelist__3x.png",
    "infoboxImageLocation" : "https://appleid.cdn-apple.com/static/deviceImages-11.0/iPhone/iPhone11,8-3b3b3c-dcdede/online-infobox.png",
    "infoboxImageLocation2x" : "https://appleid.cdn-apple.com/static/deviceImages-11.0/iPhone/iPhone11,8-3b3b3c-dcdede/online-infobox__2x.png",
    "infoboxImageLocation3x" : "https://appleid.cdn-apple.com/static/deviceImages-11.0/iPhone/iPhone11,8-3b3b3c-dcdede/online-infobox__3x.png",
    "unsupported" : false,
    "osVersion" : "15.6.1",
    "name" : "\uD83D\uDC31",
    "id" : "15026dece2d407310d19af2e9178a6d7"
  } ],
  "hsa2SignedInDevicesLink" : "https://support.apple.com/HT205064",
  "suppressChangePasswordLink" : false
  }';
});

test('can create devices from json', function () {

    $devices = Devices::from($this->json);

    expect($devices->devices->count())->toBe(1)
        ->and($devices->hsa2SignedInDevicesLink)->toBe('https://support.apple.com/HT205064')
        ->and($devices->suppressChangePasswordLink)->toBeFalse();
});
