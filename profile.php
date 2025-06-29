<?php
require_once './sessionCheck.php';
$title = $currentPath = "Profile";

$styles = "<link href='css/plugins/bootstrap-datepicker/datepicker3.css' rel='stylesheet'>
           <script src='js/plugins/toastr/toastr.min.js'></script>" . file_get_contents('static/css/profile.html');
require_once 'static/header.php';
?>
<body>
<div id="wrapper">
    <?php require_once 'static/navbar.php'; ?>

    <div id="page-wrapper" class="gray-bg">
        <?php require_once './static/other.php'; ?>
        <div class="wrapper wrapper-content">
            <div class="row">
                <div class="col-lg-4">
                    <div class="ibox" style="min-height: 400px;">
                        <div class="profile-header">
                            <div class="user-icon">
                                <i class="fa fa-user"></i>
                            </div>
                            <h2 id="displayName">Loading...</h2>
                            <p id="displayRole"><?=$role?></p>
                        </div>
                        <div class="ibox-content">
                            <div class="text-center">
                                <button class="btn btn-edit-profile btn-sm" id="editProfileBtn">
                                    <i class="fa fa-edit"></i> Edit Profile
                                </button>
                                <a href="password.php" class="btn btn-warning btn-sm">
                                    <i class="fa fa-key"></i> Change Password
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="ibox">
                        <div class="ibox-title">
                            <h5>Profile Information</h5>
                            <div class="ibox-tools">
                                <a class="collapse-link">
                                    <i class="fa fa-chevron-up"></i>
                                </a>
                            </div>
                        </div>
                        <div class="ibox-content">
                            <div id="viewMode">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="profile-info-item">
                                            <div class="profile-info-label">Username</div>
                                            <div class="profile-info-value" id="username">-</div>
                                        </div>
                                        <div class="profile-info-item">
                                            <div class="profile-info-label">First Name</div>
                                            <div class="profile-info-value" id="firstName">-</div>
                                        </div>
                                        <div class="profile-info-item">
                                            <div class="profile-info-label">Last Name</div>
                                            <div class="profile-info-value" id="lastName">-</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="profile-info-item">
                                            <div class="profile-info-label">Email Address</div>
                                            <div class="profile-info-value" id="email">-</div>
                                        </div>
                                        <div class="profile-info-item">
                                            <div class="profile-info-label">Phone Number</div>
                                            <div class="profile-info-value" id="phone">-</div>
                                        </div>
                                        <div class="profile-info-item">
                                            <div class="profile-info-label">Date of Birth</div>
                                            <div class="profile-info-value" id="birthday">-</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="profile-info-item">
                                            <div class="profile-info-label">Address</div>
                                            <div class="profile-info-value" id="address">-</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="editMode" class="edit-form">
                                <form id="updateProfileForm">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="editUsername">Username</label>
                                                <input type="text" class="form-control" id="editUsername"
                                                       name="username" required>
                                                <div class="error" id="errorUsername"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="editEmail">Email Address</label>
                                                <input type="email" class="form-control" id="editEmail" name="email"
                                                       required>
                                                <div class="error" id="errorEmail"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="editFirstName">First Name</label>
                                                <input type="text" class="form-control" id="editFirstName"
                                                       name="firstName" required>
                                                <div class="error" id="errorFirstName"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="editLastName">Last Name</label>
                                                <input type="text" class="form-control" id="editLastName"
                                                       name="lastName" required>
                                                <div class="error" id="errorLastName"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="editPhone">Phone Number</label>
                                                <input type="tel" class="form-control" id="editPhone" name="phone"
                                                       required>
                                                <div class="error" id="errorPhone"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="editBirthday">Date of Birth</label>
                                                <input type="text" class="form-control" id="editBirthday"
                                                       name="birthday" placeholder="dd/mm/yyyy" required>
                                                <div class="error" id="errorBirthday"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="editAddress">Address</label>
                                        <input type="text" class="form-control" id="editAddress" name="address">
                                        <div class="error" id="errorAddress"></div>
                                    </div>

                                    <div class="form-group text-right">
                                        <button type="button" class="btn btn-cancel-edit" id="cancelEditBtn">
                                            <i class="fa fa-times"></i> Cancel
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php require_once './static/footer.php'; ?>
    </div>
    <?php require_once './static/logout.php'; ?>
</div>

<?php require_once 'static/scripts.html'; ?>

