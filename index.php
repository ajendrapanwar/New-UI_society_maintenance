<?php

// Check if the system setup is complete

require_once 'config.php';

if (isset($_SESSION['user_id'])) 
{
    header('Location: dashboard.php');
    exit();
}

$errorMessage = '';

if(isset($_POST['btn_login']))
{
    // Get the email and password entered by the user
    $email = $_POST['email'];
    
    $password = $_POST['password'];

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Validate email address format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
    {
        $errors[] = 'Please enter a valid email address.';
    }

    // Validate password field is not empty
    if (empty($password)) 
    {
        $errors[] = 'Please enter a password.';
    }

    // If there are no validation errors, attempt to log in
    if(empty($errors)) 
    {

        // Query the database to see if a user with that username exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // If the user exists, retrieve their password hash from the database
        if ($user) 
        {
            $passwordHash = $user['password'];

            // Use the password_verify function to check if the entered password matches the password hash
            if (password_verify($password, $passwordHash)) 
            {
                // Password is correct, log the user in
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                if($user['role'] == 'user')
                {
                    header('Location: bills.php');
                }
                else
                {
                    header('Location: dashboard.php');
                }
                exit;
            } 
            else
            {
                // Password is incorrect, show an error message
                $errors[] = "Invalid password";
            }
        } 
        else 
        {
            // User not found, show an error message
            $errors[] = "email not found in database";
        }
    }
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Society Management System</title>
        <!-- Load Bootstrap 5 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    </head>
    <body>
        <div class="container">
            <div class="mt-5">
                <h1 class="text-center">Society Management System</h1>
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4 mt-5">
                        <?php
                            
                        if(isset($errors))
                        {
                            foreach ($errors as $error) 
                            {
                                echo "<div class='alert alert-danger'>$error</div>";
                            }
                        }
                        ?>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title text-center">Login</h3>
                            </div>
                            <div class="card-body">                            
                            <!-- Login form -->
                            <form id="login-form" method="post">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email">
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password">
                                    <div class="invalid-feedback">Please enter a password.</div>
                                </div>
                                <button type="submit" name="btn_login" class="btn btn-primary">Login</button>
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
            // Disable HTML5 validation
            /*$('#login-form').attr('novalidate', 'novalidate');

            // Validate form input on submit
            $('#login-form').on('submit', function(e) {
                // Prevent form submission
                e.preventDefault();

                // Remove any existing error messages
                $('#email').removeClass('is-invalid');
                $('#password').removeClass('is-invalid');
                $('.invalid-feedback').hide();

                // Get form input values
                var email = $('#email').val().trim();
                var password = $('#password').val().trim();

                // Validate email address format
                if (!isValidEmail(email)) {
                $('#email').addClass('is-invalid');
                $('#email').next('.invalid-feedback').show();
                return;
                }

                // Validate password field is not empty
                if (password === '') {
                $('#password').addClass('is-invalid');
                $('#password').next('.invalid-feedback').show();
                return;
                }

                // Submit form if input is valid
                this.submit();
            });
            });

            // Function to validate email address format
            function isValidEmail(email) {
            var emailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
            return emailRegex.test(email);
            }*/

        </script>

    </body>
</html>

