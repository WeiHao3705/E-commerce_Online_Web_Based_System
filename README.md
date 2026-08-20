# E-commerce Online Web Based System

## Overview

This is a full-featured e-commerce web application built with PHP and MySQL. It simulates an online store where guests can browse products and registered members can shop, checkout, and manage their orders, while admins manage the catalog, orders, vouchers, and members through a dedicated back-office panel.

The project was built as a learning/demo system to practice implementing a realistic e-commerce workflow end-to-end — from product browsing to checkout to order fulfillment and admin analytics — rather than as a production storefront.

> ⚠️ **Payment Notice:** This project integrates with **Stripe in test mode only**. No real payments are processed and no real card or financial data is collected or stored. All checkout transactions must be completed using [Stripe's test card numbers](https://stripe.com/docs/testing) (e.g. `4242 4242 4242 4242`). Do **not** enter real card details anywhere in this application.

## Key Features

- **Guest & Member storefront** — product browsing, search, product details, reviews, and wishlist
- **Member accounts** — registration, login, email verification, 2FA (two-factor authentication), forgot/reset password, profile management
- **Shopping cart & checkout** — cart management, address selection, voucher/discount application, and Stripe-based (test mode) payment
- **Order management** — order confirmation, order history, order cancellation, refund requests, PDF receipt generation
- **Voucher system** — voucher creation, bulk import, QR code generation/preview for vouchers
- **Live chat** — member-to-admin chat support
- **Admin dashboard** — sales/order analytics, product & stock management, order management, review moderation, activity logs, and admin account management
- **Security features** — CAPTCHA verification for first-time visitors, session-based auth, "remember me" login, device fingerprinting

## Tech Stack

- **Backend:** PHP 7.4+ (MVC-style structure: controllers, views, services, repositories, DTOs)
- **Database:** MySQL (schema files under [sql](sql))
- **Dependency management:** [Composer](https://getcomposer.org/)
- **Key packages:**
  - [`stripe/stripe-php`](https://stripe.com/docs/api/php) — payment processing (test mode)
  - [`endroid/qr-code`](https://github.com/endroid/qr-code) — voucher QR code generation
  - [`setasign/fpdf`](http://www.fpdf.org/) — PDF receipt generation
  - [`robthree/twofactorauth`](https://github.com/RobThree/TwoFactorAuth) — admin/member 2FA

## Prerequisites

- **PHP 7.4 or higher** (included with XAMPP)
- **MySQL** (included with XAMPP)
- **Composer** — PHP dependency manager
- **XAMPP** (or similar local server environment)
- **Internet connection** (for downloading Composer packages)

## Setup Guide

### 1. Install Composer

- Download from [getcomposer.org/download](https://getcomposer.org/download/) and run the installer (`Composer-Setup.exe` on Windows). Point it to `C:\xampp\php\php.exe` if it isn't auto-detected.
- Verify with:
  ```bash
  composer --version
  ```

### 2. Install project dependencies

From the project root:

```bash
composer install
```

This reads `composer.json` and installs `stripe/stripe-php`, `endroid/qr-code`, `setasign/fpdf`, and `robthree/twofactorauth` into `vendor/`. See [INSTALL_COMMANDS.md](INSTALL_COMMANDS.md) for copy-paste commands and troubleshooting.

### 3. Set up the database

- Start MySQL/Apache via XAMPP.
- Create a database named `ecommerce_db`.
- Import the schema files from the [sql](sql) directory (order/user/product/voucher tables, etc.).
- Database credentials are configured in [web/database/connection.php](web/database/connection.php) (defaults to `root` with no password, matching a stock XAMPP install).

### 4. Configure environment variables

Create a `.env` file in the project root (this file is git-ignored) with your **Stripe test keys**:

```env
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_CURRENCY=myr
```

Only use `pk_test_...` / `sk_test_...` keys from your Stripe Dashboard's **test mode** — never live keys. See [web/config/stripe_config.php](web/config/stripe_config.php).

### 5. Run the app

Place the project in `C:\xampp\htdocs\`, start Apache/MySQL in XAMPP, then visit:

```
http://localhost/E-commerce_Online_Web_Based_System/
```

## Project Structure

```
E-commerce_Online_Web_Based_System/
├── index.php               # Front controller / entry point
├── helpers.php              # Shared helper functions
├── composer.json / composer.lock
├── sql/                     # Database schema files
├── vendor/                  # Installed Composer packages
└── web/
    ├── controller/          # Request handlers (Member, Product, Cart, Voucher, Admin, ...)
    ├── service/              # Business logic services (e.g. EmailService)
    ├── repository/           # Data access layer
    ├── DTO/                  # Data transfer objects
    ├── config/               # Stripe / Google Maps configuration
    ├── database/             # DB connection
    └── views/
        ├── guest/            # Public storefront
        ├── member/            # Member dashboard & profile
        ├── product/           # Product listing & details
        ├── Cart_Order/        # Cart, checkout, payment, receipts
        ├── voucher_management/# Voucher admin & QR preview
        ├── member_management/ # Member admin
        ├── security/          # Login, register, 2FA, CAPTCHA
        ├── chat/               # Live chat
        └── admin/              # Admin dashboard, analytics, product/order management
```

## Troubleshooting

For Composer-related issues (missing binary, PHP version errors, memory limits, network errors), see the **Troubleshooting** section in [INSTALL_COMMANDS.md](INSTALL_COMMANDS.md).

## Additional Resources

- **Composer Documentation:** https://getcomposer.org/doc/
- **Stripe Testing Documentation:** https://stripe.com/docs/testing
- **endroid/qr-code Documentation:** https://github.com/endroid/qr-code
- **FPDF Documentation:** http://www.fpdf.org/
- **PHP Manual:** https://www.php.net/manual/
