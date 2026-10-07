<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

// Already logged in â€” redirect
if (isLoggedIn()) { header('Location: ' . SITE_URL . '/user/dashboard.php'); exit; }
if (isAdminLoggedIn()) { header('Location: ' . SITE_URL . '/admin/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$email || !$pass) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if ($user && password_verify($pass, $user['password'])) {
            if ($user['is_blocked']) { $error = 'Your account has been blocked. Contact support.'; }
            else {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = 'user';
                $redirect = sanitize($_GET['redirect'] ?? '');
                header('Location: ' . ($redirect ?: SITE_URL . '/user/dashboard.php')); exit;
            }
        } else { $error = 'Invalid email or password.'; }
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background:linear-gradient(135deg,#080613 0%,#0f0a1e 50%,#1a1233 100%);">
    <div class="container">
        <div class="auth-card mx-auto" style="max-width:440px;">
            <!-- Logo -->
            <div class="text-center mb-4">
                <a href="<?= SITE_URL ?>" class="text-decoration-none">
                    <i class="bi bi-book-half" style="font-size:2.5rem;color:#d4af37;"></i>
                    <h3 class="text-white fw-bold mt-2"><?= SITE_NAME ?></h3>
                </a>
                <p class="auth-subtitle">Welcome back! Sign in to your account.</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div><?php endif; ?>
            <?= renderFlash() ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-envelope me-2 text-gold"></i>Email Address</label>
                    <input type="email" name="email" id="email" class="form-control"
                           placeholder="your@email.com" required autofocus
                           value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-lock me-2 text-gold"></i>Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="loginPwd" class="form-control" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" required>
                        <button class="btn btn-outline-gold" type="button" onclick="togglePass('loginPwd',this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label text-muted" for="rememberMe">Remember me</label>
                    </div>
                    <a href="<?= SITE_URL ?>/auth/forgot_password.php" class="text-gold text-decoration-none small">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-gold w-100 py-2 fw-bold fs-6">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>

            <div class="divider-text my-4"><span>New to BookResale?</span></div>

            <a href="<?= SITE_URL ?>/auth/register.php" class="btn btn-outline-gold w-100 py-2">
                <i class="bi bi-person-plus me-2"></i>Create Free Account
            </a>

            <p class="text-center text-muted small mt-4 mb-0">
                Are you an admin?
                <a href="<?= SITE_URL ?>/admin/login.php" class="text-gold">Admin Login â†’</a>
            </p>
        </div>
    </div>
</div>
<script>
function togglePass(id, btn) {
    const i = document.getElementById(id);
    if (i.type === 'password') { i.type = 'text'; btn.innerHTML = '<i class="bi bi-eye-slash"></i>'; }
    else { i.type = 'password'; btn.innerHTML = '<i class="bi bi-eye"></i>'; }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>


