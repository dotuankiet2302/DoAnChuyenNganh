<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn Phương Thức Thanh Toán</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .payment-method {
            display: flex;
            align-items: center;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .payment-method:hover {
            background-color: #f5f5f5;
        }

        .payment-method img {
            width: 50px;
            height: 50px;
            margin-right: 20px;
        }

        @media (max-width: 576px) {
            .payment-method {
                flex-direction: column;
                align-items: flex-start;
            }
            .payment-method img {
                margin-bottom: 10px;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Chọn Phương Thức Thanh Toán</h2>
        <div class="payment-options">

            <form action="pay_now.php" method="POST" id="booking_form">
                <input type="hidden" name="payment_method" value="visa">
                <div class="payment-method" onclick="this.parentNode.submit();">
                    <img src="visa.png">
                    <span>Thanh toán bằng thẻ Visa</span>
                    <!-- <button id="book-now" name="pay_now" class="btn w-100 text-white custom-bg shadow-none mb-1" disabled>Đặt Ngay</button> -->
                </div>
            </form>

            <form action="pay_now.php" method="POST">
                <input type="hidden" name="pay_now" value="1">
                <!-- <input type="hidden" name="payment_method" value="momo"> -->
                <!-- <div class="payment-method" onclick="this.parentNode.submit();">
                    <img src="momo.png" alt="Momo">
                    <span>Thanh toán bằng VnPay</span>
                </div> -->
                <button class="btn btn-primary">Thanh toán bằng Stripe</button>
            </form><br>


            <form action="congthanhtoan.php" method="POST" id="payment-form">
                <input type="hidden" name="congthanhtoan" value="1">
                <input type="hidden" name="days" id="days_input" value="">  <!-- Thêm input hidden cho số ngày -->
                <input type="hidden" name="payment_method" value="vnpay"> <!-- Giá trị cố định cho VnPay -->
                <button type="submit" class="btn btn-success">Thanh toán bằng VnPay</button>
            </form>

            <form action="momo_payment.php" method="POST">
                <input type="hidden" name="payment_method" value="momo">
                <div class="payment-method" onclick="this.parentNode.submit();">
                    <img src="momo.png" alt="Momo">
                    <span>Thanh toán bằng Momo</span>
                </div>
            </form>

            <form action="zalopay_payment.php" method="POST">
                <input type="hidden" name="payment_method" value="zalopay">
                <div class="payment-method" onclick="this.parentNode.submit();">
                    <img src="zalopay.png" alt="ZaloPay">
                    <span>Thanh toán bằng ZaloPay</span>
                </div>
            </form>

        </div>
    </div>
</body>
</html>
<script>
    // Lấy số ngày từ URL (giả sử bạn đã truyền nó từ confirm_booking.php)
    const urlParams = new URLSearchParams(window.location.search);
    const days = urlParams.get('days');

    // Gán giá trị số ngày vào input hidden
    if (days) {
        document.getElementById('days_input').value = days;
    } else {
        // Xử lý trường hợp không có số ngày (ví dụ: hiển thị thông báo lỗi)
        console.error("Không tìm thấy số ngày đặt phòng.");
        alert("Đã xảy ra lỗi. Vui lòng thử lại.");
        // Hoặc chuyển hướng người dùng về trang trước
        // window.history.back();
    }
</script>