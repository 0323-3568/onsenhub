<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

if (isset($_GET['confirm'])) {
    $reservationId = (int)$_GET['confirm'];
    // Check current status to determine if approving modification or normal confirmation
    $stmtCheck = $pdo->prepare("SELECT status FROM reservations WHERE id = ?");
    $stmtCheck->execute([$reservationId]);
    $currentStatus = $stmtCheck->fetchColumn();

    if ($currentStatus === 'modification requested') {
        // Approve modification
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'modification approved' WHERE id = ?");
    } else {
        // Normal confirmation
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ? AND status IN ('pending', 'cancellation requested')");
    }
    $stmt->execute([$reservationId]);
    if ($stmt->rowCount()) {
        if ($currentStatus === 'modification requested') {
            logActivity('Modification Approved', "Modification approved for reservation ID: $reservationId");
        } else {
            logActivity('Reservation Confirmed', "Reservation ID: $reservationId");
        }
    }
    header("Location: reservations.php");
    exit();
}

if (isset($_GET['cancel'])) {
    $reservationId = (int)$_GET['cancel'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND status IN ('pending', 'modification requested', 'cancellation requested')");
    $stmt->execute([$reservationId]);
    if ($stmt->rowCount()) {
        logActivity('Reservation Cancelled', "Reservation ID: $reservationId");
    }
    header("Location: reservations.php");
    exit();
}

if (isset($_GET['deny_modification'])) {
    $reservationId = (int)$_GET['deny_modification'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'modification denied' WHERE id = ? AND status = 'modification requested'");
    $stmt->execute([$reservationId]);
    if ($stmt->rowCount()) {
        logActivity('Modification Denied', "Modification denied for reservation ID: $reservationId");
    }
    header("Location: reservations.php");
    exit();
}

if (isset($_GET['deny_cancellation'])) {
    $reservationId = (int)$_GET['deny_cancellation'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ? AND status = 'cancellation requested'");
    $stmt->execute([$reservationId]);
    if ($stmt->rowCount()) {
        logActivity('Cancellation Denied', "Cancellation denied for reservation ID: $reservationId");
    }
    header("Location: reservations.php");
    exit();
}

$pageTitle = "Manage Reservations";

$currentStatus = $_GET['status'] ?? ''; // Use a more descriptive variable name

// Define all valid statuses for filtering
$validStatuses = ['pending', 'confirmed', 'cancelled', 'modification requested', 'modification approved', 'modification denied', 'cancellation requested', 'expired'];

// Sorting parameters
$allowedSortColumns = [
    'id' => 'r.id',
    'check_in' => 'r.check_in',
    'total_price' => 'r.total_price',
    'created_at' => 'r.created_at' // Default booking date
];
$sortBy = isset($_GET['sort_by']) && isset($allowedSortColumns[$_GET['sort_by']]) ? $_GET['sort_by'] : 'created_at';

$allowedSortOrders = ['ASC', 'DESC'];
$sortOrder = isset($_GET['sort_order']) && in_array(strtoupper($_GET['sort_order']), $allowedSortOrders) ? strtoupper($_GET['sort_order']) : 'DESC';

// Pagination setup
$limit = 10; // Number of reservations per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base query
$sql = "
    SELECT r.*, u.username, u.email, rm.name as room_name
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN rooms rm ON r.room_id = rm.id
";
$queryParams = [];

if (!empty($currentStatus) && in_array($currentStatus, $validStatuses)) {
    $sql .= " WHERE r.status = :status";
    $queryParams[':status'] = $currentStatus;
}

// Add search filter
$searchTerm = $_GET['search'] ?? '';
if (!empty($searchTerm)) {
    // Add WHERE if not already added by status filter
    if (strpos($sql, 'WHERE') === false) {
        $sql .= " WHERE 1=1"; // Start a dummy condition if no status filter
    }
    $sql .= " AND (u.username LIKE :search OR rm.name LIKE :search OR r.id LIKE :search)";
    $queryParams[':search'] = "%$searchTerm%";
}

// Get total count for pagination *before* adding ORDER BY and LIMIT
$countSql = str_replace("SELECT r.*, u.username, u.email, rm.name as room_name", "SELECT COUNT(*) as total", $sql);
$stmtTotal = $pdo->prepare($countSql);
$stmtTotal->execute($queryParams); // Execute with the same parameters for accurate count
$totalReservations = $stmtTotal->fetchColumn();
$totalPages = ceil($totalReservations / $limit);

// Add ORDER BY and LIMIT for the main query
$sql .= " ORDER BY " . $allowedSortColumns[$sortBy] . " " . $sortOrder;
$sql .= " LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Bind :status if set (from the $queryParams array)
if (isset($queryParams[':status'])) {
    $stmt->bindValue(':status', $queryParams[':status']);
}
// Bind :search if set (from the $queryParams array)
if (isset($queryParams[':search'])) {
    $stmt->bindValue(':search', $queryParams[':search']);
}

// Bind :limit and :offset directly as integers
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();

$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare query parameters for pagination links
$paginationParams = [
    'status' => $currentStatus,
    'search' => $searchTerm,
    'sort_by' => $sortBy,
    'sort_order' => $sortOrder
];
$paginationQueryString = http_build_query($paginationParams);

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
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php if ($currentStatus === 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="confirmed" <?php if ($currentStatus === 'confirmed') echo 'selected'; ?>>Confirmed</option>
                        <option value="cancelled" <?php if ($currentStatus === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                        <option value="cancellation requested" <?php if ($currentStatus === 'cancellation requested') echo 'selected'; ?>>Cancellation Requested</option>
                        <option value="modification requested" <?php if ($currentStatus === 'modification requested') echo 'selected'; ?>>Modification Requested</option>
                        <option value="modification approved" <?php if ($currentStatus === 'modification approved') echo 'selected'; ?>>Modification Approved</option>
                        <option value="modification denied" <?php if ($currentStatus === 'modification denied') echo 'selected'; ?>>Modification Denied</option>
                        <option value="expired" <?php if ($currentStatus === 'expired') echo 'selected'; ?>>Expired</option>
                     </select>
                    </select>
                </div>
                <div class="col-md-3">
                     <label for="search" class="form-label">Search</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="User, Room, or ID..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                 <div class="col-md-2">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select id="sort_by" name="sort_by" class="form-select">
                        <option value="created_at" <?php if ($sortBy === 'created_at') echo 'selected'; ?>>Booking Date</option>
                        <option value="id" <?php if ($sortBy === 'id') echo 'selected'; ?>>Booking ID</option>
                        <option value="check_in" <?php if ($sortBy === 'check_in') echo 'selected'; ?>>Check-in Date</option>
                        <option value="total_price" <?php if ($sortBy === 'total_price') echo 'selected'; ?>>Total Price</option>
                    </select>
                </div>
                <div class="col-md-2">
                     <label for="sort_order" class="form-label">Order</label>
                    <select id="sort_order" name="sort_order" class="form-select">
                        <option value="DESC" <?php if ($sortOrder === 'DESC') echo 'selected'; ?>>Descending</option>
                        <option value="ASC" <?php if ($sortOrder === 'ASC') echo 'selected'; ?>>Ascending</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
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
                                if ($reservation['status'] == 'modification approved') {
                                    echo 'success me-1">Confirmed</span><span class="badge bg-success ms-1">Modification Approved';
                                } elseif ($reservation['status'] == 'modification denied') {
                                    echo 'success me-1">Confirmed</span><span class="badge bg-danger ms-1">Modification Denied';
                                } elseif ($reservation['status'] == 'modification requested') {
                                    echo 'success me-1">Confirmed</span><span class="badge bg-warning ms-1">Modification Requested';
                                } else {
                                    // For single badges
                                    $statusClass = 'warning'; // Default for pending, cancellation requested
                                    if ($reservation['status'] == 'confirmed') {
                                        $statusClass = 'success';
                                    } elseif (in_array($reservation['status'], ['cancelled', 'expired'])) {
                                        $statusClass = 'danger';
                                    }
                                    echo $statusClass . '">' . ucfirst($reservation['status']);
                                }
                            ?></span>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/view_booking.php?id=<?php echo $reservation['id']; ?>">View</a></li>
                                        <?php if ($reservation['status'] == 'pending'): ?>
                                        <li><a class="dropdown-item" href="?confirm=<?php echo $reservation['id']; ?>">Confirm</a></li>
                                        <li><a class="dropdown-item" href="?cancel=<?php echo $reservation['id']; ?>">Cancel</a></li>
                                        <?php elseif ($reservation['status'] == 'modification requested'): ?>
                                        <li><a class="dropdown-item" href="?confirm=<?php echo $reservation['id']; ?>">Approve Modification</a></li>
                                        <li><a class="dropdown-item" href="?deny_modification=<?php echo $reservation['id']; ?>">Deny Modification</a></li>
                                        <?php elseif ($reservation['status'] == 'cancellation requested'): ?>
                                        <li><a class="dropdown-item" href="?cancel=<?php echo $reservation['id']; ?>">Approve Cancellation</a></li>
                                        <li><a class="dropdown-item" href="?deny_cancellation=<?php echo $reservation['id']; ?>">Deny Cancellation</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php if ($page == $i) echo 'active'; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo htmlspecialchars($paginationQueryString); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>