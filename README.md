# SmartCart — Premium E-Commerce Management System

A complete, production-structured e-commerce management system built with **PHP, MySQL, HTML5, CSS3, Bootstrap 5, JavaScript (AJAX), and Chart.js** — featuring a **Dark Luxury + Clean Modern** design, three role-based panels (Customer / Admin / Staff), and a full shopping-to-fulfillment workflow.

---

## 1. Requirements

- XAMPP (PHP 8.0+ and MySQL/MariaDB) — https://www.apachefriends.org
- A modern browser
- No internet connection required to run it locally (Bootstrap/Chart.js/fonts load from CDN, so keep internet on for full styling — see note in Section 7)

---

## 2. Installation — Step by Step

### Step 1: Copy the project
Extract this project folder and place it inside your XAMPP `htdocs` directory, e.g.:

```
C:\xampp\htdocs\smartcart\      (Windows)
/Applications/XAMPP/htdocs/smartcart/   (Mac)
/opt/lampp/htdocs/smartcart/    (Linux)
```

### Step 2: Start Apache & MySQL
Open the XAMPP Control Panel and start **Apache** and **MySQL**.

### Step 3: Create the database
1. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
2. Click **New** → create a database named `smartcart_db` (or skip this — the SQL file creates it automatically)

### Step 4: Import the SQL file
1. In phpMyAdmin, select the `smartcart_db` database
2. Go to the **Import** tab
3. Choose the file `database/smartcart_db.sql`
4. Click **Go**

This creates all 21+ tables (users, products, orders, inventory, coupons, reviews, notifications, etc.) and loads demo data (products, categories, sample orders, demo accounts).

