<?php
require_once 'auth_user.php';
require_once 'db.php';

$role = $_SESSION['role'] ?? '';
$is_admin = ($role === 'admin');

$stmt = $pdo->prepare("
    SELECT id, title, category, content
    FROM spiels
    WHERE is_active = 1
    ORDER BY category ASC, id ASC
");
$stmt->execute();
$spiels = $stmt->fetchAll();

$grouped_spiels = [];
foreach ($spiels as $spiel) {
    $grouped_spiels[$spiel['category']][] = $spiel;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Mga Spiel</title>
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
            align-items: center;
            justify-content: space-between;
            gap: 16px;
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
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 20px;
        }

        .page-header {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 24px;
        }

        .page-header h1 {
            margin: 0 0 10px;
            font-size: 28px;
        }

        .page-header p {
            margin: 0;
            color: #666;
            line-height: 1.6;
        }

        .admin-banner {
            margin-top: 16px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #eef4ff;
            border-left: 5px solid #2c6bed;
            color: #2b4c7e;
        }

        .category-block {
            margin-bottom: 28px;
        }

        .category-title {
            margin: 0 0 14px;
            font-size: 22px;
            color: #1f2937;
        }

        .spiel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }

        .spiel-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .spiel-card h3 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .spiel-meta {
            font-size: 13px;
            color: #777;
            margin-bottom: 12px;
        }

        .spiel-content {
            white-space: pre-wrap;
            line-height: 1.6;
            color: #222;
            background: #f8fafc;
            padding: 14px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            min-height: 110px;
        }

        .spiel-actions {
            margin-top: 14px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-button {
            border: none;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
display: inline-flex;
align-items: center;
justify-content: center;
        }

        .copy-button {
            background: #2c6bed;
            color: #fff;
        }

        .copy-button:hover {
            background: #1f57c8;
        }

        .edit-button {
            background: #f59e0b;
            color: #fff;
        }

        .edit-button:hover {
            background: #d97706;
        }

        .empty-state {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            color: #666;
        }

        .copy-status {
            margin-top: 18px;
            font-size: 14px;
            color: #1d4ed8;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-title">PTB - Mga Spiel</div>
            <a class="back-link" href="navigation.php">← Balik sa Navigation</a>
        </div>

        <form class="logout-form" method="POST" action="logout.php">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </div>

    <div class="container">
        <div class="page-header">
            <h1>Mga Spiel</h1>
            <p>
                Dito makikita ang mga standard na reply na puwedeng kopyahin at i-send sa customer.
                Piliin lang ang tamang spiel para mas consistent at mas madali ang customer handling.
            </p>

            <?php if ($is_admin): ?>
                <div class="admin-banner">
                    Admin view ito. Sa susunod, dito rin puwedeng magdagdag, mag-edit, at mag-deactivate ng mga spiel.
                </div>
            <?php endif; ?>

            <div id="copyStatus" class="copy-status"></div>
        </div>

        <?php if (empty($grouped_spiels)): ?>
            <div class="empty-state">
                Wala pang active na spiel sa system.
            </div>
        <?php else: ?>
            <?php foreach ($grouped_spiels as $category => $items): ?>
                <div class="category-block">
                    <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>

                    <div class="spiel-grid">
                        <?php foreach ($items as $spiel): ?>
                            <div class="spiel-card">
                                <h3><?php echo htmlspecialchars($spiel['title']); ?></h3>
                                <div class="spiel-meta">
                                    Category: <?php echo htmlspecialchars($spiel['category']); ?>
                                </div>

                                <div class="spiel-content" id="spiel-content-<?php echo (int)$spiel['id']; ?>">
                                    <?php echo htmlspecialchars($spiel['content']); ?>
                                </div>

                                <div class="spiel-actions">
                                    <button
                                        type="button"
                                        class="action-button copy-button"
                                        onclick="copySpiel('spiel-content-<?php echo (int)$spiel['id']; ?>')"
                                    >
                                        Kopyahin
                                    </button>

<?php if ($is_admin): ?>
    <a
        href="edit_spiel.php?id=<?php echo (int)$spiel['id']; ?>"
        class="action-button edit-button"
        style="text-decoration:none; display:inline-block;"
    >
        I-edit
    </a>
<?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function copySpiel(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(function () {
                document.getElementById('copyStatus').innerText = 'Nakopya na ang spiel.';
            }).catch(function () {
                document.getElementById('copyStatus').innerText = 'Hindi nakopya ang spiel. Paki-try ulit.';
            });
        }
    </script>
</body>
</html>