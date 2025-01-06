<?php
    require('inc/essentials.php');
    require('inc/db_config.php');
    adminLogin();


    // Top 10 khách hàng theo doanh thu trong ngày hôm nay (kèm số lần đặt phòng)
    $top_customers_day_query = mysqli_query($con, 
        "SELECT 
        b.user_name, 
        SUM(o.trans_amt) AS total_revenue, 
        COUNT(o.booking_id) AS total_orders
        FROM booking_order o
        JOIN booking_details b ON o.booking_id = b.booking_id
        WHERE DATE(o.check_in) = CURDATE()
        GROUP BY b.user_name
        ORDER BY total_revenue DESC
        LIMIT 10"
    );

    $top_customers_day = [];
    while ($row = mysqli_fetch_assoc($top_customers_day_query)) {
        $top_customers_day[] = $row;
    }

    // Top 10 khách hàng theo doanh thu trong tháng này (kèm số lần đặt phòng)
    $top_customers_month_query = mysqli_query($con,
        "SELECT 
        b.user_name, 
        SUM(o.trans_amt) AS total_revenue, 
        COUNT(o.booking_id) AS total_orders
        FROM booking_order o
        JOIN booking_details b ON o.booking_id = b.booking_id
        WHERE DATE_FORMAT(check_in, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
        GROUP BY b.user_name
        ORDER BY total_revenue DESC
        LIMIT 10"
    );

    $top_customers_month = [];
    while ($row = mysqli_fetch_assoc($top_customers_month_query)) {
        $top_customers_month[] = $row;
    }

    // Top 10 khách hàng theo doanh thu trong năm nay (kèm số lần đặt phòng)
    $top_customers_year_query = mysqli_query($con,
        "SELECT 
        b.user_name, 
        SUM(o.trans_amt) AS total_revenue, 
        COUNT(o.booking_id) AS total_orders
        FROM booking_order o
        JOIN booking_details b ON o.booking_id = b.booking_id
        WHERE YEAR(check_in) = YEAR(CURDATE())
        GROUP BY b.user_name
        ORDER BY total_revenue DESC
        LIMIT 10"
    );

    $top_customers_year = [];
    while ($row = mysqli_fetch_assoc($top_customers_year_query)) {
        $top_customers_year[] = $row;
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THỐNG KÊ</title>
    <?php require('inc/links.php'); ?>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #000;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        h2 {
            color: #333;
        }
    </style>
</head>
<body class="bg-light">
    <?php require('inc/header.php'); ?>
    <div class="container-fluid" id="main-content">
        <div class="row">
            <div class="col-lg-10 ms-auto p-4 overflow-hidden">
                <h3>Top 10 Khách Hàng Thân Thiết</h3>
                
                <!-- Thêm các biểu đồ hiển thị theo hàng ngang -->
                <div class="row">
                    <div class="col-12">
                        <!-- Theo Ngày -->
                        <h2 class="text-center">Thống Kê Theo Ngày Hôm Nay</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên Khách Hàng</th>
                                    <th>Tổng Doanh Thu</th>
                                    <th>Số Lần Đặt</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $stt = 1;
                                foreach ($top_customers_day as $customer) {
                                    $total_revenue = number_format($customer['total_revenue'], 0, ',', '.');
                                    echo "<tr>
                                        <td>{$stt}</td>
                                        <td>{$customer['user_name']}</td>
                                        <td>{$total_revenue} VNĐ</td>
                                        <td>{$customer['total_orders']}</td>
                                    </tr>";
                                    $stt++;
                                }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="row mt-5">
                    <div class="col-12">
                        <!-- Theo Tháng -->
                        <h2 class="text-center">Thống Kê Theo Tháng Hiện Tại</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên Khách Hàng</th>
                                    <th>Tổng Doanh Thu</th>
                                    <th>Số Lần Đặt</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $stt = 1;
                                foreach ($top_customers_month as $customer) {
                                    $total_revenue = number_format($customer['total_revenue'], 0, ',', '.');
                                    echo "<tr>
                                        <td>{$stt}</td>
                                        <td>{$customer['user_name']}</td>
                                        <td>{$total_revenue} VNĐ</td>
                                        <td>{$customer['total_orders']}</td>
                                    </tr>";
                                    $stt++;
                                }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="row mt-5">
                    <div class="col-12">
                        <!-- Theo Năm -->
                        <h2 class="text-center">Thống Kê Theo Năm Hiện Tại</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên Khách Hàng</th>
                                    <th>Tổng Doanh Thu</th>
                                    <th>Số Lần Đặt</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                                $stt = 1;
                                foreach ($top_customers_year as $customer) {
                                    $total_revenue = number_format($customer['total_revenue'], 0, ',', '.');
                                    echo "<tr>
                                        <td>{$stt}</td>
                                        <td>{$customer['user_name']}</td>
                                        <td>{$total_revenue} VNĐ</td>
                                        <td>{$customer['total_orders']}</td>
                                    </tr>";
                                    $stt++;
                                }
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>



