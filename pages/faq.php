<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

$pageTitle = 'FAQ - Frequently Asked Questions';
$pageDesc  = 'Find answers to common questions about buying and selling books on BookResale.';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$faqs = [
    'Buying Books' => [
        ['How do I buy a book?', 'Browse or search for the book you need, click "View Details", then "Add to Cart" or "Buy Now". Complete the checkout process by providing your shipping address and selecting a payment method.'],
        ['Are the books genuine?', 'Yes! All book listings go through admin review before being published. Sellers provide detailed condition descriptions and photos.'],
        ['Can I return a book?', 'Returns are handled between buyer and seller. You can contact the seller directly through our messaging system to discuss returns.'],
        ['What payment methods are accepted?', 'We accept Cash on Delivery (COD), UPI (Google Pay, PhonePe, Paytm), Credit Card, and Debit Card payments.'],
        ['How long will delivery take?', 'Delivery typically takes 3â€“7 business days depending on your location and the seller\'s location.'],
    ],
    'Selling Books' => [
        ['How do I list a book for sale?', 'Login to your account, click "Sell a Book" in the menu, fill in book details (title, author, condition, price), upload photos, and submit for admin approval.'],
        ['How long does approval take?', 'Admin approval usually takes 24â€“48 hours. You will receive a notification once your listing is approved or if any changes are needed.'],
        ['Can I negotiate the price?', 'Yes! When listing your book, you can mark it as "Negotiable" so buyers can contact you directly to discuss the price.'],
        ['What are the fees for selling?', 'BookResale is completely FREE for sellers. No listing fee, no commission. You keep 100% of your selling price.'],
        ['Can I edit my listing after approval?', 'Yes, you can edit your listing anytime from "My Listings". However, editing a live listing will require re-approval.'],
    ],
    'Account & Orders' => [
        ['How do I track my order?', 'Go to "My Orders" in your dashboard to see real-time order status with tracking updates from pending to delivered.'],
        ['Can I cancel an order?', 'You can cancel an order if its status is still "Pending" or "Confirmed". Go to My Orders â†’ Order Details â†’ Cancel Order.'],
        ['I forgot my password. What do I do?', 'Click "Forgot Password" on the login page, enter your registered email, and follow the reset link that will be displayed.'],
        ['How do I contact a seller?', 'On the book detail page, click "Contact Seller" to send a message. The seller will respond through our messaging system.'],
        ['Is my personal information safe?', 'Yes! We use HTTPS encryption, password hashing, and prepared SQL statements to keep your data secure at all times.'],
    ],
];
?>
<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb breadcrumb-dark">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item active">FAQ</li>
        </ol>
    </nav>

    <div class="text-center mb-5">
        <h1 class="section-title">Frequently Asked <span class="text-gold">Questions</span></h1>
        <p class="text-muted mt-3 fs-5">Everything you need to know about BookResale.</p>
    </div>

    <!-- Search -->
    <div class="glass-card p-4 mb-5 mx-auto" style="max-width:600px;">
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search text-gold"></i></span>
            <input type="text" class="form-control" id="faqSearch" placeholder="Search questions...">
        </div>
    </div>

    <!-- Category Tabs -->
    <ul class="nav nav-pills mb-4 justify-content-center gap-2" id="faqTabs">
        <?php $i = 0; foreach ($faqs as $cat => $qs): ?>
        <li class="nav-item">
            <button class="nav-link <?= $i===0?'active btn-gold':'btn-outline-gold' ?> btn" data-tab="<?= $i ?>" onclick="switchFaqTab(this,<?= $i ?>)">
                <?= $cat ?>
            </button>
        </li>
        <?php $i++; endforeach; ?>
    </ul>

    <!-- FAQ Accordions -->
    <?php $i = 0; foreach ($faqs as $cat => $qs): ?>
    <div class="faq-section <?= $i===0?'':'d-none' ?>" id="faqTab_<?= $i ?>">
        <div class="accordion" id="faqAccordion_<?= $i ?>">
            <?php foreach ($qs as $j => $qa): ?>
            <div class="accordion-item faq-item mb-3" style="background:rgba(255,255,255,0.04);border:1px solid rgba(212,175,55,0.15);border-radius:12px;overflow:hidden;">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#faq_<?= $i ?>_<?= $j ?>"
                            style="background:transparent;color:#fff;box-shadow:none;border-bottom:1px solid rgba(212,175,55,0.1);">
                        <i class="bi bi-question-circle me-2 text-gold"></i>
                        <?= $qa[0] ?>
                    </button>
                </h2>
                <div id="faq_<?= $i ?>_<?= $j ?>" class="accordion-collapse collapse">
                    <div class="accordion-body text-muted" style="background:rgba(255,255,255,0.02);">
                        <?= $qa[1] ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php $i++; endforeach; ?>

    <!-- Still have questions -->
    <div class="glass-card p-5 text-center mt-5">
        <i class="bi bi-chat-question fs-1 text-gold mb-3 d-block"></i>
        <h4 class="text-white fw-bold mb-2">Still have questions?</h4>
        <p class="text-muted mb-4">Our support team is ready to help you with any queries.</p>
        <a href="<?= SITE_URL ?>/pages/contact.php" class="btn btn-gold px-4"><i class="bi bi-envelope me-2"></i>Contact Support</a>
    </div>
</div>

<script>
function switchFaqTab(btn, idx) {
    document.querySelectorAll('.faq-section').forEach(s => s.classList.add('d-none'));
    document.getElementById('faqTab_' + idx).classList.remove('d-none');
    document.querySelectorAll('#faqTabs .nav-link').forEach(b => { b.classList.remove('active','btn-gold'); b.classList.add('btn-outline-gold'); });
    btn.classList.remove('btn-outline-gold'); btn.classList.add('active','btn-gold');
}

// FAQ Search
document.getElementById('faqSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    if (!q) {
        document.querySelectorAll('.faq-item').forEach(i => i.style.display='');
        return;
    }
    // Show all tabs
    document.querySelectorAll('.faq-section').forEach(s => s.classList.remove('d-none'));
    document.querySelectorAll('.faq-item').forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(q) ? '' : 'none';
    });
});
</script>

<style>
.accordion-button::after { filter: brightness(0) invert(0.8) sepia(1) saturate(4) hue-rotate(5deg); }
.accordion-button:not(.collapsed) { background: rgba(212,175,55,0.08) !important; color: #d4af37 !important; }
</style>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