<script>
    $(document).ready(function () {
        let userData = {};
        let isEditing = false;

        loadProfileData();

        $('#editBirthday').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true,
            endDate: 'today'
        });

        $('#editProfileBtn').on('click', function () {
            if (!isEditing) {
                toggleEditMode();
            }
        });

        $('#cancelEditBtn').on('click', function () {
            cancelEdit();
        });

        $.validator.addMethod("over18", function (value, element) {
            if (!value) return false;
            const [day, month, year] = value.split("/").map(Number);
            const dob = new Date(year, month - 1, day);
            const today = new Date();
            const age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            const dayDiff = today.getDate() - dob.getDate();

            return age > 18 || (age === 18 && (monthDiff > 0 || (monthDiff === 0 && dayDiff >= 0)));
        }, "You must be at least 18 years old.");

        $('#updateProfileForm').validate({
            rules: {
                username: {
                    required: true,
                    minlength: 3,
                    pattern: /^[a-zA-Z0-9]+$/
                },
                firstName: {
                    required: true,
                    minlength: 2,
                    pattern: /^[a-zA-Z]+$/
                },
                lastName: {
                    required: true,
                    minlength: 3,
                    pattern: /^[a-zA-Z]+$/
                },
                email: {
                    required: true,
                    email: true
                },
                phone: {
                    required: true,
                    pattern: /^0?\d{9,}$/
                },
                birthday: {
                    required: true,
                    over18: true
                }
            },
            messages: {
                username: {
                    required: "Please enter your username",
                    minlength: "Username must be at least 3 characters long",
                    pattern: "Username can only contain letters and numbers"
                },
                firstName: {
                    required: "Please enter your first name",
                    minlength: "First name must be at least 2 characters long",
                    pattern: "First name can only contain letters"
                },
                lastName: {
                    required: "Please enter your last name",
                    minlength: "Last name must be at least 3 characters long",
                    pattern: "Last name can only contain letters"
                },
                email: {
                    required: "Please enter your email address",
                    email: "Please enter a valid email address"
                },
                phone: {
                    required: "Please enter your phone number",
                    pattern: "Phone number must be at least 9 digits long"
                },
                birthday: {
                    required: "Please enter your date of birth",
                    over18: "You must be at least 18 years old."
                }
            },
            errorPlacement: function (error, element) {
                const fieldName = element.attr('name');
                error.appendTo(`#error${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)}`);
            },
            submitHandler: function (form) {
                saveProfile();
            }
        });

        function loadProfileData() {
            $.ajax({
                url: "./api/v1/general/get-profile.php",
                type: "GET",
                data: {
                    user_id: "<?php echo htmlspecialchars($_SESSION['user_id']); ?>",
                    action: "get-profile"
                },
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        userData = response;
                        updateDisplayData(response);
                    } else {
                        toastr.error("Failed to load profile data: " + response.message);
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error("Error loading profile: " + error);
                }
            });
        }

        function updateDisplayData(data) {
            $('#username').text(data.username || '-');
            $('#firstName').text(data.firstName || '-');
            $('#lastName').text(data.lastName || '-');
            $('#email').text(data.email || '-');
            $('#phone').text(data.phone || '-');
            $('#birthday').text(data.birthday || '-');
            $('#address').text(data.address || '-');
            $('#displayName').text((data.firstName || '') + ' ' + (data.lastName || ''));
        }

        function toggleEditMode() {
            isEditing = true;

            $('#editUsername').val(userData.username || '');
            $('#editFirstName').val(userData.firstName || '');
            $('#editLastName').val(userData.lastName || '');
            $('#editEmail').val(userData.email || '');
            $('#editPhone').val(userData.phone || '');
            $('#editBirthday').val(userData.birthday || '');
            $('#editAddress').val(userData.address || '');

            $('#viewMode').hide();
            $('#editMode').show();
            $('#editProfileBtn').text('Editing...').prop('disabled', true);
        }

        function cancelEdit() {
            isEditing = false;
            $('#editMode').hide();
            $('#viewMode').show();
            $('#editProfileBtn').text('Edit Profile').prop('disabled', false);

            $('#updateProfileForm').validate().resetForm();
            $('.error').text('');
        }

        function saveProfile() {
            const formData = new FormData();
            formData.append('action', 'update-profile');
            formData.append('user_id', "<?php echo htmlspecialchars($_SESSION['user_id']); ?>");
            formData.append('username', $('#editUsername').val());
            formData.append('firstName', $('#editFirstName').val());
            formData.append('lastName', $('#editLastName').val());
            formData.append('email', $('#editEmail').val());
            formData.append('phone', $('#editPhone').val());
            formData.append('birthday', $('#editBirthday').val());
            formData.append('address', $('#editAddress').val());

            $.ajax({
                url: './api/v1/general/update-profile.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        userData = response.data;
                        updateDisplayData(response.data);
                        cancelEdit();
                        toastr.success('Profile updated successfully!');
                    } else {
                        toastr.error(response.message || 'Failed to update profile');
                    }
                },
                error: function (xhr, status, error) {
                    let response;
                    try {
                        response = JSON.parse(xhr.responseText);
                    } catch (e) {
                        toastr.error('Error updating profile: ' + error);
                        return;
                    }
                    if (response.message === "No changes were made or user not found.") {
                        toastr.warning(response.message);
                    } else {
                        toastr.error('Error updating profile: ' + response.message);
                    }
                }
            });
        }
    });
</script>
<script src="js/plugins/bootstrap-datepicker/bootstrap-datepicker.js"></script>
<script src="scripts/general.js"></script>
</body>
</html>