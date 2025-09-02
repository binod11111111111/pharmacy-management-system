<?php
// shop.php
session_start();
require_once 'php/db_connect.php';

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(m.name LIKE ? OR m.description LIKE ? OR m.manufacturer LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($category_filter) {
    $where_conditions[] = "m.category_id = ?";
    $params[] = $category_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM medicines m $where_clause";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_medicines = $count_stmt->fetch()['total'];
$total_pages = ceil($total_medicines / $per_page);

// Get medicines
$query = "SELECT m.*, c.name as category_name 
          FROM medicines m 
          LEFT JOIN categories c ON m.category_id = c.id 
          $where_clause 
          ORDER BY m.name LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper: Check if customer has an approved prescription
function hasVerifiedPrescription($pdo, $customer_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE customer_id = ? AND status = 'approved'");
    $stmt->execute([$customer_id]);
    return $stmt->fetchColumn() > 0;
}

$customer_has_verified_prescription = false;
if (isCustomerLoggedIn()) {
    $customer_has_verified_prescription = hasVerifiedPrescription($pdo, $_SESSION['customer_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop - MediCare Pharmacy</title>
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
                    <li><a href="upload_prescription.php">Upload Prescription</a></li>
                    <?php if (isCustomerLoggedIn()): ?>
                        <li><a href="customer_dashboard.php">My Account</a></li>
                        <li><a href="cart.php" class="cart-icon">🛒 Cart <span class="cart-count">0</span></a></li>
                        <li><a href="php/auth/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="php/auth/login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1 style="margin-bottom: 30px; color: #2c3e50;">Shop Medicines</h1>
        
        <!-- Search and Filter -->
        <div class="card">
            <div style="display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="search-input">Search Medicines</label>
                    <input type="text" id="search-input" class="form-control" 
                           placeholder="Search by name, description, or manufacturer..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="category-filter">Category</label>
                    <select id="category-filter" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button onclick="searchMedicines()" class="btn btn-primary">Search</button>
            </div>
        </div>

        <!-- Results Summary -->
        <div style="margin: 20px 0;">
            <p style="color: #7f8c8d;">
                Showing <?= count($medicines) ?> of <?= $total_medicines ?> medicines
                <?php if ($search): ?>
                    for "<?= htmlspecialchars($search) ?>"
                <?php endif; ?>
                <?php if ($category_filter): ?>
                    <?php 
                    $selected_category = array_filter($categories, fn($c) => $c['id'] == $category_filter);
                    if ($selected_category) {
                        echo 'in ' . htmlspecialchars(array_values($selected_category)[0]['name']);
                    }
                    ?>
                <?php endif; ?>
            </p>
        </div>

        <!-- Medicine Grid -->
        <?php if (empty($medicines)): ?>
            <div class="card">
                <div style="text-align: center; padding: 40px;">
                    <h2 style="color: #7f8c8d;">No medicines found</h2>
                    <p>Try adjusting your search criteria or browse all medicines.</p>
                    <a href="shop.php" class="btn btn-primary">View All Medicines</a>
                </div>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($medicines as $medicine): ?>
                <div class="product-card">
                    <h3><?= htmlspecialchars($medicine['name']) ?></h3>
                    <p><strong>Category:</strong> <?= htmlspecialchars($medicine['category_name'] ?? 'Uncategorized') ?></p>
                    <?php if ($medicine['manufacturer']): ?>
                        <p><strong>Manufacturer:</strong> <?= htmlspecialchars($medicine['manufacturer']) ?></p>
                    <?php endif; ?>
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
                        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 8px; border-radius: 4px; margin: 10px 0;">
                            <small style="color: #856404;">⚠️ Prescription Required</small>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($medicine['expiry_date']): ?>
                        <p style="font-size: 0.9rem; color: #7f8c8d;">
                            Expires: <?= date('M Y', strtotime($medicine['expiry_date'])) ?>
                        </p>
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px;">
                        <?php if (isCustomerLoggedIn()): ?>
                            <?php if ($medicine['prescription_required']): ?>
                                <?php if (!$customer_has_verified_prescription): ?>
                                    <button class="btn btn-secondary" style="width: 100%;" disabled>
                                        Prescription not verified
                                    </button>
                                    <div style="color: #e74c3c; font-size: 0.95rem; margin-top: 5px;">
                                        Please upload and verify your prescription to buy this medicine.
                                    </div>
                                <?php elseif ($medicine['stock_quantity'] > 0): ?>
                                    <button class="btn btn-primary" style="width: 100%;"
                                            onclick="addToCart(<?= $medicine['id'] ?>, '<?= addslashes($medicine['name']) ?>', <?= $medicine['price'] ?>, <?= $medicine['stock_quantity'] ?>)">
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary" style="width: 100%;" disabled>
                                        Out of Stock
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($medicine['stock_quantity'] > 0): ?>
                                    <button class="btn btn-primary" style="width: 100%;"
                                            onclick="addToCart(<?= $medicine['id'] ?>, '<?= addslashes($medicine['name']) ?>', <?= $medicine['price'] ?>, <?= $medicine['stock_quantity'] ?>)">
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary" style="width: 100%;" disabled>
                                        Out of Stock
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="php/auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                               class="btn btn-secondary" style="width: 100%; text-align: center;">
                                Login to Purchase
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div style="text-align: center; margin-top: 40px;">
                <div style="display: inline-flex; gap: 10px;">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                           class="btn btn-secondary">« Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="btn btn-primary"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="btn btn-secondary"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                           class="btn btn-secondary">Next »</a>
                    <?php endif; ?>
                </div>
                <p style="margin-top: 15px; color: #7f8c8d;">
                    Page <?= $page ?> of <?= $total_pages ?>
                </p>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="js/script.js"></script>
</body>
</html>