<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('admin/inc/db_config.php');
require('admin/inc/essentials.php');

date_default_timezone_set('Asia/Ho_Chi_Minh');

session_start();

$_SESSION['payment_message'] = 'success';
header("Location: " . SITE_URL . "index.php");
if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
  redirect('index.php');
}

require 'vendor/autoload.php';
\Stripe\Stripe::setApiKey('sk_test_51QFnuBRoozzQBHBlr9wXT5DX9t8VE4gmFzi2zqGUloFMJzvfLWIJS5kuju4k0IErGFP4899Ljx7tsMSSeCPBNMtg00iZu0NNCQ'); // Replace with your secret key

if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'stripe') {
  $frm_data = filteration($_POST);

  //redirect('choose_payment.php');
    // Tính toán số đêm ở lại
    $days = $_POST['days'];

    if (isset($_POST['total_payment']) && !empty($_POST['total_payment'])) {
        $total_payment = $_POST['total_payment'];
        $total_price = $total_payment * $days;

        // Unset $_POST['total_payment'] sau khi sử dụng xong
        unset($_POST['total_payment']);

    } else {
        $total_price = $_SESSION['room']['price'] * $days;
    }

  $ORDER_ID = 'ORD_' . $_SESSION['uId'] . random_int(11111, 9999999);
  $CUST_ID = $_SESSION['uId'];
  $TXN_AMOUNT = $total_price;

  // Create a Stripe Checkout Session
  $checkout_session = \Stripe\Checkout\Session::create([
    'payment_method_types' => ['card'],
    'line_items' => [[
        'price_data' => [
            'currency' => 'vnd',
            'unit_amount' => $total_price,
            'product_data' => [
                'name' => $_SESSION['room']['name'],
                'description' => $number_of_nights . ' night' . ($number_of_nights > 1 ? 's' : ''),
            ],
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
    "success_url" => "http://localhost:3000/index.php?payment_status=success",
    "cancel_url" => "http://localhost:3000/index.php?payment_status=cancel",
  ]);

  // Redirect to Stripe Checkout
  header("HTTP/1.1 303 See Other");
  header("Location: " . $checkout_session->url);
  //exit;

  $query1 = "INSERT INTO `booking_order`(`user_id`, `room_id`, `check_in`, `check_out`,`order_id`) VALUES (?,?,?,?,?)";

  insert($query1, [
    $CUST_ID, $_SESSION['room']['id'], $frm_data['checkin'],
    $frm_data['checkout'], $ORDER_ID
  ], 'issss');

  $booking_id = mysqli_insert_id($con);

  $query2 = "INSERT INTO `booking_details`(`booking_id`, `room_name`, `price`, `total_pay`,
      `user_name`, `phonenum`, `address`) VALUES (?,?,?,?,?,?,?)";

  insert($query2, [
    $booking_id, $_SESSION['room']['name'], $_SESSION['room']['price'],
    $TXN_AMOUNT, $frm_data['name'], $frm_data['phonenum'], $frm_data['address']
  ], 'issssss');

  $room_id = $_SESSION['room']['id'];
  $query = "SELECT quantity FROM rooms WHERE id = ?";
  $result = select($query, [$room_id], 'i');

  if ($result && mysqli_num_rows($result) > 0) {
    $room = mysqli_fetch_assoc($result);
    $quantity = $room['quantity'];
    $new_quantity = $quantity - 0;
    $query = "UPDATE rooms SET quantity = ? WHERE id = ?";
    update($query, [$new_quantity, $room_id], 'ii');
  }

   redirect("bookings.php");
}

// if (isset($_POST['thanhtoanStripe'])) {

//   $frm_data = filteration($_POST);

//   //redirect('choose_payment.php');
//     // Tính toán số đêm ở lại
//     $days = $_POST['days'];

//     if (isset($_POST['total_payment']) && !empty($_POST['total_payment'])) {
//         $total_payment = $_POST['total_payment'];
//         $total_price = $total_payment * $days;

//         // Unset $_POST['total_payment'] sau khi sử dụng xong
//         unset($_POST['total_payment']);

//     } else {
//         $total_price = $_SESSION['room']['price'] * $days;
//     }

//   $ORDER_ID = 'ORD_' . $_SESSION['uId'] . random_int(11111, 9999999);
//   $CUST_ID = $_SESSION['uId'];
//   $TXN_AMOUNT = $total_price;

//   // Create a Stripe Checkout Session
//   $checkout_session = \Stripe\Checkout\Session::create([
//     'payment_method_types' => ['card'],
//     'line_items' => [[
//         'price_data' => [
//             'currency' => 'vnd', // Set your currency
//             'unit_amount' => $total_price, // Sử dụng tổng giá đã tính
//                 'product_data' => [
//                     'name' => $_SESSION['room']['name'],
//                     'description' => $number_of_nights . ' night' . ($number_of_nights > 1 ? 's' : ''), // Mô tả số đêm
//                 ],
//         ],
//         'quantity' => 1,
//     ]],
//     'mode' => 'payment',
//     "success_url" => "http://localhost:3000/pay_status.php?session_id={CHECKOUT_SESSION_ID}",
//     "cancel_url" => "http://localhost:3000/index.php",
//   ]);

//   // Redirect to Stripe Checkout
//   header("HTTP/1.1 303 See Other");
//   header("Location: " . $checkout_session->url);
//   //exit;

//   $query1 = "INSERT INTO `booking_order`(`user_id`, `room_id`, `check_in`, `check_out`,`order_id`) VALUES (?,?,?,?,?)";

//   insert($query1, [
//     $CUST_ID, $_SESSION['room']['id'], $frm_data['checkin'],
//     $frm_data['checkout'], $ORDER_ID
//   ], 'issss');

//   $booking_id = mysqli_insert_id($con);

//   $query2 = "INSERT INTO `booking_details`(`booking_id`, `room_name`, `price`, `total_pay`,
//       `user_name`, `phonenum`, `address`) VALUES (?,?,?,?,?,?,?)";

//   insert($query2, [
//     $booking_id, $_SESSION['room']['name'], $_SESSION['room']['price'],
//     $TXN_AMOUNT, $frm_data['name'], $frm_data['phonenum'], $frm_data['address']
//   ], 'issssss');

//   $room_id = $_SESSION['room']['id'];
//   $query = "SELECT quantity FROM rooms WHERE id = ?";
//   $result = select($query, [$room_id], 'i');

//   if ($result && mysqli_num_rows($result) > 0) {
//     $room = mysqli_fetch_assoc($result);
//     $quantity = $room['quantity'];
//     $new_quantity = $quantity - 0;
//     $query = "UPDATE rooms SET quantity = ? WHERE id = ?";
//     update($query, [$new_quantity, $room_id], 'ii');
//   }

//    redirect("bookings.php");
// }
