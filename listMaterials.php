<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connect.php';

// Fetch all materials
$sql = "SELECT * FROM materials ORDER BY material_id DESC";
$result = $conn->query($sql);
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Material List - Inventory Management System</title>
        <link rel="stylesheet" href="styles.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            .search-bar {
                margin-bottom: 18px;
                display: flex;
                align-items: center;
            }
            .search-bar input[type="text"] {
                padding: 8px 16px;
                border: 1px solid #ccc;
                border-radius: 6px;
                font-size: 1rem;
                width: 260px;
                margin-right: 10px;
            }
            .search-bar label {
                margin-right: 10px;
                font-weight: 500;
            }
            .low-stock {
                background: #ffeaea !important;
                color: #b70000 !important;
                font-weight: 500;
            }
            .low-stock td {
                border-bottom: 2px solid #b70000;
            }
            .btn-pagination {
                background: #e6e8ed;
                color: #222;
                border: none;
                border-radius: 4px;
                padding: 7px 15px;
                font-size: 1rem;
                cursor: pointer;
                margin: 0 2px;
                transition: background 0.14s;
            }
            .btn-pagination:hover {
                background: #d3d8e2;
            }
        </style>
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
            <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <li><a href="insertItem.html" class="nav-link">Insert Item</a></li>
            <li><a href="insertMaterial.html" class="nav-link">Insert Material</a></li>
            <li><a href="listMaterials.php" class="nav-link" aria-current="page">Material List</a></li>
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
            <h1>Material List</h1>
            <button id="exportCsvBtn" class="btn-secondary" style="margin-bottom:12px;">Export to CSV</button>
            <div class="search-bar">
                <label for="searchInput">Search:</label>
                <input type="text" id="searchInput" placeholder="Type to filter by any field...">
            </div>
            <div style="margin-bottom:10px;">
                <span style="display:inline-block;width:18px;height:18px;vertical-align:middle;background:#ffeaea;border:1px solid #b70000;margin-right:6px;"></span>
                <span style="vertical-align:middle;font-size:1rem;color:#b70000;font-weight:500;">Low Stock (below re-order level)</span>
            </div>
            <div class="table-responsive">
                <table class="material-table" id="materialsTable">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Physical Qty</th>
                        <th>Reserved Qty</th>
                        <th>Unit</th>
                        <th>Re-order Level</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php $isLowStock = $row['physical_quantity'] < $row['re_order_level']; ?>
                            <tr<?= $isLowStock ? ' class="low-stock"' : '' ?>>
                                <td><?= htmlspecialchars($row['material_id']) ?></td>
                                <td><?= htmlspecialchars($row['material_name']) ?></td>
                                <td><?= htmlspecialchars($row['physical_quantity']) ?></td>
                                <td><?= htmlspecialchars($row['reserved_quantity']) ?></td>
                                <td><?= htmlspecialchars($row['unit']) ?></td>
                                <td><?= htmlspecialchars($row['re_order_level']) ?></td>
                                <td><?= htmlspecialchars($row['material_description']) ?></td>
                                <td>
                                    <a href="editMaterial.php?id=<?= $row['material_id'] ?>" class="btn-edit">Edit</a>
                                    <button class="btn-delete" data-id="<?= $row['material_id'] ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center;">No materials found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
            <div id="pagination" style="margin-top:18px;text-align:center;"></div>
        </div>
    </main>
    <script>
        // Pagination setup
        const rowsPerPage = 10;
        let currentPage = 1;

        function renderTablePage(page) {
            const rows = Array.from(document.querySelectorAll('#materialsTable tbody tr'));
            // Only visible rows (filtered by search)
            const filteredRows = rows.filter(row => row.style.display !== 'none');
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);

            // Hide all first
            rows.forEach(row => row.style.display = 'none');
            // Show current page
            const start = (page - 1) * rowsPerPage;
            const end = start + rowsPerPage;
            filteredRows.slice(start, end).forEach(row => row.style.display = '');

            // Render pagination buttons
            const pagination = document.getElementById('pagination');
            pagination.innerHTML = '';
            if (totalPages > 1) {
                for (let i = 1; i <= totalPages; i++) {
                    const btn = document.createElement('button');
                    btn.textContent = i;
                    btn.className = (i === page ? 'btn-secondary' : 'btn-pagination');
                    btn.style.margin = '0 3px 0 3px';
                    btn.onclick = () => {
                        currentPage = i;
                        renderTablePage(currentPage);
                    };
                    pagination.appendChild(btn);
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Session/login and logout logic
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

            // Delete button logic (AJAX)
            document.querySelectorAll('.btn-delete').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const materialId = this.getAttribute('data-id');
                    if (confirm("Are you sure you want to delete this material?")) {
                        fetch('deleteMaterial.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'id=' + encodeURIComponent(materialId)
                        })
                            .then(response => response.json())
                            .then(data => {
                                showToast(data.message, !data.success);
                                if (data.success) {
                                    this.closest('tr').remove();
                                    renderTablePage(currentPage); // update page after deletion
                                }
                            })
                            .catch(() => showToast("Delete failed.", true));
                    }
                });
            });

            // SEARCH/FILTER LOGIC
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const filter = this.value.trim().toLowerCase();
                const rows = document.querySelectorAll('#materialsTable tbody tr');
                rows.forEach(function(row) {
                    const cellsText = Array.from(row.querySelectorAll('td')).slice(0, -1).map(td => td.textContent.toLowerCase()).join(' ');
                    if (cellsText.indexOf(filter) > -1) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                currentPage = 1;
                renderTablePage(currentPage);
            });

            // CSV Export logic (only visible rows)
            document.getElementById('exportCsvBtn').addEventListener('click', function() {
                const table = document.getElementById('materialsTable');
                let csv = [];
                const rows = table.querySelectorAll('tr');
                for (let i = 0; i < rows.length; i++) {
                    if (rows[i].style.display === 'none') continue;
                    let row = [], cols = rows[i].querySelectorAll('th,td');
                    for (let j = 0; j < cols.length - 1; j++) {
                        let text = cols[j].innerText.replace(/"/g, '""');
                        row.push('"' + text + '"');
                    }
                    csv.push(row.join(','));
                }
                const csvBlob = new Blob([csv.join('\n')], { type: 'text/csv' });
                const url = URL.createObjectURL(csvBlob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'materials_list.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });

            // Initial render
            renderTablePage(currentPage);
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
            }, 2600);
        }
    </script>
    </body>
    </html>
<?php $conn->close(); ?>