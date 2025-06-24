$(document).ready(function () {
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


    $("#login-form").validate({
        rules: {
            email: {
                required: true,
                email: true
            },
            password: {
                required: true,
                minlength: 8,
                pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&.*])[A-Za-z\d!@#$%^&.*]{8,}$/
            },
        },
        messages: {
            email: {
                required: "Please enter your email address",
                email: "Please enter a valid email address"
            },
            password: {
                required: "Please enter your password",
                minlength: "Password must be at least 8 characters long",
                pattern: "Password must contain at least one uppercase letter, one lowercase letter, and one number"
            },
        },
        errorPlacement: function (error, element) {
            if (element.attr("name") === "password") {
                error.appendTo("#errorPassword");
            } else
                error.insertAfter(element);
        },
        submitHandler: function (form) {
            event.preventDefault();
            const formData = $(form).serialize();
            $.ajax({
                url: "./api/v1/general/login.php",
                type: "POST",
                data: formData,
                dataType: "json",
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

$(document).on('click', '.toggle-password', function () {
    const target = $(this).data('target');
    const input = $(target);
    const type = input.attr('type') === 'password' ? 'text' : 'password';
    input.attr('type', type);
    $(this).toggleClass('fa-eye fa-eye-slash');
});