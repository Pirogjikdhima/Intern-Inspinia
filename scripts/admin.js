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



$(document).ready(function() {
    $("#addForm").validate({
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
            birthday: {
                required: true,
                over18: true,
            },
            phone: {
                required: true,
                pattern: /^\d{9,}$/
            },
            email: {
                required: true,
                email: true
            },
            address: {
                required: false,
            },
            password: {
                required: true,
                minlength: 8,
                pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&.*])[A-Za-z\d!@#$%^&.*]{8,}$/
            },
            confirmPassword: {
                required: true,
                minlength: 8,
                equalTo: "#add-password"
            },
            termsChecked: {
                required: true,
            },
        },
        messages: {
            username: {
                required: "Please enter your username",
                minlength: "Your username must be at least 3 characters long",
                pattern: "Username can only contain letters and numbers"
            },
            firstName: {
                required: "Please enter your first name",
                minlength: "Your first name must be at least 2 characters long",
                pattern: "First name can only contain letters"
            },
            lastName: {
                required: "Please enter your last name",
                minlength: "Your last name must be at least 3 characters long",
                pattern: "Last name can only contain letters"
            },
            birthday: {
                required: "Please enter your date of birth",
                over18: "You must be at least 18 years old."
            },
            phone: {
                required: "Please enter your phone number",
                pattern: "Phone number must be at least 9 digits long"
            },
            email: {
                required: "Please enter your email address",
                email: "Please enter a valid email address"
            },
            password: {
                required: "Please enter your password",
                minlength: "Password must be at least 8 characters long",
                pattern: "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character"
            },
            confirmPassword: {
                required: "Please confirm your password",
                minlength: "Password must be at least 8 characters long",
                equalTo: "Passwords do not match"
            },
            termsChecked: {
                required: "You must accept the terms and conditions"
            },
        },
        errorPlacement: function (error, element) {
            if (element.attr("name") === "termsChecked")
                error.appendTo("#add-errorTermsChecked");
            else
                error.insertAfter(element);
        },
        submitHandler: function (form) {
            const formData = new FormData(form);
            formData.append('action', 'add-user');
            formData.append('role', PHP_SESSION_ROLE);

            $.ajax({
                url: "./api/v1/admin/add-user.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                dataType: "json",
                success: function (response) {
                    if (response.success) {
                        toastr.options.onHidden = function () {
                            location.reload();
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
            return false;
        }
    });

    $("#updateForm").validate({
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
            birthday: {
                required: true,
                over18: true
            },
            phone: {
                required: true,
                pattern: /^\d{9,}$/
            },
            email: {
                required: true,
                email: true
            },
            address: {
                required: false
            },
            termsChecked: {
                required: true
            },
            manager_id: {
                required: false
            },
            role_id: {
                required: false
            }
        },
        messages: {
            username: {
                required: "Please enter your username",
                minlength: "Your username must be at least 3 characters long",
                pattern: "Username can only contain letters and numbers"
            },
            firstName: {
                required: "Please enter your first name",
                minlength: "Your first name must be at least 2 characters long",
                pattern: "First name can only contain letters"
            },
            lastName: {
                required: "Please enter your last name",
                minlength: "Your last name must be at least 3 characters long",
                pattern: "Last name can only contain letters"
            },
            birthday: {
                required: "Please enter your date of birth",
                over18: "You must be at least 18 years old."
            },
            phone: {
                required: "Please enter your phone number",
                pattern: "Phone number must be at least 9 digits long"
            },
            email: {
                required: "Please enter your email address",
                email: "Please enter a valid email address"
            },
            termsChecked: {
                required: "You must accept the terms and conditions"
            }
        },
        errorPlacement: function (error, element) {
            if (element.attr("name") === "termsChecked") {
                error.appendTo("#update-errorTermsChecked");
            } else {
                error.insertAfter(element);
            }
        },
        submitHandler: function (form) {
            const formData = new FormData(form);
            formData.append('user_id', $('#update-user-id').val());
            formData.append('action', 'update-user');
            formData.append('role', PHP_SESSION_ROLE);
            const termsChecked = $('#update-termsChecked').is(':checked') ? 'true' : 'false';
            formData.set('termsChecked', termsChecked);

            $.ajax({
                url: './api/v1/admin/update-user.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        toastr.options.onHidden = function () {
                            location.reload();
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
            return false;
        }
    });
});