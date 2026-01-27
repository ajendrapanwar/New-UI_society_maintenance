<nav class="sb-topnav navbar navbar-expand navbar-dark bg-primary">
    <a class="navbar-brand ps-3" href="#">Society Maintenance</a>
    <ul class="navbar-nav ms-auto me-3">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-user fa-fw"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>profile.php">Profile</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>change_password.php">Change Password</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>logout.php">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>
