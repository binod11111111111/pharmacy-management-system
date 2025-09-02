<?php
// cart.php
session_start();
require_once 'php/db_connect.php';

if (!isCustomerLoggedIn()) {
    header("Location: php/auth/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - MediCare Pharmacy</title>
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
        <h1 style="margin-bottom: 30px; color: #2c3e50;">Shopping Cart</h1>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            <!-- Cart Items -->
            <div>
                <div id="cart-items">
                    <!-- Items will be loaded by JavaScript -->
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
                </div>
            </div>
            
            <!-- Cart Summary -->
            <div id="cart-total">
                <!-- Total will be loaded by JavaScript -->
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>