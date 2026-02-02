<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../core/config.php';

// requireRole(['admin', 'cashier','user']);


if (isset($_POST['action'])) {

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

			$filterQuery = " WHERE (flat_number LIKE '%$search%' OR floor LIKE '%$search%' OR block_number LIKE '%$search%', OR flat_type LIKE '%$search%')";
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

	if ($_POST['action'] == 'fetch_users') {
		// Columns to fetch
		$columns = ['id', 'name', 'email', 'role', 'mobile', 'dob', 'gender'];

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
			"data" => $data
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
		$_SESSION['success'] = 'Bill already paid';
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

		$_SESSION['success'] = 'Cash payment marked successfully';
	} catch (Exception $e) {
		$pdo->rollBack();
		die('Payment failed: ' . $e->getMessage());
	}

	header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? BASE_URL . 'dashboard.php'));
	exit();
}

// MARK MAINTENANCE BILL AS ONLINE PAID
if (isset($_POST['action']) && $_POST['action'] === 'mark_online_payment') {

	requireRole(['admin', 'cashier']);

	$billId        = $_POST['bill_id'] ?? '';
	$paymentMode   = 'online'; // FIXED: hardcoded
	$paymentMethod = $_POST['payment_method'] ?? '';
	$note          = trim($_POST['note'] ?? '');

	// ===== VALIDATION =====
	if (
		!ctype_digit($billId) ||
		$paymentMethod === '' ||
		!isset($_FILES['proof'])
	) {
		http_response_code(400);
		exit('Invalid data');
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

		$stmt->execute([
			$billId,
			$paymentMode,     // online
			$paymentMethod,   // upi / debit_card / etc
			$newFileName,
			$note
		]);

		// UPDATE BILL STATUS
		$stmt = $pdo->prepare("
            UPDATE maintenance_bills
            SET status = 'paid'
            WHERE id = ?
        ");
		$stmt->execute([$billId]);

		$pdo->commit();
		echo 'success';
	} catch (Exception $e) {
		$pdo->rollBack();
		http_response_code(500);
		exit($e->getMessage());
	}

	exit;
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

		$data[] = [
			'month_year'   => $monthName . ' ' . $r['bill_year'],
			'amount'       => '₹' . number_format($r['amount'], 2),
			'fine'         => '₹' . number_format($r['fine_amount'], 2),
			'total'        => '<strong>₹' . number_format($r['total_amount'], 2) . '</strong>',
			'status'       => '<span class="badge bg-' . ($r['status'] === 'paid' ? 'success' : 'warning') . '">' . ucfirst($r['status']) . '</span>',
			'payment_mode' => $paymentText,
			'paid_on'      => $r['paid_on'] ? date('d-m-Y H:i', strtotime($r['paid_on'])) : '-',
			'overdue'      => $r['status'] === 'overdue' ? 'Yes' : 'No',
			'action' => (
				$r['status'] !== 'paid' &&
				in_array($_SESSION['user_role'], ['admin', 'cashier'])
			)
				? '
					<select 
						class="form-select form-select-sm payment-type"
						data-bill="' . $r['id'] . '"
						style="min-width:110px">
						<option value="">Payment</option>
						<option value="cash">Cash</option>
						<option value="online">Online</option>
					</select>'
				: '<span class="text-muted" style="margin-left: 5px;">Paid</span>'
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
			'status'       => $bill['status'],
			'payment_mode' => $paymentText,
			'paid_on'      => $bill['paid_on'] ? date('d-m-Y H:i', strtotime($bill['paid_on'])) : '-',
			'overdue'      => $isOverdue ? 'Yes' : 'No',
			'action'       => $bill['status'] === 'paid'
				? '<a href="pay_Details.php?bill_id=' . $bill['maintenance_bill_id'] . '" class="btn btn-sm btn-info">View</a>'
				: '<span class="badge bg-warning p-2">Pending</span>'
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

// EXPORT ALL BILLS TO EXCEL
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
			mp.payment_mode,
			mp.paid_on,
			CASE 
				WHEN mb.status != 'paid' AND mb.due_date < CURDATE() THEN 'Yes'
				ELSE 'No'
			END AS overdue
		FROM maintenance_bills mb
		JOIN flats f ON f.id = mb.flat_id
		LEFT JOIN maintenance_payments mp 
			ON mp.maintenance_bill_id = mb.id
		$whereSql
		ORDER BY mb.bill_year DESC, mb.bill_month DESC
	");

	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// ===== Excel Headers =====
	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=maintenance_bills.xls");
	header("Pragma: no-cache");
	header("Expires: 0");

	echo "Flat\tBlock\tMonth/Year\tAmount\tFine\tTotal\tStatus\tPayment Mode\tPaid On\tOverdue\n";

	foreach ($rows as $row) {
		echo implode("\t", [
			$row['flat_number'],
			$row['block_number'],
			$row['month_year'],
			$row['amount'],
			$row['fine_amount'],
			$row['total_amount'],
			ucfirst($row['status']),
			$row['payment_mode'] ?? '-',
			$row['paid_on'] ?? '-',
			$row['overdue']
		]) . "\n";
	}

	exit;
}




// Guards Fetch
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

		$data[] = [
			'month_year' => date('F', mktime(0, 0, 0, $r['month'], 1)) . ' ' . $r['year'],
			'reading'    => $r['reading'],
			'amount'     => '₹' . number_format($r['amount'], 2),
			'paid'       => '₹' . number_format($r['paid_amount'], 2),
			'pending'    => '₹' . number_format($r['pending'], 2),
			'status'     => '<span class="badge bg-' .
				($r['status'] == 'paid' ? 'success' : ($r['status'] == 'partial' ? 'info' : 'warning')) .
				'">' . ucfirst($r['status']) . '</span>',
			'last_paid'  => $r['last_paid_on'] ? date('d-M-Y', strtotime($r['last_paid_on'])) : '-',
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

// EXPORT ELECTRICITY BILLS TO EXCEL
if (isset($_GET['action']) && $_GET['action'] === 'electricity_bills_export_excel') {

	requireRole(['admin', 'cashier']);

	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=electricity_bills_" . date('Ymd_His') . ".xls");

	// Get filters from GET
	$month  = $_GET['month'] ?? '';
	$year   = $_GET['year'] ?? '';
	$status = $_GET['status'] ?? '';

	$query = "SELECT month, year, amount, paid_amount, (amount - paid_amount) AS pending, status, last_paid_on FROM electricity_bills WHERE 1";
	$params = [];

	if (!empty($month)) {
		$query .= " AND month = :month";
		$params[':month'] = $month;
	}
	if (!empty($year)) {
		$query .= " AND year = :year";
		$params[':year'] = $year;
	}
	if (!empty($status)) {
		$query .= " AND status = :status";
		$params[':status'] = $status;
	}

	$stmt = $pdo->prepare($query);
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo "<table border='1'>";
	echo "<tr>
            <th>Month</th>
            <th>Year</th>
            <th>Total Amount</th>
            <th>Paid</th>
            <th>Pending</th>
            <th>Status</th>
            <th>Last Paid</th>
          </tr>";

	foreach ($rows as $r) {
		echo "<tr>";
		echo "<td>" . date('F', mktime(0, 0, 0, $r['month'], 1)) . "</td>";
		echo "<td>{$r['year']}</td>";
		echo "<td>₹" . number_format($r['amount'], 2) . "</td>";
		echo "<td>₹" . number_format($r['paid_amount'], 2) . "</td>";
		echo "<td>₹" . number_format($r['pending'], 2) . "</td>";
		echo "<td>" . ucfirst($r['status']) . "</td>";
		echo "<td>" . ($r['last_paid_on'] ? date('d-M-Y', strtotime($r['last_paid_on'])) : '—') . "</td>";
		echo "</tr>";
	}

	echo "</table>";
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
			'paid_on'    => $r['paid_on'] ? date('d-m-Y H:i', strtotime($r['paid_on'])) : '-',
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

// EXPORT GUARD SALARY
if (isset($_GET['action']) && $_GET['action'] == 'export_guard_salary') {

	requireRole(['admin', 'cashier']);

	$month  = $_GET['month'] ?? '';
	$year   = $_GET['year'] ?? '';
	$status = $_GET['status'] ?? '';

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

	$stmt = $pdo->prepare("
        SELECT g.name, g.mobile, gs.salary_month, gs.salary_year, gs.salary_amount, gs.status, gs.paid_on
        FROM guard_salary gs
        JOIN security_guards g ON g.id = gs.guard_id
        $where
        ORDER BY gs.salary_year DESC, gs.salary_month DESC
    ");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=guard_salary.xls");

	echo "Name\tMobile\tMonth\tYear\tSalary\tStatus\tPaid On\n";

	foreach ($rows as $r) {
		echo "{$r['name']}\t{$r['mobile']}\t{$r['salary_month']}\t{$r['salary_year']}\t{$r['salary_amount']}\t{$r['status']}\t{$r['paid_on']}\n";
	}
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
			'paid_on' => $r['paid_on'] ? date('d-m-Y H:i', strtotime($r['paid_on'])) : '-',
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

// EXPORT GARBAGE SALARY
if (isset($_GET['action']) && $_GET['action'] == "export_garbage_salary") {

	requireRole(['admin', 'cashier']);

	$month = $_GET['month'] ?? '';
	$year = $_GET['year'] ?? '';
	$status = $_GET['status'] ?? '';

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

	$stmt = $pdo->prepare("
	SELECT gc.name,gc.mobile,gs.salary_month,gs.salary_year,gs.salary_amount,gs.status,gs.paid_on
	FROM garbage_salary gs
	JOIN garbage_collectors gc ON gc.id=gs.collector_id
	$where
	");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=garbage_salary.xls");

	echo "Name\tMobile\tMonth\tYear\tSalary\tStatus\tPaid On\n";
	foreach ($rows as $r) {
		echo "{$r['name']}\t{$r['mobile']}\t{$r['salary_month']}\t{$r['salary_year']}\t{$r['salary_amount']}\t{$r['status']}\t{$r['paid_on']}\n";
	}
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
			'paid_on' => $r['paid_on'] ? date('d-m-Y H:i', strtotime($r['paid_on'])) : '-',
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

// EXPORT SWEEPER SALARY
if (isset($_GET['action']) && $_GET['action'] == "export_sweeper_salary") {

	requireRole(['admin', 'cashier']);

	$month = $_GET['month'] ?? '';
	$year = $_GET['year'] ?? '';
	$status = $_GET['status'] ?? '';

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

	$stmt = $pdo->prepare("
	SELECT s.name,s.mobile,ss.salary_month,ss.salary_year,ss.salary_amount,ss.status,ss.paid_on
	FROM sweeper_salary ss
	JOIN sweepers s ON s.id=ss.sweeper_id
	$where
	");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=sweeper_salary.xls");

	echo "Name\tMobile\tMonth\tYear\tSalary\tStatus\tPaid On\n";
	foreach ($rows as $r) {
		echo "{$r['name']}\t{$r['mobile']}\t{$r['salary_month']}\t{$r['salary_year']}\t{$r['salary_amount']}\t{$r['status']}\t{$r['paid_on']}\n";
	}
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
			'created_at' => date('d-m-Y', strtotime($r['created_at']))
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

// EXPORT Miscellaneous Work
if (isset($_GET['action']) && $_GET['action'] == 'export_misc_work') {

	requireRole(['admin', 'cashier']);

	$month = $_GET['month'] ?? '';
	$year = $_GET['year'] ?? '';

	$where = " WHERE 1 ";
	$params = [];

	if ($month != '') {
		$where .= " AND month=? ";
		$params[] = $month;
	}
	if ($year != '') {
		$where .= " AND year=? ";
		$params[] = $year;
	}

	$stmt = $pdo->prepare("SELECT * FROM miscellaneous_works $where ORDER BY year DESC, month DESC");
	$stmt->execute($params);
	$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=misc_work.xls");

	echo "Title\tWorker\tContact\tAmount\tMonth\tYear\tDate\n";

	foreach ($rows as $r) {
		$m = date('F', mktime(0, 0, 0, $r['month'], 1));
		echo "{$r['work_title']}\t{$r['worker_name']}\t{$r['contact_number']}\t{$r['amount']}\t$m\t{$r['year']}\t" . date('d-m-Y', strtotime($r['created_at'])) . "\n";
	}
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

    /* ================= FILTER CONDITIONS ================= */
    $where = " WHERE 1=1 ";
    $params = [];

    if (ctype_digit($month)) {
        $where .= " AND month = :month ";
        $params[':month'] = $month;
    }

    if (ctype_digit($year)) {
        $where .= " AND year = :year ";
        $params[':year'] = $year;
    }

    if (in_array($status, ['paid','unpaid'])) {
        $where .= " AND status = :status ";
        $params[':status'] = $status;
    }

    /* ================= FETCH ALL EXPENSES ================= */
    $sql = "
    SELECT id, month, year, reading AS name, amount, status, 'electricity_bills' AS source_table
    FROM electricity_bills
    WHERE 1=1
    " . ($month ? " AND MONTH(created_at) = :month " : "") . ($year ? " AND YEAR(created_at) = :year " : "") . ($status ? " AND status = :status " : "") . "

    UNION ALL

    SELECT id, month, year, worker_name AS name, amount, status, 'miscellaneous_works' AS source_table
    FROM miscellaneous_works
    WHERE 1=1
    " . ($month ? " AND month = :month " : "") . ($year ? " AND year = :year " : "") . ($status ? " AND status = :status " : "") . "

    UNION ALL

    SELECT ss.id, ss.salary_month AS month, ss.salary_year AS year,
           sw.name AS name, ss.salary_amount AS amount, ss.status, 'sweeper_salary' AS source_table
    FROM sweeper_salary ss
    JOIN sweepers sw ON sw.id = ss.sweeper_id
    WHERE 1=1
    " . ($month ? " AND ss.salary_month = :month " : "") . ($year ? " AND ss.salary_year = :year " : "") . ($status ? " AND ss.status = :status " : "") . "

    UNION ALL

    SELECT gs.id, gs.salary_month AS month, gs.salary_year AS year,
           sg.name AS name, gs.salary_amount AS amount, gs.status, 'guard_salary' AS source_table
    FROM guard_salary gs
    JOIN security_guards sg ON sg.id = gs.guard_id
    WHERE 1=1
    " . ($month ? " AND gs.salary_month = :month " : "") . ($year ? " AND gs.salary_year = :year " : "") . ($status ? " AND gs.status = :status " : "") . "

    UNION ALL

    SELECT gc.id, gc.salary_month AS month, gc.salary_year AS year,
           gcol.name AS name, gc.salary_amount AS amount, gc.status, 'garbage_salary' AS source_table
    FROM garbage_salary gc
    JOIN garbage_collectors gcol ON gcol.id = gc.collector_id
    WHERE 1=1
    " . ($month ? " AND gc.salary_month = :month " : "") . ($year ? " AND gc.salary_year = :year " : "") . ($status ? " AND gc.status = :status " : "") . "

    ORDER BY year DESC, month DESC
    LIMIT :start, :length
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    $grandTotal = $paidTotal = $unpaidTotal = 0;

    foreach ($rows as $r) {
        $monthName = date('F', mktime(0,0,0,$r['month'],1));
        $monthYear = $monthName . ' ' . $r['year'];
        $amount = $r['amount'];
        $statusSimple = ($r['status'] === 'paid') ? 'paid' : 'unpaid';

        $grandTotal += $amount;
        if ($statusSimple === 'paid') $paidTotal += $amount;
        else $unpaidTotal += $amount;

        $data[] = [
            'id' => $r['id'],
            'month_year' => $monthYear,
            'name' => $r['name'],
            'amount' => $amount,
            'status' => "<span class='badge bg-".($statusSimple==='paid'?'success':'danger')."'>".ucfirst($statusSimple)."</span>",
            'source' => $r['source_table']
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => count($data),
        'recordsFiltered' => count($data),
        'data' => $data,
        'grandTotal' => $grandTotal,
        'paidTotal' => $paidTotal,
        'unpaidTotal' => $unpaidTotal
    ]);
    exit;
}

// EXPORT EXPENSES
if (isset($_GET['action']) && $_GET['action'] === 'export_expense_bills') {

    requireRole(['admin','cashier']);

    $month  = $_GET['month'] ?? '';
    $year   = $_GET['year'] ?? '';
    $status = $_GET['status'] ?? '';

    $where = " WHERE 1=1 ";
    $params = [];

    if (ctype_digit($month)) { $where .= " AND month = :month "; $params[':month'] = $month; }
    if (ctype_digit($year))  { $where .= " AND year = :year "; $params[':year'] = $year; }
    if (in_array($status,['paid','unpaid'])) { $where .= " AND status = :status "; $params[':status'] = $status; }

    $sql = "
    SELECT id, month, year, reading AS name, amount, status, 'electricity_bills' AS source_table
    FROM electricity_bills
    WHERE 1=1
    " . ($month ? " AND MONTH(created_at) = :month " : "") . ($year ? " AND YEAR(created_at) = :year " : "") . ($status ? " AND status = :status " : "") . "

    UNION ALL

    SELECT id, month, year, worker_name AS name, amount, status, 'miscellaneous_works' AS source_table
    FROM miscellaneous_works
    WHERE 1=1
    " . ($month ? " AND month = :month " : "") . ($year ? " AND year = :year " : "") . ($status ? " AND status = :status " : "") . "

    UNION ALL

    SELECT ss.id, ss.salary_month AS month, ss.salary_year AS year,
           sw.name AS name, ss.salary_amount AS amount, ss.status, 'sweeper_salary' AS source_table
    FROM sweeper_salary ss
    JOIN sweepers sw ON sw.id = ss.sweeper_id
    WHERE 1=1
    " . ($month ? " AND ss.salary_month = :month " : "") . ($year ? " AND ss.salary_year = :year " : "") . ($status ? " AND ss.status = :status " : "") . "

    UNION ALL

    SELECT gs.id, gs.salary_month AS month, gs.salary_year AS year,
           sg.name AS name, gs.salary_amount AS amount, gs.status, 'guard_salary' AS source_table
    FROM guard_salary gs
    JOIN security_guards sg ON sg.id = gs.guard_id
    WHERE 1=1
    " . ($month ? " AND gs.salary_month = :month " : "") . ($year ? " AND gs.salary_year = :year " : "") . ($status ? " AND gs.status = :status " : "") . "

    UNION ALL

    SELECT gc.id, gc.salary_month AS month, gc.salary_year AS year,
           gcol.name AS name, gc.salary_amount AS amount, gc.status, 'garbage_salary' AS source_table
    FROM garbage_salary gc
    JOIN garbage_collectors gcol ON gcol.id = gc.collector_id
    WHERE 1=1
    " . ($month ? " AND gc.salary_month = :month " : "") . ($year ? " AND gc.salary_year = :year " : "") . ($status ? " AND gc.status = :status " : "") . "

    ORDER BY year DESC, month DESC
    ";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) { $stmt->bindValue($k,$v); }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ===== Excel Headers =====
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=expense_bills.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "ID\tMonth/Year\tName/File\tAmount\tStatus\tSource Table\n";

    foreach ($rows as $row) {
        $monthName = date('F', mktime(0,0,0,$row['month'],1));
        echo implode("\t", [
            $row['id'],
            $monthName.' '.$row['year'],
            $row['name'],
            $row['amount'],
            ucfirst($row['status']),
            $row['source_table']
        ]) . "\n";
    }

    exit;
}
