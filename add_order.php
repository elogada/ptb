<?php
require_once 'auth_user.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Add Order</title>
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
        <strong>PTB - Add Order</strong>
    </div>

    <div class="container">
        <div class="card">
            <h1>Add Order</h1>
            <p>Placeholder page pa ito for now. Dito natin gagawin mamaya ang form para sa bagong order.</p>

            <a class="back-link" href="orders.php">← Balik sa Orders</a>
        </div>
    </div>
</body>
</html>