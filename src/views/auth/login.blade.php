
@extends('layout/minimal')
@section('browser_title','Login')
@section('content')

<style type="text/css">
    .err-red{color:red;}
</style>
<!-- Content Wrap -->
<div class="login-wrap">
    <!-- Page Main Content -->
    <div class="page-main-content">
        <div class="container">
            <div class="row flex-row">
                <div class="col-md-6 bg-login">
                    <div class="bg-login-content">
                        <div class="center-align">
                            <p class="img-center-title">Welcome to</p>
                            <img src="{{ URL::asset('public/img/eyatra/dems_logo.svg') }}" class="img-responsive">
                                <small>V1.0.0</small>
                        </div>
                    </div><!-- Background Login Content -->
                </div><!-- Column -->
                <div class="col-md-6 login-responsive">
                    <div class="login-content">
                        <div class="login-box">
                            <div class="img-center">
                                <p class="img-center-title">Welcome to</p>
                                <img src="{{ URL::asset('public/img/eyatra/dems_login_header.svg') }}" class="img-responsive">
                            </div><!-- Img Center -->
                            <form method = "POST" action = "{{route('login')}}">
                                {{csrf_field()}}

                            <div class="tvs-logo">
                                <img src="{{ URL::asset('public/img/eyatra/dems_login_header.svg') }}" class="img-responsive">
                            </div><!-- TVS Logo -->
                            <div class="form-group text-left">
                               <!--  <select class="form-control">
                                    <option>TVS</option>
                                </select> -->
                                 {{Form::select('company_id' , $company_list , '' ,['autocomplete' => 'off' , 'class' => 'form-control company_id','id' => 'company_id'])}}
                            </div><!-- Feild -->
                            <div class="form-group">
                                <div class="input-group pb-1">
                                    <span class="input-group-addon"><img class="img-responsive" src="{{ URL::asset('public/img/bg/user.svg') }}" /></span>
                                    <input type="text" class="form-control" placeholder="Username" name = "username" value="{{ old('username') }}">
                                </div><!-- /input-group -->
                            </div><!-- Feild -->
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-addon"><img class="img-responsive" src="{{ URL::asset('public/img/bg/password.svg') }}" /></span>
                                    <input type="password" class="form-control" placeholder="Password" name="password" value="{{ old('password') }}" id = "password" data-toggle="password" required>
                                    <span class="input-group-btn">
                                        <button class="btn btn-eye" type="button" onclick="if(password.type == 'text') password.type = 'password';else password.type = 'text';">
                                            <img src="{{ URL::asset('public/img/bg/eye.svg') }}" class="img-responsive">
                                        </button>
                                    </span>
                                </div><!-- /input-group -->
                            </div><!-- Feild -->
                            <div class="login-action">
                                <div class="checkbox-wrap">
                                    <div class="checkbox">
                                        <input id="use_ad_password" type="checkbox" name="use_ad_password">
                                        <label for="use_ad_password">Login with AD Password</label>
                                    </div>

                                </div>
                                <div class="login-forgot">
                                    <a href="#!" class="forgot-pass">
                                        Forgot Your Password?
                                    </a>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-login">Login</button>
                            <!-- <div class="link-btns text-center" >
                                <a href="{{route('password.request')}}" class="btn btn-link">Forgot Password?</a>
                                <a href="{{route('register')}}" class="btn btn-link">Request for New User</a>
                            </div> -->
                        </form>
                        </div><!-- Login Inbox -->
                        <!-- Login Right -->
                        <div class="login-right pass-none">
                            <div class="login-title">
                                <h4 class="title">Forgot Password</h4>
                            </div>
                            <!-- Form -->
                            <form class="forgot-form" id="checkMobileNumber">
                                 @csrf
                                <div class="form-group">
                                    <div class="input-text input-group">
                                        <span class="input-group-addon"><i class="fa fa-mobile"></i></span>
                                        <input type="text" class="form-control contact_number"  id="contact_number" name="contact_number" placeholder="Enter Mobile Number" required="" >
                                    </div><!-- Field -->
                                    <div class="error_texts" ></div>
                                </div>
                                <ul class="login-action">
                                    <li>
                                        <button class="btn btn-back" id="btn-forgot" type="button">
                                            Cancel
                                        </button>
                                    </li>
                                    <li>
                                        <button class="btn btn-otp" id="confirmation-link" type="submit">
                                            Send OTP
                                        </button>
                                    </li>
                                </ul>
                            </form>
                        </div><!-- Login Right -->
                        <!-- Login Right -->
                        <div class="login-right pass-next">
                            <div class="login-title">
                                <h4 class="title">Enter OTP</h4>
                            </div>
                            <!-- Form -->
                            <form class="confirmation-form" id="confirmOTP">
                                @csrf
                                <div class="form-group">
                                    <div class="input-text input-group">
                                        <span class="input-group-addon"><i class="fa fa-mobile"></i></span>
                                        <input type="hidden" name="user_id" id="user_id1">
                                        <input type="password" id="otp_no" name="otp_no" class="form-control otp_no" placeholder="Enter OTP" required="required" maxlength="6" minlength="6" autocomplete="false"/>
                                    </div><!-- Field -->
                                     <div class="otp_error_texts" ></div>
                                </div>
                                <ul class="login-action">
                                    <li>
                                        <button class="btn btn-back-two" type="button">
                                            Cancel
                                        </button>
                                    </li>
                                    <li>
                                        <button class="btn btn-next" type="submit" id="password-link" >
                                            Verify
                                        </button>
                                    </li>
                                </ul>
                            </form>
                        </div><!-- Login Right -->
                        <!-- Login Right -->
                        <div class="login-right pass-confirm">
                            <div class="login-title">
                                <h4 class="title">Enter Password</h4>
                            </div>
                            <!-- Form -->
                            <form class="password-form" id="setPassword" method="POST" action="{{route('setPasswordForm')}}">
                                 @csrf
                                <div class="form-group">
                                    <label>New Password</label>
                                    <div class="input-text input-group">
                                        <span class="input-group-addon"><img class="img-responsive" src="{{ URL::asset('public/img/bg/password.svg') }}" /></span>
                                        <input type="hidden" name="user_id" id="user_id2">
                                        <input type="password" class="form-control password"  name="password" id="old_password" placeholder="Enter New Password" autocomplete="false">
                                        <span class="input-group-btn">
                                        <button class="btn btn-eye old_pass_toggle" type="button">

                                            <img src="{{ URL::asset('public/img/bg/eye.svg') }}" class="img-responsive">
                                        </button>
                                    </span>
                                    </div><!-- Field -->
                                    <div class="password_error_text color-red" ></div>

                                </div>


                                <div class="form-group">
                                    <label>Confirm Password</label>
                                    <div class="input-text input-group">
                                        <span class="input-group-addon"><img class="img-responsive" src="{{ URL::asset('public/img/bg/password.svg') }}" /></span>
                                        <input type="password" class="form-control confirm_password" name="confirm_password" id="confirm_password" placeholder="Enter Confirm Password" autocomplete="false">
                                       <span class="input-group-btn">
                                        <button class="btn btn-eye confi_pass_toggle" type="button">

                                            <img src="{{ URL::asset('public/img/bg/eye.svg') }}" class="img-responsive">
                                        </button>
                                    </span>
                                    </div><!-- Field -->
                                    <div class="confirm_password_error_text color-red" ></div>

                                </div>

                                <ul class="login-action">
                                    <li>
                                        <button class="btn btn-back-three" type="button">
                                            Cancel
                                        </button>
                                    </li>
                                    <li>
                                        <button class="btn btn-confirm" id="set-password-link" type="submit">
                                            confirm
                                        </button>
                                    </li>
                                </ul>
                            </form>
                        </div><!-- Login Right -->
                    </div><!-- Login Content -->
                    <div class="login-footer">
                        <p class="footer-description"> Powered by</p>
                        <div class="footer-logo">
                            <img class="img-responsive" src="{{ URL::asset('public/img/uitoux.png') }}">
                        </div><!-- Footer Log -->
                    </div><!-- Login Footer -->
                </div><!-- Column -->
            </div><!-- Row -->
        </div><!-- Container -->
    </div><!-- Page Main Content -->
