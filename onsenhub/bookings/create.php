<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

if (isAdmin()) {
    header("Location: " . SITE_URL . "/admin/reservations.php");
    exit();
}

$pageTitle = "Create Booking";
$error = '';

// Get room details if room_id is provided
$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$room = null;

if ($roomId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ? AND is_available = TRUE");
    $stmt->execute([$roomId]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$room) {
        // If room_id was given but room not found or not available
        $_SESSION['overlayMessage'] = "The selected room (ID: {$roomId}) is not available or does not exist.";
        $_SESSION['overlayType'] = "error";
        header("Location: " . SITE_URL . "/rooms.php"); // Redirect to room selection
        exit();
    } else {
        // Fetch all reservations that should block dates
        $stmtBlocked = $pdo->prepare("SELECT check_in, check_out FROM reservations WHERE room_id = ? AND status IN ('pending', 'confirmed', 'modification approved', 'modification requested', 'cancellation requested')");
        $stmtBlocked->execute([$roomId]);
        $allBlockedReservations = $stmtBlocked->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = intval($_POST['room_id']);
    $checkIn = $_POST['check_in'];
    $checkOut = $_POST['check_out'];
    $guests = intval($_POST['guests']);
    
    // Validate inputs
    if (empty($roomId)) {
        $error = "Please select a room";
    } elseif (empty($checkIn) || empty($checkOut)) {
        $error = "Please select check-in and check-out dates";
    } elseif ($checkIn >= $checkOut) {
        $error = "Check-out date must be after check-in date";
    } elseif ($guests < 1) {
        $error = "Number of guests must be at least 1";
    } else {
        // Get room details
        $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->execute([$roomId]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$room) {
            $error = "Selected room not found";
        } elseif ($guests > $room['capacity']) {
            $error = "Number of guests exceeds room capacity";
        } else {
            // SERVER-SIDE DATE CONFLICT CHECK for strictly blocked dates
            $conflictStmt = $pdo->prepare("
                SELECT COUNT(*) FROM reservations
                WHERE room_id = :room_id
                AND status IN ('pending', 'confirmed', 'modification approved', 'modification requested', 'cancellation requested')
                AND (
                    (:check_in < check_out AND :check_out > check_in) -- Selected range overlaps an existing one
                )
            ");
            $conflictStmt->execute([
                ':room_id' => $roomId,
                ':check_in' => $checkIn,
                ':check_out' => $checkOut
            ]);
            $conflictCount = $conflictStmt->fetchColumn();

            if ($conflictCount > 0) {
                $error = "The selected dates are no longer available. Please choose different dates.";
            } else {
                // Calculate total price
                $date1 = new DateTime($checkIn);
                $date2 = new DateTime($checkOut);
                $nights = $date2->diff($date1)->days;
                $totalPrice = $room['price'] * $nights;
                
                // Create reservation
                $stmt = $pdo->prepare("INSERT INTO reservations (user_id, room_id, check_in, check_out, guests, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $roomId,
                    $checkIn,
                    $checkOut,
                    $guests,
                    $totalPrice
                ]);
                
                logActivity('create_booking', 'Created booking for room ' . $roomId);
                
                $_SESSION['overlayMessage'] = "Reservation created successfully. Please wait for approval.";
                $_SESSION['overlayType'] = "success";
                header("Location: list.php");
                exit();
            }
        }
    }
}

include '../includes/header.php';
?>

<?php
if ($error) {
    $overlayMessage = $error;
    $overlayType = "error";
}
?>

<?php include '../includes/overlay.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Create Booking</h2>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <h4>Select Room</h4>
                            <?php if ($room): ?>
                            <div class="card mb-3" id="roomPreviewCard">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img id="roomPreviewImage" src="<?php echo SITE_URL; ?>/assets/img/<?php echo $room['image'] ?? 'default.jpg'; ?>" class="img-fluid rounded-start" alt="<?php echo htmlspecialchars($room['name']); ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title" id="roomPreviewName"><?php echo htmlspecialchars($room['name']); ?></h5>
                                            <p class="card-text" id="roomPreviewDescription"><?php echo htmlspecialchars($room['description']); ?></p>
                                            <p class="card-text"><small class="text-muted">Capacity: <span id="roomPreviewCapacity"><?php echo $room['capacity']; ?></span> guests</small></p>
                                            <p class="card-text"><strong class="text-primary" id="roomPreviewPrice">₱<?php echo number_format($room['price'], 2); ?> per night</strong></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                            <?php else: ?>
                            <select class="form-select" name="room_id" id="roomSelect" required>
                                <option value="">Select a room</option>
                                <?php
                                $roomsData = [];
                                $stmt = $pdo->query("SELECT * FROM rooms WHERE is_available = TRUE");
                                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)):
                                    $roomsData[$r['id']] = $r;
                                ?>
                                <option value="<?php echo $r['id']; ?>" <?php echo $roomId == $r['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($r['name']); ?> (₱<?php echo number_format($r['price'], 2); ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="card mt-3 d-none" id="dynamicRoomPreview">
                                <div class="row g-0">
                                    <div class="col-md-4">
                                        <img id="dynamicRoomImage" src="" class="img-fluid rounded-start" alt="Room Image">
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card-body">
                                            <h5 class="card-title" id="dynamicRoomName"></h5>
                                            <p class="card-text" id="dynamicRoomDescription"></p>
                                            <p class="card-text"><small class="text-muted">Capacity: <span id="dynamicRoomCapacity"></span> guests</small></p>
                                            <p class="card-text"><strong class="text-primary" id="dynamicRoomPrice"></strong></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="check_in" class="form-label">Check-in Date</label>
                                <input type="date" class="form-control" id="check_in" name="check_in" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="check_out" class="form-label">Check-out Date</label>
                                <input type="date" class="form-control" id="check_out" name="check_out" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="guests" class="form-label">Number of Guests</label>
                            <input type="number" class="form-control" id="guests" name="guests" min="1" max="<?php echo $room ? $room['capacity'] : 10; ?>" value="1" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Book Now</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
const allBlockedReservations = <?php echo json_encode($allBlockedReservations ?? []); ?>;

let disabledDates = []; // Changed from const to let

function updateGlobalDisabledDatesArray(reservationsData) {
    disabledDates.length = 0; // Clear the array
    reservationsData.forEach(res => { // expects array of {check_in: 'Y-m-d', check_out: 'Y-m-d'}
        let currentDate = new Date(res.check_in);
        const checkOutDate = new Date(res.check_out);
        while (currentDate < checkOutDate) {
            disabledDates.push(currentDate.toISOString().split('T')[0]);
            currentDate.setDate(currentDate.getDate() + 1);
        }
    });
}

updateGlobalDisabledDatesArray(allBlockedReservations); // Initial population if room_id is in URL

// Initialize flatpickr on check_in
const checkInPicker = flatpickr("#check_in", {
    minDate: "today",
    disable: disabledDates,
    onChange: function(selectedDates, dateStr, instance) {
        if (selectedDates.length > 0) {
            const minCheckOutDate = new Date(selectedDates[0]);
            minCheckOutDate.setDate(minCheckOutDate.getDate() + 1);
            checkOutPicker.set('minDate', minCheckOutDate);
            // Also update disabled dates for checkOutPicker if needed, though `disable` option is global for the instance
            if (checkOutPicker.selectedDates.length > 0 && checkOutPicker.selectedDates[0] < minCheckOutDate) {
                checkOutPicker.clear();
            }
        }
    }
});

// Initialize flatpickr on check_out
const checkOutPicker = flatpickr("#check_out", {
    minDate: new Date().fp_incr(1), // tomorrow
    disable: disabledDates
});

// Override the default disabled date style to red out disabled dates
const style = document.createElement('style');
style.innerHTML = `
.flatpickr-day.disabled, .flatpickr-day.disabled:hover { /* Changed from red to gray */
    background: #e0e0e0 !important; /* Light gray background */
    color: white !important;
    cursor: not-allowed !important;
}
`;
document.head.appendChild(style);

// Validate selected dates against reserved dates (fallback)
function validateDates() {
    const checkInVal = document.getElementById('check_in').value;
    const checkOutVal = document.getElementById('check_out').value;

    if (!checkInVal || !checkOutVal) {
        return true; // no dates selected yet
    }

    const checkInDate = new Date(checkInVal);
    const checkOutDate = new Date(checkOutVal);

    if (checkOutDate <= checkInDate) {
        alert('Check-out date must be after check-in date.');
        return false;
    }

    // Check each date in the selected range for conflicts
    for (let d = new Date(checkInDate); d < checkOutDate; d.setDate(d.getDate() + 1)) {
        const dateStr = d.toISOString().split('T')[0];
        if (disabledDates.includes(dateStr)) {
            alert('Selected dates include unavailable dates. Please choose different dates.');
            return false;
        }
    }
    return true;
}

document.querySelector('form').addEventListener('submit', function(e) {
    if (!validateDates()) {
        e.preventDefault();
    }
});

// Room data for dynamic preview
const roomsData = <?php echo json_encode($roomsData ?? []); ?>;

const roomSelect = document.getElementById('roomSelect');
const dynamicRoomPreview = document.getElementById('dynamicRoomPreview');
const dynamicRoomImage = document.getElementById('dynamicRoomImage');
const dynamicRoomName = document.getElementById('dynamicRoomName');
const dynamicRoomDescription = document.getElementById('dynamicRoomDescription');
const dynamicRoomCapacity = document.getElementById('dynamicRoomCapacity');
const dynamicRoomPrice = document.getElementById('dynamicRoomPrice');

if (roomSelect) { // Check if the dropdown select element exists
    roomSelect.addEventListener('change', function() {
        const selectedRoomId = this.value;
        if (selectedRoomId && roomsData[selectedRoomId]) {
            const room = roomsData[selectedRoomId];
            // Update room preview details
            dynamicRoomImage.src = "<?php echo SITE_URL; ?>/assets/img/" + (room.image || 'default.jpg');
            dynamicRoomImage.alt = room.name;
            dynamicRoomName.textContent = room.name;
            dynamicRoomDescription.textContent = room.description;
            dynamicRoomCapacity.textContent = room.capacity;
            dynamicRoomPrice.textContent = "₱" + parseFloat(room.price).toFixed(2) + " per night";
            dynamicRoomPreview.classList.remove('d-none');

            // Fetch and update calendar availability for the selected room
            fetch(`calendar.php?room_id=${selectedRoomId}`)
                .then(response => response.json())
                .then(data => {
                    disabledDates.length = 0; // Clear the global disabledDates array
                    if (data && data.disabledDates && Array.isArray(data.disabledDates)) {
                        disabledDates.push(...data.disabledDates); // Populate with new dates
                    }
                    // Update Flatpickr instances
                    checkInPicker.set('disable', disabledDates);
                    checkOutPicker.set('disable', disabledDates);
                })
                .catch(error => {
                    console.error('Error fetching room availability:', error);
                    disabledDates.length = 0; // Clear on error too
                    checkInPicker.set('disable', disabledDates);
                    checkOutPicker.set('disable', disabledDates);
                });
        } else {
            dynamicRoomPreview.classList.add('d-none');
            disabledDates.length = 0; // Clear if no room is selected
            checkInPicker.set('disable', disabledDates);
            checkOutPicker.set('disable', disabledDates);
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>