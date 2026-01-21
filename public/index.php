<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/config.php';

$errors = [];

header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

//    Handle Login

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_login'])) {

    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {

        $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];

            // Role-based redirect
            if ($user['role'] === 'user') {
                header('Location: ' . BASE_URL . 'dashboard.php');
            } else {
                header('Location: ' . BASE_URL . 'dashboard.php');
            }
            exit;
        } else {
            $errors[] = 'Invalid email or password.';
        }
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Society Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <script>
        window.addEventListener("pageshow", function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>

</head>

<body>
    <div class="container">
        <div class="mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4 mt-5">

                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>


                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title text-center">Login</h3>
                        </div>
                        <div class="card-body">
                            <!-- Login form -->
                            <form method="post" novalidate>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>

                                <button type="submit" name="btn_login" class="btn btn-primary w-100">
                                    Login
                                </button>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Load Bootstrap 5 JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

        <script>
            $(document).ready(function() {
                /* 
                $('#login-form').attr('novalidate', 'novalidate');

                $('#login-form').on('submit', function(e) {
                    e.preventDefault();
                    $('#email').removeClass('is-invalid');
                    $('#password').removeClass('is-invalid');
                    $('.invalid-feedback').hide();
                    var email = $('#email').val().trim();
                    var password = $('#password').val().trim();
                    if (!isValidEmail(email)) {
                        $('#email').addClass('is-invalid');
                        $('#email').next('.invalid-feedback').show();
                        return;
                    }
                    if (password === '') {
                        $('#password').addClass('is-invalid');
                        $('#password').next('.invalid-feedback').show();
                        return;
                    }
                    this.submit();
                });

                function isValidEmail(email) {
                    var emailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
                    return emailRegex.test(email);
                }
                */
            });
        </script>


</body>

</html>