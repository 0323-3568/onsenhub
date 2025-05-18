<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header("Location: ../rooms.php");
    exit();
}

// Check if room exists
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$room) {
    header("Location: ../rooms.php");
    exit();
}

// Delete room
$stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
$stmt->execute([$id]);

logActivity('delete_room', 'Deleted room: ' . $room['name']);

header("Location: ../rooms.php");
exit();
?>
