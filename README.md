# E-commerce Online Web Based System

## Overview

This is an E-commerce Online Web Based System built with PHP. The project uses Composer for dependency management and includes packages like `endroid/qr-code` for QR code generation, `stripe/stripe-php` for payment processing, and `setasign/fpdf` for PDF generation.

## Prerequisites

Before installing, ensure you have:

- **PHP 7.4 or higher** (included with XAMPP)
- **Composer** - PHP dependency manager
- **XAMPP** (or similar local server environment)
- **Internet connection** (for downloading packages)

## Installation Guide

### Step 1: Install Composer

Composer is a dependency manager for PHP. You need to install it first before installing project dependencies.

#### For Windows (XAMPP Users)

1. **Download Composer:**
   - Visit: https://getcomposer.org/download/
   - Click on "Composer-Setup.exe" to download the Windows installer

2. **Run the Installer:**
   - Double-click the downloaded `Composer-Setup.exe` file
   - The installer will automatically detect your PHP installation
   - If it doesn't find PHP automatically, browse to your XAMPP PHP directory:
     ```
     C:\xampp\php\php.exe
     ```
   - Follow the installation wizard and complete the setup

3. **Verify Installation:**
   - Open Command Prompt or PowerShell
   - Run: `composer --version`
   - You should see the Composer version number if installed correctly

### Step 2: Install Project Dependencies

Once Composer is installed, you can install all project dependencies including `endroid/qr-code`.

1. **Open Command Prompt or PowerShell**

2. **Navigate to your project directory:**
   ```
   Example : cd C:\xampp\htdocs\E-commerce_Online_Web_Based_System
   ```

3. **Install dependencies:**
   ```
   composer install
   ```

   This command will:
   - Read the `composer.json` file
   - Download and install all required packages
   - Create a `vendor` folder with all dependencies
   - Install `endroid/qr-code` (^6.0)
   - Install `stripe/stripe-php` (^19.1)
   - Install `setasign/fpdf` (^1.8)
   - Install `robthree/twofactorauth` (^3.0) - For admin 2FA authentication

4. **Verify Installation:**
   - Check if `vendor` folder exists in your project root
   - The `endroid/qr-code` package will be located at: `vendor/endroid/qr-code/`
   - The `stripe/stripe-php` package will be located at: `vendor/stripe/stripe-php/`
   - The `setasign/fpdf` package will be located at: `vendor/setasign/fpdf/`
   - The `robthree/twofactorauth` package will be located at: `vendor/robthree/twofactorauth/`

## Project Structure

After installation, your project structure should include:

```
E-commerce_Online_Web_Based_System/
├── composer.json          # Dependency configuration
├── composer.lock          # Locked versions
├── vendor/                # Installed packages (auto-generated)
│   ├── autoload.php      # Autoloader file
│   ├── endroid/
│   │   └── qr-code/      # QR code library
│   ├── stripe/
│   │   └── stripe-php/   # Stripe payment library
│   ├── setasign/
│   │   └── fpdf/         # FPDF library for PDF generation
│   └── robthree/
│       └── twofactorauth/ # Two-factor authentication library
├── web/                   # Web application files
└── ...
```

## Using Installed Packages

### Using endroid/qr-code

After installation, you can use the QR code library in your PHP files:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Writer\PngWriter;

// Create QR code
$result = Builder::create()
    ->writer(new PngWriter())
    ->data('https://example.com')
    ->encoding(new Encoding('UTF-8'))
    ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
    ->size(300)
    ->margin(10)
    ->build();

// Save QR code to file
$result->saveToFile(__DIR__ . '/qr-code.png');
```

### Using stripe/stripe-php

After installation, you can use the Stripe payment library in your PHP files:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Stripe\Stripe;
use Stripe\PaymentIntent;

// Set your secret key (get it from Stripe Dashboard)
Stripe::setApiKey('sk_test_your_secret_key_here');

// Create a payment intent
try {
    $paymentIntent = PaymentIntent::create([
        'amount' => 2000, // Amount in cents ($20.00)
        'currency' => 'usd',
        'payment_method_types' => ['card'],
    ]);
    
    echo "Payment Intent created: " . $paymentIntent->id;
} catch (\Stripe\Exception\ApiErrorException $e) {
    echo "Error: " . $e->getMessage();
}
```

**Note:** Make sure to configure your Stripe API keys in your project's configuration file (e.g., `web/config/stripe_config.php`).

### Using setasign/fpdf

After installation, you can use the FPDF library to generate PDF files in your PHP files:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

// Create PDF instance
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// Set font
$pdf->SetFont('Arial', 'B', 16);

// Add content
$pdf->Cell(0, 10, 'Hello World', 0, 1, 'C');

// Output PDF
$pdf->Output('D', 'document.pdf'); // 'D' for download, 'I' for inline, 'F' for file
```

**Note:** FPDF is used in this project for generating activity log reports and other PDF documents. The library is automatically loaded via Composer autoload.

## Updating Dependencies

To update all dependencies to their latest compatible versions:

```bash
composer update
```

To update only a specific package:

```bash
composer update endroid/qr-code
```

or

```bash
composer update stripe/stripe-php
```

or

```bash
composer update setasign/fpdf
```

or

```bash
composer update robthree/twofactorauth
```

## Troubleshooting

### Composer Not Found
- **Issue:** `'composer' is not recognized as an internal or external command`
- **Solution:** 
  - Make sure Composer is installed and added to your system PATH
  - Restart your terminal/command prompt after installation
  - Try using: `php composer.phar install`

### PHP Version Error
- **Issue:** Composer requires PHP 7.4 or higher
- **Solution:** 
  - Check your PHP version: `php -v`
  - Update XAMPP if you have an older version

### Memory Limit Error
- **Issue:** `Fatal error: Allowed memory size exhausted`
- **Solution:**
  - Increase PHP memory limit in `php.ini`: `memory_limit = 512M`
  - Or run: `php -d memory_limit=512M composer.phar install`

### Network/Download Issues
- **Issue:** Timeout or connection errors during installation
- **Solution:**
  - Check your internet connection
  - Clear Composer cache: `composer clear-cache`
  - Try again: `composer install`

## Quick Reference

For a quick list of all installation commands that you can copy and paste, see the **INSTALL_COMMANDS.md** file in this directory.

## Additional Resources

- **Composer Documentation:** https://getcomposer.org/doc/
- **endroid/qr-code Documentation:** https://github.com/endroid/qr-code
- **Stripe PHP Documentation:** https://stripe.com/docs/api/php
- **FPDF Documentation:** http://www.fpdf.org/
- **PHP Manual:** https://www.php.net/manual/
