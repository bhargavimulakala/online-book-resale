// ============================================================
// main.js — BookResale Frontend Logic
// ============================================================

// ---- Toast Notification ----
function showToast(message, type = 'info', duration = 3500) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill' };
    const colors = { success: '#10b981', error: '#ef4444', info: '#3b82f6', warning: '#f59e0b' };
    const id = 'toast_' + Date.now();
    const html = `<div id="${id}" class="toast align-items-center show border-0 mb-2" role="alert" style="background:rgba(15,10,30,0.95);border:1px solid rgba(212,175,55,0.3)!important;backdrop-filter:blur(20px);border-radius:12px;min-width:280px;">
        <div class="d-flex align-items-center p-3 gap-2">
            <i class="bi ${icons[type] || icons.info}" style="color:${colors[type] || colors.info};font-size:1.2rem;flex-shrink:0;"></i>
            <div class="flex-grow-1 text-white small fw-500">${message}</div>
            <button type="button" class="btn-close btn-close-white btn-sm ms-2" onclick="this.closest('[id^=toast_]').remove()"></button>
        </div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    setTimeout(() => document.getElementById(id)?.remove(), duration);
}

// ---- Cart Actions ----
function addToCart(bookId, qty = 1, callback = null) {
    fetch('/online-book-resale/ajax/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=add&book_id=${bookId}&qty=${qty}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.redirect) { window.location.href = data.redirect; return; }
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) {
            // Update cart badge
            const badges = document.querySelectorAll('.cart-count-badge');
            badges.forEach(b => { b.textContent = data.cart_count; b.style.display = data.cart_count > 0 ? 'flex' : 'none'; });
            if (callback) callback(data);
        }
    })
    .catch(() => showToast('Network error. Please try again.', 'error'));
}

function removeFromCart(cartId, callback = null) {
    fetch('/online-book-resale/ajax/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=remove&cart_id=${cartId}`
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message, data.success ? 'info' : 'error');
        if (data.success) {
            const badges = document.querySelectorAll('.cart-count-badge');
            badges.forEach(b => { b.textContent = data.cart_count; b.style.display = data.cart_count > 0 ? 'flex' : 'none'; });
            if (callback) callback(data);
        }
    });
}

function updateCartQty(cartId, qty, totalEl = null) {
    fetch('/online-book-resale/ajax/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update&cart_id=${cartId}&qty=${qty}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (totalEl) totalEl.textContent = '₹' + parseFloat(data.item_total).toFixed(2);
            const grandEl = document.getElementById('cartGrandTotal');
            if (grandEl) grandEl.textContent = '₹' + parseFloat(data.grand_total).toFixed(2);
        }
    });
}

// ---- Wishlist ----
function toggleWishlist(bookId, btn = null) {
    fetch('/online-book-resale/ajax/wishlist_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `book_id=${bookId}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.redirect) { window.location.href = data.redirect; return; }
        showToast(data.message, data.success ? (data.added ? 'success' : 'info') : 'error');
        if (data.success && btn) {
            const icon = btn.querySelector('i');
            if (icon) {
                icon.className = data.added ? 'bi bi-heart-fill' : 'bi bi-heart';
                icon.style.color = data.added ? '#ef4444' : '';
            }
            btn.classList.toggle('active', data.added);
        }
    })
    .catch(() => showToast('Network error.', 'error'));
}

// ---- Coupon Apply ----
document.addEventListener('DOMContentLoaded', function () {
    const applyCouponBtn = document.getElementById('applyCouponBtn');
    if (applyCouponBtn) {
        applyCouponBtn.addEventListener('click', function () {
            const code = document.getElementById('couponInput')?.value?.trim();
            if (!code) { showToast('Please enter a coupon code.', 'warning'); return; }
            fetch('/online-book-resale/ajax/cart_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=apply_coupon&code=${encodeURIComponent(code)}`
            })
            .then(r => r.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => location.reload(), 1000);
            });
        });
    }

    // ---- Live Search Suggestions ----
    const searchInputs = document.querySelectorAll('.live-search-input');
    searchInputs.forEach(input => {
        let debounce;
        const dropdown = document.createElement('div');
        dropdown.className = 'search-suggestions';
        dropdown.style.cssText = 'position:absolute;top:100%;left:0;right:0;z-index:999;background:rgba(15,10,30,0.97);border:1px solid rgba(212,175,55,0.3);border-radius:12px;backdrop-filter:blur(20px);display:none;max-height:280px;overflow-y:auto;';
        input.parentElement.style.position = 'relative';
        input.parentElement.appendChild(dropdown);
        input.addEventListener('input', function () {
            clearTimeout(debounce);
            const q = this.value.trim();
            if (q.length < 2) { dropdown.style.display = 'none'; return; }
            debounce = setTimeout(() => {
                fetch(`/online-book-resale/ajax/search_suggest.php?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.results?.length) { dropdown.style.display = 'none'; return; }
                    dropdown.innerHTML = data.results.map(r => `<a href="/online-book-resale/pages/book_details.php?id=${r.id}" class="d-block px-3 py-2 text-white text-decoration-none" style="border-bottom:1px solid rgba(255,255,255,0.05);font-size:0.9rem;" onmouseover="this.style.background='rgba(212,175,55,0.1)'" onmouseout="this.style.background=''">${r.title} <small class="text-muted">by ${r.author}</small></a>`).join('');
                    dropdown.style.display = 'block';
                });
            }, 300);
        });
        document.addEventListener('click', e => { if (!input.contains(e.target)) dropdown.style.display = 'none'; });
    });

    // ---- Animate on scroll ----
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } });
    }, { threshold: 0.1 });
    document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));

    // ---- Gallery Image Switch ----
    window.switchGalleryImage = function (src, thumb) {
        const main = document.getElementById('galleryMain');
        if (main) { main.src = src; main.style.opacity = '0'; setTimeout(() => main.style.opacity = '1', 50); }
        document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
        if (thumb) thumb.classList.add('active');
    };

    // ---- Star Rating Input ----
    const starInputs = document.querySelectorAll('.star-rating-input');
    starInputs.forEach(container => {
        const stars = container.querySelectorAll('i');
        const ratingInput = document.getElementById('ratingValue');
        stars.forEach((star, idx) => {
            star.style.cursor = 'pointer';
            star.addEventListener('mouseover', () => {
                stars.forEach((s, i) => { s.className = i <= idx ? 'bi bi-star-fill text-warning' : 'bi bi-star text-muted'; });
            });
            star.addEventListener('mouseout', () => {
                const current = parseInt(ratingInput?.value || 5);
                stars.forEach((s, i) => { s.className = i < current ? 'bi bi-star-fill text-warning' : 'bi bi-star text-muted'; });
            });
            star.addEventListener('click', () => {
                if (ratingInput) ratingInput.value = idx + 1;
                stars.forEach((s, i) => { s.className = i <= idx ? 'bi bi-star-fill text-warning' : 'bi bi-star text-muted'; });
            });
        });
    });

    // ---- Sidebar Toggle (Admin) ----
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('sidebar-hidden'));
    }

    // ---- Image Preview for File Inputs ----
    document.querySelectorAll('input[type="file"][data-preview]').forEach(input => {
        input.addEventListener('change', function () {
            const container = document.getElementById(this.dataset.preview);
            if (!container) return;
            Array.from(this.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width:80px;height:90px;object-fit:cover;border-radius:8px;border:2px solid rgba(212,175,55,0.4);';
                    container.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    });

    // ---- Bootstrap form validation ----
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', e => {
            if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
            form.classList.add('was-validated');
        });
    });
});
