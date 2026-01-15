<?php

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') 
{
  	header('Location: logout.php');
  	exit();
}

if(isset($_POST['edit_flats']))
{
	// Validate the form data
  	$flat_number = $_POST['flat_number'];
  	$floor = $_POST['floor'];
  	$block_number = $_POST['block_number'];
  	$flat_type = $_POST['flat_type'];
  	$id = $_POST['id'];

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
  		$sql = "UPDATE flats SET flat_number = ?, floor = ?, block_number = ?, flat_type = ? WHERE id = ?";

  		$pdo->prepare($sql)->execute([$flat_number, $floor, $block_number, $flat_type, $id]);

  		$_SESSION['success'] = 'Flat Data Edit';

  		header('location:flats.php');
  		exit();
  	}
}

if(isset($_GET['id']))
{
	// Prepare a SELECT statement to retrieve the flats's details
  	$stmt = $pdo->prepare("SELECT * FROM flats WHERE id = ?");
  	$stmt->execute([$_GET['id']]);

  	// Fetch the user's details from the database
  	$flat = $stmt->fetch(PDO::FETCH_ASSOC);
}

include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Flats</h1>
    <ol class="breadcrumb mb-4">
    	<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="flats.php">Flats Management</a></li>
        <li class="breadcrumb-item active">Edit Flats Management</li>
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
				<h5 class="card-title">Edit Flats Data</h5>
			</div>
			<div class="card-body">
				<form id="add-flat-form" method="POST">
				  	<div class="mb-3">
				    	<label for="flat-number" class="form-label">Flat Number</label>
				    	<input type="text" class="form-control" id="flat-number" name="flat_number" value="<?php echo (isset($flat['flat_number'])) ? $flat['flat_number'] : ''; ?>">
				  	</div>
				  	<div class="mb-3">
				    	<label for="floor" class="form-label">Floor</label>
				    	<input type="number" class="form-control" id="floor" name="floor" value="<?php echo (isset($flat['floor'])) ? $flat['floor'] : ''; ?>">
				  	</div>
				  	<div class="mb-3">
				    	<label for="floor" class="form-label">Block Number</label>
				    	<input type="text" class="form-control" id="block_number" name="block_number" value="<?php echo (isset($flat['block_number'])) ? $flat['block_number'] : ''; ?>">
				  	</div>
				  	<div class="mb-3">
				    	<label for="floor" class="form-label">Type</label>
				    	<select name="flat_type" id="flat_type" class="form-control">
				    		<option value="">Select Type</option>
				    		<?php 
				    		foreach($type as $t)
				    		{
				    			echo '<option value="'.$t.'">'.$t.'</option>';
				    		}
				    		?>
				    	</select>
				  	</div>
				  	<input type="hidden" name="id" value="<?php echo (isset($flat['id'])) ? $flat['id'] : ''; ?>" />
				  	<button type="submit" name="edit_flats" class="btn btn-primary">Edit Flat</button>
				  	<script>
				  		$('#flat_type').val('<?php echo (isset($flat['flat_type'])) ? $flat['flat_type'] : ''; ?>');
				  	</script>
				</form>
			</div>
		</div>
	</div>
</div>

<?php

include('footer.php');

?>