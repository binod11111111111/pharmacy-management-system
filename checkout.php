<?php
// checkout.php
session_start();
require_once 'php/db_connect.php';

if (!isCustomerLoggedIn()) {
    header("Location: php/auth/login.php");
    exit();
}

$error = '';
$success = '';
$cart_data = json_decode($_POST['cart_data'] ?? '[]', true);

if (empty($cart_data)) {
    header("Location: cart.php");
    exit();
}

// Get customer info
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$_SESSION['customer_id']]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// Process order
if ($_POST && isset($_POST['place_order'])) {
    $shipping_address = trim($_POST['shipping_address']);
    
    if (empty($shipping_address)) {
        $error = 'Please provide a shipping address';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Calculate total
            $total_amount = 0;
            $order_items = [];
            
            // Validate cart items and check stock
            foreach ($cart_data as $item) {
                $stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
                $stmt->execute([$item['id']]);
                $medicine = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$medicine) {
                    throw new Exception("Medicine not found: " . $item['name']);
                }
                
                if ($medicine['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for: " . $medicine['name']);
                }
                
                $item_total = $medicine['price'] * $item['quantity'];
                $total_amount += $item_total;
                
                $order_items[] = [
                    'medicine_id' => $medicine['id'],
                    'quantity' => $item['quantity'],
                    'price' => $medicine['price']
                ];
            }
            
            // Create order
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount, shipping_address) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['customer_id'], $total_amount, $shipping_address]);
            $order_id = $pdo->lastInsertId();
            
            // Add order items and update stock
            foreach ($order_items as $item) {
                // Insert order item
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, medicine_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $item['medicine_id'], $item['quantity'], $item['price']]);
                
                // Update stock
                $stmt = $pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['medicine_id']]);
            }
            
            $pdo->commit();
            
            $success = "Order placed successfully! Order ID: #$order_id";
            
            // Clear the cart data for success display
            $cart_data = [];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Calculate totals for display
$subtotal = 0;
foreach ($cart_data as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal > 50 ? 0 : 5.99;
$total = $subtotal + $shipping;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - MediCare Pharmacy</title>
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
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="php/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1 style="margin-bottom: 30px; color: #2c3e50;">Checkout</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <div class="card">
                <div style="text-align: center; padding: 30px;">
                    <h2>Thank you for your order!</h2>
                    <p>Your order has been placed successfully and will be processed soon.</p>
                    <div style="margin-top: 20px;">
                        <a href="customer_dashboard.php" class="btn btn-primary">View My Orders</a>
                        <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
                    </div>
                </div>
            </div>
        <?php elseif (!empty($cart_data)): ?>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Order Details -->
            <div>
                <div class="card">
                    <h2>Order Items</h2>
                    <?php foreach ($cart_data as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee;">
                        <div>
                            <h4><?= htmlspecialchars($item['name']) ?></h4>
                            <p>Quantity: <?= $item['quantity'] ?></p>
                            <p>Price: Rs.<?= number_format($item['price'], 2) ?> each</p>
                        </div>
                        <div>
                            <strong>Rs.<?= number_format($item['price'] * $item['quantity'], 2) ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="card">
                    <h2>Shipping Information</h2>
                    <form method="POST" id="checkoutForm">
                        <input type="hidden" name="cart_data" value="<?= htmlspecialchars(json_encode($cart_data)) ?>">
                        
                        <div class="form-group">
                            <label for="customer_info">Customer</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($customer['full_name']) ?> (<?= htmlspecialchars($customer['email']) ?>)" 
                                   readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="shipping_address">Shipping Address *</label>
                            <textarea id="shipping_address" name="shipping_address" class="form-control" 
                                      rows="4" required placeholder="Enter your complete shipping address..."><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Method</label>
                            <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                                <p>💳 Cash on Delivery</p>
                                <small style="color: #7f8c8d;">Pay when your order is delivered to your address.</small>
                            </div>
                        </div>
                        
                        <button type="submit" name="place_order" class="btn btn-success" style="width: 100%; padding: 15px;">
                            Place Order
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div>
                <div class="card">
                    <h2>Order Summary</h2>
                    
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal:</span>
                            <span>Rs.<?= number_format($subtotal, 2) ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Shipping:</span>
                            <span>
                                <?php if ($shipping == 0): ?>
                                    <span style="color: #27ae60;">FREE</span>
                                <?php else: ?>
                                    Rs.<?= number_format($shipping, 2) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($subtotal < 50): ?>
                        <div style="background: #fff3cd; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                            <small style="color: #856404;">
                                💡 Add Rs.<?= number_format(50 - $subtotal, 2) ?> more for free shipping!
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold;">
                            <span>Total:</span>
                            <span style="color: #27ae60;">Rs.<?= number_format($total, 2) ?></span>
                        </div>
                    </div>
                    
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin-top: 20px;">
                        <h4 style="color: #27ae60; margin-bottom: 10px;">🚚 Delivery Information</h4>
                        <p style="margin-bottom: 5px; color: #2d5a2d;"><strong>Estimated Delivery:</strong> 1-2 business days</p>
                        <p style="margin-bottom: 5px; color: #2d5a2d;"><strong>Delivery Hours:</strong> 9 AM - 6 PM</p>
                        <p style="color: #2d5a2d;"><strong>Payment:</strong> Cash on Delivery</p>
                    </div>
                    
                    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 15px;">
                        <h4 style="color: #856404; margin-bottom: 10px;">⚠️ Important Notes</h4>
                        <ul style="margin: 0; padding-left: 20px; color: #856404;">
                            <li>Please have the exact amount ready for cash payment</li>
                            <li>Someone must be present at the delivery address</li>
                            <li>Prescription medicines require valid prescription</li>
                            <li>Orders can be cancelled within 30 minutes of placing</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <?php else: ?>
            <div class="card">
                <div style="text-align: center; padding: 40px;">
                    <h2>Your cart is empty</h2>
                    <p>Add some medicines to your cart before checkout.</p>
                    <a href="shop.php" class="btn btn-primary">Shop Now</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Clear cart after successful order
        <?php if ($success): ?>
        localStorage.removeItem('cart');
        updateCartDisplay();
        <?php endif; ?>
    </script>
</body>
</html>