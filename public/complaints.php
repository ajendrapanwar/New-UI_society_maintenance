<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';
requireRole(['admin']);


/* ================= DATATABLE FETCH COMPLAINTS ================= */
if (isset($_POST['action']) && $_POST['action'] === 'fetch_complaints') {

    $query = "
        SELECT c.*, u.name, u.mobile
        FROM complaints c
        JOIN users u ON c.user_id = u.id
        ORDER BY c.id DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        "draw" => intval($_POST['draw'] ?? 1),
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}



/* ================= FETCH SINGLE COMPLAINT (MODAL) ================= */
if (isset($_POST['action']) && $_POST['action'] === 'get_complaint') {

    $id = (int)$_POST['id'];

    $stmt = $pdo->prepare("
        SELECT c.*, u.name, u.mobile
        FROM complaints c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);

    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($complaint);
    exit;
}



/* ================= UPDATE COMPLAINT (MODAL) ================= */
if (isset($_POST['action']) && $_POST['action'] === 'update_complaint') {

    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    $resolve_note = trim($_POST['resolve_note']);

    if ($status === 'completed' && empty($resolve_note)) {
        echo json_encode(['status' => 'error', 'msg' => 'Resolve note required']);
        exit;
    }

    $resolved_at = ($status === 'completed') ? date("Y-m-d H:i:s") : NULL;

    $stmt = $pdo->prepare("
        UPDATE complaints 
        SET status = ?, resolve_note = ?, resolved_at = ?
        WHERE id = ?
    ");

    if ($stmt->execute([$status, $resolve_note, $resolved_at, $id])) {
        flash_set('success', 'Complaint updated successfully');
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'msg' => 'Update failed']);
    }

    exit;
}




/* ================= DELETE COMPLAINT ================= */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {

    $id = (int)$_GET['id'];

    $check = $pdo->prepare("SELECT status FROM complaints WHERE id = ?");
    $check->execute([$id]);
    $status = $check->fetchColumn();

    if ($status === 'completed') {
        $stmt = $pdo->prepare("DELETE FROM complaints WHERE id = ?");
        $stmt->execute([$id]);

        flash_set('success', 'Complaint deleted successfully');
    } else {
        flash_set('err', 'Only completed complaints can be deleted');
    }

    header("Location: " . BASE_URL . "complaints.php");
    exit;
}


