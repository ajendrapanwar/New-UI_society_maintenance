<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';

requireRole(['admin']);

/* PHPMailer */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../vendor/PHPMailer/Exception.php';

// ================= GET COMPLAINT ID =================
if (!isset($_GET['id'])) {
    die("Complaint ID missing!");
}
$id = $_GET['id'];

// ================= FETCH COMPLAINT WITH USER EMAIL =================
$stmt = $pdo->prepare("
    SELECT c.*, u.name, u.mobile, u.email 
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    die("Complaint not found!");
}

$error = "";

// ================= UPDATE COMPLAINT =================
if (isset($_POST['update'])) {

    $status = $_POST['status'];
    $resolve_note = trim($_POST['resolve_note']);

    // Validate resolve note
    if ($status == 'completed' && empty($resolve_note)) {
        $error = "Resolve note is required!";
    } else {

        $resolved_at = ($status == 'completed') ? date("Y-m-d H:i:s") : NULL;

        // Update DB
        $stmt = $pdo->prepare("
            UPDATE complaints 
            SET status=?, resolve_note=?, resolved_at=? 
            WHERE id=?
        ");

        if ($stmt->execute([$status, $resolve_note, $resolved_at, $id])) {
            // $_SESSION['success'] = "Complaint updated successfully!";
            flash_set('success', 'Complaint updated successfully!');
            header("Location: " . BASE_URL . "complaints.php");
            exit();
        } else {
            flash_set('err', 'Database error! Complaint not updated.');
            header('Location: ' . BASE_URL . 'edit/edit_complaint.php');
            exit();
        }


        // ================= SEND EMAIL TO USER ONLY WHEN COMPLETED =================
        if ($status == 'completed') {

            $userEmail = $complaint['email'];
            $user_name = $complaint['name'];
            $subject   = $complaint['subject'];

            $mail = new PHPMailer(true);

            try {
                // SMTP SETTINGS
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'fakeclint01@gmail.com'; // YOUR SMTP EMAIL
                $mail->Password   = 'xjpv lacb osnv cwmj';     // YOUR APP PASSWORD
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // FROM EMAIL (must same as SMTP)
                $mail->setFrom('fakeclint01@gmail.com', 'Society Maintenance System');

                // SEND TO USER
                $mail->addAddress($userEmail, $user_name);

                // EMAIL FORMAT
                $mail->isHTML(true);
                $mail->Subject = "Your Complaint Has Been Resolved";

                // PROFESSIONAL EMAIL TEMPLATE
                $mail->Body = "
                <div style='font-family:Segoe UI,Arial,sans-serif;background:#f4f6f9;padding:30px'>

                    <div style='max-width:650px;margin:auto;background:#ffffff;
                                border-radius:10px;box-shadow:0 5px 20px rgba(0,0,0,0.1);
                                overflow:hidden;border:1px solid #e5e5e5'>

                        <div style='background:#198754;padding:15px;text-align:center;color:white'>
                            <h2 style='margin:0;font-size:20px;'>Complaint Resolved ✅</h2>
                        </div>

                        <div style='padding:20px;color:#333;font-size:14px'>
                            <p>Hello <b>$user_name</b>,</p>
                            <p>Your complaint has been successfully resolved.</p>

                            <table style='width:100%;border-collapse:collapse;font-size:14px;margin-top:15px'>
                                <tr style='background:#f8f9fa'>
                                    <td style='padding:10px;border:1px solid #ddd;font-weight:bold;'>Subject</td>
                                    <td style='padding:10px;border:1px solid #ddd;'>$subject</td>
                                </tr>

                                <tr>
                                    <td style='padding:10px;border:1px solid #ddd;font-weight:bold;'>Status</td>
                                    <td style='padding:10px;border:1px solid #ddd;color:green;font-weight:bold;'>Completed</td>
                                </tr>

                                <tr style='background:#f8f9fa'>
                                    <td style='padding:10px;border:1px solid #ddd;font-weight:bold;'>Resolve Note</td>
                                    <td style='padding:10px;border:1px solid #ddd;'>$resolve_note</td>
                                </tr>
                            </table>

                            <p style='margin-top:15px;'>Thank you for using Society Maintenance System.</p>
                        </div>

                        <div style='background:#f1f1f1;padding:10px;text-align:center;font-size:12px;color:#777'>
                            Society Maintenance System © " . date('Y') . "
                        </div>

                    </div>
                </div>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("User Mail Error: " . $mail->ErrorInfo);
            }
        }
    }
}

include __DIR__ . '/../../resources/layout/header.php';
?>


<style>
    .img-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.95);
        text-align: center;
    }

    .modal-content-img {
        max-width: 90%;
        max-height: 80%;
        border-radius: 5px;
        transition: transform 0.2s ease;
        z-index: 1;
    }

    /* CLOSE BUTTON */
    .close-btn {
        position: fixed;
        top: 10px;
        right: 20px;
        color: white;
        font-size: 35px;
        font-weight: bold;
        cursor: pointer;
        z-index: 10000;
    }

    /* ZOOM BUTTONS */
    .zoom-controls {
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10000;
    }

    .zoom-controls button {
        padding: 8px 15px;
        font-size: 18px;
        margin: 0 5px;
        border: none;
        border-radius: 5px;
        background: white;
        cursor: pointer;
    }

    .zoom-controls button:hover {
        background: #0d6efd;
        color: white;
    }
