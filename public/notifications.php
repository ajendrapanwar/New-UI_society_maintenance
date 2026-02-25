<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['admin']);

/* ===== DELETE NOTIFICATION ===== */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id=?");
    $stmt->execute([$_GET['id']]);

    // $_SESSION['success'] = "Notification deleted successfully";
    flash_set('success', 'Notification deleted successfully');
    header("Location: notifications.php");
    exit();
}

/* ===== FETCH NOTIFICATIONS ===== */
$stmt = $pdo->query("SELECT * FROM notifications ORDER BY id DESC");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../resources/layout/header.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broadcast Notifications</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">

    <!-- <style>
        .action-btns {
            display: flex;
            gap: 6px;
            white-space: nowrap;
        }

        .action-btns .btn {
            padding: 3px 8px;
            font-size: 13px;
        }
    </style> -->

</head>

<body>



    <div class="main-wrapper">

        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">
            <h1 class="fw-800 mb-4">Society Broadcast</h1>

            <div class="data-card mb-5">
                <h5 class="fw-bold mb-3">Compose New Notice</h5>
                <form>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold text-muted">NOTICE TITLE</label>
                            <input type="text" class="form-control bg-light border-0 py-2" placeholder="e.g. Water Supply Interruption">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">CATEGORY</label>
                            <select class="form-select bg-light border-0 py-2">
                                <option>General Info</option>
                                <option>Emergency</option>
                                <option>Event</option>
                                <option>Maintenance</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">MESSAGE CONTENT</label>
                            <textarea class="form-control bg-light border-0" rows="3" placeholder="Write the details here..."></textarea>
                        </div>
                        <div class="col-12 text-end">
                            <button type="button" class="btn btn-light px-4 me-2">Save Draft</button>
                            <button type="submit" class="btn btn-brand shadow-sm">
                                <i class="fa-solid fa-paper-plane me-2"></i> Send to All Residents
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <h5 class="fw-bold mb-3">Recent Announcements</h5>

            <div class="notice-card shadow-sm border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="type-tag tag-emergency mb-2 d-inline-block">Emergency</span>
                        <h5 class="fw-bold mb-1">Lift Maintenance: Block B</h5>
                        <p class="text-muted small mb-0">The main lift in Block B will be under repair today from 2:00 PM to 5:00 PM. Please use the service lift.</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted fw-bold d-block">20 FEB 2026</small>
                        <button class="btn btn-sm btn-link text-danger p-0 mt-2"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            </div>

            <div class="notice-card shadow-sm border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="type-tag tag-info mb-2 d-inline-block">General Info</span>
                        <h5 class="fw-bold mb-1">Republic Day Celebration</h5>
                        <p class="text-muted small mb-0">Join us for the flag hoisting ceremony at the Clubhouse lawn tomorrow at 8:30 AM.</p>
                    </div>
                    <div class="text-end">
                        <small class="text-muted fw-bold d-block">19 FEB 2026</small>
                        <button class="btn btn-sm btn-link text-danger p-0 mt-2"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            </div>

        </main>

    </div>


    <!-- DATATABLES -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#notification-table').DataTable();
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>