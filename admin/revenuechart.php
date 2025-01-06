<?php
    require('inc/essentials.php');
    require('inc/db_config.php');
    adminLogin();

    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;
    $filter_month = $_GET['filter_month'] ?? null; // Dạng YYYY-MM
    $filter_quarter = $_GET['filter_quarter'] ?? null; // Giá trị 1, 2, 3, 4
    $filter_year = $_GET['filter_year'] ?? null; // Giá trị YYYY
    
    $chart_data = [];
    
    // Lọc theo khoảng ngày cụ thể
    if ($start_date && $end_date) {
        $query = "SELECT DATE(check_in) as date, SUM(trans_amt) as revenue 
                  FROM booking_order 
                  WHERE DATE(check_in) BETWEEN '$start_date' AND '$end_date' 
                  GROUP BY DATE(check_in)";
        $result = mysqli_query($con, $query);
    
        while ($row = mysqli_fetch_assoc($result)) {
            $chart_data[] = ['label' => $row['date'], 'value' => $row['revenue']];
        }
    }
    // Lọc theo tháng
    elseif ($filter_month) {
        $query = "SELECT DAY(check_in) as day, SUM(trans_amt) as revenue 
                FROM booking_order 
                WHERE DATE_FORMAT(check_in, '%Y-%m') = '$filter_month' 
                GROUP BY day";
        $result = mysqli_query($con, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            $chart_data[] = ['label' => 'Ngày ' . $row['day'], 'value' => $row['revenue']];
        }
    }

    // Lọc theo quý
    elseif ($filter_quarter && $filter_year) {
        // Xác định tháng bắt đầu và kết thúc của quý
        $quarter_months = [
            '1' => ['01', '03'], // Quý 1: Từ tháng 1 đến tháng 3
            '2' => ['04', '06'], // Quý 2: Từ tháng 4 đến tháng 6
            '3' => ['07', '09'], // Quý 3: Từ tháng 7 đến tháng 9
            '4' => ['10', '12'], // Quý 4: Từ tháng 10 đến tháng 12
        ];

        $start_month = $quarter_months[$filter_quarter][0];
        $end_month = $quarter_months[$filter_quarter][1];

        $query = "SELECT CONCAT(YEAR(check_in), '-', MONTH(check_in)) as month, SUM(trans_amt) as revenue 
                FROM booking_order 
                WHERE YEAR(check_in) = '$filter_year' 
                AND MONTH(check_in) BETWEEN '$start_month' AND '$end_month'
                GROUP BY MONTH(check_in)";
        $result = mysqli_query($con, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            $chart_data[] = ['label' => 'Tháng ' . substr($row['month'], -2), 'value' => $row['revenue']];
        }
    }

    // Lọc theo năm
    elseif ($filter_year) {
        $query = "SELECT MONTH(check_in) as month, SUM(trans_amt) as revenue 
                FROM booking_order 
                WHERE YEAR(check_in) = '$filter_year' 
                GROUP BY month";
        $result = mysqli_query($con, $query);

        while ($row = mysqli_fetch_assoc($result)) {
            $chart_data[] = ['label' => 'Tháng ' . $row['month'], 'value' => $row['revenue']];
        }
    }

    

