<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Admin access check
requireRole(['admin', 'cashier']);


// DELETE ALLOTMENT
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
	<title>Maintenance Records</title>

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
			<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
				<h1 class="fw-800 m-0">Maintenance Ledger</h1>
			</div>

			<div class="data-card shadow-sm border-0">
				<div class="table-responsive">
					<table id="allotment-table" class="table table-hover align-middle w-100">
						<thead>
							<tr>
								<th>ID</th>
								<th>Flat Number</th>
								<th>Block Number</th>
								<th>Allotted To</th>
								<th>Type</th>
								<th class="text-end">Action</th>
							</tr>
						</thead>
						<tbody>
							<!-- NO DUMMY DATA (DataTables will load dynamically) -->
						</tbody>
					</table>
				</div>
			</div>
		</main>

	</div>


	<!-- DataTables -->
	<script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>



	<script>
		$(document).ready(function() {

			$('#allotment-table').DataTable({
				dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
				processing: true,
				serverSide: true,
				responsive: true,
				pageLength: 5,
				lengthMenu: [5, 10, 25, 50],

				language: {
					search: "",
					searchPlaceholder: "Search records..."
				},


				autoWidth: false,

				ajax: {
					url: 'action.php',
					type: 'POST',
					data: {
						action: 'maintenanceBillRecords'
					}
				},

				order: [
					[0, 'desc']
				],

				columns: [{
						data: 'id'
					},
					{
						data: 'flat_number'
					},
					{
						data: 'block_number'
					},
					{
						data: 'name'
					},
					{
						data: 'flat_type'
					},
					{
						data: null,
						orderable: false,
						searchable: false,
						className: "text-end",
						render: function(data, type, row) {
							return `
                        <a href="view/view_maintanenceBillRecords.php?id=${row.id}"
                           class="btn btn-sm btn-brand"
						    style="font-size: 10px;">
                           <i class="fa fa-eye me-1"></i> View
                        </a>
                    `;
						}
					}
				]
			});

		});
	</script>


</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>