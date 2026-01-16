<?php

require_once __DIR__ . '/../../core/config.php';

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: logout.php');
    exit();
}

$errors = [];

// Fetch flats for dropdown
$flats = $pdo->query("SELECT id, flat_number, block_number FROM flats ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch bill data if id is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM bills WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bill) {
        $_SESSION['error'] = "Bill not found";
        header('Location: bills.php');
        exit();
    }
}

if (isset($_POST['edit_bill'])) {

    // Get POST values
    $bill_title = trim($_POST['bill_title']);
    $flat_id = $_POST['flat_id'];
    $amount = $_POST['amount'];
    $month = $_POST['month'];
    $id = $_POST['id'];

    // Validation
    if (empty($bill_title)) {
        $errors[] = 'Please define Bill Title';
    }
    if (empty($flat_id)) {
        $errors[] = 'Please select Flat Number';
    }
    if (empty($amount)) {
        $errors[] = 'Please enter Bill Amount';
    } elseif (!is_numeric($amount)) {
        $errors[] = 'Amount must be a number';
    }
    if (empty($month)) {
        $errors[] = 'Please select Bill Month';
    }

    // Update bill if valid
    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE bills SET flat_id = ?, bill_title = ?, amount = ?, month = ? WHERE id = ?");
        $stmt->execute([$flat_id, $bill_title, $amount, $month, $id]);

        $_SESSION['success'] = 'Bill Data has been updated';
        header('Location: bills.php');
        exit();
    }

    // If error, prefill $bill variable with submitted values
    $bill = [
        'id' => $id,
        'bill_title' => $bill_title,
        'flat_id' => $flat_id,
        'amount' => $amount,
        'month' => $month
    ];
}

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Bill Data</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>bills.php">Bills Management</a></li>
        <li class="breadcrumb-item active">Edit Bill Data</li>
    </ol>

    <div class="col-md-4">

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Edit Bill Data</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="bill_title">Bill Title</label>
                        <input type="text" id="bill_title" name="bill_title" class="form-control"
                               value="<?php echo isset($bill['bill_title']) ? htmlspecialchars($bill['bill_title']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="flat_id">Flat Number</label>
                        <select name="flat_id" id="flat_id" class="form-control">
                            <option value="">Select Flat Number</option>
                            <?php foreach ($flats as $flat): ?>
                                <option value="<?php echo $flat['id']; ?>"
                                    <?php echo (isset($bill['flat_id']) && $bill['flat_id'] == $flat['id']) ? 'selected' : ''; ?>>
                                    <?php echo $flat['block_number'] . ' - ' . $flat['flat_number']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="amount">Amount</label>
                        <input type="number" step="0.01" id="amount" name="amount" class="form-control"
                               value="<?php echo isset($bill['amount']) ? htmlspecialchars($bill['amount']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="month">Month</label>
                        <input type="month" id="month" name="month" class="form-control"
                               value="<?php echo isset($bill['month']) ? htmlspecialchars($bill['month']) : ''; ?>">
                    </div>

                    <input type="hidden" name="id" value="<?php echo isset($bill['id']) ? $bill['id'] : ''; ?>">

                    <button type="submit" name="edit_bill" class="btn btn-primary">Update Bill</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>
