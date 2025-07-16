<?php
session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /index.php');
        exit();
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION['role'] !== $role) {
        // Optionally redirect to unauthorized page or dashboard
        header('HTTP/1.1 403 Forbidden');
        echo "403 Forbidden - You do not have permission to access this page.";
        exit();
    }
}

function require_roles(array $roles) {
    require_login();
    if (!in_array($_SESSION['role'], $roles)) {
        header('HTTP/1.1 403 Forbidden');
        echo "403 Forbidden - You do not have permission to access this page.";
        exit();
    }
}
?>
