<?php
require_once "./global/api.php";
if (isLoggedIn()){
    header("Location: ./profile.php");
    exit();
}
$title = "Login";
$styles = file_get_contents('static/css/login.html');
require "static/header.php";
?>

<style>
  /* Center the form vertically and horizontally using Inspinia styles */
  body.gray-bg {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
  }

  .middle-box {
    position: static;
    width: 100%;
    max-width: 400px;
    margin: 0;
    transform: none;
  }

  .logo-name {
    margin-bottom: 30px;
  }

  .welcome-text {
    margin-bottom: 20px;
    color: #676a6c;
    font-size: 14px;
    line-height: 1.6;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    body.gray-bg {
      padding: 10px;
    }

    .middle-box {
      max-width: 100%;
    }
  }
</style>

<body class="gray-bg">
<div class="middle-box text-center loginscreen animated fadeInDown">
    <div>
        <div class="logo-name" style="margin-bottom: 30px;">
            <i class="fa fa-shield-alt" style="font-size: 60px; color: #1ab394; margin-bottom: 20px;"></i>
        </div>

        <h3>Welcome Back</h3>
        <p>Please sign in to your account</p>

        <div class="welcome-text">
            <p><strong>Access your profile</strong></p>
            <p>Manage your profile, view reports, and stay connected with your team.
                Sign in securely to continue where you left off.</p>
        </div>

        <form id="login-form" class="m-t" role="form" method="POST">
            <div class="form-group">
                <input id="email" name="email" type="email" class="form-control" placeholder="Email Address" autocomplete="on" required>
            </div>
            <div class="form-group">
                <div class="input-group">
                    <input id="password" name="password" type="password" class="form-control" placeholder="Password" required>
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fa fa-eye toggle-password" data-target="#password"></i>
                        </span>
                    </div>
                </div>
                <p id="errorPassword"></p>
            </div>
            <button id="submit" type="submit" class="btn btn-primary block full-width m-b">Sign In</button>

            <a href="./reset-password.php"><small>Forgot your password?</small></a>
            <p class="text-muted text-center"><small>Don't have an account yet?</small></p>
            <a class="btn btn-sm btn-white btn-block" href="./register.php">Create an account</a>
        </form>

        <div class="welcome-text" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e7eaec;">
            <p style="margin-bottom: 10px; color: #1ab394; font-weight: 600;">
                <i class="fa fa-check-circle"></i> Secure Login
            </p>
            <p style="margin-bottom: 8px; font-size: 13px;">
                <i class="fa fa-user-shield" style="color: #1ab394; margin-right: 5px;"></i>
                Your data is protected with enterprise-grade security
            </p>
            <p style="margin-bottom: 8px; font-size: 13px;">
                <i class="fa fa-mobile-alt" style="color: #1ab394; margin-right: 5px;"></i>
                Access from any device, anywhere
            </p>
            <p style="margin-bottom: 0; font-size: 13px;">
                <i class="fa fa-clock" style="color: #1ab394; margin-right: 5px;"></i>
                24/7 availability and support
            </p>
        </div>
    </div>
</div>

<?php require 'static/scripts.html'; ?>
<script src="scripts/login.js"></script>
</body>
</html>