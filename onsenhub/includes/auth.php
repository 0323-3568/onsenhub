<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

// Register new user
function registerUser($username, $email, $password, $firstName, $lastName, $phone) {
    global $pdo;
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName) || empty($phone)) {
        return "All fields are required";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format";
    }
    
    if (!preg_match('/^\d+$/', $phone)) {
        return "Phone number must contain only digits";
    }
    
    // Check if username/email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        return "Username or email already exists";
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName, $phone]);
    
    logActivity('register', 'New user registered: ' . $username);
    
    return true;
}

// Login user
function loginUser($username, $password) {
    global $pdo;
    
    // Find user by username or email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        logActivity('login', 'User logged in');
        
        return true;
    }
    
    return "Invalid username or password";
}

// Logout user
function logoutUser() {
    logActivity('logout', 'User logged out');
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}
?>