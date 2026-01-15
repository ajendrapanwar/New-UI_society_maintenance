<?php

require_once 'config.php';

if(isset($_POST['add_user']))
{
	// Validate the form data
  	$name = $_POST['name'];
  	$email = $_POST['email'];
  	$password = $_POST['password'];

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
  	if (empty($password)) 
  	{
 	   $errors['password'] = 'Please enter your password';
  	}
  	else
  	{
  		$password = password_hash($password, PASSWORD_DEFAULT);
  	}

  	// If the form data is valid, update the user's password
  	if (empty($errors)) 
  	{
  		// Insert user data into the database
	    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')");

	    $stmt->execute([$name, $email, $password]);

  		$_SESSION['success'] = 'New User Data Added';

  		header('location:users.php');
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
    <h1 class="mt-4">Add Users</h1>
    <ol class="breadcrumb mb-4">
    	<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="flats.php">Users Management</a></li>
        <li class="breadcrumb-item active">Add User</li>
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
				<h5 class="card-title">Add User</h5>
			</div>
			<div class="card-body">
				<form method="post">
				  	<div class="mb-3">
				    	<label for="name">Name</label>
				    	<input type="text" class="form-control" id="name" name="name" placeholder="Enter name">
				  	</div>
				  	<div class="mb-3">
				    	<label for="email">Email address</label>
				    	<input type="email" class="form-control" id="email" name="email" placeholder="Enter email">
				  	</div>
				  	<div class="mb-3">
				    	<label for="password">Password</label>
				    	<input type="password" class="form-control" id="password" name="password" placeholder="Enter password">
				  	</div>
				  	<button type="submit" name="add_user" class="btn btn-primary">Submit</button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php

include('footer.php');

?>