<?php
require_once __DIR__ . '/../core/config.php';
?>
<div id="sidebar">
    <nav class="sb-sidenav accordion sb-sidenav-dark">
        <div class="sb-sidenav-menu mt-3">
            <div class="nav flex-column">
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a class="nav-link sidebar-link" data-page="users.php">Users</a>
                    <a class="nav-link sidebar-link" data-page="flats.php">Flats</a>
                    <a class="nav-link sidebar-link" data-page="allotments.php">Allotments</a>
                    <!-- more links -->
                <?php elseif ($_SESSION['user_role'] === 'cashier'): ?>
                    <a class="nav-link sidebar-link" data-page="maintanenceRecords.php">Maintenance Records</a>
                    <a class="nav-link sidebar-link" data-page="all_bill.php">Maintenance Bills</a>
                <?php else: ?>
                    <a class="nav-link sidebar-link" data-page="view/view_userMaintanenceBill.php">My Bills</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</div>
