<?php
require_once 'session_bootstrap.php';
require_once 'db.php';

// Capture session values before destroying the session
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'Unknown';

// Log logout only if a valid logged-in user exists
if ($user_id !== null) {
    $details = "User logged out. Username: {$username}.";

    $log_stmt = $pdo->prepare("
        INSERT INTO audit_logs (user_id, action, target_type, target_id, details)
        VALUES (:user_id, :action, :target_type, :target_id, :details)
    ");
    $log_stmt->execute([
        ':user_id' => $user_id,
        ':action' => 'LOGOUT',
        ':target_type' => 'user',
        ':target_id' => $user_id,
        ':details' => $details
    ]);
}

// Clear session data
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session
session_destroy();

header("Location: index.php");
exit;
?>