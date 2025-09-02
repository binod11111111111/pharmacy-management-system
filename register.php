<?php
// register.php
session_start();
require_once 'php/db_connect.php';

$error = '';
$success = '';

if ($_POST) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validation
   // Validation
if (empty($full_name) || empty($email) || empty($password)) {
    $error = 'Please fill in all required fields';
} elseif ($password !== $confirm_password) {
    $error = 'Passwords do not match';
} elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters long';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address';
} elseif (!empty($phone) && !preg_match('/^9\d{9}$/', $phone)) {
    // phone is optional, but if entered, must start with 9 and have 10 digits
    $error = 'Please enter a valid 10-digit phone number starting with 9';
} else {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        $error = 'Email address already exists';
    } else {
        // Create account
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO customers (full_name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $hashed_password, $phone, $address]);
            
            $success = 'Account created successfully! You can now login.';
        } catch (PDOException $e) {
            $error = 'Registration failed. Please try again.';
        }
    }
}

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MediCare Pharmacy</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <h1>🏥 MediCare Pharmacy</h1>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                    <li><a href="php/auth/login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div style="max-width: 500px; margin: 0 auto;">
            <div class="card">
                <h2 style="text-align: center; margin-bottom: 30px; color: #2c3e50;">Create Account</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="php/auth/login.php" class="btn btn-primary">Login Now</a>
                    </div>
                <?php else: ?>
                
                <form method="POST" id="registerForm">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                        <small style="color: #7f8c8d;">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
                </form>
                
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <p>Already have an account? <a href="php/auth/login.php" style="color: #3498db;">Login here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>