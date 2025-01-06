<?php
  require('inc/essentials.php');
  require('inc/db_config.php');
  adminLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Sao lưu & Phục hồi</title>
  <?php require('inc/links.php'); ?>
</head>
<body class="bg-light">

  <?php require('inc/header.php'); ?>

  <div class="container-fluid" id="main-content">
    <div class="row">
      <div class="col-lg-10 ms-auto p-4 overflow-hidden">
        <h3 class="mb-4">SAO LƯU VÀ PHỤC HỒI DỮ LIỆU</h3>

        <!-- Phần này chứa một "card" (thẻ chứa nội dung), bao gồm tiêu đề "Sao lưu dữ liệu" và một nút "Tạo bản sao lưu".
Khi nút được nhấn, hàm JavaScript backup_data() sẽ được gọi để thực hiện quá trình sao lưu. -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="card-title m-0">Sao lưu dữ liệu</h5>
              <button type="button" onclick="backup_data()" class="btn btn-dark shadow-none btn-sm">
                <i class="bi bi-download"></i> Tạo bản sao lưu
              </button>
            </div>
            <p class="card-text">Tạo bản sao lưu cơ sở dữ liệu để đảm bảo an toàn dữ liệu.</p>
          </div>
        </div>

        <!-- Phần này chứa một card khác cho việc phục hồi dữ liệu. Người dùng sẽ chọn tệp sao lưu .sql để tải lên và phục hồi dữ liệu.
Biểu mẫu (<form>) chứa một trường để người dùng chọn tệp và một nút để gửi yêu cầu phục hồi dữ liệu. -->
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between mb-3">
              <h5 class="card-title m-0">Phục hồi dữ liệu</h5>
            </div>
            <form id="restore_form">
              <div class="mb-3">
                <label class="form-label">Chọn file sao lưu (.sql)</label>
                <input type="file" name="backup_file" accept=".sql" class="form-control shadow-none" required>
              </div>
              <button type="submit" class="btn btn-primary shadow-none">
                <i class="bi bi-arrow-clockwise"></i> Phục hồi
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  <?php require('inc/scripts.php'); ?>

  <script>
    // Hàm backup_data() gửi một yêu cầu AJAX (qua XMLHttpRequest) đến tệp ajax/backup_restore.php để thực hiện sao lưu dữ liệu.
    // Nếu phản hồi từ server là 1, một thông báo thành công sẽ hiển thị. Ngược lại, nếu có lỗi, thông báo lỗi sẽ hiển thị.
    function backup_data() {
      let xhr = new XMLHttpRequest();
      xhr.open("POST", "ajax/backup_restore.php", true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

      xhr.onload = function() {
        if(this.responseText == 1) {
          alert('success', 'Đã tạo bản sao lưu thành công!');
        }
        else {
          alert('error', 'Không thể tạo bản sao lưu!');
        }
      }

      xhr.send('backup');
    }

//     Khi form phục hồi được gửi, hàm JavaScript này ngăn chặn hành vi mặc định (gửi form).
// Nó tạo ra một đối tượng FormData chứa dữ liệu của form và gửi yêu cầu AJAX đến ajax/backup_restore.php với các dữ liệu cần thiết để phục hồi.
// Nếu phục hồi thành công (phản hồi là 1), nó sẽ hiển thị thông báo thành công và làm mới form. Nếu có lỗi, một thông báo lỗi sẽ hiển thị.
    restore_form.addEventListener('submit', function(e) {
      e.preventDefault();

      let data = new FormData(this);
      data.append('restore', '');

      let xhr = new XMLHttpRequest();
      xhr.open("POST", "ajax/backup_restore.php", true);

      xhr.onload = function() {
        let response = this.responseText;
        if(response == 1) {
          alert('success', 'Phục hồi dữ liệu thành công!');
          restore_form.reset();
        }
        else {
          alert('error', 'Không thể phục hồi dữ liệu: ' + response);
        }
      }

      xhr.send(data);
    });
  </script>

</body>
</html>