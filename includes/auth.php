<?php
// ============================================================
// Authentication & Session Guards
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// Check if admin is logged in
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && $_SESSION['user_role'] === 'admin';
}

// Require user login - redirect if not
function requireUserLogin(string $redirectTo = ''): void {
    if (!isLoggedIn()) {
        $ref = $redirectTo ?: $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/auth/login.php?redirect=' . urlencode($ref));
        exit;
    }
}

// Require admin login
function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

// Get current logged-in user data
function getCurrentUser(mysqli $conn): ?array {
    if (!isLoggedIn()) return null;
    $id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND is_blocked=0");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Get current admin data
function getCurrentAdmin(mysqli $conn): ?array {
    if (!isAdminLoggedIn()) return null;
    $id = (int)$_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
