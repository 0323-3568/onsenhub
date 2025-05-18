<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$pageTitle = "Edit Booking";
$error = '';

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
    WHERE r.id = ? AND r.user_id = ? AND r.status IN ('pending', 'confirmed', 'modification requested', 'cancellation requested', 'modification approved')
");
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: list.php");
    exit();
}

// Check if modification is allowed based on time for 'confirmed' or 'modification approved' bookings
$canModifyBasedOnTime = true; // Default for pending
if (in_array($booking['status'], ['confirmed', 'modification approved', 'modification requested', 'modification denied'])) {
    if (!canModifyBookingByTime($booking['check_in'])) {
        $canModifyBasedOnTime = false;
    }
}

// If trying to access edit page for a booking that shouldn't be editable due to time constraint or status
$allowedToEditStatuses = ['pending', 'confirmed', 'modification approved', 'modification requested', 'modification denied'];
if (!in_array($booking['status'], $allowedToEditStatuses) || (!$canModifyBasedOnTime && in_array($booking['status'], ['confirmed', 'modification approved', 'modification requested', 'modification denied']))) {
    // If status is not editable OR (it's confirmed/mod_approved AND time rule fails)
    if (!$canModifyBasedOnTime && in_array($booking['status'], ['confirmed', 'modification approved', 'modification requested', 'modification denied'])) {
        $_SESSION['overlayMessage'] = "This booking cannot be modified as it is less than 24 hours before check-in, or the check-in time has passed.";
        $_SESSION['overlayType'] = "error";
    }
    header("Location: view.php?id=" . $bookingId);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $checkIn = $_POST['check_in'];
        $checkOut = $_POST['check_out'];
        $guests = intval($_POST['guests']);

        // Common validation
        if (empty($checkIn) || empty($checkOut)) {
            $error = "Please select check-in and check-out dates";
        } elseif ($checkIn >= $checkOut) {
            $error = "Check-out date must be after check-in date";
        } elseif ($guests < 1) {
            $error = "Number of guests must be at least 1";
        } elseif ($guests > $booking['room_capacity']) {
            $error = "Number of guests exceeds room capacity";
        } else {
            // Validation passed
            $date1 = new DateTime($checkIn);
            $date2 = new DateTime($checkOut);
            $nights = $date2->diff($date1)->days;
            $totalPrice = $booking['room_price'] * $nights;

            $newStatus = '';
            $logAction = '';
            $logDetails = '';
            $overlayMessageText = '';
            $redirectLocation = "view.php?id=" . $bookingId; // Default redirect

            if ($booking['status'] == 'pending') {
                $newStatus = 'confirmed';
                $logAction = 'update_booking';
                $logDetails = 'Updated pending booking ' . $bookingId . ' to confirmed. Details: ' . $checkIn . '-' . $checkOut . ', ' . $guests . ' guests.';
                $overlayMessageText = "Booking updated and confirmed successfully.";
            } elseif ($booking['status'] == 'confirmed') {
                // User clicked "Request Modification" for a confirmed booking.
                // Save new details and set status to 'modification requested'.
                $newStatus = 'modification requested';
                $logAction = 'modification_requested';
                $logDetails = 'Modification requested for booking ' . $bookingId . ' with new details: ' . $checkIn . '-' . $checkOut . ', ' . $guests . ' guests.';
                $overlayMessageText = "Modification request with new details submitted. Please wait for admin approval.";
            } elseif ($booking['status'] == 'modification approved') {
                // User clicked "Update Booking" for a modification_approved booking.
                // Save new details and set status to 'modification requested' for re-approval.
                $newStatus = 'modification requested';
                $logAction = 'modification_resubmitted';
                $logDetails = 'Re-submitted modification for booking ' . $bookingId . ' with new details: ' . $checkIn . '-' . $checkOut . ', ' . $guests . ' guests.';
                $overlayMessageText = "Booking changes submitted as a new modification request. Please wait for admin approval.";
            } elseif ($booking['status'] == 'modification requested') {
                // User is updating an existing modification request
                $newStatus = 'modification requested'; // Status remains the same
                $logAction = 'modification_updated';
                $logDetails = 'Updated existing modification request for booking ' . $bookingId . '. New details: ' . $checkIn . '-' . $checkOut . ', ' . $guests . ' guests.';
                $overlayMessageText = "Modification request updated. Please wait for admin approval.";
            } elseif ($booking['status'] == 'modification denied') {
                // User is editing a booking whose previous modification was denied.
                $newStatus = 'modification requested'; // Becomes a new request
                $logAction = 'modification_resubmitted_after_denial';
                $logDetails = 'Re-submitted modification after denial for booking ' . $bookingId . '. Details: ' . $checkIn . '-' . $checkOut . ', ' . $guests . ' guests.';
                $overlayMessageText = "New modification request submitted. Please wait for admin approval.";
            } else {
                $error = "Booking cannot be modified in its current status.";
            }

            if (!$error && !empty($newStatus)) {
                $stmt = $pdo->prepare("UPDATE reservations SET check_in = ?, check_out = ?, guests = ?, total_price = ?, status = ? WHERE id = ?");
                $stmt->execute([$checkIn, $checkOut, $guests, $totalPrice, $newStatus, $bookingId]);
                
                logActivity($logAction, $logDetails);
                
                $_SESSION['overlayMessage'] = $overlayMessageText;
                $_SESSION['overlayType'] = "success";
                
                header("Location: " . $redirectLocation);
                exit();
            }
        }
    } elseif (isset($_POST['cancel'])) {
        if ($booking['status'] == 'pending') {
            // Cancel reservation directly
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$bookingId]);
            
            logActivity('cancel_booking', 'Cancelled booking ' . $bookingId);
            
            $_SESSION['overlayMessage'] = "Booking cancelled successfully.";
            $_SESSION['overlayType'] = "success";
            
            header("Location: view.php?id=" . $bookingId);
            exit();
        } elseif ($booking['status'] == 'confirmed') {
            // User requests cancellation after confirmation
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancellation requested' WHERE id = ?");
            $stmt->execute([$bookingId]);
            
            logActivity('cancellation_requested', 'Cancellation requested for booking ' . $bookingId);
            
            $_SESSION['overlayMessage'] = "Cancellation request submitted. Please wait for admin approval.";
            $_SESSION['overlayType'] = "success";
            
            header("Location: edit.php?id=" . $bookingId);
            exit();
        } else {
            $error = "Booking cannot be cancelled in its current status.";
        }
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-black text-white">
                    <h3 class="mb-0">Edit Booking</h3>
                </div>
                <div class="card-body">
