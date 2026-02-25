<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';

requireRole(['user']);

/* PHPMailer */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '../../../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '../../../vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '../../../vendor/PHPMailer/Exception.php';

$errors = [];

/* USER DATA */
$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$subject = $message = "";

/* FORM SUBMIT */
if (isset($_POST['submit'])) {

    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    /* ================= IMAGE UPLOAD ================= */
    $imageName = NULL;

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

    /* ================= VALIDATION ================= */
    if (empty($subject)) $errors['subject'] = "Subject is required!";
    if (empty($message)) $errors['message'] = "Complaint message is required!";

    /* ================= INSERT DATA ================= */
    if (empty($errors)) {

        /* ================= FETCH ADMIN EMAILS ================= */
        $admins = $pdo->query("SELECT name,email FROM users WHERE role='admin'")->fetchAll();

        $stmt = $pdo->prepare("INSERT INTO complaints (user_id, subject, message, image) VALUES (?,?,?,?)");

        if ($stmt->execute([$user_id, $subject, $message, $imageName])) {
            // $_SESSION['success'] = "Complaint submitted successfully!";
            flash_set('success', 'Complaint submitted successfully!');
            header("Location: " . BASE_URL . "view/view_userComplaint_status.php");
            exit();
        } else {
            flash_set('err', 'Database error! Complaint not added.');
            header('Location: ' . BASE_URL . 'add/add_complaint.php');
            exit();
        }

        /* ================= SEND EMAIL ================= */
        if ($admins) {

            $mail = new PHPMailer(true);

            try {
                // SMTP CONFIG
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'fakeclint01@gmail.com';
                $mail->Password   = 'xjpv lacb osnv cwmj';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('YOUR_GMAIL@gmail.com', 'Society Maintenance System');

                // Add all admins
                foreach ($admins as $admin) {
                    $mail->addAddress($admin['email'], $admin['name']);
                }

                // Email HTML
                $mail->isHTML(true);
                $mail->Subject = "New Complaint Raised";

                $mail->Body = "
                <div style='max-width:650px;margin:auto;font-family:Segoe UI,Arial,sans-serif;background:#f2f4f8;padding:20px;border-radius:10px;'>

                    <!-- HEADER -->
                    <div style='background:#0d6efd;color:white;padding:16px 20px;border-radius:10px 10px 0 0;
                                font-size:20px;font-weight:600;text-align:center;letter-spacing:0.5px;'>
                        🚨 New Complaint Raised
                    </div>

                    <!-- BODY -->
                    <div style='background:white;padding:20px;border-radius:0 0 10px 10px;'>

                        <p style='font-size:15px;color:#333;margin-bottom:12px;'>
                            A new complaint has been submitted in the system. Details are below:
                        </p>

                        <table style='border-collapse:collapse;width:100%;font-size:14px;border:1px solid #dee2e6;'>
                            <tr style='background:#f1f5ff;'>
                                <td style='padding:10px;font-weight:600;border:1px solid #dee2e6;width:35%;'>User Name</td>
                                <td style='padding:10px;border:1px solid #dee2e6;'>$user_name</td>
                            </tr>
                            <tr>
                                <td style='padding:10px;font-weight:600;border:1px solid #dee2e6;background:#fafafa;'>Subject</td>
                                <td style='padding:10px;border:1px solid #dee2e6;'>$subject</td>
                            </tr>
                            <tr style='background:#fdfefe;'>
                                <td style='padding:10px;font-weight:600;border:1px solid #dee2e6;'>Message</td>
                                <td style='padding:10px;border:1px solid #dee2e6;line-height:1.6;'>$message</td>
                            </tr>
                        </table>

                        <!-- FOOTER -->
                        <div style='margin-top:20px;padding-top:10px;border-top:1px solid #eee;
                                    text-align:center;font-size:12px;color:#777;'>
                            Society Maintenance System <br>
                            <span style='color:#999;'>Automated Notification</span>
                        </div>

                    </div>
                </div>
                ";


                // Attach image if uploaded
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
    }
}

include __DIR__ . '/../../resources/layout/header.php';
?>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Raise a Complaint</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>view/view_userComplaint_status.php">Complaint Status</a></li>
        <li class="breadcrumb-item active">Raise a Complaint</li>
    </ol>

    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header">
                <h5 class="mb-0">Raise Complaint</h5>
            </div>

            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">

                    <!-- USER NAME -->
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" class="form-control" value="<?= $user_name ?>" readonly>
                    </div>

                    <!-- SUBJECT -->
                    <div class="mb-3">
                        <label>Subject *</label>
                        <input type="text" name="subject"
                            class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                            value="<?= htmlspecialchars($subject) ?>">

                        <?php if (isset($errors['subject'])): ?>
                            <div class="invalid-feedback"><?= $errors['subject'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- MESSAGE -->
                    <div class="mb-3">
                        <label>Complaint Message *</label>
                        <textarea name="message"
                            class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                            rows="4"><?= htmlspecialchars($message) ?></textarea>

                        <?php if (isset($errors['message'])): ?>
                            <div class="invalid-feedback"><?= $errors['message'] ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- IMAGE -->
                    <div class="mb-3">
                        <label>Upload Image (Optional)</label>
                        <input type="file" name="image"
                            class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>">

                        <?php if (isset($errors['image'])): ?>
                            <div class="invalid-feedback"><?= $errors['image'] ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary">Submit Complaint</button>

                    <a href="<?= BASE_URL ?>view/view_userComplaint_status.php" class="btn btn-secondary mx-2">Back</a>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../resources/layout/footer.php'; ?>