<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['admin', 'cashier']);

// DELETE (Admin Only)
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete' && is_numeric($_GET['id'])) {
    requireRole(['admin']);
    $stmt = $pdo->prepare("DELETE FROM miscellaneous_works WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    // $_SESSION['success'] = "Record deleted successfully";
    flash_set('success', 'Record deleted successfully');
    header("Location: miscellaneous_work.php");
    exit;
}

include('../resources/layout/header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Miscellaneous Work</title>

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
                <h1 class="fw-800 m-0">Miscellaneous Work</h1>
                <button class="btn btn-brand shadow-sm" data-bs-toggle="modal" data-bs-target="#addMiscModal">
                    <i class="fa-solid fa-plus me-2"></i> Add Expense
                </button>
            </div>

            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover datatable w-100">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Work Description</th>
                                <th>Vendor/Person</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>15 Feb 2026</td>
                                <td class="fw-bold">Lift Sensor Replacement</td>
                                <td>OTIS Services</td>
                                <td><span class="badge bg-light text-dark border">Repairs</span></td>
                                <td class="fw-bold">₹8,500</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border"><i class="fa fa-receipt"></i></button>
                                    <button class="btn btn-sm btn-light border text-danger"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>10 Feb 2026</td>
                                <td class="fw-bold">Main Gate Painting</td>
                                <td>Local Contractor</td>
                                <td><span class="badge bg-light text-dark border">Maintenance</span></td>
                                <td class="fw-bold">₹5,750</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border"><i class="fa fa-receipt"></i></button>
                                    <button class="btn btn-sm btn-light border text-danger"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

    </div>

    <!-- DataTables CSS & JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>



    <script>
        $(document).ready(function() {

            let columns = [{
                    data: 'id'
                },
                {
                    data: 'month_year'
                },
                {
                    data: 'work_title'
                },
                {
                    data: 'description'
                },
                {
                    data: 'worker_name'
                },
                {
                    data: 'contact_number'
                },
                {
                    data: 'amount'
                },
                {
                    data: 'status'
                },
                {
                    data: 'paid_on'
                }
            ];

            <?php if ($_SESSION['user_role'] == 'admin') { ?>
                columns.push({
                    data: null,
                    orderable: false,
                    render: function(d) {
                        return `<button class="btn btn-danger btn-sm delete_btn" data-id="${d.id}">Delete</button>`;
                    }
                });
            <?php } ?>

            let table = $('#misc-table').DataTable({
                processing: true,
                serverSide: true,
                searching: false,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                ajax: {
                    url: '<?= BASE_URL ?>action.php',
                    type: 'POST',
                    data: function(d) {
                        d.action = 'fetch_misc_works';
                        d.month = $('#filter-month').val();
                        d.year = $('#filter-year').val();
                    }
                },
                columns: columns
            });

            $('#filter-month, #filter-year').change(function() {
                table.ajax.reload();
            });

            $('#reset-filters').click(function() {
                $('#filter-month,#filter-year').val('');
                table.ajax.reload();
            });

            $(document).on('click', '.delete_btn', function() {
                if (confirm('Delete this record?')) {
                    window.location = 'miscellaneous_work.php?action=delete&id=' + $(this).data('id');
                }
            });

            $('#export-excel').click(function() {
                let month = $('#filter-month').val();
                let year = $('#filter-year').val();
                window.location = '<?= BASE_URL ?>action.php?action=export_misc_work&month=' + month + '&year=' + year;
            });

        });
    </script>

</body>

</html>

<?php include('../resources/layout/footer.php'); ?>