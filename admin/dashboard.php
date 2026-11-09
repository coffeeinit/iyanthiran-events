<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';

// Check admin authentication
requireAdmin();

// Get dashboard statistics
$stats = getDashboardStats();
$recent_registrations = getRecentRegistrations(5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>

    <?php include 'includes/header.php'; ?>

        <div class="container-fluid">
            <main>
                <div class="d-flex">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="event_add.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-plus me-1"></i>Add Event
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="stat-number"><?= $stats['total_events'] ?></div>
                                        <div>Total Events</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="stat-number"><?= $stats['total_registrations'] ?></div>
                                        <div>Total Registrations</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="stat-number"><?= $stats['today_traffic'] ?></div>
                                        <div>Today's Views</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-eye"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="stat-number">₹<?= $stats['total_payments'] ?></div>
                                        <div>Total Revenue</div>
                                    </div>
                                    <div class="stat-icon">
                                        <i class="fas fa-rupee-sign"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Registrations -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Registrations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_registrations)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Event</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_registrations as $registration): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($registration['name']) ?></td>
                                                <td><?= htmlspecialchars($registration['email']) ?></td>
                                                <td><?= htmlspecialchars($registration['event_title']) ?></td>
                                                <td><?= date('M j, Y', strtotime($registration['created_at'])) ?></td>
                                                <td>
                                                    <span class="badge bg-success">Verified</span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5>No Recent Registrations</h5>
                                <p class="text-muted">New registrations will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>