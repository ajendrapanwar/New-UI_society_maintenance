<?php
require_once __DIR__ . '/../core/config.php';

/* ================= ACCESS CONTROL ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
	header('Location: ' . BASE_URL . 'logout.php');
	exit();
}

/* ================= DELETE ALLOTMENT ================= */
if (
	isset($_GET['action'], $_GET['id']) &&
	$_GET['action'] === 'delete' &&
	ctype_digit($_GET['id'])
) {
	$stmt = $pdo->prepare("DELETE FROM allotments WHERE id = ?");
	$stmt->execute([$_GET['id']]);

	$_SESSION['success'] = 'Allotment removed successfully';
	header('Location: ' . BASE_URL . 'allotments.php');
	exit();
}

include __DIR__ . '/../resources/layout/header.php';
?>

<div class="container-fluid px-4">
	<h1 class="mt-4">Allotments Management</h1>

	<ol class="breadcrumb mb-4">
		<li class="breadcrumb-item">
			<a href="<?= BASE_URL ?>dashboard.php">Dashboard</a>
		</li>
		<li class="breadcrumb-item active">Allotments</li>
	</ol>

	<?php if (!empty($_SESSION['success'])): ?>
		<div class="alert alert-success">
			<?= htmlspecialchars($_SESSION['success']) ?>
		</div>
		<?php unset($_SESSION['success']); ?>
	<?php endif; ?>

	<div class="card">
		<div class="card-header d-flex justify-content-between">
			<h5 class="mb-0">Allotments List</h5>
			<a href="<?= BASE_URL ?>add/add_allotments.php"
			   class="btn btn-success btn-sm">
				Add Allotment
			</a>
		</div>

		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-bordered table-striped"
					   id="allotment-table">
					<thead>
						<tr>
							<th>ID</th>
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
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>

<!-- ================= DATATABLES ================= -->
<link rel="stylesheet"
	  href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">

<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function () {

	const table = $('#allotment-table').DataTable({
		processing: true,
		serverSide: true,

		pageLength: 5,
		lengthMenu: [5, 10, 25, 50],

		ajax: {
			url: 'action.php',
			type: 'POST',
			data: {
				action: 'fetch_allotments'
			}
		},

		order: [[0, 'desc']],

		columns: [
			{ data: 'id' },
			{ data: 'name' },
			{ data: 'flat_number' },
			{ data: 'block_number' },
			{ data: 'flat_type' },
			{
				data: 'rate',
				render: function (data) {
					return data ? data : 'N/A';
				}
			},
			{ data: 'move_in_date' },
			{ data: 'created_at' },
			{
				data: null,
				orderable: false,
				searchable: false,
				render: function (data, type, row) {
					return `
						<button class="btn btn-sm btn-danger delete-btn"
								data-id="${row.id}">
							Delete
						</button>
					`;
				}
			}
		]
	});

	/* ================= DELETE BUTTON ================= */
	$(document).on('click', '.delete-btn', function () {
		const id = $(this).data('id');
		if (confirm('Are you sure you want to delete this allotment?')) {
			window.location.href =
				'<?= BASE_URL ?>allotments.php?action=delete&id=' + id;
		}
	});

});
</script>
