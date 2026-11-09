<?php
require_once 'includes/auth.php';
require_once '../includes/functions.php';

// Handle AJAX requests for chart data
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $db = getDB();
    
    switch ($_GET['action']) {
        case 'registration_data':
            // Get registration data for the last 30 days
            $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                    FROM registrations 
                    WHERE otp_verified = TRUE AND created_at >= CURRENT_DATE - INTERVAL '30 days'
                    GROUP BY DATE(created_at) 
                    ORDER BY date";
            $data = $db->fetchAll($sql);
            
            $labels = [];
            $values = [];
            
            foreach ($data as $row) {
                $labels[] = date('M j', strtotime($row['date']));
                $values[] = intval($row['count']);
            }
            
            echo json_encode(['labels' => $labels, 'values' => $values]);
            exit;
            
        case 'traffic_data':
            // Get traffic source data
            $sql = "SELECT 
                        COALESCE(utm_source, 'Direct') as source,
                        COUNT(*) as count
                    FROM event_views 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY COALESCE(utm_source, 'Direct') 
                    ORDER BY count DESC 
                    LIMIT 10";
            $data = $db->fetchAll($sql);
            
            $labels = [];
            $values = [];
            
            foreach ($data as $row) {
                $labels[] = $row['source'];
                $values[] = intval($row['count']);
            }
            
            echo json_encode(['labels' => $labels, 'values' => $values]);
            exit;
    }
}

