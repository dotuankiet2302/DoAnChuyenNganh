<?php

// require __DIR__ . "/vendor/autoload.php";

// $stripe_secret_key = "sk_test_51QFnuBRoozzQBHBlr9wXT5DX9t8VE4gmFzi2zqGUloFMJzvfLWIJS5kuju4k0IErGFP4899Ljx7tsMSSeCPBNMtg00iZu0NNCQ";

// \Stripe\Stripe::setApiKey($stripe_secret_key);

// $checkout_session = \Stripe\Checkout\Session::create([
//     "mode" => "payment",
//     "success_url" => "http://localhost:3000/success.php",
//     "cancel_url" => "http://localhost:3000/index.php",
//     "locale" => "auto",
//     "line_items" => [
//         [
//             "quantity" => 1,
//             "price_data" => [
//                 "currency" => "usd",
//                 "unit_amount" => 2000,
//                 "product_data" => [
//                     "name" => "T-shirt"
//                 ]
//             ]
//         ],
//         [
//             "quantity" => 2,
//             "price_data" => [
//                 "currency" => "usd",
//                 "unit_amount" => 700,
//                 "product_data" => [
//                     "name" => "Hat"
//                 ]
//             ]
//         ]        
//     ]
// ]);

// http_response_code(303);
// header("Location: " . $checkout_session->url);


require 'vendor/autoload.php';
\Stripe\Stripe::setApiKey('sk_test_51QFnuBRoozzQBHBlr9wXT5DX9t8VE4gmFzi2zqGUloFMJzvfLWIJS5kuju4k0IErGFP4899Ljx7tsMSSeCPBNMtg00iZu0NNCQ'); // Replace with your secret key

session_start();
try {

    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'vnd', // Set your currency
                'unit_amount' => $_SESSION['room']['price'], // Amount in cents/smallest currency unit
                'product_data' => [
                    'name' => $_SESSION['room']['name'],
                ],
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        //'success_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/pay_status.php?session_id={CHECKOUT_SESSION_ID}', // Full URL
        //'cancel_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/cancel.php', // Full URL for cancel_url as well
        "success_url" => "http://localhost:3000/pay_status.php",
        "cancel_url" => "http://localhost:3000/index.php",
    ]);

    $_SESSION['checkout_session_id'] = $checkout_session->id; // Store the session ID (optional, for debugging or tracking)

    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);  // Redirect to Stripe checkout
    exit; // Important: Stop further execution

} catch (\Stripe\Exception\ApiErrorException $e) {
    // Handle Stripe API errors
    echo "Error creating checkout session: " . $e->getMessage();
}