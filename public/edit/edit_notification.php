<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';

requireRole(['admin']);

$errors = [];
$title = $message = $start_date = $end_date = '';

/* ===== GET ID ===== */
$id = $_GET['id'] ?? '';
if (!$id) {
    header("Location: " . BASE_URL . "notifications.php");
    exit();
}

/* ===== FETCH EXISTING DATA ===== */
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE id=?");
$stmt->execute([$id]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$notification) {
    header("Location: " . BASE_URL . "notifications.php");
    exit();
}

/* Fill variables */
$title      = $notification['title'];
$message    = $notification['message'];
$start_date = $notification['start_date'];
$end_date   = $notification['end_date'];

/* ===== HANDLE FORM SUBMIT ===== */
if (isset($_POST['update_notification'])) {

    $title      = trim($_POST['title'] ?? '');
    $message    = trim($_POST['message'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date   = $_POST['end_date'] ?? '';

    /* ===== VALIDATION ===== */
    if ($title === '') $errors['title'] = "Please enter title";
    if ($message === '') $errors['message'] = "Please enter notification message";
    if ($start_date === '') $errors['start_date'] = "Please select start date";

    // Convert empty end date to NULL
    if ($end_date === '') $end_date = NULL;

    if ($start_date != '' && $end_date != NULL && $start_date > $end_date) {
        $errors['end_date'] = "End date must be after start date";
    }

    /* ===== UPDATE ===== */
    if (empty($errors)) {

        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET title=?, message=?, start_date=?, end_date=?
            WHERE id=?
        ");



        if ($stmt->execute([$title, $message, $start_date, $end_date, $id])) {
            // $_SESSION['success'] = "Notification updated successfully";
            flash_set('success', 'Notification updated successfully');
            header("Location: " . BASE_URL . "notifications.php");
            exit();
        } else {
            flash_set('err', 'Database error! Notification not updated.');
            header('Location: ' . BASE_URL . 'edit/edit_notification.php');
            exit();
        }
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Edit Notification</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>notifications.php">Notifications</a></li>
        <li class="breadcrumb-item active">Edit Notification</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Edit Notification</h5>
                </div>

                <div class="card-body">
                    <form method="POST" autocomplete="off" novalidate>

                        <div class="row">

                            <!-- Title -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" required value="<?= htmlspecialchars($title) ?>">
                                <?php if (isset($errors['title'])): ?><small class="text-danger"><?= $errors['title'] ?></small><?php endif; ?>
                            </div>

                            <!-- Start Date -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="start_date" required value="<?= $start_date ?>">
                                <?php if (isset($errors['start_date'])): ?><small class="text-danger"><?= $errors['start_date'] ?></small><?php endif; ?>
                            </div>

                            <!-- End Date -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                                <?php if (isset($errors['end_date'])): ?><small class="text-danger"><?= $errors['end_date'] ?></small><?php endif; ?>
                            </div>

                            <!-- Message -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Notification Message <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="message" rows="3" required><?= htmlspecialchars($message) ?></textarea>
                                <?php if (isset($errors['message'])): ?><small class="text-danger"><?= $errors['message'] ?></small><?php endif; ?>
                            </div>

                        </div>

                        <button type="submit" name="update_notification" class="btn btn-primary">Update</button>
                        <a href="<?= BASE_URL ?>notifications.php" class="btn btn-secondary mx-2">Back</a>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>