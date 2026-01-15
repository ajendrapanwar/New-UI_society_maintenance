<?php

require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'user')) 
{
  	header('Location: logout.php');
  	exit();
}

if(isset($_GET['id']))
{
	$sql = '
	SELECT complaints.id, users.name, flats.flat_number, flats.block_number, complaints.description, complaints.status, complaints.created_at, complaints.master_comment, complaints.user_id FROM complaints
		JOIN users ON users.id = complaints.user_id 
		JOIN flats ON flats.id = complaints.flat_id 
		WHERE complaints.id = ?
	';
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$_GET['id']]);
	$complaint = $stmt->fetch(PDO::FETCH_ASSOC);

	if(isset($_GET['action']) && $_GET['action'] == 'notification')
	{
		if($_SESSION['user_role'] == 'admin')
		{
			$notification_type = 'Complaint';
		}
		else
		{
			$notification_type = 'Complaint Status';
		}
		$stmt = $pdo->prepare("UPDATE notifications SET read_status = 'read' WHERE user_id = '".$_SESSION['user_id']."' AND notiification_type = '".$notification_type."' AND event_id = '".$_GET['id']."'");

		$stmt->execute();
	}
}

if(isset($_POST['process_complaint']))
{
	$master_comment = $_POST['master_comment'];
	$status = $_POST['status'];
	$id = $_POST['id'];

	if (empty($master_comment)) 
  	{
	    $errors[] = 'Please Enter Comment';
  	}

  	if (empty($status)) 
  	{
	    $errors[] = 'Please Select Complaint Status';
  	}

  	// If the form data is valid, update the user's password
  	if (empty($errors)) 
  	{
  		$sql = '
		SELECT master_comment FROM complaints 
		WHERE id = ?
		';
		$stmt = $pdo->prepare($sql);
		$stmt->execute([$id]);
		$previous_comment = $stmt->fetch(PDO::FETCH_ASSOC);

		//echo '<pre>';
		//print_r($previous_comment['master_comment']);
		//echo '</pre>';

		if($previous_comment['master_comment'] != '')
		{
			$comment_data = json_decode($previous_comment['master_comment'], true);

			$comment_data[] = array(
				'datetime'			=>	date('Y-m-d H:i:s'),
				'details'			=>	$master_comment
			);
		}
		else
		{
			$comment_data[] = array(
				'datetime'			=>	date('Y-m-d H:i:s'),
				'details'			=>	$master_comment
			);
		}

		//echo '<pre>';
		//print_r($comment_data);
		//echo '</pre>';

		$sql = "UPDATE complaints SET master_comment = ?, status = ? WHERE id = ?";

		$pdo->prepare($sql)->execute([json_encode($comment_data), $status, $id]);

	    // insert notification data into notifications table
		$message = "Your Complaint for ".$_POST['hidden_description']." has been processed by Admin.";
		
		$notification_link = 'view_complaint.php?id='.$id.'&action=notification';
		$stmt = $pdo->prepare("INSERT INTO notifications (user_id, notiification_type, event_id, message, link) VALUES (?, ?, ?, ?, ?)");
		$stmt->execute([$_POST["hidden_user_id"], 'Complaint Status', $id, $message, $notification_link]);

  		$_SESSION['success'] = 'Complaint has been processed';

  		header('location:complaints.php');
  		exit();
  	}
}

include('header.php');

?>

<div class="container-fluid px-4">
    <h1 class="mt-4">View Complaints</h1>
    <ol class="breadcrumb mb-4">
    	<li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="complaints.php">Complaints Management</a></li>
        <li class="breadcrumb-item active">View Complaints</li>
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
				<h5 class="card-title">View Complaint</h5>
			</div>
			<div class="card-body">
				<div class="row mb-3">
					<div class="col-md-4"><b>User Name</b></div>
					<div class="col-md-8"><?php echo (isset($complaint['name'])) ? $complaint['name'] : 'NA'; ?></div>
				</div>
				<div class="row mb-3">
					<div class="col-md-4"><b>Flat Number</b></div>
					<div class="col-md-8"><?php echo (isset($complaint['flat_number'])) ? $complaint['block_number'] . ' - ' . $complaint['flat_number'] : 'NA'; ?></div>
				</div>
				<div class="row mb-3">
					<div class="col-md-4"><b>Complaints Details</b></div>
					<div class="col-md-8"><?php echo (isset($complaint['description'])) ? $complaint['description'] : 'NA'; ?></div>
				</div>
				<?php

				$status = '';

				$tstatus = '';

				if(isset($complaint['status']))
				{
					if($complaint['status'] == 'pending')
					{
						$tstatus = 'pending';

						$status = '<span class="badge bg-primary">Pending</span>';
					}
					if($complaint['status'] == 'in_progress')
					{
						$tstatus = 'in_progress';

						$status = '<span class="badge bg-warning">In Progress</span>';
					}
					if($complaint['status'] == 'resolved')
					{
						$tstatus = 'resolved';

						$status = '<span class="badge bg-success">Resolved</span>';
					}
				}
				?>
				<div class="row mb-3">
					<div class="col-md-4"><b>Status</b></div>
					<div class="col-md-8"><?php echo $status; ?></div>
				</div>

				<?php

				if(isset($complaint['master_comment']))
				{
					if($complaint['master_comment'] != '')
					{
						$master_comment = json_decode($complaint['master_comment'], true);

						if(count($master_comment) > 0)
						{
							echo '
							<div class="row mb-3">
								<div class="col-md-4"><b>Master Comment</b></div>
								<div class="col-md-8">
							';

							foreach($master_comment as $comment)
							{
								echo '<p><b>Comment Date & Time</b> - ' . $comment['datetime'] . '<br />';
								echo '<b>Comment</b> - ' . $comment['details'] . '</p>';
							}

							echo '</div>
							</div>
							';
						}
					}
				}

				if($_SESSION['user_role'] == 'admin')
				{
					if($tstatus != 'resolved')
					{
				?>

				<form method="post">
					<div class="mb-3">
						<label><b>Master Comment</b></label>
						<textarea name="master_comment" class="form-control" rows="5"></textarea>
					</div>
					<div class="mb-3">
						<label><b>Complaint Status</b></label>
						<select name="status" class="form-control">
							<option value="">Select Status</option>
							<option value="in_progress">In Progress</option>
							<option value="resolved">Resolved</option>
						</select>
					</div>
					<div class="mb-3">
						<input type="hidden" name="id" value="<?php echo isset($complaint['id']) ? $complaint['id'] : ''; ?>" />
						<input type="hidden" name="hidden_description" value="<?php echo isset($complaint['description']) ? $complaint['description'] : ''; ?>" />
						<input type="hidden" name="hidden_user_id" value="<?php echo isset($complaint['user_id']) ? $complaint['user_id'] : ''; ?>" />
						<input type="submit" name="process_complaint" class="btn btn-primary" value="Submit" />
					</div>
				</form>

				<?php
					}
				}

				?>

			</div>
		</div>
	</div>
</div>

<?php

include('footer.php');

?>