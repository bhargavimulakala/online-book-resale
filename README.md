# Online Book Resale and Purchase System

A complete full-stack web application for buying and selling new and used books, built for college students and general users.

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap 5.3 |
| Backend | PHP 8.x (Core PHP, no framework) |
| Database | MySQL 5.7+ / MariaDB |
| Server | XAMPP (Apache + MySQL) |
| Charts | Chart.js 4.4 |

## Folder Structure

```
online-book-resale/
├── admin/              ← Admin panel pages
│   ├── login.php
│   ├── dashboard.php   ← Stats + Charts
│   ├── users.php       ← Block/Unblock/Delete users
│   ├── books.php       ← Approve/Reject/Delete listings
│   ├── categories.php  ← Add/Edit/Delete categories
│   ├── orders.php      ← View & update order status
│   ├── payments.php    ← Transactions + Coupon manager
│   ├── reports.php     ← Revenue analytics
│   ├── messages.php    ← User messages viewer
│   ├── feedback.php    ← Reviews manager
│   └── logout.php
├── ajax/               ← AJAX handler endpoints
│   ├── cart_action.php
│   ├── wishlist_action.php
│   ├── message_action.php
│   ├── notification_action.php
│   └── search_suggest.php
├── assets/
│   ├── css/style.css   ← Master stylesheet (1100+ lines)
│   ├── js/main.js      ← Frontend JS
│   ├── js/admin.js     ← Admin JS + Chart.js helpers
│   └── uploads/        ← Uploaded images (books/, profiles/)
├── auth/               ← Authentication pages
│   ├── login.php
│   ├── register.php
│   ├── forgot_password.php
│   ├── reset_password.php
│   └── logout.php
├── config/
│   ├── config.php      ← Site config, constants
│   └── database.php    ← MySQLi connection
├── database/
│   └── online_book_resale.sql  ← Full schema + sample data
├── includes/           ← Shared PHP includes
│   ├── functions.php   ← All helper functions
│   ├── auth.php        ← Auth guards
│   ├── header.php
│   ├── navbar.php
│   ├── footer.php
│   └── sidebar.php     ← Admin sidebar
├── pages/              ← Public-facing pages
│   ├── books.php       ← Browse/filter/search books
│   ├── book_details.php
│   ├── checkout.php
│   ├── order_confirmation.php
│   ├── invoice.php     ← Print-ready invoice
│   ├── about.php
│   ├── contact.php
│   └── faq.php
├── seller/             ← Seller portal
│   ├── add_book.php
│   └── edit_book.php
├── user/               ← User dashboard pages
│   ├── dashboard.php
│   ├── profile.php
│   ├── orders.php
│   ├── cart.php
│   ├── wishlist.php
│   ├── mybooks.php     ← Seller's listings
│   ├── messages.php    ← Buyer-seller chat
│   └── notifications.php
└── index.php           ← Homepage
```

## Setup Instructions

### 1. XAMPP Setup
1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**
2. Copy the entire `online-book-resale` folder to `C:\xampp\htdocs\`

### 2. Database Import
1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click **New** → Database name: `online_book_resale` → Create
3. Click **Import** → Choose `database/online_book_resale.sql` → Go

### 3. Access the Site
| URL | Page |
|---|---|
| `http://localhost/online-book-resale/` | Homepage |
| `http://localhost/online-book-resale/auth/login.php` | User Login |
| `http://localhost/online-book-resale/auth/register.php` | Register |
| `http://localhost/online-book-resale/admin/login.php` | Admin Login |

## Demo Credentials

| Role | Email | Password |
|---|---|---|
| Admin | admin@bookresale.com | Admin@123 |
| User | aarav@example.com | *(use Test@1234 after updating hash)* |

> **Note:** The sample user passwords in the SQL are bcrypt-hashed with `password_hash('password')`. To test, register a new user through the website.

## Key Features

### User / Buyer
- Browse & search books with filters (category, condition, price range)
- Book detail page with image gallery, reviews, star ratings
- Add to cart / Buy Now / Add to Wishlist
- Checkout with COD, UPI, Credit/Debit card (demo)
- Order tracking with status updates
- Print-ready invoice download
- Buyer-seller messaging system
- Notifications for order updates

### Seller
- List books for sale with multiple images
- Edit listings (re-submitted for approval)
- View listing status (pending/approved/rejected/sold)
- Get notified when books are approved or rejected

### Admin
- Dashboard with Chart.js analytics (revenue bar chart, orders doughnut, category chart)
- Approve / Reject book listings with reason
- Block / Unblock / Delete users
- Manage categories (add/edit/delete)
- Update order status through the delivery pipeline
- Manage discount coupons (% or fixed, with expiry)
- View all payments and transactions
- Reports: top selling books, top sellers, category revenue
- View all user reviews and delete inappropriate ones
- Monitor buyer-seller messages

## Security
- All SQL via **prepared statements** (no raw queries with user input)
- Passwords hashed with **`password_hash()`** (BCRYPT)
- **Session regeneration** on login
- Input **sanitized** with `htmlspecialchars()`
- File upload **MIME-type** and extension validation
- HTTP-only session cookies
- Admin/User guard functions on every protected page

## Customization

### Change Site Name / URL
Edit `config/config.php`:
```php
define('SITE_NAME', 'BookResale');
define('SITE_URL', 'http://localhost/online-book-resale');
```

### Change Database Password
Edit `config/database.php`:
```php
define('DB_PASS', 'your_mysql_password');
```

---
*Developed as a Final Year MCA/BCA Project*
