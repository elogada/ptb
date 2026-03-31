<?php
require_once 'auth_user.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    http_response_code(400);
    exit('400 Bad Request - Invalid order ID.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Edit Order</title>
    <style>
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

        .back-link {
            display: inline-block;
            margin-top: 16px;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <strong>PTB - Edit Order</strong>
    </div>

    <div class="container">
        <div class="card">
            <h1>Edit Order #<?php echo (int)$order_id; ?></h1>
            <p>Placeholder page pa ito for now. Dito natin gagawin mamaya ang full order editing form.</p>

            <a class="back-link" href="orders.php">← Balik sa Orders</a>
        </div>
    </div>
</body>
</html>