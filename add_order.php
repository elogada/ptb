<?php
require_once 'auth_user.php';
require_once 'db.php';

$error = '';

$customer_name = '';
$customer_contact = '';
$delivery_address = '';
$delivery_fee = '0.00';
$status = 'New';
$closure_reason = '';
$notes = '';
$assigned_user_id = '';

$allowed_statuses = [
    'New',
    'For Confirmation',
    'Confirmed',
    'Preparing',
    'Out for Delivery',
    'Completed',
    'Cancelled',
    'Rejected'
];

$allowed_closure_reasons = [
    '',
    'Customer unresponsive',
    'Customer cancelled before confirmation',
    'Customer cancelled after confirmation',
    'No funds / cannot pay',
    'Fake buyer / suspicious',
    'Abusive / hostile behavior',
    'Incomplete address',
    'Outside service area',
    'Item unavailable',
    'Delivery failed',
    'Other'
];

// Fetch active products
$product_stmt = $pdo->prepare("
    SELECT id, product_name, category, unit, selling_price
    FROM products
    WHERE is_active = 1
    ORDER BY category ASC, product_name ASC
");
$product_stmt->execute();
$products = $product_stmt->fetchAll();

// Fetch users for assignment
$user_stmt = $pdo->prepare("
    SELECT id, username, role
    FROM users
    ORDER BY username ASC
");
$user_stmt->execute();
$users = $user_stmt->fetchAll();

// Build product lookup for server-side validation
$product_lookup = [];
foreach ($products as $product) {
    $product_lookup[(int)$product['id']] = $product;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_contact = trim($_POST['customer_contact'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $delivery_fee = trim($_POST['delivery_fee'] ?? '0');
    $status = trim($_POST['status'] ?? 'New');
    $closure_reason = trim($_POST['closure_reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $assigned_user_id = trim($_POST['assigned_user_id'] ?? '');

    $selected_products = $_POST['selected_products'] ?? [];
    $quantities = $_POST['quantities'] ?? [];

    if ($customer_name === '' || $delivery_address === '' || !in_array($status, $allowed_statuses, true)) {
        $error = 'May kulang o invalid na field. Paki-check ulit ang form.';
    } elseif (!is_numeric($delivery_fee) || (float)$delivery_fee < 0) {
        $error = 'Ang delivery fee ay dapat valid number.';
    } else {
        $order_lines = [];
        $order_items_structured = [];
        $subtotal = 0.00;

        foreach ($selected_products as $product_id_raw) {
            $product_id = (int)$product_id_raw;

            if (!isset($product_lookup[$product_id])) {
                continue;
            }

            $qty = isset($quantities[$product_id]) ? (int)$quantities[$product_id] : 0;
            if ($qty <= 0) {
                continue;
            }

            $product = $product_lookup[$product_id];
            $price = (float)$product['selling_price'];
            $line_total = $price * $qty;
            $subtotal += $line_total;

            $order_lines[] = $qty . 'x ' . $product['product_name'];
            $order_items_structured[] = [
                'product_id' => $product_id,
                'product_name' => $product['product_name'],
                'qty' => $qty,
                'price' => $price
            ];
        }

        if (empty($order_lines)) {
            $error = 'Pumili ng kahit isang product at lagyan ng valid quantity.';
        } else {
            if (($status === 'Cancelled' || $status === 'Rejected') && $closure_reason === '') {
                $error = 'Kailangan ng closure reason kapag Cancelled o Rejected ang status.';
            } elseif ($status !== 'Cancelled' && $status !== 'Rejected') {
                $closure_reason = null;
            } elseif (!in_array($closure_reason, $allowed_closure_reasons, true)) {
                $error = 'Invalid ang closure reason.';
            }

            if ($error === '') {
                $created_by = (int)$_SESSION['user_id'];
                $updated_by = $created_by;
                $delivery_fee_value = (float)$delivery_fee;
                $total_amount = $subtotal + $delivery_fee_value;
                $order_items = implode(', ', $order_lines);

                $assigned_user_id_value = null;
                if ($assigned_user_id !== '') {
                    foreach ($users as $user) {
                        if ((int)$user['id'] === (int)$assigned_user_id) {
                            $assigned_user_id_value = (int)$assigned_user_id;
                            break;
                        }
                    }
                }

                $insert_stmt = $pdo->prepare("
                    INSERT INTO orders (
                        customer_name,
                        customer_contact,
                        delivery_address,
                        order_items,
                        order_items_json,
                        subtotal,
                        delivery_fee,
                        total_amount,
                        status,
                        closure_reason,
                        notes,
                        assigned_user_id,
                        created_by,
                        updated_by
                    ) VALUES (
                        :customer_name,
                        :customer_contact,
                        :delivery_address,
                        :order_items,
                        :order_items_json,
                        :subtotal,
                        :delivery_fee,
                        :total_amount,
                        :status,
                        :closure_reason,
                        :notes,
                        :assigned_user_id,
                        :created_by,
                        :updated_by
                    )
                ");

                $insert_stmt->execute([
                    ':customer_name' => $customer_name,
                    ':customer_contact' => ($customer_contact === '' ? null : $customer_contact),
                    ':delivery_address' => $delivery_address,
                    ':order_items' => $order_items,
                    ':order_items_json' => json_encode($order_items_structured, JSON_UNESCAPED_UNICODE),
                    ':subtotal' => number_format($subtotal, 2, '.', ''),
                    ':delivery_fee' => number_format($delivery_fee_value, 2, '.', ''),
                    ':total_amount' => number_format($total_amount, 2, '.', ''),
                    ':status' => $status,
                    ':closure_reason' => $closure_reason,
                    ':notes' => ($notes === '' ? null : $notes),
                    ':assigned_user_id' => $assigned_user_id_value,
                    ':created_by' => $created_by,
                    ':updated_by' => $updated_by
                ]);

                $new_order_id = (int)$pdo->lastInsertId();

                $details = 'Order added. '
                    . 'Customer: ' . $customer_name . '. '
                    . 'Contact: ' . ($customer_contact === '' ? '' : $customer_contact) . '. '
                    . 'Address: ' . $delivery_address . '. '
                    . 'Items: ' . $order_items . '. '
                    . 'Subtotal: ' . number_format($subtotal, 2, '.', '') . '. '
                    . 'Delivery Fee: ' . number_format($delivery_fee_value, 2, '.', '') . '. '
                    . 'Total: ' . number_format($total_amount, 2, '.', '') . '. '
                    . 'Status: ' . $status . '. '
                    . 'Closure Reason: ' . (($closure_reason ?? '') === '' ? '' : $closure_reason) . '. '
                    . 'Assigned User ID: ' . ($assigned_user_id_value === null ? '' : $assigned_user_id_value) . '.';

                $log_stmt = $pdo->prepare("
                    INSERT INTO audit_logs (user_id, action, target_type, target_id, details)
                    VALUES (:user_id, :action, :target_type, :target_id, :details)
                ");
                $log_stmt->execute([
                    ':user_id' => $created_by,
                    ':action' => 'ADD_ORDER',
                    ':target_type' => 'order',
                    ':target_id' => $new_order_id,
                    ':details' => $details
                ]);

                header('Location: orders.php?success=added');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Add Order</title>
    <style>
        * { box-sizing: border-box; }
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
        .logout-form { margin: 0; }
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
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 20px;
        }
        .card {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }
        h1 { margin-top: 0; margin-bottom: 10px; }
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
        .form-group { margin-bottom: 18px; }
        .full-width { grid-column: 1 / -1; }
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
            min-height: 100px;
            resize: vertical;
        }
        .product-table-wrapper { overflow-x: auto; }
        .product-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }
        .product-table th,
        .product-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-size: 14px;
            vertical-align: middle;
        }
        .product-table th {
            background: #f9fafb;
            text-transform: uppercase;
            font-size: 12px;
            color: #555;
        }
        .qty-select { max-width: 90px; }
        .summary-box {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 18px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .summary-row:last-child {
            margin-bottom: 0;
            font-weight: bold;
            font-size: 16px;
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
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">PTB - Add Order</div>
            <a class="back-link" href="orders.php">← Balik sa Orders</a>
        </div>

        <form class="logout-form" method="POST" action="logout.php">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </div>

    <div class="container">
        <div class="card">
            <h1>Magdagdag ng Order</h1>
            <p class="subtitle">Puwedeng mag-encode rito ng bagong order kasama ang customer details, products, at pricing.</p>

            <?php if ($error !== ''): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="addOrderForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="customer_name">Customer Name</label>
                        <input type="text" id="customer_name" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_contact">Contact Details</label>
                        <input type="text" id="customer_contact" name="customer_contact" value="<?php echo htmlspecialchars($customer_contact); ?>" placeholder="FB name, mobile number, etc.">
                    </div>

                    <div class="form-group full-width">
                        <label for="delivery_address">Delivery Address</label>
                        <textarea id="delivery_address" name="delivery_address" required><?php echo htmlspecialchars($delivery_address); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="assigned_user_id">Assigned User</label>
                        <select id="assigned_user_id" name="assigned_user_id">
                            <option value="">-- Walang assignment --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo (int)$user['id']; ?>" <?php echo ((string)$assigned_user_id === (string)$user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="delivery_fee">Delivery Fee</label>
                        <input type="number" id="delivery_fee" name="delivery_fee" step="0.01" min="0" value="<?php echo htmlspecialchars($delivery_fee); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required onchange="toggleClosureReason()">
                            <?php foreach ($allowed_statuses as $status_option): ?>
                                <option value="<?php echo htmlspecialchars($status_option); ?>" <?php echo ($status === $status_option) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status_option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" id="closureReasonGroup">
                        <label for="closure_reason">Closure Reason</label>
                        <select id="closure_reason" name="closure_reason">
                            <?php foreach ($allowed_closure_reasons as $reason): ?>
                                <option value="<?php echo htmlspecialchars($reason); ?>" <?php echo ($closure_reason === $reason) ? 'selected' : ''; ?>>
                                    <?php echo ($reason === '') ? '-- Wala --' : htmlspecialchars($reason); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes"><?php echo htmlspecialchars($notes); ?></textarea>
                        <div class="helper-text">Optional internal notes ito, tulad ng landmarks, customer behavior, o reminders.</div>
                    </div>
                </div>

                <div class="card" style="padding:18px; margin-top:8px; margin-bottom:18px;">
                    <h2 style="margin-top:0;">Piliin ang Products</h2>

                    <?php if (empty($products)): ?>
                        <p>Wala pang active products sa system. Magdagdag muna sa Product Catalog.</p>
                    <?php else: ?>
                        <div class="product-table-wrapper">
                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th>Piliin</th>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Unit</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <?php $pid = (int)$product['id']; ?>
                                        <tr>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="selected_products[]"
                                                    value="<?php echo $pid; ?>"
                                                    class="product-checkbox"
                                                    data-product-id="<?php echo $pid; ?>"
                                                    data-price="<?php echo htmlspecialchars($product['selling_price']); ?>"
                                                    onchange="recomputeOrder()"
                                                >
                                            </td>
                                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                                            <td><?php echo htmlspecialchars($product['unit'] ?? '-'); ?></td>
                                            <td>₱<?php echo number_format((float)$product['selling_price'], 2); ?></td>
                                            <td>
                                                <select
                                                    name="quantities[<?php echo $pid; ?>]"
                                                    class="qty-select"
                                                    data-product-id="<?php echo $pid; ?>"
                                                    onchange="recomputeOrder()"
                                                >
                                                    <?php for ($i = 0; $i <= 20; $i++): ?>
                                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </td>
                                            <td>
                                                ₱<span id="line-total-<?php echo $pid; ?>">0.00</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card" style="padding:18px;">
                    <h2 style="margin-top:0;">Order Summary</h2>

                    <div class="summary-box">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>₱<span id="subtotalDisplay">0.00</span></span>
                        </div>
                        <div class="summary-row">
                            <span>Delivery Fee</span>
                            <span>₱<span id="deliveryFeeDisplay"><?php echo htmlspecialchars(number_format((float)$delivery_fee, 2)); ?></span></span>
                        </div>
                        <div class="summary-row">
                            <span>Total Amount</span>
                            <span>₱<span id="totalAmountDisplay">0.00</span></span>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:18px;">
                        <label for="order_items_preview">Selected Items Preview</label>
                        <textarea id="order_items_preview" readonly></textarea>
                        <div class="helper-text">Auto-generated ito base sa products na pinili mo.</div>
                    </div>
                </div>

                <div class="button-row">
                    <button type="submit" class="primary-button">I-save ang Order</button>
                    <a href="orders.php" class="secondary-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function recomputeOrder() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            const deliveryFeeInput = document.getElementById('delivery_fee');
            const orderPreview = document.getElementById('order_items_preview');

            let subtotal = 0;
            let lines = [];

            checkboxes.forEach(function (checkbox) {
                const productId = checkbox.dataset.productId;
                const price = parseFloat(checkbox.dataset.price || '0');
                const qtySelect = document.querySelector('select[name="quantities[' + productId + ']"]');
                const qty = parseInt(qtySelect.value || '0', 10);
                const row = checkbox.closest('tr');
                const productName = row.children[1].innerText.trim();

                let lineTotal = 0;

                if (checkbox.checked && qty > 0) {
                    lineTotal = price * qty;
                    subtotal += lineTotal;
                    lines.push(qty + 'x ' + productName);
                }

                const lineTotalSpan = document.getElementById('line-total-' + productId);
                lineTotalSpan.innerText = lineTotal.toFixed(2);
            });

            const deliveryFee = parseFloat(deliveryFeeInput.value || '0');
            const totalAmount = subtotal + deliveryFee;

            document.getElementById('subtotalDisplay').innerText = subtotal.toFixed(2);
            document.getElementById('deliveryFeeDisplay').innerText = deliveryFee.toFixed(2);
            document.getElementById('totalAmountDisplay').innerText = totalAmount.toFixed(2);
            orderPreview.value = lines.join(', ');
        }

        function toggleClosureReason() {
            const status = document.getElementById('status').value;
            const group = document.getElementById('closureReasonGroup');

            if (status === 'Cancelled' || status === 'Rejected') {
                group.classList.remove('hidden');
            } else {
                group.classList.add('hidden');
                document.getElementById('closure_reason').value = '';
            }
        }

        document.getElementById('delivery_fee').addEventListener('input', recomputeOrder);

        window.addEventListener('load', function () {
            toggleClosureReason();
            recomputeOrder();
        });
    </script>
</body>
</html>
