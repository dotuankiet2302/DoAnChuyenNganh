<?php
  require('inc/essentials.php');
  require('inc/db_config.php');
  adminLogin();

  $from_date = $_GET['from_date'] ?? null;
  $to_date = $_GET['to_date'] ?? null;
  $filter_month = $_GET['filter_month'] ?? null;
  $filter_quarter = $_GET['filter_quarter'] ?? null;
  $filter_year = $_GET['filter_year'] ?? null;
  
  // Mặc định lấy tất cả dữ liệu
  $where_clause = "1=1";
  
  // Lọc theo khoảng ngày
  if ($from_date && $to_date) {
      $where_clause .= " AND DATE(check_in) BETWEEN '$from_date' AND '$to_date'";
  }
  // Lọc theo tháng
  elseif ($filter_month) {
      $where_clause .= " AND DATE_FORMAT(check_in, '%Y-%m') = '$filter_month'";
  }
  // Lọc theo quý
  elseif ($filter_quarter && $filter_year) {
      $quarter_months = [
          '1' => ['01', '03'], // Quý 1: Tháng 1 - 3
          '2' => ['04', '06'], // Quý 2: Tháng 4 - 6
          '3' => ['07', '09'], // Quý 3: Tháng 7 - 9
          '4' => ['10', '12'], // Quý 4: Tháng 10 - 12
      ];
      $start_month = $quarter_months[$filter_quarter][0];
      $end_month = $quarter_months[$filter_quarter][1];
      $where_clause .= " AND DATE_FORMAT(check_in, '%Y-%m') BETWEEN '$filter_year-$start_month' AND '$filter_year-$end_month'";
  }
  // Lọc theo năm
  elseif ($filter_year) {
      $where_clause .= " AND YEAR(check_in) = '$filter_year'";
  }
  
  // Truy vấn dữ liệu thống kê
  $data_query = "SELECT 
                      DATE(check_in) as date, 
                      COUNT(*) as booking_count, 
                      SUM(CASE WHEN refund = 0 THEN 1 ELSE 0 END) as refund_count 
                  FROM 
                      booking_order 
                  WHERE 
                      $where_clause 
                  GROUP BY 
                      DATE(check_in);
  ";
  $data_result = mysqli_query($con, $data_query);
  $data = [];
  while ($row = mysqli_fetch_assoc($data_result)) {
      $data[] = $row;
  }
  
  // Chuyển dữ liệu thành JSON
  $data_json = json_encode($data);
  
?>





<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>THỐNG KÊ</title>
  <?php require('inc/links.php'); ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .filter-form {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .filter-item {
      display: flex;
      align-items: center;
      gap: 5px;
    }
    .filter-item input, .filter-item button {
      height: 36px;
    }
    canvas {
      max-width: 200%;
      margin: 0 auto;
      display: block;
    }
  </style>
</head>
<body class="bg-light">
  <?php require('inc/header.php'); ?>

  <div class="container-fluid" id="main-content">
    <div class="row">
      <div class="col-lg-10 ms-auto p-4 overflow-hidden">
        <h3>THỐNG KÊ</h3>

        <!-- Bộ lọc -->
        <form action="" method="GET" class="filter-form">
          <!-- Bộ lọc theo ngày -->
          <div class="filter-item">
              <label for="from_date">Từ ngày:</label>
              <input type="date" name="from_date" id="from_date" value="<?php echo $_GET['from_date'] ?? ''; ?>">
          </div>
          <div class="filter-item">
              <label for="to_date">Đến ngày:</label>
              <input type="date" name="to_date" id="to_date" value="<?php echo $_GET['to_date'] ?? ''; ?>">
          </div>
          <!-- Bộ lọc theo tháng -->
          <div class="filter-item">
              <label for="filter_month">Tháng:</label>
              <input type="month" name="filter_month" id="filter_month" value="<?php echo $_GET['filter_month'] ?? ''; ?>">
          </div>
          <!-- Bộ lọc theo quý -->
          <div class="filter-item">
              <label for="filter_quarter">Quý:</label>
              <select name="filter_quarter" id="filter_quarter">
                  <option value="">--Chọn Quý--</option>
                  <option value="1" <?php echo ($_GET['filter_quarter'] ?? '') == '1' ? 'selected' : ''; ?>>Quý 1</option>
                  <option value="2" <?php echo ($_GET['filter_quarter'] ?? '') == '2' ? 'selected' : ''; ?>>Quý 2</option>
                  <option value="3" <?php echo ($_GET['filter_quarter'] ?? '') == '3' ? 'selected' : ''; ?>>Quý 3</option>
                  <option value="4" <?php echo ($_GET['filter_quarter'] ?? '') == '4' ? 'selected' : ''; ?>>Quý 4</option>
              </select>
          </div>
          <!-- Bộ lọc theo năm -->
          <div class="filter-item">
              <label for="filter_year">Năm:</label>
              <input type="number" name="filter_year" id="filter_year" placeholder="YYYY" value="<?php echo $_GET['filter_year'] ?? ''; ?>">
          </div>
          <div>
              <button type="submit" class="btn btn-primary">Lọc</button>
              <a href="export_statistics.php?type=bookings&<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                Xuất Excel
            </a>
          </div>
      </form>


        <!-- Biểu đồ -->
        <div class="row">
            <div class="col-md-12">
                <h5>Biểu đồ doanh thu</h5>
                <canvas id="statisticChart" width="1300px" height="500px"></canvas>
            </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Dữ liệu từ PHP
    const data = <?php echo $data_json; ?>;

    // Xử lý dữ liệu cho Chart.js
    const labels = data.map(item => item.date);
    const bookingValues = data.map(item => item.booking_count);
    const refundValues = data.map(item => item.refund_count);

    // Tạo biểu đồ
    const ctx = document.getElementById('statisticChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Số lần đặt',
                    data: bookingValues,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Số lần hủy',
                    data: refundValues,
                    backgroundColor: 'rgba(255, 99, 132, 0.6)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    enabled: true
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Thời gian',
                        font: {
                            size: 16
                        },
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Số lượng',
                        font: {
                            size: 16
                        },
                    }
                }
            }
        }
    });

  </script>

  <?php require('inc/scripts.php'); ?>
</body>
</html>
