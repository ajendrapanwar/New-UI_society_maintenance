<?php

require_once 'config.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') 
{
  	header('Location: logout.php');
  	exit();
}

// Get total flats
$sql = "SELECT COUNT(*) AS total_flats FROM flats";
$stmt = $pdo->query($sql);
$total_flats = $stmt->fetch(PDO::FETCH_ASSOC)['total_flats'];

$flat_id = '';

// Get total bills
$sql = "SELECT COUNT(*) AS total_bills FROM bills";
if($_SESSION['user_role'] == 'user')
{
	$stmt = $pdo->prepare('SELECT flat_id FROM allotments WHERE user_id = ?');
	$stmt->execute([$_SESSION['user_id']]);
	$flat_id = $stmt->fetch(PDO::FETCH_ASSOC)['flat_id'];
	$sql .=" WHERE flat_id = '".$flat_id."'";
}
$stmt = $pdo->query($sql);
$total_bills = $stmt->fetch(PDO::FETCH_ASSOC)['total_bills'];

// Get total allotments
$sql = "SELECT COUNT(*) AS total_allotments FROM allotments";
$stmt = $pdo->query($sql);
$total_allotments = $stmt->fetch(PDO::FETCH_ASSOC)['total_allotments'];

// Get total visitors
$sql = "SELECT COUNT(*) AS total_visitors FROM visitors";
if($_SESSION['user_role'] == 'user')
{
	$sql .=" WHERE flat_id = '".$flat_id."'";
}
$stmt = $pdo->query($sql);
$total_visitors = $stmt->fetch(PDO::FETCH_ASSOC)['total_visitors'];

// Get total unresolved complaints
$sql = "SELECT COUNT(*) AS total_unresolved_complaints FROM complaints WHERE status = 'unresolved'";
if($_SESSION['user_role'] == 'user')
{
	$sql .=" AND flat_id = '".$flat_id."'";
}
$stmt = $pdo->query($sql);
$total_unresolved_complaints = $stmt->fetch(PDO::FETCH_ASSOC)['total_unresolved_complaints'];

// Get total in progress complaints
$sql = "SELECT COUNT(*) AS total_in_progress_complaints FROM complaints WHERE status = 'in_progress'";
if($_SESSION['user_role'] == 'user')
{
	$sql .=" AND flat_id = '".$flat_id."'";
}
$stmt = $pdo->query($sql);
$total_in_progress_complaints = $stmt->fetch(PDO::FETCH_ASSOC)['total_in_progress_complaints'];

// Get total resolved complaints
$sql = "SELECT COUNT(*) AS total_resolved_complaints FROM complaints WHERE status = 'resolved'";
if($_SESSION['user_role'] == 'user')
{
	$sql .=" AND flat_id = '".$flat_id."'";
}
$stmt = $pdo->query($sql);
$total_resolved_complaints = $stmt->fetch(PDO::FETCH_ASSOC)['total_resolved_complaints'];

// Get total complaints
$sql = "SELECT COUNT(*) AS total_complaints FROM complaints";
if($_SESSION['user_role'] == 'user')
{
	$sql .=" WHERE flat_id = '".$flat_id."'";
}
$stmt = $pdo->query($sql);
$total_complaints = $stmt->fetch(PDO::FETCH_ASSOC)['total_complaints'];

include('header.php');

?>


                <div class="container-fluid px-4">
                    <h1 class="mt-4">Dashboard</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                    <div class="row">
                    	<?php 
                    	if($_SESSION['user_role'] == 'admin')
                    	{
                    	?>
                        <div class="col-xl-3 col-md-6">
                            <div class="card">
								<div class="card-header">
									<h5>Total Flats</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_flats; ?></p>
								</div>
							</div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Bills</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_bills; ?></p>
								</div>
							</div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Allotment</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_allotments; ?></p>
								</div>
							</div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                        	<div class="card">
								<div class="card-header">
									<h5>Total In-process Complaints</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_in_progress_complaints; ?></p>
								</div>
							</div>
                        </div>
                        <div class="col-xl-3 col-md-6 mt-3">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Visitors</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_visitors; ?></p>
								</div>
							</div>
                        </div>
                        <div class="col-xl-3 col-md-6 mt-3">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Unresolved Complaints</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_unresolved_complaints; ?></p>
								</div>
							</div>
                        </div>
                        <div class="col-xl-3 col-md-6 mt-3">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Resolved Complaints</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_resolved_complaints; ?></p>
								</div>
							</div>
                        </div>
                        <div class="col-xl-3 col-md-6 mt-3">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Complaints</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_complaints; ?></p>
								</div>
							</div>
                        </div>
                        <?php
                    	}
                    	else
                    	{
                    	?>
                    	<div class="col-xl-4 col-md-6">
                        	<div class="card">
								<div class="card-header">
									<h5>Total In-process Complaints</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_in_progress_complaints; ?></p>
								</div>
							</div>
                        </div>                        
                        <div class="col-xl-4 col-md-6 mt-3">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Unresolved Complaints</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_unresolved_complaints; ?></p>
								</div>
							</div>
                        </div>
                        <div class="col-xl-4 col-md-6 mt-3">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Resolved Complaints</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_resolved_complaints; ?></p>
								</div>
							</div>
                        </div>
                        <div class="col-xl-4 col-md-6 mt-3">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Complaints</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_complaints; ?></p>
								</div>
							</div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Bills</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_bills; ?></p>
								</div>
							</div>
                        </div>
                        
                        <div class="col-xl-4 col-md-6 mt-3">
                        	<div class="card">
								<div class="card-header">
									<h5>Total Visitors</h5>
								</div>
								<div class="card-body">
									<p class="card-text"><?php echo $total_visitors; ?></p>
								</div>
							</div>
                        </div>
                        
                        
                    	<?php
                    	}
                        ?>
                    </div>
                </div>
<?php
	include('footer.php');
?>