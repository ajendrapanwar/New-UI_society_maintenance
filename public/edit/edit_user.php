<?php

require_once __DIR__ . '/../../core/config.php';


//    ACCESS CONTROL

if (
	!isset($_SESSION['user_id']) ||
	$_SESSION['user_role'] !== 'admin'
) {
	header('Location: ' . BASE_URL . 'logout.php');
	exit();
}

$errors = [];
$user = null;


//    UPDATE USER

if (isset($_POST['edit_user'])) {

	$id       = (int) $_POST['id'];
	$name     = trim($_POST['name']);
	$email    = trim($_POST['email']);
	$password = $_POST['password'];

	if ($name === '') {
		$errors[] = 'Please enter your name';
	}

	if ($email === '') {
		$errors[] = 'Please enter your email address';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors[] = 'Please enter a valid email address';
	}

	if (empty($errors)) {

		if ($password === '') {
			$sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";
			$pdo->prepare($sql)->execute([$name, $email, $id]);
		} else {
			$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
			$sql = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?";
			$pdo->prepare($sql)->execute([$name, $email, $hashedPassword, $id]);
		}

		$_SESSION['success'] = 'User data has been updated successfully';
		header('Location: ' . BASE_URL . 'users.php');
		exit();
	}
}


//    FETCH USER DATA

if (isset($_GET['id']) && is_numeric($_GET['id'])) {

	$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
	$stmt->execute([(int)$_GET['id']]);
	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$user) {
		header('Location: ' . BASE_URL . 'users.php');
		exit();
	}
} else {
	header('Location: ' . BASE_URL . 'users.php');
	exit();
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4">
	<h1 class="mt-4">Edit User</h1>

	<ol class="breadcrumb mb-4">
		<li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="<?= BASE_URL ?>users.php">Users Management</a></li>
		<li class="breadcrumb-item active">Edit User</li>
	</ol>

	<div class="col-md-4">

		<?php foreach ($errors as $error): ?>
			<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
		<?php endforeach; ?>

		<div class="card">
			<div class="card-header">
				<h5 class="card-title">Edit User Data</h5>
			</div>

			<div class="card-body">
				<form method="post">
					<div class="mb-3">
						<label>Name</label>
						<input type="text" class="form-control"
							name="name"
							value="<?= htmlspecialchars($user['name']) ?>">
					</div>

					<div class="mb-3">
						<label>Email</label>
						<input type="email" class="form-control"
							name="email"
							value="<?= htmlspecialchars($user['email']) ?>">
					</div>

					<div class="mb-3">
						<label>Password (Set New Password)</label>
						<input type="password" class="form-control" name="password">
					</div>

					<input type="hidden" name="id" value="<?= $user['id'] ?>">

					<button type="submit" name="edit_user" class="btn btn-primary">
						Update
					</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>