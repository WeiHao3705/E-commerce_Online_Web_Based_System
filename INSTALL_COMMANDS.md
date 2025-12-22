# Installation Commands - Copy & Paste

## Quick Installation (Copy-Paste All Commands)

### Step 1: Verify Composer Installation
```bash
composer --version
```

### Step 2: Navigate to Project Directory
```bash
cd C:\xampp\htdocs\E-commerce_Online_Web_Based_System
```

### Step 3: Install All Dependencies
```bash
composer install
```

This will install:
- `endroid/qr-code` (^6.0)
- `stripe/stripe-php` (^19.1)
- `setasign/fpdf` (^1.8)

### Step 4: Verify Installation
```bash
dir vendor
dir vendor\endroid\qr-code
dir vendor\stripe\stripe-php
dir vendor\setasign\fpdf
```

---

## Alternative: Install Individual Packages

### Install Only endroid/qr-code
```bash
composer require endroid/qr-code
```

### Install Only stripe/stripe-php
```bash
composer require stripe/stripe-php
```

### Install Only setasign/fpdf
```bash
composer require setasign/fpdf
```

---
