<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="fas fa-chart-line me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'events.php' ? 'active' : '' ?>" href="events.php">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Events
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'registrations.php' ? 'active' : '' ?>" href="registrations.php">
                    <i class="fas fa-users me-2"></i>
                    Registrations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'gallery.php' ? 'active' : '' ?>" href="gallery.php">
                    <i class="fas fa-images me-2"></i>
                    Gallery
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'analytics.php' ? 'active' : '' ?>" href="analytics.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    Analytics
                </a>
            </li>
        </ul>
    </div>
</nav>