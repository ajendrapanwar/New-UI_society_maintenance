<?php
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

requireRole(['admin', 'user']);


/* ===== ADD NOTIFICATION ===== */
$errors = [];
$title = $category = $message = $start_date = $end_date = '';

if (isset($_POST['send_notification'])) {

    $title    = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $message  = trim($_POST['message'] ?? '');
    $start_date = !empty($_POST['start_date'])
        ? date('Y-m-d H:i:s', strtotime($_POST['start_date']))
        : null;
    $end_date = !empty($_POST['end_date'])
        ? date('Y-m-d H:i:s', strtotime($_POST['end_date']))
        : null;

    // Validation
    if ($title === '') $errors['title'] = "Notice title is required";
    if ($category === '') $errors['category'] = "Category is required";
    if (empty($_POST['start_date'])) $errors['start_date'] = "Start date is required";
    if ($message === '') $errors['message'] = "Message content is required";

    if (!empty($start_date) && !empty($end_date) && $end_date < $start_date) {
        $errors['end_date'] = "End date must be after start date";
    }

    // Insert if no errors
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (title, category, message, start_date, end_date)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $category, $message, $start_date, $end_date]);

        flash_set('success', 'Notification sent successfully');
        header("Location: notifications.php");
        exit();
    }
}


/* ===== DELETE NOTIFICATION ===== */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] == 'delete') {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id=?");
    $stmt->execute([$_GET['id']]);

    // $_SESSION['success'] = "Notification deleted successfully";
    flash_set('success', 'Notification deleted successfully');
    header("Location: notifications.php");
    exit();
}



/* ===== FETCH NOTIFICATIONS (USER: HIDE EXPIRED, ADMIN: SHOW ALL) ===== */

