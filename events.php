<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Log page view
logPageView('events_listing');

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = EVENTS_PER_PAGE;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Get events with filters
$where_conditions = ['is_hidden = 0'];
$params = [];

if ($status_filter && in_array($status_filter, ['upcoming', 'live', 'past'])) {
    $where_conditions[] = 'status = ?';
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = '(title LIKE ? OR description LIKE ? OR venue LIKE ?)';
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}

$where_clause = implode(' AND ', $where_conditions);

$db = getDB();

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM events WHERE $where_clause";
$total_events = $db->fetchOne($count_sql, $params)['total'];
$total_pages = ceil($total_events / $limit);

// Get events for current page
$events_sql = "SELECT * FROM events WHERE $where_clause ORDER BY date ASC, time ASC LIMIT $limit OFFSET $offset";
$events = $db->fetchAll($events_sql, $params);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Events - <?= SITE_NAME ?></title>
    <meta name="description" content="Browse all available events. Find workshops, seminars, conferences and more.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body data-page-name="events_listing">

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-calendar-alt me-2"></i>
                <?= SITE_NAME ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="events.php">All Events</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <section class="hero-section">
        <div class="container">
            <h1 class="mb-4">All Events</h1>
            <p class="lead">Discover and register for amazing events happening near you</p>
        </div>
    </section>

    <!-- Filters and Search -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-md-6">
                    <form method="GET" action="events.php" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" 
                               placeholder="Search events..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                <div class="col-lg-4 col-md-6">
                    <form method="GET" action="events.php">
                        <?php if ($search): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Events</option>
                            <option value="upcoming" <?= $status_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="live" <?= $status_filter === 'live' ? 'selected' : '' ?>>Live</option>
                            <option value="past" <?= $status_filter === 'past' ? 'selected' : '' ?>>Past</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Events Grid -->
    <section class="py-5">
        <div class="container">
            <?php if ($search || $status_filter): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?= $total_events ?> event(s) found
                                <?php if ($search): ?>
                                    for "<?= htmlspecialchars($search) ?>"
                                <?php endif; ?>
                                <?php if ($status_filter): ?>
                                    in <?= ucfirst($status_filter) ?> events
                                <?php endif; ?>
                            </h5>
                            
                            <?php if ($search || $status_filter): ?>
                                <a href="events.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i>
                                    Clear Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="row g-4">
                <?php if (!empty($events)): ?>
                    <?php foreach ($events as $event): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="event-card shadow-hover fade-in" data-event-id="<?= $event['id'] ?>">
                                <div class="event-card-image">
                                    <?php if ($event['banner_image']): ?>
                                        <img src="<?= htmlspecialchars($event['banner_image']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                                    <?php endif; ?>
                                    <span class="event-status status-<?= $event['status'] ?>">
                                        <?= ucfirst($event['status']) ?>
                                    </span>
                                </div>
                                
                                <div class="event-card-body">
                                    <h5 class="event-card-title">
                                        <?= htmlspecialchars($event['title']) ?>
                                    </h5>
                                    
                                    <div class="event-meta mb-3">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                                            <span><?= formatDate($event['date']) ?></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="fas fa-clock text-primary me-2"></i>
                                            <span><?= formatTime($event['time']) ?></span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                            <span><?= htmlspecialchars(substr($event['venue'], 0, 50)) ?><?= strlen($event['venue']) > 50 ? '...' : '' ?></span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($event['description']): ?>
                                        <p class="event-description">
                                            <?= htmlspecialchars(substr($event['description'], 0, 120)) ?>...
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="event-price <?= $event['is_paid'] ? '' : 'free' ?>">
                                        <?php if ($event['is_paid']): ?>
                                            <i class="fas fa-tag me-1"></i>
                                            ₹<?= number_format($event['price'], 2) ?>
                                        <?php else: ?>
                                            <i class="fas fa-gift me-1"></i>
                                            Free Event
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="event.php?id=<?= $event['id'] ?>" class="btn btn-primary">
                                            <i class="fas fa-eye me-2"></i>
                                            View Details
                                        </a>
                                        
                                        <?php if ($event['status'] !== 'past'): ?>
                                            <a href="event.php?id=<?= $event['id'] ?>#register" class="btn btn-success">
                                                <i class="fas fa-user-plus me-2"></i>
                                                Register Now
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-4x text-muted mb-4"></i>
                            <h4>No Events Found</h4>
                            <p class="text-muted">
                                <?php if ($search || $status_filter): ?>
                                    Try adjusting your search criteria or
                                    <a href="events.php">view all events</a>
                                <?php else: ?>
                                    Check back soon for upcoming events!
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Events pagination" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5>
                        <i class="fas fa-calendar-alt me-2"></i>
                        <?= SITE_NAME ?>
                    </h5>
                    <p>Your premier destination for discovering and registering for amazing events.</p>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="events.php">All Events</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> info@events.com</li>
                        <li><i class="fas fa-phone me-2"></i> +91 12345 67890</li>
                    </ul>
                </div>
            </div>
            
            <hr class="mt-4">
            
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Built with ❤️ for amazing events</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>

</body>
</html>
