$(document).ready(function () {
    // Open Login Modal
    $("#login-btn").click(function () {
        $("#login-modal").fadeIn();
    });

    // Open Register Modal
    $("#register-btn").click(function () {
        $("#register-modal").fadeIn();
    });

    // Close Modals
    $(".close").click(function () {
        $(".modal").fadeOut();
    });

    // Handle Login Form Submission
    $("#login-form").submit(function (e) {
        e.preventDefault();
        const email = $("#login-email").val();
        const password = $("#login-password").val();

        $.post("php/login_handler.php", { email: email, password: password }, function (response) {
            if (response.success) {
                window.location.href = "../public/dashboard.php"; // Redirect to dashboard
            } else {
                $("#login-feedback").text(response.message);
            }
        }, "json");
    });

    // Handle Register Form Submission
    $("#register-form").submit(function (e) {
        e.preventDefault();
        const email = $("#register-email").val();
        const password = $("#register-password").val();

        $.post("php/register_handler.php", { email: email, password: password }, function (response) {
            if (response.success) {
                $("#register-feedback").css("color", "green").text(response.message);
            } else {
                $("#register-feedback").text(response.message);
            }
        }, "json");
    });
});
