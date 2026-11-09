<?php
// Track page views for analytics
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $page_name = sanitize($input['page'] ?? '');
    $event_id = isset($input['event_id']) ? intval($input['event_id']) : null;
    
    if ($page_name) {
        logPageView($page_name, $event_id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid page name']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>