include('../resources/layout/header.php');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Complaints</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>

    <div class="main-wrapper">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">
            <h1 class="fw-800 mb-4">Resident Complaints</h1>
            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table class="table table-hover w-100" id="complaints-table">
                        <thead>
                            <tr>
                                <th>User Name</th>
                                <th>Mobile</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Resolve Note</th>
                                <th>Resolved At</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </main>
    </div>


    <!-- Edit Complaint Modal -->
    <div class="modal fade" id="editComplaintModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">
                        <i class="fa fa-file-alt me-2"></i>Update Complaint
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">

                    <form id="editComplaintForm" class="row g-3" autocomplete="off">

                        <!-- Hidden ID -->
                        <input type="hidden" id="complaint_id" name="id">

                        <!-- User Name -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">USER NAME</label>
                            <input type="text" id="c_name"
                                class="form-control bg-light border-0" readonly>
                        </div>

                        <!-- Mobile -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">MOBILE</label>
                            <input type="text" id="c_mobile"
                                class="form-control bg-light border-0" readonly>
                        </div>

                        <!-- Subject -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">SUBJECT</label>
                            <input type="text" id="c_subject"
                                class="form-control bg-light border-0" readonly>
                        </div>

                        <!-- Message -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">MESSAGE</label>
                            <textarea id="c_message"
                                class="form-control bg-light border-0" rows="2" readonly></textarea>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">
                                STATUS <span class="text-danger">*</span>
                            </label>
                            <select name="status" id="c_status"
                                class="form-select bg-light border-0">
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                        <!-- Resolve Note -->
                        <div class="col-12" id="resolveBox" style="display:none;">
                            <label class="form-label small fw-bold text-muted">
                                RESOLVE NOTE <span class="text-danger">*</span>
                            </label>
                            <textarea name="resolve_note" id="c_resolve_note"
                                class="form-control bg-light border-0"
                                rows="3"
                                placeholder="Write resolution note..."></textarea>
                            <div class="invalid-feedback" id="resolveError"></div>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit"
                                class="btn btn-brand w-100 py-3">
                                Update Complaint
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>


    <!-- DELETE Complaint MODAL -->
    <div class="modal fade" id="deleteComplaintModal" tabindex="-1">
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
                        ⚠️
                    </div>

                    <!-- Title -->
                    <h5 class="fw-bold mb-2 text-danger">Delete Complaint</h5>

                    <!-- Message -->
                    <p class="text-muted mb-4">
                        Are you sure you want to delete this complaint?<br>
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
                            id="confirmDeleteComplaintBtn"
                            class="btn btn-danger px-4 py-2 fw-bold"
                            style="border-radius:10px;">
                            Yes, Delete
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>



    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {

            $('#complaints-table').DataTable({
                dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
                processing: true,
                serverSide: true,
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                language: {
                    search: "",
                    searchPlaceholder: "Search complaints..."
                },

                ajax: {
                    url: 'complaints.php',
                    type: 'POST',
                    data: {
                        action: 'fetch_complaints'
                    }
                },

                columns: [{
                        data: "name"
                    },
                    {
                        data: "mobile"
                    },
                    {
                        data: "subject"
                    },
                    {
                        data: "message"
                    },

                    {
                        data: "status",
                        render: function(data) {
                            if (data === 'pending')
                                return "<span class='badge bg-warning'>Pending</span>";
                            if (data === 'processing')
                                return "<span class='badge bg-info'>Processing</span>";
                            if (data === 'completed')
                                return "<span class='badge bg-success'>Completed</span>";
                        }
                    },

                    {
                        data: "created_at"
                    },

                    {
                        data: "resolve_note",
                        render: function(data) {
                            return data ? data : "-";
                        }
                    },

                    {
                        data: "resolved_at",
                        render: function(data) {
                            return data ? data : "-";
                        }
                    },

                    {
                        data: null,
                        orderable: false,
                        className: "text-end",
                        render: function(data) {

                            let buttons = '';

                            // If NOT completed → show View
                            if (data.status !== 'completed') {
                                buttons += `
                                    <button class="btn btn-sm btn-light border view_btn"
                                            data-id="${data.id}" title="View">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                `;
                            }

                            // If completed → show Delete
                            if (data.status === 'completed') {
                                buttons += `
                                    <button class="btn btn-sm btn-light border text-danger delete_btn"
                                            data-id="${data.id}" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                `;
                            }

                            return `
                                <div class="d-flex justify-content-end gap-2">
                                    ${buttons}
                                </div>
                            `;
                        }
                    }
                ]
            });




            // OPEN MODAL & FETCH DATA
            $(document).on('click', '.view_btn', function() {
                let id = $(this).data('id');

                $.ajax({
                    url: 'complaints.php',
                    type: 'POST',
                    data: {
                        action: 'get_complaint',
                        id: id
                    },
                    success: function(data) {

                        // If response is string, convert safely
                        if (typeof data === "string") {
                            data = JSON.parse(data);
                        }

                        if (!data || !data.id) {
                            alert("Failed to load complaint data!");
                            return;
                        }

                        $('#complaint_id').val(data.id);
                        $('#c_name').val(data.name);
                        $('#c_mobile').val(data.mobile);
                        $('#c_subject').val(data.subject);
                        $('#c_message').val(data.message);
                        $('#c_status').val(data.status);
                        $('#c_resolve_note').val(data.resolve_note ? data.resolve_note : '');

                        toggleResolveBox();

                        const modal = new bootstrap.Modal(document.getElementById('editComplaintModal'));
                        modal.show();
                    }
                });
            });

            // SHOW/HIDE RESOLVE NOTE
            function toggleResolveBox() {
                if ($('#c_status').val() === 'completed') {
                    $('#resolveBox').show();
                } else {
                    $('#resolveBox').hide();
                    $('#c_resolve_note').removeClass('is-invalid');
                    $('#resolveError').text('');
                }
            }

            $('#c_status').on('change', toggleResolveBox);

            // UPDATE COMPLAINT AJAX
            $('#editComplaintForm').on('submit', function(e) {
                e.preventDefault();

                let status = $('#c_status').val();
                let note = $('#c_resolve_note').val().trim();

                if (status === 'completed' && note === '') {
                    $('#c_resolve_note').addClass('is-invalid');
                    $('#resolveError').text('Resolve note is required!');
                    return;
                }

                $.ajax({
                    url: 'complaints.php',
                    type: 'POST',
                    data: {
                        action: 'update_complaint',
                        id: $('#complaint_id').val(),
                        status: status,
                        resolve_note: note
                    },
                    success: function(res) {
                        let response = JSON.parse(res);

                        if (response.status === 'success') {
                            $('#editComplaintModal').modal('hide');

                            // Reload full page to show flash message (like delete)
                            window.location.reload();
                        }
                    }
                });
            });


            let deleteComplaintId = null;

            // OPEN DELETE MODAL
            $(document).on('click', '.delete_btn', function() {
                deleteComplaintId = $(this).data('id');

                let modal = new bootstrap.Modal(document.getElementById('deleteComplaintModal'));
                modal.show();
            });

            // CONFIRM DELETE
            $('#confirmDeleteComplaintBtn').on('click', function() {

                if (deleteComplaintId) {
                    window.location.href = 'complaints.php?action=delete&id=' + deleteComplaintId;
                }
            });

        });
    </script>


</body>

</html>

<?php include('../resources/layout/footer.php'); ?>