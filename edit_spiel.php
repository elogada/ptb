<?php
require_once 'auth_admin.php';
require_once 'db.php';

$spiel_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($spiel_id <= 0) {
    http_response_code(400);
    exit('400 Bad Request - Invalid spiel ID.');
}

$stmt = $pdo->prepare("
    SELECT id, title, category, content
    FROM spiels
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $spiel_id]);
$spiel = $stmt->fetch();

if (!$spiel) {
    http_response_code(404);
    exit('404 Not Found - Spiel not found.');
}

$error = '';
$title = $spiel['title'];
$category = $spiel['category'];
$content = $spiel['content'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $updated_by = $_SESSION['user_id'];

    if ($title === '' || $category === '' || $content === '') {
        $error = 'Pakilagyan ang lahat ng required fields.';
    } else {
        // Save old values before update for audit logging
        $old_title = $spiel['title'];
        $old_category = $spiel['category'];
        $old_content = $spiel['content'];

        $update_stmt = $pdo->prepare("
            UPDATE spiels
            SET title = :title,
                category = :category,
                content = :content,
                updated_by = :updated_by
            WHERE id = :id
        ");
        $update_stmt->execute([
            ':title' => $title,
            ':category' => $category,
            ':content' => $content,
            ':updated_by' => $updated_by,
            ':id' => $spiel_id
        ]);

        // Build audit details
        $details = "Spiel updated. "
            . "Old Title: {$old_title} | New Title: {$title}. "
            . "Old Category: {$old_category} | New Category: {$category}. "
            . "Old Content: {$old_content} | New Content: {$content}.";

        // Insert audit log
        $log_stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, target_type, target_id, details)
            VALUES (:user_id, :action, :target_type, :target_id, :details)
        ");
        $log_stmt->execute([
            ':user_id' => $updated_by,
            ':action' => 'EDIT_SPIEL',
            ':target_type' => 'spiel',
            ':target_id' => $spiel_id,
            ':details' => $details
        ]);

        header("Location: admin_spiels.php?success=updated");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Edit Spiel</title>
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

        .container {
            max-width: 760px;
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

        .error-message {
            margin-bottom: 16px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #fee2e2;
            color: #991b1b;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
            font-family: Arial, sans-serif;
        }

        textarea {
            min-height: 140px;
            resize: vertical;
        }

        .button-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .primary-button, .secondary-link {
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: bold;
            text-decoration: none;
        }

        .primary-button {
            border: none;
            cursor: pointer;
            background: #f59e0b;
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
        <div><strong>PTB - Edit Spiel</strong></div>
        <a class="back-link" href="admin_spiels.php">← Balik sa Spiel Management</a>
    </div>

    <div class="container">
        <div class="card">
            <h1>I-edit ang Spiel #<?php echo (int)$spiel_id; ?></h1>

            <?php if ($error !== ''): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($category); ?>" required>
                </div>

                <div class="form-group">
                    <label for="content">Spiel Content</label>
                    <textarea id="content" name="content" required><?php echo htmlspecialchars($content); ?></textarea>
                </div>

                <div class="button-row">
                    <button type="submit" class="primary-button">I-update ang Spiel</button>
                    <a href="admin_spiels.php" class="secondary-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>