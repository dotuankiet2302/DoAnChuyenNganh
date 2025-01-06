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

    if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
        redirect('index.php');
    }

    // Hàm gửi request đến MoMo
    function execPostRequest($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
        );
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'momo') {
        $frm_data = filteration($_POST);
        $days = $_POST['days'];

        // Kiểm tra xem $_POST['total_payment'] có tồn tại hay không
        if (isset($_POST['total_payment']) && !empty($_POST['total_payment'])) {
            $total_payment = $_POST['total_payment'];
            $total_price = $total_payment * $days;
        } else {
            $total_price = $_SESSION['room']['price'] * $days;
        }

        $ORDER_ID = 'ORD_' . $_SESSION['uId'] . random_int(11111, 9999999);
        $CUST_ID = $_SESSION['uId'];

        // Cấu hình MoMo
        $endpoint = "https://test-payment.momo.vn/v2/gateway/api/create";
        $partnerCode = "MOMOBKUN20180529";
        $accessKey = "klm05TvNBzhg7h7j";
        $secretKey = "at67qH6mk8w5Y1nAyMoYKMWACiEi2bsa";
        
        $orderInfo = "Thanh toán qua MoMo";
        $amount = $total_price;
        $orderId = time() . "";
        $redirectUrl = "http://localhost:3000/index.php?payment_status=success";
        $ipnUrl = "http://localhost:3000/payment_ipn.php";
        $extraData = "";
        $requestId = time() . "";
        $requestType = "captureWallet";

        // Tạo chữ ký
        $rawHash = "accessKey=" . $accessKey . 
                "&amount=" . $amount . 
                "&extraData=" . $extraData . 
                "&ipnUrl=" . $ipnUrl . 
                "&orderId=" . $orderId . 
                "&orderInfo=" . $orderInfo . 
                "&partnerCode=" . $partnerCode . 
                "&redirectUrl=" . $redirectUrl . 
                "&requestId=" . $requestId . 
                "&requestType=" . $requestType;

        $signature = hash_hmac("sha256", $rawHash, $secretKey);

        $data = [
            'partnerCode' => $partnerCode,
            'partnerName' => "Test",
            'storeId' => "MomoTestStore",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        ];

        // Lưu thông tin đặt phòng trước khi chuyển hướng
        $query1 = "INSERT INTO `booking_order`(`user_id`, `room_id`, `check_in`, `check_out`,`order_id`) VALUES (?,?,?,?,?)";
        
        insert($query1, [
            $CUST_ID, 
            $_SESSION['room']['id'],
            $frm_data['checkin'],
            $frm_data['checkout'],
            $ORDER_ID
        ], 'issss');
        
        $booking_id = mysqli_insert_id($con);
        
        $query2 = "INSERT INTO `booking_details`(`booking_id`, `room_name`, `price`, `total_pay`,
            `user_name`, `phonenum`, `address`) VALUES (?,?,?,?,?,?,?)";
        
        insert($query2, [
            $booking_id, 
            $_SESSION['room']['name'], 
            $_SESSION['room']['price'],
            $amount, 
            $frm_data['name'], 
            $frm_data['phonenum'], 
            $frm_data['address']
        ], 'issssss');
        
        // Cập nhật số lượng phòng
        $room_id = $_SESSION['room']['id'];
        $query = "SELECT quantity FROM rooms WHERE id = ?";
        $result = select($query, [$room_id], 'i');
        
        if ($result && mysqli_num_rows($result) > 0) {
            $room = mysqli_fetch_assoc($result);
            $quantity = $room['quantity'];
            $new_quantity = $quantity - 1;
            $query = "UPDATE rooms SET quantity = ? WHERE id = ?";
            update($query, [$new_quantity, $room_id], 'ii');
        }

        // Gửi request đến MoMo
        $result = execPostRequest($endpoint, json_encode($data));
        $jsonResult = json_decode($result, true);

        if (!empty($jsonResult['payUrl'])) {
            header('Location: ' . $jsonResult['payUrl']);
            die();
        } else {
            alert('error','Có lỗi xảy ra trong quá trình thanh toán');
            redirect('index.php');
        }
    }
?>