?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>THỐNG KÊ DOANH THU</title>
    <?php require('inc/links.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Căn giữa biểu đồ và giảm kích thước */
        .chart-container {
            width: 70%; /* Giảm kích thước biểu đồ xuống 70% */
            margin: 0 auto; /* Căn giữa biểu đồ theo chiều ngang */
        }

        canvas {
            display: block; /* Đảm bảo canvas nằm trong khung */
            max-width: 100%; /* Để biểu đồ tự điều chỉnh trong container */
            height: auto; /* Giữ tỷ lệ khi giảm kích thước */
        }
        .filter-form {
            gap: 10px; /* Khoảng cách giữa các phần tử */
            flex-wrap: wrap; /* Để tự xuống dòng nếu màn hình nhỏ */
        }

        /* Giảm khoảng cách giữa nhãn và input */
        .filter-item {
            display: flex;
            align-items: center; /* Căn giữa nhãn và input theo chiều dọc */
            gap: 5px; /* Khoảng cách giữa nhãn và input */
            margin: 0; /* Bỏ khoảng cách mặc định */
        }

        .filter-item label {
            font-size: 20px; /* Nhỏ gọn nhưng vẫn rõ ràng */
            font-weight: 500; /* Font nhấn nhẹ, không quá đậm */
            margin: 0; /* Không thêm khoảng cách */
        }

        .filter-item input {
            height: 36px; /* Chiều cao nhất quán */
            padding: 5px; /* Đệm trong input */
            font-size: 14px;
            border: 1px solid #ccc; /* Viền nhẹ */
            border-radius: 4px; /* Bo góc cho đẹp */
        }

        button[type="submit"] {
            height: 36px; /* Chiều cao bằng input */
            padding: 0 15px; /* Khoảng cách nội bộ */
            font-size: 14px;
        }
    </style>
</head>
<body class="bg-light">
    <?php require('inc/header.php'); ?>

    <div class="container-fluid" id="main-content">
        <div class="row">
            <div class="col-lg-10 ms-auto p-4 overflow-hidden">
                <h3 class="mb-4">Thống kê doanh thu</h3>

                <!-- Bộ lọc -->
                <form action="" method="GET" class="d-flex align-items-center justify-content-center gap-2 mb-4 filter-form">
                    <div class="filter-item">
                        <label for="start_date">Từ ngày:</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo $_GET['start_date'] ?? ''; ?>">
                    </div>
                    <div class="filter-item">
                        <label for="end_date">Đến ngày:</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo $_GET['end_date'] ?? ''; ?>">
                    </div>
                    <div class="filter-item">
                        <label for="filter_month">Tháng:</label>
                        <input type="month" name="filter_month" id="filter_month" value="<?php echo $_GET['filter_month'] ?? ''; ?>">
                    </div>
                    <div class="filter-item">
                        <label for="filter_quarter">Quý:</label>
                        <select name="filter_quarter" id="filter_quarter">
                            <option value="">--Chọn Quý--</option>
                            <option value="1" <?php echo (isset($_GET['filter_quarter']) && $_GET['filter_quarter'] === '1') ? 'selected' : ''; ?>>Quý 1</option>
                            <option value="2" <?php echo (isset($_GET['filter_quarter']) && $_GET['filter_quarter'] === '2') ? 'selected' : ''; ?>>Quý 2</option>
                            <option value="3" <?php echo (isset($_GET['filter_quarter']) && $_GET['filter_quarter'] === '3') ? 'selected' : ''; ?>>Quý 3</option>
                            <option value="4" <?php echo (isset($_GET['filter_quarter']) && $_GET['filter_quarter'] === '4') ? 'selected' : ''; ?>>Quý 4</option>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label for="filter_year">Năm:</label>
                        <input type="number" name="filter_year" id="filter_year" placeholder="YYYY" value="<?php echo $_GET['filter_year'] ?? ''; ?>">
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Lọc</button>
                        <a href="export_statistics.php?type=revenue&<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                            Xuất Excel
                        </a>
                    </div>
                </form>





                <!-- Biểu đồ -->
                <div class="row">
                    <div class="col-md-12">
                        <h5>Biểu đồ Đặt phòng</h5>
                        <canvas id="revenueChart" width="1300px" height="400px"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dữ liệu từ PHP
        const chartData = <?php echo json_encode($chart_data); ?>;

        // Chuẩn bị dữ liệu cho Chart.js
        const labels = chartData.map(data => data.label);
        const values = chartData.map(data => data.value);

        // Kiểm tra dữ liệu
        console.log("Chart Data:", chartData);
        console.log("Labels:", labels);
        console.log("Values:", values);

        // Vẽ biểu đồ
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu',
                    data: values,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    x: {
                        title: {
                            display: true, // Hiển thị tiêu đề trục x
                            text: 'Thời gian', // Tiêu đề trục x
                            font: {
                                size: 16 // Kích thước font
                            },
                            padding: { top: 10 } // Khoảng cách với trục
                        }
                    },
                    y: {
                        title: {
                            display: true, // Hiển thị tiêu đề trục y
                            text: 'Doanh thu', // Tiêu đề trục y
                            font: {
                                size: 16 // Kích thước font
                            },
                            padding: { right: 10 } // Khoảng cách với trục
                        },
                        beginAtZero: true // Bắt đầu từ 0
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });

    </script>

    <?php require('inc/scripts.php'); ?>
</body>
</html>
