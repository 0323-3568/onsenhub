<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Ensure user is admin
if (!isAdmin()) {
    $_SESSION['error_message'] = "You are not authorized to view this page.";
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$pageTitle = "View Booking Details";
include '../includes/header.php';

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id <= 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Invalid booking ID.</div></div>";
    include '../includes/footer.php';
    exit();
}

// Fetch booking details along with user and room information
$stmt = $pdo->prepare("
    SELECT 
        res.*, 
        u.username AS user_username, 
        u.email AS user_email,
        u.first_name AS user_first_name,
        u.last_name AS user_last_name,
        r.name AS room_name,
        r.price AS room_price
    FROM reservations res
    JOIN users u ON res.user_id = u.id
    JOIN rooms r ON res.room_id = r.id
    WHERE res.id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Booking not found.</div></div>";
    include '../includes/footer.php';
    exit();
}
?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4">Booking Details #<?php echo htmlspecialchars($booking['id']); ?></h2>

    <div class="card">
        <div class="card-header">
            Booking for Room: <strong><?php echo htmlspecialchars($booking['room_name']); ?></strong>
        </div>
        <div class="card-body">
            <h5 class="card-title">User Information</h5>
            <p><strong>User:</strong> <?php echo htmlspecialchars($booking['user_first_name'] . ' ' . $booking['user_last_name']); ?> (<?php echo htmlspecialchars($booking['user_username']); ?>)</p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['user_email']); ?></p>
            <hr>
            <h5 class="card-title mt-3">Reservation Information</h5>
            <p><strong>Check-in Date:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($booking['check_in']))); ?></p>
            <p><strong>Check-out Date:</strong> <?php echo htmlspecialchars(date('F j, Y', strtotime($booking['check_out']))); ?></p>
            <p><strong>Total Price:</strong> <?php echo formatCurrency($booking['total_price']); ?></p>
            <p><strong>Status:</strong>
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
                    if ($booking['status'] == 'confirmed') $statusClass = 'success';
                    elseif (in_array($booking['status'], ['cancelled', 'expired'])) $statusClass = 'danger';
                    echo '<span class="badge bg-' . $statusClass . '">' . htmlspecialchars(ucfirst($booking['status'])) . '</span>';
                }
                ?>
            </p>
            <p><strong>Booked At:</strong> <?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($booking['created_at']))); ?></p>
        </div>
        <div class="card-footer">
            <a href="<?php echo SITE_URL; ?>/admin/reservations.php" class="btn btn-secondary">Back to All Bookings</a>
            <!-- Add edit/cancel booking buttons here if needed -->
        </div>
    </div>
</div>

<?php
include '../includes/footer.php';
?>