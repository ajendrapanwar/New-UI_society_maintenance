<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';

requireRole(['admin']);

// PHPMailer Load
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../vendor/PHPMailer/SMTP.php';
require_once __DIR__ . '/../../vendor/PHPMailer/Exception.php';

$errors = [];
$name = $mobile = $vehicle = $flat_id = $purpose = $visit_type = "";


/* FETCH FLATS */
$flats = $pdo->query("
    SELECT f.id, f.flat_number, f.block_number
    FROM allotments a
    JOIN flats f ON a.flat_id = f.id
    ORDER BY f.block_number, f.flat_number
")->fetchAll();


/* FORM SUBMIT */
if (isset($_POST['submit'])) {

    $name = trim($_POST['visitor_name']);
    $mobile = trim($_POST['mobile']);
    $vehicle = strtoupper(trim($_POST['vehicle_no']));
    $flat_id = $_POST['flat_id'];
    $visit_type = $_POST['visit_type'] ?? '';
    $purpose    = trim($_POST['purpose']);

    /* VALIDATION */
    if ($name == '') $errors['visitor_name'] = "Enter visitor name";

    if ($mobile == '') {
        $errors['mobile'] = "Enter mobile number";
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['mobile'] = "Enter valid 10 digit mobile number";
    }

    if ($vehicle == '') {
        $errors['vehicle_no'] = "Enter vehicle number";
    } elseif (!preg_match('/^[A-Z0-9- ]{4,20}$/', $vehicle)) {
        $errors['vehicle_no'] = "Enter valid vehicle number";
    }

    if ($flat_id == '') $errors['flat_id'] = "Select flat";
    if ($visit_type == '') $errors['visit_type'] = "Select visit type";
    if ($visit_type == 'Other' && $purpose == '') {
        $errors['purpose'] = "Enter purpose";
    }

    // CHECK VEHICLE IN RESIDENT PARKING
    $checkVehicle = $pdo->prepare("SELECT id FROM resident_parking WHERE vehicle1 = ? OR vehicle2 = ?");
    $checkVehicle->execute([$vehicle, $vehicle]);
    if ($checkVehicle->rowCount() > 0) {
        $errors['vehicle_no'] = "This vehicle is already registered in resident parking";
    }


    /* GET OWNER EMAIL */
    $getOwner = $pdo->prepare("
        SELECT u.name, u.email 
        FROM users u
        JOIN allotments a ON u.id = a.user_id
        WHERE a.flat_id = ?
    ");
    $getOwner->execute([$flat_id]);
    $owner = $getOwner->fetch();

    $ownerEmail = $owner['email'] ?? '';
    $ownerName  = $owner['name'] ?? 'Owner';



    /* INSERT + SEND EMAIL */
    if (empty($errors)) {

        // INSERT VISITOR
        $stmt = $pdo->prepare("INSERT INTO visitor_entries(visitor_name,mobile,vehicle_no,flat_id,visit_type,purpose) VALUES(?,?,?,?,?,?)");

        if ($stmt->execute([$name, $mobile, $vehicle, $flat_id, $visit_type, $purpose])) {
            // $_SESSION['success'] = "Visitor Entry Added & Email Sent to Owner";
            flash_set('success', 'Visitor Entry Added & Email Sent to Owner');
            header('Location: ' . BASE_URL . 'visitors.php');
            exit();
        } else {

            flash_set('err', 'Database error! Visitor not added.');
            header('Location: ' . BASE_URL . 'add/add_visitor.php');
            exit();
        }

        // SEND EMAIL
        if (!empty($ownerEmail)) {

            $mail = new PHPMailer(true);

            try {
                // SMTP SETTINGS
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'fakeclint01@gmail.com';
                $mail->Password   = 'xjpv lacb osnv cwmj';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // FROM & TO
                $mail->setFrom('YOUR_GMAIL@gmail.com', 'Society Gate System');
                $mail->addAddress($ownerEmail, $ownerName);

                // EMAIL CONTENT
                $mail->isHTML(true);
                $mail->Subject = "Visitor Alert - Society Gate";

                $mail->Body = "
                <div style='max-width:600px;margin:auto;font-family:Arial,sans-serif;background:#f5f5f5;padding:20px;border-radius:10px;'>

                    <div style='background:#1e88e5;color:white;padding:15px;border-radius:8px 8px 0 0;text-align:center;font-size:20px;font-weight:bold;'>
                        🚨 Visitor Alert - Society Gate
                    </div>

                    <div style='background:white;padding:20px;border-radius:0 0 8px 8px;'>

                        <p style='font-size:16px;color:#333;'>Hello <b>$ownerName</b>,</p>

                        <p style='font-size:15px;color:#444;'>
                            <b style='color:#1e88e5;'>$visit_type</b> is waiting at your flat.
                        </p>

                        <table style='width:100%;border-collapse:collapse;font-size:14px;margin-top:15px;'>
                            <tr>
                                <td style='padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>Visitor Name</td>
                                <td style='padding:8px;border:1px solid #ddd;'>$name</td>
                            </tr>
                            <tr>
                                <td style='padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>Mobile</td>
                                <td style='padding:8px;border:1px solid #ddd;'>$mobile</td>
                            </tr>
                            <tr>
                                <td style='padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>Vehicle</td>
                                <td style='padding:8px;border:1px solid #ddd;'>$vehicle</td>
                            </tr>
                            <tr>
                                <td style='padding:8px;border:1px solid #ddd;background:#f9f9f9;font-weight:bold;'>Purpose</td>
                                <td style='padding:8px;border:1px solid #ddd;'>$purpose</td>
                            </tr>
                        </table>

                        <div style='margin-top:20px;font-size:13px;color:#777;text-align:center;'>
                            Society Gate System • Automated Notification
                        </div>

                    </div>

                </div>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Email Error: " . $mail->ErrorInfo);
            }
        }
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Add Visitor</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>visitors.php">Visitors</a></li>
        <li class="breadcrumb-item active">Add Visitor</li>
    </ol>

    <div class="col-lg-7 col-md-9">

        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="card-title mb-0">Visitor Entry Form</h5>
            </div>

            <div class="card-body">
                <form method="POST">

                    <div class="row">

                        <!-- VISITOR NAME -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Visitor Name <span class="text-danger">*</span></label>
                            <input type="text" name="visitor_name" class="form-control" value="<?= htmlspecialchars($name) ?>">
                            <small class="text-danger"><?= $errors['visitor_name'] ?? '' ?></small>
                        </div>

                        <!-- MOBILE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($mobile) ?>">
                            <small class="text-danger"><?= $errors['mobile'] ?? '' ?></small>
                        </div>

                        <!-- VEHICLE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vehicle No <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_no" class="form-control" value="<?= htmlspecialchars($vehicle) ?>">
                            <small class="text-danger"><?= $errors['vehicle_no'] ?? '' ?></small>
                        </div>


                        <!-- FLAT -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Visiting Flat <span class="text-danger">*</span></label>
                            <select name="flat_id" class="form-select">
                                <option value="">Select Flat</option>
                                <?php foreach ($flats as $f): ?>
                                    <option value="<?= $f['id'] ?>" <?= ($flat_id == $f['id']) ? 'selected' : '' ?>>
                                        Block <?= $f['block_number'] ?> - Flat <?= $f['flat_number'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-danger"><?= $errors['flat_id'] ?? '' ?></small>
                        </div>

                        <!-- VISIT TYPE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Visit Type <span class="text-danger">*</span></label>
                            <select name="visit_type" id="visit_type" class="form-select">
                                <option value="">Select Type</option>
                                <option value="Guest" <?= ($visit_type == 'Guest') ? 'selected' : '' ?>>Guest</option>
                                <option value="Delivery Boy" <?= ($visit_type == 'Delivery Boy') ? 'selected' : '' ?>>Delivery Boy</option>
                                <option value="Electrician" <?= ($visit_type == 'Electrician') ? 'selected' : '' ?>>Electrician</option>
                                <option value="Plumber" <?= ($visit_type == 'Plumber') ? 'selected' : '' ?>>Plumber</option>
                                <option value="Other" <?= ($visit_type == 'Other') ? 'selected' : '' ?>>Other</option>
                            </select>
                            <small class="text-danger"><?= $errors['visit_type'] ?? '' ?></small>
                        </div>


                        <!-- PURPOSE -->
                        <div class="col-md-12 mb-3" id="purposeBox" style="display:none;">
                            <label class="form-label">Purpose</label>
                            <input type="text" name="purpose" class="form-control" value="<?= htmlspecialchars($purpose) ?>">
                            <small class="text-danger"><?= $errors['purpose'] ?? '' ?></small>
                        </div>


                    </div>

                    <button name="submit" class="btn btn-success">Entry IN</button>

                    <a href="<?= BASE_URL ?>visitors.php" class="btn btn-secondary mx-2">Back</a>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('visit_type').addEventListener('change', function() {
        let purposeBox = document.getElementById('purposeBox');
        if (this.value === 'Other') {
            purposeBox.style.display = 'block';
        } else {
            purposeBox.style.display = 'none';
        }
    });

    // Auto show if form reload with Other selected
    window.onload = function() {
        let vt = document.getElementById('visit_type').value;
        if (vt === 'Other') {
            document.getElementById('purposeBox').style.display = 'block';
        }
    };
</script>


<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>