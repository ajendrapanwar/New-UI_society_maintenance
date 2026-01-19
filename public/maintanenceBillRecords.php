<?php
require_once __DIR__ . '/../core/config.php';

// ACCESS CONTROL
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
	header('Location: ' . BASE_URL . 'logout.php');
	exit();
}

// DELETE ALLOTMENT
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
	<h1 class="mt-4">Maintenance Bill Records Management</h1>

	<ol class="breadcrumb mb-4">
		<li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
		<li class="breadcrumb-item active">Bill Records</li>
	</ol>

	<?php if (!empty($_SESSION['success'])): ?>
		<div class="alert alert-success">
			<?= htmlspecialchars($_SESSION['success']) ?>
		</div>
		<?php unset($_SESSION['success']); ?>
	<?php endif; ?>

	<div class="card">
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-bordered table-striped" id="allotment-table">
					<thead>
						<tr>
							<th>ID</th>
                            <th>Flat Number</th>
							<th>Block Number</th>
							<th>Allotted To</th>
							<th>Type</th>
							<th width="120">Action</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<script>
$(function () {
	$('#allotment-table').DataTable({
		processing: true,
		serverSide: true,

		pageLength: 5,
		lengthMenu: [5, 10, 25, 50],

		ajax: {
			url: 'action.php',
			type: 'POST',
			data: {
				action: 'maintenanceBillRecords'
			}
		},

		order: [[0, 'desc']],
		columns: [
			{ data: 'id' },
			{ data: 'flat_number' },
			{ data: 'block_number' },
			{ data: 'name' },
			{ data: 'flat_type' },
			{
				data: null,
				orderable: false,
				searchable: false,
				render: function (data, type, row) {
					return `
						<a href="view/view_maintanenceBillRecords.php?id=${row.id}"
						   class="btn btn-sm btn-warning">View</a>
					`;
				}
			}
		]
	});
});
</script>

