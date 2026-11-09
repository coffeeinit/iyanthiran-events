<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get event slug or ID from URL parameter
$slug = $_GET['slug'] ?? $_GET['id'] ?? '';

// If numeric ID provided, convert to slug by looking up in database
if (is_numeric($slug)) {
    $event_id = intval($slug);
    $db = getDB();
    $event_data = $db->fetchOne("SELECT slug FROM events WHERE id = ?", [$event_id]);
    if ($event_data && !empty($event_data['slug'])) {
        $slug = $event_data['slug'];
    } else {
        // If no slug, use ID for backward compatibility
        $slug = $event_id;
    }
}



if (!$slug) {
    header('Location: events.php');
    exit;
}

// Get event details
$event = getEventBySlug($slug);

if (!$event) {
    header('Location: events.php');
    exit;
}

// Log page view
logPageView('event_detail', $event['id']);

// Get registration count
$registration_count = getRegistrationCount($event['id']);

// Check if registration is full
$is_full = $event['max_registrations'] && $registration_count >= $event['max_registrations'];

// Get gallery items
$gallery_photos = getGalleryItems($event['id'], 'photo');
$gallery_videos = getGalleryItems($event['id'], 'video');

// Get YouTube video ID
$youtube_video_id = getYouTubeVideoId($event['youtube_url']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> - <?= SITE_NAME ?></title>
    <meta name="description" content="<?= htmlspecialchars(substr($event['description'], 0, 160)) ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= htmlspecialchars($event['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars(substr($event['description'], 0, 160)) ?>">
    <meta property="og:type" content="event">
    <meta property="og:url" content="<?= SITE_URL ?>/event/<?= $slug ?>">
    <?php if ($event['banner_image']): ?>
        <meta property="og:image" content="<?= SITE_URL ?>/<?= $event['banner_image'] ?>">
    <?php endif; ?>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <!-- Razorpay if needed -->
    <?php if ($event['is_paid']): ?>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <?php endif; ?>
</head>
<body data-page-name="event_detail" data-event-id="<?= $event['id'] ?>">

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
                        <a class="nav-link" href="events.php">All Events</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Event Header -->
    <section class="py-0">
        <div class="container">
            <div class="event-detail-header">
                <div class="event-banner">
                    <?php if ($event['banner_image']): ?>
                        <img src="<?= htmlspecialchars($event['banner_image']) ?>" alt="<?= htmlspecialchars($event['title']) ?>">
                    <?php endif; ?>
                </div>
                
                <div class="event-details">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h1 class="event-title"><?= htmlspecialchars($event['title']) ?></h1>
                        <span class="badge badge-<?= $event['status'] ?> fs-6">
                            <?= ucfirst($event['status']) ?>
                        </span>
                    </div>
                    
                    <div class="event-info-grid">
                        <div class="info-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div class="info-content">
                                <h6>Date</h6>
                                <p><?= formatDate($event['date'], 'l, F j, Y') ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div class="info-content">
                                <h6>Time</h6>
                                <p><?= formatTime($event['time']) ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="info-content">
                                <h6>Venue</h6>
                                <p><?= htmlspecialchars($event['venue']) ?></p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-<?= $event['is_paid'] ? 'tag' : 'gift' ?>"></i>
                            <div class="info-content">
                                <h6>Price</h6>
                                <p>
                                    <?php if ($event['is_paid']): ?>
                                        ₹<?= number_format($event['price'], 2) ?>
                                    <?php else: ?>
                                        Free
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-users"></i>
                            <div class="info-content">
                                <h6>Registrations</h6>
                                <p>
                                    <?= $registration_count ?>
                                    <?php if ($event['max_registrations']): ?>
                                        / <?= $event['max_registrations'] ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <i class="fas fa-share-alt"></i>
                            <div class="info-content">
                                <h6>Share</h6>
                                <div class="d-flex gap-2">
                                    <a href="https://wa.me/?text=<?= urlencode($event['title'] . ' - ' . SITE_URL . '/event/' . $slug) ?>" 
                                       class="btn btn-sm btn-success" target="_blank">
                                        <i class="fab fa-whatsapp"></i>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode($event['title']) ?>&url=<?= urlencode(SITE_URL . '/event/' . $slug) ?>" 
                                       class="btn btn-sm btn-info" target="_blank">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Event Content -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Event Description -->
                    <?php if ($event['description']): ?>
                        <div class="mb-5">
                            <h3 class="mb-4">About This Event</h3>
                            <div class="content">
                                <?= nl2br(htmlspecialchars($event['description'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- YouTube Video -->
                    <?php if ($youtube_video_id): ?>
                        <div class="mb-5">
                            <h3 class="mb-4">Event Preview</h3>
                            <div class="youtube-container">
                                <iframe 
                                    src="https://www.youtube.com/embed/<?= $youtube_video_id ?>?enablejsapi=1" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                    allowfullscreen>
                                </iframe>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Gallery Preview -->
                    <?php if (!empty($gallery_photos) || !empty($gallery_videos)): ?>
                        <div class="mb-5">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3>Event Gallery</h3>
                                <a href="gallery.php?event_id=<?= $event['id'] ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-images me-1"></i>
                                    View Full Gallery
                                </a>
                            </div>
                            
                            <div class="gallery-grid">
                                <?php 
                                $preview_items = array_merge(
                                    array_slice($gallery_photos, 0, 3),
                                    array_slice($gallery_videos, 0, 2)
                                );
                                foreach (array_slice($preview_items, 0, 6) as $item): 
                                ?>
                                    <div class="gallery-item" 
                                         data-media-type="<?= $item['media_type'] ?>" 
                                         data-media-src="<?= htmlspecialchars($item['media_path']) ?>">
                                        <?php if ($item['media_type'] === 'photo'): ?>
                                            <img src="<?= htmlspecialchars($item['media_path']) ?>" alt="Gallery Image">
                                        <?php else: ?>
                                            <video src="<?= htmlspecialchars($item['media_path']) ?>"></video>
                                            <div class="play-button">
                                                <i class="fas fa-play"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <!-- Registration Section -->
                    <div class="registration-section" id="register">
                        <h3 class="mb-4">
                            <i class="fas fa-user-plus me-2"></i>
                            Register for Event
                        </h3>
                        
                        <?php if ($event['status'] === 'past'): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-clock me-2"></i>
                                This event has already ended.
                            </div>
                        <?php elseif ($is_full): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-users me-2"></i>
                                Registration is full. No more spots available.
                            </div>
                        <?php else: ?>
                            <form id="registrationForm" method="POST" action="register.php">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                                
                                <?php if ($event['is_paid']): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Payment Required:</strong> ₹<?= number_format($event['price'], 2) ?>
                                        <br><small>You will be redirected to secure payment gateway after registration.</small>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>
                                        <?= $event['is_paid'] ? 'Register & Pay' : 'Register Now' ?>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Event Stats -->
                    <div class="mt-4 p-4 bg-light rounded">
                        <h5 class="mb-3">Event Statistics</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Registered:</span>
                            <strong><?= $registration_count ?></strong>
                        </div>
                        <?php if ($event['max_registrations']): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Max Capacity:</span>
                                <strong><?= $event['max_registrations'] ?></strong>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?= min(100, ($registration_count / $event['max_registrations']) * 100) ?>%">
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between">
                            <span>Status:</span>
                            <strong class="text-<?= $event['status'] === 'live' ? 'success' : ($event['status'] === 'upcoming' ? 'primary' : 'secondary') ?>">
                                <?= ucfirst($event['status']) ?>
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Alert Container -->
    <div id="alertContainer"></div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="events.php" class="text-light">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Events
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/registration.js"></script>

</body>
</html>
