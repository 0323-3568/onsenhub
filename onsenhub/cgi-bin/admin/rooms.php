<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$pageTitle = "Manage Rooms";

// Fetch all rooms
$stmt = $pdo->query("SELECT * FROM rooms ORDER BY id DESC");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Rooms</h2>
        <a href="rooms/create.php" class="btn btn-primary">Add New Room</a>
    </div>

    <?php if (empty($rooms)): ?>
    <div class="alert alert-info">No rooms found.</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Image</th>
                    <th>Capacity</th>
                    <th>Price</th>
                    <th>Available</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                <tr>
                    <td><?php echo $room['id']; ?></td>
                    <td><?php echo htmlspecialchars($room['name']); ?></td>
                    <td>
                        <img src="<?php echo SITE_URL; ?>/assets/img/<?php echo $room['image'] ?? 'default.jpg'; ?>" alt="<?php echo htmlspecialchars($room['name']); ?>" width="80" height="60" style="object-fit: cover;">
                    </td>
                    <td><?php echo $room['capacity']; ?></td>
                    <td>₱<?php echo number_format($room['price'], 2); ?></td>
                    <td><?php echo $room['is_available'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <a href="rooms/view.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-primary me-1">View</a>
                        <a href="rooms/edit.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-secondary me-1">Edit</a>
                        <a href="rooms/delete.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this room?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
