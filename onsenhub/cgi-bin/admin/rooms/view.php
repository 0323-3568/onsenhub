<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$pageTitle = "View Room";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: ../rooms.php");
    exit();
}

// Fetch room data
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header("Location: ../rooms.php");
    exit();
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <h2>View Room</h2>

    <div class="card mb-4" style="max-width: 600px;">
        <?php if ($room['image']): ?>
        <img src="<?php echo SITE_URL; ?>/assets/img/<?php echo $room['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($room['name']); ?>" style="object-fit: cover; height: 300px;">
        <?php endif; ?>
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($room['name']); ?></h5>
            <p class="card-text"><?php echo nl2br(htmlspecialchars($room['description'])); ?></p>
            <p class="card-text"><strong>Capacity:</strong> <?php echo $room['capacity']; ?> guests</p>
            <p class="card-text"><strong>Price:</strong> ₱<?php echo number_format($room['price'], 2); ?> per night</p>
            <p class="card-text"><strong>Available:</strong> <?php echo $room['is_available'] ? 'Yes' : 'No'; ?></p>
        </div>
    </div>

    <a href="../rooms.php" class="btn btn-secondary">Back to Rooms</a>
</div>

<?php include '../../includes/footer.php'; ?>
