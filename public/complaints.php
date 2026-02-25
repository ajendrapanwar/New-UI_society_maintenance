<?php
require_once __DIR__ . '/../core/config.php';

requireRole(['admin']);

/* FETCH ALL COMPLAINTS WITH USER DATA */
$complaints = $pdo->query("
    SELECT c.*, u.name, u.mobile 
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.id DESC
")->fetchAll();

include('../resources/layout/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Complaints</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>

<div class="main-wrapper">

    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <main id="main-content">
        <h1 class="fw-800 mb-4">Resident Complaints</h1>
        <div class="data-card border-0 shadow-sm">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Flat</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#101</td>
                        <td>Water Leakage</td>
                        <td>A-402</td>
                        <td><span class="text-danger">High</span></td>
                        <td><span class="badge bg-warning">Open</span></td>
                        <td><button class="btn btn-sm btn-brand">Resolve</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </main>

</div>

</body>

</html>

<?php include('../resources/layout/footer.php'); ?>