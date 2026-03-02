<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';

requireRole(['user']);

/* PHPMailer */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../vendor/PHPMailer/Exception.php';

$errors = [];

/* USER DATA */
$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$subject = $message = "";

/* ================= SUBMIT COMPLAINT ================= */
if (isset($_POST['submit_complaint'])) {

    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    /* IMAGE UPLOAD */
    $imageName = null;

    if (!empty($_FILES['image']['name'])) {

        $folder = $_SERVER['DOCUMENT_ROOT'] . "/society_maintenance/public/uploads/complaints/";
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $errors['image'] = "Only JPG, PNG, WEBP images allowed!";
        } else {
            $imageName = time() . "_" . rand(1000, 9999) . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $folder . $imageName);
        }
    }

    /* VALIDATION */
    if (empty($subject)) $errors['subject'] = "Subject is required!";
    if (empty($message)) $errors['message'] = "Complaint message is required!";

    /* INSERT */
    if (empty($errors)) {

        $admins = $pdo->query("SELECT name,email FROM users WHERE role='admin'")->fetchAll();

        $stmt = $pdo->prepare("
            INSERT INTO complaints (user_id, subject, message, image, status, created_at)
            VALUES (?,?,?,?, 'pending', NOW())
        ");

        if ($stmt->execute([$user_id, $subject, $message, $imageName])) {

            /* SEND EMAIL TO ADMINS */
            if ($admins) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'fakeclint01@gmail.com';
                    $mail->Password   = 'xjpv lacb osnv cwmj';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('fakeclint01@gmail.com', 'Society Maintenance System');

                    foreach ($admins as $admin) {
                        $mail->addAddress($admin['email'], $admin['name']);
                    }

                    $mail->isHTML(true);
                    $mail->Subject = "New Complaint Raised";
                    $mail->Body = "
                        <h3>New Complaint Submitted</h3>
                        <p><strong>User:</strong> {$user_name}</p>
                        <p><strong>Subject:</strong> {$subject}</p>
                        <p><strong>Message:</strong> {$message}</p>
                    ";

                    if ($imageName) {
                        $filePath = $_SERVER['DOCUMENT_ROOT'] . "/society_maintenance/public/uploads/complaints/" . $imageName;
                        if (file_exists($filePath)) {
                            $mail->addAttachment($filePath);
                        }
                    }

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Email Error: " . $mail->ErrorInfo);
                }
            }

            flash_set('success', 'Complaint submitted successfully!');
            header("Location: " . BASE_URL . "view/view_userComplaint_status.php");
            exit();
        } else {
            flash_set('err', 'Database error!');
        }
    }
}

/* FETCH USER COMPLAINTS */
$stmt = $pdo->prepare("
    SELECT * FROM complaints
    WHERE user_id = ?
    ORDER BY id DESC
");
$stmt->execute([$user_id]);
$complaints = $stmt->fetchAll();

include __DIR__ . '/../../resources/layout/header.php';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Status</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">


    <link rel="stylesheet" href="../../assets/css/styles.css">

</head>

<body>

    <div class="main-wrapper">
        <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

        <main id="main-content">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title m-0">My Complaints</h1>

                <button class="btn btn-brand shadow-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#raiseComplaintModal">
                    <i class="fa-solid fa-plus me-2"></i> Raise Complaint
                </button>
            </div>

            <div class="data-card shadow-sm border-0">
                <div class="table-responsive">
                    <table id="user-bills-table" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Resolve Note</th>
                                <th>Resolved At</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($complaints)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-danger fw-bold">
                                        No Complaint Found !!!
                                    </td>
                                </tr>
                            <?php else: ?>

                                <?php foreach ($complaints as $c): ?>
                                    <tr>
                                        <td><?= $c['id'] ?></td>
                                        <td><?= htmlspecialchars($c['subject']) ?></td>
                                        <td><?= htmlspecialchars($c['message']) ?></td>

                                        <!-- STATUS -->
                                        <td>
                                            <?php
                                            if ($c['status'] == 'pending') echo "<span class='badge bg-warning'>Pending</span>";
                                            if ($c['status'] == 'processing') echo "<span class='badge bg-info'>Processing</span>";
                                            if ($c['status'] == 'completed') echo "<span class='badge bg-success'>Completed</span>";
                                            ?>
                                        </td>

                                        <!-- CREATED DATE -->
                                        <td><?= $c['created_at'] ?></td>

                                        <!-- RESOLVE NOTE -->
                                        <td><?= !empty($c['resolve_note']) ? htmlspecialchars($c['resolve_note']) : '-' ?></td>

                                        <!-- RESOLVED DATE -->
                                        <td><?= !empty($c['resolved_at']) ? $c['resolved_at'] : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>



    <!-- RAISE COMPLAINT MODAL -->
    <div class="modal fade" id="raiseComplaintModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius:20px;">

                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-800">Raise Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4 pt-0">

                    <form method="POST" enctype="multipart/form-data" class="row g-3">

                        <!-- NAME -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                USER NAME
                            </label>
                            <input type="text"
                                class="form-control bg-light border-0"
                                value="<?= htmlspecialchars($user_name) ?>"
                                readonly>
                        </div>

                        <!-- SUBJECT -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                SUBJECT <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                name="subject"
                                value="<?= htmlspecialchars($subject) ?>"
                                class="form-control bg-light border-0 <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                                placeholder="Enter complaint subject">
                            <small class="text-danger"><?= $errors['subject'] ?? '' ?></small>
                        </div>

                        <!-- MESSAGE -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                MESSAGE <span class="text-danger">*</span>
                            </label>
                            <textarea name="message"
                                rows="4"
                                class="form-control bg-light border-0 <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                                placeholder="Describe your complaint"><?= htmlspecialchars($message) ?></textarea>
                            <small class="text-danger"><?= $errors['message'] ?? '' ?></small>
                        </div>

                        <!-- IMAGE -->
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted">
                                UPLOAD IMAGE (OPTIONAL)
                            </label>
                            <input type="file"
                                name="image"
                                class="form-control bg-light border-0 <?= isset($errors['image']) ? 'is-invalid' : '' ?>">
                            <small class="text-danger"><?= $errors['image'] ?? '' ?></small>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="submit_complaint"
                                class="btn btn-brand w-100 py-3 mt-2">
                                Submit Complaint
                            </button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>




    <!-- DataTables Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#complaint-table').DataTable({
                pageLength: 10,
                ordering: true,
                responsive: true,
                language: {
                    emptyTable: "No complaints found"
                }
            });
        });
    </script>

    <?php if (!empty($errors)): ?>
        <script>
            var raiseComplaintModal = new bootstrap.Modal(document.getElementById('raiseComplaintModal'));
            raiseComplaintModal.show();
        </script>
    <?php endif; ?>


</body>

</html>


<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>