### Step 5: Configure the database connection
Open `config/database.php` and confirm these match your XAMPP setup (defaults work for a standard XAMPP install):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'smartcart_db');
define('DB_USER', 'root');
define('DB_PASS', '');           // default XAMPP MySQL has no password
```

Also confirm `BASE_URL` matches your folder name:
```php
define('BASE_URL', '/smartcart/');
```
If you placed the project in a different folder name, update this to match (e.g. `/my-project/`).

### Step 6: Run the project
Visit:
```
http://localhost/smartcart/
```

That's it — the storefront should load with the full Dark Luxury homepage.

---

## 3. Demo Accounts

All demo accounts use the password: **`Password@123`**

| Role     | Email                  | Password       |
|----------|-------------------------|----------------|
| Admin    | admin@smartcart.com     | Password@123   |
| Staff    | staff@smartcart.com     | Password@123   |
| Customer | nimal@example.com       | Password@123   |
| Customer | kamala@example.com      | Password@123   |

Login for all roles happens at `/auth/login.php` — the system automatically redirects each role to its correct dashboard (Admin Dashboard / Staff Dashboard / Customer Dashboard).

You can also register a brand-new customer account at `/auth/register.php`.

---

## 4. Folder Structure

```
smartcart/
├── admin/              Admin panel (dashboard, products, categories, brands,
│                        inventory, orders, customers, coupons, reviews,
│                        notifications, reports w/ Chart.js)
├── staff/               Staff panel (dashboard, assigned orders, inventory)
├── customer/             Customer panel (dashboard, profile, addresses,
│                        cart, checkout, orders, tracking, wishlist, notifications)
├── products/            Public shop listing, product details, search
├── auth/                Login, register, logout, forgot/reset password
├── ajax/                AJAX endpoints (cart, wishlist, coupons, search, quick view)
├── includes/             Shared PHP: db bootstrap, auth, functions, header/footer/navbar
├── config/               Database configuration
├── assets/               CSS, JS, images (SVG placeholders for products/hero)
├── uploads/products/     Uploaded product images land here
├── database/             smartcart_db.sql — full schema + demo data
└── index.php             Homepage
```

---

## 5. Core Features Implemented

**Customer**
- Registration/login with hashed passwords, session security
- Home page: hero, categories, featured/new/best-selling products, flash sale
  with live countdown, recommendations, recently viewed, testimonials, newsletter
- Shop page: search, category/brand/price/rating/availability filters, sorting, pagination
- Product details: image gallery, specs, reviews (verified-purchase gated), related products, "customers also bought"
- Cart: AJAX add/update/remove, live totals
- Wishlist: AJAX toggle
- Multi-step checkout: address (saved or new), delivery method, simulated payment (COD / card), coupon codes, order confirmation
- Order history, order details, order cancellation, order tracking timeline
- Notifications center
- Profile & address management

**Admin**
- Dashboard with KPI cards + Chart.js: sales trend, order status breakdown, category sales, top products
- Product CRUD with multi-image upload, stock initialization
- Category & Brand CRUD
- Inventory management with manual stock adjustment + full stock history log
- Order management: status workflow, staff assignment, cancellation with stock restore
- Customer management: view, ban/unban, order history per customer
- Coupon CRUD (percentage/fixed, usage limits, min order, expiry)
- Review moderation (approve/hide/delete)
- Notification broadcast system
- Reports: Sales (date range), Products, Customers, Orders — all with Chart.js visualizations

**Staff**
- Dashboard with assignment stats
- Assigned order management with status updates
- Inventory view with quick stock adjustment

**Security**
- Prepared statements (PDO) everywhere — no raw SQL concatenation
- Password hashing via `password_hash()` / `password_verify()`
- CSRF tokens on every state-changing form and AJAX call
- Output escaping via `e()` (htmlspecialchars) throughout
- Session hardening (httponly cookies, session regeneration on login)
- Role-based access control (`require_role()`) guarding every panel page
- Secure file uploads (MIME-type sniffing, size limits, randomized filenames)
- `.htaccess` protecting `/config` and `/database` folders from direct web access

---

## 6. Testing the Main Features

1. **Browse & shop**: Visit the homepage → Shop → filter/search → open a product → Add to Cart.
2. **Checkout**: Go to Cart → Checkout → pick/add an address → choose delivery + payment (try coupon `WELCOME10`) → Place Order.
3. **Track an order**: Customer Dashboard → Orders → View → Track Order.
4. **Admin workflow**: Log in as admin → Orders → open an order → change status (e.g. confirmed → processing → shipped → delivered) → the customer gets a notification.
5. **Inventory**: Admin → Inventory → adjust stock on a product → check Stock History.
6. **Staff workflow**: Assign an order to the Staff demo account from Admin → Orders → log in as staff → update its status.
7. **Reviews**: As a customer who has a *delivered* order for a product, open that product page and leave a review → check Admin → Reviews to moderate it.
8. **Reports**: Admin → Reports → Sales/Products/Customers/Orders tabs to see Chart.js analytics.

---

## 7. Troubleshooting

| Issue | Fix |
|---|---|
| "Service temporarily unavailable" / DB connection error | Make sure MySQL is running in XAMPP and `config/database.php` credentials match. |
| Blank page / 500 error | Set `display_errors` to `1` temporarily in `config/database.php` (`ini_set('display_errors','1')`) to see the actual PHP error, or check your Apache `error.log`. |
| Styling looks broken / no fonts or icons | Bootstrap, Bootstrap Icons, Google Fonts and Chart.js are loaded from CDN — make sure the machine running XAMPP has internet access. |
| Login fails with demo accounts | Re-import `database/smartcart_db.sql` — the demo password hashes are verified bcrypt hashes for `Password@123`. |
| Uploaded images don't show | Ensure the `uploads/products/` folder is writable (on Linux/Mac: `chmod -R 755 uploads/`). |
| "403 Access Denied" | You're logged in as the wrong role for that page — each panel (admin/staff/customer) is access-controlled by role. |
| Page not found at `/smartcart/...` | Confirm the project folder name matches `BASE_URL` in `config/database.php`. |

---

## 8. Notes on the Demo/Academic Scope

- **Payments are simulated.** No real payment gateway is integrated — card details are validated for format only, never stored (only the last 4 digits are kept on the order record), and orders paid by "card" are marked paid instantly for demo purposes.
- **Password reset** surfaces the reset link directly on-screen (since no SMTP/mail server is configured in a typical local XAMPP setup) instead of emailing it — clearly labeled as demo behavior on that page.
- Product images use generated SVG placeholders by default; upload your own JPG/PNG/WEBP images via the Admin → Products panel to replace them.

---

Built as a complete HNDIT-level final project — PHP · MySQL · Bootstrap 5 · Chart.js · AJAX.
