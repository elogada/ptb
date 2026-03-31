<?php
require_once 'auth_user.php';
require_once 'db.php';

// Fetch active products
$stmt = $pdo->query("SELECT id, product_name, selling_price FROM products WHERE is_active = 1 ORDER BY product_name ASC");
$products = $stmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $customer_name = trim($_POST['customer_name'] ?? '');
    $items = $_POST['items'] ?? [];

    if ($customer_name === '' || empty($items)) {
        $error = 'Pakilagay ang customer name at pumili ng kahit isang product.';
    } else {

        $order_items = [];
        $total_amount = 0;

        foreach ($items as $product_id => $qty) {
            $qty = (int)$qty;

            if ($qty <= 0) continue;

            foreach ($products as $p) {
                if ($p['id'] == $product_id) {
                    $subtotal = $qty * $p['selling_price'];

                    $order_items[] = [
                        'product_id' => $p['id'],
                        'name' => $p['product_name'],
                        'qty' => $qty,
                        'price' => $p['selling_price'],
                        'subtotal' => $subtotal
                    ];

                    $total_amount += $subtotal;
                }
            }
        }

        if (empty($order_items)) {
            $error = 'Walang valid na items.';
        } else {

            $user_id = $_SESSION['user_id'];

            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    customer_name,
                    status,
                    total_amount,
                    items_json,
                    created_by
                ) VALUES (
                    :customer_name,
                    'New',
                    :total_amount,
                    :items_json,
                    :created_by
                )
            ");

            $stmt->execute([
                ':customer_name' => $customer_name,
                ':total_amount' => $total_amount,
                ':items_json' => json_encode($order_items),
                ':created_by' => $user_id
            ]);

            $order_id = $pdo->lastInsertId();

            // Audit log
            $log = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details)
                VALUES (:user_id, 'ADD_ORDER', 'order', :target_id, :details)
            ");

            $log->execute([
                ':user_id' => $user_id,
                ':target_id' => $order_id,
                ':details' => "Order created. Customer: {$customer_name}, Total: {$total_amount}"
            ]);

            header("Location: orders.php?success=added");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add Order</title>
    <style>
        body { font-family: Arial; background:#f4f6f9; }
        .container { max-width:900px; margin:40px auto; background:#fff; padding:24px; border-radius:12px; }
        table { width:100%; border-collapse: collapse; }
        td, th { padding:10px; border-bottom:1px solid #ddd; }
        .total { font-size:18px; font-weight:bold; margin-top:20px; }
        button { padding:10px 16px; background:#16a34a; color:#fff; border:none; border-radius:8px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Mag Add ng Order</h2>

    <?php if ($error): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Customer Name:</label>
        <input type="text" name="customer_name" required style="width:100%; padding:10px; margin-bottom:20px;">

        <table>
            <tr>
                <th>Select</th>
                <th>Product</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Subtotal</th>
            </tr>

            <?php foreach ($products as $p): ?>
            <tr>
                <td>
                    <input type="checkbox" class="chk" data-id="<?php echo $p['id']; ?>">
                </td>
                <td><?php echo htmlspecialchars($p['product_name']); ?></td>
                <td><?php echo $p['selling_price']; ?></td>
                <td>
                    <select name="items[<?php echo $p['id']; ?>]" class="qty" data-id="<?php echo $p['id']; ?>">
                        <?php for ($i=0;$i<=10;$i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </td>
                <td id="sub_<?php echo $p['id']; ?>">0</td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="total">
            Total: ₱<span id="total">0</span>
        </div>

        <br>
        <button type="submit">Save Order</button>
    </form>
</div>

<script>
const prices = {
<?php foreach ($products as $p): ?>
    <?php echo $p['id']; ?>: <?php echo $p['selling_price']; ?>,
<?php endforeach; ?>
};

function updateTotals() {
    let total = 0;

    document.querySelectorAll('.qty').forEach(q => {
        let id = q.dataset.id;
        let qty = parseInt(q.value);
        let subtotal = qty * prices[id];

        document.getElementById('sub_'+id).innerText = subtotal;
        total += subtotal;
    });

    document.getElementById('total').innerText = total;
}

document.querySelectorAll('.qty').forEach(q => {
    q.addEventListener('change', updateTotals);
});
</script>

</body>
</html>