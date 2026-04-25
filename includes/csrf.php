<?php

function ensure_csrf_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function csrf_token(): string
{
    ensure_csrf_session_started();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function csrf_verify(): void
{
    ensure_csrf_session_started();

    $token = $_POST['csrf_token'] ?? '';
    $valid = is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);

    if ($valid) {
        return;
    }

    http_response_code(400);
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($is_ajax) {
        echo 'Invalid CSRF token. Please refresh and try again.';
    } else {
        echo "<script>alert('Invalid form token. Please try again.'); window.location.href = document.referrer || '/Project/index.php';</script>";
    }
    exit;
}
