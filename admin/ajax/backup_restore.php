<?php
  // Thêm vào đầu file backup_restore.php để kiểm tra quyền
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  require('../inc/db_config.php');
  require('../inc/essentials.php');
  adminLogin();

  if(isset($_POST['backup'])) 
  {
    $date = date("Y-m-d");
    $time = date("H-i-s");
    $filename = "backup_".$date."_".$time.".sql";
    
    // Path để lưu file backup
    $backup_path = "../backup_files/";
    if(!is_dir($backup_path)){
      mkdir($backup_path);
    }

    // Đường dẫn đến mysqldump trong XAMPP
    $mysqldump = "C:/xampp/mysql/bin/mysqldump.exe";

    // Command để backup MySQL database
    if($pass == "") {
        // Nếu không có mật khẩu
        $command = "\"{$mysqldump}\" --user={$uname} --host={$hname} {$db} > \"{$backup_path}{$filename}\"";
    } else {
        // Nếu có mật khẩu
        $command = "\"{$mysqldump}\" --user={$uname} --password={$pass} --host={$hname} {$db} > \"{$backup_path}{$filename}\"";
    }
    // Thêm error logging
    $output = array();
    $return_var = 0;

    exec($command, $output, $return_var);
    
    if($return_var == 0) {
      echo 1;
    }
    else {
      echo 0;
    }
  }

  if(isset($_POST['restore']))
  {
    if($_FILES['backup_file']['error'] == 0) {
      $filename = $_FILES['backup_file']['name'];
      $tmp_name = $_FILES['backup_file']['tmp_name'];
      
      if(pathinfo($filename, PATHINFO_EXTENSION) == 'sql') {
        $mysql = "C:/xampp/mysql/bin/mysql.exe";
        
        // Sử dụng trực tiếp file tạm thời thay vì di chuyển file
        if($pass == "") {
            $command = "\"{$mysql}\" --user={$uname} --host={$hname} {$db} < \"{$tmp_name}\"";
        } else {
            $command = "\"{$mysql}\" --user={$uname} --password={$pass} --host={$hname} {$db} < \"{$tmp_name}\"";
        }
        
        $output = array();
        $return_var = 0;
        
        exec($command . " 2>&1", $output, $return_var);
        
        if($return_var == 0) {
          echo 1; // Restore thành công
        } else {
          echo "Error during restore: " . implode("\n", $output);
        }
      }
      else {
        echo "Error: Invalid file type";
      }
    }
    else {
      echo "Error: File upload failed";
    }
  }
?>