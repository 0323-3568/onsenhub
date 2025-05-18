<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$pageTitle = "Admin Dashboard";

// Get stats
$usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$roomsCount = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$bookingsCount = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();

// Handle potential NULL revenue
$revenue = (float)$pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM reservations WHERE status = 'confirmed'")->fetchColumn();

// Get recent bookings
$recentBookings = $pdo->query("
    SELECT r.*, u.username, rm.name as room_name
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN rooms rm ON r.room_id = rm.id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent activity logs
$recentLogs = $pdo->query("
    SELECT l.*, u.username
    FROM activity_logs l
    LEFT JOIN users u ON l.user_id = u.id
    ORDER BY l.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-5">
    <h2 class="mb-4">Admin Dashboard</h2>
    
    <style>
        .dashboard-card {
            background-color: #000000 !important;
            color: white !important;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .dashboard-card:hover {
            background-color: #1a1a1a !important;
            color: white !important;
            text-decoration: none;
        }
        @media (max-width: 767.98px) {
            .dashboard-card {
                margin-bottom: 1rem !important;
            }
            .dashboard-row > [class*="col-"] {
                margin-bottom: 1rem;
            }
        }
        .dashboard-icon-wrapper {
        width: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        }
    </style>
    <div class="row mb-4 dashboard-row">
        <div class="col-md-3">
            <a href="users.php" class="card dashboard-card text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 me-3 dashboard-icon-wrapper">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">Users</h5>
                        <p class="card-text display-6 mb-0"><?php echo $usersCount; ?></p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="rooms.php" class="card dashboard-card text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 me-3 dashboard-icon-wrapper">
                        <i class="fas fa-bed fa-2x"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">Rooms</h5>
                        <p class="card-text display-6 mb-0"><?php echo $roomsCount; ?></p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="reservations.php" class="card dashboard-card text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 me-3 dashboard-icon-wrapper">
                        <i class="fas fa-calendar-check fa-2x"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">Bookings</h5>
                        <p class="card-text display-6 mb-0"><?php echo $bookingsCount; ?></p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="#" class="card dashboard-card text-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0 me-3 dashboard-icon-wrapper">
                        <i class="fas fa-dollar-sign fa-2x"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="card-title mb-1">Revenue</h5>
                        <p class="card-text display-6 mb-0">₱<?php echo formatLargeNumber($revenue); ?></p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Bookings</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentBookings)): ?>
                    <p class="text-muted">No recent bookings</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Room</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td><?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                    <td>
                                        <?php
                                        if ($booking['status'] == 'modification approved') {
                                            echo '<span class="badge bg-success me-1">Confirmed</span>';
                                            echo '<span class="badge bg-success">Modification Approved</span>';
                                        } elseif ($booking['status'] == 'modification denied') {
                                            echo '<span class="badge bg-success me-1">Confirmed</span>';
                                            echo '<span class="badge bg-danger">Modification Denied</span>';
                                        } elseif ($booking['status'] == 'modification requested') {
                                            echo '<span class="badge bg-success me-1">Confirmed</span>';
                                            echo '<span class="badge bg-warning">Modification Requested</span>';
                                        } else {
                                            $statusClass = 'warning'; // Default
                                            if ($booking['status'] == 'confirmed') {
                                                $statusClass = 'success';
                                            } elseif (in_array($booking['status'], ['cancelled', 'expired'])) {
                                                $statusClass = 'danger';
                                            }
                                            echo '<span class="badge bg-' . $statusClass . '">' . ucfirst($booking['status']) . '</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="reservations.php" class="btn btn-primary">View All</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentLogs)): ?>
                    <p class="text-muted">No recent activity</p>
                    <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($recentLogs as $log): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <small class="text-muted"><?php echo date('M j, H:i', strtotime($log['created_at'])); ?></small>
                                <small><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></small>
                            </div>
                            <div><?php echo htmlspecialchars($log['action']); ?></div>
                            <?php if ($log['details']): ?>
                            <small class="text-muted"><?php echo htmlspecialchars($log['details']); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>