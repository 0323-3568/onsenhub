<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

// Handle confirm and cancel actions
if (isset($_GET['confirm'])) {
    $reservationId = (int)$_GET['confirm'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ? AND status = 'pending'");
    $stmt->execute([$reservationId]);
    if ($stmt->rowCount()) {
        logActivity('Reservation Confirmed', "Reservation ID: $reservationId");
    }
    header("Location: reservations.php");
    exit();
}

if (isset($_GET['cancel'])) {
    $reservationId = (int)$_GET['cancel'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
    $stmt->execute([$reservationId]);
    if ($stmt->rowCount()) {
        logActivity('Reservation Cancelled', "Reservation ID: $reservationId");
    }
    header("Location: reservations.php");
    exit();
}

$pageTitle = "Manage Reservations";

// Get all reservations
$query = "
    SELECT r.*, u.username, u.email, rm.name as room_name
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN rooms rm ON r.room_id = rm.id
";

// Handle status filter
$status = isset($_GET['status']) ? $_GET['status'] : '';
if (in_array($status, ['pending', 'confirmed', 'cancelled'])) {
    $query .= " WHERE r.status = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$status]);
} else {
    $stmt = $pdo->query($query);
}

$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Reservations</h2>
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="reservations.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php if (empty($reservations)): ?>
            <div class="alert alert-info">No reservations found</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Room</th>
                            <th>Dates</th>
                            <th>Guests</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $reservation): 
                            $checkIn = new DateTime($reservation['check_in']);
                            $checkOut = new DateTime($reservation['check_out']);
                            $nights = $checkOut->diff($checkIn)->days;
                        ?>
                        <tr>
                            <td><?php echo $reservation['id']; ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($reservation['username']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($reservation['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($reservation['room_name']); ?></td>
                            <td>
                                <?php echo $checkIn->format('M j'); ?> - 
                                <?php echo $checkOut->format('M j, Y'); ?>
                                <br>
                                <small><?php echo $nights; ?> night<?php echo $nights != 1 ? 's' : ''; ?></small>
                            </td>
                            <td><?php echo $reservation['guests']; ?></td>
                            <td>₱<?php echo number_format($reservation['total_price'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $reservation['status'] == 'confirmed' ? 'success' : 
                                    ($reservation['status'] == 'cancelled' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($reservation['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="../bookings/view.php?id=<?php echo $reservation['id']; ?>">View</a></li>
                                        <?php if ($reservation['status'] == 'pending'): ?>
                                        <li><a class="dropdown-item" href="?confirm=<?php echo $reservation['id']; ?>">Confirm</a></li>
                                        <li><a class="dropdown-item" href="?cancel=<?php echo $reservation['id']; ?>">Cancel</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>