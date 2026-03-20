<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/helpers.php';
include __DIR__ . '/flash.php';

requireRole(['admin', 'cashier', 'user']);

// Fetch notifications
$now = date('Y-m-d H:i:s');

$notifStmt = $pdo->prepare("
        SELECT * 
        FROM notifications 
        WHERE (start_date IS NULL OR start_date <= ?)
        AND (end_date IS NULL OR end_date >= ?)
        ORDER BY id DESC
    ");
$notifStmt->execute([$now, $now]);

$latestNotifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
$notifCount = count($latestNotifications);


$currentPage = basename($_SERVER['PHP_SELF']);


/* ================= NOTIFICATION COUNT ================= */
$notificationCount = 0;

try {
    // Show only active notifications (or you can change condition)
    $stmtNoti = $pdo->query("
        SELECT COUNT(*) 
        FROM notifications 
        WHERE (end_date IS NULL OR end_date >= NOW())
    ");
    $notificationCount = (int)$stmtNoti->fetchColumn();
} catch (Exception $e) {
    $notificationCount = 0; // fallback safety
}



/* ================= USER COMPLETED COMPLAINT COUNT ================= */

$usercomplaintCount = 0;

if ($_SESSION['user_role'] === 'user') {

    $userId = $_SESSION['user_id'];

    $stmtComplaint = $pdo->prepare("
        SELECT COUNT(*)
        FROM complaints
        WHERE user_id = ?
        AND status = 'completed'
        AND user_seen = 0
    ");

    $stmtComplaint->execute([$userId]);

    $usercomplaintCount = (int)$stmtComplaint->fetchColumn();
}


/* ================= COMPLAINT COUNT ================= */
$complaintCount = 0;

try {

    $stmtComplaint = $pdo->query("
        SELECT COUNT(*)
        FROM complaints
        WHERE status IN ('pending','processing')
    ");

    $complaintCount = (int)$stmtComplaint->fetchColumn();
} catch (Exception $e) {
    $complaintCount = 0;
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <link rel="stylesheet" href="../../assets/css/styles.css">

</head>

<body>

    <!-- SideBar -->
    <div id="sidebar">

        <button class="sidebar-close-btn" onclick="toggleSidebar()">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="sidebar-header">Society Maintenance</div>

        <!---------------------------- Admin Sidebar ---------------------------->
        <?php if ($_SESSION['user_role'] === 'admin'): ?>

            <nav class="nav flex-column">
                <a class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>

                <div class="nav-group-label">Collection Management</div>
                <a class="nav-link <?= ($currentPage == 'users.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>users.php"><i class="fa-solid fa-users"></i> Users</a>
                <a class="nav-link <?= ($currentPage == 'flats.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>flats.php"><i class="fa-solid fa-building"></i> Flats</a>
                <a class="nav-link <?= ($currentPage == 'allotments.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>allotments.php"><i class="fa-solid fa-key"></i> Allotments</a>
                <a class="nav-link <?= ($currentPage == 'maintanenceRate.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>maintanenceRate.php"><i class="fa-solid fa-indian-rupee-sign"></i> Maintenance Rate</a>
                <a class="nav-link <?= ($currentPage == 'maintanenceRecords.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>maintanenceRecords.php"><i class="fa-solid fa-file-invoice-dollar"></i> Maintenance Records</a>

                <div class="nav-group-label">Expense & Salary</div>
                <a class="nav-link <?= ($currentPage == 'miscellaneous_work.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>miscellaneous_work.php"><i class="fa-solid fa-screwdriver-wrench"></i> Miscellaneous Work</a>
                <a class="nav-link collapsed" data-bs-toggle="collapse" href="#salarySub"><i class="fa-solid fa-wallet"></i> Salary Management <i class="fa-solid fa-chevron-down ms-auto opacity-50"></i></a>
                <div class="collapse nav-sub" id="salarySub">
                    <a class="nav-link <?= ($currentPage == 'salary_security.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>salary_security.php">Security Salary</a>
                    <a class="nav-link <?= ($currentPage == 'salary_sweeper.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>salary_sweeper.php">Sweeper Salary</a>
                    <a class="nav-link <?= ($currentPage == 'salary_garbageCollector.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>salary_garbageCollector.php">Garbage Collector</a>
                </div>
                <a class="nav-link <?= ($currentPage == 'electricity_bills.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>electricity_bills.php"><i class="fa-solid fa-bolt"></i> Electricity Bills</a>

                <div class="nav-group-label">Parking & Security</div>
                <a class="nav-link <?= ($currentPage == 'resident_parking.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>resident_parking.php"><i class="fa-solid fa-car"></i> Resident Parking</a>
                <a class="nav-link <?= ($currentPage == 'visitors.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>visitors.php"><i class="fa-solid fa-user-clock"></i> Visitors</a>
                <a class="nav-link <?= ($currentPage == 'vehicle_finder.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>vehicle_finder.php"><i class="fa-solid fa-magnifying-glass"></i> Vehicle Finder</a>

                <div class="nav-group-label">Admin Control</div>
                <a class="nav-link d-flex align-items-center justify-content-between <?= ($currentPage == 'complaints.php') ? 'active' : '' ?>"
                    href="<?= BASE_URL ?>complaints.php">

                    <span>
                        <i class="fa-solid fa-headset"></i> Complaints
                    </span>

                    <?php if ($complaintCount > 0): ?>
                        <span style="background: #0d6efd;color: #fff;font-size: 11px;font-weight: 700;padding: 2px 8px;border-radius: 999px;min-width: 22px;text-align: center;">
                            <?= $complaintCount ?>
                        </span>
                    <?php endif; ?>

                </a>
                <a class="nav-link d-flex align-items-center justify-content-between <?= ($currentPage == 'notifications.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>notifications.php"><span><i class="fa-solid fa-bell"></i> Notifications</span>
                    <?php if ($notificationCount > 0): ?>
                        <span style="background: #0d6efd;color: #fff;font-size: 11px;font-weight: 700;padding: 2px 8px;border-radius: 999px;min-width: 22px;text-align: center;"><?= $notificationCount ?></span>
                    <?php endif; ?>
                </a>

                <div class="nav-group-label">Reports</div>
                <a class="nav-link <?= ($currentPage == 'all_bill.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>all_bill.php"><i class="fa-solid fa-chart-column"></i> Collection Report</a>
                <a class="nav-link <?= ($currentPage == 'all_expense.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>all_expense.php"><i class="fa-solid fa-chart-line"></i> Expense Report</a>

                <div class="nav-group-label">Records</div>
                <a class="nav-link <?= ($currentPage == 'tenants.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>tenants.php"><i class="fa-solid fa-people-group"></i> Tenant History</a>


                <hr class="mx-3 my-4 opacity-10 border-white">
            </nav>

            <!---------------------------- Cashier Sidebar ---------------------------->
        <?php elseif ($_SESSION['user_role'] === 'cashier'): ?>

            <nav class="nav flex-column">
                <a class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>dashboard.php"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>

                <div class="nav-group-label">Collection Management</div>
                <a class="nav-link <?= ($currentPage == 'maintanenceRecords.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>maintanenceRecords.php"><i class="fa-solid fa-file-invoice-dollar"></i> Maintenance Records</a>

                <div class="nav-group-label">Expense & Salary</div>
                <a class="nav-link <?= ($currentPage == 'miscellaneous_work.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>miscellaneous_work.php"><i class="fa-solid fa-screwdriver-wrench"></i> Miscellaneous Work</a>
                <a class="nav-link collapsed" data-bs-toggle="collapse" href="#salarySub"><i class="fa-solid fa-wallet"></i> Salary Management <i class="fa-solid fa-chevron-down ms-auto opacity-50"></i></a>
                <div class="collapse nav-sub" id="salarySub">
                    <a class="nav-link <?= ($currentPage == 'salary_security.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>salary_security.php">Security Salary</a>
                    <a class="nav-link <?= ($currentPage == 'salary_sweeper.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>salary_sweeper.php">Sweeper Salary</a>
                    <a class="nav-link <?= ($currentPage == 'salary_garbageCollector.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>salary_garbageCollector.php">Garbage Collector</a>
                </div>
                <a class="nav-link <?= ($currentPage == 'electricity_bills.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>electricity_bills.php"><i class="fa-solid fa-bolt"></i> Electricity Bills</a>

                <div class="nav-group-label">Reports</div>
                <a class="nav-link <?= ($currentPage == 'all_bill.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>all_bill.php"><i class="fa-solid fa-chart-column"></i> Collection Report</a>
                <a class="nav-link <?= ($currentPage == 'all_expense.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>all_expense.php"><i class="fa-solid fa-chart-line"></i> Expense Report</a>

                <hr class="mx-3 my-4 opacity-10 border-white">
            </nav>

            <!---------------------------- User Sidebar ---------------------------->
        <?php else: ?>

            <nav class="nav flex-column">
                <a class="nav-link <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>dashboard.php"><i class="fa-solid fa-house-user"></i> My Home</a>
                <div class="nav-group-label">Payments & Bills</div>
                <a class="nav-link <?= ($currentPage == 'view/view_userMaintanenceBill.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>view/view_userMaintanenceBill.php"><i class="fa-solid fa-file-invoice-dollar"></i> Maintenance Bills</a>

                <div class="nav-group-label">Guests & Records</div>
                <a class="nav-link" href="<?= BASE_URL ?>user_visitors.php"><i class="fa-solid fa-user-shield"></i> Guest History</a>
                <a class="nav-link <?= ($currentPage == 'tenants.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>tenants.php"><i class="fa-solid fa-people-group"></i> Tenant History</a>

                <div class="nav-group-label">Support</div>
                <a class="nav-link d-flex align-items-center justify-content-between 
                    <?= ($currentPage == 'view_userComplaint_status.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>view/view_userComplaint_status.php">
                    <span><i class="fa-solid fa-circle-question me-2"></i><span style="margin-right: 10px;">My Complaints</span>

                        <?php if ($usercomplaintCount > 0): ?>
                            <span style="
                                    background: #dc3545;
                                    color: #fff;
                                    font-size: 11px;
                                    font-weight: 700;
                                    padding: 3px 8px;
                                    border-radius: 999px;
                                    min-width: 20px;
                                    text-align: center;
                                "><?= $usercomplaintCount ?></span>
                        <?php endif; ?>
                </a>
                <a class="nav-link d-flex align-items-center justify-content-between <?= ($currentPage == 'notifications.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>notifications.php">
                    <span>
                        <i class="fa-solid fa-bullhorn me-2"></i><span style="margin-right: 10px;">Society Notices</span>
                        <?php if ($notificationCount > 0): ?>
                            <span style="
                                    background: #dc3545;
                                    color: #fff;
                                    font-size: 11px;
                                    font-weight: 700;
                                    padding: 3px 7px;
                                    border-radius: 999px;
                                    min-width: 20px;
                                    text-align: center;
                                ">
                                <?= $notificationCount ?>
                            </span>
                        <?php endif; ?>
                    </span>
                </a>

                <hr class="mx-3 my-4 opacity-10 border-white">
            </nav>

        <?php endif; ?>


        <a class="nav-link text-warning" href="<?= BASE_URL ?>logout.php"><i class="fa-solid fa-power-off"></i> Logout System</a>

    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-open');
        }

        $(document).ready(function() {
            // Auto init ONLY simple tables (not server-side tables)
            $('.datatable').each(function() {
                if (!$.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable({
                        dom: '<"d-flex justify-content-between mb-4"lf>rt<"d-flex justify-content-between mt-4"ip>',
                        language: {
                            search: "",
                            searchPlaceholder: "Search records..."
                        }
                    });
                }
            });
        });
    </script>


</body>

</html>