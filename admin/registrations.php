<?php
require_once 'includes/auth.php';
require_once '../includes/functions.php';

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $event_id = intval($_GET['event_id'] ?? 0);
    
    $db = getDB();
    
    if ($event_id) {
        $sql = "SELECT r.*, e.title as event_title 
                FROM registrations r 
                JOIN events e ON r.event_id = e.id 
                WHERE r.event_id = ? AND r.otp_verified = TRUE 
                ORDER BY r.created_at DESC";
        $registrations = $db->fetchAll($sql, [$event_id]);
        $filename = "registrations_event_" . $event_id . "_" . date('Y-m-d') . ".csv";
    } else {
        $sql = "SELECT r.*, e.title as event_title 
                FROM registrations r 
                JOIN events e ON r.event_id = e.id 
                WHERE r.otp_verified = TRUE 
                ORDER BY r.created_at DESC";
        $registrations = $db->fetchAll($sql);
        $filename = "all_registrations_" . date('Y-m-d') . ".csv";
    }
    
    $headers = ['ID', 'Event', 'Name', 'Email', 'Phone', 'Payment Status', 'UTM Source', 'UTM Medium', 'UTM Campaign', 'Registration Date'];
    
    $data = [];
    foreach ($registrations as $reg) {
        $data[] = [
            $reg['id'],
            $reg['event_title'],
            $reg['name'],
            $reg['email'],
            $reg['phone'],
            $reg['payment_status'],
            $reg['utm_source'] ?? '',
            $reg['utm_medium'] ?? '',
            $reg['utm_campaign'] ?? '',
            formatDate($reg['created_at'], 'Y-m-d H:i:s')
        ];
    }
    
    exportToCSV($data, $filename, $headers);
    exit;
}

// Pagination and filters
$page = max(1, intval($_GET['page'] ?? 1));
$limit = REGISTRATIONS_PER_PAGE;
$offset = ($page - 1) * $limit;

$event_filter = intval($_GET['event_id'] ?? 0);
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ['r.otp_verified = TRUE']; // Only show verified registrations
$params = [];

if ($event_filter) {
    $where_conditions[] = 'r.event_id = ?';
    $params[] = $event_filter;
}

