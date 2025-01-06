<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require('inc/links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> - MÃ GIẢM GIÁ</title>
</head>
<body class="bg-light">
  <?php 
      require('inc/header.php'); 
      
      if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
          redirect('index.php');
      }

      if (!isset($_SESSION['uId'])) {
          echo "User ID không tồn tại trong session.";
          exit;
      }

      // Truy vấn thông tin người dùng
      $query_user = "SELECT * FROM `user_cred` WHERE id = ?";
      $result_user = select($query_user, [$_SESSION['uId']], 'i');
      if (!$result_user) {
          echo "Lỗi truy vấn người dùng.";
          exit;
      }
      $user = mysqli_fetch_assoc($result_user);
      $dob = $user['dob']; 

      $today = date('Y-m-d');
      $birthday_discount = (date('m-d', strtotime($dob)) == date('m-d', strtotime($today))) ? true : false;

      $holiday_discounts = [
          '01-01' => 'Tết Dương Lịch', 
          '04-30' => 'Ngày Thống Nhất',
          '09-02' => 'Quốc Khánh', 
          '12-25' => 'Giáng Sinh', 
      ];
      
      $holiday_discount = isset($holiday_discounts[date('m-d')]) ? true : false;

      if (!isset($_SESSION['discount_codes'])) {
          $query = "SELECT SUM(bd.total_pay) AS total_payment 
                    FROM booking_order bo
                    INNER JOIN booking_details bd ON bo.booking_id = bd.booking_id
                    WHERE bo.user_id = ? AND bo.booking_status = 'Đã Thanh Toán'";

          $result = select($query, [$_SESSION['uId']], 'i');
          $data = mysqli_fetch_assoc($result);

          $total_payment = $data['total_payment'];

          $discount_codes = [];

          if ($total_payment >= 3000000) {
              $discount_codes[] = [
                  'code' => generateDiscountCode(),
                  'discount' => '5%'
              ];
          }
          if ($total_payment >= 5000000) {
              $discount_codes[] = [
                  'code' => generateDiscountCode(),
                  'discount' => '10%'
              ];
          }
          if ($total_payment >= 10000000) {
              $discount_codes[] = [
                  'code' => generateDiscountCode(),
                  'discount' => '15%'
              ];
          }

          if ($birthday_discount || $holiday_discount) {
              $discount_codes[] = [
                  'code' => generateDiscountCode(),
                  'discount' => '20%'
              ];
          }

          $_SESSION['discount_codes'] = $discount_codes;
      } else {
          $discount_codes = $_SESSION['discount_codes'];
      }

      if (isset($_SESSION['redeemed_discount']) && $_SESSION['redeemed_discount'] == true) {
          $discount_code = generateDiscountCode(); 
          $discount_codes[] = [
              'code' => $discount_code,
              'discount' => '8%'
          ];
          $_SESSION['discount_codes'] = $discount_codes; 
          unset($_SESSION['redeemed_discount']); 
      }

      function generateDiscountCode() {
          $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
          $code = '';
          for ($i = 0; $i < 7; $i++) {
              $code .= $characters[rand(0, strlen($characters) - 1)];
          }
          return $code;
      }
  ?>

  <div class="container">
    <div class="row">
      <div class="col-12 my-5 px-4">
        <h2 class="fw-bold">MÃ GIẢM GIÁ</h2>
        <div style="font-size: 14px;">
          <a href="index.php" class="text-secondary text-decoration-none">TRANG CHỦ</a>
          <span class="text-secondary"> > </span>
          <a href="#" class="text-secondary text-decoration-none">MÃ GIẢM GIÁ</a>
        </div>

        <?php
          if (isset($_SESSION['discount_code_message'])) {
              echo "<div class='alert alert-info mt-4'>" . $_SESSION['discount_code_message'] . "</div>";
              unset($_SESSION['discount_code_message']);
          }
          
          if (count($discount_codes) > 0) {
              echo "<div class='row row-cols-1 row-cols-md-3 g-4 my-4'>";
              $index = 1;
              foreach ($discount_codes as $discount) {
                echo "<div class='col'>
                        <div class='card h-100 shadow-sm'>
                          <div class='card-body'>
                            <h5 class='card-title'>Mã Giảm Giá $index</h5>
                            <p class='card-text'><strong>{$discount['code']}</strong></p>
                            <p class='card-text'>Giảm giá: {$discount['discount']} trên tổng đơn hàng của bạn.</p>
                            <!-- Nút Sử dụng -->
                            <a href='rooms.php?discount_code={$discount['code']}' 
                               class='btn btn-primary w-100 mt-3 use-discount' 
                               data-code='{$discount['code']}' 
                               id='discount-{$discount['code']}'>Sử Dụng</a>
                          </div>
                        </div>
                      </div>";
                $index++;
            } 
              echo "</div>";
          } else {
              echo "<div class='alert alert-info'>Tổng thanh toán của bạn chưa đủ điều kiện để nhận mã giảm giá.</div>";
          }
        ?>
      </div>
    </div>
  </div>
  
  <?php require('inc/footer.php'); ?>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      let usedDiscountCodes = JSON.parse(localStorage.getItem('usedDiscountCodes')) || [];

      document.querySelectorAll('.use-discount').forEach(function (button) {
        const discountCode = button.getAttribute('data-code');

        if (usedDiscountCodes.includes(discountCode)) {
          button.style.display = 'none';
        }

        button.addEventListener('click', function () {
          if (!usedDiscountCodes.includes(discountCode)) {
            usedDiscountCodes.push(discountCode);
            localStorage.setItem('usedDiscountCodes', JSON.stringify(usedDiscountCodes));
          }

          button.style.display = 'none';
        });
      });
    });
  </script>
</body>
</html>
