<?php
require_once 'session_bootstrap.php';
require_once 'db.php';

// If already logged in, skip login page
if (isset($_SESSION['user_id'])) {
    header("Location: navigation.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_username = trim($_POST['username'] ?? '');
    $input_password = trim($_POST['password'] ?? '');

    if ($input_username !== '' && $input_password !== '') {
        $hashed_password = md5($input_password);

        $stmt = $pdo->prepare("
            SELECT id, username, role
            FROM users
            WHERE username = :username
              AND password = :password
            LIMIT 1
        ");
        $stmt->execute([
            ':username' => $input_username,
            ':password' => $hashed_password
        ]);

        $user = $stmt->fetch();

        if ($user) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Audit log for successful login
            $details = "Successful login for username: {$user['username']}.";

            $log_stmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, target_type, target_id, details)
                VALUES (:user_id, :action, :target_type, :target_id, :details)
            ");
            $log_stmt->execute([
                ':user_id' => $user['id'],
                ':action' => 'LOGIN',
                ':target_type' => 'user',
                ':target_id' => $user['id'],
                ':details' => $details
            ]);

            header("Location: navigation.php");
            exit;
        } else {
            $error = "Invalid login details.";
        }
    } else {
        $error = "Invalid login details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PTB - Order Management Console</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            min-height: 100vh;
            background: url('title-person.png') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.30);
            z-index: 0;
        }

        .page-wrap {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.50);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 14px;
            padding: 32px 28px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
        }

        .login-title {
            margin: 0 0 8px;
            font-size: 24px;
            text-align: center;
            color: #111;
        }

        .login-subtitle {
            margin: 0 0 24px;
            text-align: center;
            color: #222;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: bold;
            color: #111;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            font-size: 15px;
            outline: none;
            background: rgba(255, 255, 255, 0.88);
            color: #111;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #2c6bed;
            box-shadow: 0 0 0 3px rgba(44, 107, 237, 0.15);
        }

        .login-button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #2c6bed;
            color: #fff;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .login-button:hover {
            background: #1f57c8;
        }

        .error-message {
            margin-bottom: 16px;
            padding: 10px 12px;
            border-radius: 8px;
            background: rgba(255, 227, 227, 0.92);
            color: #b00020;
            font-size: 14px;
            text-align: center;
        }

        .footer-note {
            margin-top: 18px;
            text-align: center;
            font-size: 12px;
            color: #222;
        }
    </style>
</head>
<body>
    <div class="page-wrap">
        <div class="login-card">
            <h1 class="login-title">PTB - Order Management Console</h1>
            <p class="login-subtitle">Please sign in to continue.</p>

            <?php if ($error !== ''): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="login-button">Sign In</button>
            </form>

            <div class="footer-note">
                Internal access only
            </div>
        </div>
    </div>
</body>
</html>