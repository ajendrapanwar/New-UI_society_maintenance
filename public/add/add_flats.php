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


//    ADD FLAT

if (isset($_POST['add_flats'])) {

	$flat_number  = trim($_POST['flat_number']);
	$floor        = trim($_POST['floor']);
	$block_number = trim($_POST['block_number']);
	$flat_type    = trim($_POST['flat_type']);
	$created_at   = date('Y-m-d H:i:s');

	if ($flat_number === '') {
		$errors[] = 'Flat Number is required';
	}

	if ($floor === '') {
		$errors[] = 'Floor Number is required';
	}

	if ($flat_type === '') {
		$errors[] = 'Please select flat type';
	}

	if (empty($errors)) {

		$sql = "INSERT INTO flats 
                (flat_number, floor, block_number, flat_type, created_at)
                VALUES (?, ?, ?, ?, ?)";

		$pdo->prepare($sql)->execute([
			$flat_number,
			$floor,
			$block_number,
			$flat_type,
			$created_at
		]);

		$_SESSION['success'] = 'New flat added successfully';
		header('Location: ' . BASE_URL . 'flats.php');
		exit();
	}
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4">
	<h1 class="mt-4">Add Flat</h1>

	<ol class="breadcrumb mb-4">
		<li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
		<li class="breadcrumb-item"><a href="<?= BASE_URL ?>flats.php">Flats Management</a></li>
		<li class="breadcrumb-item active">Add Flat</li>
	</ol>

	<div class="col-md-4">

		<?php foreach ($errors as $error): ?>
			<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
		<?php endforeach; ?>

		<div class="card">
			<div class="card-header">
				<h5 class="card-title">Add Flat</h5>
			</div>

			<div class="card-body">
				<form method="post">

					<div class="mb-3">
						<label class="form-label">Flat Number</label>
						<input type="text" class="form-control" name="flat_number"
							value="<?= htmlspecialchars($_POST['flat_number'] ?? '') ?>">
					</div>

					<div class="mb-3">
						<label class="form-label">Floor</label>
						<input type="number" class="form-control" name="floor"
							value="<?= htmlspecialchars($_POST['floor'] ?? '') ?>">
					</div>

					<div class="mb-3">
						<label class="form-label">Block Number</label>
						<input type="text" class="form-control" name="block_number"
							value="<?= htmlspecialchars($_POST['block_number'] ?? '') ?>">
					</div>

					<div class="mb-3">
						<label class="form-label">Type</label>
						<select name="flat_type" class="form-control">
							<option value="">Select Type</option>
							<?php foreach ($flatTypes as $t): ?>
								<option value="<?= $t ?>">
									<?= $t ?>
								</option>
							<?php endforeach; ?>
						</select>

					</div>
					<button type="submit" name="add_flats" class="btn btn-primary">
						Add Flat
					</button>

				</form>
			</div>
		</div>
	</div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>