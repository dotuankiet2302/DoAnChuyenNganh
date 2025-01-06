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

    // Cấu hình ZaloPay
    $config = [
        "app_id" => 2553,
        "key1" => "PcY4iZIKFCIdgZvA6ueMcMHHUbRLYjPL",
        "key2" => "kLtgPl8HHhfvMuDHPwKfgfsY4Ydm9eIz",
        "endpoint" => "https://sb-openapi.zalopay.vn/v2/create"
    ];

    if (isset($_POST['payment_method']) && $_POST['payment_method'] === 'zalopay') {
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

        $embeddata = '{}'; // Merchant's data
        $items = '[]'; // Merchant's data
        $transID = rand(0,1000000); // Random trans id

        $order = [
            "app_id" => $config["app_id"],
            "app_time" => round(microtime(true) * 1000), // miliseconds
            "app_trans_id" => date("ymd") . "_" . $transID,
            "app_user" => "user123",
            "item" => $items,
            "embed_data" => $embeddata,
            "amount" => $total_price,
            "description" => "Thanh toán đơn đặt phòng #$ORDER_ID",
            "bank_code" => "zalopayapp"
        ];

        // appid|app_trans_id|appuser|amount|apptime|embeddata|item
        $data = $order["app_id"] . "|" . $order["app_trans_id"] . "|" . $order["app_user"] . "|" . $order["amount"]
            . "|" . $order["app_time"] . "|" . $order["embed_data"] . "|" . $order["item"];
        $order["mac"] = hash_hmac("sha256", $data, $config["key1"]);

        $context = stream_context_create([
            "http" => [
                "header" => "Content-type: application/x-www-form-urlencoded\r\n",
                "method" => "POST",
                "content" => http_build_query($order)
            ]
        ]);

        // Lưu thông tin đặt phòng
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
            $total_price, $frm_data['name'], $frm_data['phonenum'], $frm_data['address']
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

        // Gửi request đến ZaloPay
        $resp = file_get_contents($config["endpoint"], false, $context);
        $result = json_decode($resp, true);

        if ($result['return_code'] == 1) {
            header('Location: ' . $result['order_url']);
            die();
        } else {
            alert('error', 'Có lỗi xảy ra trong quá trình thanh toán');
            redirect('index.php');
        }
    }
?>