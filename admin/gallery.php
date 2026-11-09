<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once 'includes/auth.php';

// Check admin authentication
checkAuth();

// Handle gallery actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'upload':
                $result = handleBulkUpload();
                break;
            case 'delete':
                $result = handleDelete();
                break;
            case 'update_caption':
                $result = handleUpdateCaption();
                break;
        }
    }
}

// Get events for dropdown
$events = getEvents();

// Get selected event gallery
$selected_event_id = $_GET['event_id'] ?? ($_POST['event_id'] ?? '');
$gallery_items = [];
$selected_event = null;

if ($selected_event_id) {
    $selected_event = getEventById($selected_event_id);
    $gallery_items = getGalleryItems($selected_event_id);
}

// Handle bulk upload
function handleBulkUpload() {
    global $selected_event_id;
    
    if (!$selected_event_id || !isset($_FILES['gallery_files'])) {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Please select an event and files to upload'];
        return;
    }
    
    $uploaded_count = 0;
    $error_count = 0;
    $files = $_FILES['gallery_files'];
    
    // Create upload directory if it doesn't exist
    $upload_dir = '../assets/gallery/' . $selected_event_id . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Process each file
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file_info = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'size' => $files['size'][$i]
            ];
            
            if (uploadGalleryFileToEvent($selected_event_id, $file_info)) {
                $uploaded_count++;
            } else {
                $error_count++;
            }
        } else {
            $error_count++;
        }
    }
    
    if ($uploaded_count > 0) {
        $_SESSION['admin_message'] = [
            'type' => 'success', 
            'text' => "Successfully uploaded $uploaded_count file(s)" . ($error_count > 0 ? " ($error_count failed)" : "")
        ];
    } else {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'No files were uploaded successfully'];
    }
}

