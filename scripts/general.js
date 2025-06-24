$.validator.addMethod("pattern", function (value, element, param) {
    if (this.optional(element)) {
        return true;
    }
    if (typeof param === "string") {
        param = new RegExp("^(?:" + param + ")$");
    }
    return param.test(value);
}, "Invalid format.");

$(document).ready(function () {
    $('#passwordChangeForm').validate({
        rules: {
            new_password: {
                required: true,
                minlength: 8,
                pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&.*])[A-Za-z\d!@#$%^&.*]{8,}$/
            }, new_confirm_password: {
                required: true, minlength: 8, equalTo: "#newPassword"
            }
        }, messages: {
            new_password: {
                required: "Please enter your password",
                minlength: "Password must be at least 8 characters long",
                pattern: "Password must contain at least one uppercase letter, one lowercase letter, and one number"
            }, new_confirm_password: {
                required: "Please confirm your password",
                minlength: "Password must be at least 8 characters long",
                equalTo: "Passwords do not match",
            },
        }, errorPlacement: function (error, element) {
            if (element.attr("name") === "new_password") {
                error.appendTo("#newPasswordError");
            } else {
                error.appendTo("#newConfirmPasswordError");
            }
        }, submitHandler: function (form) {
            const formData = new FormData(form);
            $.ajax({
                url: 'api/v1/general/change-password.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        toastr.options.onHidden = function () {
                            window.location.href = response.location;
                        };
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function (xhr) {
                    const errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : "An unexpected error occurred. Please try again.";
                    toastr.error(errorMessage);
                }
            });
        }

    });
    $('#reset-password').validate({
        rules: {
            new_password: {
                required: true,
                minlength: 8,
                pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&.*])[A-Za-z\d!@#$%^&.*]{8,}$/
            }, messages: {
                new_password: {
                    required: "Please enter your password",
                    minlength: "Password must be at least 8 characters long",
                    pattern: "Password must contain at least one uppercase letter, one lowercase letter, and one number"
                },
            },
        }, errorPlacement: function (error) {
            error.appendTo("#errorNewPassword");
        }, submitHandler: function (form, event) {
            event.preventDefault();
            const formData = new FormData(form);
            $.ajax({
                url: './api/v1/general/reset-password.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        toastr.options.onHidden = function () {
                            window.location.href = response.redirect;
                        }
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function (xhr) {
                    const errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : "An unexpected error occurred. Please try again.";
                    toastr.error(errorMessage);
                }
            });
        }
    });
    $('.logout-btn').on('click', function (e) {
        $('#logoutModal').modal('show');
    })
    $('#confirmLogout').on('click', function () {
        $.ajax({
            url: 'api/v1/general/logout.php',
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    toastr.options.onHidden = function () {
                        window.location.href = response.location;
                    };
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                const errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : "An unexpected error occurred. Please try again.";
                toastr.error(errorMessage);
            }
        });
    })
})

$(document).on('click', '.toggle-password', function () {
    const target = $(this).data('target');
    const input = $(target);
    const type = input.attr('type') === 'password' ? 'text' : 'password';
    input.attr('type', type);
    $(this).toggleClass('fa-eye fa-eye-slash');
});