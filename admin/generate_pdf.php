<?php 

  require('inc/mpdf/vendor/autoload.php');
  require('inc/essentials.php');
  require('inc/db_config.php');

  adminLogin();

  if(isset($_GET['gen_pdf']) && isset($_GET['id']))
  {
    $frm_data = filteration($_GET);
    $query = "SELECT bo.*, bd.*,uc.email FROM `booking_order` bo
      INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
      INNER JOIN `user_cred` uc ON bo.user_id = uc.id
      WHERE ((bo.booking_status='Đã Thanh Toán') 
      OR (bo.booking_status='Đã Huỷ')
      OR (bo.booking_status='Đã Xác Nhận Đặt Phòng')) 
      AND bo.booking_id = '$frm_data[id]'";

    $res = mysqli_query($con,$query);
    $total_rows = mysqli_num_rows($res);

    if($total_rows==0){
      header('location: dashboard.php');
      exit;
    }
    // Sau đó sử dụng
    $data = mysqli_fetch_assoc($res);

    $date = date("H:i | d-m-Y",strtotime($data['datentime']));
    $checkin = date("d-m-Y",strtotime($data['check_in']));
    $checkout = date("d-m-Y",strtotime($data['check_out']));

        // Tính số ngày
    $checkin_ts = strtotime($data['check_in']);
    $checkout_ts = strtotime($data['check_out']);
    $days = round(($checkout_ts - $checkin_ts) / (60 * 60 * 24));


    // Đường dẫn đến file ảnh của bạn
    $image_path ='C:/xampp/htdocs/khachsan/images/icon_hotel.jpg';
    $image_data = base64_encode(file_get_contents($image_path));

    // Kiểm tra file có tồn tại không
    if (!file_exists($image_path)) {
        die("Lỗi: File không tồn tại tại đường dẫn: " . $image_path);
    }

    // Kiểm tra file có thể đọc được không
    if (!is_readable($image_path)) {
        die("Lỗi: PHP không có quyền đọc file tại: " . $image_path);
    }

    // Kiểm tra kích thước file
    $filesize = filesize($image_path);
    if ($filesize === false || $filesize === 0) {
        die("Lỗi: File rỗng hoặc không thể đọc kích thước");
    }

    // Kiểm tra permissions của file
    $perms = fileperms($image_path);
    echo "Quyền của file: " . decoct($perms & 0777);

    $price = number_format($data['price'], 0, ',', '.');
    $trans_amt = number_format($data['trans_amt'], 0, ',', '.');
    
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body {
                font-family: 'DejaVu Sans', sans-serif;
                margin: 20px;
            }
            .invoice-table {
                width: 100%;
                border-collapse: collapse;
            }
            .invoice-table td {
                border: none;  /* Bỏ viền cho tất cả các ô */
            }
            .logo-cell {
                width: 120px;
                vertical-align: top;
                padding: 0;  /* Bỏ padding */
            }
            .content-cell {
                vertical-align: middle;
                padding-left: 30px;
            }
            .header-image {
                width: 100px;
                height: 100px;
                object-fit: contain;
            }
            .title-row {
                width: 100%;
                text-align: center;
                margin-bottom: 20px;
            }
            .header-title {
                color: #D4001A;
                font-size: 20px;
                font-weight: bold;
                display: block;
                margin: 0 auto;
            }
            .info-container {
                text-align: right;
            }
            .customer-info-label {
                color: #000000;
                font-weight: bold;
            }
            .customer-info-value {
                color: #000000;
            }
            .thank-you {
                text-align: center;
                color: #000000;
                font-weight: bold;
                margin-top: 30px;
                margin-bottom: 20px;
            }
            .info {
                color: #D4001A;
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
                background-color: #f8f9fa;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            th, td {
                padding: 12px 15px;
                text-align: left;
                border: 1px solid #ddd;
            }
            tr:nth-child(even) {
                background-color: #ffffff;
            }
            tr:hover {
                background-color: #f5f5f5;
            }
            .room-info {
                font-weight: bold;
                color: #D4001A;
            }
            .date-info {
                color: #666;
            }
            .status-info {
                font-weight: bold;
                text-transform: uppercase;
                font-size: 0.9em;
            }
            .total {
                text-align: right;
                margin: 20px 0;
            }
            .footer {
                margin-top: 30px;
                background-color: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .footer p {
                margin: 8px 0;
            }
            .red {
                color: #D4001A;
                margin-bottom: 15px;
            }
        </style>
    </head>
    
    <body>
        <table class='invoice-table'>
            <tr>
                <td class='logo-cell'>
                    <img src='data:image/jpeg;base64,$image_data' class='header-image' alt='Logo'>
                </td>
                <td colspan='2' style='text-align: center;'>
                    <div class='header-title'>
                        HÓA ĐƠN<br>
                        KHÁCH SẠN ROBINS VILLA
                    </div>
                </td>
            </tr>
            <tr>
                <td></td>
                <td class='content-cell'>
                    <div class='info-container'>
                        <span class='customer-info-label'>Thông tin khách hàng</span><br>
                        <span class='customer-info-label'>Tên:</span> <span class='customer-info-value'>{$data['user_name']}</span><br>
                        <span class='customer-info-label'>Số điện thoại:</span> <span class='customer-info-value'>{$data['phonenum']}</span><br>
                        <span class='customer-info-label'>Địa chỉ:</span> <span class='customer-info-value'>{$data['address']}</span><br>
                        <span class='customer-info-label'>Email:</span> <span class='customer-info-value'>$data[email]</span><br>
                        <span class='customer-info-label'>Ngày:</span> <span class='customer-info-value'>$date</span>
                    </div>
                </td>
            </tr>
        </table>

        <table>
            <tr class='room-info'>
                <td><strong>Tên Phòng:</strong> $data[room_name]</td>
                <td><strong>Trạng Thái:</strong> $data[booking_status]</td>
            </tr>
            <tr class='date-info'>
                <td><strong>Ngày Vào:</strong> $checkin</td>
                <td><strong>Giá:</strong> $price VNĐ</td>
            </tr>
            <tr class='date-info'>
                <td><strong>Ngày Ra:</strong> $checkout</td>
                <td><strong>Số ngày đặt:</strong> $days ngày</td>
            </tr>
        </table>

        <div class='total'>
            Tổng cộng: $trans_amt VNĐ<br>
            Thuế (0%): 0đ<br>
            <strong class='red'>TỔNG TIỀN: $trans_amt VNĐ</strong>
        </div>

        <div class='footer'>
            <table style='width: 100%; border: none;'>
                <tr>
                    <td style='width: 50%; vertical-align: top; border: none;'>
                        <div class='red'><strong>Thông tin thanh toán</strong></div>
                        <p>Ngân hàng Sacombank</p>
                        <p>Tài khoản: Robins Villa</p>
                        <p>Số tài khoản: 123-456-7890</p>
                        <p>Hạn thanh toán: $checkout</p>
                    </td>
                    <td style='width: 50%; vertical-align: top; border: none;'>
                        <div class='red'><strong>Thông tin liên hệ</strong></div>
                        <p>robinsvilla@gmail.vn</p>
                        <p>20 Tran Binh Trong, Ward 5, Đà Lạt</p>
                        <p>+84 375 361 065</p>
                        <p>robinsvilla.vn</p>
                    </td>
                </tr>
            </table>
        </div>
        <div class='thank-you'>
            Khách sạn Robins Villa<br>
            Trân trọng cảm ơn quý khách!!!
        </div>
    </body>
    ";
    $html.="</html>";

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15
    ]);

    $mpdf->WriteHTML($html);
    $mpdf->Output($data['order_id'].'.pdf', 'D');


  }
  else{
    header('location: dashboard.php');
  }
  
?>