// Handle CSV exports
if (isset($_GET['export'])) {
    $db = getDB();
    
    switch ($_GET['export']) {
        case 'events':
            $events = $db->fetchAll("SELECT * FROM events ORDER BY created_at DESC");
            $headers = ['ID', 'Title', 'Date', 'Time', 'Venue', 'Price', 'Status', 'Is Hidden', 'Created At'];
            $data = [];
            
            foreach ($events as $event) {
                $data[] = [
                    $event['id'],
                    $event['title'],
                    $event['date'],
                    $event['time'],
                    $event['venue'],
                    $event['is_paid'] ? $event['price'] : 'Free',
                    $event['status'],
                    $event['is_hidden'] ? 'Yes' : 'No',
                    $event['created_at']
                ];
            }
            
            exportToCSV($data, 'events_' . date('Y-m-d') . '.csv', $headers);
            break;
            
        case 'analytics':
            $analytics = $db->fetchAll("
                SELECT 
                    ev.page_name,
                    ev.utm_source,
                    ev.utm_medium,
                    ev.utm_campaign,
                    ev.referrer,
                    ev.created_at,
                    e.title as event_title
                FROM event_views ev
                LEFT JOIN events e ON ev.event_id = e.id
                ORDER BY ev.created_at DESC
            ");
            
            $headers = ['Page', 'Event', 'UTM Source', 'UTM Medium', 'UTM Campaign', 'Referrer', 'Date'];
            $data = [];
            
            foreach ($analytics as $row) {
                $data[] = [
                    $row['page_name'],
                    $row['event_title'] ?? 'N/A',
                    $row['utm_source'] ?? '',
                    $row['utm_medium'] ?? '',
                    $row['utm_campaign'] ?? '',
                    $row['referrer'] ?? '',
                    $row['created_at']
                ];
            }
            
            exportToCSV($data, 'analytics_' . date('Y-m-d') . '.csv', $headers);
            break;
    }
}

$db = getDB();

// Get analytics data
$analytics_data = [
    'total_views' => $db->fetchOne("SELECT COUNT(*) as count FROM event_views")['count'],
    'unique_visitors' => $db->fetchOne("SELECT COUNT(DISTINCT ip_address) as count FROM event_views")['count'],
    'total_events' => $db->fetchOne("SELECT COUNT(*) as count FROM events")['count'],
    'total_registrations' => $db->fetchOne("SELECT COUNT(*) as count FROM registrations WHERE otp_verified = TRUE")['count'],
    'today_views' => $db->fetchOne("SELECT COUNT(*) as count FROM event_views WHERE DATE(created_at) = CURRENT_DATE")['count'],
    'this_month_registrations' => $db->fetchOne("SELECT COUNT(*) as count FROM registrations WHERE otp_verified = TRUE AND EXTRACT(MONTH FROM created_at) = EXTRACT(MONTH FROM NOW())")['count']
];

// Get top events by views
$top_events = $db->fetchAll("
    SELECT 
        e.title,
        e.date,
        COUNT(ev.id) as view_count,
        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id AND otp_verified = TRUE) as registration_count
    FROM events e
    LEFT JOIN event_views ev ON e.id = ev.event_id
    GROUP BY e.id
    ORDER BY view_count DESC
    LIMIT 10
");

// Get UTM campaign performance
$utm_performance = $db->fetchAll("
    SELECT 
        utm_campaign,
        utm_source,
        COUNT(DISTINCT ip_address) as unique_visitors,
        COUNT(*) as total_views,
        (SELECT COUNT(*) FROM registrations WHERE utm_campaign = ev.utm_campaign AND otp_verified = TRUE) as conversions
    FROM event_views ev
    WHERE utm_campaign IS NOT NULL
    GROUP BY utm_campaign, utm_source
    ORDER BY conversions DESC, total_views DESC
    LIMIT 10
");

// Get recent activity
$recent_activity = $db->fetchAll("
    SELECT 
        'page_view' as type,
        ev.page_name as description,
        e.title as event_title,
        ev.created_at
    FROM event_views ev
    LEFT JOIN events e ON ev.event_id = e.id
    UNION ALL
    SELECT 
        'registration' as type,
        CONCAT('Registration by ', r.name) as description,
        e.title as event_title,
        r.created_at
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    WHERE r.otp_verified = TRUE
    ORDER BY created_at DESC
    LIMIT 20
");

$page_title = 'Analytics';
$current_page = 'analytics';
?>

<?php include 'includes/header.php'; ?>

<div class="content-wrapper">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Analytics & Reports</h2>
        <div class="d-flex gap-2">
            <a href="?export=events" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-download me-1"></i>
                Export Events
            </a>
            <a href="?export=analytics" class="btn btn-outline-success btn-sm">
                <i class="fas fa-download me-1"></i>
                Export Analytics
            </a>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="dashboard-stats mb-5">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-title">Total Page Views</div>
                <div class="stat-card-icon">
                    <i class="fas fa-eye"></i>
                </div>
            </div>
            <div class="stat-card-value"><?= number_format($analytics_data['total_views']) ?></div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-card-header">
                <div class="stat-card-title">Unique Visitors</div>
                <div class="stat-card-icon success">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-card-value"><?= number_format($analytics_data['unique_visitors']) ?></div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-card-header">
                <div class="stat-card-title">Today's Views</div>
                <div class="stat-card-icon warning">
                    <i class="fas fa-calendar-day"></i>
                </div>
            </div>
            <div class="stat-card-value"><?= number_format($analytics_data['today_views']) ?></div>
        </div>
        
        <div class="stat-card danger">
            <div class="stat-card-header">
                <div class="stat-card-title">This Month's Registrations</div>
                <div class="stat-card-icon danger">
                    <i class="fas fa-user-plus"></i>
                </div>
            </div>
            <div class="stat-card-value"><?= number_format($analytics_data['this_month_registrations']) ?></div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="row mb-5">
        <div class="col-lg-6">
            <div class="admin-table">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0">Registration Trends (Last 30 Days)</h5>
                </div>
                <div class="p-3">
                    <canvas id="registrationChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="admin-table">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0">Traffic Sources (Last 30 Days)</h5>
                </div>
                <div class="p-3">
                    <canvas id="trafficChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Data Tables -->
    <div class="row">
        <!-- Top Events -->
        <div class="col-lg-6 mb-4">
            <div class="admin-table">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0">Top Events by Views</h5>
                </div>
                
                <?php if (!empty($top_events)): ?>
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Views</th>
                                <th>Registrations</th>
                                <th>Conversion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_events as $event): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars(substr($event['title'], 0, 30)) ?><?= strlen($event['title']) > 30 ? '...' : '' ?></strong>
                                            <br><small class="text-muted"><?= formatDate($event['date']) ?></small>
                                        </div>
                                    </td>
                                    <td><?= number_format($event['view_count']) ?></td>
                                    <td><?= number_format($event['registration_count']) ?></td>
                                    <td>
                                        <?php 
                                        $conversion = $event['view_count'] > 0 ? ($event['registration_count'] / $event['view_count']) * 100 : 0;
                                        ?>
                                        <span class="badge badge-<?= $conversion > 5 ? 'success' : ($conversion > 2 ? 'warning' : 'secondary') ?>">
                                            <?= number_format($conversion, 1) ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No event data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- UTM Campaign Performance -->
        <div class="col-lg-6 mb-4">
            <div class="admin-table">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0">UTM Campaign Performance</h5>
                </div>
                
                <?php if (!empty($utm_performance)): ?>
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Source</th>
                                <th>Visitors</th>
                                <th>Conversions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utm_performance as $campaign): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars(substr($campaign['utm_campaign'], 0, 20)) ?><?= strlen($campaign['utm_campaign']) > 20 ? '...' : '' ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?= htmlspecialchars($campaign['utm_source']) ?></span>
                                    </td>
                                    <td><?= number_format($campaign['unique_visitors']) ?></td>
                                    <td>
                                        <strong><?= number_format($campaign['conversions']) ?></strong>
                                        <?php if ($campaign['unique_visitors'] > 0): ?>
                                            <br><small class="text-muted">
                                                (<?= number_format(($campaign['conversions'] / $campaign['unique_visitors']) * 100, 1) ?>%)
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No UTM campaign data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="admin-table">
                <div class="p-3 border-bottom">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                
                <?php if (!empty($recent_activity)): ?>
                    <div class="p-3">
                        <div class="recent-activity">
                            <?php foreach ($recent_activity as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-avatar">
                                        <i class="fas fa-<?= $activity['type'] === 'registration' ? 'user-plus' : 'eye' ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <h6><?= htmlspecialchars($activity['description']) ?></h6>
                                        <p><?= htmlspecialchars($activity['event_title'] ?? 'General') ?></p>
                                    </div>
                                    <div class="activity-time">
                                        <?= formatDate($activity['created_at'], 'M j, g:i A') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">No recent activity</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php include 'includes/footer.php'; ?>
