<?php

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function require_login(): void
{
    ensure_session_started();

    if (!isset($_SESSION['username'])) {
        header('Location: /Project/index.php');
        exit;
    }
}

function require_admin(): void
{
    require_login();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $_SESSION['auth_error'] = 'Access denied: admin only.';
        header('Location: /Project/pages/wellcome.php');
        exit;
    }
}
