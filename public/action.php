<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';


if (isset($_POST['action'])) {

	if ($_POST['action'] == 'fetch_users') {
		// Columns to fetch
		$columns = ['id', 'name', 'email', 'role', 'mobile', 'dob', 'gender', 'created_at'];

		$table = 'users';
		$query = "SELECT " . implode(",", $columns) . " FROM $table";

		// Total records
		$count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();

		// Filter
		$filterQuery = '';
		if (!empty($_POST['search']['value'])) {
			$search = $_POST['search']['value'];
			$filterQuery = " WHERE (name LIKE '%$search%' OR email LIKE '%$search%' OR role LIKE '%$search%' OR mobile LIKE '%$search%' OR dob LIKE '%$search%' OR gender LIKE '%$search%')";
		}
		$query .= $filterQuery;

		// Count filtered
		$countFiltered = $pdo->query($query)->rowCount();

		// Ordering
		$orderColumn = $columns[$_POST['order'][0]['column']];
		$orderDir = $_POST['order'][0]['dir'];
		$query .= " ORDER BY $orderColumn $orderDir";

		// Pagination
		$start = $_POST['start'];
		$length = $_POST['length'];
		$query .= " LIMIT $start, $length";

		// Execute
		$stmt = $pdo->query($query);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Format DOB (optional)
		foreach ($results as &$row) {
			$row['dob'] = !empty($row['dob']) ? date('d/m/Y', strtotime($row['dob'])) : '';
		}

		// Return JSON
		echo json_encode([
			"draw" => intval($_POST['draw']),
			"recordsTotal" => intval($count),
			"recordsFiltered" => intval($countFiltered),
			"data" => $results
		]);
		exit;
	}

	if ($_POST['action'] === 'get_user') {

		$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
		$stmt->execute([$_POST['id']]);
		echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
		exit;
	}

	if ($_POST['action'] === 'update_user') {

		$errors = [];

		$id       = (int)($_POST['id'] ?? 0);
		$name     = trim($_POST['user_name'] ?? '');
		$email    = trim($_POST['user_email'] ?? '');
		$password = $_POST['user_password'] ?? '';
		$mobile   = trim($_POST['user_mobile'] ?? '');
		$dob      = $_POST['user_dob'] ?? '';
		$gender   = $_POST['user_gender'] ?? '';
		$role     = $_POST['user_role'] ?? '';

		// ===== VALIDATION =====
		if ($name === '') {
			$errors['name'] = 'Please enter name';
		}

		if ($email === '') {
			$errors['email'] = 'Please enter email';
		} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors['email'] = 'Invalid email address';
		}

		if ($mobile === '') {
			$errors['mobile'] = 'Please enter mobile';
		} elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
			$errors['mobile'] = 'Mobile must be 10 digits';
		}

		if ($dob === '') {
			$errors['dob'] = 'Please select date of birth';
		}

		if (!in_array($gender, ['Male', 'Female', 'Other'])) {
			$errors['gender'] = 'Please select gender';
		}

		if (!in_array($role, ['admin', 'cashier', 'user'])) {
			$errors['role'] = 'Please select role';
		}

		// ===== CHECK USER EXISTS =====
		if (empty($errors)) {
			$checkUser = $pdo->prepare("SELECT id FROM users WHERE id=?");
			$checkUser->execute([$id]);

			if (!$checkUser->fetch()) {
				echo json_encode([
					'status' => 'error',
					'message' => 'User not found'
				]);
				exit;
			}
		}

		// ===== DUPLICATE CHECK (EXCLUDE CURRENT USER) =====
		if (empty($errors)) {
			$check = $pdo->prepare("SELECT id, email, mobile FROM users WHERE (email=? OR mobile=?) AND id != ?");
			$check->execute([$email, $mobile, $id]);
			$existing = $check->fetch(PDO::FETCH_ASSOC);

			if ($existing) {
				if ($existing['email'] === $email) {
					$errors['email'] = 'Email already exists';
				}
				if ($existing['mobile'] === $mobile) {
					$errors['mobile'] = 'Mobile already exists';
				}
			}
		}

		// ===== RETURN VALIDATION ERRORS =====
		if (!empty($errors)) {
			echo json_encode([
				'status' => 'validation_error',
				'errors' => $errors
			]);
			exit;
		}

		// ===== UPDATE (PASSWORD OPTIONAL) =====
		if ($password === '') {

			$stmt = $pdo->prepare("
            UPDATE users 
            SET name=?, email=?, mobile=?, dob=?, gender=?, role=? 
            WHERE id=?
        ");

			$success = $stmt->execute([$name, $email, $mobile, $dob, $gender, $role, $id]);
		} else {

			$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

			$stmt = $pdo->prepare("
            UPDATE users 
            SET name=?, email=?, password=?, mobile=?, dob=?, gender=?, role=? 
            WHERE id=?
        ");

			$success = $stmt->execute([$name, $email, $hashedPassword, $mobile, $dob, $gender, $role, $id]);
		}

		if ($success) {

			flash_set('success', 'User updated successfully');

			echo json_encode([
				'status' => 'success'
			]);
		} else {

			echo json_encode([
				'status' => 'error',
				'message' => 'Database error! User not updated'
			]);
		}
		exit;
	}

	if ($_POST['action'] == 'fetch_flats') {
		// Define the columns that should be returned in the response
		$columns = array(
			'id',
			'flat_number',
			'floor',
			'block_number',
			'flat_type',
			'created_at'
		);

		// Define the table name and the primary key column
		$table = 'flats';
		$primaryKey = 'id';

		// Define the base query
		$query = "SELECT " . implode(", ", $columns) . " FROM $table";

		// Get the total number of records
		$count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();

		// Define the filter query
		$filterQuery = '';
		if (!empty($_POST['search']['value'])) {
			$search = $_POST['search']['value'];

			$filterQuery = " WHERE (
				flat_number LIKE '%$search%' 
				OR floor LIKE '%$search%' 
				OR block_number LIKE '%$search%' 
				OR flat_type LIKE '%$search%'
			)";
		}

		// Add the filter query to the base query
		$query .= $filterQuery;

		// Get the number of filtered records
		$countFiltered = $pdo->query($query)->rowCount();

		// Add sorting to the query
		$orderColumn = $columns[$_POST['order'][0]['column']];
		$orderDirection = $_POST['order'][0]['dir'];
		$query .= " ORDER BY $orderColumn $orderDirection";

		// Add pagination to the query
		$start = $_POST['start'];
		$length = $_POST['length'];
		$query .= " LIMIT $start, $length";

		// Execute the query and fetch the results
		$stmt = $pdo->query($query);
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Build the response
		$response = array(
			"draw" => intval($_REQUEST['draw']),
			"recordsTotal" => intval($count),
			"recordsFiltered" => intval($countFiltered),
			"data" => $results
		);

		// Convert the response to JSON and output it
		echo json_encode($response);
	}

	if ($_POST['action'] === 'get_flat') {

		$id = (int) $_POST['id'];

		$stmt = $pdo->prepare("SELECT * FROM flats WHERE id=?");
		$stmt->execute([$id]);

		echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
		exit();
	}

	if ($_POST['action'] === 'update_flat') {

		$id           = (int) $_POST['id'];
		$floor        = trim($_POST['floor'] ?? '');
		$flat_number  = trim($_POST['flat_number'] ?? '');
		$block_number = trim($_POST['block_number'] ?? '');
		$flat_type    = trim($_POST['flat_type'] ?? '');

		$errors = [];

		// VALIDATION
		if ($floor === '' || !is_numeric($floor)) {
			$errors[] = "Valid floor required";
		}

		if ($flat_number === '') {
			$errors[] = "Flat number required";
		}

		if ($block_number === '') {
			$errors[] = "Block required";
		}

		if ($flat_type === '') {
			$errors[] = "Flat type required";
		}

		// DUPLICATE CHECK
		if (empty($errors)) {
			$check = $pdo->prepare("
            SELECT id FROM flats 
            WHERE floor = ? AND flat_number = ? AND block_number = ? AND id != ?
        ");
			$check->execute([$floor, $flat_number, $block_number, $id]);

			if ($check->fetch()) {
				$errors[] = "This flat already exists!";
			}
		}

		if (!empty($errors)) {
			echo json_encode([
				'status' => 'error',
				'message' => implode(', ', $errors)
			]);
			exit();
		}

		// UPDATE
		$stmt = $pdo->prepare("
        UPDATE flats 
        SET flat_number=?, floor=?, block_number=?, flat_type=?
        WHERE id=?
     ");

		$updated = $stmt->execute([$flat_number, $floor, $block_number, $flat_type, $id]);

		if ($updated) {

			flash_set('success', 'Flat updated successfully');

			echo json_encode([
				'status' => 'success'
			]);
		} else {
			flash_set('err', 'Database error! Flat not updated');
			echo json_encode([
				'status' => 'error'
			]);
		}
		exit;
	}

	if ($_POST['action'] == 'fetch_allotments') {

		$columns = array(
			'allotments.id',
			'users.name',
			'flats.flat_number',
			'flats.block_number',
			'flats.flat_type',
			'maintenance_rates.rate',
			'allotments.move_in_date',
			'allotments.created_at'
		);

		// ================= BASE QUERY =================
		$query = "
		SELECT " . implode(", ", $columns) . "
		FROM allotments
		INNER JOIN flats ON allotments.flat_id = flats.id
		INNER JOIN users ON allotments.user_id = users.id
		LEFT JOIN maintenance_rates 
			ON flats.flat_type = maintenance_rates.flat_type
		";

		// ================= FILTERING =================
		$filterQuery = '';
		if (!empty($_POST['search']['value'])) {
			$search = $_POST['search']['value'];
			$filterQuery = "
			WHERE (
				flats.flat_number LIKE :search 
				OR users.name LIKE :search
			)
		";
		}

		// ================= TOTAL RECORDS =================
		$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM allotments");
		$stmtTotal->execute();
		$recordsTotal = $stmtTotal->fetchColumn();

		// ================= FILTERED RECORDS =================
		if ($filterQuery) {
			$stmtFiltered = $pdo->prepare("
			SELECT COUNT(*) 
			FROM allotments
			INNER JOIN flats ON allotments.flat_id = flats.id
			INNER JOIN users ON allotments.user_id = users.id
			LEFT JOIN maintenance_rates 
				ON flats.flat_type = maintenance_rates.flat_type
			$filterQuery
		");
			$stmtFiltered->execute([':search' => "%$search%"]);
			$recordsFiltered = $stmtFiltered->fetchColumn();
		} else {
			$recordsFiltered = $recordsTotal;
		}

		// ================= ORDERING =================
		$orderColumnIndex = $_POST['order'][0]['column'];
		$orderColumn = $columns[$orderColumnIndex];
		$orderDir = $_POST['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';

		$query .= $filterQuery;
		$query .= " ORDER BY $orderColumn $orderDir";

		// ================= PAGINATION =================
		$start  = (int) $_POST['start'];
		$length = (int) $_POST['length'];
		$query .= " LIMIT $start, $length";

		// ================= FETCH DATA =================
		$stmt = $pdo->prepare($query);
		if ($filterQuery) {
			$stmt->execute([':search' => "%$search%"]);
		} else {
			$stmt->execute();
		}

		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// ================= RESPONSE =================
		echo json_encode([
			"draw" => intval($_POST['draw']),
			"recordsTotal" => intval($recordsTotal),
			"recordsFiltered" => intval($recordsFiltered),
			"data" => $data
		]);
		exit();
	}

	if (isset($_POST['action']) && $_POST['action'] === 'fetch_maintenance_rates') {

		$columns = ['id', 'flat_type', 'rate', 'overdue_fine', 'created_at'];

		$limit  = (int) $_POST['length'];
		$start  = (int) $_POST['start'];
		$order  = $columns[$_POST['order'][0]['column']];
		$dir    = $_POST['order'][0]['dir'];
		$search = $_POST['search']['value'];

		// Total records
		$stmt = $pdo->query("SELECT COUNT(*) FROM maintenance_rates");
		$recordsTotal = $stmt->fetchColumn();

		$where = '';
		$params = [];

		if (!empty($search)) {
			$where = " WHERE flat_type LIKE :search ";
			$params[':search'] = "%$search%";
		}

		// Filtered count
		$countSql = "SELECT COUNT(*) FROM maintenance_rates $where";
		$stmt = $pdo->prepare($countSql);
		$stmt->execute($params);
		$recordsFiltered = $stmt->fetchColumn();

		// Data query
		$sql = "
        SELECT id, flat_type, rate, overdue_fine, created_at
        FROM maintenance_rates
        $where
        ORDER BY $order $dir
        LIMIT :start, :limit
     ";

		$stmt = $pdo->prepare($sql);

		foreach ($params as $key => $value) {
			$stmt->bindValue($key, $value);
		}

		$stmt->bindValue(':start', $start, PDO::PARAM_INT);
		$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		echo json_encode([
			"draw" => intval($_POST['draw']),
			"recordsTotal" => $recordsTotal,
			"recordsFiltered" => $recordsFiltered,
			"data" => $data,
			'status' => 'success'
		]);
		exit;
	}

	if (isset($_POST['action']) && $_POST['action'] === 'maintenanceBillRecords') {

		$columns = array(
			'allotments.id',
			'flats.flat_number',
			'flats.block_number',
			'users.name',
			'flats.flat_type'
		);

		/* ================= BASE QUERY ================= */
		$query = "
		SELECT
			allotments.id,
			flats.flat_number,
			flats.block_number,
			users.name,
			flats.flat_type
		FROM allotments
		INNER JOIN flats ON allotments.flat_id = flats.id
		INNER JOIN users ON allotments.user_id = users.id
		";

		/* ================= SEARCH FILTER ================= */
		$filterQuery = '';
		if (!empty($_POST['search']['value'])) {
			$search = $_POST['search']['value'];
			$filterQuery = "
			WHERE (
				flats.flat_number LIKE :search OR
				flats.block_number LIKE :search OR
				users.name LIKE :search OR
				flats.flat_type LIKE :search
			)
			";
		}

		/* ================= TOTAL RECORDS ================= */
		$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM allotments");
		$stmtTotal->execute();
		$recordsTotal = $stmtTotal->fetchColumn();

		/* ================= FILTERED RECORDS ================= */
		if ($filterQuery) {
			$stmtFiltered = $pdo->prepare("
			SELECT COUNT(*)
			FROM allotments
			INNER JOIN flats ON allotments.flat_id = flats.id
			INNER JOIN users ON allotments.user_id = users.id
			$filterQuery
			");
			$stmtFiltered->execute([':search' => "%$search%"]);
			$recordsFiltered = $stmtFiltered->fetchColumn();
		} else {
			$recordsFiltered = $recordsTotal;
		}

		/* ================= ORDERING ================= */
		$orderColumnIndex = $_POST['order'][0]['column'];
		$orderColumn = $columns[$orderColumnIndex];
		$orderDir = $_POST['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';

		$query .= $filterQuery;
		$query .= " ORDER BY $orderColumn $orderDir";

		/* ================= PAGINATION ================= */
		$start  = (int)$_POST['start'];
		$length = (int)$_POST['length'];
		$query .= " LIMIT $start, $length";

		/* ================= FETCH DATA ================= */
		$stmt = $pdo->prepare($query);

		if ($filterQuery) {
			$stmt->execute([':search' => "%$search%"]);
		} else {
			$stmt->execute();
		}

		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		/* ================= RESPONSE ================= */
		echo json_encode([
			"draw"            => intval($_POST['draw']),
			"recordsTotal"    => intval($recordsTotal),
			"recordsFiltered" => intval($recordsFiltered),
			"data"            => $data
		]);
		exit();
	}
}




//	MARK MAINTENANCE BILL AS CASH PAID 
if (isset($_GET['action'], $_GET['maintenance_bill_id']) && $_GET['action'] === 'mark_cash_payment' && ctype_digit($_GET['maintenance_bill_id'])) {

	// Admin access check
	requireRole(['admin', 'cashier']);

	$billId = $_GET['maintenance_bill_id'];

	// Fetch bill
	$stmt = $pdo->prepare("
        SELECT id, total_amount, status
        FROM maintenance_bills
        WHERE id = ?
    ");
	$stmt->execute([$billId]);
	$bill = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$bill) {
		die('Bill not found');
	}

	if ($bill['status'] === 'paid') {
		// $_SESSION['success'] = 'Bill already paid';
		flash_set('success', 'Bill already paid');
		header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . 'dashboard.php'));
		exit();
	}


	try {
		$pdo->beginTransaction();

		$stmt = $pdo->prepare("
			INSERT INTO maintenance_payments
			(maintenance_bill_id, payment_mode, paid_on, created_at)
			VALUES (?, 'cash', NOW(), NOW())
		");
		$stmt->execute([$billId]);


		// Update bill status
		$stmt = $pdo->prepare("
            UPDATE maintenance_bills
            SET status = 'paid'
            WHERE id = ?
        ");
		$stmt->execute([$billId]);

		$pdo->commit();

		// $_SESSION['success'] = 'Cash payment marked successfully';
		flash_set('success', 'Cash payment marked successfully');
	} catch (Exception $e) {
		$pdo->rollBack();
		flash_set('err', 'Database error! Cash payment marked failed.');
		// die('Payment failed: ' . $e->getMessage());
	}

	header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . 'dashboard.php'));
	exit();
}

// MARK MAINTENANCE BILL AS ONLINE PAID
if (isset($_POST['action']) && $_POST['action'] === 'mark_online_payment') {

	requireRole(['admin', 'cashier']);

	$billId        = $_POST['bill_id'] ?? '';
	$paymentMode   = 'online';
	$paymentMethod = $_POST['payment_method'] ?? '';
	$note          = trim($_POST['note'] ?? '');

	// ===== VALIDATION =====
	$errors = [];

	if (!ctype_digit($billId)) {
		$errors['bill_id'] = "Invalid Bill ID";
	}
	if ($paymentMethod === '') {
		$errors['payment_method'] = "Payment method is required";
	}
	if (empty($note)) {
		$errors['note'] = "Note is required";
	}
	if (!isset($_FILES['proof']) || $_FILES['proof']['error'] != UPLOAD_ERR_OK) {
		$errors['proof'] = "Payment proof is required";
	}

	if (!empty($errors)) {
		echo json_encode(['status' => 'error', 'errors' => $errors]);
		exit;
	}

	/* ===== FETCH BILL + FLAT DETAILS ===== */
	$stmt = $pdo->prepare("
        SELECT 
            mb.bill_month,
            mb.bill_year,
            f.flat_number,
            f.block_number
        FROM maintenance_bills mb
        JOIN flats f ON f.id = mb.flat_id
        WHERE mb.id = ?
    ");
	$stmt->execute([$billId]);
	$bill = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$bill) {
		http_response_code(404);
		exit('Bill not found');
	}

	/* ===== FILE VALIDATION ===== */
	if ($_FILES['proof']['error'] !== UPLOAD_ERR_OK) {
		http_response_code(400);
		exit('File upload error');
	}

	$ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
	$allowed = ['jpg', 'jpeg', 'png', 'pdf'];

	if (!in_array($ext, $allowed)) {
		http_response_code(400);
		exit('Invalid file type');
	}

	/* ===== FILE RENAME ===== */
	$monthName = date('F', mktime(0, 0, 0, $bill['bill_month'], 1));
	$year      = $bill['bill_year'];
	$flatNo    = preg_replace('/\s+/', '', $bill['flat_number']);
	$blockNo   = preg_replace('/\s+/', '', $bill['block_number']);

	$newFileName = "{$monthName}-{$year}_{$blockNo}-{$flatNo}_{$billId}.{$ext}";
	$uploadDir   = __DIR__ . "/uploads/payment_proofs/";
	$uploadPath  = $uploadDir . $newFileName;

	if (!is_dir($uploadDir)) {
		mkdir($uploadDir, 0777, true);
	}

	if (!move_uploaded_file($_FILES['proof']['tmp_name'], $uploadPath)) {
		http_response_code(500);
		exit('File move failed');
	}

	/* ===== SAVE PAYMENT ===== */
	try {
		$pdo->beginTransaction();

		// INSERT PAYMENT
		$stmt = $pdo->prepare("
            INSERT INTO maintenance_payments
            (
                maintenance_bill_id,
                payment_mode,
                payment_method,
                proof,
                note,
                paid_on,
                created_at
            )
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");

		$stmt->execute([$billId, $paymentMode, $paymentMethod, $newFileName, $note]);

		// UPDATE BILL STATUS
		$stmt = $pdo->prepare("
            UPDATE maintenance_bills
            SET status = 'paid'
            WHERE id = ?
        ");
		$stmt->execute([$billId]);

		$pdo->commit();
		echo json_encode([
			'status' => 'success',
			'message' => 'Online payment marked successfully'
		]);
		// echo 'success';
		flash_set('success', 'Online payment marked successfully');
		exit;
	} catch (Exception $e) {
		$pdo->rollBack();

		echo json_encode([
			'status' => 'error',
			'errors' => [
				'general' => 'Database error! Online payment failed.'
			]
		]);
		flash_set('err', 'Database error! Online payment marked failed.');
		exit;
	}


	exit();
}



// FETCH USER BILLS
if (isset($_POST['action']) && $_POST['action'] === 'fetch_user_bills') {

	header('Content-Type: application/json');
	ini_set('display_errors', 0);

	$draw   = intval($_POST['draw'] ?? 0);
	$start  = intval($_POST['start'] ?? 0);
	$length = intval($_POST['length'] ?? 10);

	$allotmentId = $_POST['allotment_id'] ?? '';

	if (!ctype_digit($allotmentId)) {
		echo json_encode([
			"draw" => $draw,
			"recordsTotal" => 0,
			"recordsFiltered" => 0,
			"data" => []
		]);
		exit;
	}

	/* ===== GET USER + FLAT ===== */
	$stmt = $pdo->prepare("SELECT user_id, flat_id FROM allotments WHERE id = ?");
	$stmt->execute([$allotmentId]);
	$allotment = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$allotment) {
		echo json_encode([
			"draw" => $draw,
			"recordsTotal" => 0,
			"recordsFiltered" => 0,
			"data" => []
		]);
		exit;
	}

	$userId = $allotment['user_id'];
	$flatId = $allotment['flat_id'];

	/* ===== SEARCH ===== */
	$search = trim($_POST['search']['value'] ?? '');
	$searchSql = '';
	$params = [
		':user_id' => $userId,
		':flat_id' => $flatId
	];

	if ($search !== '') {
		$searchSql = " AND (
        mb.status LIKE :search
        OR mb.bill_year LIKE :search
        OR CONCAT(
            MONTHNAME(
                STR_TO_DATE(
                    CONCAT(mb.bill_year, '-', mb.bill_month, '-01'),
                    '%Y-%m-%d'
                )
            ),
            ' ',
            mb.bill_year
        ) LIKE :search
        OR mp.payment_mode LIKE :search
		OR mp.payment_method LIKE :search
        OR mb.amount LIKE :search
        OR mb.total_amount LIKE :search
        OR DATE_FORMAT(mp.paid_on, '%d-%m-%Y') LIKE :search
    )";

		$params[':search'] = "%$search%";
	}

	/* ===== TOTAL RECORDS ===== */
	$stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM maintenance_bills mb
        WHERE mb.user_id = :user_id 
          AND mb.flat_id = :flat_id
    ");
	$stmt->execute([
		':user_id' => $userId,
		':flat_id' => $flatId
	]);
	$recordsTotal = $stmt->fetchColumn();

	/* ===== FILTERED RECORDS (JOIN REQUIRED) ===== */
	$stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM maintenance_bills mb
        LEFT JOIN maintenance_payments mp 
            ON mp.maintenance_bill_id = mb.id
        WHERE mb.user_id = :user_id 
          AND mb.flat_id = :flat_id
          $searchSql
    ");
	$stmt->execute($params);
	$recordsFiltered = $stmt->fetchColumn();

	/* ===== ORDERING ===== */
	$orderMap = [
		0 => 'mb.bill_year',
		1 => 'mb.amount',
		2 => 'mb.fine_amount',
		3 => 'mb.total_amount',
		4 => 'mb.status',
		5 => 'mp.payment_mode',
		6 => 'mp.paid_on'
	];

	$orderCol = $orderMap[$_POST['order'][0]['column'] ?? 0] ?? 'mb.bill_year';
	$orderDir = ($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

	/* ===== DATA QUERY ===== */
	$stmt = $pdo->prepare("
			SELECT 
		mb.*,
		mp.payment_mode,
		mp.payment_method,
		mp.paid_on
	FROM maintenance_bills mb
	LEFT JOIN maintenance_payments mp 
		ON mp.maintenance_bill_id = mb.id
        WHERE mb.user_id = :user_id 
          AND mb.flat_id = :flat_id
          $searchSql
        ORDER BY $orderCol $orderDir
        LIMIT :start, :length
    ");

	foreach ($params as $k => $v) {
		$stmt->bindValue($k, $v);
	}
	$stmt->bindValue(':start', $start, PDO::PARAM_INT);
	$stmt->bindValue(':length', $length, PDO::PARAM_INT);

	$stmt->execute();
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	/* ===== FORMAT DATA ===== */
	$data = [];

	foreach ($rows as $r) {

		$monthName = date('F', mktime(0, 0, 0, $r['bill_month'], 1));

		$paymentText = '-';

		if ($r['payment_mode'] === 'online') {
			$paymentText = 'Online (' . strtoupper(str_replace('_', ' ', $r['payment_method'])) . ')';
		} elseif ($r['payment_mode']) {
			$paymentText = ucfirst($r['payment_mode']);
		}

		if ($r['status'] === 'paid') {
			$actionBtn = '
            <a href="' . BASE_URL . 'view/pay_Details.php?bill_id=' . $r['id'] . '" 
               class="btn btn-sm btn-brand"
				style="font-size: 10px;
				    background-color: #4F47E5;
					color: white;
					border-radius: 10px;
					font-weight: 600;
					padding: 0.6rem 1.5rem;
					border: none;
					transition: 0.2s;">
                <i class="fa fa-eye me-1"></i> View
        	</a>';
		} elseif (in_array($_SESSION['user_role'], ['admin', 'cashier'])) {
			$actionBtn = '
            <select 
			class="form-select form-select-sm payment-type"
			data-bill="' . $r['id'] . '"
			style="
				min-width:130px;
				border-radius:12px;
				border:1px solid #e5e7eb;
				padding:6px 12px;
				font-size:13px;
				font-weight:600;
				background-color:#f8f9fa;
				box-shadow:0 2px 6px rgba(0,0,0,0.05);
				transition:all 0.2s ease;
				cursor:pointer;
			">
			<option value="">💳 Payment</option>
			<option value="cash">💵 Cash</option>
			<option value="online">🌐 Online</option>
		</select>';
		} else {
			$actionBtn = '<span class="text-muted">-</span>';
		}

		$data[] = [
			'month_year'   => $monthName . ' ' . $r['bill_year'],
			'amount'       => '₹' . number_format($r['amount'], 2),
			'fine'         => '₹' . number_format($r['fine_amount'], 2),
			'total'        => '<strong>₹' . number_format($r['total_amount'], 2) . '</strong>',
			'status'       => '<span class="badge bg-' . ($r['status'] === 'paid' ? 'success' : 'warning') . '">' . ucfirst($r['status']) . '</span>',
			'payment_mode' => $paymentText,
			'paid_on'      => $r['paid_on'] ? date('d-m-Y H:i', strtotime($r['paid_on'])) : '-',
			'overdue'      => $r['status'] === 'overdue' ? 'Yes' : 'No',
			'action'       => $actionBtn
		];
	}


	echo json_encode([
		"draw" => $draw,
		"recordsTotal" => intval($recordsTotal),
		"recordsFiltered" => intval($recordsFiltered),
		"data" => $data
	]);
	exit;
}



//	VIEW FOR USER PAY AND VIEW
if (isset($_POST['action']) && $_POST['action'] === 'fetch_user_bills_user') {

	header('Content-Type: application/json');

	$userId = $_SESSION['user_id'];

	// Get user's latest allotment
	$stmt = $pdo->prepare("
        SELECT a.flat_id, f.flat_number, f.block_number, f.flat_type
        FROM allotments a
        JOIN flats f ON a.flat_id = f.id
        WHERE a.user_id = ?
        ORDER BY a.id DESC
        LIMIT 1
    ");
	$stmt->execute([$userId]);
	$userAllotment = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$userAllotment) {
		echo json_encode([
			"draw" => intval($_POST['draw']),
			"recordsTotal" => 0,
			"recordsFiltered" => 0,
			"data" => []
		]);
		exit;
	}

	$flatId = $userAllotment['flat_id'];
	$flatType = $userAllotment['flat_type'];

	// Fetch maintenance rate
	$rateStmt = $pdo->prepare("SELECT rate, overdue_fine FROM maintenance_rates WHERE flat_type = ?");
	$rateStmt->execute([$flatType]);
	$rateData = $rateStmt->fetch(PDO::FETCH_ASSOC);
	$baseAmount = $rateData['rate'] ?? 0;
	$overdueFineRate = $rateData['overdue_fine'] ?? 0;

	// Fetch all bills for this user/flat
	$stmt = $pdo->prepare("
        SELECT 
    mb.id AS maintenance_bill_id,
    mb.bill_month,
    mb.bill_year,
    mb.amount,
    mb.fine_amount,
    mb.status,
    mb.due_date,
    mp.payment_mode,
    mp.payment_method,
    mp.paid_on
        FROM maintenance_bills mb
        LEFT JOIN maintenance_payments mp ON mp.maintenance_bill_id = mb.id
        WHERE mb.user_id = ? AND mb.flat_id = ?
        ORDER BY mb.bill_year DESC, mb.bill_month DESC
    ");
	$stmt->execute([$userId, $flatId]);
	$bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Filter by search term in PHP
	$search = $_POST['search']['value'] ?? '';
	$filteredBills = [];
	foreach ($bills as $bill) {
		$monthName = date('F', mktime(0, 0, 0, $bill['bill_month'], 1));
		$bill['month_name'] = $monthName;

		$matchesSearch = empty($search) ||
			str_contains(strtolower($monthName), strtolower($search)) ||
			str_contains(strtolower($bill['bill_year']), strtolower($search)) ||
			str_contains(strtolower($bill['status']), strtolower($search)) ||
			str_contains(strtolower($bill['payment_mode'] ?? ''), strtolower($search)) ||
			str_contains(strtolower($bill['payment_method'] ?? ''), strtolower($search));


		if ($matchesSearch) {
			$filteredBills[] = $bill;
		}
	}

	$recordsTotal = count($bills);
	$recordsFiltered = count($filteredBills);

	// Pagination
	$start = (int)($_POST['start'] ?? 0);
	$length = (int)($_POST['length'] ?? 10);
	$pageData = array_slice($filteredBills, $start, $length);

	// Format for DataTables
	$data = [];
	foreach ($pageData as $bill) {
		$fineAmount = $bill['fine_amount'] ?? 0;
		$isOverdue = false;

		if ($bill['status'] !== 'paid') {
			$dueWithGrace = strtotime($bill['due_date'] . ' +7 days');
			if (time() > $dueWithGrace && $fineAmount == 0) {
				$fineAmount = $overdueFineRate;
				$isOverdue = true;
			}
		}

		$totalAmount = ($bill['amount'] ?? $baseAmount) + $fineAmount;

		$paymentText = '-';

		if ($bill['payment_mode'] === 'online') {
			$paymentText = 'Online (' . strtoupper(str_replace('_', ' ', $bill['payment_method'])) . ')';
		} elseif ($bill['payment_mode']) {
			$paymentText = ucfirst($bill['payment_mode']);
		}


		$data[] = [
			'month_year'   => $bill['month_name'] . ' ' . $bill['bill_year'],
			'amount'       => number_format($bill['amount'] ?? $baseAmount, 2),
			'fine'         => number_format($fineAmount, 2),
			'total'        => number_format($totalAmount, 2),
			'status' => match ($bill['status']) {
				'paid'    => '<span class="badge bg-success p-2">Paid</span>',
				'pending' => '<span class="badge bg-warning p-2">Pending</span>',
				'overdue' => '<span class="badge bg-danger p-2">Overdue</span>',
			},
			'payment_mode' => $paymentText,
			'paid_on'      => $bill['paid_on'] ? date('d-m-Y H:i', strtotime($bill['paid_on'])) : '-',
			'overdue'      => $isOverdue ? 'Yes' : 'No',
			// Find the 'action' key in your $data array and replace it:
			'action' => $bill['status'] === 'paid'
				? '<a href="pay_Details.php?bill_id=' . $bill['maintenance_bill_id'] . '" class="btn btn-sm btn-info text-white"> View Receipt</a>'
				: '<button class="btn btn-sm btn-primary pay-now-btn" 
							data-bill-id="' . $bill['maintenance_bill_id'] . '" 
							data-amount="' . ($totalAmount * 100) . '" 
							data-month="' . $bill['month_name'] . ' ' . $bill['bill_year'] . '">
							Pay Now
				</button>'
		];
	}

	echo json_encode([
		"draw" => intval($_POST['draw']),
		"recordsTotal" => $recordsTotal,
		"recordsFiltered" => $recordsFiltered,
		"data" => $data
	]);
	exit;
}

// Razorpay Payment Gateway
if (isset($_POST['action']) && $_POST['action'] === 'capture_payment') {
	header('Content-Type: application/json');

	$billId    = $_POST['bill_id'];
	$paymentId = $_POST['razorpay_payment_id'];
	$mode      = $_POST['payment_mode'] ?? 'online';
	$method    = $_POST['payment_method'] ?? 'upi'; // Defaulting to upi for now

	try {
		$pdo->beginTransaction();

		// 1. Update the bill status
		$stmt = $pdo->prepare("UPDATE maintenance_bills SET status = 'paid' WHERE id = ?");
		$stmt->execute([$billId]);

		// 2. Insert into maintenance_payments 
		// Note: I added razorpay_payment_id to the query based on your DB schema
		$stmt = $pdo->prepare("
            INSERT INTO maintenance_payments 
            (maintenance_bill_id, payment_mode, payment_method, razorpay_payment_id, paid_on) 
            VALUES (?, ?, ?, ?, NOW())
        ");
		$stmt->execute([$billId, $mode, $method, $paymentId]);

		$pdo->commit();
		echo json_encode(['status' => 'success']);
	} catch (Exception $e) {
		$pdo->rollBack();
		echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
	}
	exit;
}



// FETCH ALL MAINTENANCE BILLS 
if (isset($_POST['action']) && $_POST['action'] === 'fetch_all_bills') {

	header('Content-Type: application/json');

	$draw   = intval($_POST['draw'] ?? 0);
	$start  = intval($_POST['start'] ?? 0);
	$length = intval($_POST['length'] ?? 10);

	$month  = $_POST['month']  ?? '';
	$year   = $_POST['year']   ?? '';
	$status = $_POST['status'] ?? '';

	/* ================= FILTER CONDITIONS ================= */
	$where  = " WHERE 1=1 ";
	$params = [];

	if (ctype_digit($month)) {
		$where .= " AND mb.bill_month = :month ";
		$params[':month'] = $month;
	}

	if (ctype_digit($year)) {
		$where .= " AND mb.bill_year = :year ";
		$params[':year'] = $year;
	}

	if (in_array($status, ['paid', 'pending', 'overdue'])) {
		$where .= " AND mb.status = :status ";
		$params[':status'] = $status;
	}

	/* ================= TOTAL RECORDS ================= */
	$recordsTotal = $pdo->query("SELECT COUNT(*) FROM maintenance_bills")->fetchColumn();

	/* ================= FILTERED RECORDS ================= */
	$stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM maintenance_bills mb
        $where
    ");
	$stmt->execute($params);
	$recordsFiltered = $stmt->fetchColumn();

	/* ================= ORDERING ================= */
	$orderMap = [
		0 => 'mb.flat_id',
		1 => 'mb.flat_id',
		2 => 'mb.bill_year',
		3 => 'mb.amount',
		4 => 'mb.fine_amount',
		5 => 'mb.total_amount',
		6 => 'mb.status',
		7 => 'mb.id',
		8 => 'mb.id'
	];

	$orderCol = $orderMap[$_POST['order'][0]['column'] ?? 2] ?? 'mb.bill_year';
	$orderDir = ($_POST['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

	/* ================= DATA QUERY ================= */
	$stmt = $pdo->prepare("
        SELECT
            f.flat_number,
            f.block_number,
            mb.bill_month,
            mb.bill_year,
            mb.amount,
            mb.fine_amount,
            mb.total_amount,
            mb.status,
            mp.payment_mode,
            mp.payment_method,
            mp.paid_on
        FROM maintenance_bills mb
        JOIN flats f ON f.id = mb.flat_id
        LEFT JOIN maintenance_payments mp 
            ON mp.maintenance_bill_id = mb.id
        $where
        ORDER BY $orderCol $orderDir
        LIMIT :start, :length
    ");

	foreach ($params as $k => $v) {
		$stmt->bindValue($k, $v);
	}
	$stmt->bindValue(':start', $start, PDO::PARAM_INT);
	$stmt->bindValue(':length', $length, PDO::PARAM_INT);

	$stmt->execute();
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	/* ================= FORMAT DATA ================= */
	$data = [];

	foreach ($rows as $r) {

		$monthName = date('F', mktime(0, 0, 0, $r['bill_month'], 1));

		$paymentText = '-';
		if ($r['payment_mode'] === 'online') {
			$paymentText = 'Online (' . strtoupper(str_replace('_', ' ', $r['payment_method'])) . ')';
		} elseif ($r['payment_mode']) {
			$paymentText = ucfirst($r['payment_mode']);
		}

		$data[] = [
			'flat_number' => $r['flat_number'],
			'block_number' => $r['block_number'],
			'month_year'  => $monthName . ' ' . $r['bill_year'],
			'amount'      => '₹' . number_format($r['amount'], 2),
			'fine'        => '₹' . number_format($r['fine_amount'], 2),
			'total'       => '<strong>₹' . number_format($r['total_amount'], 2) . '</strong>',
			'status'      => '<span class="badge bg-' .
				($r['status'] === 'paid' ? 'success' : ($r['status'] === 'overdue' ? 'danger' : 'warning')) .
				'">' . ucfirst($r['status']) . '</span>',
			'payment_mode' => $paymentText,
			'paid_on'     => $r['paid_on'] ? date('d-m-Y H:i', strtotime($r['paid_on'])) : '-',
			'overdue'     => $r['status'] === 'overdue' ? 'Yes' : 'No'
		];
	}

	echo json_encode([
		"draw"            => $draw,
		"recordsTotal"    => intval($recordsTotal),
		"recordsFiltered" => intval($recordsFiltered),
		"data"            => $data
	]);
	exit;
}

// FETCH TOTALS FOR ALL MAINTENANCE BILLS 
if (isset($_POST['action']) && $_POST['action'] === 'fetch_bill_totals') {

	header('Content-Type: application/json');

	$month  = $_POST['month'] ?? '';
	$year   = $_POST['year'] ?? '';
	$status = $_POST['status'] ?? '';

	$where = " WHERE 1=1 ";
	$params = [];

	if (ctype_digit($month)) {
		$where .= " AND bill_month = :month ";
		$params[':month'] = $month;
	}

	if (ctype_digit($year)) {
		$where .= " AND bill_year = :year ";
		$params[':year'] = $year;
	}

	if (in_array($status, ['paid', 'pending', 'overdue'])) {
		$where .= " AND status = :status ";
		$params[':status'] = $status;
	}

	// Grand Total
	$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM maintenance_bills $where");
	$stmt->execute($params);
	$grandTotal = $stmt->fetchColumn() ?: 0;

	// Paid Total
	$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM maintenance_bills $where AND status='paid'");
	$stmt->execute($params);
	$paidTotal = $stmt->fetchColumn() ?: 0;

	// Pending + Overdue Total
	$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM maintenance_bills $where AND status IN ('pending','overdue')");
	$stmt->execute($params);
	$pendingTotal = $stmt->fetchColumn() ?: 0;

	echo json_encode([
		'grandTotal'   => number_format($grandTotal, 2),
		'paidTotal'    => number_format($paidTotal, 2),
		'pendingTotal' => number_format($pendingTotal, 2)
	]);
	exit;
}

// EXPORT ALL BILLS TO EXCEL (REAL EXCEL FORMAT)
if (isset($_GET['action']) && $_GET['action'] === 'export_all_maintenance_bills') {

	requireRole(['admin', 'cashier']);

	$month  = $_GET['month']  ?? '';
	$year   = $_GET['year']   ?? '';
	$status = $_GET['status'] ?? '';

	$where = [];
	$params = [];

	if (ctype_digit($month)) {
		$where[] = 'mb.bill_month = ?';
		$params[] = $month;
	}

	if (ctype_digit($year)) {
		$where[] = 'mb.bill_year = ?';
		$params[] = $year;
	}

	if (in_array($status, ['paid', 'pending', 'overdue'])) {
		$where[] = 'mb.status = ?';
		$params[] = $status;
	}

	$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

	/* ===== FETCH DATA (CORRECT - NO DUPLICATE PAYMENT BUG) ===== */
	$stmt = $pdo->prepare("
		SELECT 
			f.flat_number,
			f.block_number,
			CONCAT(
				MONTHNAME(STR_TO_DATE(CONCAT(mb.bill_year,'-',mb.bill_month,'-01'),'%Y-%m-%d')),
				' ',
				mb.bill_year
			) AS month_year,
			mb.amount,
			mb.fine_amount,
			mb.total_amount,
			mb.status,

			(SELECT mp2.payment_mode 
			 FROM maintenance_payments mp2 
			 WHERE mp2.maintenance_bill_id = mb.id 
			 ORDER BY mp2.id DESC LIMIT 1) AS payment_mode,

			(SELECT mp2.paid_on 
			 FROM maintenance_payments mp2 
			 WHERE mp2.maintenance_bill_id = mb.id 
			 ORDER BY mp2.id DESC LIMIT 1) AS paid_on,

			CASE 
				WHEN mb.status != 'paid' AND mb.due_date < CURDATE() THEN 'Yes'
				ELSE 'No'
			END AS overdue

		FROM maintenance_bills mb
		JOIN flats f ON f.id = mb.flat_id
		$whereSql
		ORDER BY mb.bill_year DESC, mb.bill_month DESC
	");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	/* ===== TOTALS ===== */
	$totalStmt = $pdo->prepare("
		SELECT 
			SUM(total_amount) AS grand_total,
			SUM(CASE WHEN status='paid' THEN total_amount ELSE 0 END) AS paid_total,
			SUM(CASE WHEN status IN ('pending','overdue') THEN total_amount ELSE 0 END) AS pending_total
		FROM maintenance_bills mb
		$whereSql
	");
	$totalStmt->execute($params);
	$totals = $totalStmt->fetch(PDO::FETCH_ASSOC);

	$grandTotal   = $totals['grand_total'] ?? 0;
	$paidTotal    = $totals['paid_total'] ?? 0;
	$pendingTotal = $totals['pending_total'] ?? 0;

	/* ===== FILTER TEXT ===== */
	$monthText  = ctype_digit($month) ? date('F', mktime(0, 0, 0, $month, 1)) : 'All Months';
	$yearText   = ctype_digit($year) ? $year : 'All Years';
	$statusText = $status ? ucfirst($status) : 'All Status';

	/* ===== EXCEL HEADERS ===== */
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=maintenance_collection_report.xls");
	header("Pragma: no-cache");
	header("Expires: 0");

	echo "
	<html>
	<head>
		<meta charset='UTF-8'>
	</head>
	<body>

	<table border='1' cellspacing='0' cellpadding='6'>

		<tr>
			<th colspan='10' style='font-size:18px; font-weight:bold; text-align:center;'>
				Society Maintenance Collection Report
			</th>
		</tr>

		<tr>
			<td colspan='10'><strong>Month:</strong> {$monthText}</td>
		</tr>
		<tr>
			<td colspan='10'><strong>Year:</strong> {$yearText}</td>
		</tr>
		<tr>
			<td colspan='10'><strong>Status:</strong> {$statusText}</td>
		</tr>
		<tr>
			<td colspan='10'><strong>Generated On:</strong> " . date('d-m-Y H:i') . "</td>
		</tr>

		<tr style='background:#343a40; color:#fff; font-weight:bold; text-align:center;'>
			<th>Flat</th>
			<th>Block</th>
			<th>Month / Year</th>
			<th>Amount (₹)</th>
			<th>Fine (₹)</th>
			<th>Total (₹)</th>
			<th>Status</th>
			<th>Payment Mode</th>
			<th>Paid On</th>
			<th>Overdue</th>
		</tr>
	";

	foreach ($rows as $row) {

		$paidOn = $row['paid_on'] ? date('d-m-Y H:i', strtotime($row['paid_on'])) : '-';
		$paymentMode = $row['payment_mode'] ? ucfirst($row['payment_mode']) : '-';

		echo "<tr>
			<td>{$row['flat_number']}</td>
			<td>{$row['block_number']}</td>
			<td>{$row['month_year']}</td>
			<td>" . number_format((float)$row['amount'], 2) . "</td>
			<td>" . number_format((float)$row['fine_amount'], 2) . "</td>
			<td><strong>" . number_format((float)$row['total_amount'], 2) . "</strong></td>
			<td>" . ucfirst($row['status']) . "</td>
			<td>{$paymentMode}</td>
			<td>{$paidOn}</td>
			<td>{$row['overdue']}</td>
		</tr>";
	}

	// SUMMARY SECTION
	echo "
		<tr><td colspan='10' style='background:#f2f2f2; font-weight:bold; text-align:center;'>SUMMARY</td></tr>
		<tr>
			<td colspan='5'><strong>Grand Total</strong></td>
			<td colspan='5'><strong>₹" . number_format($grandTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td colspan='5'><strong>Total Paid</strong></td>
			<td colspan='5' style='color:green;'><strong>₹" . number_format($paidTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td colspan='5'><strong>Total Pending/Overdue</strong></td>
			<td colspan='5' style='color:red;'><strong>₹" . number_format($pendingTotal, 2) . "</strong></td>
		</tr>

	</table>
	</body>
	</html>";
	exit;
}




// FETCH ELECTRICITY BILLS
if (isset($_POST['action']) && $_POST['action'] === 'fetch_electricity_bills') {

	header('Content-Type: application/json');

	$draw   = intval($_POST['draw'] ?? 0);
	$start  = intval($_POST['start'] ?? 0);
	$length = intval($_POST['length'] ?? 10);

	$recordsTotal = $pdo->query("SELECT COUNT(*) FROM electricity_bills")->fetchColumn();
	$recordsFiltered = $recordsTotal;

	$stmt = $pdo->prepare("
		SELECT
		id,
		month,
		year,
		reading,
		amount,
		paid_amount,
		bill_file,
		bill_receipt,
		(amount - paid_amount) AS pending,
		status,
		last_paid_on
		FROM electricity_bills
		WHERE 1
			" . (!empty($_POST['month']) ? " AND month = :month" : "") . "
			" . (!empty($_POST['year']) ? " AND year = :year" : "") . "
			" . (!empty($_POST['status']) ? " AND status = :status" : "") . "
		ORDER BY year DESC, month DESC
		LIMIT :start, :length
	");


	if (!empty($_POST['month'])) $stmt->bindValue(':month', (int)$_POST['month'], PDO::PARAM_INT);
	if (!empty($_POST['year'])) $stmt->bindValue(':year', (int)$_POST['year'], PDO::PARAM_INT);
	if (!empty($_POST['status'])) $stmt->bindValue(':status', $_POST['status'], PDO::PARAM_STR);


	$stmt->bindValue(':start', $start, PDO::PARAM_INT);
	$stmt->bindValue(':length', $length, PDO::PARAM_INT);
	$stmt->execute();

	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$data = [];

	foreach ($rows as $r) {

		$billBtn = $r['bill_file']
			? '<button class="btn btn-sm btn-info view-file" data-file="' . htmlspecialchars($r['bill_file']) . '">
            <i class="bi bi-eye"></i> View
       </button>'
			: '<span class="text-muted">No File</span>';

		$receiptBtn = $r['bill_receipt']
			? '<button class="btn btn-sm btn-secondary view-file" data-file="' . htmlspecialchars($r['bill_receipt']) . '">
            <i class="bi bi-eye"></i> View
       </button>'
			: '<span class="text-muted">No Receipt</span>';

		$data[] = [
			'month_year' => date('F', mktime(0, 0, 0, $r['month'], 1)) . ' ' . $r['year'],
			'reading'    => $r['reading'],
			'amount'     => '₹' . number_format($r['amount'], 2),
			'paid'       => '₹' . number_format($r['paid_amount'], 2),
			'pending'    => '₹' . number_format($r['pending'], 2),
			'status'     => '<span class="badge bg-' .
				($r['status'] == 'paid' ? 'success' : ($r['status'] == 'partial' ? 'info' : 'warning')) .
				'">' . ucfirst($r['status']) . '</span>',
			'bill_file'    => $billBtn,      // NEW COLUMN
			'receipt_file' => $receiptBtn,   // NEW COLUMN
			'last_paid'  => $r['last_paid_on'] ? date('d-M-Y h:i A', strtotime($r['last_paid_on'])) : '-',
			'action'     =>
			$r['status'] !== 'paid'
				? '<button class="btn btn-sm btn-primary pay-bill" data-id="' . $r['id'] . '">Pay</button>'
				: '<span class="text-muted">Paid</span>'
		];
	}

	echo json_encode([
		"draw" => $draw,
		"recordsTotal" => $recordsTotal,
		"recordsFiltered" => $recordsFiltered,
		"data" => $data
	]);
	exit;
}

// FETCH SINGLE ELECTRICITY BILL DETAILS
if (isset($_POST['action']) && $_POST['action'] == 'get_electricity_bill') {

	$id = (int) $_POST['id'];

	$stmt = $pdo->prepare("SELECT amount, paid_amount FROM electricity_bills WHERE id=?");
	$stmt->execute([$id]);
	$bill = $stmt->fetch(PDO::FETCH_ASSOC);

	if ($bill) {
		$bill['pending'] = $bill['amount'] - $bill['paid_amount'];
		echo json_encode($bill);
	}
	exit;
}

// PAY ELECTRICITY BILL (POPUP SUBMIT)
if (isset($_POST['action']) && $_POST['action'] === 'pay_electricity_bill') {

	requireRole(['admin', 'cashier']);

	$billId     = (int) $_POST['bill_id'];
	$paidAmount = (float) $_POST['paid_amount'];
	$mode       = $_POST['payment_mode'];

	$stmt = $pdo->prepare("
        SELECT amount, paid_amount
        FROM electricity_bills
        WHERE id = ?
    ");
	$stmt->execute([$billId]);
	$bill = $stmt->fetch(PDO::FETCH_ASSOC);

	if (!$bill) exit;

	$newPaid = $bill['paid_amount'] + $paidAmount;
	$status  = ($newPaid >= $bill['amount']) ? 'paid' : 'partial';

	$pdo->beginTransaction();

	// Payment history
	$pdo->prepare("
        INSERT INTO electricity_payments
        (electricity_bill_id, paid_amount, payment_mode, paid_on)
        VALUES (?, ?, ?, NOW())
    ")->execute([$billId, $paidAmount, $mode]);

	if ($newPaid > $bill['amount']) {
		echo "Invalid payment";
		exit;
	}

	// Update bill
	$pdo->prepare("
        UPDATE electricity_bills
        SET paid_amount = ?, status = ?, last_paid_on = NOW()
        WHERE id = ?
    ")->execute([$newPaid, $status, $billId]);

	$pdo->commit();
	exit;
}

// EXPORT ELECTRICITY BILLS (REAL EXCEL REPORT FORMAT)
if (isset($_GET['action']) && $_GET['action'] === 'electricity_bills_export_excel') {

	requireRole(['admin', 'cashier']);

	// Get filters
	$month  = $_GET['month'] ?? '';
	$year   = $_GET['year'] ?? '';
	$status = $_GET['status'] ?? '';

	$where = [];
	$params = [];

	if (ctype_digit($month)) {
		$where[] = 'month = :month';
		$params[':month'] = $month;
	}

	if (ctype_digit($year)) {
		$where[] = 'year = :year';
		$params[':year'] = $year;
	}

	if (in_array($status, ['paid', 'unpaid', 'partial'])) {
		$where[] = 'status = :status';
		$params[':status'] = $status;
	}

	$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

	/* ===== FETCH DATA ===== */
	$stmt = $pdo->prepare("
		SELECT 
			month,
			year,
			amount,
			paid_amount,
			(amount - paid_amount) AS pending,
			status,
			last_paid_on,
			created_at
		FROM electricity_bills
		$whereSql
		ORDER BY year DESC, month DESC
	");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	/* ===== TOTAL CALCULATIONS ===== */
	$grandTotal = 0;
	$totalPaid = 0;
	$totalPending = 0;

	foreach ($rows as $r) {
		$grandTotal += (float)$r['amount'];
		$totalPaid += (float)$r['paid_amount'];
		$totalPending += (float)$r['pending'];
	}

	/* ===== FILTER TEXT ===== */
	$monthText  = ctype_digit($month) ? date('F', mktime(0, 0, 0, $month, 1)) : 'All Months';
	$yearText   = ctype_digit($year) ? $year : 'All Years';
	$statusText = $status ? ucfirst($status) : 'All Status';

	/* ===== EXCEL HEADERS ===== */
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=electricity_bills_report.xls");
	header("Pragma: no-cache");
	header("Expires: 0");

	echo "
	<html>
	<head>
		<meta charset='UTF-8'>
	</head>
	<body>

	<table border='1' cellspacing='0' cellpadding='6'>

		<tr>
			<th colspan='7' style='font-size:18px; font-weight:bold; text-align:center;'>
				Electricity Bills Report
			</th>
		</tr>

		<tr>
			<td colspan='7'><strong>Month:</strong> {$monthText}</td>
		</tr>
		<tr>
			<td colspan='7'><strong>Year:</strong> {$yearText}</td>
		</tr>
		<tr>
			<td colspan='7'><strong>Status:</strong> {$statusText}</td>
		</tr>
		<tr>
			<td colspan='7'><strong>Generated On:</strong> " . date('d-m-Y H:i') . "</td>
		</tr>

		<tr style='background:#343a40; color:#fff; font-weight:bold; text-align:center;'>
			<th>Month / Year</th>
			<th>Total Amount (₹)</th>
			<th>Paid Amount (₹)</th>
			<th>Pending Amount (₹)</th>
			<th>Status</th>
			<th>Last Paid On</th>
			<th>Created Date</th>
		</tr>
	";

	foreach ($rows as $r) {

		$monthName = date('F', mktime(0, 0, 0, $r['month'], 1));
		$monthYear = $monthName . ' ' . $r['year'];

		$statusLabel = ucfirst($r['status']);
		$statusColor = ($r['status'] === 'paid') ? 'green' : (($r['status'] === 'partial') ? 'orange' : 'red');

		$lastPaid = $r['last_paid_on']
			? date('d-m-Y h:i A', strtotime($r['last_paid_on']))
			: '-';

		$createdDate = !empty($r['created_at'])
			? date('d-m-Y h:i A', strtotime($r['created_at']))
			: '-';


		echo "<tr>
			<td>{$monthYear}</td>
			<td><strong>₹" . number_format((float)$r['amount'], 2) . "</strong></td>
			<td style='color:green;'>₹" . number_format((float)$r['paid_amount'], 2) . "</td>
			<td style='color:red;'>₹" . number_format((float)$r['pending'], 2) . "</td>
			<td style='font-weight:bold; color:{$statusColor};'>{$statusLabel}</td>
			<td>{$lastPaid}</td>
			<td>{$createdDate}</td>
		</tr>";
	}

	/* ===== SUMMARY SECTION ===== */
	echo "
		<tr>
			<td colspan='7' style='background:#f2f2f2; font-weight:bold; text-align:center;'>
				SUMMARY
			</td>
		</tr>
		<tr>
			<td><strong>Grand Total</strong></td>
			<td colspan='6'><strong>₹" . number_format($grandTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td><strong>Total Paid</strong></td>
			<td colspan='6' style='color:green;'><strong>₹" . number_format($totalPaid, 2) . "</strong></td>
		</tr>
		<tr>
			<td><strong>Total Pending</strong></td>
			<td colspan='6' style='color:red;'><strong>₹" . number_format($totalPending, 2) . "</strong></td>
		</tr>

	</table>
	</body>
	</html>";

	exit;
}



// Fetch Guards
if (isset($_POST['action']) && $_POST['action'] === 'fetch_guards') {

	$columns = [
		'id',
		'name',
		'mobile',
		'dob',
		'gender',
		'shift',
		'joining_date',
		'salary'
	];

	$limit  = $_POST['length'];
	$start  = $_POST['start'];
	$order  = $columns[$_POST['order'][0]['column']];
	$dir    = $_POST['order'][0]['dir'];
	$search = $_POST['search']['value'];

	$where = '';
	$params = [];

	if (!empty($search)) {
		$where = "WHERE name LIKE ? OR mobile LIKE ?";
		$params[] = "%$search%";
		$params[] = "%$search%";
	}

	/* ===== TOTAL ===== */
	$total = $pdo->query("SELECT COUNT(*) FROM security_guards")->fetchColumn();

	/* ===== FILTERED ===== */
	if ($where) {
		$stmt = $pdo->prepare("SELECT COUNT(*) FROM security_guards $where");
		$stmt->execute($params);
		$filtered = $stmt->fetchColumn();
	} else {
		$filtered = $total;
	}

	/* ===== DATA ===== */
	$sql = "
        SELECT id, name, mobile, dob, gender, shift, joining_date, salary
        FROM security_guards
        $where
        ORDER BY $order $dir
        LIMIT $start, $limit
    ";

	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);

	echo json_encode([
		"draw" => intval($_POST['draw']),
		"recordsTotal" => $total,
		"recordsFiltered" => $filtered,
		"data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
	]);
	exit;
}

// FETCH GUARD SALARY BILLS
if (isset($_POST['action']) && $_POST['action'] === 'fetch_guard_salary') {

	header('Content-Type: application/json');

	$draw   = $_POST['draw'];
	$start  = $_POST['start'];
	$length = $_POST['length'];

	// FILTER VALUES
	$month  = $_POST['month'] ?? '';
	$year   = $_POST['year'] ?? '';
	$status = $_POST['status'] ?? '';

	// WHERE CONDITIONS
	$where = " WHERE 1 ";
	$params = [];

	if ($month != '') {
		$where .= " AND gs.salary_month = ? ";
		$params[] = $month;
	}

	if ($year != '') {
		$where .= " AND gs.salary_year = ? ";
		$params[] = $year;
	}

	if ($status != '') {
		$where .= " AND gs.status = ? ";
		$params[] = $status;
	}

	// TOTAL RECORDS
	$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM guard_salary gs $where");
	$stmtTotal->execute($params);
	$recordsFiltered = $stmtTotal->fetchColumn();

	// FETCH DATA
	$sql = "
        SELECT 
            gs.id,
            gs.salary_month,
            gs.salary_year,
            gs.salary_amount,
            gs.status,
            gs.paid_on,
            g.name,
            g.mobile,
            g.dob
        FROM guard_salary gs
        JOIN security_guards g ON g.id = gs.guard_id
        $where
        ORDER BY gs.salary_year DESC, gs.salary_month DESC
        LIMIT $start, $length
    ";

	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$data = [];

	foreach ($rows as $r) {

		$monthYear = date('F', mktime(0, 0, 0, $r['salary_month'], 1)) . ' ' . $r['salary_year'];

		$statusBadge = $r['status'] == 'paid'
			? '<span class="badge bg-success">Paid</span>'
			: '<span class="badge bg-warning">Unpaid</span>';

		if ($r['status'] == 'unpaid') {
			$action = '<button class="btn btn-sm btn-primary pay-salary" data-id="' . $r['id'] . '">Pay</button>';
		} else {
			$action = '<span class="text-muted">Paid</span>';
		}


		$data[] = [
			'month_year' => $monthYear,
			'name'       => $r['name'],
			'mobile'     => $r['mobile'],
			'dob'        => date('d-m-Y', strtotime($r['dob'])),
			'salary'     => '₹' . number_format($r['salary_amount'], 2),
			'status'     => $statusBadge,
			'paid_on' => $r['paid_on'] ? date('d-m-Y h:i A', strtotime($r['paid_on'])) : '-',
			'action'     => $action
		];
	}

	echo json_encode([
		"draw" => $draw,
		"recordsTotal" => $recordsFiltered,
		"recordsFiltered" => $recordsFiltered,
		"data" => $data
	]);
	exit;
}

// MARK GUARD SALARY PAID
if (isset($_POST['action']) && $_POST['action'] == 'mark_guard_salary_paid') {

	requireRole(['admin', 'cashier']);

	$id = (int) $_POST['id'];

	$pdo->prepare("
        UPDATE guard_salary 
        SET status='paid', paid_on=NOW()
        WHERE id=?
    ")->execute([$id]);

	echo "success";
	exit;
}

// EXPORT GUARD SALARY (REAL EXCEL REPORT - FIXED NO created_at)
if (isset($_GET['action']) && $_GET['action'] == 'export_guard_salary') {

	requireRole(['admin', 'cashier']);

	$month  = $_GET['month'] ?? '';
	$year   = $_GET['year'] ?? '';
	$status = $_GET['status'] ?? '';

	$where = [];
	$params = [];

	if (ctype_digit($month)) {
		$where[] = 'gs.salary_month = ?';
		$params[] = $month;
	}

	if (ctype_digit($year)) {
		$where[] = 'gs.salary_year = ?';
		$params[] = $year;
	}

	if (in_array($status, ['paid', 'unpaid', 'pending'])) {
		$where[] = 'gs.status = ?';
		$params[] = $status;
	}

	$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

	/* ===== FETCH DATA (REMOVED created_at) ===== */
	$stmt = $pdo->prepare("
        SELECT 
            g.name,
            g.mobile,
            gs.salary_month,
            gs.salary_year,
            gs.salary_amount,
            gs.status,
            gs.paid_on
        FROM guard_salary gs
        JOIN security_guards g ON g.id = gs.guard_id
        $whereSql
        ORDER BY gs.salary_year DESC, gs.salary_month DESC
    ");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	/* ===== TOTAL CALCULATIONS ===== */
	$grandTotal = 0;
	$paidTotal = 0;
	$unpaidTotal = 0;

	foreach ($rows as $r) {
		$amount = (float)$r['salary_amount'];
		$grandTotal += $amount;

		if ($r['status'] === 'paid') {
			$paidTotal += $amount;
		} else {
			$unpaidTotal += $amount;
		}
	}

	/* ===== FILTER TEXT ===== */
	$monthText  = ctype_digit($month) ? date('F', mktime(0, 0, 0, $month, 1)) : 'All Months';
	$yearText   = ctype_digit($year) ? $year : 'All Years';
	$statusText = $status ? ucfirst($status) : 'All Status';

	/* ===== EXCEL HEADERS ===== */
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=guard_salary_report.xls");
	header("Pragma: no-cache");
	header("Expires: 0");

	echo "
	<html>
	<head>
		<meta charset='UTF-8'>
	</head>
	<body>

	<table border='1' cellspacing='0' cellpadding='6'>

		<tr>
			<th colspan='6' style='font-size:18px; font-weight:bold; text-align:center;'>
				Security Guard Salary Report
			</th>
		</tr>

		<tr>
			<td colspan='6'><strong>Month:</strong> {$monthText}</td>
		</tr>
		<tr>
			<td colspan='6'><strong>Year:</strong> {$yearText}</td>
		</tr>
		<tr>
			<td colspan='6'><strong>Status:</strong> {$statusText}</td>
		</tr>
		<tr>
			<td colspan='6'><strong>Generated On:</strong> " . date('d-m-Y H:i') . "</td>
		</tr>

		<tr style='background:#343a40; color:#fff; font-weight:bold; text-align:center;'>
			<th>Guard Name</th>
			<th>Mobile</th>
			<th>Month / Year</th>
			<th>Salary Amount (₹)</th>
			<th>Status</th>
			<th>Paid On</th>
		</tr>
	";

	foreach ($rows as $r) {

		$monthName = date('F', mktime(0, 0, 0, $r['salary_month'], 1));
		$monthYear = $monthName . ' ' . $r['salary_year'];

		$statusLabel = ucfirst($r['status']);
		$statusColor = ($r['status'] === 'paid') ? 'green' : 'red';

		$paidOn = $r['paid_on']
			? date('d-m-Y h:i A', strtotime($r['paid_on']))
			: '-';


		echo "<tr>
			<td>{$r['name']}</td>
			<td>{$r['mobile']}</td>
			<td>{$monthYear}</td>
			<td><strong>₹" . number_format((float)$r['salary_amount'], 2) . "</strong></td>
			<td style='color:{$statusColor}; font-weight:bold;'>{$statusLabel}</td>
			<td>{$paidOn}</td>
		</tr>";
	}

	/* ===== SUMMARY ===== */
	echo "
		<tr>
			<td colspan='3'><strong>Grand Total Salary</strong></td>
			<td colspan='3'><strong>₹" . number_format($grandTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td colspan='3'><strong>Total Paid</strong></td>
			<td colspan='3' style='color:green;'><strong>₹" . number_format($paidTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td colspan='3'><strong>Total Unpaid</strong></td>
			<td colspan='3' style='color:red;'><strong>₹" . number_format($unpaidTotal, 2) . "</strong></td>
		</tr>

	</table>
	</body>
	</html>";

	exit;
}




// FETCH GARBAGE SALARY
if (isset($_POST['action']) && $_POST['action'] == "fetch_garbage_salary") {

	header('Content-Type: application/json');

	$draw = $_POST['draw'];
	$start = $_POST['start'];
	$length = $_POST['length'];

	$month = $_POST['month'] ?? '';
	$year = $_POST['year'] ?? '';
	$status = $_POST['status'] ?? '';

	$where = " WHERE 1 ";
	$params = [];

	if ($month != '') {
		$where .= " AND gs.salary_month=? ";
		$params[] = $month;
	}
	if ($year != '') {
		$where .= " AND gs.salary_year=? ";
		$params[] = $year;
	}
	if ($status != '') {
		$where .= " AND gs.status=? ";
		$params[] = $status;
	}

	$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM garbage_salary gs $where");
	$stmtTotal->execute($params);
	$records = $stmtTotal->fetchColumn();

	$sql = "
	SELECT gs.*, gc.name, gc.mobile, gc.dob
	FROM garbage_salary gs
	JOIN garbage_collectors gc ON gc.id=gs.collector_id
	$where
	ORDER BY gs.salary_year DESC, gs.salary_month DESC
	LIMIT $start,$length
	";
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$data = [];

	foreach ($rows as $r) {
		$monthYear = date('F', mktime(0, 0, 0, $r['salary_month'], 1)) . " " . $r['salary_year'];

		$badge = $r['status'] == 'paid'
			? '<span class="badge bg-success">Paid</span>'
			: '<span class="badge bg-warning">Unpaid</span>';

		$action = $r['status'] == 'unpaid'
			? '<button class="btn btn-sm btn-primary pay-salary" data-id="' . $r['id'] . '">Pay</button>'
			: '<span class="text-muted">Paid</span>';

		$data[] = [
			'month_year' => $monthYear,
			'name' => $r['name'],
			'mobile' => $r['mobile'],
			'dob' => date('d-m-Y', strtotime($r['dob'])),
			'salary' => '₹' . number_format($r['salary_amount'], 2),
			'status' => $badge,
			'paid_on' => $r['paid_on'] ? date('d-m-Y h:i A', strtotime($r['paid_on'])) : '-',
			'action' => $action
		];
	}

	echo json_encode([
		"draw" => $draw,
		"recordsTotal" => $records,
		"recordsFiltered" => $records,
		"data" => $data
	]);
	exit;
}

// MARK GARBAGE SALARY PAID
if (isset($_POST['action']) && $_POST['action'] == "mark_garbage_salary_paid") {
	requireRole(['admin', 'cashier']);
	$id = (int)$_POST['id'];
	$pdo->prepare("UPDATE garbage_salary SET status='paid', paid_on=NOW() WHERE id=?")->execute([$id]);
	echo "success";
	exit;
}

// EXPORT GARBAGE SALARY (REAL EXCEL REPORT FORMAT)
if (isset($_GET['action']) && $_GET['action'] == "export_garbage_salary") {

	requireRole(['admin', 'cashier']);

	$month  = $_GET['month'] ?? '';
	$year   = $_GET['year'] ?? '';
	$status = $_GET['status'] ?? '';

	$where = [];
	$params = [];

	/* ===== SAFE FILTERS ===== */
	if (ctype_digit($month)) {
		$where[] = "gs.salary_month = ?";
		$params[] = $month;
	}

	if (ctype_digit($year)) {
		$where[] = "gs.salary_year = ?";
		$params[] = $year;
	}

	if (in_array($status, ['paid', 'unpaid', 'pending'])) {
		$where[] = "gs.status = ?";
		$params[] = $status;
	}

	$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

	/* ===== FETCH DATA (FIXED QUERY) ===== */
	$stmt = $pdo->prepare("
		SELECT 
			gc.name,
			gc.mobile,
			gs.salary_month,
			gs.salary_year,
			gs.salary_amount,
			gs.status,
			gs.paid_on
		FROM garbage_salary gs
		JOIN garbage_collectors gc ON gc.id = gs.collector_id
		$whereSql
		ORDER BY gs.salary_year DESC, gs.salary_month DESC
	");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	/* ===== TOTAL CALCULATION ===== */
	$grandTotal  = 0;
	$paidTotal   = 0;
	$unpaidTotal = 0;

	foreach ($rows as $r) {
		$amount = (float)$r['salary_amount'];
		$grandTotal += $amount;

		if ($r['status'] === 'paid') {
			$paidTotal += $amount;
		} else {
			$unpaidTotal += $amount;
		}
	}

	/* ===== FILTER TEXT ===== */
	$monthText  = ctype_digit($month) ? date('F', mktime(0, 0, 0, $month, 1)) : 'All Months';
	$yearText   = ctype_digit($year) ? $year : 'All Years';
	$statusText = $status ? ucfirst($status) : 'All Status';

	/* ===== EXCEL HEADERS ===== */
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=garbage_salary_report.xls");
	header("Pragma: no-cache");
	header("Expires: 0");

	echo "
	<html>
	<head>
		<meta charset='UTF-8'>
	</head>
	<body>

	<table border='1' cellspacing='0' cellpadding='6'>

		<tr>
			<th colspan='6' style='font-size:18px; font-weight:bold; text-align:center;'>
				Garbage Collector Salary Report
			</th>
		</tr>

		<tr>
			<td colspan='6'><strong>Month:</strong> {$monthText}</td>
		</tr>
		<tr>
			<td colspan='6'><strong>Year:</strong> {$yearText}</td>
		</tr>
		<tr>
			<td colspan='6'><strong>Status:</strong> {$statusText}</td>
		</tr>
		<tr>
			<td colspan='6'><strong>Generated On:</strong> " . date('d-m-Y H:i') . "</td>
		</tr>

		<tr style='background:#343a40; color:#fff; font-weight:bold; text-align:center;'>
			<th>Collector Name</th>
			<th>Mobile</th>
			<th>Month / Year</th>
			<th>Salary Amount (₹)</th>
			<th>Status</th>
			<th>Paid On</th>
		</tr>
	";

	foreach ($rows as $r) {

		$monthName = date('F', mktime(0, 0, 0, $r['salary_month'], 1));
		$monthYear = $monthName . ' ' . $r['salary_year'];

		$statusLabel = ucfirst($r['status']);
		$statusColor = ($r['status'] === 'paid') ? 'green' : 'red';

		$paidOn = $r['paid_on']
			? date('d-m-Y h:i A', strtotime($r['paid_on']))
			: '-';


		echo "<tr>
			<td>{$r['name']}</td>
			<td>{$r['mobile']}</td>
			<td>{$monthYear}</td>
			<td><strong>₹" . number_format((float)$r['salary_amount'], 2) . "</strong></td>
			<td style='color:{$statusColor}; font-weight:bold;'>{$statusLabel}</td>
			<td>{$paidOn}</td>
		</tr>";
	}

	/* ===== SUMMARY SECTION ===== */
	echo "
		<tr>
			<td colspan='3'><strong>Grand Total Salary</strong></td>
			<td colspan='3'><strong>₹" . number_format($grandTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td colspan='3'><strong>Total Paid</strong></td>
			<td colspan='3' style='color:green;'><strong>₹" . number_format($paidTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td colspan='3'><strong>Total Unpaid</strong></td>
			<td colspan='3' style='color:red;'><strong>₹" . number_format($unpaidTotal, 2) . "</strong></td>
		</tr>

	</table>
	</body>
	</html>";
	exit;
}





// FETCH SWEEPER SALARY
if (isset($_POST['action']) && $_POST['action'] == "fetch_sweeper_salary") {

	header('Content-Type: application/json');

	$draw = $_POST['draw'];
	$start = $_POST['start'];
	$length = $_POST['length'];

	$month = $_POST['month'] ?? '';
	$year = $_POST['year'] ?? '';
	$status = $_POST['status'] ?? '';

	$where = " WHERE 1 ";
	$params = [];

	if ($month != '') {
		$where .= " AND ss.salary_month=? ";
		$params[] = $month;
	}
	if ($year != '') {
		$where .= " AND ss.salary_year=? ";
		$params[] = $year;
	}
	if ($status != '') {
		$where .= " AND ss.status=? ";
		$params[] = $status;
	}

	$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM sweeper_salary ss $where");
	$stmtTotal->execute($params);
	$records = $stmtTotal->fetchColumn();

	$sql = "
	SELECT ss.*, s.name, s.mobile, s.dob
	FROM sweeper_salary ss
	JOIN sweepers s ON s.id=ss.sweeper_id
	$where
	ORDER BY ss.salary_year DESC, ss.salary_month DESC
	LIMIT $start,$length
	";
	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$data = [];

	foreach ($rows as $r) {
		$monthYear = date('F', mktime(0, 0, 0, $r['salary_month'], 1)) . " " . $r['salary_year'];

		$badge = $r['status'] == 'paid'
			? '<span class="badge bg-success">Paid</span>'
			: '<span class="badge bg-warning">Unpaid</span>';

		$action = $r['status'] == 'unpaid'
			? '<button class="btn btn-sm btn-primary pay-salary" data-id="' . $r['id'] . '">Pay</button>'
			: '<span class="text-muted">Paid</span>';

		$data[] = [
			'month_year' => $monthYear,
			'name' => $r['name'],
			'mobile' => $r['mobile'],
			'dob' => date('d-m-Y', strtotime($r['dob'])),
			'salary' => '₹' . number_format($r['salary_amount'], 2),
			'status' => $badge,
			'paid_on' => $r['paid_on'] ? date('d-m-Y h:i A', strtotime($r['paid_on'])) : '-',
			'action' => $action
		];
	}

	echo json_encode([
		"draw" => $draw,
		"recordsTotal" => $records,
		"recordsFiltered" => $records,
		"data" => $data
	]);
	exit;
}

// MARK SWEEPER SALARY PAID
if (isset($_POST['action']) && $_POST['action'] == "mark_sweeper_salary_paid") {
	requireRole(['admin', 'cashier']);
	$id = (int)$_POST['id'];
	$pdo->prepare("UPDATE sweeper_salary SET status='paid', paid_on=NOW() WHERE id=?")->execute([$id]);
	echo "success";
	exit;
}

// EXPORT SWEEPER SALARY (REAL EXCEL REPORT FORMAT)
if (isset($_GET['action']) && $_GET['action'] == "export_sweeper_salary") {

	requireRole(['admin', 'cashier']);

	$month  = $_GET['month'] ?? '';
	$year   = $_GET['year'] ?? '';
	$status = $_GET['status'] ?? '';

	$where = [];
	$params = [];

	if (ctype_digit($month)) {
		$where[] = "ss.salary_month = ?";
		$params[] = $month;
	}

	if (ctype_digit($year)) {
		$where[] = "ss.salary_year = ?";
		$params[] = $year;
	}

	if (in_array($status, ['paid', 'unpaid', 'pending'])) {
		$where[] = "ss.status = ?";
		$params[] = $status;
	}

	$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

	/* ===== FETCH DATA ===== */
	$stmt = $pdo->prepare("
		SELECT 
			s.name,
			s.mobile,
			ss.salary_month,
			ss.salary_year,
			ss.salary_amount,
			ss.status,
			ss.paid_on
		FROM sweeper_salary ss
		JOIN sweepers s ON s.id = ss.sweeper_id
		$whereSql
		ORDER BY ss.salary_year DESC, ss.salary_month DESC
	");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	/* ===== TOTAL CALCULATION ===== */
	$grandTotal  = 0;
	$paidTotal   = 0;
	$unpaidTotal = 0;

	foreach ($rows as $r) {
		$amount = (float)$r['salary_amount'];
		$grandTotal += $amount;

		if ($r['status'] === 'paid') {
			$paidTotal += $amount;
		} else {
			$unpaidTotal += $amount;
		}
	}

	/* ===== FILTER TEXT ===== */
	$monthText  = ctype_digit($month) ? date('F', mktime(0, 0, 0, $month, 1)) : 'All Months';
	$yearText   = ctype_digit($year) ? $year : 'All Years';
	$statusText = $status ? ucfirst($status) : 'All Status';

	/* ===== EXCEL HEADERS ===== */
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=sweeper_salary_report.xls");
	header("Pragma: no-cache");
	header("Expires: 0");

	echo "
	<html>
	<head>
		<meta charset='UTF-8'>
	</head>
	<body>

	<table border='1' cellspacing='0' cellpadding='6'>

		<tr>
			<th colspan='6' style='font-size:18px; font-weight:bold; text-align:center;'>
				Sweeper Salary Report
			</th>
		</tr>

		<tr>
			<td colspan='6'><strong>Month:</strong> {$monthText}</td>
		</tr>
		<tr>
			<td colspan='6'><strong>Year:</strong> {$yearText}</td>
		</tr>
		<tr>
			<td colspan='6'><strong>Status:</strong> {$statusText}</td>
		</tr>
		<tr>
			<td colspan='6'><strong>Generated On:</strong> " . date('d-m-Y H:i') . "</td>
		</tr>

		<tr style='background:#343a40; color:#fff; font-weight:bold; text-align:center;'>
			<th>Sweeper Name</th>
			<th>Mobile</th>
			<th>Month / Year</th>
			<th>Salary Amount (₹)</th>
			<th>Status</th>
			<th>Paid On</th>
		</tr>
	";

	foreach ($rows as $r) {

		$monthName = date('F', mktime(0, 0, 0, $r['salary_month'], 1));
		$monthYear = $monthName . ' ' . $r['salary_year'];

		$statusLabel = ucfirst($r['status']);
		$statusColor = ($r['status'] === 'paid') ? 'green' : 'red';

		$paidOn = $r['paid_on']
			? date('d-m-Y h:i A', strtotime($r['paid_on']))
			: '-';


		echo "<tr>
			<td>{$r['name']}</td>
			<td>{$r['mobile']}</td>
			<td>{$monthYear}</td>
			<td><strong>₹" . number_format((float)$r['salary_amount'], 2) . "</strong></td>
			<td style='color:{$statusColor}; font-weight:bold;'>{$statusLabel}</td>
			<td>{$paidOn}</td>
		</tr>";
	}

	/* ===== SUMMARY SECTION ===== */
	echo "
		<tr>
			<td colspan='3'><strong>Grand Total Salary</strong></td>
			<td colspan='3'><strong>₹" . number_format($grandTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td colspan='3'><strong>Total Paid</strong></td>
			<td colspan='3' style='color:green;'><strong>₹" . number_format($paidTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td colspan='3'><strong>Total Unpaid</strong></td>
			<td colspan='3' style='color:red;'><strong>₹" . number_format($unpaidTotal, 2) . "</strong></td>
		</tr>

	</table>
	</body>
	</html>";
	exit;
}





// FETCH Miscellaneous Work
if (isset($_POST['action']) && $_POST['action'] == 'fetch_misc_works') {

	header('Content-Type: application/json');

	$draw   = $_POST['draw'];
	$start  = $_POST['start'];
	$length = $_POST['length'];

	$month  = $_POST['month'] ?? '';
	$year   = $_POST['year'] ?? '';
	$search = $_POST['search']['value'] ?? '';

	$where = " WHERE 1 ";
	$params = [];

	// Month Filter
	if ($month != '') {
		$where .= " AND month=? ";
		$params[] = $month;
	}

	// Year Filter
	if ($year != '') {
		$where .= " AND year=? ";
		$params[] = $year;
	}

	// Search Filter
	if ($search != '') {
		$where .= " AND (work_title LIKE ? OR worker_name LIKE ? OR contact_number LIKE ?) ";
		$params[] = "%$search%";
		$params[] = "%$search%";
		$params[] = "%$search%";
	}

	// Total Records
	$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM miscellaneous_works $where");
	$stmtTotal->execute($params);
	$records = $stmtTotal->fetchColumn();

	// Data Query
	$sql = "SELECT * FROM miscellaneous_works 
	        $where 
	        ORDER BY year DESC, month DESC 
	        LIMIT $start,$length";

	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$data = [];

	foreach ($rows as $r) {

		// Month Year Format Like Sweeper Salary
		$monthYear = date('F', mktime(0, 0, 0, $r['month'], 1)) . " " . $r['year'];

		$badge = '<span class="badge bg-success">Paid</span>';

		$data[] = [
			'id' => $r['id'],
			'month_year' => $monthYear,
			'work_title' => $r['work_title'],
			'description' => $r['description'],
			'worker_name' => $r['worker_name'],
			'contact_number' => $r['contact_number'],
			'amount' => '₹' . number_format($r['amount'], 2),
			'status' => $badge,
			'paid_on' => $r['paid_on']
				? date('d-m-Y h:i A', strtotime($r['paid_on']))
				: '-'
		];
	}

	echo json_encode([
		"draw" => $draw,
		"recordsTotal" => $records,
		"recordsFiltered" => $records,
		"data" => $data
	]);
	exit;
}

// EXPORT MISCELLANEOUS WORK (REAL EXCEL FORMAT)
if (isset($_GET['action']) && $_GET['action'] == 'export_misc_work') {

	requireRole(['admin', 'cashier']);

	$month = $_GET['month'] ?? '';
	$year  = $_GET['year'] ?? '';

	$where = [];
	$params = [];

	if (ctype_digit($month)) {
		$where[] = 'month = ?';
		$params[] = $month;
	}

	if (ctype_digit($year)) {
		$where[] = 'year = ?';
		$params[] = $year;
	}

	$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

	/* ===== FETCH DATA ===== */
	$stmt = $pdo->prepare("
        SELECT 
            work_title,
            worker_name,
            contact_number,
            amount,
            month,
            year,
            status
        FROM miscellaneous_works
        $whereSql
        ORDER BY year DESC, month DESC
    ");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	/* ===== TOTAL CALCULATIONS ===== */
	$grandTotal = 0;
	foreach ($rows as $r) {
		$grandTotal += (float)$r['amount'];
	}

	/* ===== FILTER TEXT ===== */
	$monthText = ctype_digit($month) ? date('F', mktime(0, 0, 0, $month, 1)) : 'All Months';
	$yearText  = ctype_digit($year) ? $year : 'All Years';

	/* ===== EXCEL HEADERS ===== */
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=miscellaneous_work_report.xls");
	header("Pragma: no-cache");
	header("Expires: 0");

	echo "
    <html>
    <head>
        <meta charset='UTF-8'>
    </head>
    <body>

    <table border='1' cellspacing='0' cellpadding='6'>

        <tr>
            <th colspan='7' style='font-size:18px; font-weight:bold; text-align:center;'>
                Miscellaneous Work Expense Report
            </th>
        </tr>

        <tr>
            <td colspan='7'><strong>Month:</strong> {$monthText}</td>
        </tr>
        <tr>
            <td colspan='7'><strong>Year:</strong> {$yearText}</td>
        </tr>
        <tr>
            <td colspan='7'><strong>Generated On:</strong> " . date('d-m-Y H:i') . "</td>
        </tr>

        <tr style='background:#343a40; color:#fff; font-weight:bold; text-align:center;'>
            <th>Work Title</th>
            <th>Worker Name</th>
            <th>Contact Number</th>
            <th>Amount (₹)</th>
            <th>Month / Year</th>
            <th>Status</th>
            <th>Created Date</th>
        </tr>
    ";

	foreach ($rows as $r) {
		$monthName = date('F', mktime(0, 0, 0, $r['month'], 1));
		$monthYear = $monthName . ' ' . $r['year'];
		$status = ucfirst($r['status'] ?? 'unpaid');
		$statusColor = ($r['status'] ?? '') === 'paid' ? 'green' : 'red';
		$createdDate = '-'; // placeholder since created_at doesn't exist

		echo "<tr>
            <td>{$r['work_title']}</td>
            <td>{$r['worker_name']}</td>
            <td>{$r['contact_number']}</td>
            <td><strong>₹" . number_format((float)$r['amount'], 2) . "</strong></td>
            <td>{$monthYear}</td>
            <td style='color:{$statusColor}; font-weight:bold;'>{$status}</td>
            <td>{$createdDate}</td>
        </tr>";
	}

	/* ===== SUMMARY SECTION ===== */
	echo "
        <tr>
            <td colspan='7' style='background:#f2f2f2; font-weight:bold; text-align:center;'>
                SUMMARY
            </td>
        </tr>
        <tr>
            <td colspan='3'><strong>Grand Total Expense</strong></td>
            <td colspan='4'><strong>₹" . number_format($grandTotal, 2) . "</strong></td>
        </tr>

    </table>
    </body>
    </html>";

	exit;
}




// FETCH ALL EXPENSES
if (isset($_POST['action']) && $_POST['action'] === 'fetch_all_expenses') {

	header('Content-Type: application/json');

	$draw   = intval($_POST['draw'] ?? 0);
	$start  = intval($_POST['start'] ?? 0);
	$length = intval($_POST['length'] ?? 10);

	$month  = $_POST['month'] ?? '';
	$year   = $_POST['year'] ?? '';
	$status = $_POST['status'] ?? '';

	$params = [];

	/* ================= FETCH DATA ================= */
	$sql = "
	SELECT * FROM (
		SELECT id, month, year, reading AS name, NULL AS work_title, amount, status, last_paid_on AS paid_on, 'electricity_bills' AS source_table 
		FROM electricity_bills WHERE 1=1
		" . ($month ? " AND month = :month " : "") . "
		" . ($year ? " AND year = :year " : "") . "
		" . ($status ? " AND status = :status " : "") . "

		UNION ALL

		SELECT id, month, year, worker_name AS name, work_title, amount, status, paid_on AS paid_on, 'miscellaneous_works' AS source_table 
		FROM miscellaneous_works WHERE 1=1
		" . ($month ? " AND month = :month " : "") . "
		" . ($year ? " AND year = :year " : "") . "
		" . ($status ? " AND status = :status " : "") . "


		UNION ALL

		SELECT ss.id, ss.salary_month AS month, ss.salary_year AS year, sw.name AS name, NULL AS work_title,
			ss.salary_amount AS amount, ss.status, ss.paid_on, 'sweeper_salary' AS source_table
		FROM sweeper_salary ss 
		JOIN sweepers sw ON sw.id = ss.sweeper_id 
		WHERE 1=1
		" . ($month ? " AND ss.salary_month = :month " : "") . "
		" . ($year ? " AND ss.salary_year = :year " : "") . "
		" . ($status ? " AND ss.status = :status " : "") . "

		UNION ALL

		SELECT gs.id, gs.salary_month AS month, gs.salary_year AS year, sg.name AS name, NULL AS work_title,
			gs.salary_amount AS amount, gs.status, gs.paid_on, 'guard_salary' AS source_table
		FROM guard_salary gs 
		JOIN security_guards sg ON sg.id = gs.guard_id 
		WHERE 1=1
		" . ($month ? " AND gs.salary_month = :month " : "") . "
		" . ($year ? " AND gs.salary_year = :year " : "") . "
		" . ($status ? " AND gs.status = :status " : "") . "

		UNION ALL

		SELECT gc.id, gc.salary_month AS month, gc.salary_year AS year, gcol.name AS name, NULL AS work_title,
			gc.salary_amount AS amount, gc.status, gc.paid_on, 'garbage_salary' AS source_table
		FROM garbage_salary gc 
		JOIN garbage_collectors gcol ON gcol.id = gc.collector_id 
		WHERE 1=1
		" . ($month ? " AND gc.salary_month = :month " : "") . "
		" . ($year ? " AND gc.salary_year = :year " : "") . "
		" . ($status ? " AND gc.status = :status " : "") . "
	) x
	ORDER BY year DESC, month DESC
	LIMIT :start, :length
	";


	if ($month)  $params[':month']  = $month;
	if ($year)   $params[':year']   = $year;
	if ($status) $params[':status'] = $status;

	$stmt = $pdo->prepare($sql);
	foreach ($params as $k => $v) {
		$stmt->bindValue($k, $v);
	}
	$stmt->bindValue(':start', $start, PDO::PARAM_INT);
	$stmt->bindValue(':length', $length, PDO::PARAM_INT);
	$stmt->execute();
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// FORMAT DATA
	$data = [];
	foreach ($rows as $r) {

		$monthName = date('F', mktime(0, 0, 0, $r['month'], 1));

		// SOURCE NAME
		$sourceMap = [
			'electricity_bills'   => 'Electricity Bill',
			'miscellaneous_works' => 'Miscellaneous Work',
			'sweeper_salary'      => 'Sweeper Salary',
			'guard_salary'        => 'Guard Salary',
			'garbage_salary'      => 'Garbage Collector Salary'
		];

		$formattedSource = $sourceMap[$r['source_table']] ?? ucwords(str_replace('_', ' ', $r['source_table']));

		// NAME FORMAT
		if ($r['source_table'] === 'electricity_bills') {
			$formattedName = "Meter Reading (" . $r['name'] . ")";
		} elseif ($r['source_table'] === 'miscellaneous_works') {
			$formattedName = !empty($r['work_title'])
				? $r['name'] . " (" . $r['work_title'] . ")"
				: $r['name'];
		} else {
			$formattedName = $r['name'];
		}

		// 🆕 PAID ON FORMAT (AM/PM)
		$paidOn = !empty($r['paid_on'])
			? date('d-m-Y h:i A', strtotime($r['paid_on']))
			: '-';

		$data[] = [
			'id' => $r['id'],
			'month_year' => $monthName . ' ' . $r['year'],
			'name' => $formattedName,
			'source' => $formattedSource,
			'amount' => '₹' . number_format($r['amount'], 2),
			'status' => "<span class='badge bg-" . ($r['status'] == 'paid' ? 'success' : 'danger') . "'>" . ucfirst($r['status']) . "</span>",
			'paid_on' => $paidOn // NEW COLUMN
		];
	}



	/* ================= COUNT TOTAL ROWS ================= */
	$countSql = "
    SELECT COUNT(*) FROM (
        SELECT id FROM electricity_bills
        UNION ALL SELECT id FROM miscellaneous_works
        UNION ALL SELECT id FROM sweeper_salary
        UNION ALL SELECT id FROM guard_salary
        UNION ALL SELECT id FROM garbage_salary
    ) x
    ";

	$totalRecords = $pdo->query($countSql)->fetchColumn();

	echo json_encode([
		'draw' => $draw,
		'recordsTotal' => $totalRecords,
		'recordsFiltered' => $totalRecords,
		'data' => $data
	]);
	exit;
}

// FETCH EXPENSE TOTALS
if (isset($_POST['action']) && $_POST['action'] === 'fetch_expense_totals') {

	header('Content-Type: application/json');

	$month  = $_POST['month'] ?? '';
	$year   = $_POST['year'] ?? '';
	$status = $_POST['status'] ?? '';

	$params = [];

	// ===== ELECTRICITY =====
	$sqlElectric = "SELECT amount, status FROM electricity_bills WHERE 1=1";
	if ($month) {
		$sqlElectric .= " AND month = :month";
		$params[':month'] = $month;
	}
	if ($year) {
		$sqlElectric .= " AND year = :year";
		$params[':year'] = $year;
	}
	if ($status) {
		$sqlElectric .= " AND status = :status";
		$params[':status'] = $status;
	}

	// ===== MISC WORK =====
	$sqlMisc = "SELECT amount, status FROM miscellaneous_works WHERE 1=1";
	if ($month) $sqlMisc .= " AND month = :month";
	if ($year)  $sqlMisc .= " AND year = :year";
	if ($status) $sqlMisc .= " AND status = :status";

	// ===== SWEEPER =====
	$sqlSweeper = "SELECT salary_amount AS amount, status FROM sweeper_salary WHERE 1=1";
	if ($month) $sqlSweeper .= " AND salary_month = :month";
	if ($year)  $sqlSweeper .= " AND salary_year = :year";
	if ($status) $sqlSweeper .= " AND status = :status";

	// ===== GUARD =====
	$sqlGuard = "SELECT salary_amount AS amount, status FROM guard_salary WHERE 1=1";
	if ($month) $sqlGuard .= " AND salary_month = :month";
	if ($year)  $sqlGuard .= " AND salary_year = :year";
	if ($status) $sqlGuard .= " AND status = :status";

	// ===== GARBAGE =====
	$sqlGarbage = "SELECT salary_amount AS amount, status FROM garbage_salary WHERE 1=1";
	if ($month) $sqlGarbage .= " AND salary_month = :month";
	if ($year)  $sqlGarbage .= " AND salary_year = :year";
	if ($status) $sqlGarbage .= " AND status = :status";

	// ===== UNION ALL =====
	$sql = "
        SELECT SUM(amount) AS grandTotal,
               SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) AS paidTotal,
               SUM(CASE WHEN status!='paid' THEN amount ELSE 0 END) AS unpaidTotal
        FROM (
            $sqlElectric
            UNION ALL
            $sqlMisc
            UNION ALL
            $sqlSweeper
            UNION ALL
            $sqlGuard
            UNION ALL
            $sqlGarbage
        ) x
    ";

	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);
	$row = $stmt->fetch(PDO::FETCH_ASSOC);

	echo json_encode([
		'grandTotal' => number_format($row['grandTotal'] ?? 0, 2),
		'paidTotal'  => number_format($row['paidTotal'] ?? 0, 2),
		'unpaidTotal' => number_format($row['unpaidTotal'] ?? 0, 2)
	]);
	exit;
}

// EXPORT EXPENSES TO REAL EXCEL FORMAT (PROFESSIONAL REPORT)
if (isset($_GET['action']) && $_GET['action'] === 'export_expense_bills') {

	requireRole(['admin', 'cashier']);

	$month  = $_GET['month']  ?? '';
	$year   = $_GET['year']   ?? '';
	$status = $_GET['status'] ?? '';

	$params = [];

	/* ===== MAIN UNION QUERY (MATCHING YOUR FETCH LOGIC) ===== */
	$sql = "
		SELECT * FROM (
			SELECT 
				id, 
				month, 
				year, 
				reading AS name, 
				NULL AS work_title, 
				amount, 
				status,
				last_paid_on AS paid_on,
				'Electricity Bill' AS source
			FROM electricity_bills WHERE 1=1
			" . ($month ? " AND month = :month " : "") . "
			" . ($year ? " AND year = :year " : "") . "
			" . ($status ? " AND status = :status " : "") . "

			UNION ALL

			SELECT 
				id, 
				month, 
				year, 
				worker_name AS name, 
				work_title, 
				amount, 
				status,
				paid_on AS paid_on,
				'Misc Work' AS source
			FROM miscellaneous_works WHERE 1=1
			" . ($month ? " AND month = :month " : "") . "
			" . ($year ? " AND year = :year " : "") . "
			" . ($status ? " AND status = :status " : "") . "

			UNION ALL

			SELECT 
				ss.id, 
				ss.salary_month AS month, 
				ss.salary_year AS year,
				sw.name AS name, 
				NULL AS work_title, 
				ss.salary_amount AS amount, 
				ss.status,
				ss.paid_on AS paid_on,
				'Sweeper Salary' AS source
			FROM sweeper_salary ss
			JOIN sweepers sw ON sw.id = ss.sweeper_id
			WHERE 1=1
			" . ($month ? " AND ss.salary_month = :month " : "") . "
			" . ($year ? " AND ss.salary_year = :year " : "") . "
			" . ($status ? " AND ss.status = :status " : "") . "

			UNION ALL

			SELECT 
				gs.id, 
				gs.salary_month AS month, 
				gs.salary_year AS year,
				sg.name AS name, 
				NULL AS work_title, 
				gs.salary_amount AS amount, 
				gs.status,
				gs.paid_on AS paid_on,
				'Guard Salary' AS source
			FROM guard_salary gs
			JOIN security_guards sg ON sg.id = gs.guard_id
			WHERE 1=1
			" . ($month ? " AND gs.salary_month = :month " : "") . "
			" . ($year ? " AND gs.salary_year = :year " : "") . "
			" . ($status ? " AND gs.status = :status " : "") . "

			UNION ALL

			SELECT 
				gc.id, 
				gc.salary_month AS month, 
				gc.salary_year AS year,
				gcol.name AS name, 
				NULL AS work_title, 
				gc.salary_amount AS amount, 
				gc.status,
				gc.paid_on AS paid_on,
				'Garbage Collector Salary' AS source
			FROM garbage_salary gc
			JOIN garbage_collectors gcol ON gcol.id = gc.collector_id
			WHERE 1=1
			" . ($month ? " AND gc.salary_month = :month " : "") . "
			" . ($year ? " AND gc.salary_year = :year " : "") . "
			" . ($status ? " AND gc.status = :status " : "") . "
		) x
		ORDER BY year DESC, month DESC
	";



	if ($month)  $params[':month']  = $month;
	if ($year)   $params[':year']   = $year;
	if ($status) $params[':status'] = $status;

	$stmt = $pdo->prepare($sql);
	foreach ($params as $k => $v) {
		$stmt->bindValue($k, $v);
	}
	$stmt->execute();
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	/* ===== TOTALS CALCULATION ===== */
	$grandTotal = 0;
	$paidTotal = 0;
	$unpaidTotal = 0;

	foreach ($rows as $r) {
		$amount = (float)$r['amount'];
		$grandTotal += $amount;

		if ($r['status'] === 'paid') {
			$paidTotal += $amount;
		} else {
			$unpaidTotal += $amount;
		}
	}

	/* ===== FILTER TEXT ===== */
	$monthText  = ctype_digit($month) ? date('F', mktime(0, 0, 0, $month, 1)) : 'All Months';
	$yearText   = ctype_digit($year) ? $year : 'All Years';
	$statusText = $status ? ucfirst($status) : 'All Status';

	/* ===== EXCEL HEADERS ===== */
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=expense_report.xls");
	header("Pragma: no-cache");
	header("Expires: 0");

	echo "
	<html>
	<head>
		<meta charset='UTF-8'>
	</head>
	<body>

	<table border='1' cellspacing='0' cellpadding='7'>

		<tr>
			<th colspan='7' style='font-size:18px; font-weight:bold; text-align:center;'>
				Society Expense Report
			</th>
		</tr>

		<tr>
			<td colspan='7'><strong>Month:</strong> {$monthText}</td>
		</tr>
		<tr>
			<td colspan='7'><strong>Year:</strong> {$yearText}</td>
		</tr>
		<tr>
			<td colspan='7'><strong>Status:</strong> {$statusText}</td>
		</tr>
		<tr>
			<td colspan='7'><strong>Generated On:</strong> " . date('d-m-Y H:i') . "</td>
		</tr>

		<tr style='background:#343a40; color:#fff; font-weight:bold; text-align:center;'>
			<th>ID</th>
			<th>Month / Year</th>
			<th>Name / Reading</th>
			<th>Expense Type</th>
			<th>Amount (₹)</th>
			<th>Status</th>
			<th>Paid On</th>
		</tr>
	";

	foreach ($rows as $row) {

		$monthName = date('F', mktime(0, 0, 0, $row['month'], 1));
		$monthYear = $monthName . ' ' . $row['year'];

		$statusColor = ($row['status'] === 'paid') ? 'green' : 'red';

		/* ===== FORMAT NAME / READING (FINAL LOGIC) ===== */
		if ($row['source'] === 'Electricity Bill') {
			// Example: Meter Reading (1450)
			$nameReading = "Meter Reading (" . $row['name'] . ")";
		} elseif ($row['source'] === 'Misc Work') {
			// Example: Ramesh (Pipeline Repair)
			if (!empty($row['work_title'])) {
				$nameReading = $row['name'] . " (" . $row['work_title'] . ")";
			} else {
				$nameReading = $row['name'];
			}
		} else {
			// Salary rows (Sweeper, Guard, Garbage)
			$nameReading = $row['name'];
		}

		$paidOn = !empty($row['paid_on'])
			? date('d-m-Y h:i A', strtotime($row['paid_on']))
			: '-';

		echo "<tr>
    <td>{$row['id']}</td>
    <td>{$monthYear}</td>
    <td>{$nameReading}</td>
    <td>{$row['source']}</td>
    <td><strong>₹" . number_format((float)$row['amount'], 2) . "</strong></td>
    <td style='color:{$statusColor}; font-weight:bold;'>" . ucfirst($row['status']) . "</td>
    <td>{$paidOn}</td>
	</tr>";
	}


	// ===== SUMMARY SECTION =====
	echo "
		<tr><td colspan='7' style='background:#f2f2f2; font-weight:bold; text-align:center;'>SUMMARY</td></tr>
		<tr>
			<td colspan='4'><strong>Grand Total Expense</strong></td>
			<td colspan='3'><strong>₹" . number_format($grandTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td colspan='4'><strong>Total Paid</strong></td>
			<td colspan='3' style='color:green;'><strong>₹" . number_format($paidTotal, 2) . "</strong></td>
		</tr>
		<tr>
			<td colspan='4'><strong>Total Unpaid</strong></td>
			<td colspan='3' style='color:red;'><strong>₹" . number_format($unpaidTotal, 2) . "</strong></td>
		</tr>

	</table>
	</body>
	</html>";

	exit;
}
