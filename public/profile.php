<?php
require_once __DIR__ . '/../core/config.php';

requireRole(['admin', 'cashier', 'user','security_guard']);

$errors = [];
$success = '';

// Logged user ID
$user_id = $_SESSION['user_id'];

/* ===== FETCH USER DATA FIRST ===== */
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* ===== UPDATE PROFILE ===== */
if (isset($_POST['save_button'])) {

	$name  = trim($_POST['name'] ?? '');
	$email = trim($_POST['email'] ?? '');

	// Name validation
	if ($name === '') {
		$errors['name'] = 'Please enter your name.';
	}

	// Email validation
	if ($email === '') {
		$errors['email'] = 'Please enter your email.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors['email'] = 'Invalid email format.';
	}

	// Check duplicate email (except logged user)
	if (!isset($errors['email'])) {
		$check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
		$check->execute([$email, $user_id]);

		if ($check->rowCount() > 0) {
			$errors['email'] = 'This email is already registered.';
		}
	}

	// Update if no errors
	if (empty($errors)) {
		$stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
		$stmt->execute([$name, $email, $user_id]);

		$_SESSION['user_name'] = $name;
		$success = "Profile updated successfully.";

		// Refresh user data
		$user['name'] = $name;
		$user['email'] = $email;
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
	<title>Profile</title>

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
				<h1 class="page-title m-0">Profile</h1>
			</div>

			<?php if ($success): ?>
				<div class="alert alert-success"><?= $success ?></div>
			<?php endif; ?>

			<div class="col-md-4">
				<div class="card border-0 shadow-lg">
					<div class="card-header border-0 p-4 pb-0 bg-white">
						<h5 class="fw-800 m-0">Edit Profile</h5>
					</div>

					<div class="card-body p-4">
						<form method="POST" class="row g-3" autocomplete="off">

							<!-- FULL NAME -->
							<div class="col-12">
								<label class="form-label small fw-bold text-muted">
									FULL NAME <span class="text-danger">*</span>
								</label>
								<input type="text"
									name="name"
									value="<?= htmlspecialchars($_POST['name'] ?? $user['name']) ?>"
									class="form-control bg-light border-0 shadow-sm <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
									placeholder="e.g. Rahul Sharma">

								<?php if (isset($errors['name'])): ?>
									<small class="text-danger"><?= $errors['name'] ?></small>
								<?php endif; ?>
							</div>

							<!-- EMAIL -->
							<div class="col-12">
								<label class="form-label small fw-bold text-muted">
									EMAIL <span class="text-danger">*</span>
								</label>
								<input type="email"
									name="email"
									value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
									class="form-control bg-light border-0 shadow-sm <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
									placeholder="rahul@example.com">

								<?php if (isset($errors['email'])): ?>
									<small class="text-danger"><?= $errors['email'] ?></small>
								<?php endif; ?>
							</div>

							<!-- BUTTONS -->
							<div class="col-12 mt-4">
								<button type="submit" name="save_button" class="btn btn-brand w-100">
									Update Profile
								</button>
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