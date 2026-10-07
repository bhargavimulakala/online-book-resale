<!-- FOOTER -->
<footer class="site-footer mt-5">
    <div class="footer-top">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand">
                        <a href="<?= SITE_URL ?>" class="footer-logo">
                            <i class="bi bi-book-half"></i> <?= SITE_NAME ?>
                        </a>
                        <p class="mt-3">India's trusted platform for buying and selling new & used books at affordable prices for students and readers.</p>
                        <div class="social-links mt-3">
                            <a href="#"><i class="bi bi-facebook"></i></a>
                            <a href="#"><i class="bi bi-instagram"></i></a>
                            <a href="#"><i class="bi bi-twitter-x"></i></a>
                            <a href="#"><i class="bi bi-youtube"></i></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-heading">Quick Links</h6>
                    <ul class="footer-links">
                        <li><a href="<?= SITE_URL ?>">Home</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/books.php">Browse Books</a></li>
                        <li><a href="<?= SITE_URL ?>/seller/add_book.php">Sell a Book</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/about.php">About Us</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/contact.php">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6 class="footer-heading">Categories</h6>
                    <ul class="footer-links">
                        <li><a href="<?= SITE_URL ?>/pages/books.php?cat=2">Computer Science</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/books.php?cat=1">Engineering</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/books.php?cat=7">Competitive Exams</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/books.php?cat=8">Fiction</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/books.php?cat=6">School Books</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6 class="footer-heading">Contact Info</h6>
                    <ul class="footer-contact">
                        <li><i class="bi bi-geo-alt-fill"></i> Mumbai, Maharashtra, India</li>
                        <li><i class="bi bi-envelope-fill"></i> admin@bookresale.com</li>
                        <li><i class="bi bi-telephone-fill"></i> +91 98765 43210</li>
                        <li><i class="bi bi-clock-fill"></i> Mon-Sat: 9AM - 6PM</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="<?= SITE_URL ?>/pages/faq.php">FAQ</a> |
                    <a href="#">Privacy Policy</a> |
                    <a href="#">Terms of Use</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Toast Notification Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" style="z-index:9999;"></div>

<!-- Bootstrap 5.3 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
