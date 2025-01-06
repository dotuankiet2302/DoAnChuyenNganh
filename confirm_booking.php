<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require('inc/links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> - CONFIRM BOOKING</title>
  <style>
    .button-disabled {
      background-color: #77d7c9; 
      color: #999;
      cursor: not-allowed; 
      pointer-events: none;
    }
    .payment-method-container {
        margin: 10px 0;
        display: grid;
        grid-template-columns: repeat(2, 1fr); /* Hiển thị 2 cột */
        gap: 10px; /* Khoảng cách giữa các phương thức */
    }

    .payment-method-option {
        display: flex;
        align-items: center;
        padding: 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .payment-method-option.selected {
        border-color: #2ec1ac;
        background-color: #f8f9fa;
    }

    .payment-method-option:hover {
        border-color: #2ec1ac;
    }

    .payment-method-option img {
        width: 40px;
        height: 40px;
        margin-right: 15px;
        object-fit: contain; /* Đảm bảo ảnh không bị méo */
    }

    .payment-method-option label {
        margin: 0;
        cursor: pointer;
        font-weight: 500;
    }
    .button-disabled {
        background-color: #77d7c9; 
        color: #999;
        cursor: not-allowed; 
        pointer-events: none;
    }
    /* Responsive */
    @media (max-width: 576px) {
        .payment-method-container {
            grid-template-columns: 1fr; /* Chuyển về 1 cột trên mobile */
        }
    }
  </style>
</head>

<body class="bg-light">

  <?php require('inc/header.php'); ?>

  <?php

  /*
      Check room id from url is present or not
      Shutdown mode is active or not
      User is logged in or not
    */

  if (!isset($_GET['id']) || $settings_r['shutdown'] == true) {
    redirect('rooms.php');
  } else if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
    redirect('rooms.php');
  }

  // filter and get room and user data

  $data = filteration($_GET);

  $room_res = select("SELECT * FROM `rooms` WHERE `id`=? AND `status`=? AND `removed`=?", [$data['id'], 1, 0], 'iii');

  if (mysqli_num_rows($room_res) == 0) {
    redirect('rooms.php');
  }

  $room_data = mysqli_fetch_assoc($room_res);

  $_SESSION['room'] = [
    "id" => $room_data['id'],
    "name" => $room_data['name'],
    "price" => $room_data['price'],
    "payment" => null,
    "available" => false,
  ];


  $user_res = select("SELECT * FROM `user_cred` WHERE `id`=? LIMIT 1", [$_SESSION['uId']], "i");
  $user_data = mysqli_fetch_assoc($user_res);

  // Áp dụng mã giảm giá nếu có
  $total_payment = $room_data['price']; // Giá gốc
  $discount_applied = false;

  if (isset($_SESSION['discount_code'])) {
    $discount_code = $_SESSION['discount_code'];

    // Lấy thông tin mã giảm giá
    foreach ($_SESSION['discount_codes'] as $discount) {
      if ($discount['code'] === $discount_code) {
        $discount_value = str_replace('%', '', $discount['discount']); // Chuyển "10%" -> 10
        $total_payment = $total_payment * (1 - $discount_value / 100); // Áp dụng giảm giá
        $discount_applied = true;
        break;
      }
    }
  }
  ?>
  


  <div class="container">
    <div class="row">

      <div class="col-12 my-5 mb-4 px-4">
        <h2 class="fw-bold">XÁC NHẬN ĐẶT PHÒNG</h2>
        <div style="font-size: 14px;">
          <a href="index.php" class="text-secondary text-decoration-none">TRANG CHỦ</a>
          <span class="text-secondary"> > </span>
          <a href="rooms.php" class="text-secondary text-decoration-none">PHÒNG</a>
          <span class="text-secondary"> > </span>
          <a href="#" class="text-secondary text-decoration-none">XÁC NHẬN</a>
        </div>
      </div>

      <div class="col-lg-7 col-md-12 px-4">
        <?php
        $room_thumb = ROOMS_IMG_PATH . "thumbnail.jpg";
        $thumb_q = mysqli_query($con, "SELECT * FROM `room_images` 
            WHERE `room_id`='$room_data[id]' 
            AND `thumb`='1'");

        if (mysqli_num_rows($thumb_q) > 0) {
          $thumb_res = mysqli_fetch_assoc($thumb_q);
          $room_thumb = ROOMS_IMG_PATH . $thumb_res['image'];
        }

        $formatted_price = number_format($room_data['price'], 0, ',', '.');
        $formatted_total_payment = number_format($total_payment, 0, ',', '.');
        echo <<<data
            <div class="card p-3 shadow-sm rounded">
              <img src="$room_thumb" class="img-fluid rounded mb-3">
              <h5>$room_data[name]</h5>
              <h6>Giá gốc: {$formatted_price} vnđ</h6>
          data;

          if ($discount_applied) {
            echo "<h6>Giá sau khi giảm: <span class='text-success'>$formatted_total_payment vnđ</span></h6>";
          }
        echo "</div>";
  
        ?>
      </div>

      <div class="col-lg-5 col-md-12 px-4">
        <div class="card mb-4 border-0 shadow-sm rounded-3">
          <div class="card-body">
            <form action="congthanhtoan.php" method="POST" target="_blank" id="booking_form">
              <h6 class="mb-3">CHI TIẾT PHÒNG ĐẶT</h6>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Tên</label>
                  <input name="name" type="text" value="<?php echo $user_data['name'] ?>" class="form-control shadow-none" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Số Điện Thoại</label>
                  <input name="phonenum" type="number" value="<?php echo $user_data['phonenum'] ?>" class="form-control shadow-none" required>
                </div>
                <div class="col-md-12 mb-3">
                  <label class="form-label">Địa Chỉ</label>
                  <textarea name="address" class="form-control shadow-none" rows="1" required><?php echo $user_data['address'] ?></textarea>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Ngày Nhận Phòng</label>
                  <input name="checkin" onchange="check_availability()" type="date" class="form-control shadow-none" required>
                </div>
                <div class="col-md-6 mb-4">
                  <label class="form-label">Ngày Trả Phòng</label>
                  <input name="checkout" onchange="check_availability()" type="date" class="form-control shadow-none" required>
                </div>

                <div class="col-12">
                  <div class="spinner-border text-info mb-3 d-none" id="info_loader" role="status">
                    <span class="visually-hidden">Đang Tải ...</span>
                  </div>

                  <h6 class="mb-3 text-danger" id="pay_info">Cung cấp ngày nhận phòng và trả phòng!</h6>
                  <h6 class="mb-3 text-danger" id="pay_info"></h6>

                  <input type="hidden" name="days" id="days_input">
                  <input type="hidden" name="payment_method" id="payment_method_input">
                  <input type="hidden" name="total_payment" value="<?php echo $total_payment; ?>">

                  <div class="payment-method-container">
                    <div class="payment-method-option selected" onclick="selectPayment('vnpay')">
                        <input type="radio" name="payment_method" value="vnpay" id="vnpay" style="display: none;" checked>
                        <img src="images/vnpay.jpg" alt="VnPay">
                        <label for="vnpay">VnPay</label>
                    </div>
                    <div class="payment-method-option" onclick="selectPayment('momo')">
                        <input type="radio" name="payment_method" value="momo" id="momo" style="display: none;">
                        <img src="images/momo.png" alt="Momo">
                        <label for="momo">Momo QR</label>
                    </div>
                    <div class="payment-method-option" onclick="selectPayment('zalopay')">
                        <input type="radio" name="payment_method" value="zalopay" id="zalopay" style="display: none;">
                        <img src="images/zalopay.png" alt="ZaloPay">
                        <label for="zalopay">ZaloPay QR</label>
                    </div>
                    <div class="payment-method-option" onclick="selectPayment('stripe')">
                        <input type="radio" name="payment_method" value="stripe" id="stripe" style="display: none;">
                        <img src="images/stripe.png" alt="Stripe">
                        <label for="stripe">Stripe</label>
                    </div>
                </div>
                  <button type="submit" id="book-now" name="congthanhtoan" class="btn w-100 text-white custom-bg shadow-none mb-1">Đặt Ngay</button>
                </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>



  <?php require('inc/footer.php'); ?>
  <script>
    let booking_form = document.getElementById('booking_form');
    let info_loader = document.getElementById('info_loader');
    let pay_info = document.getElementById('pay_info');

    function selectPayment(method) {
        // Bỏ chọn tất cả các options
        document.querySelectorAll('.payment-method-option').forEach(option => {
            option.classList.remove('selected');
        });
        
        // Chọn option được click
        document.querySelector(`input[value="${method}"]`).checked = true;
        document.querySelector(`input[value="${method}"]`).closest('.payment-method-option').classList.add('selected');
    }
    document.addEventListener('DOMContentLoaded', function() {
        // Đặt VnPay làm mặc định
        selectPayment('vnpay');
    });

    document.getElementById('booking_form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        let paymentMethod = document.querySelector('input[name="payment_method"]:checked');
        
        // Nếu không có phương thức thanh toán nào được chọn, tự động chọn VnPay
        if (!paymentMethod) {
            document.querySelector('input[value="vnpay"]').checked = true;
            paymentMethod = document.querySelector('input[value="vnpay"]');
        }
        
        // Set action form dựa vào phương thức thanh toán
        switch(paymentMethod.value) {
            case 'vnpay':
                this.action = 'congthanhtoan.php';
                break;
            case 'momo':
                this.action = 'thanhtoanMomo.php';
                break;
            case 'zalopay':
                this.action = 'thanhtoanZalopay.php';
                break;
            case 'stripe':
                this.action = 'thanhtoanStripe.php';
                break;
            default:
                this.action = 'congthanhtoan.php'; // Mặc định là VnPay
        }
        
        // Submit form
        this.submit();
    });

    function check_availability() {
      let checkin_val = booking_form.elements['checkin'].value;
      let checkout_val = booking_form.elements['checkout'].value;

      booking_form.elements['congthanhtoan'].setAttribute('disabled', true);

      if (checkin_val != '' && checkout_val != '') {
        pay_info.classList.add('d-none');
        pay_info.classList.replace('text-dark', 'text-danger');
        info_loader.classList.remove('d-none');

        let data = new FormData();

        data.append('check_availability', '');
        data.append('check_in', checkin_val);
        data.append('check_out', checkout_val);

        // Gửi mã giảm giá (nếu có)
        let discount_code = '<?php echo isset($_SESSION['discount_code']) ? $_SESSION['discount_code'] : ''; ?>';
        if (discount_code) {
          data.append('discount_code', discount_code);
        }

        let xhr = new XMLHttpRequest();
        xhr.open("POST", "ajax/confirm_booking.php", true);

        xhr.onload = function() {
        let data = JSON.parse(this.responseText);

        if (data.status == 'check_in_out_equal') {
            pay_info.innerText = "Bạn không thể trả phòng trong cùng một ngày!";
        } else if (data.status == 'check_out_earlier') {
            pay_info.innerText = "Ngày trả phòng sớm hơn ngày nhận phòng!";
        } else if (data.status == 'check_in_earlier') {
            pay_info.innerText = "Ngày nhận phòng sớm hơn ngày hôm nay!";
        } else if (data.status == 'unavailable') {
            pay_info.innerText = data.message;
            pay_info.classList.add('text-danger');
            booking_form.elements['congthanhtoan'].setAttribute('disabled', true);
            booking_form.elements['congthanhtoan'].classList.add('button-disabled');
        } else {
            pay_info.innerHTML = `
                Số Phòng Trống: ${data.c_rooms} <br>
                Số ngày Đặt: ${data.days} <br>
                Tổng số tiền phải trả: <span class="text-success">${data.payment} VNĐ</span>
            `;
            pay_info.classList.replace('text-danger', 'text-dark');
            booking_form.elements['congthanhtoan'].removeAttribute('disabled');
            
            booking_form.elements['congthanhtoan'].classList.remove('button-disabled');

            <?php 
              unset($_SESSION['discount_code']);
              $_SESSION['room']['payment'] = $room_data['price']; 
            ?>
          }
          if (data.days) {
              document.getElementById('days_input').value = data.days;
          }
          pay_info.classList.remove('text-danger', 'text-dark'); // Remove both classes first
            if (data.status.startsWith('check_')) { // Add text-danger for error messages
                pay_info.classList.add('text-danger');
            } else if (data.status !== 'unavailable') { // Add text-dark for success message
                pay_info.classList.add('text-dark');
            }
            pay_info.classList.remove('d-none');
            info_loader.classList.add('d-none');
        }

        xhr.send(data);
      }
    }
  </script>

</body>

</html>