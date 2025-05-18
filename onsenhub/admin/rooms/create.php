<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$pageTitle = "Create Room";
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $capacity = intval($_POST['capacity']);
    $price = floatval($_POST['price']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $image = '';

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
            // Insert room into database
            $stmt = $pdo->prepare("INSERT INTO rooms (name, description, capacity, price, is_available, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $capacity, $price, $is_available, $image]);

            logActivity('create_room', 'Created room: ' . $name);

            $success = "Room created successfully.";
            // Clear form fields
            $name = $description = '';
            $capacity = $price = 0;
            $is_available = 1;
            $image = '';
        }
    }
}

include '../../includes/header.php';
?>

<div class="container py-5">
    <h2>Create Room</h2>

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
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="capacity" class="form-label">Capacity</label>
            <input type="number" class="form-control" id="capacity" name="capacity" min="1" value="<?php echo htmlspecialchars($capacity ?? 1); ?>" required>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price (₱ per night)</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" min="0" value="<?php echo htmlspecialchars($price ?? 0); ?>" required>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_available" name="is_available" <?php echo (isset($is_available) && $is_available) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_available">Available</label>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Room Image</label>
            <input type="file" class="form-control" id="image" name="image" accept=".jpg,.jpeg,.png,.gif">
        </div>
        <button type="submit" class="btn btn-primary">Create Room</button>
        <a href="../rooms.php" class="btn btn-secondary ms-2">Back to Rooms</a>
    </form>
</div>

<?php include '../../includes/footer.php'; ?>
