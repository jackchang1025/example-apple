<?php

return [
    'index' => [
        'title' => '登录 Apple ID',
    ],

    'signin'          => [
        'title'           => '登录 Apple ID',
        'password'        => '密码',
        'password?'       => '密码?',
        'remember'        => '记住我的 Apple ID',
        'forgot_password' => '忘记密码?',
        'forgot?'         => '忘记?',
        'forgot_account'  => '忘记了你的 Apple ID 或',
        'continue'        => '继续',
        'close'           => '关闭',
        'account'         => '管理你的 Apple 帐户',
        'email'           => '电子邮件或电话号码',
        'new_window_open' => '在新窗口中打开。',
        'incorrect'       => 'Apple ID 或密码不正确',
        'account_bind_phone' => '该账号已绑定手机号，请使用其他账号',
    ],
    'auth'            => [
        'two_factor_authentication'                    => '双重认证',
        'info'                                         => '一条包含验证码的信息已发送至你的设备。点击允许，并输入验证码以继续。',
        'incorrect_verification_code'                  => '验证码不正确',
        'verifying'                                    => '正在验证…',
        'did_not_receive_verification_code'            => '没有收到验证码？',
        'other_options'                                => '其他选项',
        'resend_verification_code'                     => '重新发送验证码',
        'send_text_message'                            => '发送短信给我',
        'get_new_verification_code'                    => '获取新验证码。',
        'get_code'                                     => '获取一条包含代码的短信。',
        'more_options'                                 => '更多选项…',
        'opens_new_window'                             => '在新窗口中打开。',
        'please_confirm_your_phone_number_for_support' => '请确认你的电话号码，以获得支持。',
    ],
    'auth_phone_list' => [
        'verify_identity'  => '验证你的身份',
        'select_phone'     => '请选择一个电话号码接收验证码。',
        'phone_type'       => '短信',
        'cannot_use'       => '无法使用这些电话号码 ?',
        'opens_new_window' => '在新窗口中打开。',
    ],

    'result' => [
        'two_factor_auth' => 'Apple 双重验证',
        'enabled_message' => '您的Apple ID，已成功开启双重验证，感谢您的配合。',
        'done'            => '完成',
    ],

    'sms' => [
        'two_factor_auth'     => '双重认证',
        'enter_code'          => '请输入验证码。输入验证码后，页面会自动更新。',
        'digit'               => '位',
        'code_sent'           => '一条包含验证码的信息已发送至',
        'code_enter_continue' => '输入验证码以继续。',
        'switch_other_number' => '切换其他号码',
        'verifying'           => '正在验证…',
        'no_code'             => '没有收到验证码？',
        'resend_code'         => '重新发送验证码',
        'get_new_code'        => '获取新验证码。',
        'voice_call'          => '拨打语音来电',
        'voice_call_info'     => '接听语音来电并获得验证码。',
        'more_options'        => '更多选项…',
        'confirm_phone'       => '请确认你的电话号码，以获得支持。',
        'opens_new_window'    => '在新窗口中打开。',
        'error'               => [
            'incorrect_verification_code' => '验证码不正确',
            'try_later'                   => '发送失败,请稍后重试',
        ],
    ],
    'send_sms' => [
        'error' => '验证码发送失败,请稍后重试',
        'verification_code_sent_too_many_times' => '你的验证码发送次数过多,请稍后重试',
    ],

    'stolen_protection' => [
        'title'         => '关闭"失窃设备保护"',
        'settings_icon' => '设置图标',
        'steps'         => [
            'goto_settings' => '前往"设置"',
            'and_steps'     => ', 然后执行以下一项操作:',
            'face_id'       => '在配备面容 ID 的 iPhone 上: 轻点"面容 ID 与密码"，然后输入密码。',
            'touch_id'      => '在配备主屏幕按钮的 iPhone 上: 轻点"触控 ID 与密码"，然后输入密码。',
            'scroll_tap'    => '向下滚动并轻点"失窃设备保护"。',
            'turn_off'      => '关闭"失窃设备保护"。',
        ],
        'return_login'  => '返回登录',
    ],
];
