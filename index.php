<?php
// index.php
session_start();
require_once 'php/db_connect.php';

// Get featured medicines
$stmt = $pdo->query("SELECT m.*, c.name as category_name FROM medicines m 
                     LEFT JOIN categories c ON m.category_id = c.id 
                     ORDER BY m.created_at DESC LIMIT 6");
$featured_medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MediCare  - Your Health Partner</title>
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
                    <?php if (isCustomerLoggedIn()): ?>
                        <li><a href="customer_dashboard.php">My Account</a></li>
                        <li><a href="cart.php" class="cart-icon">🛒 Cart <span class="cart-count">0</span></a></li>
                        <li><a href="php/auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="php/auth/login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                    <?php if (isAdminLoggedIn()): ?>
                        <li><a href="php/admin/dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <!-- Hero Section -->
        <div class="card">
            <div style="text-align: center; padding: 40px 0;">
                <h2 style="font-size: 2.5rem; margin-bottom: 20px; color: #2c3e50;">Welcome to MediCare Pharmacy</h2>
                <p style="font-size: 1.2rem; color: #7f8c8d; margin-bottom: 30px;">Your trusted partner for quality medicines and healthcare products</p>
                <a href="shop.php" class="btn btn-primary" style="font-size: 1.1rem; padding: 15px 30px;">Shop Now</a>
            </div>
        </div>

        <!-- Features Section -->
        <div class="stats-grid">
            <div class="stat-card success">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Online Service</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number">500+</div>
                <div class="stat-label">Medicines Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">Fast</div>
                <div class="stat-label">Home Delivery</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-number">100%</div>
                <div class="stat-label">Genuine Products</div>
            </div>
        </div>

        <!-- Featured Products -->
        <div class="card">
            <h2 style="margin-bottom: 30px; color: #2c3e50;">Featured Medicines</h2>
            <div class="products-grid">
                <?php foreach ($featured_medicines as $medicine): ?>
                <div class="product-card">
                    <h3><?= htmlspecialchars($medicine['name']) ?></h3>
                    <p><strong>Category:</strong> <?= htmlspecialchars($medicine['category_name'] ?? 'Uncategorized') ?></p>
                    <p><?= htmlspecialchars($medicine['description']) ?></p>
                    <div class="product-price">Rs.<?= number_format($medicine['price'], 2) ?></div>
                    <div class="product-stock">
                        <?php if ($medicine['stock_quantity'] > 0): ?>
                            <span style="color: #27ae60;">✓ In Stock (<?= $medicine['stock_quantity'] ?> available)</span>
                        <?php else: ?>
                            <span style="color: #e74c3c;">✗ Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($medicine['prescription_required']): ?>
                        <p style="color: #e74c3c; font-weight: bold;">⚠️ Prescription Required</p>
                    <?php endif; ?>
                    
                    <?php if (isCustomerLoggedIn() && $medicine['stock_quantity'] > 0): ?>
                        <button class="btn btn-primary" 
                                onclick="addToCart(<?= $medicine['id'] ?>, '<?= addslashes($medicine['name']) ?>', <?= $medicine['price'] ?>, <?= $medicine['stock_quantity'] ?>)">
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <a href="php/auth/login.php" class="btn btn-secondary">Login to Purchase</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- About Section -->
        <div class="card">
            <h2 style="margin-bottom: 20px; color: #2c3e50;">Why Choose MediCare?</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
                <div style="text-align: center;">
                    <h3 style="color: #3498db;">🎯 Quality Assured</h3>
                    <p>All medicines are sourced from certified manufacturers and undergo strict quality checks.</p>
                </div>
                <div style="text-align: center;">
                    <h3 style="color: #27ae60;">🚚 Fast Delivery</h3>
                    <p>Get your medicines delivered to your doorstep within 24-48 hours.</p>
                </div>
                <div style="text-align: center;">
                    <h3 style="color: #e74c3c;">💰 Best Prices</h3>
                    <p>Competitive pricing with regular discounts and offers on all products.</p>
                </div>
                <div style="text-align: center;">
                    <h3 style="color: #f39c12;">🔒 Secure Shopping</h3>
                    <p>Your personal and payment information is protected with advanced encryption.</p>
                </div>
            </div>
        </div>
    </div>

     <footer>
    <footer style="background: #2c3e50; color: white; text-align: center; padding: 30px 0; margin-top: 50px;">
        <div class="container">
            <p>&copy; 2025 MediCare . All rights reserved.</p>
            
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>