if ($status_filter && in_array($status_filter, ['completed', 'pending', 'failed'])) {
    $where_conditions[] = 'r.payment_status = ?';
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = '(r.name LIKE ? OR r.email LIKE ? OR r.phone LIKE ? OR e.title LIKE ?)';
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

$db = getDB();

// Get total count
$count_sql = "SELECT COUNT(*) as total 
              FROM registrations r 
              JOIN events e ON r.event_id = e.id 
              $where_clause";
$total_registrations = $db->fetchOne($count_sql, $params)['total'];
$total_pages = ceil($total_registrations / $limit);

// Get registrations
$registrations_sql = "SELECT r.*, e.title as event_title, e.date as event_date, e.time as event_time, e.is_paid, e.price
                      FROM registrations r 
                      JOIN events e ON r.event_id = e.id 
                      $where_clause 
                      ORDER BY r.created_at DESC 
                      LIMIT $limit OFFSET $offset";
$registrations = $db->fetchAll($registrations_sql, $params);

// Get events for filter dropdown
$events = $db->fetchAll("SELECT id, title FROM events ORDER BY date DESC");

$page_title = 'Registrations';
$current_page = 'registrations';
?>

<?php include 'includes/header.php'; ?>

<div class="content-wrapper">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Registrations</h2>
        <div class="d-flex gap-2">
            <a href="?export=csv<?= $event_filter ? '&event_id=' . $event_filter : '' ?>" class="btn btn-outline-success btn-sm">
                <i class="fas fa-download me-1"></i>
                Export CSV
            </a>
            <a href="events.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-calendar-alt me-1"></i>
                Manage Events
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="admin-table mb-4">
        <div class="p-3">
            <div class="row">
                <div class="col-md-3">
                    <form method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" 
                               placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <div class="col-md-3">
                    <form method="GET">
                        <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                        <?php if ($status_filter): ?><input type="hidden" name="status" value="<?= $status_filter ?>"><?php endif; ?>
                        <select name="event_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Events</option>
                            <?php foreach ($events as $event): ?>
                                <option value="<?= $event['id'] ?>" <?= $event_filter == $event['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($event['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                
                <div class="col-md-3">
                    <form method="GET">
                        <?php if ($search): ?><input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                        <?php if ($event_filter): ?><input type="hidden" name="event_id" value="<?= $event_filter ?>"><?php endif; ?>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Failed</option>
                        </select>
                    </form>
                </div>
                
                <div class="col-md-3 text-end">
                    <?php if ($search || $event_filter || $status_filter): ?>
                        <a href="registrations.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-title">Total Registrations</div>
                    <div class="stat-card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?= $total_registrations ?></div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card success">
                <div class="stat-card-header">
                    <div class="stat-card-title">Paid Registrations</div>
                    <div class="stat-card-icon success">
                        <i class="fas fa-credit-card"></i>
                    </div>
                </div>
                <div class="stat-card-value">
                    <?= $db->fetchOne("SELECT COUNT(*) as count FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.otp_verified = TRUE AND e.is_paid = TRUE AND r.payment_status = 'completed'")['count'] ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card warning">
                <div class="stat-card-header">
                    <div class="stat-card-title">Today's Registrations</div>
                    <div class="stat-card-icon warning">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <div class="stat-card-value">
                    <?= $db->fetchOne("SELECT COUNT(*) as count FROM registrations WHERE otp_verified = TRUE AND DATE(created_at) = CURRENT_DATE")['count'] ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card danger">
                <div class="stat-card-header">
                    <div class="stat-card-title">Total Revenue</div>
                    <div class="stat-card-icon danger">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                </div>
                <div class="stat-card-value">
                    ₹<?= number_format($db->fetchOne("SELECT SUM(e.price) as total FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.otp_verified = TRUE AND e.is_paid = TRUE AND r.payment_status = 'completed'")['total'] ?? 0, 2) ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Registrations Table -->
    <div class="admin-table">
        <?php if (!empty($registrations)): ?>
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Registrant Details</th>
                        <th>Event</th>
                        <th>Registration Date</th>
                        <th>Payment</th>
                        <th>UTM Source</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?= htmlspecialchars($reg['name']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($reg['email']) ?></small>
                                    <br><small class="text-muted"><?= htmlspecialchars($reg['phone']) ?></small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong><?= htmlspecialchars($reg['event_title']) ?></strong>
                                    <br><small class="text-muted">
                                        <?= formatDate($reg['event_date']) ?> at <?= formatTime($reg['event_time']) ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <?= formatDate($reg['created_at'], 'M j, Y') ?>
                                    <br><small class="text-muted"><?= formatDate($reg['created_at'], 'g:i A') ?></small>
                                </div>
                            </td>
                            <td>
                                <?php if ($reg['is_paid']): ?>
                                    <div>
                                        <span class="badge badge-<?= $reg['payment_status'] === 'completed' ? 'success' : ($reg['payment_status'] === 'pending' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst($reg['payment_status']) ?>
                                        </span>
                                        <br><small>₹<?= number_format($reg['price'], 2) ?></small>
                                    </div>
                                <?php else: ?>
                                    <span class="badge badge-success">Free Event</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($reg['utm_source']): ?>
                                    <div>
                                        <strong>Source:</strong> <?= htmlspecialchars($reg['utm_source']) ?>
                                        <?php if ($reg['utm_medium']): ?><br><strong>Medium:</strong> <?= htmlspecialchars($reg['utm_medium']) ?><?php endif; ?>
                                        <?php if ($reg['utm_campaign']): ?><br><strong>Campaign:</strong> <?= htmlspecialchars($reg['utm_campaign']) ?><?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <small class="text-muted">Direct</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="../event/<?= encodeId($reg['event_id']) ?>" class="btn btn-outline-primary" target="_blank" title="View Event">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="showRegistrationDetails(<?= htmlspecialchars(json_encode($reg)) ?>)" class="btn btn-outline-info" title="View Details">
                                        <i class="fas fa-info"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-user-times fa-3x text-muted mb-3"></i>
                <h5>No Registrations Found</h5>
                <p class="text-muted">
                    <?php if ($search || $event_filter || $status_filter): ?>
                        Try adjusting your search criteria or <a href="registrations.php">view all registrations</a>
                    <?php else: ?>
                        No registrations have been made yet.
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
                        <a class="page-link" href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $event_filter ? '&event_id=' . $event_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">
                            Previous
                        </a>
                    </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $event_filter ? '&event_id=' . $event_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $event_filter ? '&event_id=' . $event_filter : '' ?><?= $status_filter ? '&status=' . $status_filter : '' ?>">
                            Next
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Registration Details Modal -->
<div class="modal fade" id="registrationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registration Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="registrationDetails">
                <!-- Details will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showRegistrationDetails(registration) {
    const modal = new bootstrap.Modal(document.getElementById('registrationModal'));
    const detailsDiv = document.getElementById('registrationDetails');
    
    detailsDiv.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Personal Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Name:</strong></td><td>${registration.name}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>${registration.email}</td></tr>
                    <tr><td><strong>Phone:</strong></td><td>${registration.phone}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Event Information</h6>
                <table class="table table-sm">
                    <tr><td><strong>Event:</strong></td><td>${registration.event_title}</td></tr>
                    <tr><td><strong>Date:</strong></td><td>${registration.event_date} at ${registration.event_time}</td></tr>
                    <tr><td><strong>Payment:</strong></td><td>${registration.is_paid == 1 ? '₹' + parseFloat(registration.price).toFixed(2) : 'Free'}</td></tr>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h6>UTM Tracking</h6>
                <table class="table table-sm">
                    <tr><td><strong>Source:</strong></td><td>${registration.utm_source || 'N/A'}</td></tr>
                    <tr><td><strong>Medium:</strong></td><td>${registration.utm_medium || 'N/A'}</td></tr>
                    <tr><td><strong>Campaign:</strong></td><td>${registration.utm_campaign || 'N/A'}</td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Technical Details</h6>
                <table class="table table-sm">
                    <tr><td><strong>IP Address:</strong></td><td>${registration.ip_address || 'N/A'}</td></tr>
                    <tr><td><strong>Registration ID:</strong></td><td>#${registration.id.toString().padStart(6, '0')}</td></tr>
                    <tr><td><strong>Payment ID:</strong></td><td>${registration.payment_id || 'N/A'}</td></tr>
                </table>
            </div>
        </div>
    `;
    
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>
