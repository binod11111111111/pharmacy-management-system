<?php
// order_details.php
session_start();
require_once 'php/db_connect.php';

if (!isCustomerLoggedIn()) {
    header("Location: php/auth/login.php");
    exit();
}

$order_id = $_GET['id'] ?? null;
if (!$order_id) {
    header("Location: customer_dashboard.php");
    exit();
}

// Get order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
$stmt->execute([$order_id, $_SESSION['customer_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: customer_dashboard.php");
    exit();
}

// Get order items
$stmt = $pdo->prepare("SELECT oi.*, m.name as medicine_name, m.manufacturer 
                       FROM order_items oi 
                       JOIN medicines m ON oi.medicine_id = m.id 
                       WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order['id'] ?> - MediCare Pharmacy</title>
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
                    <li><a href="customer_dashboard.php">My Account</a></li>
                    <li><a href="cart.php" class="cart-icon">🛒 Cart <span class="cart-count">0</span></a></li>
                    <li><a href="php/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="color: #2c3e50;">Order #<?= $order['id'] ?></h1>
            <a href="customer_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Order Items -->
            <div>
                <div class="card">
                    <h2>Order Items</h2>
                    <?php foreach ($order_items as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee;">
                        <div>
                            <h4><?= htmlspecialchars($item['medicine_name']) ?></h4>
                            <?php if ($item['manufacturer']): ?>
                                <p style="color: #7f8c8d; margin: 5px 0;">by <?= htmlspecialchars($item['manufacturer']) ?></p>
                            <?php endif; ?>
                            <p>Quantity: <?= $item['quantity'] ?></p>
                            <p>Price: Rs.<?= number_format($item['price'], 2) ?> each</p>
                        </div>
                        <div style="text-align: right;">
                            <strong style="font-size: 1.1rem;">Rs.<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="text-align: right; margin-top: 20px; padding-top: 20px; border-top: 2px solid #3498db;">
                        <h3 style="color: #27ae60;">Total: Rs.<?= number_format($order['total_amount'], 2) ?></h3>
                    </div>
                </div>
                
                <!-- Shipping Address -->
                <div class="card">
                    <h2>Shipping Address</h2>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px;">
                        <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                    </div>
                </div>
            </div>
            
            <!-- Order Status -->
            <div>
                <div class="card">
                    <h2>Order Status</h2>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <span class="badge" style="background: 
                            <?php 
                            switch($order['status']) {
                                case 'pending': echo '#f39c12'; break;
                                case 'processing': echo '#3498db'; break;
                                case 'shipped': echo '#9b59b6'; break;
                                case 'delivered': echo '#27ae60'; break;
                                case 'cancelled': echo '#e74c3c'; break;
                            }
                            ?>; color: white; padding: 10px 20px; border-radius: 25px; font-size: 16px; font-weight: bold;">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>
                    
                    <!-- Status Timeline -->
                    <div style="margin: 30px 0;">
                        <div class="status-timeline">
                            <div class="status-step <?= in_array($order['status'], ['pending', 'processing', 'shipped', 'delivered']) ? 'completed' : '' ?>">
                                <div class="status-icon">📝</div>
                                <div class="status-text">Order Placed</div>
                            </div>
                            <div class="status-step <?= in_array($order['status'], ['processing', 'shipped', 'delivered']) ? 'completed' : '' ?>">
                                <div class="status-icon">⚙️</div>
                                <div class="status-text">Processing</div>
                            </div>
                            <div class="status-step <?= in_array($order['status'], ['shipped', 'delivered']) ? 'completed' : '' ?>">
                                <div class="status-icon">🚚</div>
                                <div class="status-text">Shipped</div>
                            </div>
                            <div class="status-step <?= $order['status'] === 'delivered' ? 'completed' : '' ?>">
                                <div class="status-icon">✅</div>
                                <div class="status-text">Delivered</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Information -->
                <div class="card">
                    <h2>Order Information</h2>
                    <div style="line-height: 1.8;">
                        <p><strong>Order Date:</strong><br><?= date('F j, Y g:i A', strtotime($order['order_date'])) ?></p>
                        <p><strong>Last Updated:</strong><br><?= date('F j, Y g:i A', strtotime($order['updated_at'])) ?></p>
                        <p><strong>Payment Method:</strong><br>💳 Cash on Delivery</p>
                        <?php if (in_array($order['status'], ['shipped', 'delivered'])): ?>
                            <p><strong>Estimated Delivery:</strong><br>1-2 business days</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card">
                    <h2>Actions</h2>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php if ($order['status'] === 'pending'): ?>
                            <button class="btn btn-danger" onclick="cancelOrder(<?= $order['id'] ?>)">Cancel Order</button>
                        <?php endif; ?>
                        <a href="shop.php" class="btn btn-primary">Order Again</a>
                        <button class="btn btn-secondary" onclick="window.print()">Print Receipt</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .status-timeline {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .status-step {
            display: flex;
            align-items: center;
            gap: 15px;
            opacity: 0.4;
            transition: opacity 0.3s;
        }
        
        .status-step.completed {
            opacity: 1;
        }
        
        .status-icon {
            font-size: 20px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 50%;
            border: 2px solid #ddd;
        }
        
        .status-step.completed .status-icon {
            background: #27ae60;
            border-color: #27ae60;
            color: white;
        }
        
        .status-text {
            font-weight: 500;
        }
        
        @media print {
            .header, .btn, nav { display: none !important; }
            body { background: white !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; }
        }
    </style>

    <script src="js/script.js"></script>
    <script>
        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                // In a real application, this would send a request to cancel the order
                alert('Order cancellation request submitted. You will be contacted shortly.');
            }
        }
    </script>
</body>
</html>