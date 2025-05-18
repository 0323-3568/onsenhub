<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$pageTitle = "Edit Room";
$error = '';
$success = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $capacity = intval($_POST['capacity']);
    $price = floatval($_POST['price']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $image = $room['image'];

    // Validate inputs
    if (empty($name)) {
        $error = "Room name is required.";
    } elseif ($capacity < 1) {
        $error = "Capacity must be at least 1.";
    } elseif ($price < 0) {
        $error = "Price must be a positive number.";
    } else {
        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../assets/img/';
            $filename = basename($_FILES['image']['name']);
            $targetFile = $uploadDir . $filename;
            $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($fileType, $allowedTypes)) {
                $error = "Only JPG, JPEG, PNG, and GIF files are allowed for the image.";
            } else {
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $image = $filename;
                } else {
                    $error = "Failed to upload image.";
                }
            }
        }

        if (!$error) {
            // Update room in database
            $stmt = $pdo->prepare("UPDATE rooms SET name = ?, description = ?, capacity = ?, price = ?, is_available = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $description, $capacity, $price, $is_available, $image, $id]);

            logActivity('edit_room', 'Edited room: ' . $name);

            $success = "Room updated successfully.";

            // Refresh room data
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmt->execute([$id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <h2>Edit Room</h2>

<?php
if ($success) {
    $overlayMessage = $success;
    $overlayType = "success";
} elseif ($error) {
    $overlayMessage = $error;
    $overlayType = "error";
}
?>

<?php if (isset($overlayMessage) && isset($overlayType)) {
    include '../../includes/overlay.php';
} ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Room Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($room['name']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($room['description']); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="capacity" class="form-label">Capacity</label>
            <input type="number" class="form-control" id="capacity" name="capacity" min="1" value="<?php echo htmlspecialchars($room['capacity']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price (₱ per night)</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" min="0" value="<?php echo htmlspecialchars($room['price']); ?>" required>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_available" name="is_available" <?php echo $room['is_available'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_available">Available</label>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Room Image</label>
            <?php if ($room['image']): ?>
            <div class="mb-2">
                <img src="<?php echo SITE_URL; ?>/assets/img/<?php echo $room['image']; ?>" alt="<?php echo htmlspecialchars($room['name']); ?>" width="120" style="object-fit: cover;">
            </div>
            <?php endif; ?>
            <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.gif">
            <small class="form-text text-muted">Upload a new image to replace the current one.</small>
        </div>
        <button type="submit" class="btn btn-primary">Update Room</button>
        <a href="../rooms.php" class="btn btn-secondary ms-2">Back to Rooms</a>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
