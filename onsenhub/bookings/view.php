<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$pageTitle = "View Booking";

// Get booking ID from URL
$bookingId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($bookingId <= 0) {
    header("Location: list.php");
    exit();
}

// Get booking details
$stmt = $pdo->prepare("
    SELECT r.*, rm.name as room_name, rm.description as room_description, rm.price as room_price, 
           rm.capacity as room_capacity, rm.image as room_image
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: list.php");
    exit();
}

// Calculate nights
$checkIn = new DateTime($booking['check_in']);
$checkOut = new DateTime($booking['check_out']);
$nights = $checkOut->diff($checkIn)->days;

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-black text-white">
                    <h3 class="mb-0">Booking Details</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Booking Information</h5>
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
                                    if ($booking['status'] == 'confirmed') {
                                        $statusClass = 'success';
                                    } elseif (in_array($booking['status'], ['cancelled', 'expired'])) {
                                        $statusClass = 'danger';
                                    }
                                    echo '<span class="badge bg-' . $statusClass . '">' . ucfirst($booking['status']) . '</span>';
                                }
                                ?>
                            </p>
                            <p><strong>Booking ID:</strong> <?php echo $booking['id']; ?></p>
                            <p><strong>Booking Date:</strong> <?php echo date('M j, Y', strtotime($booking['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Dates</h5>
                            <p><strong>Check-in:</strong> <?php echo date('M j, Y', strtotime($booking['check_in'])); ?></p>
                            <p><strong>Check-out:</strong> <?php echo date('M j, Y', strtotime($booking['check_out'])); ?></p>
                            <p><strong>Nights:</strong> <?php echo $nights; ?></p>
                            <p><strong>Guests:</strong> <?php echo $booking['guests']; ?></p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Room Details</h5>
                        <div class="card">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="<?php echo SITE_URL; ?>/assets/img/<?php echo $booking['room_image'] ?? 'default.jpg'; ?>" class="img-fluid rounded-start" alt="<?php echo htmlspecialchars($booking['room_name']); ?>">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($booking['room_name']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($booking['room_description']); ?></p>
                                        <p class="card-text"><small class="text-muted">Capacity: <?php echo $booking['room_capacity']; ?> guests</small></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <h5>Payment Summary</h5>
                        <table class="table">
                            <tr>
                                <th>Room Rate (<?php echo $nights; ?> nights)</th>
                                <td class="text-end">₱<?php echo number_format($booking['room_price'] * $nights, 2); ?></td>
                            </tr>
                            <tr>
                                <th>Total</th>
                                <td class="text-end fw-bold">₱<?php echo number_format($booking['total_price'], 2); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="list.php" class="btn btn-outline-secondary">Back to List</a>
                        <div>
                            <?php
                            $allowModificationAction = false;
                            if ($booking['status'] == 'pending') {
                                $allowModificationAction = true;
                            } elseif (in_array($booking['status'], ['confirmed', 'modification approved', 'modification requested', 'modification denied']) && canModifyBookingByTime($booking['check_in'])) {
                                $allowModificationAction = true;
                            }

                            if ($allowModificationAction) {
                                // Consistent "Edit Booking" button, leading to edit.php which has more specific submit buttons
                                echo '<a href="edit.php?id=' . $booking['id'] . '" class="btn btn-primary">Edit Booking</a>';
                            } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>