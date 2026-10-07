<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param('s', $email); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user) {
            $token = generateToken(32);
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $upd = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE email=?");
            $upd->bind_param('sss', $token, $expires, $email); $upd->execute();
            $resetLink = SITE_URL . '/auth/reset_password.php?token=' . $token;
            $message = "Reset link generated. In a real system this would be emailed.<br><small>Demo link: <a href='$resetLink' class='text-gold'>$resetLink</a></small>";
        } else {
            // Don't reveal if email exists (security best practice)
            $message = 'If this email is registered, you will receive a password reset link.';
        }
    }
}

$pageTitle = 'Forgot Password';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background:linear-gradient(135deg,#080613,#0f0a1e,#1a1233);">
    <div class="container">
        <div class="auth-card mx-auto" style="max-width:420px;">
            <div class="text-center mb-4">
                <div class="mb-3" style="width:70px;height:70px;background:rgba(212,175,55,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="bi bi-key fs-2 text-gold"></i>
                </div>
                <h2 class="auth-title">Forgot Password?</h2>
                <p class="auth-subtitle">Enter your email and we'll send a reset link.</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div><?php endif; ?>
            <?php if ($message): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $message ?></div><?php endif; ?>

            <?php if (!$message): ?>
            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-envelope me-2 text-gold"></i>Registered Email *</label>
                    <input type="email" name="email" class="form-control" placeholder="your@email.com" required
                           value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                </div>
                <button type="submit" class="btn btn-gold w-100 py-2 fw-bold">
                    <i class="bi bi-send me-2"></i>Send Reset Link
                </button>
            </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="<?= SITE_URL ?>/auth/login.php" class="text-gold text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i>Back to Login
                </a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>


