<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Log page view
logPageView('homepage');

// Get upcoming and live events
$events = getEvents('upcoming', 6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Discover Amazing Events</title>
    <meta name="description" content="Discover and register for amazing events. Join workshops, seminars, and conferences.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body data-page-name="homepage">

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
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">All Events</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="mb-4">Discover Amazing Events</h1>
            <p class="lead">Join workshops, seminars, conferences and more. Register now and don't miss out!</p>
            <a href="events.php" class="btn btn-light btn-lg">
                <i class="fas fa-calendar me-2"></i>
                View All Events
            </a>
        </div>
    </section>

    <!-- Featured Events -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-5">
                    <h2 class="text-gradient">Featured Events</h2>
                    <p class="text-muted">Don't miss these upcoming events</p>
                </div>
            </div>
            
            <div class="row g-4">
                <?php if (!empty($events)): ?>
                    <?php foreach ($events as $event): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="event-card shadow-hover" data-event-id="<?= $event['id'] ?>">
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
                                            <span><?= htmlspecialchars(substr($event['venue'], 0, 50)) ?>...</span>
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
                            <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                            <h4>No Events Available</h4>
                            <p class="text-muted">Check back soon for upcoming events!</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (count($events) >= 6): ?>
                <div class="text-center mt-5">
                    <a href="events.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-arrow-right me-2"></i>
                        View All Events
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center mb-5">
                    <h2>Why Choose Our Events?</h2>
                    <p class="text-muted">Experience the best with our professional event management</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-shield-alt fa-3x text-primary"></i>
                        </div>
                        <h5>Secure Registration</h5>
                        <p class="text-muted">Safe and secure registration process with email verification</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-mobile-alt fa-3x text-primary"></i>
                        </div>
                        <h5>Mobile Friendly</h5>
                        <p class="text-muted">Register and manage your events from any device, anywhere</p>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="text-center">
                        <div class="feature-icon mb-3">
                            <i class="fas fa-bell fa-3x text-primary"></i>
                        </div>
                        <h5>Event Reminders</h5>
                        <p class="text-muted">Get timely reminders so you never miss an event</p>
                    </div>
                </div>
            </div>
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
                    <p>Your premier destination for discovering and registering for amazing events. Join workshops, seminars, conferences and more.</p>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="events.php">All Events</a></li>
                        <li><a href="#" onclick="alert('Feature coming soon!')">About Us</a></li>
                        <li><a href="#" onclick="alert('Feature coming soon!')">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> info@events.com</li>
                        <li><i class="fas fa-phone me-2"></i> +91 12345 67890</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> Your City, India</li>
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
