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
                        <a href="rooms/delete.php?id=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-danger delete-room-btn" data-room-id="<?php echo $room['id']; ?>">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Confirmation Overlay Modal -->
<div id="deleteConfirmationOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1050; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:8px; max-width:400px; width:90%; box-shadow:0 2px 10px rgba(0,0,0,0.3); text-align:center;">
        <p>Are you sure you want to delete this room?</p>
        <div style="margin-top:20px;">
            <button id="confirmDeleteBtn" class="btn btn-danger me-2">Delete</button>
            <button id="cancelDeleteBtn" class="btn btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-room-btn');
    const overlay = document.getElementById('deleteConfirmationOverlay');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    const cancelBtn = document.getElementById('cancelDeleteBtn');
    let deleteUrl = '';

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            deleteUrl = this.href;
            overlay.style.display = 'flex';
        });
    });

    confirmBtn.addEventListener('click', function() {
        window.location.href = deleteUrl;
    });

    cancelBtn.addEventListener('click', function() {
        overlay.style.display = 'none';
        deleteUrl = '';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
