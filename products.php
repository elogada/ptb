<?php
require_once 'auth_user.php';
require_once 'db.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$limit = 10;
$offset = ($page - 1) * $limit;

// Count active products only
$count_stmt = $pdo->query("SELECT COUNT(*) AS total FROM products WHERE is_active = 1");
$total_products = (int)$count_stmt->fetch()['total'];

$total_pages = max(1, (int)ceil($total_products / $limit));

if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Fetch active products only
$stmt = $pdo->prepare("
    SELECT
        id,
        product_name,
        category,
        unit,
        selling_price,
        stock_mode,
        notes,
        created_at
    FROM products
    WHERE is_active = 1
    ORDER BY category ASC, product_name ASC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Product Catalog</title>
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
            max-width: 1200px;
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

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .page-header p {
            margin: 0;
            color: #666;
            line-height: 1.6;
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
            min-width: 900px;
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

        .stock-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
            line-height: 1.2;
        }

        .stock-stocked {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .stock-ondemand {
            background: #fef3c7;
            color: #92400e;
        }

        .notes-cell {
            white-space: pre-wrap;
            line-height: 1.5;
            color: #444;
            max-width: 260px;
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
            <div class="topbar-title">PTB - Product Catalog</div>
            <a class="back-link" href="navigation.php">← Balik sa Navigation</a>
        </div>

        <form class="logout-form" method="POST" action="logout.php">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>Product Catalog</h1>
            <p>
                Dito makikita ang active products na puwedeng i-reference habang nagha-handle ng orders.
                Read-only view ito para sa standard users.
            </p>

            <div class="summary-line">
                Kabuuang active products: <strong><?php echo $total_products; ?></strong>
                &nbsp;|&nbsp;
                Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong>
            </div>
        </div>

        <?php if (empty($products)): ?>
            <div class="empty-state">
                Wala pang active products sa system.
            </div>
        <?php else: ?>
            <div class="table-card">
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Unit</th>
                                <th>Selling Price</th>
                                <th>Stock Mode</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>#<?php echo (int)$product['id']; ?></td>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td><?php echo htmlspecialchars($product['unit'] ?? '-'); ?></td>
                                    <td>₱<?php echo number_format((float)$product['selling_price'], 2); ?></td>
                                    <td>
                                        <?php if ($product['stock_mode'] === 'Stocked'): ?>
                                            <span class="stock-badge stock-stocked">Stocked</span>
                                        <?php else: ?>
                                            <span class="stock-badge stock-ondemand">On-Demand</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="notes-cell"><?php echo htmlspecialchars($product['notes'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <div class="pagination-info">
                        Showing active products in groups of 10.
                    </div>

                    <div class="pagination-buttons">
                        <?php if ($page > 1): ?>
                            <a class="page-button" href="products.php?page=<?php echo $page - 1; ?>">← Previous</a>
                        <?php else: ?>
                            <span class="page-button-disabled">← Previous</span>
                        <?php endif; ?>

                        <?php if ($page < $total_pages): ?>
                            <a class="page-button" href="products.php?page=<?php echo $page + 1; ?>">Next →</a>
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