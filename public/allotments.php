<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';


// Admin access check
requireRole(['admin']);


/* ================= ADD ALLOTMENT FROM MODAL ================= */
$errors = [];

if (isset($_POST['add_allotment'])) {

	$user_id      = trim($_POST['user_id'] ?? '');
	$flat_id      = trim($_POST['flat_id'] ?? '');
	$move_in_date = trim($_POST['move_in_date'] ?? '');
	$created_at   = date('Y-m-d H:i:s');

	// ===== VALIDATION =====
	if ($user_id === '') {
		$errors['user_id'] = 'Please select user';
	}

	if ($flat_id === '') {
		$errors['flat_id'] = 'Please select flat';
	}

	if ($move_in_date === '') {
		$errors['move_in_date'] = 'Move in date required';
	}

	// ===== DUPLICATE CHECK (Flat Already Allotted?) =====
	if (empty($errors)) {
		$check = $pdo->prepare("SELECT id FROM allotments WHERE flat_id = ?");
		$check->execute([$flat_id]);

		if ($check->fetch()) {
			$errors['duplicate'] = 'This flat is already allotted';
		}
	}

	// ===== INSERT =====
	if (empty($errors)) {

		$stmt = $pdo->prepare("
			INSERT INTO allotments 
			(user_id, flat_id, move_in_date, created_at)
			VALUES (?, ?, ?, ?)
		");

		if ($stmt->execute([$user_id, $flat_id, $move_in_date, $created_at])) {

			flash_set('success', 'Allotment added successfully');
			header('Location: ' . BASE_URL . 'allotments.php');
			exit();
		}
	}
}

/* ================= FETCH USERS WITHOUT ALLOTMENT (SAME AS OLD CODE) ================= */
$users = $pdo->query(
	"SELECT u.id, u.name
     FROM users u
     LEFT JOIN allotments a ON u.id = a.user_id
     WHERE a.user_id IS NULL
     ORDER BY u.name"
)->fetchAll(PDO::FETCH_ASSOC);


/* ================= FETCH FLATS NOT ALLOTTED (SAME AS OLD CODE) ================= */
$flats = $pdo->query(
	"SELECT f.id, f.block_number, f.flat_number
     FROM flats f
     LEFT JOIN allotments a ON f.id = a.flat_id
     WHERE a.flat_id IS NULL
     ORDER BY f.block_number, f.flat_number"
)->fetchAll(PDO::FETCH_ASSOC);



/* ================= DELETE ALLOTMENT ================= */
if (
	isset($_GET['action'], $_GET['id']) &&
	$_GET['action'] === 'delete' &&
	ctype_digit($_GET['id'])
) {
	$stmt = $pdo->prepare("DELETE FROM allotments WHERE id = ?");
	$stmt->execute([$_GET['id']]);

	// $_SESSION['success'] = 'Allotment removed successfully';
	flash_set('success', 'Allotment removed successfully');
	header('Location: ' . BASE_URL . 'allotments.php');
	exit();
}

include __DIR__ . '/../resources/layout/header.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Flat Allotments</title>

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
				<h1 class="page-title m-0">Unit Allotments</h1>
				<button class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#addAllotmentModal">
					<i class="fa-solid fa-link me-2"></i> New Allotment
				</button>
			</div>

			<div class="data-card shadow-sm border-0">
				<div class="table-responsive">
					<table class="table table-hover w-100" id="allotment-table">
						<thead>
							<tr>
								<th>Allotted To</th>
								<th>Flat Number</th>
								<th>Block Number</th>
								<th>Type</th>
								<th>Maintenance Rate</th>
								<th>Move In</th>
								<th>Created At</th>
								<th width="80">Action</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>
		</main>

	</div>


	<!-- Add Allotment Modal -->
	<div class="modal fade" id="addAllotmentModal" tabindex="-1">

		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content border-0 shadow-lg" style="border-radius:20px;">

				<div class="modal-header border-0 p-4">
					<h5 class="modal-title fw-800">New Allotment</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
				</div>

				<div class="modal-body p-4 pt-0">

					<?php if (isset($errors['duplicate'])): ?>
						<div class="alert alert-danger">
							<?= htmlspecialchars($errors['duplicate']) ?>
						</div>
					<?php endif; ?>

					<form method="POST" class="row g-3">

						<!-- USER -->
						<div class="col-12">
							<label class="form-label small fw-bold text-muted">
								USER <span class="text-danger">*</span>
							</label>
							<select name="user_id" class="form-select bg-light border-0">
								<option value="">Select User</option>
								<?php foreach ($users as $user): ?>
									<option value="<?= $user['id'] ?>"
										<?= (($_POST['user_id'] ?? '') == $user['id']) ? 'selected' : '' ?>>
										<?= htmlspecialchars($user['name']) ?>
									</option>
								<?php endforeach; ?>
							</select>
							<?php if (isset($errors['user_id'])): ?>
								<small class="text-danger"><?= $errors['user_id'] ?></small>
							<?php endif; ?>
						</div>

						<!-- FLAT -->
						<div class="col-12">
							<label class="form-label small fw-bold text-muted">
								FLAT <span class="text-danger">*</span>
							</label>
							<select name="flat_id" class="form-select bg-light border-0">
								<option value="">Select Flat</option>
								<?php foreach ($flats as $flat): ?>
									<option value="<?= $flat['id'] ?>"
										<?= (($_POST['flat_id'] ?? '') == $flat['id']) ? 'selected' : '' ?>>
										Block <?= htmlspecialchars($flat['block_number']) ?>
										- <?= htmlspecialchars($flat['flat_number']) ?>
									</option>
								<?php endforeach; ?>
							</select>
							<?php if (isset($errors['flat_id'])): ?>
								<small class="text-danger"><?= $errors['flat_id'] ?></small>
							<?php endif; ?>
						</div>

						<!-- MOVE IN -->
						<div class="col-12">
							<label class="form-label small fw-bold text-muted">
								MOVE IN DATE <span class="text-danger">*</span>
							</label>
							<input type="date"
								name="move_in_date"
								value="<?= htmlspecialchars($_POST['move_in_date'] ?? '') ?>"
								class="form-control bg-light border-0">
							<?php if (isset($errors['move_in_date'])): ?>
								<small class="text-danger"><?= $errors['move_in_date'] ?></small>
							<?php endif; ?>
						</div>

						<div class="col-12">
							<button type="submit"
								name="add_allotment"
								class="btn btn-brand w-100 py-3 mt-3">
								Save Allotment
							</button>
						</div>

					</form>

				</div>
			</div>
		</div>
	</div>


	<!-- Delete Confirmation Modal -->
	<div class="modal fade" id="deleteAllotmentModal" tabindex="-1">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content border-0 shadow-lg" style="border-radius:18px;">

				<div class="modal-body text-center p-4">

					<!-- Icon -->
					<div class="mb-3">
						<div style="
                        width:70px;
                        height:70px;
                        margin:auto;
                        border-radius:50%;
                        background:#ffeaea;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        font-size:28px;
                        color:#dc3545;
                        box-shadow:0 8px 20px rgba(220,53,69,0.15);
                    ">
							<i class="fa fa-trash"></i>
						</div>
					</div>

					<!-- Title -->
					<h5 class="fw-bold mb-2">Delete Allotment?</h5>

					<!-- Subtitle -->
					<p class="text-muted mb-4">
						This action cannot be undone. Are you sure you want to delete this allotment?
					</p>

					<!-- Hidden ID -->
					<input type="hidden" id="delete_allotment_id">

					<!-- Buttons -->
					<div class="d-flex gap-3 justify-content-center">
						<button type="button"
							class="btn btn-light px-4 py-2"
							data-bs-dismiss="modal"
							style="border-radius:10px; min-width:110px;">
							Cancel
						</button>

						<button type="button"
							id="confirmDeleteBtn"
							class="btn btn-danger px-4 py-2"
							style="
                            border-radius:10px;
                            min-width:110px;
                            box-shadow:0 6px 18px rgba(220,53,69,0.25);
                        ">
							Yes, Delete
						</button>
					</div>

				</div>
			</div>
		</div>
	</div>


	<!-- ================= DATATABLES ================= -->
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
	<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

	<script>
		$(function() {

			const table = $('#allotment-table').DataTable({
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
						action: 'fetch_allotments'
					}
				},

				order: [
					[0, 'desc']
				],

				columns: [
					{
						data: 'name'
					},
					{
						data: 'flat_number'
					},
					{
						data: 'block_number'
					},
					{
						data: 'flat_type'
					},
					{
						data: 'rate',
						render: function(data) {
							return data ? data : 'N/A';
						}
					},
					{
						data: 'move_in_date'
					},
					{
						data: 'created_at'
					},
					{
						data: null,
						orderable: false,
						searchable: false,
						render: function(data, type, row) {
							return `
							<button class="btn btn-sm btn-light border text-danger delete_btn mt-1"
										data-id="${row.id}" title="Delete">
									<i class="fa fa-trash"></i>
							</button>
						`;
						}
					}
				]
			});

			/* ================= MODERN DELETE POPUP ================= */
			$(document).on('click', '.delete_btn', function() {
				const id = $(this).data('id');

				// Set ID in hidden input
				$('#delete_allotment_id').val(id);

				// Show modern modal popup (center screen)
				const deleteModal = new bootstrap.Modal(
					document.getElementById('deleteAllotmentModal')
				);
				deleteModal.show();
			});

			// Confirm delete button click
			$('#confirmDeleteBtn').on('click', function() {
				const id = $('#delete_allotment_id').val();

				// Show loading state
				$(this).html('<span class="spinner-border spinner-border-sm me-2"></span>Deleting...');
				$(this).prop('disabled', true);

				// Redirect to delete
				window.location.href =
					'<?= BASE_URL ?>allotments.php?action=delete&id=' + id;
			});

		});
	</script>


	<?php if (!empty($errors)): ?>
		<script>
			document.addEventListener("DOMContentLoaded", function() {
				var myModal = new bootstrap.Modal(document.getElementById('addAllotmentModal'));
				myModal.show();
			});
		</script>
	<?php endif; ?>



</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>