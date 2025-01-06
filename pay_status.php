<!-- <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require('inc/links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> - BOOKING STATUS</title>
</head>
<body class="bg-light">

  <?php require('inc/header.php'); ?>

  <div class="container">
    <div class="row">

      <div class="col-12 my-5 mb-3 px-4">
        <h2 class="fw-bold">PAYMENT STATUS</h2>
      </div>

      <?php 

        $frm_data = filteration($_GET);

        if(!(isset($_SESSION['login']) && $_SESSION['login']==true)){
          redirect('index.php');
        }

        $booking_q = "SELECT bo.*, bd.* FROM `booking_order` bo 
          INNER JOIN `booking_details` bd ON bo.booking_id=bd.booking_id
          WHERE bo.order_id=? AND bo.user_id=? AND bo.booking_status!=?";
      
        $booking_res = select($booking_q,[$frm_data['order'],$_SESSION['uId'],'Đã Đặt'],'sis');

        if(mysqli_num_rows($booking_res)==0){
          redirect('index.php');
        }

        $booking_fetch = mysqli_fetch_assoc($booking_res);

        if($booking_fetch['trans_status']=="TXN_SUCCESS")
        {
          echo<<<data
            <div class="col-12 px-4">
              <p class="fw-bold alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                Payment done! Booking successful.
                <br><br>
                <a href='bookings.php'>Go to Bookings</a>
              </p>
            </div>
          data;
        }
        else
        {
          echo<<<data
            <div class="col-12 px-4">
              <p class="fw-bold alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                Payment failed! $booking_fetch[trans_resp_msg]
                <br><br>
                <a href='bookings.php'>Go to Bookings</a>
              </p>
            </div>
          data;
        }

      ?>

    </div>
  </div>


  <?php require('inc/footer.php'); ?>

</body>
</html> -->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require('inc/links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> - BOOKING STATUS</title>
</head>
<body class="bg-light">

  <?php 
  require('inc/header.php'); 
  require 'vendor/autoload.php';
  \Stripe\Stripe::setApiKey('sk_test_51QFnuBRoozzQBHBlr9wXT5DX9t8VE4gmFzi2zqGUloFMJzvfLWIJS5kuju4k0IErGFP4899Ljx7tsMSSeCPBNMtg00iZu0NNCQ');
  ?>

  <div class="container">
    <div class="row">

      <div class="col-12 my-5 mb-3 px-4">
        <h2 class="fw-bold">PAYMENT STATUS</h2>
      </div>

      <?php 

        if(!(isset($_SESSION['login']) && $_SESSION['login']==true)){
          redirect('index.php');
        }

        if (isset($_GET['session_id'])) {
            $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);

            if ($session->payment_status === 'paid') {

                // Lấy thông tin đặt phòng từ session
                $booking_data = $_SESSION['booking_data'];

                $ORDER_ID = 'ORD_' . $_SESSION['uId'] . random_int(11111, 9999999);

                $query1 = "INSERT INTO `booking_order`(`user_id`, `room_id`, `check_in`, `check_out`,`order_id`) VALUES (?,?,?,?,?)";

                insert($query1, [
                  $_SESSION['uId'], $booking_data['room_id'], $booking_data['check_in'],
                  $booking_data['check_out'], $ORDER_ID
                ], 'issss');

                $booking_id = mysqli_insert_id($con);

                $query2 = "INSERT INTO `booking_details`(`booking_id`, `room_name`, `price`, `total_pay`,
                    `user_name`, `phonenum`, `address`) VALUES (?,?,?,?,?,?,?)";

                insert($query2, [
                  $booking_id, $_SESSION['room']['name'], $_SESSION['room']['price'],
                  $booking_data['total_price'], $booking_data['name'], $booking_data['phonenum'], $booking_data['address']
                ], 'issssss');

                $room_id = $booking_data['room_id'];
                $query = "SELECT quantity FROM rooms WHERE id = ?";
                $result = select($query, [$room_id], 'i');

                if ($result && mysqli_num_rows($result) > 0) {
                  $room = mysqli_fetch_assoc($result);
                  $quantity = $room['quantity'];
                  $new_quantity = $quantity - 1; // Giảm số lượng phòng sau khi đặt
                  $query = "UPDATE rooms SET quantity = ? WHERE id = ?";
                  update($query, [$new_quantity, $room_id], 'ii');
                }

                // Xóa dữ liệu booking khỏi session sau khi đã xử lý
                unset($_SESSION['booking_data']);

                echo<<<data
                  <div class="col-12 px-4">
                    <p class="fw-bold alert alert-success">
                      <i class="bi bi-check-circle-fill"></i>
                      Payment done! Booking successful.
                      <br><br>
                      <a href='bookings.php'>Go to Bookings</a>
                    </p>
                  </div>
                data;

            } else {
                echo<<<data
                  <div class="col-12 px-4">
                    <p class="fw-bold alert alert-danger">
                      <i class="bi bi-exclamation-triangle-fill"></i>
                      Payment failed!
                      <br><br>
                      <a href='bookings.php'>Go to Bookings</a>
                    </p>
                  </div>
                data;
            }
        } else {
            echo<<<data
              <div class="col-12 px-4">
                <p class="fw-bold alert alert-danger">
                  <i class="bi bi-exclamation-triangle-fill"></i>
                  Invalid Session ID.
                  <br><br>
                  <a href='index.php'>Go to Home</a>
                </p>
              </div>
            data;
        }

      ?>

    </div>
  </div>


  <?php require('inc/footer.php'); ?>

</body>
</html>