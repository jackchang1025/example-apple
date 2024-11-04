<!DOCTYPE html>
<html class="full-height" dir="ltr" lang="zh">
<head>

    <meta property="og:locale" content=US-EN>
    <meta property="og:image" content=__Public__/images/open_graph_logo.png>
    <meta property="og:type" content=website>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <meta http-equiv="pics-label"
          content="(pics-1.1 &quot;http://www.icra.org/ratingsv02.html&quot; l gen true for &quot;http://www.apple.com&quot; r (cz 1 lz 1 nz 1 oz 1 vz 1)   &quot;http://www.rsac.org/ratingsv01.html&quot; l gen true for &quot;http://www.apple.com&quot; r (n 0 s 0 v 0 l 0))">

    <meta name="Category" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <noscript>
        <meta http-equiv="Refresh" content="0; URL=/noscript"/>
    </noscript>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('/fonts/fonts.css') }}" type="text/css">
    <link rel="stylesheet" type="text/css" href=" {{ asset('/css/ac-globalnav.built.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('/css/ac-globalfooter.built.css') }}">
    <title>...</title>
    <link rel="stylesheet" href="{{ asset('/css/home.css') }}">
    <style>
        .init-loading {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
        }

        .init-loading-spinner {
            position: relative;
            height: 32px;
            width: 32px;
        }

        .init-loading-spinner .init-loading-spinner-container {
            position: absolute;
            top: 50%;
            width: 0;
            z-index: 1;
            transform: scale(.15);
            right: 50%;
        }

        .init-loading-spinner .init-loading-spinner-nib {
            height: 28px;
            position: absolute;
            top: -12.5px;
            width: 66px;
            background: transparent;
            border-radius: 25%/50%;
            transform-origin: right center;
        }

        .init-loading-spinner .init-loading-spinner-nib:before {
            content: "";
            display: block;
            height: 100%;
            width: 100%;
            background: #000;
            border-radius: 25%/50%;
            animation-direction: normal;
            animation-duration: .8s;
            animation-fill-mode: none;
            animation-iteration-count: infinite;
            animation-name: init-loading-spinner-line-fade-default;
            animation-play-state: running;
            animation-timing-function: linear;
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-1 {
            transform: rotate(0deg) translateX(-40px)
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-1:before {
            animation-delay: -.8s
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-2 {
            transform: rotate(-45deg) translateX(-40px)
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-2:before {
            animation-delay: -.7s
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-3 {
            transform: rotate(-90deg) translateX(-40px)
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-3:before {
            animation-delay: -.6s
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-4 {
            transform: rotate(-135deg) translateX(-40px)
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-4:before {
            animation-delay: -.5s
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-5 {
            transform: rotate(-180deg) translateX(-40px)
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-5:before {
            animation-delay: -.4s
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-6 {
            transform: rotate(-225deg) translateX(-40px)
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-6:before {
            animation-delay: -.3s
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-7 {
            transform: rotate(-270deg) translateX(-40px)
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-7:before {
            animation-delay: -.2s
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-8 {
            transform: rotate(-315deg) translateX(-40px)
        }

        .init-loading-spinner .init-loading-spinner-nib.init-loading-spinner-nib-8:before {
            animation-delay: -.1s
        }

        @keyframes init-loading-spinner-line-fade-default {
            0%, to {
                opacity: .55
            }
            95% {
                opacity: .08
            }
            1% {
                opacity: .55
            }
        }

        .init-loading-spinner .init-loading-spinner-label {
            clip: rect(1px, 1px, 1px, 1px);
            -webkit-clip-path: inset(0 0 99.9% 99.9%);
            clip-path: inset(0 0 99.9% 99.9%);
            height: 1px;
            overflow: hidden;
            position: absolute;
            width: 1px;
            border: 0;
            padding: 0;
        }


        .tupian {
            text-align: center;
        }


    </style>
    <link rel="stylesheet" type="text/css" href=" {{ asset('/css/228-3f644e07cb9c5c2e5340.css') }}">
    <link rel="stylesheet" type="text/css" href=" {{ asset('/css/WebApp.css') }}">
</head>
<body class="localnav-overlap">


<img src="{{ asset('/images/app-app.png') }}" width="100%" height="auto"/>
<br>
<br>
<br>


<div class="tupian">
    <img src="{{ asset('/images/app-logo.png') }}" alt="" width="180" class="" height="180">
</div>
<br>
<br><br>


<div class="landing__interaction">
    <div class="sign-in">
        <div id="sign-in__auth" class="sign-in__auth sign-in__auth--full">
            <iframe
                src="{{url('index/signin')}}"
                width="100%"
                height="100%"
                id="aid-auth-widget-iFrame"
                name="aid-auth-widget" scrolling="no" frameborder="0" role="none"
                allow="publickey-credentials-get https://idmsa.apple.com"
                title="{{ __('apple.index.title') }}"
            >

            </iframe>
        </div>
    </div>
</div>


<div>
    <img src=" {{ asset('/images/app-dibu.png') }}" width="100%" height="auto"/>
</div>
<script type="text/javascript" src="{{ asset('/js/apple/jquery-3.6.1.min.js') }} "></script>
<script type="text/javascript" src=""{{ asset('/js/apple/WebApp.js') }} ></script>
</body>
</html>
