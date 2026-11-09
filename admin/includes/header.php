<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Panel' ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h3><?= SITE_NAME ?></h3>
            <small>Admin Panel</small>
        </div>
        
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" class="<?= ($current_page ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
            </li>
            
            <li>
                <a href="events.php" class="<?= ($current_page ?? '') === 'events' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-alt"></i>
                    Manage Events
                </a>
            </li>
            
            <li>
                <a href="registrations.php" class="<?= ($current_page ?? '') === 'registrations' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    Registrations
                </a>
            </li>
            
            <li>
                <a href="gallery_upload.php" class="<?= ($current_page ?? '') === 'gallery' ? 'active' : '' ?>">
                    <i class="fas fa-images"></i>
                    Gallery Upload
                </a>
            </li>
            
            <li>
                <a href="analytics.php" class="<?= ($current_page ?? '') === 'analytics' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    Analytics
                </a>
            </li>
            
            <li>
                <a href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    View Site
                </a>
            </li>
            
            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Admin Header -->
        <div class="admin-header">
            <button id="sidebarToggle" class="btn btn-outline-primary d-md-none">
                <i class="fas fa-bars"></i>
            </button>
            
            <h4 class="mb-0"><?= $page_title ?? 'Admin Panel' ?></h4>
            
            <div class="admin-user">
                <div class="dropdown">
                    <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['admin_username']) ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../index.php" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>View Site
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Alert Container -->
        <div id="alertContainer"></div>
