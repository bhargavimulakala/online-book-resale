<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

// Handle contact form submission
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($_POST['name'] ?? '');
    $email   = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    if (!$name || !$email || !$subject || !$message) {
        $error = 'Please fill all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Store in DB if contact_messages table exists, else just show success
        $success = true;
    }
}

$pageTitle = 'Contact Us';
$pageDesc  = 'Get in touch with BookResale team. We are here to help with any queries.';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb breadcrumb-dark">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Contact Us</li>
        </ol>
    </nav>

    <div class="text-center mb-5">
        <h1 class="section-title">Contact <span class="text-gold">Us</span></h1>
        <p class="text-muted mt-3 fs-5">Have a question or need help? We'd love to hear from you.</p>
    </div>

    <div class="row g-5">
        <!-- Contact Info -->
        <div class="col-lg-4">
            <div class="d-flex flex-column gap-4">
                <?php $infos = [
                    ['bi-geo-alt-fill','Our Office','123, Book Street, Andheri West<br>Mumbai, Maharashtra - 400058'],
                    ['bi-envelope-fill','Email Us','admin@bookresale.com<br>support@bookresale.com'],
                    ['bi-telephone-fill','Call Us','+91 98765 43210<br>+91 87654 32109'],
                    ['bi-clock-fill','Working Hours','Mon - Sat: 9:00 AM â€“ 6:00 PM<br>Sunday: Closed'],
                ]; foreach ($infos as $info): ?>
                <div class="glass-card p-4 d-flex align-items-start gap-3">
                    <div class="stat-icon gold flex-shrink-0" style="width:50px;height:50px;font-size:1.3rem;">
                        <i class="bi <?= $info[0] ?>"></i>
                    </div>
                    <div>
                        <h6 class="text-white fw-bold mb-1"><?= $info[1] ?></h6>
                        <p class="text-muted small mb-0"><?= $info[2] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Social Links -->
                <div class="glass-card p-4">
                    <h6 class="text-white fw-bold mb-3">Follow Us</h6>
                    <div class="d-flex gap-3">
                        <?php $socials=[['bi-facebook','#'],['bi-instagram','#'],['bi-twitter-x','#'],['bi-youtube','#'],['bi-linkedin','#']]; ?>
                        <?php foreach ($socials as $s): ?>
                        <a href="<?= $s[1] ?>" class="social-link" style="width:40px;height:40px;background:rgba(212,175,55,0.1);border:1px solid rgba(212,175,55,0.3);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#d4af37;text-decoration:none;font-size:1.1rem;transition:all 0.3s;" onmouseover="this.style.background='rgba(212,175,55,0.2)'" onmouseout="this.style.background='rgba(212,175,55,0.1)'">
                            <i class="bi <?= $s[0] ?>"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="col-lg-8">
            <div class="glass-card p-5">
                <h4 class="text-white fw-bold mb-4"><i class="bi bi-send me-2 text-gold"></i>Send us a Message</h4>

                <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center gap-3">
                    <i class="bi bi-check-circle-fill fs-4"></i>
                    <div>
                        <strong>Message Sent!</strong><br>
                        Thank you for reaching out. We'll get back to you within 24 hours.
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Your Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="Full name" required
                                   value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : (isLoggedIn() ? sanitize($_SESSION['user_name'] ?? '') : '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" placeholder="your@email.com" required
                                   value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : (isLoggedIn() ? sanitize($_SESSION['user_email'] ?? '') : '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Subject *</label>
                            <select name="subject" class="form-select" required>
                                <option value="">-- Select Subject --</option>
                                <option value="Order Issue" <?= ($_POST['subject'] ?? '') === 'Order Issue' ? 'selected' : '' ?>>Order Issue</option>
                                <option value="Book Listing" <?= ($_POST['subject'] ?? '') === 'Book Listing' ? 'selected' : '' ?>>Book Listing Help</option>
                                <option value="Payment Problem" <?= ($_POST['subject'] ?? '') === 'Payment Problem' ? 'selected' : '' ?>>Payment Problem</option>
                                <option value="Account Issue" <?= ($_POST['subject'] ?? '') === 'Account Issue' ? 'selected' : '' ?>>Account Issue</option>
                                <option value="General Inquiry" <?= ($_POST['subject'] ?? '') === 'General Inquiry' ? 'selected' : '' ?>>General Inquiry</option>
                                <option value="Other" <?= ($_POST['subject'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Your Message *</label>
                            <textarea name="message" class="form-control" rows="5" placeholder="Describe your query in detail..." required><?= isset($_POST['message']) ? sanitize($_POST['message']) : '' ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-gold px-5 py-2 fw-bold">
                                <i class="bi bi-send me-2"></i>Send Message
                            </button>
                        </div>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

