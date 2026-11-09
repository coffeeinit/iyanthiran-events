<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get parameters
$event_encoded = $_GET['event'] ?? '';
$registration_encoded = $_GET['reg'] ?? '';

if (!$event_encoded || !$registration_encoded) {
    header('Location: events.php');
    exit;
}

$event_id = decodeId($event_encoded);
$registration_id = decodeId($registration_encoded);

if (!$event_id || !$registration_id) {
    header('Location: events.php');
    exit;
}

$db = getDB();

// Get registration and event details
$sql = "SELECT r.*, e.title, e.date, e.time, e.venue, e.is_paid, e.price 
        FROM registrations r 
        JOIN events e ON r.event_id = e.id 
        WHERE r.id = ? AND r.event_id = ? AND r.otp_verified = TRUE";

$registration = $db->fetchOne($sql, [$registration_id, $event_id]);

if (!$registration) {
    header('Location: events.php');
    exit;
}

// Log page view
logPageView('registration_success', $event_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - <?= SITE_NAME ?></title>
    <meta name="description" content="Your registration for <?= htmlspecialchars($registration['title']) ?> has been confirmed.">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    
    <style>
        .success-container {
            min-height: 60vh;
            display: flex;
            align-items: center;
        }
        
        .success-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .success-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .success-icon {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }
        
        .registration-details {
            padding: 30px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
        }
        
        .detail-value {
            font-weight: 500;
            color: #333;
        }
        
        .qr-code {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
    </style>
</head>
<body data-page-name="registration_success" data-event-id="<?= $event_id ?>">

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

    <!-- Success Content -->
    <section class="success-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="success-card">
                        <!-- Success Header -->
                        <div class="success-header">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h2 class="mb-3">Registration Confirmed!</h2>
                            <p class="lead mb-0">Thank you for registering. Your spot is secured!</p>
                        </div>
                        
                        <!-- Registration Details -->
                        <div class="registration-details">
                            <h4 class="mb-4">Registration Details</h4>
                            
                            <div class="detail-item">
                                <span class="detail-label">
                                    <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                    Event
                                </span>
                                <span class="detail-value"><?= htmlspecialchars($registration['title']) ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    Name
                                </span>
                                <span class="detail-value"><?= htmlspecialchars($registration['name']) ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">
                                    <i class="fas fa-envelope me-2 text-primary"></i>
                                    Email
                                </span>
                                <span class="detail-value"><?= htmlspecialchars($registration['email']) ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">
                                    <i class="fas fa-phone me-2 text-primary"></i>
                                    Phone
                                </span>
                                <span class="detail-value"><?= htmlspecialchars($registration['phone']) ?></span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">
                                    <i class="fas fa-calendar me-2 text-primary"></i>
                                    Date & Time
                                </span>
                                <span class="detail-value">
                                    <?= formatDate($registration['date'], 'l, F j, Y') ?> at <?= formatTime($registration['time']) ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">
                                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                    Venue
                                </span>
                                <span class="detail-value"><?= htmlspecialchars($registration['venue']) ?></span>
                            </div>
                            
                            <?php if ($registration['is_paid']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">
                                        <i class="fas fa-credit-card me-2 text-primary"></i>
                                        Payment Status
                                    </span>
                                    <span class="detail-value">
                                        <span class="badge bg-success">Paid - ₹<?= number_format($registration['price'], 2) ?></span>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <span class="detail-label">
                                    <i class="fas fa-clock me-2 text-primary"></i>
                                    Registration ID
                                </span>
                                <span class="detail-value">
                                    <code>#<?= str_pad($registration['id'], 6, '0', STR_PAD_LEFT) ?></code>
                                </span>
                            </div>
                            
                            <!-- QR Code Placeholder -->
                            <div class="qr-code">
                                <h6 class="mb-3">Event Entry Pass</h6>
                                <div style="width: 150px; height: 150px; background: #f0f0f0; margin: 0 auto; display: flex; align-items: center; justify-content: center; border: 2px dashed #ccc;">
                                    <div class="text-center">
                                        <i class="fas fa-qrcode fa-3x text-muted"></i>
                                        <br><small class="text-muted">QR Code</small>
                                    </div>
                                </div>
                                <p class="mt-3 mb-0 text-muted">
                                    <small>Show this confirmation at the event entrance</small>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="px-4 pb-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="events.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Back to Events
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <button onclick="window.print()" class="btn btn-primary w-100">
                                        <i class="fas fa-print me-2"></i>
                                        Print Confirmation
                                    </button>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <button onclick="shareRegistration()" class="btn btn-outline-success">
                                    <i class="fas fa-share-alt me-2"></i>
                                    Share Registration
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Important Notes -->
    <section class="py-4 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            Important Notes
                        </h6>
                        <ul class="mb-0">
                            <li>A confirmation email has been sent to your registered email address</li>
                            <li>You will receive an event reminder 24 hours before the event</li>
                            <li>Please arrive 15 minutes early for check-in</li>
                            <li>Bring a valid ID for verification at the venue</li>
                            <?php if ($registration['is_paid']): ?>
                                <li>Your payment has been processed successfully</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
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
                    <a href="events.php" class="text-light">
                        <i class="fas fa-calendar me-1"></i>
                        Browse More Events
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
        function shareRegistration() {
            const eventTitle = "<?= htmlspecialchars($registration['title']) ?>";
            const eventDate = "<?= formatDate($registration['date']) ?>";
            const text = `I just registered for ${eventTitle} on ${eventDate}! 🎉`;
            const url = "<?= SITE_URL ?>/event/<?= encodeId($event_id) ?>";
            
            if (navigator.share) {
                navigator.share({
                    title: 'Event Registration',
                    text: text,
                    url: url
                });
            } else {
                // Fallback to WhatsApp
                const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(text + ' ' + url)}`;
                window.open(whatsappUrl, '_blank');
            }
        }
        
        // Print styles
        const printStyles = `
            @media print {
                .navbar, .footer, .btn { display: none !important; }
                .success-card { box-shadow: none !important; border: 1px solid #ccc; }
                body { background: white !important; }
            }
        `;
        
        const styleSheet = document.createElement('style');
        styleSheet.textContent = printStyles;
        document.head.appendChild(styleSheet);
    </script>

</body>
</html>
