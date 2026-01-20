<?php

require_once __DIR__ . '/../core/config.php';

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
			'allotments.move_out_date',
			'allotments.created_at'
		);

		// Base query
		$query = "
		SELECT " . implode(", ", $columns) . "
		FROM allotments
		INNER JOIN flats ON allotments.flat_id = flats.id
		INNER JOIN users ON allotments.user_id = users.id
		LEFT JOIN maintenance_rates ON flats.flat_type = maintenance_rates.flat_type
		";

		// Filtering
		$filterQuery = '';
		if (!empty($_POST['search']['value'])) {
			$search = $_POST['search']['value'];
			$filterQuery = " WHERE (flats.flat_number LIKE :search OR users.name LIKE :search)";
		}

		// Count total records
		$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM allotments");
		$stmtTotal->execute();
		$recordsTotal = $stmtTotal->fetchColumn();

		// Count filtered records
		if ($filterQuery) {
			$stmtFiltered = $pdo->prepare("SELECT COUNT(*) FROM allotments
            INNER JOIN flats ON allotments.flat_id = flats.id
            INNER JOIN users ON allotments.user_id = users.id
            LEFT JOIN maintenance_rates ON flats.flat_type = maintenance_rates.flat_type
            $filterQuery");
			$stmtFiltered->execute([':search' => "%$search%"]);
			$recordsFiltered = $stmtFiltered->fetchColumn();
		} else {
			$recordsFiltered = $recordsTotal;
		}

		// Add filter to main query
		$query .= $filterQuery;

		// Ordering
		$orderColumnIndex = $_POST['order'][0]['column'];
		$orderColumn = $columns[$orderColumnIndex];
		$orderDir = $_POST['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
		$query .= " ORDER BY $orderColumn $orderDir";

		// Pagination
		$start = (int)$_POST['start'];
		$length = (int)$_POST['length'];
		$query .= " LIMIT $start, $length";

		// Fetch data
		$stmt = $pdo->prepare($query);
		if ($filterQuery) {
			$stmt->execute([':search' => "%$search%"]);
		} else {
			$stmt->execute();
		}
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Response
		$response = [
			"draw" => intval($_POST['draw']),
			"recordsTotal" => intval($recordsTotal),
			"recordsFiltered" => intval($recordsFiltered),
			"data" => $data
		];

		echo json_encode($response);
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

	if (
		isset($_GET['action'], $_GET['bill_id']) &&
		$_GET['action'] === 'mark_cash_payment' &&
		ctype_digit($_GET['bill_id'])
	) {

		if ($_SESSION['user_role'] !== 'admin') {
			exit('Unauthorized');
		}

		$billId = $_GET['bill_id'];

		$stmt = $pdo->prepare("
			SELECT id, total_amount, status
			FROM maintenance_bills
			WHERE id = ?
			");
		$stmt->execute([$billId]);
		$bill = $stmt->fetch();

		if (!$bill || $bill['status'] === 'paid') {
			$_SESSION['success'] = 'Bill already paid or invalid';
			header('Location: ' . $_SERVER['HTTP_REFERER']);
			exit();
		}

		$pdo->beginTransaction();

		$pdo->prepare("
			INSERT INTO maintenance_payments
			(maintenance_bill_id, payment_mode, paid_amount, paid_on, paid_by_admin)
			VALUES (?, 'cash', ?, NOW(), ?)
			")->execute([
			$billId,
			$bill['total_amount'],
			$_SESSION['user_id']
		]);


		$pdo->prepare("
			UPDATE maintenance_bills
			SET status = 'paid'
			WHERE id = ?
			")->execute([$billId]);

		$pdo->commit();

		$_SESSION['success'] = 'Cash payment marked successfully';
		header('Location: ' . $_SERVER['HTTP_REFERER']);
		exit();
	}

}


/* =========================================================
   MARK MAINTENANCE BILL AS CASH PAID
========================================================= */
if (
    isset($_GET['action'], $_GET['bill_id']) &&
    $_GET['action'] === 'mark_cash_payment' &&
    ctype_digit($_GET['bill_id'])
) {

    // Admin access check
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
        die('Unauthorized');
    }

    $billId = $_GET['bill_id'];

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

    // Generate CASH payment ID
    $paymentId = 'CASH-' . strtoupper(uniqid());

    try {
        $pdo->beginTransaction();

        // Insert payment (MATCHING TABLE)
        $stmt = $pdo->prepare("
            INSERT INTO maintenance_payments
            (maintenance_bill_id, payment_mode, payment_id, paid_on, created_at)
            VALUES (?, 'cash', ?, NOW(), NOW())
        ");
        $stmt->execute([
            $billId,
            $paymentId
        ]);

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

