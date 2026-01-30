<?php
require_once __DIR__ . '/../../core/config.php';

$errors = [];
$name = $email = $mobile = $dob = $gender = $role = '';
$password = '';
$user = null;

//    ACCESS CONTROL
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
	header('Location: logout.php');
	exit();
}

//    FETCH USER DATA
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
	$stmt->execute([(int)$_GET['id']]);
	$user = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$user) {
		header('Location: ../users.php');
		exit();
	}

	// Pre-fill form variables
	$name   = $user['name'] ?? '';
	$email  = $user['email'] ?? '';
	$mobile = $user['mobile'] ?? '';
	$dob    = $user['dob'] ?? '';
	$gender = $user['gender'] ?? '';
	$role   = $user['role'] ?? '';
} else {
	header('Location: ../users.php');
	exit();
}

//    HANDLE FORM SUBMIT
if (isset($_POST['edit_user'])) {

	$id       = (int)$_POST['id'];
	$name     = trim($_POST['user_name'] ?? '');
	$email    = trim($_POST['user_email'] ?? '');
	$password = $_POST['user_password'] ?? '';
	$mobile   = trim($_POST['user_mobile'] ?? '');
	$dob      = $_POST['user_dob'] ?? '';
	$gender   = $_POST['user_gender'] ?? '';
	$role     = $_POST['user_role'] ?? '';

	// VALIDATION
	if ($name === '') $errors['name'] = 'Please enter name';
	if ($email === '') $errors['email'] = 'Please enter email';
	elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email';
	if ($mobile === '') $errors['mobile'] = 'Please enter mobile';
	elseif (!preg_match('/^[0-9]{10}$/', $mobile)) $errors['mobile'] = 'Mobile must be 10 digits';
	if ($dob === '') {
		$errors['dob'] = 'Please select date of birth';
	} elseif (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dob)) {
		$errors['dob'] = 'Date of birth must be in dd/mm/yyyy format';
	} else {
		// convert dd/mm/yyyy to yyyy-mm-dd for database
		$parts = explode('/', $dob);
		if (checkdate($parts[1], $parts[0], $parts[2])) {
			$dob = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
		} else {
			$errors['dob'] = 'Invalid date';
		}
	}
	if (!in_array($gender, ['Male', 'Female', 'Other'])) $errors['gender'] = 'Please select gender';
	if (!in_array($role, ['admin', 'cashier', 'user'])) {
		$errors['role'] = 'Please select role';
	}

	// CHECK DUPLICATE EMAIL & MOBILE (exclude current user)
	if (empty($errors)) {
		$check = $pdo->prepare("SELECT id, email, mobile FROM users WHERE (email = ? OR mobile = ?) AND id != ?");
		$check->execute([$email, $mobile, $id]);
		$existing = $check->fetch(PDO::FETCH_ASSOC);

		if ($existing) {
			if ($existing['email'] === $email) {
				$errors['email'] = 'Email already exists';
			}
			if ($existing['mobile'] === $mobile) {
				$errors['mobile'] = 'Mobile number already exists';
			}
		}
	}

	// UPDATE USER
	if (empty($errors)) {
		if ($password === '') {
			$stmt = $pdo->prepare("UPDATE users SET name=?, email=?, mobile=?, dob=?, gender=?, role=? WHERE id=?");
			$stmt->execute([$name, $email, $mobile, $dob, $gender, $role, $id]);
		} else {
			$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
			$stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=?, mobile=?, dob=?, gender=?, role=? WHERE id=?");
			$stmt->execute([$name, $email, $hashedPassword, $mobile, $dob, $gender, $role, $id]);
		}

		$_SESSION['success'] = 'User updated successfully';
		header('Location: ../users.php');
		exit();
	}
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4 mb-4">
	<h1 class="mt-4">Edit User</h1>

	<ol class="breadcrumb mb-4">
		<li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="../users.php">Users Management</a></li>
		<li class="breadcrumb-item active">Edit User</li>
	</ol>

	<div class="row justify-content-center">
		<div class="col-12 col-xl-10">

			<div class="card">
				<div class="card-header">
					<h5 class="card-title mb-0">Edit User</h5>
				</div>

				<div class="card-body">
					<form method="post" autocomplete="off" id="editUserForm" novalidate>
						<input type="text" name="nope" style="display:none">
						<input type="password" name="nope_pass" style="display:none">

						<div class="row">

							<div class="col-md-6 mb-3">
								<label class="form-label">Name</label>
								<input type="text" class="form-control" name="user_name" autocomplete="new-name" required value="<?= htmlspecialchars($name) ?>">
								<?php if (isset($errors['name'])): ?><small class="text-danger"><?= $errors['name'] ?></small><?php endif; ?>
							</div>

							<div class="col-md-6 mb-3">
								<label class="form-label">Email</label>
								<input type="text" class="form-control" name="user_email" autocomplete="new-email" required value="<?= htmlspecialchars($email) ?>">
								<?php if (isset($errors['email'])): ?><small class="text-danger"><?= $errors['email'] ?></small><?php endif; ?>
							</div>

							<div class="col-md-6 mb-3">
								<label class="form-label">Password (Set New Password)</label>
								<input type="password" class="form-control" name="user_password" autocomplete="new-password">
								<?php if (isset($errors['password'])): ?><small class="text-danger"><?= $errors['password'] ?></small><?php endif; ?>
							</div>

							<div class="col-md-6 mb-3">
								<label class="form-label">Mobile</label>
								<input type="text" class="form-control" name="user_mobile" autocomplete="off" inputmode="numeric" required value="<?= htmlspecialchars($mobile) ?>">
								<?php if (isset($errors['mobile'])): ?><small class="text-danger"><?= $errors['mobile'] ?></small><?php endif; ?>
							</div>

							<div class="col-md-6 mb-3">
								<label class="form-label">Date of Birth</label>
								<input type="date"
									class="form-control"
									name="user_dob"
									required
									value="<?= htmlspecialchars($dob) ?>">

								<?php if (isset($errors['dob'])): ?><small class="text-danger"><?= $errors['dob'] ?></small><?php endif; ?>
							</div>


							<div class="col-md-6 mb-3">
								<label class="form-label">Gender</label>
								<select name="user_gender" class="form-select" autocomplete="off" required>
									<option value="">Select Gender</option>
									<option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
									<option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
									<option value="Other" <?= $gender === 'Other' ? 'selected' : '' ?>>Other</option>
								</select>
								<?php if (isset($errors['gender'])): ?><small class="text-danger"><?= $errors['gender'] ?></small><?php endif; ?>
							</div>

							<div class="col-md-6 mb-3">
								<label class="form-label">Role</label>
								<select name="user_role" class="form-select" required>
									<option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
									<option value="cashier" <?= $role === 'cashier' ? 'selected' : '' ?>>Cashier</option>
									<option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
								</select>

								<?php if (isset($errors['role'])): ?><small class="text-danger"><?= $errors['role'] ?></small><?php endif; ?>
							</div>

						</div>

						<input type="hidden" name="id" value="<?= $user['id'] ?>">

						<button type="submit" name="edit_user" class="btn btn-primary">Update</button>
						<a href="<?= BASE_URL ?>users.php" class="btn btn-secondary">
							Back
						</a>
					</form>
				</div>
			</div>

		</div>
	</div>
</div>

<script>
	document.addEventListener("DOMContentLoaded", function() {
		// Prevent Chrome autofill
		const fakeText = document.querySelector('input[name="nope"]');
		const fakePass = document.querySelector('input[name="nope_pass"]');
		if (fakeText && fakePass) {
			fakeText.focus();
			fakePass.focus();
			document.body.focus();
		}
	});
</script>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>