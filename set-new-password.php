<?php
require_once './vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . "/global/");
$dotenv->load();

require_once './global/config.php';
require_once './global/db.php';
require_once './global/helper.php';
require_once './global/api.php';
$conn = connectDB();

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $combined_token = urldecode(trim($_GET['token']));
    $token = substr($combined_token, 0, 64);
    $userId = (int)substr($combined_token, 64);

    $stmt = $conn -> prepare("SELECT * FROM password_resets WHERE user_id = ? AND token = ?");
    $stmt -> bind_param("is", $userId, $token);
    $stmt -> execute();
    $result = $stmt -> get_result();

    if ($result -> num_rows == 0) {
        echo json_encode(["success" => false, "message" => "Invalid request."]);
        exit;
    }

    $row = $result -> fetch_assoc();
    $expires_at = $row['expires_at'];
    $current_time = date('Y-m-d H:i:s');

    if ($current_time > $expires_at) {
        echo json_encode(["success" => false, "message" => "Token expired."]);
        header("Location: login.php");
        exit;
    }

    $title = "Reset Password";
    $combined_token = encryptMessage($combined_token);
    require "static/header.php";

}else{
    header("Location: login.php");
    exit();
}
?>

<style>
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

  #errorNewPassword,
  #errorNewConfirmPassword {
    color: #ed5565;
    font-size: 12px;
    margin-top: 5px;
    display: block;
    min-height: 15px;
  }
</style>

<body class="gray-bg">
<div class="middle-box text-center loginscreen animated fadeInDown">
    <div>
        <h3>Reset Your Password</h3>
        <p>Enter your new password below to complete the reset process.</p>

        <form id="reset-password" class="m-t" role="form" method="POST">
            <input type="hidden" name="user_id" value="<?=htmlspecialchars($combined_token)?>">
            <input type="hidden" name="action" value="reset-password">

            <div class="form-group">
                <div class="input-group">
                    <input type="password" class="form-control" id="newPassword" name="new_password" placeholder="New Password">
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fa fa-eye toggle-password" data-target="#newPassword" style="cursor: pointer;"></i>
                        </span>
                    </div>
                </div>
                <p id="errorNewPassword"></p>
            </div>

            <div class="form-group">
                <div class="input-group">
                    <input type="password" class="form-control" id="newConfirmPassword" name="new_confirm_password" placeholder="Confirm New Password">
                    <div class="input-group-append">
                        <span class="input-group-text">
                            <i class="fa fa-eye toggle-password" data-target="#newConfirmPassword" style="cursor: pointer;"></i>
                        </span>
                    </div>
                </div>
                <p id="errorNewConfirmPassword"></p>
            </div>

            <button type="submit" class="btn btn-primary block full-width m-b">Reset Password</button>

            <p class="text-muted text-center">
                <small>Remember your password? <a href="login.php">Sign In</a></small>
            </p>
        </form>
    </div>
</div>

<?php include 'static/scripts.html'; ?>

<script>
    $(document).ready(function () {
        toastr.options.positionClass = "toast-top-right";

        $(document).on('click', '.toggle-password', function () {
            const target = $(this).data('target');
            const input = $(target);
            const type = input.attr('type') === 'password' ? 'text' : 'password';
            input.attr('type', type);
            $(this).toggleClass('fa-eye fa-eye-slash');
        });

        $.validator.addMethod(
            "pattern",
            function (value, element, param) {
                if (this.optional(element)) {
                    return true;
                }
                if (typeof param === "string") {
                    param = new RegExp("^(?:" + param + ")$");
                }
                return param.test(value);
            },
            "Invalid format."
        );

        $("#reset-password").validate({
            rules: {
                new_password: {
                    required: true,
                    minlength: 8,
                    pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&.*])[A-Za-z\d!@#$%^&.*]{8,}$/
                },
                new_confirm_password: {
                    required: true,
                    minlength: 8,
                    equalTo: "#newPassword"
                }
            },
            messages: {
                new_password: {
                    required: "Please enter your new password",
                    minlength: "Password must be at least 8 characters long",
                    pattern: "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character"
                },
                new_confirm_password: {
                    required: "Please confirm your new password",
                    minlength: "Password must be at least 8 characters long",
                    equalTo: "Passwords do not match"
                }
            },
            errorPlacement: function (error, element) {
                if (element.attr("name") === "new_password")
                    error.appendTo("#errorNewPassword");
                else if (element.attr("name") === "new_confirm_password")
                    error.appendTo("#errorNewConfirmPassword")
                else
                    error.insertAfter(element);
            },
            submitHandler: function (form) {
                const formData = new FormData(form);

                $.ajax({
                    url: "./api/v1/general/reset-password.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: "json",
                    success: function (response) {
                        if (response.success) {
                            toastr.options.onHidden = function () {
                                window.location.href = response.location || "login.php";
                            };
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        const errorMessage = xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : "An unexpected error occurred. Please try again.";
                        toastr.error(errorMessage);
                    }
                });
            }
        });
    });
</script>
</body>
</html>