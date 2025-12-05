<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, minimum-scale=0.5, user-scalable=yes">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - NGEAR' : 'NGEAR - Sports & Fitness Store'; ?></title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            width: 100%;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 1920px;
            margin: 0 auto;
            padding: 0 clamp(10px, 2vw, 40px);
        }

        /* Responsive container adjustments */
        @media (min-width: 320px) {
            .container {
                padding: 0 15px;
            }
        }

        @media (min-width: 480px) {
            .container {
                padding: 0 20px;
            }
        }

        @media (min-width: 768px) {
            .container {
                padding: 0 30px;
            }
        }

        @media (min-width: 1024px) {
            .container {
                padding: 0 40px;
            }
        }

        @media (min-width: 1440px) {
            .container {
                padding: 0 60px;
            }
        }

        @media (min-width: 1920px) {
            .container {
                padding: 0 80px;
            }
        }

        /* Prevent horizontal scroll */
        img,
        video,
        iframe {
            max-width: 100%;
            height: auto;
        }

        /* Global responsive utilities */
        .responsive-img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        /* Flexible text sizes */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        p,
        span,
        div,
        a,
        li {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* Responsive tables */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        @media (max-width: 768px) {
            table {
                font-size: 14px;
            }

            /* Make tables scrollable on mobile */
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Responsive buttons */
        button,
        .btn,
        a.btn {
            white-space: nowrap;
            min-height: 44px;
            /* Touch target size */
            min-width: 44px;
        }

        /* Form elements responsive */
        input,
        textarea,
        select {
            max-width: 100%;
            box-sizing: border-box;
        }

        /* Flexible containers */
        .flex-container {
            display: flex;
            flex-wrap: wrap;
        }

        /* Grid responsive */
        .grid-responsive {
            display: grid;
            gap: 1rem;
            grid-template-columns: 1fr;
        }

        @media (min-width: 480px) {
            .grid-responsive {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 768px) {
            .grid-responsive {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .grid-responsive {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        /* Support for browser zoom */
        @media (min-resolution: 2dppx) {
            /* High DPI displays */
        }

        /* Landscape orientation adjustments */
        @media (orientation: landscape) and (max-height: 500px) {
            .container {
                padding: 0 15px;
            }
        }

        /* Print styles */
        @media print {

            .navbar,
            .menu-toggle,
            .cart-icon {
                display: none !important;
            }

            body {
                overflow: visible !important;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    </style>
</head>

<body></body>