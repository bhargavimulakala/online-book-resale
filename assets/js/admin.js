// ============================================================
// admin.js — Admin Panel JS: Charts, CRUD helpers, Table search
// ============================================================

// ---- Chart.js Global Defaults ----
if (typeof Chart !== 'undefined') {
    Chart.defaults.color = 'rgba(255,255,255,0.6)';
    Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.font.size = 12;
}

// ---- Revenue Bar Chart ----
function initRevenueChart(labels, data) {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Revenue (₹)',
                data,
                backgroundColor: 'rgba(212,175,55,0.25)',
                borderColor: '#d4af37',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => '₹' + parseFloat(ctx.raw).toLocaleString('en-IN') } } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' } },
                y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { callback: v => '₹' + v.toLocaleString('en-IN') } }
            }
        }
    });
}

// ---- Orders Doughnut Chart ----
function initOrdersChart(statusData) {
    const ctx = document.getElementById('ordersChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'Confirmed', 'Packed', 'Shipped', 'Delivered', 'Cancelled'],
            datasets: [{
                data: statusData,
                backgroundColor: ['#f59e0b','#3b82f6','#8b5cf6','#06b6d4','#10b981','#ef4444'],
                borderWidth: 2,
                borderColor: 'rgba(15,10,30,0.8)',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true } }
            },
            cutout: '65%'
        }
    });
}

// ---- Category Horizontal Bar Chart ----
function initCategoryChart(labels, data) {
    const ctx = document.getElementById('categoryChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Books',
                data,
                backgroundColor: [
                    'rgba(212,175,55,0.5)','rgba(124,58,237,0.5)','rgba(59,130,246,0.5)',
                    'rgba(16,185,129,0.5)','rgba(239,68,68,0.5)','rgba(245,158,11,0.5)',
                    'rgba(6,182,212,0.5)','rgba(236,72,153,0.5)'
                ],
                borderColor: [
                    '#d4af37','#7c3aed','#3b82f6','#10b981','#ef4444','#f59e0b','#06b6d4','#ec4899'
                ],
                borderWidth: 1.5,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.04)' } },
                y: { grid: { display: false } }
            }
        }
    });
}

// ---- Table Live Search ----
function initTableSearch(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('input', function () {
        const q = this.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

// ---- Admin: Toggle User Block/Unblock ----
function toggleUserBlock(userId, block) {
    const action = block ? 'block' : 'unblock';
    if (!confirm(`${block ? 'Block' : 'Unblock'} this user?`)) return;
    fetch('/online-book-resale/admin/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=${action}&id=${userId}`
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 900);
    });
}

// ---- Admin: Approve / Reject Book ----
function approveBook(bookId, action) {
    if (action === 'reject') {
        const reason = prompt('Enter rejection reason (optional):', 'Does not meet our listing guidelines.');
        if (reason === null) return; // cancelled
        fetch('/online-book-resale/admin/books.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=reject&id=${bookId}&reason=${encodeURIComponent(reason)}`
        })
        .then(r => r.json())
        .then(data => { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) setTimeout(() => location.reload(), 900); });
    } else {
        if (!confirm('Approve this book listing?')) return;
        fetch('/online-book-resale/admin/books.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=approve&id=${bookId}`
        })
        .then(r => r.json())
        .then(data => { showToast(data.message, data.success ? 'success' : 'error'); if (data.success) setTimeout(() => location.reload(), 900); });
    }
}

// ---- Admin: Update Order Status ----
function adminUpdateOrderStatus(orderId, newStatus, btn = null) {
    if (!confirm(`Update order status to "${newStatus}"?`)) return;
    fetch('/online-book-resale/admin/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_status&id=${orderId}&status=${newStatus}`
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success && btn) {
            document.querySelectorAll('#statusButtons .btn').forEach(b => b.classList.replace('btn-gold', 'btn-outline-gold'));
            btn.classList.replace('btn-outline-gold', 'btn-gold');
        }
    });
}

// ---- Admin: Delete Category ----
function deleteCategory(id) {
    if (!confirm('Delete this category? (Cannot delete if books exist under it)')) return;
    fetch('/online-book-resale/admin/categories.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete&id=${id}`
    })
    .then(r => r.json())
    .then(data => {
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) document.getElementById('catRow_' + id)?.remove();
    });
}

// ---- Sidebar Toggle ----
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    if (btn && sidebar) {
        btn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            document.querySelector('.admin-content')?.classList.toggle('expanded');
        });
    }
});

// ---- Expose showToast globally from main.js ----
if (typeof showToast === 'undefined') {
    window.showToast = function (msg, type = 'info') {
        const c = document.getElementById('toastContainer') || document.body;
        const d = document.createElement('div');
        d.textContent = msg;
        d.style.cssText = `position:fixed;bottom:20px;right:20px;background:#${type==='success'?'10b981':type==='error'?'ef4444':'d4af37'};color:white;padding:12px 20px;border-radius:8px;z-index:9999;font-weight:600;`;
        document.body.appendChild(d);
        setTimeout(() => d.remove(), 3000);
    };
}
