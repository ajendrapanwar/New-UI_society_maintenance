<?php
require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';

requireRole(['admin']);

$errors = [];

/* GET ID */
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "resident_parking.php");
    exit();
}

$id = $_GET['id'];

/* FETCH EXISTING RECORD */
$stmt = $pdo->prepare("
    SELECT rp.*, f.flat_number, f.block_number, u.name AS user_name
    FROM resident_parking rp
    JOIN flats f ON rp.flat_id = f.id
    LEFT JOIN users u ON rp.user_id = u.id
    WHERE rp.id=?
");
$stmt->execute([$id]);
$parking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$parking) {
    die("Record not found");
}

/* HANDLE UPDATE */
if (isset($_POST['update'])) {

    $vehicle_count = $_POST['vehicle_count'];
    $vehicle1 = trim($_POST['vehicle1']);
    $vehicle2 = trim($_POST['vehicle2']);

    /* VALIDATION */
    if ($vehicle_count == 1 && $vehicle1 == '') {
        $errors['vehicle1'] = "Enter Vehicle No";
    }

    if ($vehicle_count == 2) {
        if ($vehicle1 == '') $errors['vehicle1'] = "Enter Vehicle 1 No";
        if ($vehicle2 == '') $errors['vehicle2'] = "Enter Vehicle 2 No";
    }

    /* UPDATE */
    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE resident_parking 
            SET vehicle_count=?, vehicle1=?, vehicle2=?
            WHERE id=?
        ");

        if ($stmt->execute([$vehicle_count, $vehicle1, $vehicle2, $id])) {
            // $_SESSION['success'] = "Parking Updated Successfully";
            flash_set('success', 'Parking Updated Successfully');
            header("Location: " . BASE_URL . "resident_parking.php");
            exit();
        } else {
            flash_set('err', 'Database error! Parking not updated.');
            header('Location: ' . BASE_URL . 'edit/edit_resident_parking.php');
            exit();
        }
    }
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<style>
    /* Disabled fields cursor fix */
    input:disabled,
    select:disabled,
    textarea:disabled {
        cursor: not-allowed !important;
        background-color: #f8f9fa;
        /* light gray */
        opacity: 1;
        /* prevent Bootstrap fading */
    }
</style>

<div class="sidebar-overlay" onclick="toggleSidebar()"></div>


<div class="container-fluid px-4 mb-4">
    <h1 class="mt-4">Edit Resident Parking</h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>resident_parking.php">Resident Parking</a></li>
        <li class="breadcrumb-item active">Edit Parking</li>
    </ol>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5>Edit Resident Parking</h5>
            </div>

            <div class="card-body">
                <form method="POST">

                    <div class="row">

                        <!-- FLAT NO (DISABLED) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Flat No</label>
                            <input type="text" class="form-control" value="<?= $parking['flat_number'] ?>" disabled>
                        </div>

                        <!-- BLOCK (DISABLED) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Block</label>
                            <input type="text" class="form-control" value="<?= $parking['block_number'] ?>" disabled>
                        </div>

                        <!-- RESIDENT NAME (DISABLED) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Resident Name</label>
                            <input type="text" class="form-control" value="<?= $parking['user_name'] ?>" disabled>
                        </div>

                        <!-- MOBILE (DISABLED) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mobile</label>
                            <input type="text" class="form-control" value="<?= $parking['mobile'] ?>" disabled>
                        </div>

                        <!-- VEHICLE COUNT -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No of Vehicles</label>
                            <select name="vehicle_count" id="vehicle_count" class="form-select">
                                <option value="1" <?= $parking['vehicle_count'] == 1 ? 'selected' : '' ?>>1</option>
                                <option value="2" <?= $parking['vehicle_count'] == 2 ? 'selected' : '' ?>>2</option>
                            </select>
                        </div>

                        <!-- VEHICLE 1 -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vehicle No 1</label>
                            <input type="text" name="vehicle1" class="form-control"
                                value="<?= htmlspecialchars($parking['vehicle1']) ?>">
                            <small class="text-danger"><?= $errors['vehicle1'] ?? '' ?></small>
                        </div>

                        <!-- VEHICLE 2 -->
                        <div class="col-md-6 mb-3" id="vehicle2_box">
                            <label class="form-label">Vehicle No 2</label>
                            <input type="text" name="vehicle2" class="form-control"
                                value="<?= htmlspecialchars($parking['vehicle2']) ?>">
                            <small class="text-danger"><?= $errors['vehicle2'] ?? '' ?></small>
                        </div>

                    </div>

                    <button type="submit" name="update" class="btn btn-primary">Update Parking</button>

                    <a href="<?= BASE_URL ?>resident_parking.php" class="btn btn-secondary mx-2">Back</a>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    /* SHOW/HIDE VEHICLE 2 */
    function toggleVehicle2() {
        let v = document.getElementById("vehicle_count").value;
        document.getElementById("vehicle2_box").style.display = (v == 2) ? "block" : "none";
    }

    document.getElementById("vehicle_count").addEventListener("change", toggleVehicle2);
    toggleVehicle2(); // run on page load
</script>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>