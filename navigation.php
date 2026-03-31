<?php
require_once 'auth_user.php';
require_once 'db.php';

$username = $_SESSION['username'] ?? 'Unknown';
$role = $_SESSION['role'] ?? 'Unknown';
$user_id = $_SESSION['user_id'] ?? 0;
$is_admin = ($role === 'admin');

date_default_timezone_set('Asia/Manila');
$today_display = date('F j, Y');

// Dashboard summary queries
$product_count_stmt = $pdo->query("SELECT COUNT(*) AS total FROM products");
$total_products = (int)$product_count_stmt->fetch()['total'];

$order_count_stmt = $pdo->query("SELECT COUNT(*) AS total FROM orders");
$total_orders = (int)$order_count_stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Navigation</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #222;
        }

        .topbar {
            background: #1f2937;
            color: #fff;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .topbar-title {
            font-size: 20px;
            font-weight: bold;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .user-badge {
            font-size: 14px;
            background: rgba(255,255,255,0.12);
            padding: 8px 10px;
            border-radius: 8px;
        }

        .logout-form {
            margin: 0;
        }

        .logout-button {
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            background: #dc2626;
            color: #fff;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .logout-button:hover {
            background: #b91c1c;
        }

        .container {
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 20px;
        }

        .welcome-card {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
        }

        .welcome-card h1 {
            margin: 0 0 10px;
            font-size: 28px;
        }

        .meta {
            margin: 6px 0;
            color: #555;
        }

        .dashboard-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .summary-item {
            background: #ffffff;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .summary-label {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .summary-value {
            font-size: 22px;
            font-weight: bold;
            color: #111827;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }

        .nav-card {
            background: #fff;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-decoration: none;
            color: inherit;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .nav-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 34px rgba(0, 0, 0, 0.12);
        }

        .nav-card h2 {
            margin: 0 0 10px;
            font-size: 20px;
        }

        .nav-card p {
            margin: 0;
            color: #666;
            line-height: 1.5;
            font-size: 14px;
        }

        .admin-note {
            margin-top: 20px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #eef4ff;
            border-left: 5px solid #2c6bed;
            color: #2b4c7e;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-title">PTB - Order Management Console</div>

        <div class="topbar-right">
            <div class="user-badge">
                <?php echo htmlspecialchars($username); ?> | <?php echo htmlspecialchars($role); ?>
            </div>

            <form class="logout-form" method="POST" action="logout.php">
                <button type="submit" class="logout-button">Log Out</button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="welcome-card">
            <h1>Welcome!</h1>
            <p class="meta"><strong>User ID:</strong> <?php echo (int)$user_id; ?></p>
            <p class="meta"><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></p>
            <p class="meta"><strong>Role:</strong> <?php echo htmlspecialchars($role); ?></p>

            <div class="dashboard-summary">
                <div class="summary-item">
                    <div class="summary-label">Today</div>
                    <div class="summary-value"><?php echo htmlspecialchars($today_display); ?></div>
                </div>

                <div class="summary-item">
                    <div class="summary-label">Products in Catalog</div>
                    <div class="summary-value"><?php echo $total_products; ?></div>
                </div>

                <div class="summary-item">
                    <div class="summary-label">Total Orders</div>
                    <div class="summary-value"><?php echo $total_orders; ?></div>
                </div>
            </div>

            <?php if ($is_admin): ?>
                <div class="admin-note">
                    Admin access detected. Puwede kang mag-manage ng content at settings sa admin pages.
                </div>
            <?php endif; ?>
        </div>

        <div class="grid">
            <a class="nav-card" href="spiels.php">
                <h2>Mga Spiel</h2>
                <p>Tingnan at kopyahin ang mga standard na reply para sa customer interaction.</p>
            </a>

            <a class="nav-card" href="products.php">
                <h2>Products</h2>
                <p>Tingnan ang active product catalog, pricing, at stock mode reference.</p>
            </a>

            <a class="nav-card" href="orders.php">
                <h2>Orders</h2>
                <p>Tingnan ang order queue, status, customer details, at magbukas ng order para i-edit.</p>
            </a>

            <a class="nav-card" href="audit_trail.php">
                <h2>Audit Trail</h2>
                <p>Tingnan ang system activity logs at mag-export ng CSV kung kailangan.</p>
            </a>

            <?php if ($is_admin): ?>
                <a class="nav-card" href="admin_spiels.php">
                    <h2>Admin - Spiel Management</h2>
                    <p>Magdagdag, mag-edit, at mag-activate/deactivate ng mga spiel.</p>
                </a>

                <a class="nav-card" href="admin_products.php">
                    <h2>Admin - Product Catalog</h2>
                    <p>Mag-manage ng product list, category, pricing, at stocking reference.</p>
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
