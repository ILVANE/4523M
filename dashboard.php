<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

// --- Dashboard Stats Queries ---

// 1. Total Items (materials)
$sql_total = "SELECT COUNT(*) AS total FROM materials";
$res_total = $conn->query($sql_total);
$total_items = $res_total ? (int)($res_total->fetch_assoc()['total'] ?? 0) : 0;

// 2. Pending Orders (order_status = 'pending')
$sql_pending = "SELECT COUNT(*) AS pending FROM orders WHERE order_status='pending'";
$res_pending = $conn->query($sql_pending);
$pending_orders = $res_pending ? (int)($res_pending->fetch_assoc()['pending'] ?? 0) : 0;

// 3. Low Stock (materials below re-order level)
$sql_low = "SELECT COUNT(*) AS low FROM materials WHERE physical_quantity < re_order_level";
$res_low = $conn->query($sql_low);
$low_stock = $res_low ? (int)($res_low->fetch_assoc()['low'] ?? 0) : 0;

// 4. Monthly Revenue (sum of 'total' for completed orders this month)
$firstDay = date('Y-m-01');
$lastDay = date('Y-m-t');
$sql_revenue = "SELECT SUM(total) AS revenue FROM orders WHERE order_status='completed' AND order_date BETWEEN '$firstDay' AND '$lastDay'";
$res_revenue = $conn->query($sql_revenue);
$monthly_revenue = $res_revenue ? (float)($res_revenue->fetch_assoc()['revenue'] ?? 0) : 0;

// --- Recent Activity Section (partially simulated) ---
$activity = [];

// New product/material added
$sql_last_mat = "SELECT material_name, created_at FROM materials ORDER BY created_at DESC LIMIT 1";
$res_last_mat = $conn->query($sql_last_mat);
if ($res_last_mat && $row = $res_last_mat->fetch_assoc()) {
    $activity[] = [
        'icon' => '➕',
        'title' => 'New product added',
        'meta' => htmlspecialchars($row['material_name']) . ' • ' . time_elapsed_string($row['created_at'])
    ];
}

// Monthly report generated (simulate)
$activity[] = [
    'icon' => '📊',
    'title' => 'Monthly report generated',
    'meta' => 'Today at ' . date('h:i A')
];

// Order updated
$sql_last_order = "SELECT order_id, updated_at FROM orders ORDER BY updated_at DESC LIMIT 1";
$res_last_order = $conn->query($sql_last_order);
if ($res_last_order && $row = $res_last_order->fetch_assoc()) {
    $activity[] = [
        'icon' => '✏️',
        'title' => 'Order #ORD-' . htmlspecialchars($row['order_id']) . ' updated',
        'meta' => time_elapsed_string($row['updated_at'])
    ];
}

// Helper: time ago string
function time_elapsed_string($datetime, $full = false) {
    if(!$datetime) return 'just now';
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard - Inventory Management System</title>
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
            <img src="\PJ-Staff - FVer/rdimage/Arisu.jpg" alt="User Profile" class="top-right-icon">
        </div>
    </header>

    <nav class="sidebar" id="sidebar">
        <ul class="nav-list">
            <li><a href="dashboard.php" class="nav-link" aria-current="page">Dashboard</a></li>
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
            <h1>Dashboard Overview</h1>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value" id="totalItems"><?= number_format($total_items) ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🧾</div>
                    <div class="stat-value" id="pendingOrders"><?= number_format($pending_orders) ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-value" id="lowStock"><?= number_format($low_stock) ?></div>
                    <div class="stat-label">Low Stock</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value" id="monthlyRevenue">$<?= number_format($monthly_revenue, 2) ?></div>
                    <div class="stat-label">Monthly Revenue</div>
                </div>
            </div>

            <!-- Recent Activity -->
            <section class="activity-section">
                <h2>Recent Activity</h2>
                <div class="activity-list" id="activityList">
                    <?php foreach ($activity as $act): ?>
                        <div class="activity-item">
                            <div class="activity-icon"><?= $act['icon'] ?></div>
                            <div class="activity-details">
                                <div class="activity-title"><?= $act['title'] ?></div>
                                <div class="activity-meta"><?= $act['meta'] ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <button class="btn-primary" onclick="window.location.href='insertItem.html'">
                        <span class="action-icon">➕</span>
                        Add New Product
                    </button>
                    <button class="btn-secondary" onclick="window.location.href='generateReport.html'">
                        <span class="action-icon">📋</span>
                        Generate Report
                    </button>
                    <button class="btn-info" onclick="window.location.href='listMaterials.php'">
                        <span class="action-icon">🔍</span>
                        View Inventory
                    </button>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Check login status
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('isLoggedIn') !== 'true') {
                window.location.href = 'login.html';
                return;
            }

            // Display username if available
            const username = localStorage.getItem('username');
            if (username) {
                document.getElementById('userDisplay').textContent = `Welcome, ${username}!`;
            }
        });

        // Logout functionality
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('username');
            window.location.href = 'login.html';
        });
    </script>
    </body>
    </html>
<?php $conn->close(); ?>