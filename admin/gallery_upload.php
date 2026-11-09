<?php
require_once 'includes/auth.php';
require_once '../includes/functions.php';

// Get event ID
$event_id = intval($_GET['event_id'] ?? 0);

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

$success_message = '';
$error_message = '';

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_files'])) {
    $uploaded_files = $_FILES['media_files'];
    $upload_count = 0;
    $error_count = 0;
    
    // Process each uploaded file
    for ($i = 0; $i < count($uploaded_files['name']); $i++) {
        if ($uploaded_files['error'][$i] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $uploaded_files['name'][$i],
                'type' => $uploaded_files['type'][$i],
                'tmp_name' => $uploaded_files['tmp_name'][$i],
                'error' => $uploaded_files['error'][$i],
                'size' => $uploaded_files['size'][$i]
            ];

            // Validate file size (up to 1GB)
            if ($file['size'] > 1024 * 1024 * 1024) {
                $error_count++;
                $error_message .= "{$file['name']} is too large. Max size is 1GB.<br>";
                continue;
            }

            // Validate file type (allow webp, mov)
            $allowed_types = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'video/mp4', 'video/avi', 'video/mov', 'video/quicktime'
            ];
            if (!in_array($file['type'], $allowed_types)) {
                $error_count++;
                $error_message .= "{$file['name']} has an invalid file type.<br>";
                continue;
            }

            // Determine media type
            $media_type = strpos($file['type'], 'video/') === 0 ? 'video' : 'photo';

            $result = uploadFile($file, $event_id, $media_type);

            if ($result['success']) {
                $upload_count++;
            } else {
                $error_count++;
                $error_message .= "Failed to upload {$file['name']}: {$result['message']}<br>";
            }
        }
    }
    
    if ($upload_count > 0) {
        $success_message = "Successfully uploaded $upload_count file(s)";
    }
    
    if ($error_count > 0 && empty($success_message)) {
        $error_message = "Failed to upload files. Please try again.";
    }
}

