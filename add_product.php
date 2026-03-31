<?php
require_once 'auth_admin.php';
require_once 'db.php';

$error = '';

$product_name = '';
$category = 'Canned Goods';
$unit = '';
$cost_price = '0.00';
$selling_price = '0.00';
$stock_mode = 'Stocked';
$is_active = 1;
$notes = '';

$allowed_categories = ['Snacks', 'Frozen Goods', 'Canned Goods'];
$allowed_stock_modes = ['Stocked', 'On-Demand'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $cost_price = trim($_POST['cost_price'] ?? '0');
    $selling_price = trim($_POST['selling_price'] ?? '0');
    $stock_mode = trim($_POST['stock_mode'] ?? '');
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $notes = trim($_POST['notes'] ?? '');

    if (
        $product_name === '' ||
        !in_array($category, $allowed_categories, true) ||
        !in_array($stock_mode, $allowed_stock_modes, true) ||
        !in_array($is_active, [0, 1], true)
    ) {
        $error = 'May invalid o kulang na input. Paki-check ulit ang form.';
    } elseif (!is_numeric($cost_price) || !is_numeric($selling_price)) {
        $error = 'Ang cost price at selling price ay dapat valid number.';
    } else {
        $created_by = $_SESSION['user_id'];

        $insert_stmt = $pdo->prepare("
            INSERT INTO products (
                product_name,
                category,
                unit,
                cost_price,
                selling_price,
                stock_mode,
                is_active,
                notes,
                created_by,
                updated_by
            ) VALUES (
                :product_name,
                :category,
                :unit,
                :cost_price,
                :selling_price,
                :stock_mode,
                :is_active,
                :notes,
                :created_by,
                :updated_by
            )
        ");

        $insert_stmt->execute([
            ':product_name' => $product_name,
            ':category' => $category,
            ':unit' => ($unit === '' ? null : $unit),
            ':cost_price' => number_format((float)$cost_price, 2, '.', ''),
            ':selling_price' => number_format((float)$selling_price, 2, '.', ''),
            ':stock_mode' => $stock_mode,
            ':is_active' => $is_active,
            ':notes' => ($notes === '' ? null : $notes),
            ':created_by' => $created_by,
            ':updated_by' => $created_by
        ]);

        $new_product_id = (int)$pdo->lastInsertId();

        $details = "Product added. "
            . "Name: {$product_name}. "
            . "Category: {$category}. "
            . "Unit: {$unit}. "
            . "Cost: " . number_format((float)$cost_price, 2, '.', '') . ". "
            . "Selling: " . number_format((float)$selling_price, 2, '.', '') . ". "
            . "Stock Mode: {$stock_mode}. "
            . "Active: {$is_active}. "
            . "Notes: {$notes}.";

        $log_stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, target_type, target_id, details)
            VALUES (:user_id, :action, :target_type, :target_id, :details)
        ");
        $log_stmt->execute([
            ':user_id' => $created_by,
            ':action' => 'ADD_PRODUCT',
            ':target_type' => 'product',
            ':target_id' => $new_product_id,
            ':details' => $details
        ]);

        header("Location: admin_products.php?success=added");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Add Product</title>
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

        .container {
            max-width: 900px;
            margin: 32px auto;
            padding: 0 20px;
        }

        .card {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 10px;
        }

        .subtitle {
            margin-top: 0;
            color: #666;
            margin-bottom: 24px;
        }

        .error-message {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #fee2e2;
            color: #991b1b;
            font-weight: bold;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
            font-family: Arial, sans-serif;
            background: #fff;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .helper-text {
            margin-top: 6px;
            font-size: 13px;
            color: #666;
        }

        .button-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .primary-button,
        .secondary-link {
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
            font-size: 14px;
        }

        .primary-button {
            border: none;
            cursor: pointer;
            background: #16a34a;
            color: #fff;
        }

        .secondary-link {
            background: #e5e7eb;
            color: #111827;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">PTB - Add Product</div>
            <a class="back-link" href="admin_products.php">← Balik sa Product Catalog</a>
        </div>

        <form class="logout-form" method="POST" action="logout.php">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </div>

    <div class="container">
        <div class="card">
            <h1>Magdagdag ng Product</h1>
            <p class="subtitle">Puwedeng mag-encode rito ng bagong product para sa catalog.</p>

            <?php if ($error !== ''): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="product_name">Product Name</label>
                        <input
                            type="text"
                            id="product_name"
                            name="product_name"
                            value="<?php echo htmlspecialchars($product_name); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <?php foreach ($allowed_categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="unit">Unit</label>
                        <input
                            type="text"
                            id="unit"
                            name="unit"
                            value="<?php echo htmlspecialchars($unit); ?>"
                            placeholder="Halimbawa: Pack, Can, Piece"
                        >
                    </div>

                    <div class="form-group">
                        <label for="cost_price">Cost Price</label>
                        <input
                            type="number"
                            id="cost_price"
                            name="cost_price"
                            step="0.01"
                            min="0"
                            value="<?php echo htmlspecialchars($cost_price); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="selling_price">Selling Price</label>
                        <input
                            type="number"
                            id="selling_price"
                            name="selling_price"
                            step="0.01"
                            min="0"
                            value="<?php echo htmlspecialchars($selling_price); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="stock_mode">Stock Mode</label>
                        <select id="stock_mode" name="stock_mode" required>
                            <?php foreach ($allowed_stock_modes as $mode): ?>
                                <option value="<?php echo htmlspecialchars($mode); ?>" <?php echo ($stock_mode === $mode) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mode); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="is_active">Status</label>
                        <select id="is_active" name="is_active" required>
                            <option value="1" <?php echo ($is_active === 1) ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo ($is_active === 0) ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes"><?php echo htmlspecialchars($notes); ?></textarea>
                        <div class="helper-text">Optional lang ito. Puwede rito ang stocking notes o internal reminders.</div>
                    </div>
                </div>

                <div class="button-row">
                    <button type="submit" class="primary-button">I-save ang Product</button>
                    <a href="admin_products.php" class="secondary-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>