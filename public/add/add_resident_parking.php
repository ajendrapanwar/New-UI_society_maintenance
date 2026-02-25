<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';

requireRole(['admin']);

$errors = [];
$flat_id = $user_id = $name = $mobile = $vehicle1 = $vehicle2 = '';
$vehicle_count = 1;

/* ================= FETCH ALLOTTED FLATS NOT IN PARKING ================= */
$flats = $pdo->query("
    SELECT f.id, f.flat_number, f.block_number, u.name, u.mobile, a.user_id
    FROM allotments a
    JOIN flats f ON a.flat_id = f.id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN resident_parking rp ON rp.flat_id = f.id
    WHERE rp.flat_id IS NULL
    ORDER BY f.block_number, f.flat_number
")->fetchAll(PDO::FETCH_ASSOC);


/* ================= HANDLE FORM SUBMIT ================= */
if (isset($_POST['submit'])) {

    $flat_id = $_POST['flat_id'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    $name = trim($_POST['name']); // auto filled
    $mobile = trim($_POST['mobile']);
    $vehicle_count = $_POST['vehicle_count'];
    $vehicle1 = trim($_POST['vehicle1']);
    $vehicle2 = trim($_POST['vehicle2']);

    /* VALIDATION */
    if (!$flat_id) $errors['flat'] = "Select Flat No";
    if (!preg_match('/^[0-9]{10}$/', $mobile)) $errors['mobile'] = "Enter valid 10 digit mobile";

    if ($vehicle_count == 1 && $vehicle1 == '') $errors['vehicle1'] = "Enter Vehicle No";
    if ($vehicle_count == 2) {
        if ($vehicle1 == '') $errors['vehicle1'] = "Enter Vehicle 1 No";
        if ($vehicle2 == '') $errors['vehicle2'] = "Enter Vehicle 2 No";
    }

    /* CHECK DUPLICATE FLAT */
    $check = $pdo->prepare("SELECT id FROM resident_parking WHERE flat_id=?");
    $check->execute([$flat_id]);
    if ($check->fetch()) {
        $errors['flat'] = "This flat already has parking registered";
    }

    /* INSERT DATA */
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO resident_parking(flat_id,user_id,name,mobile,vehicle_count,vehicle1,vehicle2)
            VALUES(?,?,?,?,?,?,?)
        ");


        if ($stmt->execute([$flat_id, $user_id, $name, $mobile, $vehicle_count, $vehicle1, $vehicle2])) {
            // $_SESSION['success'] = "Resident Parking Added Successfully";
            flash_set('success', 'Resident Parking Added Successfully');
            header('Location: ' . BASE_URL . 'resident_parking.php');
            exit();
        } else {
            flash_set('err', 'Database error! Resident parking not added.');
            header('Location: ' . BASE_URL . 'add/add_resident_parking.php');
            exit();
        }
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<style>
    .is-invalid {
        border-color: #dc3545 !important;
    }

    /* disabled cursor */
    input:disabled {
        cursor: not-allowed;
        background-color: #f8f9fa;
        opacity: 1;
    }
</style>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Add Resident Parking</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>resident_parking.php">Resident Parking</a></li>
        <li class="breadcrumb-item active">Add Resident Parking</li>
    </ol>

    <div class="col-md-6">

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Add Resident Parking</h5>
            </div>

            <div class="card-body">
                <form method="POST">

                    <div class="row">

                        <!-- FLAT DROPDOWN -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Flat No <span class="text-danger">*</span></label>
                            <select name="flat_id" id="flat_id"
                                class="form-select <?= isset($errors['flat']) ? 'is-invalid' : '' ?>">
                                <option value="">Select Flat</option>

                                <?php foreach ($flats as $f): ?>
                                    <option value="<?= $f['id'] ?>"
                                        data-name="<?= $f['name'] ?>"
                                        data-mobile="<?= $f['mobile'] ?>"
                                        data-user="<?= $f['user_id'] ?>"
                                        <?= ($flat_id == $f['id']) ? 'selected' : '' ?>>
                                        Block <?= $f['block_number'] ?> - Flat <?= $f['flat_number'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-danger"><?= $errors['flat'] ?? '' ?></small>
                        </div>

                        <!-- RESIDENT NAME (DISABLED) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Resident Name</label>
                            <input type="text" name="name" id="name"
                                class="form-control"
                                value="<?= htmlspecialchars($name) ?>"
                                disabled>
                        </div>

                        <!-- MOBILE -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile <span class="text-danger">*</span></label>
                            <input type="text" name="mobile" id="mobile"
                                class="form-control <?= isset($errors['mobile']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($mobile) ?>">
                            <small class="text-danger"><?= $errors['mobile'] ?? '' ?></small>
                        </div>

                        <!-- HIDDEN USER ID -->
                        <input type="hidden" name="user_id" id="user_id" value="<?= $user_id ?>">

                        <!-- HIDDEN NAME (because disabled fields not submitted) -->
                        <input type="hidden" name="name" id="hidden_name" value="<?= htmlspecialchars($name) ?>">

                        <!-- VEHICLE COUNT -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No of Vehicles <span class="text-danger">*</span></label>
                            <select name="vehicle_count" id="vehicle_count" class="form-select">
                                <option value="1" <?= ($vehicle_count == 1) ? 'selected' : '' ?>>1</option>
                                <option value="2" <?= ($vehicle_count == 2) ? 'selected' : '' ?>>2</option>
                            </select>
                        </div>

                        <!-- VEHICLE 1 -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vehicle No 1 <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle1"
                                class="form-control <?= isset($errors['vehicle1']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($vehicle1) ?>">
                            <small class="text-danger"><?= $errors['vehicle1'] ?? '' ?></small>
                        </div>

                        <!-- VEHICLE 2 -->
                        <div class="col-md-6 mb-3" id="vehicle2_box"
                            style="display: <?= ($vehicle_count == 2) ? 'block' : 'none' ?>;">
                            <label class="form-label">Vehicle No 2 <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle2"
                                class="form-control <?= isset($errors['vehicle2']) ? 'is-invalid' : '' ?>"
                                value="<?= htmlspecialchars($vehicle2) ?>">
                            <small class="text-danger"><?= $errors['vehicle2'] ?? '' ?></small>
                        </div>

                    </div>

                    <button type="submit" name="submit" class="btn btn-primary">Save Parking</button>

                    <a href="<?= BASE_URL ?>resident_parking.php" class="btn btn-secondary mx-2">Back</a>

                </form>
            </div>
        </div>
    </div>
</div>


<script>
    document.getElementById("flat_id").addEventListener("change", function() {
        let opt = this.options[this.selectedIndex];
        let name = opt.getAttribute("data-name");
        let mobile = opt.getAttribute("data-mobile");
        let user = opt.getAttribute("data-user");

        document.getElementById("name").value = name;
        document.getElementById("hidden_name").value = name; // important
        document.getElementById("mobile").value = mobile;
        document.getElementById("user_id").value = user;
    });

    /* SHOW / HIDE VEHICLE 2 FIELD */
    document.getElementById("vehicle_count").addEventListener("change", function() {
        document.getElementById("vehicle2_box").style.display = (this.value == 2) ? "block" : "none";
    });
</script>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>