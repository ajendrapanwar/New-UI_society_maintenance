<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';

$errors = [];
$work_title = $worker_name = $contact_number = $amount = $description = '';

// Admin access check
requireRole(['admin', 'cashier']);

/* ===== HANDLE FORM SUBMIT ===== */
if (isset($_POST['add_misc_work'])) {

    // Map submitted values to variables
    $work_title     = trim($_POST['work_title'] ?? '');
    $worker_name    = trim($_POST['worker_name'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $amount         = $_POST['amount'] ?? '';
    $description    = trim($_POST['description'] ?? '');

    // AUTO GENERATE: Set Month and Year to CURRENT date
    $targetMonth = date('n'); // Current Month (1-12)
    $targetYear  = date('Y'); // Current Year (e.g. 2026)

    /* ===== VALIDATION ===== */
    if ($work_title === '') $errors['work_title'] = 'Please enter work title';
    if ($worker_name === '') $errors['worker_name'] = 'Please enter worker name';

    if ($contact_number === '') {
        $errors['contact_number'] = 'Please enter contact number';
    } elseif (!preg_match('/^[0-9]{10}$/', $contact_number)) {
        $errors['contact_number'] = 'Mobile must be 10 digits';
    }

    if ($amount === '') {
        $errors['amount'] = 'Please enter amount';
    } elseif (!is_numeric($amount) || $amount < 0) {
        $errors['amount'] = 'Please enter a valid amount';
    }

    if ($description === '') {
        $errors['description'] = 'Please enter description';
    }

    /* ===== INSERT WORK ===== */
    if (empty($errors)) {
        $status = 'paid';

        $stmt = $pdo->prepare("
            INSERT INTO miscellaneous_works 
            (work_title, worker_name, contact_number, amount, description, month, year, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");


        if ($stmt->execute([$work_title, $worker_name, $contact_number, $amount, $description, $targetMonth, $targetYear, $status])) {
            // $_SESSION['success'] = 'Miscellaneous work added successfully';
            flash_set('success', 'Miscellaneous work added successfully');
            header('Location: ' . BASE_URL . 'miscellaneous_work.php');
            exit();
        } else {
            flash_set('err', 'Database error! Miscellaneous work not added.');
            header('Location: ' . BASE_URL . 'add/add_miscellaneous_work.php');
            exit();
        }
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Add Miscellaneous Work</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>miscellaneous_work.php">Miscellaneous Work</a></li>
        <li class="breadcrumb-item active">Add Work</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Add Miscellaneous Work</h5>
                </div>
                <div class="card-body">

                    <form method="post" autocomplete="off" id="addMiscForm" novalidate>
                        <!-- Invisible fake fields to prevent browser autofill -->
                        <input type="text" name="nope" style="display:none">
                        <input type="password" name="nope_pass" style="display:none">

                        <div class="row">

                            <!-- Work Title -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Work Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="work_title" autocomplete="new-title" required value="<?= htmlspecialchars($work_title) ?>">
                                <?php if (isset($errors['work_title'])): ?><small class="text-danger"><?= $errors['work_title'] ?></small><?php endif; ?>
                            </div>

                            <!-- Amount -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="amount" autocomplete="off" required value="<?= htmlspecialchars($amount) ?>">
                                <?php if (isset($errors['amount'])): ?><small class="text-danger"><?= $errors['amount'] ?></small><?php endif; ?>
                            </div>

                            <!-- Worker Name -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Worker Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="worker_name" autocomplete="new-worker" required value="<?= htmlspecialchars($worker_name) ?>">
                                <?php if (isset($errors['worker_name'])): ?><small class="text-danger"><?= $errors['worker_name'] ?></small><?php endif; ?>
                            </div>

                            <!-- Contact Number -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="contact_number" autocomplete="off" inputmode="numeric" required value="<?= htmlspecialchars($contact_number) ?>">
                                <?php if (isset($errors['contact_number'])): ?><small class="text-danger"><?= $errors['contact_number'] ?></small><?php endif; ?>
                            </div>

                            <!-- Description (Full Width) -->
                            <div class="col-12 mb-3">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="description" rows="3" required><?= htmlspecialchars($description) ?></textarea>
                                <?php if (isset($errors['description'])): ?><small class="text-danger"><?= $errors['description'] ?></small><?php endif; ?>
                            </div>

                        </div>

                        <button type="submit" name="add_misc_work" class="btn btn-primary">Submit</button>

                        <a href="<?= BASE_URL ?>miscellaneous_work.php" class="btn btn-secondary mx-2">Back</a>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Prevent Chrome autofill on page load
        const fakeText = document.querySelector('input[name="nope"]');
        const fakePass = document.querySelector('input[name="nope_pass"]');
        if (fakeText && fakePass) {
            fakeText.focus();
            fakePass.focus();
            document.body.focus();
        }
    });
</script>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>