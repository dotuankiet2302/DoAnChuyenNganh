<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    require('admin/inc/db_config.php');
    require('admin/inc/essentials.php');

    $hname = 'localhost';
    $uname = 'root';
    $pass = '';
    $db = 'khachsan';

    $con = mysqli_connect($hname,$uname,$pass,$db);
    if (mysqli_connect_errno()) {
        echo "Không thể kết nối đến MySQL: " . mysqli_connect_error();
        exit();
    }
    date_default_timezone_set('Asia/Ho_Chi_Minh');

    session_start();

    $_SESSION['payment_message'] = 'success';
    if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
    redirect('index.php');
    }
    
        // Đoạn mã này lưu trữ thông tin cấu hình cổng thanh toán VNPay, bao gồm mã định danh (vnp_TmnCode), 
        // khóa bảo mật (vnp_HashSecret), URL thanh toán và URL trả về sau khi thanh toán thành công.
    $vnp_TmnCode = "A450ZJ9U"; //Mã định danh merchant kết nối (Terminal Id)
    $vnp_HashSecret = "C40C9WTHX2CWB2I0UKD0LIV3MOJCT9SM"; //Secret key
    $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
    $vnp_Returnurl = "http://localhost:3000/index.php?payment_status=success";    
    $vnp_apiUrl = "http://sandbox.vnpayment.vn/merchant_webapi/merchant.html";
    $apiUrl = "https://sandbox.vnpayment.vn/merchant_webapi/api/transaction";
    //Config input format
    //Expire
    // Đoạn mã này thiết lập thời gian bắt đầu và thời gian hết hạn cho giao dịch, 
    // với thời gian hết hạn là 15 phút sau thời gian bắt đầu.
    $startTime = date("YmdHis");
    $expire = date('YmdHis',strtotime('+15 minutes',strtotime($startTime)));
    //Endconfig

    // Đoạn mã kiểm tra xem người dùng có chọn phương thức thanh toán là vnpay không. Nếu có,
    //  mã sẽ lấy dữ liệu từ biểu mẫu và tính toán tổng số tiền thanh toán dựa trên số ngày (days).
    if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'vnpay') {
        $frm_data = filteration(data: $_POST);
        $days = $_POST['days'];
        // Kiểm tra xem $_POST['total_payment'] có tồn tại hay không
        if (isset($_POST['total_payment']) && !empty($_POST['total_payment'])) {
            $total_payment = $_POST['total_payment'];
            $total_price = $total_payment * $days;

            // Unset $_POST['total_payment'] sau khi sử dụng xong
            unset($_POST['total_payment']);

        } else {
            $total_price = $_SESSION['room']['price'] * $days;
        }
      
        // Đoạn mã này tạo một mã đơn hàng duy nhất (ORDER_ID) dựa trên ID người dùng và một số ngẫu nhiên. 
        // Đồng thời thiết lập các thông tin thanh toán như số tiền thanh toán (vnp_Amount), ngôn ngữ (vnp_Locale), 
        // phương thức thanh toán (vnp_BankCode) và địa chỉ IP của khách hàng.
        $ORDER_ID = 'ORD_' . $_SESSION['uId'] . random_int(11111, 9999999);
        $CUST_ID = $_SESSION['uId'];
        //$TXN_AMOUNT = $total_price;
      
        $vnp_TxnRef = time(); //Mã giao dịch thanh toán tham chiếu của merchant
        $vnp_Amount = $total_price; // Số tiền thanh toán
        $vnp_Locale = 'vn'; //Ngôn ngữ chuyển hướng thanh toán
        $vnp_BankCode = 'NCB'; //Mã phương thức thanh toán
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR']; //IP Khách hàng thanh toán

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount *100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => "Thanh Toán VnPay",
            "vnp_OrderType" => "other",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef
            //"vnp_ExpireDate"=>$expire
        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
            $inputData['vnp_BankCode'] = $vnp_BankCode;
        }

        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        // Đoạn mã này hoàn thiện URL thanh toán với các tham số đã mã hóa và tính toán giá trị bảo mật (vnp_SecureHash), 
        // sau đó chuyển hướng người dùng tới trang thanh toán VNPay.
        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
            $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret);//  
            $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        header(header: 'Location: ' . $vnp_Url);
      
        // Đoạn mã này lưu thông tin đơn hàng vào cơ sở dữ liệu (bảng booking_order), 
        // bao gồm thông tin về người dùng, phòng đã đặt và thời gian check-in, check-out.
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
          $vnp_Amount, $frm_data['name'], $frm_data['phonenum'], $frm_data['address']
        ], 'issssss');
      
        $room_id = $_SESSION['room']['id'];
        //Trước khi thanh toán, mã kiểm tra và cập nhật số lượng phòng còn lại trong cơ sở dữ liệu, giảm đi một phòng đã được đặt.
        $query = "SELECT quantity FROM rooms WHERE id = ?";
        $result = select($query, [$room_id], 'i');
      
        if ($result && mysqli_num_rows($result) > 0) {
          $room = mysqli_fetch_assoc($result);
          $quantity = $room['quantity'];
          $new_quantity = $quantity - 0;
          $query = "UPDATE rooms SET quantity = ? WHERE id = ?";
          update($query, [$new_quantity, $room_id], 'ii');
        }
      
        // Sau khi thanh toán thành công
        if($payment_success) {
            // Cập nhật số lượng phòng trong database
            $update_q = "UPDATE `rooms` SET `quantity` = `quantity` - 1 WHERE `id`=?";
            $values = [$_SESSION['room']['id']];
            $update_res = update($update_q, $values, 'i');
            
            // Thêm booking vào database
            $booking_q = "INSERT INTO `booking_order`(`room_id`, `user_id`, `check_in`, `check_out`, `booking_status`) 
                         VALUES (?,?,?,?,'Đã Đặt')";
            $booking_values = [
                $_SESSION['room']['id'],
                $_SESSION['uId'],
                $frm_data['checkin'],
                $frm_data['checkout']
            ];
            insert($booking_q, $booking_values, 'iiss');
        }
      
         redirect("bookings.php");
    }
?>
