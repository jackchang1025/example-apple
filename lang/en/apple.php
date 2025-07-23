<?php

return [
    'index' => [
        'title' => 'Sign in with your Apple&nbsp;ID',
    ],

    'signin'          => [
        'title'           => 'Sign in with your Apple ID',
        'password'        => 'Password',
        'password?'       => 'Password?',
        'remember'        => 'Remember my Apple ID',
        'forgot_password' => 'Forgot password?',
        'forgot?'         => 'Forgot?',
        'forgot_account'  => 'Forgot your Apple ID or',
        'continue'        => 'Continue',
        'close'           => 'Close',
        'account'         => 'Manage your Apple Account',
        'email'           => 'Email or Phone Number',
        'new_window_open' => 'Opens in a new window.',
        'incorrect'       => 'Incorrect Apple ID or password.',
        'account_bind_phone' => 'This account is bound to a phone number, please use another account.',
    ],
    'auth'            => [
        'two_factor_authentication'                    => 'Two-Factor Authentication',
        'info'                                         => 'A message with a verification code has been sent to your device. Tap Allow and enter the code to continue.',
        'incorrect_verification_code'                  => 'Incorrect verification code.',
        'verifying'                                    => 'Verifying...',
        'did_not_receive_verification_code'            => 'Didn\'t receive a verification code?',
        'other_options'                                => 'Other Options',
        'resend_verification_code'                     => 'Resend verification code',
        'send_text_message'                            => 'Send me a text message',
        'get_new_verification_code'                    => 'Get a new verification code.',
        'get_code'                                     => 'Get a text message with a code.',
        'more_options'                                 => 'More options...',
        'opens_new_window'                             => 'Opens in a new window.',
        'please_confirm_your_phone_number_for_support' => 'Please confirm your phone number for support.',
    ],
    'auth_phone_list' => [
        'verify_identity'  => 'Verify Your Identity',
        'select_phone'     => 'Please select a phone number to receive a verification code.',
        'phone_type'       => 'Text Message',
        'cannot_use'       => 'Can\'t use these phone numbers?',
        'opens_new_window' => 'Opens in a new window.',
    ],

    'result' => [
        'two_factor_auth' => 'Apple ID Two-Factor Authentication',
        'enabled_message' => 'Your Apple ID is already enabled for two-factor authentication.',
        'done'            => 'Done',
    ],

    'sms' => [
        'two_factor_auth'     => 'Two-Factor Authentication',
        'enter_code'          => 'Please enter the verification code. The page will update automatically after you enter the code.',
        'digit'               => 'digit',
        'code_sent'           => 'A message with a verification code has been sent to',
        'code_enter_continue' => 'Enter the code to continue.',
        'switch_other_number' => 'Switch to another number',
        'verifying'           => 'Verifying...',
        'no_code'             => 'Didn\'t receive a code?',
        'resend_code'         => 'Resend code',
        'get_new_code'        => 'Get a new code.',
        'voice_call'          => 'Make a voice call',
        'voice_call_info'     => 'Receive a voice call to get the verification code.',
        'more_options'        => 'More options...',
        'confirm_phone'       => 'Please confirm your phone number for support.',
        'opens_new_window'    => 'Opens in a new window.',
        'error'               => [
            'incorrect_verification_code' => 'Incorrect verification code.',
            'try_later'                   => 'Failed to send, please try again later.',
        ],
    ],

    'send_sms' => [
        'error' => 'Failed to send verification code, please try again later.',
        'verification_code_sent_too_many_times'=> 'You have entered an incorrect verification code too many times. Try again later.',
    ],

    'stolen_protection' => [
        'title'         => 'Turn Off Stolen Device Protection',
        'settings_icon' => 'Settings Icon',
        'steps'         => [
            'goto_settings' => 'Go to Settings',
            'and_steps'     => ', then do one of the following:',
            'face_id'       => 'On an iPhone with Face ID: Tap Face ID & Passcode, then enter your passcode.',
            'touch_id'      => 'On an iPhone with a Home button: Tap Touch ID & Passcode, then enter your passcode.',
            'scroll_tap'    => 'Scroll down and tap Stolen Device Protection.',
            'turn_off'      => 'Turn off Stolen Device Protection.',
        ],
        'return_login'  => 'Return to Sign In',
    ],
];