</style>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="container-fluid px-4">
    <h1 class="mt-4">Update Complaint</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>complaints.php">All Complaints</a></li>
        <li class="breadcrumb-item active">Update Complaint</li>
    </ol>

    <div class="card col-md-6">
        <div class="card-header">
            <h5>Complaint #<?= $complaint['id'] ?></h5>
        </div>

        <div class="card-body">
            <form method="POST" id="complaintForm">

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label>User Name</label>
                        <input type="text" class="form-control form-control-sm" value="<?= $complaint['name'] ?>"
                            readonly>
                    </div>

                    <div class="col-md-6 mb-2">
                        <label>Mobile</label>
                        <input type="text" class="form-control form-control-sm" value="<?= $complaint['mobile'] ?>"
                            readonly>
                    </div>
                </div>

                <div class="mb-2">
                    <label>Subject</label>
                    <input type="text" class="form-control form-control-sm" value="<?= $complaint['subject'] ?>"
                        readonly>
                </div>

                <div class="mb-2">
                    <label>Message</label>
                    <textarea class="form-control form-control-sm" rows="2"
                        readonly><?= $complaint['message'] ?></textarea>
                </div>

                <?php if (!empty($complaint['image'])): ?>
                    <div class="mb-2">
                        <label>Complaint Image</label><br>
                        <?php if (!empty($complaint['image'])): ?>
                            <div class="mb-2">
                                <img src="<?= BASE_URL ?>uploads/complaints/<?= $complaint['image'] ?>"
                                    class="img-thumbnail complaint-img" width="180" id="complaintImage">
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>


                <!-- STATUS -->
                <div class="mb-2">
                    <label>Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="pending" <?= $complaint['status'] == 'pending' ? 'selected' : '' ?>>Pending
                        </option>
                        <option value="processing" <?= $complaint['status'] == 'processing' ? 'selected' : '' ?>>
                            Processing</option>
                        <option value="completed" <?= $complaint['status'] == 'completed' ? 'selected' : '' ?>>Completed
                        </option>
                    </select>
                </div>


                <!-- RESOLVE NOTE (HIDDEN BY DEFAULT) -->
                <div class="mb-2" id="resolveBox" style="display:none;">
                    <label>Resolve Note</label>

                    <textarea name="resolve_note" id="resolve_note"
                        class="form-control form-control-sm <?php echo ($error ? 'is-invalid' : ''); ?>"
                        rows="2"><?= htmlspecialchars($complaint['resolve_note'] ?? '') ?></textarea>

                    <!-- ERROR MESSAGE -->
                    <div id="resolveError" class="invalid-feedback <?php echo ($error ? 'd-block' : ''); ?>">
                        <?= $error ?>
                    </div>
                </div>



                <button type="submit" name="update" class="btn btn-success btn-sm mt-2">Update Complaint</button>

                <a href="<?= BASE_URL ?>complaints.php" class="btn btn-secondary btn-sm mt-2">Back</a>

            </form>
        </div>
    </div>
</div>



<!-- IMAGE POPUP MODAL -->
<div id="imageModal" class="img-modal">
    <span class="close-btn">&times;</span>

    <!-- Zoom Buttons -->
    <div class="zoom-controls">
        <button id="zoomIn">+</button>
        <button id="zoomOut">-</button>
        <button id="resetZoom">Reset</button>
    </div>

    <img class="modal-content-img" id="popupImg">
</div>



<script>
    const statusField = document.getElementById("status");
    const resolveBox = document.getElementById("resolveBox");
    const resolveNote = document.getElementById("resolve_note");
    const resolveError = document.getElementById("resolveError");

    // Show/Hide Resolve Note
    function toggleResolve() {
        if (statusField.value === "completed") {
            resolveBox.style.display = "block";
        } else {
            resolveBox.style.display = "none";
            resolveNote.classList.remove("is-invalid");
            resolveError.classList.remove("d-block");
            resolveError.innerText = "";
        }
    }
    toggleResolve();
    statusField.addEventListener("change", toggleResolve);

    // Frontend validation (NO ALERT)
    document.getElementById("complaintForm").addEventListener("submit", function(e) {

        if (statusField.value === "completed" && resolveNote.value.trim() === "") {
            resolveNote.classList.add("is-invalid");
            resolveError.classList.add("d-block");
            resolveError.innerText = "Resolve note is required when complaint is completed!";
            resolveNote.focus();
            e.preventDefault();
        }
    });
</script>

<script>
    const modal = document.getElementById("imageModal");
    const modalImg = document.getElementById("popupImg");
    const complaintImage = document.getElementById("complaintImage");
    const closeBtn = document.querySelector(".close-btn");

    let scale = 1; // zoom level

    // Open popup
    if (complaintImage) {
        complaintImage.onclick = function() {
            modal.style.display = "block";
            modalImg.src = this.src;
            scale = 1;
            modalImg.style.transform = "scale(1)";
        }
    }

    // Close popup
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    // Close when click outside image
    modal.onclick = function(e) {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    }

    // Zoom Buttons
    document.getElementById("zoomIn").onclick = function() {
        scale += 0.2;
        modalImg.style.transform = "scale(" + scale + ")";
    };

    document.getElementById("zoomOut").onclick = function() {
        if (scale > 0.2) {
            scale -= 0.2;
            modalImg.style.transform = "scale(" + scale + ")";
        }
    };

    document.getElementById("resetZoom").onclick = function() {
        scale = 1;
        modalImg.style.transform = "scale(1)";
    };
</script>




<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>