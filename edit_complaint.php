<?php

require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'user')) 
{
  	header('Location: logout.php');
  	exit();
}

if(isset($_POST['edit_complaint']))
{
	// Validate the form data

	$description = $_POST['description'];
	$id = $_POST['id'];

  	if (empty($description)) 
  	{
	    $errors[] = 'Complaints Description is required';
  	}

  	// If the form data is valid, update the user's password
  	if (empty($errors)) 
  	{
  		$sql = "UPDATE complaints SET description = ? WHERE id = ?";

  		$pdo->prepare($sql)->execute([$description, $id]);

  		$_SESSION['success'] = 'Your Complaints has been edited';

  		header('location:complaints.php');
  		exit();
  	}
}

if(isset($_GET['id']))
{
	$stmt = $pdo->prepare('SELECT * FROM complaints WHERE id = ? AND master_comment = ?');
	$stmt->execute([$_GET['id'], ""]);
	$complaint = $stmt->fetch(PDO::FETCH_ASSOC);
}

include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Complaints</h1>
    <ol class="breadcrumb mb-4">
    	<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="complaints.php">Complaints Management</a></li>
        <li class="breadcrumb-item active">Edit Complaints</li>
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
				<h5 class="card-title">Edit Complaint</h5>
			</div>
			<div class="card-body">
				<form id="add-flat-form" method="POST">
				  	<div class="mb-3">
				    	<label for="description" class="form-label">Complaint Description</label>
				    	<textarea name="description" id="description" class="form-control" rows="5" placeholder="Enter Complaint Description"><?php echo (isset($complaint['description'])) ? $complaint['description'] : ''; ?></textarea>
				  	</div>
				  	<input type="hidden" name="id" value="<?php echo (isset($complaint['id'])) ? $complaint['id'] : ''; ?>" />
				  	<button type="submit" name="edit_complaint" class="btn btn-primary">Edit Complaint</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php

include('footer.php');

?>