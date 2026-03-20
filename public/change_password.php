<?php
require_once __DIR__ . '/../core/config.php';

requireRole(['admin', 'cashier', 'user','security_guard']);

$errors = [];
$success = '';

/* ===== CHANGE PASSWORD ===== */
if (isset($_POST['btn_change_password'])) {

	$current_password = $_POST['current_password'] ?? '';
	$new_password     = $_POST['new_password'] ?? '';
	$confirm_password = $_POST['confirm_password'] ?? '';

	// Current password validation
	if ($current_password === '') {
		$errors['current_password'] = 'Current password is required';
	}

	// New password validation
	if ($new_password === '') {
		$errors['new_password'] = 'New password is required';
	} elseif (strlen($new_password) < 6) {
		$errors['new_password'] = 'New password must be at least 6 characters';
	}

	// Confirm password validation
	if ($confirm_password === '') {
		$errors['confirm_password'] = 'Confirm password is required';
	} elseif ($new_password !== $confirm_password) {
		$errors['confirm_password'] = 'Passwords do not match';
	}

	// Check current password from DB
	if (empty($errors)) {
		$user_id = $_SESSION['user_id'];

		$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
		$stmt->execute([$user_id]);
		$user = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($user && password_verify($current_password, $user['password'])) {

			$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

			$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
			$stmt->execute([$new_hash, $user_id]);

			$success = 'Password changed successfully';
		} else {
			$errors['current_password'] = 'Current password is incorrect';
		}
	}
}

// Layout
include(__DIR__ . '/../resources/layout/header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>

	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Change Password</title>

	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
	<link rel="stylesheet" href="../assets/css/styles.css">

</head>

<body>

	<div class="main-wrapper">

		<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

		<main id="main-content">
			<div class="d-flex justify-content-between align-items-center mb-4">
				<h1 class="page-title m-0">Change Password</h1>
			</div>

			<?php if ($success): ?>
				<div class="alert alert-success"><?= $success ?></div>
			<?php endif; ?>

			<div class="col-md-5">
				<div class="card border-0 shadow-lg">
					<div class="card-header border-0 p-4 pb-0 bg-white">
						<h5 class="fw-800 m-0">Change Password</h5>
					</div>

					<div class="card-body p-4">
						<form method="POST" class="row g-3" autocomplete="off">

							<!-- CURRENT PASSWORD -->
							<div class="col-12">
								<label class="form-label small fw-bold text-muted">
									CURRENT PASSWORD <span class="text-danger">*</span>
								</label>
								<input type="password"
									name="current_password"
									class="form-control bg-light border-0 shadow-sm <?= isset($errors['current_password']) ? 'is-invalid' : '' ?>"
									placeholder="Enter current password">

								<?php if (isset($errors['current_password'])): ?>
									<small class="text-danger"><?= $errors['current_password'] ?></small>
								<?php endif; ?>
							</div>

							<!-- NEW PASSWORD -->
							<div class="col-12">
								<label class="form-label small fw-bold text-muted">
									NEW PASSWORD <span class="text-danger">*</span>
								</label>
								<input type="password"
									name="new_password"
									class="form-control bg-light border-0 shadow-sm <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>"
									placeholder="Enter new password">

								<?php if (isset($errors['new_password'])): ?>
									<small class="text-danger"><?= $errors['new_password'] ?></small>
								<?php endif; ?>
							</div>

							<!-- CONFIRM PASSWORD -->
							<div class="col-12">
								<label class="form-label small fw-bold text-muted">
									CONFIRM NEW PASSWORD <span class="text-danger">*</span>
								</label>
								<input type="password"
									name="confirm_password"
									class="form-control bg-light border-0 shadow-sm <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
									placeholder="Confirm new password">

								<?php if (isset($errors['confirm_password'])): ?>
									<small class="text-danger"><?= $errors['confirm_password'] ?></small>
								<?php endif; ?>
							</div>

							<!-- BUTTONS -->
							<div class="col-12 mt-4">
								<button type="submit" name="btn_change_password" class="btn btn-brand w-100 ">
									Change Password
								</button>

								<a href="<?= BASE_URL ?>dashboard.php" class="btn btn-light w-100 mt-2 py-2 shadow-sm">
									Back to Dashboard
								</a>
							</div>

						</form>
					</div>
				</div>
			</div>

		</main>

	</div>


</body>

</html>

<?php include(__DIR__ . '/../resources/layout/footer.php'); ?>