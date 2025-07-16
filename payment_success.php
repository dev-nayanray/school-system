<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    // SSLCommerz sends payment data via POST or GET depending on configuration
    $tran_id = $_REQUEST['tran_id'] ?? '';
    $status = $_REQUEST['status'] ?? '';
    $amount = $_REQUEST['amount'] ?? 0;

    if ($status === 'VALID' || $status === 'SUCCESS') {
        // Update payment status in database based on tran_id
        // This is a placeholder, actual implementation depends on how tran_id maps to fees
        // For example, you might store tran_id in student_fees table or a payment table

        echo "<h2>Payment Successful</h2>";
        echo "<p>Transaction ID: " . htmlspecialchars($tran_id) . "</p>";
        echo "<p>Amount Paid: " . htmlspecialchars($amount) . "</p>";
        echo "<p>Thank you for your payment.</p>";
    } else {
        echo "<h2>Payment Status: " . htmlspecialchars($status) . "</h2>";
        echo "<p>Payment was not successful. Please try again.</p>";
    }
} else {
    echo "<h2>Invalid Request</h2>";
}
?>
