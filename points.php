<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require('inc/links.php'); ?>
  <title><?php echo isset($settings_r['site_title']) ? $settings_r['site_title'] : 'Website'; ?> - TÍCH ĐIỂM</title>
</head>
<body class="bg-light">
  <?php 
    require('inc/header.php'); 

    if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
        redirect('index.php');
    }

    // Lấy thông tin từ session
    $customer_name = isset($_SESSION['uName']) ? $_SESSION['uName'] : 'Khách hàng';
    $user_id = $_SESSION['uId'];
    $customer_points = isset($_SESSION['uPoints']) ? $_SESSION['uPoints'] : 0;

    // Truy vấn các đơn hàng đã thanh toán
    $query = "SELECT COUNT(*) AS paid_orders FROM `booking_order` WHERE user_id = ? AND booking_status = 'Đã Thanh Toán'";
    $result = select($query, [$user_id], 'i'); // Thực hiện truy vấn với tham số

    $data = mysqli_fetch_assoc($result);
    $paid_orders = $data['paid_orders'];

    if ($paid_orders > 0) {
        $customer_points += $paid_orders * 100;
    }

    // Handle the redeeming of points
    if (isset($_POST['redeem_points'])) {
        if ($customer_points >= 1000) {
            // Deduct points and mark in session that points were redeemed
            $_SESSION['uPoints'] -= 1000;
            $_SESSION['redeemed_discount'] = true; // Flag that discount was redeemed
            $_SESSION['discount_code_message'] = "Bạn đã đổi mã giảm giá thành công!";

            redirect('sales.php'); // Redirect to sales.php to handle the discount code generation
        } else {
            $_SESSION['discount_code_message'] = "Bạn không đủ điểm để đổi mã giảm giá. Bạn cần ít nhất 1000 điểm.";
        }
    }
  ?>
  
  <div class="container">
    <div class="row">
      <div class="col-12 my-5 px-4">
        <h2 class="fw-bold">TÍCH ĐIỂM</h2>
        <div style="font-size: 14px;">
          <a href="index.php" class="text-secondary text-decoration-none">TRANG CHỦ</a>
          <span class="text-secondary"> > </span>
          <a href="#" class="text-secondary text-decoration-none">TÍCH ĐIỂM</a>
        </div>
      </div>
    </div>

    <!-- Points Information -->
    <div class="row justify-content-center">
      <div class="col-12 col-md-6 px-4">
        <div class="card shadow-sm p-4">
          <h4 class="text-center">Thông tin tích điểm</h4>
          <hr>
          <form method="POST">
            <div class="mb-3">
              <label for="customerName" class="form-label">Tên khách hàng:</label>
              <input type="text" class="form-control" id="customerName" value="<?php echo $customer_name; ?>" readonly>
            </div>
            <div class="mb-3">
              <label for="points" class="form-label">Số điểm đã tích:</label>
              <input type="text" class="form-control" id="points" value="<?php echo $customer_points; ?>" readonly>
            </div>
            <div class="text-center">
              <?php if ($customer_points >= 1000): ?>
                <button type="submit" name="redeem_points" class="btn btn-primary">Đổi mã giảm giá</button>
              <?php else: ?>
                <button type="button" class="btn btn-secondary" disabled>Không đủ điểm</button>
              <?php endif; ?>
            </div>
          </form>
          <?php 
            if (isset($_SESSION['discount_code_message'])) {
                echo "<div class='alert alert-info mt-4'>" . $_SESSION['discount_code_message'] . "</div>";
                unset($_SESSION['discount_code_message']);
            }
          ?>
        </div>
      </div>
    </div>
  </div>
  
  <?php require('inc/footer.php'); ?>
</body>
</html>
