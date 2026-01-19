<?php

require_once __DIR__ . '/../core/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
	header('Location: ' . BASE_URL . 'logout.php');
	exit();
}

if (
	isset($_GET['action'], $_GET['id']) &&
	$_GET['action'] === 'delete' &&
	is_numeric($_GET['id'])
) {
	$stmt = $pdo->prepare("DELETE FROM flats WHERE id = ?");
	$stmt->execute([(int)$_GET['id']]);

	$_SESSION['success'] = 'Flat data has been removed successfully';
	header('Location: flats.php');
	exit();
}


include('../resources/layout/header.php');

?>

<div class="container-fluid px-4">
	<h1 class="mt-4">Flats Management</h1>
	<ol class="breadcrumb mb-4">
		<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
		<li class="breadcrumb-item active">Flats Management</li>
	</ol>
	<?php

	if (isset($_SESSION['success'])) {
		echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';

		unset($_SESSION['success']);
	}

	?>
	<div class="card">
		<div class="card-header">
			<div class="row">
				<div class="col col-6">
					<h5 class="card-title">Flats Management</h5>
				</div>
				<div class="col col-6">
					<div class="float-end"><a href="add/add_flats.php" class="btn btn-success btn-sm">Add</a></div>
				</div>
			</div>
		</div>
		<div class="card-body">
			<div class="table-responsive">
				<table class="table table-bordered table-striped" id="flats-table">
					<thead>
						<tr>
							<th>ID</th>
							<th>Flat Number</th>
							<th>Floor Number</th>
							<th>Block Number</th>
							<th>Type</th>
							<th>Created At</th>
							<th>Action</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>

			</div>
		</div>
	</div>
</div>

<script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.2.0/css/bootstrap.min.css">


<?php

include('../resources/layout/footer.php');

?>

<script>
	$(document).ready(function() {
		$('#flats-table').DataTable({
			processing: true,
			serverSide: true,
			pageLength: 5,          // Show 5 rows per page
			lengthMenu: [5, 10, 25, 50], // Options for rows per page
			ajax: {
				url: 'action.php',
				type: 'POST',
				data: {
					action: 'fetch_flats'
				}
			},
			columns: [
				{ data: "id" },
				{ data: "flat_number" },
				{ data: "floor" },
				{ data: "block_number" },
				{ data: "flat_type" },
				{ data: "created_at" },
				{
					data: null,
					orderable: false,
					render: function(data, type, row) {
						return `
							<a href="edit/edit_flats.php?id=${row.id}" class="btn btn-sm btn-primary">Edit</a>
							<button class="btn btn-sm btn-danger delete_btn" data-id="${row.id}">
								Delete
							</button>
						`;
					}
				}
			]
		});

		$(document).on('click', '.delete_btn', function() {
			const id = $(this).data('id');
			if (confirm("Are you sure you want to delete this flat?")) {
				window.location.href = `flats.php?action=delete&id=${id}`;
			}
		});
	});
</script>
