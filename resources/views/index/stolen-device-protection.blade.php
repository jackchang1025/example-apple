<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('apple.stolen_protection.title') }}</title>
</head>

<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica', 'Arial', sans-serif;
        line-height: 1.6;
        color: #333;
        margin: 0;
        padding: 20px;
    }

    .container {
        max-width: 600px;
        margin: 0 auto;
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
    }

    h1 {
        font-size: 20px;
        margin-bottom: 20px;
    }

    ol {
        padding-left: 20px;
    }

    li {
        margin-bottom: 15px;
    }

    ul {
        list-style-type: disc;
        padding-left: 20px;
        margin-top: 10px;
    }

    .icon {
        width: 20px;
        height: 20px;
        vertical-align: middle;
        margin-right: 5px;
    }

    p {
        margin: 0 0 10px 0;
    }
    .login-link {
        margin-top: 20px;
        text-align: center;
    }

    .login-link a {
        color: #007AFF;
        text-decoration: none;
        font-weight: 500;
    }

    .login-link a:hover {
        text-decoration: underline;
    }
</style>
<body>
<div class="container">
    <h1>{{ __('apple.stolen_protection.title') }}</h1>
    <ol>
        <li>
            <p>{{ __('apple.stolen_protection.steps.goto_settings') }} <img
                    src="https://help.apple.com/assets/6633DB1C79444B6B900807F2/6633DB1FF5976AEFF00E8880/zh_CN/88b2400e45bcf521514b7252cbb2d959.png"
                    alt="{{ __('apple.stolen_protection.settings_icon') }}"
                    class="icon">{{ __('apple.stolen_protection.steps.and_steps') }}</p>
            <ul>
                <li>{{ __('apple.stolen_protection.steps.face_id') }}</li>
                <li>{{ __('apple.stolen_protection.steps.touch_id') }}</li>
            </ul>
        </li>
        <li>
            <p>{{ __('apple.stolen_protection.steps.scroll_tap') }}</p>
        </li>
        <li>
            <p>{{ __('apple.stolen_protection.steps.turn_off') }}</p>
        </li>
    </ol>
    <div class="login-link">
        <a href="/index/index">{{ __('apple.stolen_protection.return_login') }}</a>
    </div>
</div>
</body>
</html>
