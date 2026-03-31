<?php
require_once 'session_bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('401 Unauthorized - Kailangan munang mag-sign in.');
}
?>