<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

// Fetch existing data
if (!isset($_GET['id'])) {
    echo "No Material ID specified!";
    exit;
}

$material_id = intval($_GET['id']);
$sql = "SELECT * FROM materials WHERE material_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $material_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Material not found!";
    exit;
}
$row = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Material - Inventory Management System</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<header class="top-bar">
    <div class="company-name">Smile & Sunshine</div>
    <div class="user-container">
        <div class="user-info">
            <span class="username" id="userDisplay">Welcome, User!</span>
        </div>
        <img src="PJ-Staff - FVer/rdimage/Arisu.jpg" alt="User Profile" class="top-right-icon">
    </div>
</header>
<nav class="sidebar" id="sidebar">
    <ul class="nav-list">
        <li><a href="home.html" class="nav-link">Dashboard</a></li>
        <li><a href="insertItem.html" class="nav-link">Insert Item</a></li>
        <li><a href="insertMaterial.html" class="nav-link">Insert Material</a></li>
        <li><a href="listMaterials.php" class="nav-link">Material List</a></li>
        <li><a href="updateOrder.html" class="nav-link">Update Order</a></li>
        <li><a href="generateReport.html" class="nav-link">Generate Report</a></li>
        <li><a href="deleteItem.html" class="nav-link">Delete Item</a></li>
    </ul>
    <div class="logout-container">
        <a href="#" class="btn-logout" id="logoutBtn">
            <span class="logout-icon"></span>
            <span>Logout</span>
        </a>
    </div>
</nav>
<main>
    <div class="container">
        <h1>Edit Material</h1>
        <form id="editMaterialForm" autocomplete="off">
            <input type="hidden" name="material_id" value="<?= htmlspecialchars($row['material_id']) ?>">
            <div class="form-group">
                <label>Material ID:</label>
                <input type="text" value="<?= htmlspecialchars($row['material_id']) ?>" disabled>
            </div>
            <div class="form-group">
                <label for="materialName">Material Name:</label>
                <input type="text" id="materialName" name="materialName" value="<?= htmlspecialchars($row['material_name']) ?>" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="physicalQuantity">Physical Quantity:</label>
                    <input type="number" id="physicalQuantity" name="physicalQuantity" min="0" value="<?= htmlspecialchars($row['physical_quantity']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="reservedQuantity">Reserved Quantity:</label>
                    <input type="number" id="reservedQuantity" name="reservedQuantity" min="0" value="<?= htmlspecialchars($row['reserved_quantity']) ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="unit">Unit:</label>
                    <select id="unit" name="unit" required>
                        <option value="">Select Unit</option>
                        <option value="kg" <?= $row['unit'] === 'kg' ? 'selected' : '' ?>>Kilograms</option>
                        <option value="g" <?= $row['unit'] === 'g' ? 'selected' : '' ?>>Grams</option>
                        <option value="m" <?= $row['unit'] === 'm' ? 'selected' : '' ?>>Meters</option>
                        <option value="cm" <?= $row['unit'] === 'cm' ? 'selected' : '' ?>>Centimeters</option>
                        <option value="l" <?= $row['unit'] === 'l' ? 'selected' : '' ?>>Liters</option>
                        <option value="ml" <?= $row['unit'] === 'ml' ? 'selected' : '' ?>>Milliliters</option>
                        <option value="pcs" <?= $row['unit'] === 'pcs' ? 'selected' : '' ?>>Pieces</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="reOrderLevel">Re-order Level:</label>
                    <input type="number" id="reOrderLevel" name="reOrderLevel" min="0" value="<?= htmlspecialchars($row['re_order_level']) ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label for="materialDescription">Description (Optional):</label>
                <textarea id="materialDescription" name="materialDescription" rows="3"><?= htmlspecialchars($row['material_description']) ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-primary" id="submitBtn">Save Changes</button>
                <a href="listMaterials.php" class="btn-secondary" role="button">Cancel</a>
            </div>
        </form>
        <div id="toastMsg" style="
                display: none;
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 9999;
                background: #d4edda;
                color: #155724;
                padding: 16px 28px;
                border-radius: 8px;
                box-shadow: 0 6px 24px rgba(0,0,0,0.13);
                font-size: 1.1rem;
                min-width: 220px;
                text-align: center;
                transition: opacity .3s;
            "></div>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('isLoggedIn') !== 'true') {
            window.location.href = 'login.html';
            return;
        }
        const username = localStorage.getItem('username');
        if (username) {
            document.getElementById('userDisplay').textContent = `Welcome, ${username}!`;
        }

        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('username');
            window.location.href = 'login.html';
        });

        document.getElementById('editMaterialForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;

            const formData = new FormData(this);

            fetch('updateMaterial.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    showToast(data.message, !data.success);
                    submitBtn.disabled = false;
                    if (data.success) {
                        setTimeout(() => { window.location.href = 'listMaterials.php'; }, 1200);
                    }
                })
                .catch(() => {
                    showToast("Update failed.", true);
                    submitBtn.disabled = false;
                });
        });
    });

    function showToast(message, isError) {
        const toast = document.getElementById('toastMsg');
        toast.innerHTML = message;
        toast.style.background = isError ? '#f8d7da' : '#d4edda';
        toast.style.color = isError ? '#721c24' : '#155724';
        toast.style.display = 'block';
        toast.style.opacity = 1;
        setTimeout(() => {
            toast.style.opacity = 0;
            setTimeout(() => { toast.style.display = 'none'; }, 350);
        }, 2000);
    }
</script>
</body>
</html>