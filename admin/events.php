<?php
require_once 'includes/auth.php';
require_once '../includes/functions.php';

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['bulk_action'];
    $items = json_decode($_POST['items'], true);
    
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items selected']);
        exit;
    }
    
    $db = getDB();
    $success_count = 0;
    
    foreach ($items as $item_id) {
        switch ($action) {
            case 'delete':
                $result = $db->query("DELETE FROM events WHERE id = ?", [$item_id]);
                break;
            case 'hide':
                $result = $db->query("UPDATE events SET is_hidden = TRUE WHERE id = ?", [$item_id]);
                break;
            case 'show':
                $result = $db->query("UPDATE events SET is_hidden = FALSE WHERE id = ?", [$item_id]);
                break;
        }
        
        if ($result) $success_count++;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Successfully processed $success_count item(s)"
    ]);
    exit;
}

// Pagination and filters
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;

$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
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

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$db = getDB();

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM events $where_clause";
$total_events = $db->fetchOne($count_sql, $params)['total'];
$total_pages = ceil($total_events / $limit);

// Get events
$events_sql = "SELECT *, 
                      (SELECT COUNT(*) FROM registrations WHERE event_id = events.id AND otp_verified = TRUE) as registration_count
               FROM events $where_clause 
               ORDER BY created_at DESC 
               LIMIT $limit OFFSET $offset";
$events = $db->fetchAll($events_sql, $params);

$page_title = 'Manage Events';
$current_page = 'events';
?>

<?php include 'includes/header.php'; ?>

<div class="content-wrapper">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Events</h2>
        <a href="event_add.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Add New Event
        </a>
    </div>
    
    <!-- Filters -->
    <div class="admin-table mb-4">
        <div class="p-3">
            <div class="row">
                <div class="col-md-4">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" 
                               placeholder="Search events..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <div class="col-md-4">
                    <form method="GET">
                        <?php if ($search): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="upcoming" <?= $status_filter === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="live" <?= $status_filter === 'live' ? 'selected' : '' ?>>Live</option>
                            <option value="past" <?= $status_filter === 'past' ? 'selected' : '' ?>>Past</option>
                        </select>
                    </form>
                </div>
                
                <div class="col-md-4 text-end">
                    <?php if ($search || $status_filter): ?>
                        <a href="events.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bulk Actions -->
    <div class="admin-table mb-4">
        <div class="p-3 border-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <select id="bulkAction" class="form-control me-2" style="width: auto;">
                            <option value="">Bulk Actions</option>
                            <option value="hide">Hide Selected</option>
                            <option value="show">Show Selected</option>
                            <option value="delete">Delete Selected</option>
                        </select>
                        <button id="bulkActionBtn" class="btn btn-outline-primary" disabled>
                            Apply
                        </button>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <span class="text-muted">
                        <?= $total_events ?> total events
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Events Table -->
    <div class="admin-table">
        <?php if (!empty($events)): ?>
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAll" class="form-check-input">
                        </th>
                        <th>Event Details</th>
                        <th>Date & Time</th>
                        <th>Registrations</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input item-checkbox" value="<?= $event['id'] ?>">
                            </td>
                            <td>
                                <div>
                                    <strong><?= htmlspecialchars($event['title']) ?></strong>
                                    <?php if ($event['is_hidden']): ?>
                                        <span class="badge badge-secondary ms-2">Hidden</span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(substr($event['venue'], 0, 50)) ?>
                                        <?php if ($event['is_paid']): ?>
                                            | ₹<?= number_format($event['price'], 2) ?>
                                        <?php else: ?>
                                            | Free
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= formatDate($event['date']) ?>
                                </div>
                                <div>
                                    <i class="fas fa-clock me-1"></i>
                                    <?= formatTime($event['time']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-primary">
                                    <?= $event['registration_count'] ?>
                                    <?php if ($event['max_registrations']): ?>
                                        / <?= $event['max_registrations'] ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <?= getStatusBadge($event['status']) ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="../event/<?= encodeId($event['id']) ?>" class="btn btn-outline-primary" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="event_edit.php?id=<?= $event['id'] ?>" class="btn btn-outline-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="gallery_upload.php?event_id=<?= $event['id'] ?>" class="btn btn-outline-info">
                                        <i class="fas fa-images"></i>
                                    </a>
                                    <button onclick="deleteEvent(<?= $event['id'] ?>)" class="btn btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5>No Events Found</h5>
                <p class="text-muted">
                    <?php if ($search || $status_filter): ?>
                        Try adjusting your search criteria or <a href="events.php">view all events</a>
                    <?php else: ?>
                        <a href="event_add.php" class="btn btn-primary">Add your first event</a>
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">
                            Previous
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
                            Next
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script>
function deleteEvent(id) {
    if (confirmDelete('Are you sure you want to delete this event? This action cannot be undone.')) {
        fetch('events.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `bulk_action=delete&items=${JSON.stringify([id])}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert(data.message, 'danger');
            }
        });
    }
}
</script>

<?php include 'includes/footer.php'; ?>
