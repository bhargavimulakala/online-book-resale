<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

$pageTitle = 'About Us';
$pageDesc  = 'Learn about BookResale â€” India\'s trusted platform for buying and selling new & used books at affordable prices.';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$totalBooks  = (int)$conn->query("SELECT COUNT(*) as c FROM books WHERE status='approved'")->fetch_assoc()['c'];
$totalUsers  = (int)$conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$totalOrders = (int)$conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
?>
<div class="container py-5">
    <!-- Hero Banner -->
    <div class="glass-card p-5 mb-5 text-center" style="background:linear-gradient(135deg,rgba(212,175,55,0.08),rgba(124,58,237,0.08));border:1px solid rgba(212,175,55,0.2);">
        <i class="bi bi-book-half" style="font-size:4rem;color:#d4af37;"></i>
        <h1 class="section-title mt-3 d-inline-block">About <span class="text-gold">BookResale</span></h1>
        <p class="text-muted fs-5 mt-3 mb-0" style="max-width:700px;margin:0 auto;">
            India's trusted platform connecting book lovers â€” buyers and sellers â€” for affordable access to knowledge.
        </p>
    </div>

    <!-- Mission -->
    <div class="row g-5 align-items-center mb-5">
        <div class="col-lg-6">
            <h2 class="text-white fw-bold mb-3">Our <span class="text-gold">Mission</span></h2>
            <p class="text-muted mb-3" style="line-height:1.9;">
                At BookResale, we believe every student deserves access to quality learning materials without financial strain.
                Our platform bridges the gap between those who have finished with their books and those who need them, creating
                a sustainable circular economy for knowledge.
            </p>
            <p class="text-muted mb-4" style="line-height:1.9;">
                Founded with the vision of making education affordable, we connect college students, teachers, and general
                readers across India to buy and sell textbooks, novels, and study materials at fair prices.
            </p>
            <div class="d-flex flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-gold fs-5"></i>
                    <span class="text-white">100% Secure Transactions</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-gold fs-5"></i>
                    <span class="text-white">Verified Sellers</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill text-gold fs-5"></i>
                    <span class="text-white">Eco-Friendly</span>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="row g-3">
                <?php $stats=[
                    ['bi-book-half','Books Listed',number_format($totalBooks).'+','gold'],
                    ['bi-people','Happy Users',number_format($totalUsers).'+','purple'],
                    ['bi-bag-check','Orders Placed',number_format($totalOrders).'+','green'],
                    ['bi-geo-alt','Cities Served','500+','red'],
                ]; foreach ($stats as $s): ?>
                <div class="col-6">
                    <div class="stat-card text-center">
                        <div class="stat-icon <?= $s[3] ?> mx-auto"><i class="bi <?= $s[0] ?>"></i></div>
                        <div class="stat-number mt-2"><?= $s[2] ?></div>
                        <div class="stat-label"><?= $s[1] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Why Choose Us -->
    <div class="mb-5">
        <h2 class="text-white fw-bold text-center mb-4">Why Choose <span class="text-gold">BookResale?</span></h2>
        <div class="row g-4">
            <?php $features=[
                ['bi-shield-check','Safe & Secure','All transactions are protected. Seller profiles are verified before listing books.','gold'],
                ['bi-currency-rupee','Best Prices','Save up to 80% on textbooks compared to buying new. Negotiate directly with sellers.','purple'],
                ['bi-lightning','Fast Listings','List your book in under 2 minutes. Reach thousands of buyers instantly.','green'],
                ['bi-star','Quality Assured','Admin-approved listings ensure only genuine books reach buyers.','red'],
                ['bi-headset','24/7 Support','Our support team is ready to help you with any queries or issues.','gold'],
                ['bi-recycle','Eco Friendly','Every used book sold saves trees. Join us in building a sustainable future.','purple'],
            ]; foreach ($features as $f): ?>
            <div class="col-md-6 col-lg-4 animate-on-scroll">
                <div class="glass-card p-4 h-100">
                    <div class="stat-icon <?= $f[3] ?> mb-3" style="width:52px;height:52px;font-size:1.4rem;">
                        <i class="bi <?= $f[0] ?>"></i>
                    </div>
                    <h5 class="text-white fw-bold mb-2"><?= $f[1] ?></h5>
                    <p class="text-muted small mb-0"><?= $f[2] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Team -->
    <div class="mb-5">
        <h2 class="text-white fw-bold text-center mb-4">Meet Our <span class="text-gold">Team</span></h2>
        <div class="row g-4 justify-content-center">
            <?php $team=[
                ['Rahul Mehta','Founder & CEO','IIT Bombay Alumni. Passionate about education and tech.'],
                ['Anita Sharma','CTO','Full-stack developer with 8+ years of experience.'],
                ['Karan Patel','Operations Head','Manages seller onboarding and quality assurance.'],
                ['Divya Nair','Marketing Lead','Drives growth and partnerships across India.'],
            ]; foreach ($team as $m): ?>
            <div class="col-6 col-md-3">
                <div class="glass-card p-4 text-center">
                    <i class="bi bi-person-circle" style="font-size:3.5rem;color:#d4af37;"></i>
                    <h6 class="text-white fw-bold mt-3 mb-1"><?= $m[0] ?></h6>
                    <small class="text-gold d-block mb-2"><?= $m[1] ?></small>
                    <p class="text-muted" style="font-size:0.78rem;"><?= $m[2] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CTA -->
    <div class="glass-card p-5 text-center" style="background:linear-gradient(135deg,rgba(212,175,55,0.06),rgba(124,58,237,0.06));">
        <h3 class="text-white fw-bold mb-3">Ready to Start?</h3>
        <p class="text-muted mb-4">Join thousands of students already buying and selling books on BookResale.</p>
        <div class="d-flex justify-content-center flex-wrap gap-3">
            <a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-gold btn-lg px-4"><i class="bi bi-search me-2"></i>Browse Books</a>
            <a href="<?= SITE_URL ?>/seller/add_book.php" class="btn btn-outline-gold btn-lg px-4"><i class="bi bi-plus-circle me-2"></i>Sell a Book</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

