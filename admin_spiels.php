<?php
require_once 'auth_admin.php';
require_once 'db.php';

$stmt = $pdo->prepare("
    SELECT s.id, s.title, s.category, s.content, s.is_active, s.created_at, u.username AS created_by_name
    FROM spiels s
    LEFT JOIN users u ON s.created_by = u.id
    ORDER BY s.category ASC, s.id ASC
");
$stmt->execute();
$spiels = $stmt->fetchAll();

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Admin Spiel Management</title>
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
            font-size: 14px;
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
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
        }

        .container {
            max-width: 1200px;
            margin: 32px auto;
            padding: 0 20px;
        }

        .header-card, .table-card {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .header-card h1 {
            margin: 0 0 8px;
        }

        .header-card p {
            margin: 0;
            color: #666;
        }

        .add-button {
            display: inline-block;
            padding: 12px 16px;
            border-radius: 8px;
            background: #16a34a;
            color: #fff;
            text-decoration: none;
            font-weight: bold;
        }

        .success-message {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #dcfce7;
            color: #166534;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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
        }

        .content-preview {
            max-width: 360px;
            white-space: pre-wrap;
            line-height: 1.5;
            color: #444;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-link, .action-button {
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: bold;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .edit-link {
            background: #f59e0b;
            color: #fff;
        }

        .toggle-active {
            background: #2563eb;
            color: #fff;
        }

        .toggle-inactive {
            background: #6b7280;
            color: #fff;
        }

        form {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">PTB - Admin Spiel Management</div>
            <a class="back-link" href="navigation.php">← Balik sa Navigation</a>
            <a class="back-link" href="spiels.php">View CSR Spiels</a>
        </div>

        <form method="POST" action="logout.php">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </div>

    <div class="container">
        <div class="header-card">
            <div class="header-row">
                <div>
                    <h1>Spiel Management</h1>
                    <p>Dito puwedeng magdagdag, mag-edit, at mag-activate/deactivate ng mga spiel.</p>
                </div>

                <a class="add-button" href="add_spiel.php">+ Magdagdag ng Spiel</a>
            </div>

            <?php if ($success === 'added'): ?>
                <div class="success-message">Matagumpay na nadagdag ang spiel.</div>
            <?php elseif ($success === 'updated'): ?>
                <div class="success-message">Matagumpay na na-update ang spiel.</div>
            <?php elseif ($success === 'toggled'): ?>
                <div class="success-message">Na-update ang status ng spiel.</div>
            <?php endif; ?>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Content</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($spiels as $spiel): ?>
                        <tr>
                            <td><?php echo (int)$spiel['id']; ?></td>
                            <td><?php echo htmlspecialchars($spiel['title']); ?></td>
                            <td><?php echo htmlspecialchars($spiel['category']); ?></td>
                            <td class="content-preview"><?php echo htmlspecialchars($spiel['content']); ?></td>
                            <td>
                                <?php if ((int)$spiel['is_active'] === 1): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($spiel['created_by_name'] ?? 'Unknown'); ?></td>
                            <td><?php echo htmlspecialchars($spiel['created_at']); ?></td>
                            <td>
                                <div class="action-group">
                                    <a class="action-link edit-link" href="edit_spiel.php?id=<?php echo (int)$spiel['id']; ?>">
                                        I-edit
                                    </a>

                                    <form method="POST" action="toggle_spiel.php">
                                        <input type="hidden" name="spiel_id" value="<?php echo (int)$spiel['id']; ?>">
                                        <input type="hidden" name="current_status" value="<?php echo (int)$spiel['is_active']; ?>">
                                        <button
                                            type="submit"
                                            class="action-button <?php echo ((int)$spiel['is_active'] === 1) ? 'toggle-inactive' : 'toggle-active'; ?>"
                                        >
                                            <?php echo ((int)$spiel['is_active'] === 1) ? 'I-deactivate' : 'I-activate'; ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($spiels)): ?>
                        <tr>
                            <td colspan="8">Wala pang spiel sa system.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>