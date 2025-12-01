<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Shopping Cart</title>
    <link rel="stylesheet" href="../../css/checkout.css">
</head>
<body>

<?php
// ==========================================
// Temporary Dummy Address Logic (Replace later)
// ==========================================

// Simulate logged-in user ID
$user_id = 1;

// Example: simulate user has NO address
// Change to true if you want to test with address
$hasAddress = false;

// Dummy address sample (from DB later)
$address = [
    'address1' => '123 Sports Street',
    'address2' => 'Apt 5B',
    'city' => 'Kuala Lumpur',
    'postcode' => '50000',
    'state' => 'Wilayah Persekutuan'
];
?>

<div class="checkout-container">

    <h1>Checkout</h1>

    <!-- ============================= -->
    <?php include "../../general/_header.php"; ?>

    <div class="container">

        <h2>Order Summary</h2>

        <table>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>

            <tr>
                <td>Wireless Mouse</td>
                <td>1</td>
                <td>RM 29.90</td>
                <td>RM 29.90</td>
            </tr>

            <tr>
                <td>USB-C Cable</td>
                <td>2</td>
                <td>RM 15.50</td>
                <td>RM 31.00</td>
            </tr>
        </table>

        <h3>Grand Total: RM 60.90</h3>

        <a href="payment.php">
            <button class="btn btn-primary">Proceed to Payment</button>
        </a>
    </div>

</body>
</html>
