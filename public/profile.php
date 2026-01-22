<?php

require_once __DIR__ . '/../core/config.php';

// if (!isset($_SESSION['user_id'])) {
// 	header('Location: ' . BASE_URL . 'index.php');
// 	exit();
// }

requireRole(['admin','user']);

$errors = [];
$success = '';

// Logged-in user ID
$user_id = $_SESSION['user_id'];

/* ===== UPDATE PROFILE ===== */
if (isset($_POST['save_button'])) {

	$name  = trim($_POST['name'] ?? '');
	$email = trim($_POST['email'] ?? '');

	if ($name === '') {
		$errors[] = 'Please enter your name.';
	}

	if ($email === '') {
		$errors[] = 'Please enter your email.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Invalid email format.';
	}

	if (empty($errors)) {
		$stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
		$stmt->execute([$name, $email, $user_id]);

		$_SESSION['user_name'] = $name; // update navbar name
		$success = 'Profile updated successfully.';
	}
}

/* ===== FETCH USER DATA ===== */
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Layout
include(__DIR__ . '/../resources/layout/header.php');
?>

<div class="container-fluid px-4">
	<h1 class="mt-4">Profile</h1>

	<?php if ($success): ?>
		<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
	<?php endif; ?>

	<?php foreach ($errors as $error): ?>
		<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
	<?php endforeach; ?>

	<div class="col-md-4">
		<div class="card">
			<div class="card-header">
				<h5>Edit Profile</h5>
			</div>
			<div class="card-body">
				<form method="post">
					<div class="mb-3">
						<label>Name</label>
						<input type="text" name="name" class="form-control"
							value="<?= htmlspecialchars($user['name']) ?>">
					</div>

					<div class="mb-3">
						<label>Email</label>
						<input type="email" name="email" class="form-control"
							value="<?= htmlspecialchars($user['email']) ?>">
					</div>

					<button type="submit" name="save_button" class="btn btn-primary">
						Save
					</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php include(__DIR__ . '/../resources/layout/footer.php'); ?>