<?php
require_once 'includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/hashid.php';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $venue = sanitize($_POST['venue'] ?? '');
    $youtube_url = sanitize($_POST['youtube_url'] ?? '');
    $is_paid = isset($_POST['is_paid']) ? 1 : 0;
    $price = $is_paid ? floatval($_POST['price'] ?? 0) : 0;
    $max_registrations = intval($_POST['max_registrations'] ?? 0) ?: null;
    $status = $_POST['status'] ?? 'upcoming';

    // Banner image upload
    $banner_image = '';
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../assets/banners/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid banner image type. Allowed: jpg, jpeg, png, gif.';
        } else {
            $filename = uniqid('banner_') . '.' . $ext;
            $target = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $target)) {
                $banner_image = 'assets/banners/' . $filename;
            } else {
                $errors[] = 'Failed to upload banner image.';
            }
        }
    } elseif (!empty($_FILES['banner_image']['name'])) {
        $errors[] = 'Error uploading banner image.';
    }
    
    // Validation
    if (empty($title)) $errors[] = 'Event title is required';
    if (empty($date)) $errors[] = 'Event date is required';
    if (empty($time)) $errors[] = 'Event time is required';
    if (empty($venue)) $errors[] = 'Event venue is required';
    if ($is_paid && $price <= 0) $errors[] = 'Price must be greater than 0 for paid events';
    if (!in_array($status, ['upcoming', 'live', 'past'])) $errors[] = 'Invalid status';
    
    // Validate date
    if ($date && !strtotime($date)) {
        $errors[] = 'Invalid date format';
    }
    
    // Validate YouTube URL
    if ($youtube_url && !getYouTubeVideoId($youtube_url)) {
        $errors[] = 'Invalid YouTube URL format';
    }
    
    if (empty($errors)) {
        $db = getDB();
        
        try {
            $db->beginTransaction();
            
            // Insert event
            $sql = "INSERT INTO events (title, description, date, time, venue, banner_image, youtube_url, is_paid, price, max_registrations, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $result = $db->query($sql, [
                $title, $description, $date, $time, $venue, 
                $banner_image, $youtube_url, $is_paid, $price, 
                $max_registrations, $status
            ]);
            
            if ($result) {
                $event_id = $db->lastInsertId();
                
                // Generate and update slug
                $slug = generateEventSlug($event_id);
                $db->query("UPDATE events SET slug = ? WHERE id = ?", [$slug, $event_id]);
                
                $db->commit();
                $success_message = 'Event created successfully!';
                
                // Redirect after 2 seconds
                header("refresh:2;url=events.php");
            } else {
                throw new Exception('Failed to create event');
            }
            
        } catch (Exception $e) {
            $db->rollback();
            $errors[] = 'Failed to create event: ' . $e->getMessage();
        }
    }
}

$page_title = 'Add New Event';
$current_page = 'events';
?>

<?php include 'includes/header.php'; ?>

<div class="content-wrapper">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Add New Event</h2>
        <a href="events.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Back to Events
        </a>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($success_message) ?>
            <br><small>Redirecting to events list...</small>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <!-- Event Form -->
    <div class="admin-form">
        <form method="POST" action="event_add.php" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Basic Information -->
                    <div class="mb-4">
                        <h5 class="mb-3">Basic Information</h5>
                        
                        <div class="form-group">
                            <label for="title" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Event Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="5" placeholder="Describe your event..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="venue" class="form-label">Venue *</label>
                            <textarea class="form-control" id="venue" name="venue" 
                                      rows="3" placeholder="Event venue address..." required><?= htmlspecialchars($_POST['venue'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Media -->
                    <div class="mb-4">
                        <h5 class="mb-3">Media</h5>
                        
                        <div class="form-group">
                            <label for="banner_image" class="form-label">Banner Image</label>
                            <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/*">
                            <small class="form-text text-muted">Upload a banner image (jpg, jpeg, png, gif)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="youtube_url" class="form-label">YouTube Video URL</label>
                            <input type="url" class="form-control" id="youtube_url" name="youtube_url" 
                                   value="<?= htmlspecialchars($_POST['youtube_url'] ?? '') ?>"
                                   placeholder="https://www.youtube.com/watch?v=...">
                            <small class="form-text text-muted">Optional: Add a promotional video</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Date & Time -->
                    <div class="mb-4">
                        <h5 class="mb-3">Schedule</h5>
                        
                        <div class="form-group">
                            <label for="date" class="form-label">Event Date *</label>
                            <input type="date" class="form-control" id="date" name="date" 
                                   value="<?= htmlspecialchars($_POST['date'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="time" class="form-label">Event Time *</label>
                            <input type="time" class="form-control" id="time" name="time" 
                                   value="<?= htmlspecialchars($_POST['time'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="upcoming" <?= ($_POST['status'] ?? 'upcoming') === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                <option value="live" <?= ($_POST['status'] ?? '') === 'live' ? 'selected' : '' ?>>Live</option>
                                <option value="past" <?= ($_POST['status'] ?? '') === 'past' ? 'selected' : '' ?>>Past</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Registration Settings -->
                    <div class="mb-4">
                        <h5 class="mb-3">Registration</h5>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_paid" name="is_paid" 
                                       <?= isset($_POST['is_paid']) ? 'checked' : '' ?>>
                                <label for="is_paid" class="form-check-label">
                                    This is a paid event
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-group" id="price-group" style="display: <?= isset($_POST['is_paid']) ? 'block' : 'none' ?>;">
                            <label for="price" class="form-label">Price (₹)</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" 
                                   min="0" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label for="max_registrations" class="form-label">Max Registrations</label>
                            <input type="number" class="form-control" id="max_registrations" name="max_registrations" 
                                   value="<?= htmlspecialchars($_POST['max_registrations'] ?? '') ?>" 
                                   min="1" placeholder="Leave blank for unlimited">
                            <small class="form-text text-muted">Optional: Set maximum number of registrations</small>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Create Event
                        </button>
                        
                        <a href="events.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Show/hide price field based on paid event checkbox
document.getElementById('is_paid').addEventListener('change', function() {
    const priceGroup = document.getElementById('price-group');
    const priceInput = document.getElementById('price');
    
    if (this.checked) {
        priceGroup.style.display = 'block';
        priceInput.required = true;
    } else {
        priceGroup.style.display = 'none';
        priceInput.required = false;
        priceInput.value = '';
    }
});

// Set minimum date to today
document.getElementById('date').min = new Date().toISOString().split('T')[0];
</script>

<?php include 'includes/footer.php'; ?>