<?php
if ($error) {
    $overlayMessage = $error;
    $overlayType = "error";
}
?>

<?php if (isset($overlayMessage) && isset($overlayType)) {
    include '../includes/overlay.php';
} ?>
                    
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
                                        <p class="card-text"><strong class="text-primary">₱<?php echo number_format($booking['room_price'], 2); ?> per night</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="check_in" class="form-label">Check-in Date</label>
                                <input type="date" class="form-control" id="check_in" name="check_in" 
                                    value="<?php echo htmlspecialchars($booking['check_in']); ?>" 
                                    min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="check_out" class="form-label">Check-out Date</label>
                                <input type="date" class="form-control" id="check_out" name="check_out" 
                                    value="<?php echo htmlspecialchars($booking['check_out']); ?>" 
                                    min="<?php echo date('Y-m-d', strtotime($booking['check_in'] . ' +1 day')); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="guests" class="form-label">Number of Guests</label>
                            <input type="number" class="form-control" id="guests" name="guests" 
                                min="1" max="<?php echo $booking['room_capacity']; ?>" 
                                value="<?php echo $booking['guests']; ?>" required>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                            <button type="submit" name="cancel" class="btn btn-danger">Cancel Booking</button>
                            <?php endif; ?>
                            <div>
                                <a href="view.php?id=<?php echo $booking['id']; ?>" class="btn btn-outline-secondary me-2">Back</a>
                                <?php
                                // Re-check $canModifyBasedOnTime for button display, as it might have been calculated before POST
                                $displayEditButton = false;
                                if ($booking['status'] == 'pending') {
                                    $displayEditButton = true;
                                } elseif (in_array($booking['status'], ['confirmed', 'modification approved', 'modification requested', 'modification denied']) && canModifyBookingByTime($booking['check_in'])) {
                                    $displayEditButton = true;
                                }

                                if ($displayEditButton) {
                                    $buttonText = "Update Booking"; // Default for 'pending'
                                    if ($booking['status'] == 'confirmed') {
                                        $buttonText = "Submit Modification Request";
                                    } elseif ($booking['status'] == 'modification approved') {
                                        $buttonText = "Submit New Modification Request";
                                    } elseif ($booking['status'] == 'modification requested') {
                                        $buttonText = "Update Modification Request";
                                    } elseif ($booking['status'] == 'modification denied') {
                                        $buttonText = "Resubmit Modification Request";
                                    }
                                    echo '<button type="submit" name="update" class="btn btn-primary">' . $buttonText . '</button>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="mt-3">
                            <?php if ($booking['status'] == 'confirmed'): ?>
                            <p class="text-info">You can submit a modification request by clicking the "Request Modification" button above.</p>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Set minimum check-out date based on check-in date
document.getElementById('check_in').addEventListener('change', function() {
    const checkInDate = new Date(this.value);
    const checkOutField = document.getElementById('check_out');
    
    if (this.value) {
        checkInDate.setDate(checkInDate.getDate() + 1);
        const nextDay = checkInDate.toISOString().split('T')[0];
        checkOutField.min = nextDay;
        
        // If current check-out is before new min, reset it
        if (checkOutField.value && checkOutField.value < nextDay) {
            checkOutField.value = nextDay;
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>