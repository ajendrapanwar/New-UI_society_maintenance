<?php

require_once 'config.php';

if(isset($_POST['add_flats']))
{
	// Validate the form data
  	$flat_number = $_POST['flat_number'];
  	$floor = $_POST['floor'];
  	$block_number = $_POST['block_number'];
  	$flat_type = $_POST['flat_type'];
  	$created_at = date('Y-m-d H:i:s');

  	if (empty($flat_number)) 
  	{
	    $errors[] = 'Flat Number is required';
  	}
  	if (empty($floor)) 
  	{
	    $errors[] = 'Floor Number is required';
  	}
  	if(empty($flat_type))
  	{
  		$errors[] = 'Please Select Type';
  	}

  	// If the form data is valid, update the user's password
  	if (empty($errors)) 
  	{
  		$sql = "INSERT INTO flats (flat_number, floor, block_number, flat_type, created_at) VALUES (?, ?, ?, ?, ?)";

  		$pdo->prepare($sql)->execute([$flat_number, $floor, $block_number, $flat_type, $created_at]);

  		$_SESSION['success'] = 'New Flat Data Added';

  		header('location:flats.php');
  		exit();
  	}
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') 
{
  	header('Location: logout.php');
  	exit();
}

include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Add Flats</h1>
    <ol class="breadcrumb mb-4">
    	<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="flats.php">Flats Management</a></li>
        <li class="breadcrumb-item active">Add Flats Management</li>
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
				<h5 class="card-title">Add Flats</h5>
			</div>
			<div class="card-body">
				<form id="add-flat-form" method="POST">
				  	<div class="mb-3">
				    	<label for="flat-number" class="form-label">Flat Number</label>
				    	<input type="text" class="form-control" id="flat-number" name="flat_number">
				  	</div>
				  	<div class="mb-3">
				    	<label for="floor" class="form-label">Floor</label>
				    	<input type="number" class="form-control" id="floor" name="floor">
				  	</div>
				  	<div class="mb-3">
				    	<label for="floor" class="form-label">Block Number</label>
				    	<input type="text" class="form-control" id="block_number" name="block_number">
				  	</div>
				  	<div class="mb-3">
				    	<label for="floor" class="form-label">Type</label>
				    	<select name="flat_type" class="form-control">
				    		<option value="">Select Type</option>
				    		<?php 
				    		foreach($type as $t)
				    		{
				    			echo '<option value="'.$t.'">'.$t.'</option>';
				    		}
				    		?>
				    	</select>
				  	</div>
				  	<button type="submit" name="add_flats" class="btn btn-primary">Add Flat</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php

include('footer.php');

?>