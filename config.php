<?php
// Define the database connection settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'Society');
define('DB_USER', 'root');
define('DB_PASS', '1234');

// Connect to the database
$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);

// Import the database schema if the table does not exist
$table_name = 'users'; // change this to the name of your table
$table_check_sql = "SELECT 1 FROM $table_name LIMIT 1";
$table_exists = $pdo->query($table_check_sql) !== false;

if (!$table_exists) 
{
    $sql = file_get_contents('database.sql');
    $pdo->exec($sql);

    // Insert the default admin user if it doesn't already exist
	$username = 'admin';
	$useremail = 'admin@sms.com';
	$password = password_hash('password', PASSWORD_DEFAULT);
	$user_check_sql = "SELECT 1 FROM users WHERE email=$useremail LIMIT 1";
	$user_exists = $pdo->query($user_check_sql) !== false;

	if (!$user_exists) {
	    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')";
	    $pdo->prepare($sql)->execute([$username, $useremail, $password]);
	}

	// Output a message indicating the setup was successful
	echo "Setup complete. Please log in using the credentials:\n";
	echo "Username: $useremail\n";
	echo "Password: password\n";
}

date_default_timezone_set("Asia/Calcutta");

session_start();

$type = array('1 BHK Flat', '2 BHK Flat', '3 BHK Flat', '4 BHK Flat', '5 BHK Flat', 'Penthouse', 'Row House', 'Tenament', 'Duplex', 'Villa', 'Shop');

?>
