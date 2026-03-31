<?php
require_once 'auth_admin.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('405 Method Not Allowed');
}

$spiel_id = isset($_POST['spiel_id']) ? (int)$_POST['spiel_id'] : 0;
$current_status = isset($_POST['current_status']) ? (int)$_POST['current_status'] : -1;
$updated_by = $_SESSION['user_id'];

if ($spiel_id <= 0 || ($current_status !== 0 && $current_status !== 1)) {
    http_response_code(400);
    exit('400 Bad Request - Invalid request data.');
}

$new_status = ($current_status === 1) ? 0 : 1;

$stmt = $pdo->prepare("
    UPDATE spiels
    SET is_active = :new_status,
        updated_by = :updated_by
    WHERE id = :id
");
$stmt->execute([
    ':new_status' => $new_status,
    ':updated_by' => $updated_by,
    ':id' => $spiel_id
]);

header("Location: admin_spiels.php?success=toggled");
exit;