if ($_SESSION['user_role'] === 'admin') {

    // Admin sees ALL notifications (including expired)
    $stmt = $pdo->query("
        SELECT * FROM notifications 
        ORDER BY id DESC
    ");

} else {

    // User sees ONLY active notifications
    // Show if:
    // 1. Start date reached
    // 2. End date is NULL (Present)
    // 3. End date is today or future
    $stmt = $pdo->prepare("
        SELECT * FROM notifications
        WHERE start_date <= NOW()
        AND (end_date IS NULL OR end_date >= NOW())
        ORDER BY id DESC
    ");
    $stmt->execute();
}

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
</head>

<body>

    <div class="main-wrapper">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">
            <h1 class="fw-800 mb-4">Society Broadcast</h1>
            <!---------------------------- Admin Only ---------------------------->
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <!-- Add Notification Form -->
                <div class="data-card mb-5">
                    <h5 class="fw-bold mb-3">Compose New Notice</h5>
                    <form method="POST" autocomplete="off">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-muted">NOTICE TITLE <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    name="title"
                                    value="<?= htmlspecialchars($title) ?>"
                                    class="form-control bg-light border-0 py-2 <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                    placeholder="e.g. Water Supply Interruption">

                                <?php if (isset($errors['title'])): ?>
                                    <small class="text-danger"><?= $errors['title'] ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">CATEGORY <span class="text-danger">*</span></label>
                                <select name="category" class="form-select bg-light border-0 py-2">
                                    <option value="">Select Category</option>
                                    <option value="General Info" <?= $category == 'General Info' ? 'selected' : '' ?>>General Info</option>
                                    <option value="Emergency" <?= $category == 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                                    <option value="Event" <?= $category == 'Event' ? 'selected' : '' ?>>Event</option>
                                    <option value="Maintenance" <?= $category == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                                </select>

                                <?php if (isset($errors['category'])): ?>
                                    <small class="text-danger"><?= $errors['category'] ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- Start Date -->
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">
                                    Start Date <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="datetime-local"
                                    name="start_date"
                                    value="<?= htmlspecialchars($start_date) ?>"
                                    class="form-control bg-light border-0 py-2 <?= isset($errors['start_date']) ? 'is-invalid' : '' ?>"
                                    required>

                                <?php if (isset($errors['start_date'])): ?>
                                    <small class="text-danger"><?= $errors['start_date'] ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- End Date -->
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted">End Date</label>
                                <input type="datetime-local" class="form-control bg-light border-0 py-2" name="end_date" value="<?= $end_date ?>">
                                <?php if (isset($errors['end_date'])): ?><small class="text-danger"><?= $errors['end_date'] ?></small><?php endif; ?>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">MESSAGE CONTENT <span class="text-danger">*</span></label>
                                <textarea
                                    name="message"
                                    class="form-control bg-light border-0 <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                                    rows="3"
                                    placeholder="Write the details here..."><?= htmlspecialchars($message) ?></textarea>

                                <?php if (isset($errors['message'])): ?>
                                    <small class="text-danger"><?= $errors['message'] ?></small>
                                <?php endif; ?>
                            </div>

                            <div class="col-12 text-end">
                                <button type="submit" name="send_notification" class="btn btn-brand shadow-sm">
                                    <i class="fa-solid fa-paper-plane me-2"></i> Send to All Residents
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Notification Display -->
            <h5 class="fw-bold mb-3">Recent Announcements</h5>
            <?php foreach ($notifications as $n): ?>
                <?php
                /* ===== INLINE STYLING BASED ON CATEGORY ===== */
                $borderColor = '#6c757d';
                $tagBg = '#f1f5f9';
                $tagColor = '#334155';
                $tagBorder = '#e2e8f0';

                switch ($n['category']) {
                    case 'General Info':
                        $borderColor = '#0d6efd';
                        $tagBg = '#e0f2fe';
                        $tagColor = '#0369a1';
                        $tagBorder = '#bae6fd';
                        break;

                    case 'Maintenance':
                        $borderColor = '#f59e0b';
                        $tagBg = '#fff7ed';
                        $tagColor = '#b45309';
                        $tagBorder = '#fed7aa';
                        break;

                    case 'Emergency':
                        $borderColor = '#dc3545';
                        $tagBg = '#fee2e2';
                        $tagColor = '#b91c1c';
                        $tagBorder = '#fecaca';
                        break;

                    case 'Event':
                        $borderColor = '#198754';
                        $tagBg = '#dcfce7';
                        $tagColor = '#166534';
                        $tagBorder = '#bbf7d0';
                        break;
                }

                // Date formatting
                $start_display = $n['start_date']
                    ? date('d M Y, h:i A', strtotime($n['start_date']))
                    : '-';

                $end_display = $n['end_date']
                    ? date('d M Y, h:i A', strtotime($n['end_date']))
                    : 'Present';
                ?>

                <div class="notice-card shadow-sm"
                    style="
                            border-left: 4px solid <?= $borderColor ?>;
                            border-radius: 12px;
                            padding: 16px;
                            background: #ffffff;
                            margin-bottom: 15px;
                        ">

                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <!-- CATEGORY TAG -->
                            <span style="
                                        background: <?= $tagBg ?>;
                                        color: <?= $tagColor ?>;
                                        border: 1px solid <?= $tagBorder ?>;
                                        padding: 4px 10px;
                                        font-size: 12px;
                                        font-weight: 600;
                                        border-radius: 999px;
                                        display: inline-block;
                                        margin-bottom: 6px;
                                    ">
                                <?= htmlspecialchars($n['category']) ?>
                            </span>

                            <!-- TITLE -->
                            <h5 class="fw-bold mb-1">
                                <?= htmlspecialchars($n['title']) ?>
                            </h5>

                            <!-- MESSAGE -->
                            <p class="text-muted small mb-0">
                                <?= nl2br(htmlspecialchars($n['message'])) ?>
                            </p>

                            <!-- DATE -->
                            <small class="text-muted small mt-1 d-block">
                                <?= $start_display ?> — <?= $end_display ?>
                            </small>
                        </div>

                        <!-- ADMIN DELETE BUTTON -->
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <div class="text-end">
                                <button class="btn btn-sm btn-link text-danger p-0 mt-2 delete-notification-btn"
                                    data-id="<?= $n['id'] ?>">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>

    </div>


    <!-- DELETE Confirmation MODAL -->
    <div class="modal fade" id="deleteNotificationModal" tabindex="-1">
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
                        📝
                    </div>

                    <!-- Title -->
                    <h5 class="fw-bold mb-2 text-danger">Delete Notification</h5>

                    <!-- Message -->
                    <p class="text-muted mb-4">
                        Are you sure you want to delete this notification?<br>
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

                        <a href="#" id="confirmDeleteNotificationBtn"
                            class="btn btn-danger px-4 py-2 fw-bold"
                            style="border-radius:10px;">
                            Yes, Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
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

        $(document).ready(function() {
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteNotificationModal'));

            $('.delete-notification-btn').on('click', function() {
                var notificationId = $(this).data('id');
                $('#confirmDeleteNotificationBtn').attr('href', 'notifications.php?action=delete&id=' + notificationId);
                deleteModal.show();
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>

<?php include __DIR__ . '/../resources/layout/footer.php'; ?>