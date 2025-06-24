<?php
require_once 'sessionCheck.php';
$title = $currentPath = "Change Password";
$styles = "<link href='css/plugins/toastr/toastr.min.css' rel='stylesheet'>
           <script src='js/plugins/toastr/toastr.min.js'></script>";
include 'static/header.php';
?>
<body>
<div id="wrapper">
    <?php
    require 'static/navbar.php';
    ?>

    <div id="page-wrapper" class="gray-bg">
        <?php require './static/other.php'; ?>
        <div class="wrapper wrapper-content">
            <div class="row animated fadeInRight">
                <div class="col-md-6 offset-md-3">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>Change Password</h5>
                        </div>
                        <div class="ibox-content">
                            <form id="passwordChangeForm" method="POST">
                                <input type="hidden" name="user_id"
                                       value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
                                <input type="hidden" name="action" value="change-password">
                                <div class="form-group">
                                    <label for="newPassword">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="newPassword"
                                               name="new_password">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fa fa-eye toggle-password" data-target="#newPassword"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <p id="newPasswordError"></p>
                                </div>
                                <div class="form-group">
                                    <label for="confirmPassword">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirmPassword"
                                               name="new_confirm_password">
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <i class="fa fa-eye toggle-password" data-target="#confirmPassword"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <p id="newConfirmPasswordError"></p>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block mt-4">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include 'static/scripts.html';
?>
<script src="scripts/general.js"></script>
</body>
</html>