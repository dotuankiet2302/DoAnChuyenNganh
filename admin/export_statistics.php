<?php
require('inc/essentials.php');
require('inc/db_config.php');
require '../vendor/autoload.php';

adminLogin();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Lấy các tham số filter và loại thống kê
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$filter_month = $_GET['filter_month'] ?? null;
$filter_quarter = $_GET['filter_quarter'] ?? null;
$filter_year = $_GET['filter_year'] ?? null;
$type = $_GET['type'] ?? 'revenue'; // 'revenue' hoặc 'bookings'

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Thiết lập tiêu đề dựa vào loại thống kê
if ($type == 'revenue') {
    $sheet->setTitle('Thống kê doanh thu');
    $sheet->setCellValue('A1', 'THỐNG KÊ DOANH THU');
    // Thiết lập header cho doanh thu
    $sheet->setCellValue('A2', 'STT');
    $sheet->setCellValue('B2', 'Username');
    $sheet->setCellValue('C2', 'Số điện thoại');
    $sheet->setCellValue('D2', 'Địa chỉ');
    $sheet->setCellValue('E2', 'Thời gian');
    $sheet->setCellValue('F2', 'Doanh thu (VNĐ)');
} else {
    $sheet->setTitle('Thống kê đặt phòng');
    $sheet->setCellValue('A1', 'THỐNG KÊ ĐẶT PHÒNG');
    // Thiết lập header cho đặt phòng
    $sheet->setCellValue('A2', 'STT');
    $sheet->setCellValue('B2', 'Username');
    $sheet->setCellValue('C2', 'Số điện thoại');
    $sheet->setCellValue('D2', 'Địa chỉ');
    $sheet->setCellValue('E2', 'Thời gian');
    $sheet->setCellValue('F2', 'Số lần đặt');
    $sheet->setCellValue('G2', 'Số lần hủy');
}

// Xây dựng where clause dựa vào filter
$where_clause = "1=1";
if ($start_date && $end_date) {
    $where_clause .= " AND DATE(bo.check_in) BETWEEN '$start_date' AND '$end_date'";
} elseif ($filter_month) {
    $where_clause .= " AND DATE_FORMAT(bo.check_in, '%Y-%m') = '$filter_month'";
} elseif ($filter_quarter && $filter_year) {
    $quarter_months = [
        '1' => ['01', '03'],
        '2' => ['04', '06'],
        '3' => ['07', '09'],
        '4' => ['10', '12']
    ];
    $start_month = $quarter_months[$filter_quarter][0];
    $end_month = $quarter_months[$filter_quarter][1];
    $where_clause .= " AND DATE_FORMAT(bo.check_in, '%Y-%m') BETWEEN '$filter_year-$start_month' AND '$filter_year-$end_month'";
} elseif ($filter_year) {
    $where_clause .= " AND YEAR(bo.check_in) = '$filter_year'";
}

// Query dựa vào loại thống kê
if ($type == 'revenue') {
    $query = "SELECT DATE(bo.check_in) as time_unit, 
                     SUM(bo.trans_amt) as revenue,
                     uc.name as username, 
                     uc.phonenum, 
                     uc.address 
              FROM booking_order bo
              INNER JOIN user_cred uc ON bo.user_id = uc.id
              WHERE $where_clause
              GROUP BY DATE(bo.check_in), uc.name, uc.phonenum, uc.address
              ORDER BY time_unit";
} else {
    $query = "SELECT DATE(bo.check_in) as time_unit,
                     COUNT(*) as total_bookings,
                     SUM(CASE WHEN bo.refund = 1 THEN 1 ELSE 0 END) as refund_count,
                     uc.name as username,
                     uc.phonenum,
                     uc.address
              FROM booking_order bo
              INNER JOIN user_cred uc ON bo.user_id = uc.id
              WHERE $where_clause
              GROUP BY DATE(bo.check_in), uc.name, uc.phonenum, uc.address
              ORDER BY time_unit";
}

// Thực thi query và điền dữ liệu
$result = mysqli_query($con, $query);
$row = 3;
$stt = 1;

while ($data = mysqli_fetch_assoc($result)) {
    $sheet->setCellValue('A' . $row, $stt);
    $sheet->setCellValue('B' . $row, $data['username']);
    $sheet->setCellValue('C' . $row, $data['phonenum']);
    $sheet->setCellValue('D' . $row, $data['address']);
    $sheet->setCellValue('E' . $row, $data['time_unit']);
    
    if ($type == 'revenue') {
        $sheet->setCellValue('F' . $row, $data['revenue']);
    } else {
        $total_bookings = $data['total_bookings'];
        $refund_count = $data['refund_count'];
        $successful_bookings = $total_bookings - $refund_count;
        
        $sheet->setCellValue('F' . $row, $successful_bookings);
        $sheet->setCellValue('G' . $row, $refund_count);
    }
    
    $row++;
    $stt++;
}

// Tự động điều chỉnh độ rộng cột
$lastColumn = ($type == 'revenue') ? 'F' : 'G';
foreach (range('A', $lastColumn) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Định dạng tiêu đề
$sheet->getStyle('A1:' . $lastColumn . '2')->getFont()->setBold(true);
$sheet->getStyle('A1:' . $lastColumn . '2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Headers để download file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$filename = ($type == 'revenue') ? 'thong_ke_doanh_thu_' : 'thong_ke_dat_phong_';
header('Content-Disposition: attachment;filename="' . $filename . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// Xuất file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
?>