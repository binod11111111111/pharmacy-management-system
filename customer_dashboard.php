<?php
// customer_dashboard.php
session_start();
require_once 'php/db_connect.php';

if (!isCustomerLoggedIn()) {
    header("Location: php/auth/login.php");
    exit();
}

// Get customer info
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$_SESSION['customer_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Get customer orders
$stmt = $pdo->prepare("SELECT o.*, COUNT(oi.id) as item_count 
                       FROM orders o 
                       LEFT JOIN order_items oi ON o.id = oi.order_id 
                       WHERE o.customer_id = ? 
                       GROUP BY o.id 
                       ORDER BY o.order_date DESC");
$stmt->execute([$_SESSION['customer_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle profile update
$message = '';
if ($_POST && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    if (empty($full_name)) {
        $message = 'Full name is required';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE customers SET full_name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $phone, $address, $_SESSION['customer_id']]);
            
            $_SESSION['customer_name'] = $full_name;
            $customer['full_name'] = $full_name;
            $customer['phone'] = $phone;
            $customer['address'] = $address;
            
            $message = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $message = 'Error updating profile';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - MediCare </title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="nav-container">
            <div class="logo">
                <h1>🏥 MediCare </h1>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="shop.php">Shop</a></li>
                
                        <li><a href="upload_prescription.php">Upload Prescription</a></li>
                        <li><a href="customer_dashboard.php">My Account</a></li>
                        <li><a href="cart.php" class="cart-icon">🛒 Cart <span class="cart-count">0</span></a></li>
                        <li><a href="php/auth/logout.php">Logout</a></li>
            
                        
                
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1 style="margin-bottom: 30px; color: #2c3e50;">Welcome, <?= htmlspecialchars($customer['full_name']) ?>!
        </h1>

        <?php if ($message): ?>
        <div class="alert alert-<?= strpos($message, 'Error') !== false ? 'danger' : 'success' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <!-- Profile Information -->
            <div class="card">
                <h2>My Profile</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" class="form-control"
                               value="<?= htmlspecialchars($customer['email']) ?>" readonly>
                        <small style="color: #7f8c8d;">Email cannot be changed</small>
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control"
                               value="<?= htmlspecialchars($customer['full_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control"
                               value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>

                <hr style="margin: 30px 0;">

                <div>
                    <h3>Account Information</h3>
                    <p><strong>Member Since:</strong> <?= date('F j, Y', strtotime($customer['created_at'])) ?></p>
                    <p><strong>Total Orders:</strong> <?= count($orders) ?></p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div>
                <div class="card">
                    <h2>Quick Actions</h2>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <a href="shop.php" class="btn btn-primary">Browse Medicines</a>
                        <a href="cart.php" class="btn btn-success">View Cart</a>
                        <a href="#orders" class="btn btn-warning" onclick="document.getElementById('orders').scrollIntoView()">View Orders</a>
                        <a href="php/auth/logout.php" class="btn btn-secondary">Logout</a>
                    </div>
                </div>

                <!-- Order Statistics -->
                <div class="stats-grid" style="margin-top: 20px;">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($orders) ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-number">
                            <?= count(array_filter($orders, fn($o) => $o['status'] === 'delivered')) ?>
                        </div>
                        <div class="stat-label">Delivered</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order History -->
        <div class="card" id="orders" style="margin-top: 40px;">
            <h2>Order History</h2>
            <?php if (empty($orders)): ?>
                <div style="text-align: center; padding: 40px;">
                    <h3 style="color: #7f8c8d;">No orders yet</h3>
                    <p>Start shopping to see your orders here.</p>
                    <a href="shop.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                            <td><?= $order['item_count'] ?> item(s)</td>
                            <td>Rs.<?= number_format($order['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge" style="background:
                                    <?php
                                    switch($order['status']) {
                                        case 'pending': echo '#f39c12'; break;
                                        case 'processing': echo '#3498db'; break;
                                        case 'shipped': echo '#9b59b6'; break;
                                        case 'delivered': echo '#27ae60'; break;
                                        case 'cancelled': echo '#e74c3c'; break;
                                    }
                                    ?>; color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px;">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="order_details.php?id=<?= $order['id'] ?>" class="btn btn-primary"
                                   style="font-size: 12px; padding: 5px 10px;">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>