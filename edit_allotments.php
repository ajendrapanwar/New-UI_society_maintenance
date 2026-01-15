<?php

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') 
{
  	header('Location: logout.php');
  	exit();
}

if(isset($_POST['edit_allotment']))
{
	// Get the form data
    $user_id = $_POST['user_id'];
    $flat_id = $_POST['flat_id'];
    $move_in_date = $_POST['move_in_date'];
    $move_out_date = $_POST['move_out_date'];
    $id = $_POST['id'];

  	// Validate the form data
    if (empty($user_id)) {
        $errors[] = 'User is required';
    }
    if (empty($flat_id)) {
        $errors[] = 'Flat is required';
    }
    if (empty($move_in_date)) {
        $errors[] = 'Move-in date is required';
    }

  	// If the form data is valid, update the user's password
  	if (empty($errors)) 
  	{
  		$stmt = $pdo->prepare('UPDATE allotments SET user_id = ?, flat_id = ?, move_in_date = ?, move_out_date = ? WHERE id = ?');
        
        $stmt->execute([$user_id, $flat_id, $move_in_date, $move_out_date, $id]);

        $_SESSION['success'] = 'Allotment Data has been edited';
        
        header('Location: allotments.php');
        
        exit();
  	}
}

// Prepare a SELECT statement to retrieve the flats's details
$stmt = $pdo->prepare("SELECT * FROM flats ORDER BY id DESC");
$stmt->execute();
// Fetch the flat's details from the database
$flats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare a SELECT statement to retrieve the user's details
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY name ASC");
$stmt->execute();
// Fetch the user's details from the database
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(isset($_GET['id']))
{
    // Prepare a SELECT statement to retrieve the allotments's details
    $stmt = $pdo->prepare("SELECT * FROM allotments WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    // Fetch the flat's details from the database
    $allotments = $stmt->fetch(PDO::FETCH_ASSOC);
}

include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Allotments</h1>
    <ol class="breadcrumb mb-4">
    	<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="allotments.php">Allotments Management</a></li>
        <li class="breadcrumb-item active">Edit Allotments Management</li>
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
				<h5 class="card-title">Edit Allotments</h5>
			</div>
			<div class="card-body">
				<form method="post">
  					<div class="mb-3">
    					<label for="user_id">User</label>
    					<select class="form-control" name="user_id" id="user_id">
      						<option value="">-- Select User --</option>
      						<?php foreach($users as $user): ?>
      						<option value="<?php echo $user['id']; ?>"><?php echo $user['name']; ?></option>
      						<?php endforeach; ?>
    					</select>
  					</div>
  					<div class="mb-3">
    					<label for="flat_id">Flat</label>
    					<select class="form-control" name="flat_id" id="flat_id">
      						<option value="">-- Select Flat --</option>
      						<?php foreach($flats as $flat): ?>
      						<option value="<?php echo $flat['id']; ?>"><?php echo $flat['block_number'] . ' - ' . $flat['flat_number']; ?></option>
      						<?php endforeach; ?>
    					</select>
  					</div>
  					<div class="mb-3">
    					<label for="move_in_date">Move In Date</label>
    					<input type="date" class="form-control" name="move_in_date" id="move_in_date" value="<?php echo (isset($allotments['move_in_date'])) ? $allotments['move_in_date'] : ''; ?>">
  					</div>
  					<div class="mb-3">
    					<label for="move_out_date">Move Out Date</label>
    					<input type="date" class="form-control" name="move_out_date" id="move_out_date" value="<?php echo (isset($allotments['move_out_date'])) ? $allotments['move_out_date'] : ''; ?>">
  					</div>

                    <input type="hidden" name="id" value="<?php echo (isset($allotments['id'])) ? $allotments['id'] : ''; ?>" />

  					<button type="submit" name="edit_allotment" class="btn btn-primary">Edit Allotment</button>
				</form>
                <script>

                    $('#user_id').val("<?php echo (isset($allotments['user_id'])) ? $allotments['user_id'] : ''; ?>");

                    $('#flat_id').val("<?php echo (isset($allotments['flat_id'])) ? $allotments['flat_id'] : ''; ?>");

                </script>
			</div>
		</div>
	</div>
</div>

<?php

include('footer.php');

?>


