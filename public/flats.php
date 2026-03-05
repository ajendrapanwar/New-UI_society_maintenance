<?php

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Admin access check
requireRole(['admin']);


/* ADD FLAT FROM MODAL  */
$errors = [];
if (isset($_POST['add_flats'])) {

	$floor        = trim($_POST['floor'] ?? '');
	$flat_number  = trim($_POST['flat_number'] ?? '');
	$block_number = trim($_POST['block_number'] ?? '');
	$flat_type    = trim($_POST['flat_type'] ?? '');
	$created_at   = date('Y-m-d H:i:s');

	// ===== VALIDATION =====
	if ($floor === '') {
		$errors['floor'] = 'Floor Number is required';
	} elseif (!is_numeric($floor)) {
		$errors['floor'] = 'Floor must be a number';
	}

	if ($flat_number === '') {
		$errors['flat_number'] = 'Flat Number is required';
	}

	if ($block_number === '') {
		$errors['block_number'] = 'Wing/Block is required';
	}

	if ($flat_type === '') {
		$errors['flat_type'] = 'Please select flat type';
	}

	// ===== DUPLICATE CHECK =====
	if (empty($errors)) {
		$check = $pdo->prepare("
            SELECT id FROM flats 
            WHERE floor = ? AND flat_number = ? AND block_number = ?
        ");
		$check->execute([$floor, $flat_number, $block_number]);

		if ($check->fetch()) {
			$errors['duplicate'] =
				"This flat already exists on floor {$floor}, flat {$flat_number}, block {$block_number}";
		}
	}

	// ===== INSERT IF NO ERRORS =====
	if (empty($errors)) {
		$stmt = $pdo->prepare("
            INSERT INTO flats 
            (flat_number, floor, block_number, flat_type, created_at)
            VALUES (?, ?, ?, ?, ?)
        ");

		if ($stmt->execute([$flat_number, $floor, $block_number, $flat_type, $created_at])) {

			flash_set('success', 'New flat added successfully');
			header('Location: ' . BASE_URL . 'flats.php');
			exit();
		} else {
			flash_set('err', 'Database error! flats not add.');
			header('Location: ' . BASE_URL . 'flats.php');
			exit();
		}
	}
}


/* FETCH FLAT TYPES FROM DATABASE */
$stmt = $pdo->query("SELECT type_name FROM flat_types ORDER BY type_name ASC");
$flatTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle Delete
if (
	isset($_GET['action'], $_GET['id']) &&
	$_GET['action'] === 'delete' &&
	is_numeric($_GET['id'])
) {
	$stmt = $pdo->prepare("DELETE FROM flats WHERE id = ?");
	$stmt->execute([$_GET['id']]);

	// $_SESSION['success'] = 'User has been removed successfully.';
	flash_set('success', 'Flat has been removed successfully');
	header('Location: ' . BASE_URL . 'flats.php');
	exit;
}

include('../resources/layout/header.php');

?>

<!DOCTYPE html>
<html lang="en">

<head>

	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Manage Flats</title>

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
			<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">

				<h1 class="fw-800 m-0">Flat Inventory</h1>

				<div class="sm-w-100 mt-3 mt-md-0">
					<div class="d-flex flex-column flex-md-row gap-2">

						<a href="<?= BASE_URL ?>flat_types.php" class="btn btn-brand shadow-sm">
							<i class="fa-solid fa-building me-2"></i> Flat Type
						</a>

						<button type="button" class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#addFlatModal">
							<i class="fa-solid fa-plus me-2"></i> Add Flat
						</button>

					</div>
				</div>

			</div>


			<div class="data-card shadow-sm border-0">
				<div class="table-responsive">
					<table class="table table-hover w-100" id="flats-table">
						<thead>
							<tr>
								<th>Flat No</th>
								<th>Block</th>
								<th>Flat Type</th>
								<th>Floor</th>
								<th>Created Date</th>
								<th class="text-end">Action</th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</main>
	</div>

	<!-- Add Flat Popup -->
	<div class="modal fade" id="addFlatModal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">

				<div class="modal-header border-0 p-4">
					<h5 class="modal-title fw-800">New Flat Entry</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<div class="modal-body p-4 pt-0">

					<?php if (isset($errors['duplicate'])): ?>
						<div class="alert alert-danger">
							<?= htmlspecialchars($errors['duplicate']) ?>
						</div>
					<?php endif; ?>

					<form method="POST" class="row g-3">

						<!-- FLOOR -->
						<div class="col-6">
							<label class="form-label small fw-bold text-muted">
								FLOOR <span class="text-danger">*</span>
							</label>
							<input type="text"
								name="floor"
								value="<?= htmlspecialchars($_POST['floor'] ?? '') ?>"
								class="form-control bg-light border-0"
								placeholder="e.g. 3">

							<?php if (isset($errors['floor'])): ?>
								<small class="text-danger">
									<?= $errors['floor'] ?>
								</small>
							<?php endif; ?>
						</div>

						<!-- FLAT NUMBER -->
						<div class="col-6">
							<label class="form-label small fw-bold text-muted">
								FLAT NUMBER <span class="text-danger">*</span>
							</label>
							<input type="text"
								name="flat_number"
								value="<?= htmlspecialchars($_POST['flat_number'] ?? '') ?>"
								class="form-control bg-light border-0"
								placeholder="e.g. 304">

							<?php if (isset($errors['flat_number'])): ?>
								<small class="text-danger">
									<?= $errors['flat_number'] ?>
								</small>
							<?php endif; ?>
						</div>

						<!-- BLOCK -->
						<div class="col-6">
							<label class="form-label small fw-bold text-muted">
								BLOCK <span class="text-danger">*</span>
							</label>
							<input type="text"
								name="block_number"
								value="<?= htmlspecialchars($_POST['block_number'] ?? '') ?>"
								class="form-control bg-light border-0"
								placeholder="e.g. A">

							<?php if (isset($errors['block_number'])): ?>
								<small class="text-danger">
									<?= $errors['block_number'] ?>
								</small>
							<?php endif; ?>
						</div>

						<!-- FLAT TYPE -->
						<div class="col-6">
							<label class="form-label small fw-bold text-muted">
								FLAT TYPE <span class="text-danger">*</span>
							</label>
							<select name="flat_type"
								class="form-select bg-light border-0">

								<option value="">Select Type</option>

								<?php foreach ($flatTypes as $type): ?>
									<option value="<?= htmlspecialchars($type) ?>"
										<?= (isset($_POST['flat_type']) && $_POST['flat_type'] === $type) ? 'selected' : '' ?>>
										<?= htmlspecialchars($type) ?>
									</option>
								<?php endforeach; ?>
							</select>

							<?php if (isset($errors['flat_type'])): ?>
								<small class="text-danger">
									<?= $errors['flat_type'] ?>
								</small>
							<?php endif; ?>
						</div>

						<div class="col-12">
							<button type="submit" name="add_flats"
								class="btn btn-brand w-100 py-3 mt-3">
								Save Flat
							</button>
						</div>

					</form>

				</div>
			</div>
		</div>
	</div>


	<!-- Edit Flat Popup -->
	<div class="modal fade" id="editFlatModal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">

				<div class="modal-header border-0 p-4">
					<h5 class="modal-title fw-800">Edit Flat</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<div class="modal-body p-4 pt-0">

					<form id="editFlatForm" method="POST" class="row g-3" autocomplete="off">

						<!-- Hidden ID -->
						<input type="hidden" name="id" id="edit_flat_id">

						<!-- Floor -->
						<div class="col-md-6">
							<label class="form-label small fw-bold text-muted">
								FLOOR <span class="text-danger">*</span>
							</label>
							<input type="text"
								name="floor"
								id="edit_floor"
								class="form-control bg-light border-0"
								placeholder="e.g. 3">
						</div>

						<!-- Flat Number -->
						<div class="col-md-6">
							<label class="form-label small fw-bold text-muted">
								FLAT NUMBER <span class="text-danger">*</span>
							</label>
							<input type="text"
								name="flat_number"
								id="edit_flat_number"
								class="form-control bg-light border-0"
								placeholder="e.g. 304">
						</div>

						<!-- Block -->
						<div class="col-md-6">
							<label class="form-label small fw-bold text-muted">
								BLOCK / WING <span class="text-danger">*</span>
							</label>
							<input type="text"
								name="block_number"
								id="edit_block_number"
								class="form-control bg-light border-0"
								placeholder="e.g. A">
						</div>

						<!-- Flat Type -->
						<div class="col-md-6">
							<label class="form-label small fw-bold text-muted">
								FLAT TYPE <span class="text-danger">*</span>
							</label>
							<select name="flat_type"
								id="edit_flat_type"
								class="form-select bg-light border-0">
								<option value="">Select Type</option>
								<?php foreach ($flatTypes as $type): ?>
									<option value="<?= htmlspecialchars($type) ?>">
										<?= htmlspecialchars($type) ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="col-12 mt-4">
							<button type="submit" name="edit_flats"
								class="btn btn-brand w-100 py-3">
								Update Flat
							</button>
						</div>

					</form>

				</div>
			</div>
		</div>
	</div>


	<!-- DELETE Confirmation MODAL -->
	<div class="modal fade" id="deleteFlatModal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content border-0 shadow-lg" style="border-radius:18px;">

				<div class="modal-body text-center p-4">

					<!-- Icon -->
					<div style="
                    width:60px;
                    height:60px;
                    border-radius:50%;
                    background:#ffe5e5;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    margin:0 auto 15px;
                    font-size:26px;">
						🏢
					</div>

					<!-- Title -->
					<h5 class="fw-bold mb-2 text-danger">Delete Flat</h5>

					<!-- Message -->
					<p class="text-muted mb-4">
						Are you sure you want to delete this flat?<br>
						<small class="text-danger">This action cannot be undone.</small>
					</p>

					<!-- Buttons -->
					<div class="d-flex gap-3 justify-content-center">
						<button type="button"
							class="btn btn-light px-4 py-2"
							data-bs-dismiss="modal"
							style="border-radius:10px;">
							Cancel
						</button>

						<button type="button"
							id="confirmDeleteFlatBtn"
							class="btn btn-danger px-4 py-2 fw-bold"
							style="border-radius:10px;">
							Yes, Delete
						</button>
					</div>

				</div>
			</div>
		</div>
	</div>



	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

	<script>
		$(document).ready(function() {

			$('#flats-table').DataTable({
				dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
				processing: true,
				serverSide: true,
				pageLength: 5,
				lengthMenu: [5, 10, 25, 50],
				language: {
					search: "",
					searchPlaceholder: "Search records..."
				},

				ajax: {
					url: 'action.php',
					type: 'POST',
					data: {
						action: 'fetch_flats'
					}
				},
				columns: [{
						data: "flat_number",
						render: function(data) {
							return `<span class="fw-bold">${data}</span>`;
						}
					},
					{
						data: "block_number",
						render: function(data) {
							return `Block ${data}`;
						}
					},
					{
						data: "flat_type"
					},
					{
						data: "floor",
						render: function(data) {
							return `Floor ${data}`;
						}
					},
					{
						data: "created_at",
						render: function(data) {
							if (!data) return "-";
							let d = new Date(data);
							return d.toLocaleDateString('en-GB', {
								day: '2-digit',
								month: 'short',
								year: 'numeric'
							});
						}
					},
					{
						data: null,
						orderable: false,
						className: "text-end",
						render: function(data) {
							return `
								<button class="btn btn-sm btn-light border edit_flat_btn"
										data-id="${data.id}" title="Edit">
									<i class="fa fa-pen"></i>
								</button>
								<button class="btn btn-sm btn-light border text-danger delete_btn mt-1"
										data-id="${data.id}" title="Delete">
									<i class="fa fa-trash"></i>
								</button>
							`;
						}
					}
				]
			});

			// Delete
			let deleteFlatId = null;

			// Open modern delete modal
			$(document).on('click', '.delete_btn', function() {
				deleteFlatId = $(this).data('id');
				$('#deleteFlatModal').modal('show');
			});

			// Confirm delete
			$('#confirmDeleteFlatBtn').on('click', function() {

				if (!deleteFlatId) return;

				$(this).prop('disabled', true).text('Deleting...');

				window.location.href =
					'<?= BASE_URL ?>flats.php?action=delete&id=' + deleteFlatId;
			});

			// OPEN EDIT FLAT MODAL
			$(document).on('click', '.edit_flat_btn', function() {

				let flatId = $(this).data('id');

				$.ajax({
					url: '<?= BASE_URL ?>action.php',
					type: 'POST',
					data: {
						action: 'get_flat',
						id: flatId
					},
					success: function(response) {

						let data = JSON.parse(response);

						$('#edit_flat_id').val(data.id);
						$('#edit_floor').val(data.floor);
						$('#edit_flat_number').val(data.flat_number);
						$('#edit_block_number').val(data.block_number);
						$('#edit_flat_type').val(data.flat_type);

						new bootstrap.Modal(document.getElementById('editFlatModal')).show();
					}
				});

			});


			// SUBMIT EDIT FLAT (AJAX UPDATE)
			$('#editFlatForm').submit(function(e) {
				e.preventDefault();

				$.ajax({
					url: '<?= BASE_URL ?>action.php',
					type: 'POST',
					data: $(this).serialize() + '&action=update_flat',
					success: function(response) {

						let res = JSON.parse(response);

						if (res.status === 'success') {

							bootstrap.Modal.getInstance(
								document.getElementById('editFlatModal')
							).hide();

							window.location.href = '<?= BASE_URL ?>flats.php';
						} else {
							alert(res.message);
						}

					}
				});
			});


		});
	</script>

	<?php if (!empty($errors)): ?>
		<script>
			document.addEventListener("DOMContentLoaded", function() {
				var myModal = new bootstrap.Modal(document.getElementById('addFlatModal'));
				myModal.show();
			});
		</script>
	<?php endif; ?>


</body>

</html>

<?php include('../resources/layout/footer.php'); ?>