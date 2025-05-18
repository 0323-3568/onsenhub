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
            background-color: #343a40 !important; /* dark grey */
            color: white !important;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
            margin-bottom: 1rem; /* Add bottom margin for spacing */
        }
        .dashboard-card:hover {
            background-color: #495057 !important;
            color: white !important;
            text-decoration: none;
        }
        @media (max-width: 767.98px) {
            .dashboard-card {
                margin-bottom: 1rem !important; /* Ensure spacing on small devices */
            }
            .dashboard-row > [class*="col-"] {
                margin-bottom: 1rem; /* Add spacing between columns on small devices */
            }
        }
    </style>
    <div class="row mb-4 dashboard-row">
        <div class="col-md-3">
            <a href="users.php" class="card dashboard-card text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Users</h5>
                    <p class="card-text display-6"><?php echo $usersCount; ?></p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="rooms.php" class="card dashboard-card text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Rooms</h5>
                    <p class="card-text display-6"><?php echo $roomsCount; ?></p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="reservations.php" class="card dashboard-card text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Bookings</h5>
                    <p class="card-text display-6"><?php echo $bookingsCount; ?></p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="#" class="card dashboard-card text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Revenue</h5>
                    <p class="card-text display-6"><?php echo formatCurrency($revenue); ?></p>
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
                                        <span class="badge bg-<?php 
                                            echo $booking['status'] == 'confirmed' ? 'success' : 
                                            ($booking['status'] == 'cancelled' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="reservations.php" class="btn btn-sm btn-outline-primary">View All</a>
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