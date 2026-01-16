<?php

require_once __DIR__ . '/../../core/config.php';

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: logout.php');
    exit();
}

$errors = [];

if (isset($_POST['add_bill'])) {

    // Get and sanitize input
    $bill_title = trim($_POST['bill_title']);
    $flat_id = $_POST['flat_id'];
    $amount = $_POST['amount'];
    $month = $_POST['month'];

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

    // If valid, insert into database
    if (empty($errors)) {

        // Insert bill
        $stmt = $pdo->prepare("INSERT INTO bills (flat_id, bill_title, amount, month) VALUES (?, ?, ?, ?)");
        $stmt->execute([$flat_id, $bill_title, $amount, $month]);

        $bill_id = $pdo->lastInsertId();

        // Get the user assigned to this flat
        $stmt = $pdo->prepare("SELECT user_id FROM allotments WHERE flat_id = ? LIMIT 1");
        $stmt->execute([$flat_id]);
        $user_id = $stmt->fetchColumn();

        // Add notification
        if ($user_id) {
            $message = "New bill added. Amount: $amount, Month: $month";
            $notification_link = 'bill_payment.php?id=' . $bill_id . '&action=notification';

            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, notiification_type, event_id, message, link) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, 'Bill', $bill_id, $message, $notification_link]);
        }

        $_SESSION['success'] = 'New Bill Data Added';
        header('Location: bills.php');
        exit();
    }
}

// Fetch flats
$stmt = $pdo->query("SELECT id, flat_number, block_number FROM flats ORDER BY id DESC");
$flats = $stmt->fetchAll(PDO::FETCH_ASSOC);

include(__DIR__ . '/../../resources/layout/header.php');
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Add Bill Data</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>bills.php">Bills Management</a></li>
        <li class="breadcrumb-item active">Add Bill Data</li>
    </ol>

    <div class="col-md-4">

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Add Bill Data</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="bill_title">Bill Title</label>
                        <input type="text" id="bill_title" name="bill_title" class="form-control" 
                               value="<?php echo isset($bill_title) ? htmlspecialchars($bill_title) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="flat_id">Flat Number</label>
                        <select id="flat_id" name="flat_id" class="form-control">
                            <option value="">Select Flat Number</option>
                            <?php foreach ($flats as $flat): ?>
                                <option value="<?php echo $flat['id']; ?>" 
                                    <?php echo (isset($flat_id) && $flat_id == $flat['id']) ? 'selected' : ''; ?>>
                                    <?php echo $flat['block_number'] . ' - ' . $flat['flat_number']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="amount">Amount</label>
                        <input type="number" step="0.01" id="amount" name="amount" class="form-control" 
                               value="<?php echo isset($amount) ? htmlspecialchars($amount) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="month">Month</label>
                        <input type="month" id="month" name="month" class="form-control" 
                               value="<?php echo isset($month) ? htmlspecialchars($month) : ''; ?>">
                    </div>

                    <button type="submit" name="add_bill" class="btn btn-primary">Add Bill</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../../resources/layout/footer.php'); ?>
