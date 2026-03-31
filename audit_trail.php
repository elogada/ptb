<?php
require_once 'auth_user.php';
require_once 'db.php';

$is_admin = ($_SESSION['role'] === 'admin');

// Handle CSV export (admin only)
if ($is_admin && isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_logs.csv"');

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, ['ID', 'User ID', 'Action', 'Target Type', 'Target ID', 'Details', 'Timestamp']);

    $stmt = $pdo->query("
        SELECT id, user_id, action, target_type, target_id, details, created_at
        FROM audit_logs
        ORDER BY id DESC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

// Fetch last 20 logs (admin only)
$logs = [];
if ($is_admin) {
    $stmt = $pdo->prepare("
        SELECT id, user_id, action, target_type, target_id, details, created_at
        FROM audit_logs
        ORDER BY id DESC
        LIMIT 20
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Audit Trail</title>
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
        }

        .back-link {
            color: #fff;
            text-decoration: none;
            background: rgba(255,255,255,0.12);
            padding: 8px 10px;
            border-radius: 8px;
        }

        .logout-button {
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            background: #dc2626;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }

        .container {
            max-width: 1100px;
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
        }

        .admin-warning {
            padding: 16px;
            border-radius: 10px;
            background: #fee2e2;
            color: #991b1b;
            font-weight: bold;
        }

        .export-button {
            display: inline-block;
            margin-bottom: 18px;
            padding: 12px 16px;
            border-radius: 8px;
            background: #16a34a;
            color: #fff;
            text-decoration: none;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            font-size: 14px;
            vertical-align: top;
        }

        th {
            background: #f9fafb;
        }

        .details {
            max-width: 400px;
            white-space: pre-wrap;
            line-height: 1.5;
        }

        .empty-state {
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div><strong>PTB - Audit Trail</strong></div>
        <div>
            <a class="back-link" href="navigation.php">← Balik</a>
            <form style="display:inline;" method="POST" action="logout.php">
                <button class="logout-button">Log Out</button>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h1>Audit Trail</h1>

            <?php if (!$is_admin): ?>
                <div class="admin-warning">
                    Admin lang ang puwedeng tumingin ng audit trail.
                </div>

            <?php else: ?>

                <a class="export-button" href="audit_trail.php?export=csv">
                    ⬇ Export CSV
                </a>

                <?php if (empty($logs)): ?>
                    <div class="empty-state">
                        Wala pang logs sa system.
                    </div>
                <?php else: ?>

                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Target</th>
                                <th>Details</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo (int)$log['id']; ?></td>
                                    <td><?php echo (int)$log['user_id']; ?></td>
                                    <td><?php echo htmlspecialchars($log['action']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($log['target_type']); ?>
                                        #<?php echo htmlspecialchars($log['target_id'] ?? '-'); ?>
                                    </td>
                                    <td class="details"><?php echo htmlspecialchars($log['details']); ?></td>
                                    <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>