// Handle file deletion
if (isset($_POST['delete_media']) && isset($_POST['media_id'])) {
    $media_id = intval($_POST['media_id']);
    
    $db = getDB();
    
    // Get media details
    $media = $db->fetchOne("SELECT * FROM event_gallery WHERE id = ? AND event_id = ?", [$media_id, $event_id]);
    
    if ($media) {
        // Delete file from filesystem
        $file_path = '../' . $media['media_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $result = $db->query("DELETE FROM event_gallery WHERE id = ?", [$media_id]);
        
        if ($result) {
            $success_message = "Media file deleted successfully";
        } else {
            $error_message = "Failed to delete media file";
        }
    }
}

// Get existing gallery items
$gallery_photos = getGalleryItems($event_id, 'photo');
$gallery_videos = getGalleryItems($event_id, 'video');

$page_title = 'Gallery Upload';
$current_page = 'gallery';
?>

<?php include 'includes/header.php'; ?>

<div class="content-wrapper">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Gallery Upload</h2>
            <p class="text-muted mb-0">
                Event: <strong><?= htmlspecialchars($event['title']) ?></strong> | 
                <?= count($gallery_photos) ?> photos, <?= count($gallery_videos) ?> videos
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="../gallery.php?event_id=<?= $event['id'] ?>" class="btn btn-outline-info btn-sm" target="_blank">
                <i class="fas fa-eye me-1"></i>
                View Gallery
            </a>
            <a href="event_edit.php?id=<?= $event_id ?>" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-edit me-1"></i>
                Edit Event
            </a>
            <a href="events.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>
                Back to Events
            </a>
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?= $success_message ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <?php
        // Split error_message by <br> and show each as a separate alert
        $error_lines = array_filter(array_map('trim', explode('<br>', $error_message)));
        foreach ($error_lines as $err): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($err) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- Upload Section -->
        <div class="col-lg-4">
            <div class="admin-form">
                <h5 class="mb-4">Upload Media Files</h5>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="file-upload-area mb-3">
                        <input type="file" id="media_files" name="media_files[]" multiple 
                               accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/avi,video/mov,video/quicktime" style="display: none;">
                        <div class="text-center">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                            <h6>Drop files here or click to upload</h6>
                            <p class="text-muted mb-0">
                                <small>Supports: JPG, PNG, GIF, WEBP, MP4, AVI, MOV<br>Max size: 1GB per file</small>
                            </p>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>
                            Upload Files
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="upload-info">
                    <h6>Upload Guidelines</h6>
                    <ul class="small text-muted">
                        <li>Maximum file size: 1GB</li>
                        <li>Supported image formats: JPG, PNG, GIF, WEBP</li>
                        <li>Supported video formats: MP4, AVI, MOV</li>
                        <li>Multiple files can be uploaded at once</li>
                        <li>Files are automatically organized by type</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Gallery Display -->
        <div class="col-lg-8">
            <!-- Photos Section -->
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Photos (<?= count($gallery_photos) ?>)</h5>
                </div>
                
                <?php if (!empty($gallery_photos)): ?>
                    <div class="row g-3">
                        <?php foreach ($gallery_photos as $photo): ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="gallery-item-admin">
                                    <img src="../<?= htmlspecialchars($photo['media_path']) ?>" 
                                         alt="Gallery Photo" class="img-fluid rounded">
                                    <div class="gallery-item-overlay">
                                        <div class="btn-group btn-group-sm">
                                            <a href="../<?= htmlspecialchars($photo['media_path']) ?>" 
                                               class="btn btn-light" target="_blank" title="View Full Size">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="deleteMedia(<?= $photo['id'] ?>, '<?= htmlspecialchars($photo['media_name']) ?>')" 
                                                    class="btn btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="gallery-item-info">
                                        <small class="text-muted d-block text-truncate" title="<?= htmlspecialchars($photo['media_name']) ?>">
                                            <?= htmlspecialchars($photo['media_name']) ?>
                                        </small>
                                        <small class="text-muted">
                                            <?= $photo['file_size'] ? round($photo['file_size'] / 1024, 1) . ' KB' : '' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 bg-light rounded">
                        <i class="fas fa-images fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No photos uploaded yet</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Videos Section -->
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Videos (<?= count($gallery_videos) ?>)</h5>
                </div>
                
                <?php if (!empty($gallery_videos)): ?>
                    <div class="row g-3">
                        <?php foreach ($gallery_videos as $video): ?>
                            <div class="col-md-6">
                                <div class="gallery-item-admin">
                                    <video src="../<?= htmlspecialchars($video['media_path']) ?>" 
                                           class="img-fluid rounded" controls style="max-height: 200px; width: 100%; object-fit: cover;">
                                    </video>
                                    <div class="gallery-item-overlay">
                                        <div class="btn-group btn-group-sm">
                                            <a href="../<?= htmlspecialchars($video['media_path']) ?>" 
                                               class="btn btn-light" target="_blank" title="View Full Size">
                                                <i class="fas fa-play"></i>
                                            </a>
                                            <button onclick="deleteMedia(<?= $video['id'] ?>, '<?= htmlspecialchars($video['media_name']) ?>')" 
                                                    class="btn btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="gallery-item-info">
                                        <small class="text-muted d-block text-truncate" title="<?= htmlspecialchars($video['media_name']) ?>">
                                            <?= htmlspecialchars($video['media_name']) ?>
                                        </small>
                                        <small class="text-muted">
                                            <?= $video['file_size'] ? round($video['file_size'] / (1024 * 1024), 1) . ' MB' : '' ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 bg-light rounded">
                        <i class="fas fa-video fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">No videos uploaded yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_media" value="1">
    <input type="hidden" name="media_id" id="deleteMediaId">
</form>

<style>
.gallery-item-admin {
    position: relative;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.gallery-item-admin:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.gallery-item-admin:hover .gallery-item-overlay {
    opacity: 1;
}

.gallery-item-overlay {
    position: absolute;
    top: 8px;
    right: 8px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.gallery-item-info {
    padding: 8px;
    border-top: 1px solid #eee;
}

.file-upload-area {
    cursor: pointer;
}
</style>

<script>
// File upload drag and drop functionality
document.querySelector('.file-upload-area').addEventListener('click', function() {
    document.getElementById('media_files').click();
});

document.getElementById('media_files').addEventListener('change', function() {
    const files = this.files;
    if (files.length > 0) {
        const fileNames = Array.from(files).map(f => f.name).join(', ');
        const uploadArea = document.querySelector('.file-upload-area .text-center');
        uploadArea.innerHTML = `
            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
            <h6>${files.length} file(s) selected</h6>
            <p class="text-muted mb-0"><small>${fileNames}</small></p>
        `;
    }
});

function deleteMedia(mediaId, fileName) {
    if (confirm(`Are you sure you want to delete "${fileName}"? This action cannot be undone.`)) {
        document.getElementById('deleteMediaId').value = mediaId;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
