<?php
$title = "Register";
$styles = "    
    <style>
        label.error {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }
    </style>";
require "static/header.php";
?>

<body class="gray-bg">
<div class="middle-box text-center loginscreen   animated fadeInDown">
    <div>
        <div class="register-header">
            <i class="fa fa-user-plus register-icon"></i>
            <h3>Join Our Community</h3>
            <p>Create your account and get started today</p>

            <div class="welcome-text">
                <p><strong>Welcome to the platform!</strong></p>
                <p>Join thousands of users who trust us with their business needs.
                    Create your account in just a few steps and unlock all features.</p>
            </div>
        </div>
        <form id="register-form" class="m-t" role="form" method="POST">
            <div class="form-group">
                <input id="username" name="username" type="text" class="form-control" placeholder="Username">
            </div>
            <div class="form-group">
                <input id="first-name" name="firstName" type="text" class="form-control" placeholder="First Name">
            </div>
            <div class="form-group">
                <input id="last-name" name="lastName" type="text" class="form-control" placeholder="Last Name">
            </div>
            <div class="form-group">
                <input id="birthday" name="birthday" type="text" class="form-control" data-provide="datepicker"
                       placeholder="Date of Birth" required>
            </div>
            <div class="form-group">
                <input id="phone" name="phone" type="text" class="form-control" placeholder="Phone Number">
            </div>
            <div class="form-group">
                <input id="email" name="email" type="email" class="form-control" placeholder="Email">
            </div>
            <div class="form-group">
                <input id="address" name="address" type="text" class="form-control" placeholder="Address">
            </div>
            <div class="form-group">
                <div class="input-group">
                    <input id="password" name="password" type="password" class="form-control" placeholder="Password">
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fa fa-eye toggle-password" data-target="#password"></i>
                        </span>
                    </div>
                </div>
                <p id="errorPassword"></p>
            </div>
            <div class="form-group">
                <div class="input-group">
                    <input id="confirm-password" name="confirmPassword" type="password" class="form-control" placeholder="Confirm Password">
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fa fa-eye toggle-password" data-target="#confirm-password"></i>
                        </span>
                    </div>
                </div>
                <p id="errorConfirmPassword"></p>
            </div>
            <div class="form-group">
                <div class="checkbox i-checks">
                    <label>
                        <input id ="termsChecked" type="checkbox" name="termsChecked"> <i></i> Agree to the terms and policy
                    </label>
                </div>
                <p id="errorTermsChecked"></p>
            </div>
            <button id="submit-button" type="submit" class="btn btn-primary block full-width m-b">Register</button>

            <p class="text-muted text-center"><small>Already have an account?</small></p>
            <a class="btn btn-sm btn-white btn-block" href="login.php">Login</a>
        </form>
    </div>
</div>
<?php require 'static/scripts.html'; ?>
<script>
    $(document).ready(function () {
        $('.i-checks').iCheck({
            checkboxClass: 'icheckbox_square-green',
            radioClass: 'iradio_square-green',
        });
    });
</script>
<script src="scripts/register.js"></script>
</body>
</html>