<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // change to true once properly on HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}
?>