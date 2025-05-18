<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$bodyClass = "auth-page-bg";
$pageTitle = "Login";
$error = '';

// Check for registration success or error messages in query parameters
$registerSuccess = isset($_GET['register']) && $_GET['register'] === 'success';
$registerError = isset($_GET['register']) && $_GET['register'] === 'error';
$registerErrorMessage = $registerError && isset($_GET['message']) ? htmlspecialchars(urldecode($_GET['message'])) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    $result = loginUser($username, $password);
    
    if ($result === true) {
        header("Location: " . SITE_URL);
        exit();
    } else {
        $error = $result;
    }
}

include '../includes/header.php';
?>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/overlay.css">

<?php
if ($error) {
    $overlayMessage = $error;
    $overlayType = "error";
}
?>

<?php
if ($registerSuccess) {
    $overlayMessage = "Registration successful! Please log in.";
    $overlayType = "success";
} elseif ($registerError) {
    $overlayMessage = $registerErrorMessage;
    $overlayType = "error";
}
?>

<?php if (isset($overlayMessage) && isset($overlayType)) : ?>
    <?php include '../includes/overlay.php'; ?>
<?php endif; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow auth-card">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Login to OnsenHub</h2>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username or email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="<?php echo SITE_URL; ?>/auth/register.php">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
