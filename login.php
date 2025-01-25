<?php
session_start();
include_once('Classes/UserAuth.php');
require_once 'Classes/Database.php';
require_once 'Classes/User.php';
require_once 'Classes/Borrower.php';
require_once 'Classes/Lender.php';
$message = "";
if (isset($_GET['e'])) {
    $message = $_GET['e'];
}
// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $login_result = UserAuth::login($email, $password);

    if ($login_result === 1) {
        // Redirect based on user role
        if ($_SESSION['user_role'] == "admin") {
            header('Location: Admin/index.php');
        } else if ($_SESSION['user_role'] == "borrower") {
            header('Location: Borrower/index.php');
        } else if ($_SESSION['user_role'] == "lender") {
            header('Location: Lender/index.php');
        }
        exit(); // Make sure no further code is executed after redirect

    } else if ($login_result === -1) {
        // Invalid login credentials
        $error_message = "Invalid email or password.";

    } else if ($login_result === 0) {
        echo $_SESSION['user_status'];
        // User is being verified
        $error_message = "Your account is being verified. Please wait.";
    } else if ($login_result === 2) {
        $error_message = "Your account has been suspended";
    }
}
include_once('Layout/head.php');
include_once('Layout/header.php'); ?>

<div class="border-top mt-1"></div>
<script>
    toastr.options = {
        "progressBar": true,
        "closeButton": true,
        // Other toastr options you want to add (if any)
    };

    // Only display toastr error if there is a message
    <?php if (!empty($message)): ?>
        toastr.error("<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>");
    <?php endif; ?>
</script>

<div class="container-fluid d-flex justify-content-center align-items-center" style="height: 70vh;">
    <div class="row justify-content-center">
        <div class="col-lg-12 col-xl-12">
            <div class="card shadow-lg p-4 card-form mt-3" style="border-radius: 10px; width:25rem">
                <h4 class="text-center mb-4">Login</h4>
                <!-- Show error message if login failed -->
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger text-center">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                <form action="" method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required autocomplete="off"
                            placeholder="Enter your email">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="position-relative">
                            <input type="password" class="form-control" id="password1" name="password" required
                                autocomplete="off" placeholder="Enter your password">
                            <i class="bi bi-eye-fill position-absolute" id="toggle"
                                style="right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;"></i>
                        </div>
                    </div>
                    <div class="text-start mt-1">
                        <a class="text-center" href="forgetPassword.php">Forgot Password?</a>
                    </div>
                    <div class="d-grid gap-2 mt-2">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>

                    <div class="text-center mt-3">
                        <a href="" data-bs-toggle="modal" data-bs-target="#staticBackdrop">Not Registered?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>



<script>
    const passwordInput = document.getElementById('password1');
    const toggle = document.getElementById('toggle');

    toggle.addEventListener('click', function () {
        // Toggle the password visibility
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);

        // Toggle the eye icon
        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });
</script>

<?php include_once('Layout/footer.php'); ?>