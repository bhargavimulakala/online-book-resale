<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
if (isAdminLoggedIn()) { header('Location: ' . SITE_URL . '/admin/dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM admins WHERE email=? LIMIT 1");
    $stmt->bind_param('s', $email); $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    if ($admin && password_verify($pass, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['user_role']  = 'admin';
        header('Location: ' . SITE_URL . '/admin/dashboard.php'); exit;
    } else { $error = 'Invalid admin credentials.'; }
}
$pageTitle = 'Admin Login';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-vh-100 d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#080613,#0f0a1e,#1a1233);">
    <div class="container">
        <div class="auth-card mx-auto" style="max-width:420px;">
            <div class="text-center mb-4">
                <div class="mb-3" style="width:70px;height:70px;background:rgba(212,175,55,0.15);border:2px solid rgba(212,175,55,0.3);border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="bi bi-shield-lock-fill text-gold fs-2"></i>
                </div>
                <h1 class="auth-title">Admin Panel</h1>
                <p class="auth-subtitle">BookResale Administration</p>
            </div>
            <?php if ($error): ?><div class="alert alert-danger"><?= sanitize($error) ?></div><?php endif; ?>
            <form method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-envelope me-2 text-gold"></i>Admin Email</label>
                    <input type="email" name="email" class="form-control" placeholder="admin@bookresale.com" required>
                </div>
                <div class="mb-4">
                    <label class="form-label"><i class="bi bi-lock me-2 text-gold"></i>Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="apwd" class="form-control" required>
                        <button class="btn btn-outline-gold" type="button" onclick="togglePass('apwd',this)"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button type="submit" class="btn btn-gold w-100 py-2 fw-bold"><i class="bi bi-box-arrow-in-right me-2"></i>Login to Admin Panel</button>
                <p class="text-center text-muted small mt-3">Demo: admin@bookresale.com / Admin@123</p>
            </form>
        </div>
    </div>
</div>
<script>function togglePass(id,btn){const i=document.getElementById(id);if(i.type==='password'){i.type='text';btn.innerHTML='<i class="bi bi-eye-slash"></i>';}else{i.type='password';btn.innerHTML='<i class="bi bi-eye"></i>';}}</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

