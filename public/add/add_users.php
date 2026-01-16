<?php

require_once __DIR__ . '/../../core/config.php';

$errors = [];

//    ACCESS CONTROL (ADMIN)

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: logout.php');
    exit();
}

//    HANDLE FORM SUBMIT
if (isset($_POST['add_user'])) {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if ($name === '') {
        $errors[] = 'Please enter name';
    }

    if ($email === '') {
        $errors[] = 'Please enter email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    if ($password === '') {
        $errors[] = 'Please enter password';
    }

    // Check duplicate email
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors[] = 'Email already exists';
        }
    }

    // Insert user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')"
        );
        $stmt->execute([$name, $email, $hashedPassword]);

        $_SESSION['success'] = 'New user added successfully';
        header('Location: ../users.php');
        exit();
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Add User</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="../users.php">Users Management</a></li>
        <li class="breadcrumb-item active">Add User</li>
    </ol>

    <div class="col-md-4">

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Add User</h5>
            </div>

            <div class="card-body">
                <form method="post" autocomplete="off">

                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($name ?? '') ?>" autocomplete="username">
                    </div>

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($email ?? '') ?>"autocomplete="off">
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" autocomplete="new-password">
                    </div>

                    <button type="submit" name="add_user" class="btn btn-primary">
                        Submit
                    </button>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>
