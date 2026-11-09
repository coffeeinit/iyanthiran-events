<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get event id from URL
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if (!$event_id) {
    header('Location: events.php');
    exit;
}

// Get event details
$event = getEventById($event_id);

if (!$event) {
    header('Location: events.php');
    exit;
}

// Log page view
logPageView('gallery', $event['id']);

// Get gallery items
$gallery_photos = getGalleryItems($event['id'], 'photo');
$gallery_videos = getGalleryItems($event['id'], 'video');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> - Gallery - <?= SITE_NAME ?></title>
    <meta name="description" content="Browse photos and videos from <?= htmlspecialchars($event['title']) ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        .lightbox {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lightbox-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }
        
        .lightbox-content img,
        .lightbox-content video {
            max-width: 100%;
            max-height: 100%;
            border-radius: 8px;
        }
        
        .lightbox-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .gallery-filter {
            margin-bottom: 30px;
        }
        
        .filter-btn {
            margin-right: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>
<body data-page-name="gallery" data-event-id="<?= $event['id'] ?>">

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

    <!-- Page Header -->
    <section class="hero-section">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">Event Gallery</h1>
                    <h4 class="opacity-75"><?= htmlspecialchars($event['title']) ?></h4>
                    <p class="lead mb-0">
                        <i class="fas fa-calendar me-2"></i>
                        <?= formatDate($event['date']) ?> at <?= formatTime($event['time']) ?>
                    </p>
                </div>
                <a href="event/<?= encodeId($event['id']) ?>" class="btn btn-light">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Event
                </a>
            </div>
        </div>
    </section>

    <!-- Gallery Content -->
    <section class="py-5">
        <div class="container">
            <!-- Gallery Statistics -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-images fa-2x text-primary me-3"></i>
                        <div>
                            <h5 class="mb-0"><?= count($gallery_photos) ?> Photos</h5>
                            <small class="text-muted">Available in gallery</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-video fa-2x text-primary me-3"></i>
                        <div>
                            <h5 class="mb-0"><?= count($gallery_videos) ?> Videos</h5>
                            <small class="text-muted">Available in gallery</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Buttons -->
            <div class="gallery-filter">
                <button class="btn btn-primary filter-btn active" data-filter="all">
                    <i class="fas fa-th me-2"></i>
                    All Media
                </button>
                <button class="btn btn-outline-primary filter-btn" data-filter="photo">
                    <i class="fas fa-images me-2"></i>
                    Photos (<?= count($gallery_photos) ?>)
                </button>
                <button class="btn btn-outline-primary filter-btn" data-filter="video">
                    <i class="fas fa-video me-2"></i>
                    Videos (<?= count($gallery_videos) ?>)
                </button>
            </div>
            
            <?php if (empty($gallery_photos) && empty($gallery_videos)): ?>
                <!-- Empty State -->
                <div class="text-center py-5">
                    <i class="fas fa-images fa-4x text-muted mb-4"></i>
                    <h4>No Gallery Items</h4>
                    <p class="text-muted">Gallery items will be added after the event.</p>
                    <a href="event/<?= encodeId($event['id']) ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Event Details
                    </a>
                </div>
            <?php else: ?>
                <!-- Gallery Grid -->
                <div class="gallery-grid">
                    <!-- Photos -->
                    <?php foreach ($gallery_photos as $photo): ?>
                        <div class="gallery-item fade-in" 
                             data-media-type="photo" 
                             data-media-src="<?= htmlspecialchars($photo['media_path']) ?>"
                             data-filter="photo">
                            <img src="<?= htmlspecialchars($photo['media_path']) ?>" 
                                 alt="Event Photo" 
                                 loading="lazy">
                            <div class="gallery-overlay">
                                <i class="fas fa-search-plus"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Videos -->
                    <?php foreach ($gallery_videos as $video): ?>
                        <div class="gallery-item fade-in" 
                             data-media-type="video" 
                             data-media-src="<?= htmlspecialchars($video['media_path']) ?>"
                             data-filter="video">
                            <video src="<?= htmlspecialchars($video['media_path']) ?>" 
                                   muted 
                                   loading="lazy">
                            </video>
                            <div class="play-button">
                                <i class="fas fa-play"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="event/<?= encodeId($event['id']) ?>" class="text-light">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Event
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality
            const filterBtns = document.querySelectorAll('.filter-btn');
            const galleryItems = document.querySelectorAll('.gallery-item');
            
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    
                    // Update active button
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter items
                    galleryItems.forEach(item => {
                        if (filter === 'all' || item.dataset.filter === filter) {
                            item.style.display = 'block';
                            setTimeout(() => item.classList.add('fade-in'), 10);
                        } else {
                            item.style.display = 'none';
                            item.classList.remove('fade-in');
                        }
                    });
                });
            });
        });
    </script>

</body>
</html>
