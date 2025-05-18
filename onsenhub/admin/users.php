<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/auth/login.php");
    exit();
}

$pageTitle = "Manage Users";
$success = '';
$error = '';

// Handle user actions
if (isset($_GET['action'])) {
    $userId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($userId > 0) {
        try {
            switch ($_GET['action']) {
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
                    $stmt->execute([$userId, $_SESSION['user_id']]);
                    if ($stmt->rowCount() > 0) {
                        $success = "User deleted successfully";
                        logActivity('delete_user', 'Deleted user ID: ' . $userId);
                    } else {
                        $error = "Cannot delete yourself or user not found";
                    }
                    break;
                    
                case 'toggle_admin':
                    // Prevent modifying yourself
                    if ($userId == $_SESSION['user_id']) {
                        $error = "You cannot modify your own admin status";
                        break;
                    }
                    
                    $stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
                    $stmt->execute([$userId]);
                    $success = "User admin status updated";
                    logActivity('toggle_admin', 'Changed admin status for user ID: ' . $userId);
                    break;
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid user ID";
    }
    
    // Refresh to clear GET parameters
    header("Location: users.php?success=" . urlencode($success) . "&error=" . urlencode($error));
    exit();
}

// Get all users
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_fill(0, 4, $searchTerm);
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display messages
$success = isset($_GET['success']) ? $_GET['success'] : '';
$error = isset($_GET['error']) ? $_GET['error'] : '';

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Manage Users</h2>
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>
    </div>
    
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
    include '../includes/overlay.php';
} ?>
    
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if (!empty($search)): ?>
                        <a href="users.php" class="btn btn-outline-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <?php if (empty($users)): ?>
            <div class="alert alert-info">No users found</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Admin</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td>
                                <?php if ($user['is_admin']): ?>
                                <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <li>
                                            <a class="dropdown-item" href="?action=toggle_admin&id=<?php echo $user['id']; ?>">
                                                <?php echo $user['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="?action=delete&id=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')">
                                                Delete
                                            </a>
                                        </li>
                                        <?php else: ?>
                                        <li><span class="dropdown-item text-muted">Current user</span></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>