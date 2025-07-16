<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// This file will handle SSLCommerz payment initiation and callback handling

// SSLCommerz API credentials
define('SSLCOMMERZ_STORE_ID', 'nextz68782b83a32d9');
define('SSLCOMMERZ_STORE_PASSWORD', 'nextz68782b83a32d9@ssl');
define('SSLCOMMERZ_SANDBOX', true); // Set to false for live

function sslcommerz_api_url() {
    return SSLCOMMERZ_SANDBOX ? 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php' : 'https://securepay.sslcommerz.com/gwprocess/v4/api.php';
}

// Initiate payment request
function initiate_payment($post_data) {
    $url = sslcommerz_api_url();

    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($handle);

    if ($result === false) {
        return ['status' => 'fail', 'message' => curl_error($handle)];
    }

    curl_close($handle);
    return json_decode($result, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['student_fee_id']) || isset($_POST['fee_id']))) {
    $student_id = $_SESSION['user_id']; // Assuming logged in student

    // Fetch user email from database
    $stmt = $pdo->prepare('SELECT email, name FROM users WHERE id = ?');
    $stmt->execute([$student_id]);
    $user = $stmt->fetch();

    $user_email = $user['email'] ?? '';
    $user_name = $user['name'] ?? ($_SESSION['user_name'] ?? 'Student');
    $user_city = '';
    $user_phone = '0000000000';

    if (isset($_POST['student_fee_id'])) {
        $fee_id = (int)$_POST['student_fee_id'];

        // Fetch fee details for student fee
        $stmt = $pdo->prepare('SELECT sf.id as student_fee_id, f.amount FROM student_fees sf JOIN fees f ON sf.fee_id = f.id WHERE sf.id = ? AND sf.student_id = ?');
        $stmt->execute([$fee_id, $student_id]);
        $fee = $stmt->fetch();

        if (!$fee) {
            die('Invalid fee or access denied.');
        }

        $amount = $fee['amount'];
        $tran_id = 'student_fee_tran_' . uniqid();
    } else {
        $fee_id = (int)$_POST['fee_id'];

        // Fetch fee details for class fee
        $stmt = $pdo->prepare('SELECT id, amount FROM fees WHERE id = ?');
        $stmt->execute([$fee_id]);
        $fee = $stmt->fetch();

        if (!$fee) {
            die('Invalid fee or access denied.');
        }

        $amount = $fee['amount'];
        $tran_id = 'class_fee_tran_' . uniqid();
    }

    $post_data = [
        'store_id' => SSLCOMMERZ_STORE_ID,
        'store_passwd' => SSLCOMMERZ_STORE_PASSWORD,
        'total_amount' => $amount,
        'currency' => 'BDT',
        'tran_id' => $tran_id,
        'success_url' => 'payment_success.php',
        'fail_url' => 'payment_fail.php',
        'cancel_url' => 'payment_cancel.php',
        'cus_name' => $user_name,
        'cus_email' => $user_email,
        'cus_add1' => '',
        'cus_city' => $user_city,
        'cus_country' => 'Bangladesh',
        'cus_phone' => $user_phone,
        'shipping_method' => 'NO',
        'product_name' => 'School Fee Payment',
        'product_category' => 'Education',
        'product_profile' => 'general',
    ];


    $response = initiate_payment($post_data);

    if ($response['status'] === 'SUCCESS') {
        header('Location: ' . $response['GatewayPageURL']);
        exit();
    } else {
        echo 'Payment initiation failed: ' . htmlspecialchars($response['failedreason'] ?? 'Unknown error');
    }
}
?>
