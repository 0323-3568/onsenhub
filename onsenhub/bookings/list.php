<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$pageTitle = "My Bookings";

// Allowed sorting options and their corresponding SQL order clauses
$allowedSorts = [
    'id' => 'r.id ASC',
    'price' => 'r.total_price DESC',
    'check_in' => 'r.check_in ASC'  // Changed to ascending order for earliest to latest
];

// Get sort parameter from query string, default to 'check_in'
$sort = $_GET['sort'] ?? 'check_in';
if (!array_key_exists($sort, $allowedSorts)) {
    $sort = 'check_in';
}

function updateExpiredBookings($pdo) {
    $today = date('Y-m-d');
    // Update bookings to expired if check_out date is before today and status is confirmed
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'expired' WHERE check_out < ? AND status IN ('confirmed', 'modification approved')");
    $stmt->execute([$today]);
}

// Get user's bookings with dynamic order by
$stmt = $pdo->prepare("
    SELECT r.*, rm.name as room_name, rm.image as room_image
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.user_id = ?
    ORDER BY " . $allowedSorts[$sort] . "
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['overlayMessage']) && isset($_SESSION['overlayType'])) {
    $overlayMessage = $_SESSION['overlayMessage'];
    $overlayType = $_SESSION['overlayType'];
    unset($_SESSION['overlayMessage']);
    unset($_SESSION['overlayType']);
    include '../includes/overlay.php';
}
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <?php if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']): ?>
        <h2 class="mb-2 mb-md-0">My Bookings</h2>
        <?php endif; ?>
        <div class="d-flex align-items-center">
            <label for="sortSelect" class="me-2 mb-0">Sort by:</label>
            <select id="sortSelect" class="form-select form-select-sm" style="width: auto;" onchange="location = this.value;">
                <option value="list.php?sort=id" <?php if ($sort === 'id') echo 'selected'; ?>>Booking ID</option>
                <option value="list.php?sort=price" <?php if ($sort === 'price') echo 'selected'; ?>>Price (High to Low)</option>
                <option value="list.php?sort=check_in" <?php if ($sort === 'check_in') echo 'selected'; ?>>Check-in Date</option>
            </select>
            <a href="create.php" class="btn btn-primary ms-3">New Booking</a>
        </div>
    </div>
    
<?php if (empty($bookings)): ?>
    <div class="alert alert-info">
        You don't have any bookings yet. <a href="create.php" class="alert-link">Book a room now</a>.
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($bookings as $booking): 
            $checkIn = new DateTime($booking['check_in']);
            $checkOut = new DateTime($booking['check_out']);
            $nights = $checkOut->diff($checkIn)->days;
        ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm">
                <img src="<?php echo SITE_URL; ?>/assets/img/<?php echo $booking['room_image'] ?? 'default.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($booking['room_name']); ?>" style="height: 180px; object-fit: cover;">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title"><?php echo htmlspecialchars($booking['room_name']); ?></h5>
                    <p class="card-text mb-1">
                        <strong>Booking #<?php echo $booking['id']; ?></strong><br>
                        <small class="text-muted">
                            <?php echo $checkIn->format('M j, Y'); ?> - <?php echo $checkOut->format('M j, Y'); ?><br>
                            <?php echo $nights; ?> night<?php echo $nights != 1 ? 's' : ''; ?>
                        </small>
                    </p>
                    <p class="card-text mb-1"><strong>Guests:</strong> <?php echo $booking['guests']; ?></p>
                    <p class="card-text mb-1"><strong>Total:</strong> ₱<?php echo number_format($booking['total_price'], 2); ?></p>
                    <p class="card-text mb-3">
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
                            $statusClass = 'warning'; // Default for pending, requests
                            if ($booking['status'] == 'confirmed') {
                                $statusClass = 'success';
                            } elseif (in_array($booking['status'], ['cancelled', 'expired'])) {
                                $statusClass = 'danger';
                            }
                            echo '<span class="badge bg-' . $statusClass . '">' . ucfirst($booking['status']) . '</span>';
                        }
                        ?>
                    </p>
                    <div class="mt-auto">
                        <a href="view.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-primary me-2">View</a>
                        <?php
                        $allowModificationAction = false;
                        if ($booking['status'] == 'pending') {
                            $allowModificationAction = true;
                        } elseif (in_array($booking['status'], ['confirmed', 'modification approved', 'modification requested', 'modification denied']) && canModifyBookingByTime($booking['check_in'])) {
                            $allowModificationAction = true;
                        }

                        if ($allowModificationAction) {
                            echo '<a href="edit.php?id=' . $booking['id'] . '" class="btn btn-sm btn-outline-secondary">Edit</a>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
