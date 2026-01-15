<?php

require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') 
{
  	header('Location: logout.php');
  	exit();
}

if(isset($_POST['edit_user']))
{
	// Validate the form data
  	$name = $_POST['name'];
  	$email = $_POST['email'];
  	$password = $_POST['password'];
  	$id = $_POST['id'];

  	if (empty($name)) 
  	{
	    $errors[] = 'Please enter your name';
  	}
  	if (empty($email)) 
  	{
    	$errors[] = 'Please enter your email address';
  	} 
  	else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
  	{
    	$errors[] = 'Please enter a valid email address';
  	}

  	// If the form data is valid, update the user's password
  	if (empty($errors)) 
  	{
  		if(empty($password))
  		{
	  		$sql = "UPDATE users SET name = ?, email = ? WHERE id = ?";

	  		$pdo->prepare($sql)->execute([$name, $email, $id]);
	  	}
	  	else
	  	{
	  		$password = password_hash($password, PASSWORD_DEFAULT);

	  		$sql = "UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?";

	  		$pdo->prepare($sql)->execute([$name, $email, $password, $id]);
	  	}

  		$_SESSION['success'] = 'User Data has been edited';

  		header('location:users.php');
  		exit();
  	}
}

if(isset($_GET['id']))
{
	// Prepare a SELECT statement to retrieve the flats's details
  	$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  	$stmt->execute([$_GET['id']]);

  	// Fetch the user's details from the database
  	$user = $stmt->fetch(PDO::FETCH_ASSOC);
}

include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Edit Users</h1>
    <ol class="breadcrumb mb-4">
    	<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="flats.php">Users Management</a></li>
        <li class="breadcrumb-item active">Edit User Data</li>
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
				<h5 class="card-title">Edit Users Data</h5>
			</div>
			<div class="card-body">
				<form method="post">
				  	<div class="mb-3">
				    	<label for="name">Name</label>
				    	<input type="text" class="form-control" id="name" name="name" placeholder="Enter name" value="<?php echo isset($user['name']) ? $user['name'] : ''; ?>">
				  	</div>
				  	<div class="mb-3">
				    	<label for="email">Email address</label>
				    	<input type="email" class="form-control" id="email" name="email" placeholder="Enter email" value="<?php echo isset($user['email']) ? $user['email'] : ''; ?>">
				  	</div>
				  	<div class="mb-3">
				    	<label for="password">Password</label>
				    	<input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
				  	</div>
				  	<input type="hidden" name="id" value="<?php echo isset($user['id']) ? $user['id'] : ''; ?>" />
				  	<button type="submit" name="edit_user" class="btn btn-primary">Edit</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php

include('footer.php');

?>