<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/config.php';

// ✅ Allow BOTH admin & user
if (!isset($_SESSION['user_id'])) {
	header('Location: ' . BASE_URL . 'index.php');
	exit();
}

$errors = [];
$success = '';

/* ===== CHANGE PASSWORD ===== */
if (isset($_POST['btn_change_password'])) {

	$current_password = $_POST['current_password'] ?? '';
	$new_password     = $_POST['new_password'] ?? '';
	$confirm_password = $_POST['confirm_password'] ?? '';

	if ($current_password === '') {
		$errors[] = 'Current password is required';
	}

	if ($new_password === '') {
		$errors[] = 'New password is required';
	} elseif (strlen($new_password) < 6) {
		$errors[] = 'New password must be at least 6 characters';
	}

	if ($confirm_password === '') {
		$errors[] = 'Confirm password is required';
	} elseif ($new_password !== $confirm_password) {
		$errors[] = 'Passwords do not match';
	}

	if (empty($errors)) {
		$user_id = $_SESSION['user_id'];

		// Get current password hash
		$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
		$stmt->execute([$user_id]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($user && password_verify($current_password, $user['password'])) {

			$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

			$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
			$stmt->execute([$new_hash, $user_id]);

			$success = 'Password changed successfully';
		} else {
			$errors[] = 'Current password is incorrect';
		}
	}
}

// Layout
include(__DIR__ . '/../resources/layout/header.php');
?>

<div class="container-fluid px-4">
	<h1 class="mt-4">Change Password</h1>

	<?php if ($success): ?>
		<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
	<?php endif; ?>

	<?php foreach ($errors as $error): ?>
		<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
	<?php endforeach; ?>

	<div class="col-md-4">
		<div class="card">
			<div class="card-header">
				<h5>Change Password</h5>
			</div>
			<div class="card-body">
				<form method="post">
					<div class="mb-3">
						<label>Current Password</label>
						<input type="password" name="current_password" class="form-control">
					</div>

					<div class="mb-3">
						<label>New Password</label>
						<input type="password" name="new_password" class="form-control">
					</div>

					<div class="mb-3">
						<label>Confirm New Password</label>
						<input type="password" name="confirm_password" class="form-control">
					</div>

					<button type="submit" name="btn_change_password" class="btn btn-primary">
						Change Password
					</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php include(__DIR__ . '/../resources/layout/footer.php'); ?>