</div><!-- Content Wrap -->

@endsection




@section('footer_js')

 <script src="{{ URL::asset('/public/js/auth/app-forget-password.js')}}"></script>
    <script type="text/javascript">
        var checkMobileNumberURL = "{{route('checkMobileNumber')}}";
        var confirmOTPFormURL = "{{route('confirmOTPForm')}}";
        // var setPasswordForm  "{{route('setPasswordForm')}}";
</script>

<script>
    $(".forgot-pass").click(function() {
        $('.pass-none').addClass('in');
        $('.login-box').addClass('login-none');
    });
    $(".btn-back").click(function() {
        $('.pass-none').removeClass('in');
        $('.login-box').removeClass('login-none');
    });
    /*$(".btn-otp").click(function() {
        $('.pass-next').addClass('in');
        $('.pass-none').removeClass('in');
    });*/
    $(".btn-back-two").click(function() {
        $('.pass-next').removeClass('in');
        $('.pass-none').removeClass('in');
        $('.login-box').removeClass('login-none');
    });
    /*$(".btn-next").click(function() {
        $('.pass-next').removeClass('in');
        $('.pass-confirm').addClass('in');
    });*/
    $(".btn-back-three").click(function() {
        $('.pass-confirm').removeClass('in');
        $('.pass-next').removeClass('in');
        $('.pass-none').removeClass('in');
        $('.login-box').removeClass('login-none');
    });

    $("body").on('click', '.old_pass_toggle', function() {
          //$(this).toggleClass("fa-eye fa-eye-slash");
          var input = $("#old_password");
          if (input.attr("type") === "password") {
            input.attr("type", "text");
          } else {
            input.attr("type", "password");
          }

    });
    $("body").on('click', '.confi_pass_toggle', function() {
          //$(this).toggleClass("fa-eye fa-eye-slash");
          var input = $("#confirm_password");
          if (input.attr("type") === "password") {
            input.attr("type", "text");
          } else {
            input.attr("type", "password");
          }

    });


</script>

@endsection