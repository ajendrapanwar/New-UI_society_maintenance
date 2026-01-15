<?php

require_once 'config.php';

if(isset($_POST['add_bill']))
{
	// Validate the form data
	$bill_title = $_POST['bill_title'];
  	$flat_id = $_POST['flat_id'];
  	$amount = $_POST['amount'];
  	$month = $_POST['month'];

  	if(empty($bill_title))
  	{
  		$errors[] = 'Please Define Bill Title';
  	}

  	if (empty($flat_id)) 
  	{
	    $errors[] = 'Please Select Flat Number';
  	}
  	if (empty($amount)) 
  	{
    	$errors[] = 'Please enter Bill Amount';
  	} 
  	else if (!is_numeric($amount)) 
  	{
    	$errors[] = 'Amount must be a number';
  	}
  	if (empty($month)) 
  	{
 	   $errors[] = 'Please Bill Month';
  	}

  	// If the form data is valid, update the user's password
  	if (empty($errors)) 
  	{
  		// Insert bill data into the database
	    $stmt = $pdo->prepare("INSERT INTO bills (flat_id, bill_title, amount, month) VALUES (?, ?, ?, ?)");

	    $stmt->execute([$flat_id, $bill_title, $amount, $month]);

	    // get last inserted ID
		$bill_id = $pdo->lastInsertId();

	    $user_id = $pdo->query("SELECT user_id FROM allotments WHERE flat_id = '".$flat_id."'")->fetchColumn();

	    // insert notification data into notifications table
		$message = "New bill added. Amount: ".$bill_amount.", Month: ".$bill_month."";
		
		$notification_link = 'bill_payment.php?id='.$bill_id.'&action=notification';
		$stmt = $pdo->prepare("INSERT INTO notifications (user_id, notiification_type, event_id, message, link) VALUES (?, ?, ?, ?, ?)");
		$stmt->execute([$user_id, 'Bill', $bill_id, $message, $notification_link]);

  		$_SESSION['success'] = 'New Bill Data Added';

  		header('location:bills.php');
  		exit();
  	}
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') 
{
  	header('Location: logout.php');
  	exit();
}

$sql = "SELECT id, flat_number, block_number FROM flats ORDER BY id DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute();

$flats = $stmt->fetchAll(PDO::FETCH_ASSOC);

include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Add Bill Data</h1>
    <ol class="breadcrumb mb-4">
    	<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="bills.php">Bills Management</a></li>
        <li class="breadcrumb-item active">Add Bill Data</li>
    </ol>
	<div class="col-md-4">
		<?php

		if(isset($errors))
        {
            foreach ($errors as $error) 
            {
                echo "<div class='alert alert-danger'>$error</div>";
            }
        }

		?>
		<div class="card">
			<div class="card-header">
				<h5 class="card-title">Add Bill Data</h5>
			</div>
			<div class="card-body">
				<form method="post">
					<div class="mb-3">
				    	<label for="bill_title">Bill Title</label>
				    	<input type="text" id="bill_title" name="bill_title" class="form-control" >
				  	</div>
				  	<div class="mb-3">
				    	<label for="name">Flat Number</label>
				    	<select name="flat_id" class="form-control">
				    		<option value="">Select Flat Number</option>
				    		<?php foreach($flats as $flat): ?>
				    		<option value="<?php echo $flat['id']; ?>"><?php echo $flat['block_number'] . ' - ' . $flat['flat_number']; ?></option>
				    		<?php endforeach; ?>
				    	</select>
				  	</div>
				  	<div class="mb-3">
				    	<label for="amount">Amount</label>
				    	<input type="number" id="amount" name="amount" class="form-control" step="0.01">
				  	</div>
				  	<div class="mb-3">
				    	<label for="month">Month</label>
				    	<input type="month" id="month" name="month" class="form-control">
				  	</div>
				  	<button type="submit" name="add_bill" class="btn btn-primary">Add Bill</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php

include('footer.php');

?>