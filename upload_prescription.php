<?php
session_start();
require_once 'php/db_connect.php';

if (!isCustomerLoggedIn()) {
    redirectToLogin('customer');
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['prescription'])) {
    $doctor_name = $_POST['doctor_name'] ?? '';
    $clinic_name = $_POST['clinic_name'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $target_dir = "uploads/prescriptions/";
    // Ensure directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $file_name = time() . '_' . basename($_FILES['prescription']['name']);
    $target_file = $target_dir . $file_name;
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $file_size = $_FILES['prescription']['size'];

    // Validate file type and size
    $allowed_types = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($file_type, $allowed_types)) {
        $message = "Invalid file type. Only PDF, JPG, PNG allowed.";
    } elseif ($file_size > 10 * 1024 * 1024) {
        $message = "File size exceeds 10MB limit.";
    } elseif (empty($doctor_name) || empty($clinic_name)) {
        $message = "Doctor Name and Clinic/Hospital Name are required.";
    } else {
        if (move_uploaded_file($_FILES['prescription']['tmp_name'], $target_file)) {
            $stmt = $pdo->prepare("INSERT INTO prescriptions (customer_id, file_path, doctor_name, clinic_name, notes, status) 
                                   VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['customer_id'], $file_name, $doctor_name, $clinic_name, $notes]);
            // Redirect to customer dashboard after successful upload
            header("Location: customer_dashboard.php");
            exit();
        } else {
            $message = "Failed to upload prescription.";
        }
    }
}

// Fetch customer's prescriptions
$stmt = $pdo->prepare("SELECT * FROM prescriptions WHERE customer_id = ? ORDER BY upload_date DESC");
$stmt->execute([$_SESSION['customer_id']]);
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions - MY Pharmacy</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Custom styles for upload area and textarea */
        .upload-area {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .upload-area:hover {
            background-color: #f9f9f9;
        }
        .upload-area input[type="file"] {
            display: none;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
    </style>
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
                    <li><a href="customer_dashboard.php">My Account</a></li>
                    <li><a href="cart.php" class="cart-icon">🛒 Cart <span class="cart-count">0</span></a></li>
                    <li><a href="php/auth/logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1 style="margin-bottom: 10px; color: #2c3e50;">My Prescriptions</h1>
        <p style="margin-bottom: 30px; color: #7f8c8d;">Upload and manage your medical prescriptions</p>

        <?php if ($message): ?>
            <p style="color: <?= strpos($message, 'success') !== false ? '#27ae60' : '#e74c3c'; ?>; margin-bottom: 20px;">
                <?= htmlspecialchars($message) ?>
            </p>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="card">
            <h2 style="margin-bottom: 20px; color: #2c3e50;">Upload New Prescription</h2>
            <form method="POST" enctype="multipart/form-data">
                <div style="margin-bottom: 20px;">
                    <label for="doctor_name">Doctor Name *</label>
                    <input type="text" id="doctor_name" name="doctor_name" placeholder="e.g., Dr. Ram Prasad Sharma" required>
                </div>
                <div style="margin-bottom: 20px;">
                    <label for="clinic_name">Clinic/Hospital Name *</label>
                    <input type="text" id="clinic_name" name="clinic_name" placeholder="e.g., Kathmandu Medical Center" required>
                </div>
                <div style="margin-bottom: 20px;">
                    <label for="prescription">Prescription Files * (PDF, JPG, PNG)</label>
                    <div class="upload-area" onclick="document.getElementById('prescription').click();">
                        Click to upload or drag and drop
                        <input type="file" id="prescription" name="prescription" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <p style="color: #7f8c8d; font-size: 12px;">PDF, JPG, PNG up to 10MB each</p>
                </div>
                <div style="margin-bottom: 20px;">
                    <label for="notes">Additional Notes (Optional)</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Any special instructions or notes..."></textarea>
                </div>
                <div style="display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-primary">Upload Prescription</button>
                    <button type="button" class="btn btn-info" onclick="document.getElementById('camera_input').click();">Take Photo</button>
                    <input type="file" id="camera_input" name="prescription_camera" accept="image/*" capture="environment" style="display: none;">
                </div>
            </form>
        </div>

        <!-- My Prescriptions -->
        <div class="card" style="margin-top: 30px;">
            <h2 style="margin-bottom: 20px; color: #2c3e50;">My Prescriptions</h2>
            <?php if (empty($prescriptions)): ?>
                <p style="color: #7f8c8d;">No prescriptions uploaded yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Doctor Name</th>
                            <th>Clinic/Hospital</th>
                            <th>Upload Date</th>
                            <th>Status</th>
                            <th>File</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prescriptions as $prescription): ?>
                        <tr>
                            <td>#<?= $prescription['id'] ?></td>
                            <td><?= htmlspecialchars($prescription['doctor_name']) ?></td>
                            <td><?= htmlspecialchars($prescription['clinic_name']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($prescription['upload_date'])) ?></td>
                            <td>
                                <span class="badge" style="background: 
                                    <?php 
                                    switch($prescription['status']) {
                                        case 'pending': echo '#f39c12'; break;
                                        case 'approved': echo '#27ae60'; break;
                                        case 'rejected': echo '#e74c3c'; break;
                                    }
                                    ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <?= ucfirst($prescription['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="/pharmacy_management/uploads/prescriptions/<?= htmlspecialchars($prescription['file_path']) ?>" target="_blank" class="btn btn-primary" style="font-size: 12px; padding: 5px 10px;">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Handle drag and drop
        const uploadArea = document.querySelector('.upload-area');
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.backgroundColor = '#f0f0f0';
        });
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.backgroundColor = 'transparent';
        });
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.backgroundColor = 'transparent';
            const fileInput = document.getElementById('prescription');
            fileInput.files = e.dataTransfer.files;
        });

        // Handle camera input (copy to main input)
        const cameraInput = document.getElementById('camera_input');
        cameraInput.addEventListener('change', () => {
            const fileInput = document.getElementById('prescription');
            fileInput.files = cameraInput.files;
        });
    </script>
</body>
</html>