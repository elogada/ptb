<?php
require_once 'auth_user.php';
require_once 'db.php';

$role = $_SESSION['role'] ?? '';
$is_admin = ($role === 'admin');

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$limit = 10;
$offset = ($page - 1) * $limit;

// Count total orders
$count_stmt = $pdo->query("SELECT COUNT(*) AS total FROM orders");
$total_orders = (int)$count_stmt->fetch()['total'];

$total_pages = max(1, (int)ceil($total_orders / $limit));

if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Fetch paginated orders
$stmt = $pdo->prepare("
    SELECT 
        o.id,
        o.customer_name,
        o.customer_contact,
        o.delivery_address,
        o.order_items,
        o.subtotal,
        o.delivery_fee,
        o.total_amount,
        o.status,
        o.closure_reason,
        o.notes,
        o.created_at,
        u.username AS assigned_username
    FROM orders o
    LEFT JOIN users u ON o.assigned_user_id = u.id
    ORDER BY o.id DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

function getStatusClass(string $status): string {
    return match ($status) {
        'New' => 'status-new',
        'For Confirmation' => 'status-confirmation',
        'Confirmed' => 'status-confirmed',
        'Preparing' => 'status-preparing',
        'Out for Delivery' => 'status-delivery',
        'Completed' => 'status-completed',
        'Cancelled' => 'status-cancelled',
        'Rejected' => 'status-rejected',
        default => 'status-default',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Mga Order</title>
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
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .topbar-title {
            font-size: 20px;
            font-weight: bold;
        }

        .back-link {
            color: #fff;
            text-decoration: none;
            background: rgba(255,255,255,0.12);
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 14px;
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
            max-width: 1300px;
            margin: 32px auto;
            padding: 0 20px;
        }

        .page-header,
        .table-card,
        .empty-state {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .page-header p {
            margin: 0;
            color: #666;
            line-height: 1.6;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .primary-button,
        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
        }

        .primary-button {
            background: #16a34a;
            color: #fff;
        }

        .secondary-button {
            background: #2563eb;
            color: #fff;
        }

        .summary-line {
            margin-top: 14px;
            color: #444;
            font-size: 14px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
            font-size: 14px;
        }

        th {
            background: #f9fafb;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #555;
        }

        .order-items,
        .notes-cell {
            white-space: pre-wrap;
            line-height: 1.5;
            color: #444;
            max-width: 220px;
        }

        .reason-text {
            margin-top: 6px;
            font-size: 12px;
            color: #7c2d12;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            line-height: 1.2;
        }

        .status-new {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-confirmation {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-preparing {
            background: #ede9fe;
            color: #6d28d9;
        }

        .status-delivery {
            background: #dcfce7;
            color: #166534;
        }

        .status-completed {
            background: #bbf7d0;
            color: #166534;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-rejected {
            background: #fce7f3;
            color: #9d174d;
        }

        .status-default {
            background: #e5e7eb;
            color: #374151;
        }

        .action-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 12px;
            border-radius: 8px;
            background: #f59e0b;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
        }

        .empty-state {
            color: #666;
        }

        .pagination {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .pagination-info {
            font-size: 14px;
            color: #555;
        }

        .pagination-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .page-button,
        .page-button-disabled {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
        }

        .page-button {
            background: #2563eb;
            color: #fff;
        }

        .page-button-disabled {
            background: #d1d5db;
            color: #6b7280;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">PTB - Mga Order</div>
            <a class="back-link" href="navigation.php">← Balik sa Navigation</a>
        </div>

        <form class="logout-form" method="POST" action="logout.php">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </div>

    <div class="container">
        <div class="page-header">
            <div class="page-header-row">
                <div>
                    <h1>Mga Order</h1>
                    <p>
                        Dito makikita ang listahan ng mga order sa system. Puwedeng i-review ang status,
                        customer details, at magbukas ng page para mag-edit ng order.
                    </p>
                </div>

                <div class="header-actions">
                    <a href="add_order.php" class="primary-button">+ Magdagdag ng Order</a>
                    <a href="orders.php?page=1" class="secondary-button">I-refresh</a>
                </div>
            </div>

            <div class="summary-line">
                Kabuuang orders: <strong><?php echo $total_orders; ?></strong>
                &nbsp;|&nbsp;
                Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong>
            </div>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                Wala pang order sa system.
            </div>
        <?php else: ?>
            <div class="table-card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Notes</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo (int)$order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_contact'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                                    <td class="order-items"><?php echo htmlspecialchars($order['order_items']); ?></td>
                                    <td>₱<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo getStatusClass($order['status']); ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>

                                        <?php if (!empty($order['closure_reason'])): ?>
                                            <div class="reason-text">
                                                Reason: <?php echo htmlspecialchars($order['closure_reason']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['assigned_username'] ?? '-'); ?></td>
                                    <td class="notes-cell"><?php echo htmlspecialchars($order['notes'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                    <td>
                                        <a class="action-link" href="edit_order.php?id=<?php echo (int)$order['id']; ?>">
                                            I-edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <div class="pagination-info">
                        Showing latest orders in groups of 10.
                    </div>

                    <div class="pagination-buttons">
                        <?php if ($page > 1): ?>
                            <a class="page-button" href="orders.php?page=<?php echo $page - 1; ?>">← Previous</a>
                        <?php else: ?>
                            <span class="page-button-disabled">← Previous</span>
                        <?php endif; ?>

                        <?php if ($page < $total_pages): ?>
                            <a class="page-button" href="orders.php?page=<?php echo $page + 1; ?>">Next →</a>
                        <?php else: ?>
                            <span class="page-button-disabled">Next →</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>