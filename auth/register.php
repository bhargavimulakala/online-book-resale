<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

if (isLoggedIn()) { header('Location: ' . SITE_URL . '/user/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $phone   = sanitize($_POST['phone'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$name || !$email || !$pass || !$confirm) {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
        $chk->bind_param('s', $email); $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $ins = $conn->prepare("INSERT INTO users (name,email,phone,password) VALUES (?,?,?,?)");
            $ins->bind_param('ssss', $name, $email, $phone, $hash);
            if ($ins->execute()) {
                $userId = $conn->insert_id;
                session_regenerate_id(true);
                $_SESSION['user_id']    = $userId;
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role']  = 'user';
                // Welcome notification
                $conn->query("INSERT INTO notifications (user_id,title,message,type) VALUES ($userId,'Welcome to BookResale!','Your account has been created successfully. Start browsing or listing books now!','success')");
                setFlash('success', 'Welcome! Your account has been created successfully.');
                header('Location: ' . SITE_URL . '/user/dashboard.php'); exit;
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5" style="background:linear-gradient(135deg,#080613 0%,#0f0a1e 50%,#1a1233 100%);">
    <div class="container">
        <div class="auth-card mx-auto" style="max-width:480px;">
            <div class="text-center mb-4">
                <a href="<?= SITE_URL ?>" class="text-decoration-none">
                    <i class="bi bi-book-half" style="font-size:2.5rem;color:#d4af37;"></i>
                    <h3 class="text-white fw-bold mt-2"><?= SITE_NAME ?></h3>
                </a>
                <p class="auth-subtitle">Create your free account and start saving on books!</p>
            </div>

            <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div><?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label"><i class="bi bi-person me-2 text-gold"></i>Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Your full name" required
                               value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : '' ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="bi bi-envelope me-2 text-gold"></i>Email Address *</label>
                        <input type="email" name="email" class="form-control" placeholder="your@email.com" required
                               value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label"><i class="bi bi-telephone me-2 text-gold"></i>Phone Number</label>
                        <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210"
                               value="<?= isset($_POST['phone']) ? sanitize($_POST['phone']) : '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-lock me-2 text-gold"></i>Password *</label>
                        <div class="input-group">
                            <input type="password" name="password" id="regPwd" class="form-control" placeholder="Min. 6 characters" required minlength="6">
                            <button class="btn btn-outline-gold" type="button" onclick="togglePass('regPwd',this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-lock-fill me-2 text-gold"></i>Confirm Password *</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="cfmPwd" class="form-control" placeholder="Re-enter password" required>
                            <button class="btn btn-outline-gold" type="button" onclick="togglePass('cfmPwd',this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                            <label class="form-check-label text-muted" for="agreeTerms">
                                I agree to the <a href="#" class="text-gold">Terms of Service</a> and <a href="#" class="text-gold">Privacy Policy</a> *
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-gold w-100 py-2 fw-bold fs-6">
                            <i class="bi bi-person-check me-2"></i>Create Account
                        </button>
                    </div>
                </div>
            </form>

            <div class="divider-text my-4"><span>Already have an account?</span></div>
            <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-outline-gold w-100 py-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </a>
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


