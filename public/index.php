<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize specific error variables
 $email_error = '';
 $password_error = '';
 $login_error = '';
 $email_value = '';

// Check if config exists
if (file_exists(__DIR__ . '/../core/config.php')) {
    require_once __DIR__ . '/../core/config.php';
} else {
    if (!defined('BASE_URL')) define('BASE_URL', '/');
}

// Security Headers
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit;
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_login'])) {

    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Keep the value for the input field
    $email_value = $email;

    // 1. Validate Email
    if ($email === '') {
        $email_error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_error = 'Please enter a valid email address.';
    }

    // 2. Validate Password
    if ($password === '') {
        $password_error = 'Please enter your password.';
    }

    // 3. Database Check (Only if no field errors)
    if (empty($email_error) && empty($password_error)) {
        if (isset($pdo)) {
            $stmt = $pdo->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];

                // Role-based redirect
                if ($user['role'] === 'admin') {
                    header('Location: ' . BASE_URL . 'dashboard.php');
                } elseif ($user['role'] === 'cashier') {
                    header('Location: ' . BASE_URL . 'dashboard.php');
                } else {
                    header('Location: ' . BASE_URL . 'dashboard.php');
                }
                exit;
            } else {
                $login_error = 'Invalid email or password.';
            }
        } else {
            $login_error = 'Database connection not configured.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Society Management System - Login</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="<?= BASE_URL ?>../assets/css/login_style.css">

    <script>
        // Prevent back-forward cache issues
        window.addEventListener("pageshow", function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</head>

<body class="bg-slate-50">

    <div class="flex min-h-screen">
        <!-- Left Side: Branding -->
        <div class="hidden lg:flex w-1/2 bg-pattern bg-indigo-700 items-center justify-center p-12 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/50 to-transparent"></div>

            <div class="relative z-10 max-w-lg text-white">
                <div class="mb-10">
                    <span class="text-3xl font-extrabold tracking-tighter">Society <span class="text-indigo-300">Maintenance</span></span>
                </div>
                <h2 class="text-5xl font-extrabold leading-tight mb-6">Effortless Society Governance.</h2>
                <p class="text-indigo-100 text-lg leading-relaxed mb-8">
                    Modernizing residential management with automated billing, visitor tracking, and resident engagement in one secure ecosystem.
                </p>

                <div class="space-y-4">
                    <div class="flex items-start space-x-4 bg-white/10 backdrop-blur-md p-4 rounded-2xl border border-white/20">
                        <div class="bg-indigo-500/30 p-2 rounded-lg"><i class="fa-solid fa-shield-halved text-white"></i></div>
                        <div>
                            <p class="font-bold">Enterprise Grade Security</p>
                            <p class="text-xs text-indigo-200">Your data is encrypted and backed up hourly.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Forms -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8 md:p-16 bg-white">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="mb-10 lg:hidden text-center">
                    <span class="text-3xl font-black text-indigo-600 tracking-tighter uppercase">Society Maintenance</span>
                </div>

                <div class="mb-8">
                    <h3 class="text-3xl font-bold text-slate-900">Login</h3>
                    <p class="text-slate-500 mt-2">Access your management console.</p>
                </div>

                <!-- GENERAL LOGIN ERROR (Top Alert) -->
                <?php if (!empty($login_error)): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r shadow-sm animate-pulse">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Login Error</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <p><?php echo htmlspecialchars($login_error); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tab Switcher -->
                <div class="inline-flex p-1 bg-slate-100 rounded-2xl mb-8 w-full border border-slate-200">
                    <button onclick="switchTab('otp')" id="btn-otp" class="flex-1 py-3 text-sm font-bold rounded-xl text-slate-500 transition-all hover:text-slate-700">OTP Access</button>
                    <button onclick="switchTab('pass')" id="btn-pass" class="flex-1 py-3 text-sm font-bold rounded-xl bg-white shadow-sm text-indigo-600 transition-all">Credentials</button>
                </div>

                <!-- OTP FORM -->
                <form action="" method="POST" id="form-otp" class="hidden space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2 ml-1">Registered Mobile Number</label>
                        <div class="relative group">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 font-bold group-focus-within:text-indigo-600 transition-colors">+91</span>
                            <input type="tel" name="phone" placeholder="00000 00000"
                                class="w-full pl-14 pr-4 py-4 rounded-2xl border border-slate-200 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/5 outline-none transition-all placeholder:text-slate-300">
                        </div>
                    </div>
                    <div class="p-3 bg-yellow-50 text-yellow-800 text-xs rounded-lg border border-yellow-200">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> OTP Login is not configured in the backend yet.
                    </div>
                    <button type="button" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-2xl shadow-xl shadow-indigo-100 transition-all active:scale-[0.98] opacity-75 cursor-not-allowed">
                        Send Access Code
                    </button>
                </form>

                <!-- PASSWORD FORM -->
                <form action="" method="POST" id="form-pass" class="space-y-6">
                    
                    <!-- Email Field -->
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2 ml-1">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-solid fa-envelope"></i></div>
                            
                            <!-- Dynamic class added here: If error, border becomes red -->
                            <input type="email" name="email" placeholder="admin@society.com"
                                value="<?php echo htmlspecialchars($email_value); ?>"
                                class="w-full pl-12 pr-4 py-4 rounded-2xl border outline-none transition-all placeholder:text-slate-300 
                                <?php echo (!empty($email_error) ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-200' : 'border-slate-200 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/5'); ?>">
                        </div>
                        
                        <!-- ERROR MESSAGE BLOCK ADDED HERE -->
                        <?php if (!empty($email_error)): ?>
                            <p class="text-red-500 text-xs mt-2 ml-1 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i> <?php echo htmlspecialchars($email_error); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex justify-between mb-2 ml-1">
                            <label class="text-xs font-bold uppercase text-slate-500">Secure Password</label>
                            <a href="#" class="text-xs font-bold text-indigo-600 hover:text-indigo-800">Forgot Password?</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"><i class="fa-solid fa-lock"></i></div>
                            
                            <!-- Dynamic class added here: If error, border becomes red -->
                            <input type="password" name="password" placeholder="••••••••" 
                                class="w-full pl-12 pr-4 py-4 rounded-2xl border outline-none transition-all 
                                <?php echo (!empty($password_error) ? 'border-red-500 text-red-900 focus:border-red-500 focus:ring-red-200' : 'border-slate-200 focus:border-indigo-600 focus:ring-4 focus:ring-indigo-600/5'); ?>">
                        </div>

                        <!-- ERROR MESSAGE BLOCK ADDED HERE -->
                        <?php if (!empty($password_error)): ?>
                            <p class="text-red-500 text-xs mt-2 ml-1 flex items-center">
                                <i class="fa-solid fa-circle-exclamation mr-1"></i> <?php echo htmlspecialchars($password_error); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="btn_login" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-4 rounded-2xl shadow-xl shadow-slate-200 transition-all active:scale-[0.98]">
                        Login to Dashboard
                    </button>
                </form>

            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            const isOtp = type === 'otp';
            const otpForm = document.getElementById('form-otp');
            const passForm = document.getElementById('form-pass');
            const btnOtp = document.getElementById('btn-otp');
            const btnPass = document.getElementById('btn-pass');

            otpForm.classList.toggle('hidden', !isOtp);
            passForm.classList.toggle('hidden', isOtp);

            if (isOtp) {
                btnOtp.className = 'flex-1 py-3 text-sm font-bold rounded-xl bg-white shadow-sm text-indigo-600 transition-all';
                btnPass.className = 'flex-1 py-3 text-sm font-bold rounded-xl text-slate-500 transition-all';
            } else {
                btnPass.className = 'flex-1 py-3 text-sm font-bold rounded-xl bg-white shadow-sm text-indigo-600 transition-all';
                btnOtp.className = 'flex-1 py-3 text-sm font-bold rounded-xl text-slate-500 transition-all';
            }
        }
    </script>
</body>
</html>