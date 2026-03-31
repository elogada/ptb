<?php
require_once 'auth_admin.php';
require_once 'db.php';

$error = '';
$title = '';
$category = '';
$content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $created_by = $_SESSION['user_id'];

    if ($title === '' || $category === '' || $content === '') {
        $error = 'Pakilagyan ang lahat ng required fields.';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO spiels (title, category, content, created_by)
            VALUES (:title, :category, :content, :created_by)
        ");
        $stmt->execute([
            ':title' => $title,
            ':category' => $category,
            ':content' => $content,
            ':created_by' => $created_by
        ]);

        header("Location: admin_spiels.php?success=added");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Add Spiel</title>
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

        .helper-text {
            margin-top: 6px;
            font-size: 13px;
            color: #666;
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
        <div><strong>PTB - Add Spiel</strong></div>
        <a class="back-link" href="admin_spiels.php">← Balik sa Spiel Management</a>
    </div>

    <div class="container">
        <div class="card">
            <h1>Magdagdag ng Bagong Spiel</h1>

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
                    <div class="helper-text">Example: Greeting, Clarification, Order Complete, Cancellation</div>
                </div>

                <div class="form-group">
                    <label for="content">Spiel Content</label>
                    <textarea id="content" name="content" required><?php echo htmlspecialchars($content); ?></textarea>
                    <div class="helper-text">Keep it short, malinaw, at easy i-copy-paste.</div>
                </div>

                <div class="button-row">
                    <button type="submit" class="primary-button">I-save ang Spiel</button>
                    <a href="admin_spiels.php" class="secondary-link">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>