<?php
// Simple flash message helper (session-based)
function set_flash_message($message, $type = 'success') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash_message() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!empty($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

function render_flash_message() {
    $flash = get_flash_message();
    if ($flash) {
        $class = $flash['type'] === 'error' ? 'alert-danger' : 'alert-success';
        echo "<div class='alert {$class}' role='alert'>" . htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') . "</div>";
    }
}
