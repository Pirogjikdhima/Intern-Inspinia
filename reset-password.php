<?php
$title = "Reset Password";
$styles = file_get_contents("static/css/reset-password.html");
require "static/header.php";
?>


<body class="gray-bg">
<div class="middle-box text-center loginscreen animated fadeInDown">
    <div>
        <h3>Reset Password</h3>
        <p>Enter your email address and we'll send you a link to reset your password.</p>

        <form id="reset-password-form" class="m-t" role="form" method="POST">
            <div class="form-group">
                <input id="email" name="email" type="email" class="form-control" placeholder="Your Email Address"
                       autocomplete="on">
                <p id="errorEmail"></p>
            </div>
            <button id="submit" type="submit" class="btn btn-primary block full-width m-b">Send Reset Link</button>

            <a href="login.php"><small>Back to Login</small></a>
            <p class="text-muted text-center"><small>Don't have an account?</small></p>
            <a class="btn btn-sm btn-white btn-block" href="register.php">Create an account</a>
        </form>
    </div>
</div>

<div class="modal inmodal fade" id="successModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md-custom">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span
                            class="sr-only">Close</span></button>
                <h4 class="modal-title">Email Sent Successfully</h4>
            </div>
            <div class="modal-body text-center">
                <div class="sk-spinner sk-spinner-wave" id="loadingSpinner" style="display: none;">
                    <div class="sk-rect1"></div>
                    <div class="sk-rect2"></div>
                    <div class="sk-rect3"></div>
                    <div class="sk-rect4"></div>
                    <div class="sk-rect5"></div>
                </div>
                <div id="successContent">
                    <i class="fa fa-envelope-o" style="font-size: 5em; color: #1ab394; margin-bottom: 30px;"></i>
                    <h3>Check Your Email</h3>
                    <p>We've sent a password reset link to your email address. You have <strong>2 minutes</strong> before the link
                        expires.</p>
                    <p>Please check your inbox and follow the instructions to reset your password.</p>
                    <p class="text-muted"><small>Didn't receive the email? Check your spam folder or try again.</small>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="window.location.href='login.php'">Go to Login
                </button>
                <button type="button" class="btn btn-white" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php require 'static/scripts.html'; ?>
<script>
    $(document).ready(function () {
        $("#reset-password-form").validate({
            rules: {
                email: {
                    required: true,
                    email: true
                }
            },
            messages: {
                email: {
                    required: "Please enter your email address",
                    email: "Please enter a valid email address"
                }
            },
            errorPlacement: function (error, element) {
                if (element.attr("name") === "email")
                    error.appendTo("#errorEmail");
                else
                    error.insertAfter(element);
            },
            submitHandler: function (form) {
                const $submitBtn = $("#submit");
                const originalText = $submitBtn.text();

                $submitBtn.text("Sending...").prop("disabled", true);

                const formData = {
                    email: $("#email").val(),
                    action: "reset-password"
                };

                $.ajax({
                    url: './api/v1/general/send-verification-code.php',
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    success: function (response) {
                        if (response.success) {
                            $("#successModal").modal({
                                backdrop: 'static',
                                keyboard: false
                            });

                            $("#reset-password-form")[0].reset();
                            $("#reset-password-form").validate().resetForm();

                            setTimeout(function() {
                                window.location.href = "login.php";
                            }, 10000);

                        } else {
                            toastr.error(response.message || "Failed to send reset email. Please try again.");
                        }
                    },
                    error: function (xhr, status, error) {
                        const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : "An unexpected error occurred. Please try again.";
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        $submitBtn.text(originalText).prop("disabled", false);
                    }
                });
            }
        });
        $("#successModal").on("hidden.bs.modal", function () {
            $("#loadingSpinner").hide();
            $("#successContent").show();
        });
    });
</script>
</body>
</html>