<?php

require_once 'config.php';

if(isset($_POST['btn_change_password']))
{
	// Validate the form data
  	$current_password = $_POST['current_password'];
  	$new_password = $_POST['new_password'];
  	$confirm_password = $_POST['confirm_password'];

  	if (empty($current_password)) 
  	{
	    $errors[] = 'Current password is required';
  	}
  	if (empty($new_password)) 
  	{
	    $errors[] = 'New password is required';
  	} 
  	elseif (strlen($new_password) < 6) 
  	{
    	$errors[] = 'New password must be at least 6 characters long';
  	}
  	if (empty($confirm_password)) 
  	{
    	$errors[] = 'Confirm password is required';
  	} 
  	elseif ($new_password !== $confirm_password) 
  	{
    	$errors[] = 'New password and confirm password do not match';
  	}

  	// If the form data is valid, update the user's password
  	if (empty($errors)) 
  	{
	    $user_id = $_SESSION['user_id'];
    	$sql = "SELECT password FROM users WHERE id = ?";
    	$stmt = $pdo->prepare($sql);
    	$stmt->execute([$user_id]);
    	$row = $stmt->fetch(PDO::FETCH_ASSOC);

    	if ($row && password_verify($current_password, $row['password'])) 
    	{
      		$new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
      		$sql = "UPDATE users SET password = ? WHERE id = ?";
      		$stmt = $pdo->prepare($sql);
      		$stmt->execute([$new_password_hash, $user_id]);
      		$_SESSION['success'] = 'Password changed successfully';
    	} 
    	else 
    	{
      		$errors[] = 'Current password is incorrect';
    	}
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
    <h1 class="mt-4">Profile</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Change Password</li>
    </ol>
	<div class="col-md-4">
		<?php

		if(isset($_SESSION['success']))
		{
			echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';

			unset($_SESSION['success']);
		}

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
				<h5 class="card-title">Change Password</h5>
			</div>
			<div class="card-body">
				<form method="post">
				  	<div class="mb-3">
					    <label for="current-password">Current Password</label>
					    <input type="password" class="form-control" id="current-password" name="current_password">
					</div>
				  	<div class="mb-3">
				    	<label for="new-password">New Password</label>
				    	<input type="password" class="form-control" id="new-password" name="new_password">
				  	</div>
				  	<div class="mb-3">
				    	<label for="confirm-password">Confirm New Password</label>
				    	<input type="password" class="form-control" id="confirm-password" name="confirm_password">
				  	</div>
				  	<button type="submit" name="btn_change_password" class="btn btn-primary">Change Password</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php

include('footer.php');

?>