<?php
require('admin/inc/db_config.php');
require('admin/inc/essentials.php');
require 'vendor/autoload.php'; // Include the Stripe library
\Stripe\Stripe::setApiKey('sk_test_51QFnuBRoozzQBHBlr9wXT5DX9t8VE4gmFzi2zqGUloFMJzvfLWIJS5kuju4k0IErGFP4899Ljx7tsMSSeCPBNMtg00iZu0NNCQ');

session_start();

if (isset($_GET['session_id'])) {
    $session_id = $_GET['session_id'];
    try {
        $session = \Stripe\Checkout\Session::retrieve($session_id);

        if ($session->payment_status === 'paid') {
          // Payment successful, proceed with database updates
          $ORDER_ID = 'ORD_' . $_SESSION['uId'] . random_int(11111, 9999999);
          $CUST_ID = $_SESSION['uId'];

          // ... (Your existing database insertion code) ...

          redirect("bookings.php");

        } else {
          echo "Payment failed. Please try again.";
        }
    } catch (\Stripe\Exception\ApiErrorException $e) {
      error_log("Stripe Error: " . $e->getMessage());
      echo "Error retrieving session: " . $e->getMessage();
    }
} else {
    echo "Invalid session ID.";
}

?>
