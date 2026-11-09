<?php
require_once 'db.php';
require_once 'hashid.php';

// Sanitize input data
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Log page view
function logPageView($page_name, $event_id = null) {
    $db = getDB();
    
    $utm_source = $_GET['utm_source'] ?? null;
    $utm_medium = $_GET['utm_medium'] ?? null;
    $utm_campaign = $_GET['utm_campaign'] ?? null;
    
    $sql = "INSERT INTO event_views (event_id, page_name, ip_address, referrer, user_agent, utm_source, utm_medium, utm_campaign) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $db->query($sql, [
        $event_id,
        $page_name,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_REFERER'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null,
        $utm_source,
        $utm_medium,
        $utm_campaign
    ]);
}

// Get events with filters
function getEvents($status = null, $limit = null, $offset = 0, $include_hidden = false) {
    $db = getDB();
    
    $conditions = [];
    $params = [];
    
    if ($status) {
        $conditions[] = "status = ?";
        $params[] = $status;
    }
    
    if (!$include_hidden) {
        $conditions[] = "is_hidden = FALSE";
    }
    
    $where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
    $limitClause = $limit ? "LIMIT $limit OFFSET $offset" : "";
    
    $sql = "SELECT * FROM events $where ORDER BY date ASC, time ASC $limitClause";
    
    return $db->fetchAll($sql, $params);
}

// Get event by slug or ID
function getEventBySlug($slug) {
    $db = getDB();
    
    // If numeric, treat as ID
    if (is_numeric($slug)) {
        $sql = "SELECT * FROM events WHERE id = ? AND is_hidden = FALSE";
        return $db->fetchOne($sql, [intval($slug)]);
    }
    
    // Otherwise treat as slug
    $sql = "SELECT * FROM events WHERE slug = ? AND is_hidden = FALSE";
    return $db->fetchOne($sql, [$slug]);
}

// Get event by ID
function getEventById($id) {
    $db = getDB();
    $sql = "SELECT * FROM events WHERE id = ?";
    return $db->fetchOne($sql, [$id]);
}

// Check if user already registered for event
function isAlreadyRegistered($event_id, $email, $phone) {
    $db = getDB();
    $sql = "SELECT COUNT(*) as count FROM registrations 
            WHERE event_id = ? AND (email = ? OR phone = ?) AND otp_verified = TRUE";
    
    $result = $db->fetchOne($sql, [$event_id, $email, $phone]);
    return $result['count'] > 0;
}

// Get registration count for event
function getRegistrationCount($event_id) {
    $db = getDB();
    $sql = "SELECT COUNT(*) as count FROM registrations WHERE event_id = ? AND otp_verified = TRUE";
    $result = $db->fetchOne($sql, [$event_id]);
    return $result['count'];
}

// Format date for display
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Format time for display
function formatTime($time, $format = 'g:i A') {
    return date($format, strtotime($time));
}

// Get event status badge
function getStatusBadge($status) {
    $badges = [
        'upcoming' => '<span class="badge bg-primary">Upcoming</span>',
        'live' => '<span class="badge bg-success">Live</span>',
        'past' => '<span class="badge bg-secondary">Past</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-light">Unknown</span>';
}

// Upload file handler
function uploadFile($file, $event_id, $type = 'photo') {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'File upload error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large'];
    }
    
    $allowed_types = $type === 'video' ? ALLOWED_VIDEO_TYPES : ALLOWED_IMAGE_TYPES;
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }
    
    $upload_dir = UPLOAD_PATH . $type . 's/' . $event_id . '/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Save to database
        $db = getDB();
        $relative_path = 'assets/gallery/' . $type . 's/' . $event_id . '/' . $filename;
        
        $sql = "INSERT INTO gallery (event_id, media_type, media_path, media_name, file_size) 
                VALUES (?, ?, ?, ?, ?)";
        
        $db->query($sql, [$event_id, $type, $relative_path, $file['name'], $file['size']]);
        
        return ['success' => true, 'path' => $relative_path];
    }
    
    return ['success' => false, 'message' => 'Failed to save file'];
}

// Get gallery items for event
function getGalleryItems($event_id, $type = null) {
    $db = getDB();
    
    $where = "event_id = ?";
    $params = [$event_id];
    
    if ($type) {
        $where .= " AND media_type = ?";
        $params[] = $type;
    }
    
    $sql = "SELECT * FROM gallery WHERE $where ORDER BY created_at DESC";
    return $db->fetchAll($sql, $params);
}

// Export to CSV
function exportToCSV($data, $filename, $headers) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, $headers);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Get dashboard stats
function getDashboardStats() {
    $db = getDB();
    
    // Total events
    $total_events = $db->fetchOne("SELECT COUNT(*) as count FROM events")['count'];
    
    // Total registrations
    $total_registrations = $db->fetchOne("SELECT COUNT(*) as count FROM registrations WHERE otp_verified = TRUE")['count'];
    
    // Today's traffic
    $today_traffic = $db->fetchOne("SELECT COUNT(*) as count FROM event_views WHERE DATE(created_at) = CURRENT_DATE")['count'];
    
    // Total payments (assuming paid registrations)
    $total_payments = $db->fetchOne("SELECT SUM(e.price) as total FROM registrations r 
                                     JOIN events e ON r.event_id = e.id 
                                     WHERE r.otp_verified = TRUE AND e.is_paid = TRUE AND r.payment_status = 'completed'")['total'] ?? 0;
    
    return [
        'total_events' => $total_events,
        'total_registrations' => $total_registrations,
        'today_traffic' => $today_traffic,
        'total_payments' => number_format($total_payments, 2)
    ];
}

// Get recent registrations
function getRecentRegistrations($limit = 5) {
    $db = getDB();
    $sql = "SELECT r.*, e.title as event_title 
            FROM registrations r 
            JOIN events e ON r.event_id = e.id 
            WHERE r.otp_verified = TRUE 
            ORDER BY r.created_at DESC 
            LIMIT ?";
    
    return $db->fetchAll($sql, [$limit]);
}

// Extract YouTube video ID from URL
function getYouTubeVideoId($url) {
    if (empty($url)) return null;
    
    $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
    preg_match($pattern, $url, $matches);
    
    return isset($matches[1]) ? $matches[1] : null;
}

// Clean old OTP records
function cleanExpiredOTPs() {
    $db = getDB();
    $sql = "DELETE FROM otp_verifications WHERE expires_at < NOW()";
    $db->query($sql);
}

?>
