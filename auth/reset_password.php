<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

$token = sanitize($_GET['token'] ?? '');
$error = '';
$done  = false;

if (!$token) { header('Location: ' . SITE_URL . '/auth/forgot_password.php'); exit; }

// Validate token
$stmt = $conn->prepare("SELECT * FROM users WHERE reset_token=? AND reset_expires > NOW() LIMIT 1");
$stmt->bind_param('s', $token); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    $error = 'This password reset link is invalid or has expired. Please request a new one.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$pass || strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $upd = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
        $upd->bind_param('si', $hash, $user['id']); $upd->execute();
        $done = true;
        setFlash('success', 'Password changed successfully! Please login with your new password.');
    }
}

$pageTitle = 'Reset Password';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background:linear-gradient(135deg,#080613,#0f0a1e,#1a1233);">
    <div class="container">
        <div class="auth-card mx-auto" style="max-width:420px;">
            <div class="text-center mb-4">
                <div class="mb-3" style="width:70px;height:70px;background:rgba(212,175,55,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="bi bi-shield-lock fs-2 text-gold"></i>
                </div>
                <h2 class="auth-title">Reset Password</h2>
                <p class="auth-subtitle">Create a new secure password.</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?><?php if (!$user): ?><br><a href="<?= SITE_URL ?>/auth/forgot_password.php" class="alert-link">Request new reset link â†’</a><?php endif; ?></div><?php endif; ?>

            <?php if ($done): ?>
            <div class="alert alert-success text-center">
                <i class="bi bi-check-circle-fill fs-3 d-block mb-2"></i>
                <strong>Password Updated!</strong><br>You can now login with your new password.
            </div>
            <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-gold w-100">Go to Login</a>
            <?php elseif ($user && !$error): ?>
            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-lock me-2 text-gold"></i>New Password *</label>
                    <div class="input-group">
                        <input type="password" name="password" id="newPwd" class="form-control" placeholder="Min. 6 characters" required minlength="6">
                        <button class="btn btn-outline-gold" type="button" onclick="togglePass('newPwd',this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-lock-fill me-2 text-gold"></i>Confirm New Password *</label>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="cfmPwd" class="form-control" placeholder="Re-enter password" required>
                        <button class="btn btn-outline-gold" type="button" onclick="togglePass('cfmPwd',this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-gold w-100 py-2 fw-bold">
                    <i class="bi bi-check-circle me-2"></i>Update Password
                </button>
            </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="<?= SITE_URL ?>/auth/login.php" class="text-gold text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i>Back to Login
                </a>
            </div>
        </div>
    </div>
</div>
<script>function togglePass(id,btn){const i=document.getElementById(id);if(i.type==='password'){i.type='text';btn.innerHTML='<i class="bi bi-eye-slash"></i>';}else{i.type='password';btn.innerHTML='<i class="bi bi-eye"></i>';}}</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>


