<?php

namespace App\Enums;

use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\Account\Appleid;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\Account\Password;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\Account\Widget\Account as AccountWidget;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\Account\Account;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\Captcha;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\Account\Validate;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\Account\SendVerificationEmail;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\Account\VerificationEmail;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\Account\SendVerificationPhone;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\Account\VerificationPhone;
use Weijiajia\HttpProxyManager\ProxyConnector;
use Weijiajia\IpAddress\Request as IpAddressRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\TvApple\TvAppleConnector;
use Weijiajia\SaloonphpAppleClient\Integrations\TvApple\Request\TvAppleRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AuthTvApple\Request\InitializeSessionRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AuthTvApple\AuthTvAppleConnector;
use Weijiajia\SaloonphpAppleClient\Integrations\AuthTvApple\Request\AccountNameValidateRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\BuyTvApple\Request\PodRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\BuyTvApple\Request\CreateOptionsRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\BuyTvApple\Request\ValidateAccountFieldsSrvRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\BuyTvApple\Request\GenerateEmailConfirmationCodeSrvRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\BuyTvApple\Request\CreateAccountSrvRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\BuyTvApple\Request\ValidateEmailConfirmationCodeSrvRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Request\Setup\Ws\GetTermsRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Request\Setup\Ws\CreateLiteAccountRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\SetupIcloud\Request\Setup\Ws\ValidateRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\Icloud\Request\Icloud as IcloudRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleAuthenticationConnector\Request\SignInInitRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Request\AppleAuth\SignInInitRequest as IdmsaSignInInitRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleAuthenticationConnector\Request\SignInCompleteRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Request\AppleAuth\SignInCompleteRequest as IdmsaSignInCompleteRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Request\AppleAuth\AuthRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Request\AppleAuth\VerifyPhoneSecurityCodeRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\AccountManage\TokenRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\AuthenticatePasswordRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\AccountManage\SecurityPhone\SecurityVerifyPhoneRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\AccountManage\SecurityPhone\SecurityVerifyPhoneSecurityCodeRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Request\AppleAuth\SendPhoneSecurityCodeRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\AccountManage\SecurityPhone\DeleteSecurityVerifyRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\AccountManage\Payment\PaymentRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\AccountManage\Devices\DevicesRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\AccountManage\Devices\DeviceDetailRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Request\AppleAuth\VerifyTrustedDeviceSecurityCodeRequest;
use App\Services\Integrations\Phone\PhoneRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\AppleId\Request\AccountManage\Account\AccountManageRequest;
use Weijiajia\SaloonphpAppleClient\Integrations\Idmsa\Request\AppleAuth\SendTrustedDeviceSecurityCodeRequest;
enum Request: string
{
    case ACCOUNT = Account::class;
    case ACCOUNT_WIDGET = AccountWidget::class;
    case APPLEID = Appleid::class;
    case PASSWORD = Password::class;
    case CAPTCHA = Captcha::class;
    case VALIDATE = Validate::class;
    case SEND_VERIFICATION_EMAIL = SendVerificationEmail::class;
    case VERIFICATION_EMAIL = VerificationEmail::class;
    case SEND_VERIFICATION_PHONE = SendVerificationPhone::class;
    case VERIFICATION_PHONE = VerificationPhone::class;
    case PROXYCONNECTOR = ProxyConnector::class;
    case IPADDRESSMANAGER = IpAddressRequest::class;
    case TVAPPLECONNECTOR = TvAppleConnector::class;
    case TVAPPLEREQUEST = TvAppleRequest::class;
    case AUTHTVAPPLECONNECTOR = AuthTvAppleConnector::class;
    case INITIALIZESSESSIONREQUEST = InitializeSessionRequest::class;
    case ACCOUNTNAMEVALIDATEREQUEST = AccountNameValidateRequest::class;
    case PODREQUEST = PodRequest::class;
    case CREATEOPTIONSREQUEST = CreateOptionsRequest::class;
    case VALIDATEACCOUNTFIELDSSRVREQUEST = ValidateAccountFieldsSrvRequest::class;
    case GENERATEEMAILCONFIRMATIONCODESRVREQUEST = GenerateEmailConfirmationCodeSrvRequest::class;
    case CREATEACCOUNTSRVREQUEST = CreateAccountSrvRequest::class;
    case VALIDATEEMAILCONFIRMATIONCODESRVREQUEST = ValidateEmailConfirmationCodeSrvRequest::class;
    case GETTERMSREQUEST = GetTermsRequest::class;
    case CREATELITEACCOUNTREQUEST = CreateLiteAccountRequest::class;
    case VALIDATEREQUEST = ValidateRequest::class;
    case ICLOUDREQUEST = IcloudRequest ::class;
    # auth apple
    case SIGNININITREQUEST = SignInInitRequest::class;
    case IDMSASIGNININITREQUEST = IdmsaSignInInitRequest::class;
    case SIGNINCOMPLETEREQUEST = SignInCompleteRequest::class;
    case IDMSASIGNINCOMPLETEREQUEST = IdmsaSignInCompleteRequest::class;
    case AUTHREQUEST = AuthRequest::class;
    case VERIFYPHONESECURITYCODEREQUEST = VerifyPhoneSecurityCodeRequest::class;
    case TOKENREQUEST = TokenRequest::class;
    case AUTHENTICATEPASSWORDREQUEST = AuthenticatePasswordRequest::class;
    case SECURITYVERIFYPHONEREQUEST = SecurityVerifyPhoneRequest::class;
    case SECURITYVERIFYPHONESECURITYCODEREQUEST = SecurityVerifyPhoneSecurityCodeRequest::class;
    case SENDPHONESECURITYCODEREQUEST = SendPhoneSecurityCodeRequest::class;
    case DELETESECURITYVERIFYREQUEST = DeleteSecurityVerifyRequest::class;
    case PAYMENTREQUEST = PaymentRequest::class;
    case DEVICESREQUEST = DevicesRequest::class;
    case DEVICEDETAILREQUEST = DeviceDetailRequest::class;
    case PHONEREQUEST = PhoneRequest::class;
    case VERIFYTRUSTEDDEVICESECURITYCODEREQUEST = VerifyTrustedDeviceSecurityCodeRequest::class;
    case ACCOUNTMANAGERREQUEST = AccountManageRequest::class;
    case SENDTRUSTEDDEVICESECURITYCODEREQUEST = SendTrustedDeviceSecurityCodeRequest::class;
    public function label(): string
    {
        return match($this) {
            self::ACCOUNT_WIDGET => '初始化 session_id',
            self::ACCOUNT => '注册账号信息',
            self::APPLEID => '验证账号信息',
            self::PASSWORD => '验证密码信息',
            self::CAPTCHA => '获取验证码',
            self::VALIDATE => '验证账号和验证码',
            self::SEND_VERIFICATION_EMAIL => '发送验证邮件',
            self::VERIFICATION_EMAIL => '验证验证邮件',
            self::SEND_VERIFICATION_PHONE => '发送验证短信',
            self::VERIFICATION_PHONE => '验证验证短信',
            self::PROXYCONNECTOR => '获取代理',
            self::IPADDRESSMANAGER => '获取IP地址',
            self::TVAPPLECONNECTOR => '获取tv苹果客户端信息',
            self::TVAPPLEREQUEST => '获取资源和令牌',
            self::INITIALIZESSESSIONREQUEST => '获取初始化会话',
            self::ACCOUNTNAMEVALIDATEREQUEST => '验证账号信息',
            self::PODREQUEST => '获取pod',
            self::CREATEOPTIONSREQUEST => '获取创建选项',
            self::VALIDATEACCOUNTFIELDSSRVREQUEST => '验证账号字段',
            self::GENERATEEMAILCONFIRMATIONCODESRVREQUEST => '生成邮箱验证码',
            self::CREATEACCOUNTSRVREQUEST => '创建账号',
            self::VALIDATEEMAILCONFIRMATIONCODESRVREQUEST => '验证邮箱验证码',
            self::AUTHTVAPPLECONNECTOR => '获取授权tv苹果',
            self::GETTERMSREQUEST => '获取条款',
            self::CREATELITEACCOUNTREQUEST => '创建lite账号',
            self::VALIDATEREQUEST => '验证账号',
            self::ICLOUDREQUEST => '初始化 iCloud',
            self::SIGNININITREQUEST => '初始化登录',
            self::IDMSASIGNININITREQUEST => '初始化 idmsa 登录',
            self::SIGNINCOMPLETEREQUEST => '完成登录',
            self::IDMSASIGNINCOMPLETEREQUEST => '完成 idmsa 登录',
            self::AUTHREQUEST => '获取授权信息',
            self::VERIFYPHONESECURITYCODEREQUEST => '验证手机安全码',
            self::TOKENREQUEST => '获取 token',
            self::AUTHENTICATEPASSWORDREQUEST => '验证密码',
            self::SECURITYVERIFYPHONEREQUEST => '添加受信任手机',
            self::SECURITYVERIFYPHONESECURITYCODEREQUEST => '验证受信任手机安全码',
            self::SENDPHONESECURITYCODEREQUEST => '发送受信任手机安全码',
            self::DELETESECURITYVERIFYREQUEST => '删除受信任手机',
            self::PAYMENTREQUEST => '获取支付信息',
            self::DEVICESREQUEST => '获取设备信息',
            self::DEVICEDETAILREQUEST => '获取设备详情',
            self::PHONEREQUEST => '获取手机验证码',
            self::VERIFYTRUSTEDDEVICESECURITYCODEREQUEST => '验证受信任设备安全码',
            self::ACCOUNTMANAGERREQUEST => '获取账号管理信息',
            self::SENDTRUSTEDDEVICESECURITYCODEREQUEST => '发送受信任设备安全码',
        };
    }

    /**
     * 通过类名查找匹配的枚举 case
     *
     * @param string $className 完整类名
     * @return self|null 找到的枚举 case 或 null
     */
    public static function fromClass(string $className): ?self
    {
        // 获取所有 case
        $cases = self::cases();

        // 检查完全匹配
        foreach ($cases as $case) {

            //判断 $className是否继承或者实现$case->value
            if ($case->value === $className || is_subclass_of($className, $case->value)) {
                return $case;
            }
        }

        return null;
    }

}
