<?php

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') 
{
  	header('Location: logout.php');
  	exit();
}

if(isset($_POST['edit_visitor']))
{
	$flat_id = trim($_POST['flat_id']);
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $person_to_meet = trim($_POST['person_to_meet']);
    $reason = trim($_POST['reason']);
    $in_datetime = trim($_POST['in_datetime']);
    $id = $_POST['id'];

    // Validate form fields
    if (empty($flat_id)) {
        $errors[] = 'Flat ID is required';
    }

    if (empty($name)) {
        $errors[] = 'Name is required';
    }

    if (empty($phone)) {
        $errors[] = 'Phone is required';
    }
    else
    {
    	// assume $phone contains the phone number input from the form
		$regex = "/^[0-9]{10}$/"; // regular expression to match 10 digits
		if(!preg_match($regex, $phone)) 
		{
		  	// phone number is invalid
		  	$errors[] = 'Phone number is invalid';
		}
    }

    if (empty($address)) {
        $errors[] = 'Address is required';
    }

    if (empty($person_to_meet)) {
        $errors[] = 'Person to meet is required';
    }

    if (empty($reason)) {
        $errors[] = 'Reason is required';
    }

    if (empty($in_datetime)) {
        $errors[] = 'In date and time is required';
    }

    // Insert visitor data if there are no validation errors
    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE visitors SET flat_id = ?, name = ?, phone = ?, address = ?, person_to_meet = ?, reason = ?, in_datetime = ? WHERE id = ?');
        $stmt->execute([$flat_id, $name, $phone, $address, $person_to_meet, $reason, $in_datetime, $id]);

        $_SESSION['success'] = 'Visitor Data has been Edited';

        header('Location: visitors.php');
        exit();
    }
}

if(isset($_GET['id']))
{
	// Prepare a SELECT statement to retrieve the visitor's details
  	$stmt = $pdo->prepare("SELECT * FROM visitors WHERE id = ? AND out_datetime IS NULL");
  	$stmt->execute([$_GET['id']]);

  	// Fetch the visitor's details from the database
  	$visitor = $stmt->fetch(PDO::FETCH_ASSOC);
}


include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Visitors</h1>
    <ol class="breadcrumb mb-4">
    	<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="complaints.php">Visitors Management</a></li>
        <li class="breadcrumb-item active">Edit Visitor</li>
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
				<h5 class="card-title">Edit Visitor</h5>
			</div>
			<div class="card-body">
				<form method="post">
				  	<div class="mb-3">
					    <label for="flat_id">Flat Number</label>
					    <select id="flat_id" name="flat_id" class="form-control">
					    	<option value="">Select Flat</option>
						    <?php
						      	// Query to get flat numbers from flats table
						    	$stmt = $pdo->prepare('SELECT id, flat_number, block_number FROM flats ORDER BY flat_number ASC');
								$stmt->execute([$_SESSION['user_id']]);
								$flats_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
						      	foreach($flats_result as $flat)
						      	{
						        	echo "<option value=\"" . $flat['id'] . "\">" . $flat['block_number'] . ' - ' . $flat['flat_number'] . "</option>";
						      	}
						    ?>
					    </select>
				  	</div>
				  	<div class="mb-3">
				    	<label for="name">Visitor Name</label>
				    	<input type="text" id="name" name="name" class="form-control" value="<?php echo (isset($visitor['name'])) ? $visitor['name'] : ''; ?>">
				  	</div>
				  	<div class="mb-3">
				    	<label for="phone">Phone Number</label>
				    	<input type="text" id="phone" name="phone" class="form-control" value="<?php echo (isset($visitor['phone'])) ? $visitor['phone'] : ''; ?>">
				  	</div>
				  	<div class="mb-3">
				    	<label for="address">Address</label>
				    	<textarea id="address" name="address" class="form-control"><?php echo (isset($visitor['address'])) ? $visitor['address'] : ''; ?></textarea>
				  	</div>
				  	<div class="mb-3">
				    	<label for="person_to_meet">Person to Meet</label>
				    	<input type="text" id="person_to_meet" name="person_to_meet" class="form-control" value="<?php echo (isset($visitor['person_to_meet'])) ? $visitor['person_to_meet'] : ''; ?>">
				  	</div>
				  	<div class="mb-3">
				    	<label for="reason">Reason for Visit</label>
				    	<textarea id="reason" name="reason" class="form-control"><?php echo (isset($visitor['reason'])) ? $visitor['reason'] : ''; ?></textarea>
				  	</div>
				  	<div class="mb-3">
				    	<label for="in_datetime">In Date/Time</label>
				    	<input type="datetime-local" id="in_datetime" name="in_datetime" class="form-control" value="<?php echo (isset($visitor['in_datetime'])) ? $visitor['in_datetime'] : ''; ?>">
				  	</div>
				  	<input type="hidden" name="id" value="<?php echo (isset($visitor['id'])) ? $visitor['id'] : ''; ?>" />
				  	<button type="submit" name="edit_visitor" class="btn btn-primary">Edit Visitor</button>
				  	<script>
				  		$('#flat_id').val('<?php echo (isset($visitor['flat_id'])) ? $visitor['flat_id'] : ''; ?>')
				  	</script>
				</form>
			</div>
		</div>
	</div>
</div>

<?php

include('footer.php');

?>