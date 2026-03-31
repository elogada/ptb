<?php
require_once 'auth_user.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('403 Forbidden - Admin lang ang may access sa page na ito.');
}
?>