// Handle delete
function handleDelete() {
    $gallery_id = $_POST['gallery_id'] ?? '';
    
    if (!$gallery_id) {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid gallery item'];
        return;
    }
    
    $db = getDB();
    
    // Get file path before deletion
    $item = $db->fetchOne("SELECT media_path FROM gallery WHERE id = ?", [$gallery_id]);
    
    if ($item) {
        // Delete from database
        $result = $db->query("DELETE FROM gallery WHERE id = ?", [$gallery_id]);
        
        if ($result) {
            // Delete physical file
            $file_path = '../' . $item['media_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'Gallery item deleted successfully'];
        } else {
            $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Failed to delete gallery item'];
        }
    }
}

// Handle caption update
function handleUpdateCaption() {
    $gallery_id = $_POST['gallery_id'] ?? '';
    $caption = trim($_POST['caption'] ?? '');
    
    if (!$gallery_id) {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Invalid gallery item'];
        return;
    }
    
    $db = getDB();
    $result = $db->query("UPDATE gallery SET caption = ? WHERE id = ?", [$caption, $gallery_id]);
    
    if ($result) {
        $_SESSION['admin_message'] = ['type' => 'success', 'text' => 'Caption updated successfully'];
    } else {
        $_SESSION['admin_message'] = ['type' => 'error', 'text' => 'Failed to update caption'];
    }
}

// Enhanced upload function
function uploadGalleryFileToEvent($event_id, $file) {
    // Validate file type
    $allowed_types = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'video/mp4', 'video/avi', 'video/mov', 'video/wmv'
    ];
    
    if (!in_array($file['type'], $allowed_types)) {
        return false;
    }
    
    // Validate file size (max 50MB)
    if ($file['size'] > 50 * 1024 * 1024) {
        return false;
    }
    
    // Determine media type
    $media_type = strpos($file['type'], 'image/') === 0 ? 'photo' : 'video';
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    
    // Upload path
    $upload_dir = '../assets/gallery/' . $event_id . '/';
    $file_path = $upload_dir . $filename;
    $relative_path = 'assets/gallery/' . $event_id . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Save to database
        $db = getDB();
        $sql = "INSERT INTO gallery (event_id, media_type, media_path, media_name, file_size, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        return $db->query($sql, [
            $event_id,
            $media_type,
            $relative_path,
            $file['name'],
            $file['size'],
            $_SESSION['admin_user']['username']
        ]);
    }
    
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - <?= SITE_NAME ?> Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom Admin CSS -->
    <link href="assets/css/admin.css" rel="stylesheet">
    
    <style>
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .gallery-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: white;
            transition: transform 0.2s;
        }
        
        .gallery-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .gallery-item img, .gallery-item video {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .gallery-item-info {
            padding: 10px;
        }
        
        .gallery-item-actions {
            padding: 10px;
            background: #f8f9fa;
            border-top: 1px solid #ddd;
        }
        
        .upload-zone {
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .upload-zone:hover {
            background: #e3f2fd;
            border-color: #0056b3;
        }
        
        .upload-zone.dragover {
            background: #e3f2fd;
            border-color: #0056b3;
        }
        
        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .file-preview-item {
            display: flex;
            align-items: center;
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .file-preview-item button {
            background: none;
            border: none;
            color: #dc3545;
            margin-left: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <?php include 'includes/admin_nav.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gallery Management</h1>
                </div>

                <!-- Admin Messages -->
                <?php if (isset($_SESSION['admin_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['admin_message']['type'] === 'error' ? 'danger' : $_SESSION['admin_message']['type'] ?> alert-dismissible fade show">
                        <?= htmlspecialchars($_SESSION['admin_message']['text']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['admin_message']); ?>
                <?php endif; ?>

                <!-- Event Selection -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Select Event</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="gallery.php">
                            <div class="row">
                                <div class="col-md-8">
                                    <select name="event_id" class="form-select" onchange="this.form.submit()">
                                        <option value="">Choose an event...</option>
                                        <?php foreach ($events as $event): ?>
                                            <option value="<?= $event['id'] ?>" <?= $selected_event_id == $event['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($event['title']) ?> (<?= date('M j, Y', strtotime($event['date'])) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>Load Gallery
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($selected_event): ?>
                <!-- Upload Section -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Upload Media</h5>
                        <span class="badge bg-info"><?= htmlspecialchars($selected_event['title']) ?></span>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="uploadForm">
                            <input type="hidden" name="action" value="upload">
                            <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                            
                            <div class="upload-zone" id="uploadZone">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                <h5>Drag & Drop Files Here</h5>
                                <p class="text-muted">or click to browse</p>
                                <input type="file" name="gallery_files[]" id="fileInput" multiple accept="image/*,video/*" style="display: none;">
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fileInput').click()">
                                    <i class="fas fa-folder-open me-2"></i>Browse Files
                                </button>
                            </div>
                            
                            <div class="file-preview" id="filePreview"></div>
                            
                            <div class="mt-3">
                                <button type="submit" class="btn btn-success" id="uploadBtn" disabled>
                                    <i class="fas fa-upload me-2"></i>Upload Files
                                </button>
                                <small class="text-muted ms-3">
                                    Supported formats: JPEG, PNG, GIF, WebP, MP4, AVI, MOV, WMV (max 50MB each)
                                </small>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Gallery Grid -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gallery Items</h5>
                        <span class="badge bg-secondary"><?= count($gallery_items) ?> items</span>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($gallery_items)): ?>
                            <div class="gallery-grid">
                                <?php foreach ($gallery_items as $item): ?>
                                    <div class="gallery-item">
                                        <?php if ($item['media_type'] === 'photo'): ?>
                                            <img src="../<?= htmlspecialchars($item['media_path']) ?>" alt="Gallery Image" loading="lazy">
                                        <?php else: ?>
                                            <video controls>
                                                <source src="../<?= htmlspecialchars($item['media_path']) ?>" type="video/mp4">
                                                Your browser does not support the video tag.
                                            </video>
                                        <?php endif; ?>
                                        
                                        <div class="gallery-item-info">
                                            <small class="text-muted d-block">
                                                <i class="fas fa-<?= $item['media_type'] === 'photo' ? 'image' : 'video' ?> me-1"></i>
                                                <?= ucfirst($item['media_type']) ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <?= htmlspecialchars($item['media_name']) ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <?= formatFileSize($item['file_size']) ?>
                                            </small>
                                            
                                            <!-- Caption -->
                                            <form method="POST" class="mt-2">
                                                <input type="hidden" name="action" value="update_caption">
                                                <input type="hidden" name="gallery_id" value="<?= $item['id'] ?>">
                                                <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                                                <div class="input-group input-group-sm">
                                                    <input type="text" name="caption" class="form-control" 
                                                           placeholder="Add caption..." 
                                                           value="<?= htmlspecialchars($item['caption'] ?? '') ?>">
                                                    <button type="submit" class="btn btn-outline-secondary">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                        
                                        <div class="gallery-item-actions">
                                            <div class="btn-group w-100">
                                                <a href="../<?= htmlspecialchars($item['media_path']) ?>" 
                                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteGalleryItem(<?= $item['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-images fa-4x text-muted mb-3"></i>
                                <h5>No Gallery Items</h5>
                                <p class="text-muted">Upload some photos or videos to get started!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this gallery item? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="deleteForm" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="gallery_id" id="deleteGalleryId">
                        <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // File upload handling
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const filePreview = document.getElementById('filePreview');
        const uploadBtn = document.getElementById('uploadBtn');
        let selectedFiles = [];

        // Drag and drop functionality
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            
            const files = Array.from(e.dataTransfer.files);
            addFiles(files);
        });

        // Click to upload
        uploadZone.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            const files = Array.from(this.files);
            addFiles(files);
        });

        function addFiles(files) {
            files.forEach(file => {
                if (!selectedFiles.find(f => f.name === file.name && f.size === file.size)) {
                    selectedFiles.push(file);
                }
            });
            
            updatePreview();
            updateUploadButton();
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updatePreview();
            updateUploadButton();
        }

        function updatePreview() {
            filePreview.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const preview = document.createElement('div');
                preview.className = 'file-preview-item';
                
                const icon = file.type.startsWith('image/') ? 'fa-image' : 'fa-video';
                
                preview.innerHTML = `
                    <i class="fas ${icon} me-2"></i>
                    ${file.name}
                    <button type="button" onclick="removeFile(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                filePreview.appendChild(preview);
            });
        }

        function updateUploadButton() {
            uploadBtn.disabled = selectedFiles.length === 0;
            
            // Update file input with selected files
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        }

        function deleteGalleryItem(galleryId) {
            document.getElementById('deleteGalleryId').value = galleryId;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }

        // Format file size helper
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>

<?php
// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>