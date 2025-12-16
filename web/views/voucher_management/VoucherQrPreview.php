<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic access check: only admins should access this page
if (!isset($_SESSION['user']) || $_SESSION['user']->role !== 'admin') {
    header('Location: ../../views/security/LoginForm.php');
    exit;
}

// Expect variables from controller: $voucherCode, $voucherId, $svgContent, $fileName
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher QR Preview - NGear</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link rel="stylesheet" href="../css/AllTables.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #FFF0F0 0%, #f8f4f4 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .qr-page-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }

        .qr-card {
            background: linear-gradient(135deg, #ffffff 0%, #fefefe 100%);
            border-radius: 1.25rem;
            box-shadow: 0 20px 60px rgba(255, 82, 59, 0.15), 
                        0 10px 25px rgba(0, 0, 0, 0.08);
            padding: 2.5rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
            border: 1px solid rgba(255, 82, 59, 0.1);
            position: relative;
            overflow: hidden;
        }

        .qr-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #FF523B 0%, #FF5252 100%);
        }

        .qr-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #1f2937;
            letter-spacing: -0.02em;
        }

        .qr-subtitle {
            font-size: 0.95rem;
            color: #6b7280;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .qr-subtitle strong {
            color: #FF523B;
            font-weight: 600;
            background: rgba(255, 82, 59, 0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 0.375rem;
        }

        .qr-code-wrapper {
            display: inline-flex;
            padding: 1.5rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
            border: 2px solid rgba(255, 82, 59, 0.15);
            margin-bottom: 1.75rem;
            box-shadow: 0 8px 20px rgba(255, 82, 59, 0.1),
                        inset 0 1px 0 rgba(255, 255, 255, 0.8);
            position: relative;
        }

        .qr-code-wrapper::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(135deg, #FF523B, #FF5252);
            border-radius: 1rem;
            z-index: -1;
            opacity: 0.1;
        }

        .qr-code-wrapper img {
            width: 280px;
            height: 280px;
            display: block;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .qr-code-text {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 2rem;
            padding: 0.75rem 1rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }

        .qr-code-text strong {
            color: #374151;
            font-weight: 600;
        }

        .qr-actions {
            display: flex;
            gap: 0.875rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .qr-btn-primary,
        .qr-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.625rem;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .qr-btn-primary {
            background: linear-gradient(135deg, #FF523B 0%, #FF5252 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(255, 82, 59, 0.3);
        }

        .qr-btn-primary:hover {
            background: linear-gradient(135deg, #e63e27 0%, #e04848 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 82, 59, 0.4);
        }

        .qr-btn-primary:active {
            transform: translateY(0);
        }

        .qr-btn-secondary {
            background: #ffffff;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .qr-btn-secondary:hover {
            background: #f9fafb;
            border-color: #FF523B;
            color: #FF523B;
            transform: translateY(-2px);
        }

        .qr-btn-secondary:active {
            transform: translateY(0);
        }

        .qr-btn-primary .material-symbols-outlined,
        .qr-btn-secondary .material-symbols-outlined {
            font-size: 20px;
        }

        /* Responsive design */
        @media (max-width: 640px) {
            .qr-card {
                padding: 2rem 1.5rem;
            }

            .qr-code-wrapper img {
                width: 240px;
                height: 240px;
            }

            .qr-actions {
                flex-direction: column;
            }

            .qr-btn-primary,
            .qr-btn-secondary {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="qr-page-container">
        <div class="qr-card">
            <h1 class="qr-title">Voucher QR Code</h1>
            <p class="qr-subtitle">
                Scan this QR code or download it as an image. It encodes the voucher code:
                <strong><?php echo htmlspecialchars($voucherCode, ENT_QUOTES); ?></strong>
            </p>

            <div class="qr-code-wrapper">
                <?php
                $base64 = base64_encode($svgContent);
                echo '<img src="data:image/png;base64,' . $base64 . '" alt="QR Code" />';
                ?>
            </div>

            <p class="qr-code-text">
                File name suggestion: <strong><?php echo htmlspecialchars($fileName, ENT_QUOTES); ?></strong>
            </p>

            <div class="qr-actions">
                <a
                    class="qr-btn-primary"
                    href="../controller/VoucherController.php?action=downloadVoucherQr&amp;voucher_id=<?php echo (int)$voucherId; ?>&amp;code=<?php echo urlencode($voucherCode); ?>">
                    <span class="material-symbols-outlined">download</span>
                    <span>Download QR</span>
                </a>

                <a
                    class="qr-btn-secondary"
                    href="../controller/VoucherController.php?action=showAll">
                    <span class="material-symbols-outlined">arrow_back</span>
                    <span>Back to All Vouchers</span>
                </a>
            </div>
        </div>
    </div>
</body>

</html>


