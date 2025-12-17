# Endroid QR Code Installation Guide

Quick guide for installing the `endroid/qr-code` package via Composer.

---

## Prerequisites

Before installing, make sure you have:

- **PHP 8.2 or higher** installed
- **Composer** installed and available in your system PATH

**Check your PHP version:**
```bash
php --version
```

**Check if Composer is installed:**
```bash
composer --version
```

**If Composer is not installed, download it from:**  
https://getcomposer.org/download/

---

## Installation Steps

### Step 1: Open Terminal
Open PowerShell or Command Prompt

### Step 2: Navigate to Your Project Directory
```powershell
cd c:\Users\user\Documents\GitHub\E-commerce_Online_Web_Based_System
```

### Step 3: Install the Package
Run this command:
```bash
composer require endroid/qr-code
```

### Step 4: Wait for Installation
You should see output like:
```
Using version ^6.0 for endroid/qr-code
./composer.json has been updated
Loading composer repositories with package information
Updating dependencies
Package operations: 2 installs, 0 updates, 0 removals
  - Installing bacon/bacon-qr-code (3.x.x)
  - Installing endroid/qr-code (6.0.9)
Writing lock file
Generating autoload files
```

### Step 5: Verify Installation
Check that the package is installed:
```bash
composer show endroid/qr-code
```

Check the files are there:
```powershell
dir vendor\endroid\qr-code
```

You should see folders like `assets/`, `src/` and files like `composer.json`, `LICENSE`, `README.md`

---

## Troubleshooting Common Issues

### Problem: Empty vendor/endroid/qr-code folder

**Solution:**
```powershell
# Remove the empty folder
Remove-Item -Path vendor\endroid -Recurse -Force

# Clear Composer cache
composer clear-cache

# Reinstall
composer install --prefer-dist --no-cache
```

---

### Problem: "requires php ^8.2 which is not satisfied"

**Solution:**  
Upgrade PHP to version 8.2 or higher

---

### Problem: "Class not found" errors

**Solution:**
```bash
# Regenerate autoload files
composer dump-autoload

# Or reinstall
composer install --prefer-dist
```

---

### Problem: Permission denied

**Solution:**  
Run PowerShell as Administrator

---

### Problem: Download fails

**Solution:**
```bash
# Clear cache and retry
composer clear-cache
composer install

# Or try with different settings
composer install --prefer-source
```

---

## Common Composer Commands

```bash
# Install package
composer require endroid/qr-code

# Update package
composer update endroid/qr-code

# Remove package
composer remove endroid/qr-code

# Show package info
composer show endroid/qr-code

# Clear cache
composer clear-cache

# Reinstall all packages
composer install --prefer-dist

# Regenerate autoload files
composer dump-autoload
```

---

## Additional Resources

- **Composer Website**: https://getcomposer.org/
- **Package Repository**: https://packagist.org/packages/endroid/qr-code
- **Official Documentation**: https://github.com/endroid/qr-code

---

*Last Updated: December 17, 2025*
