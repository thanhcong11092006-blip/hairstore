<?php
// Kết nối MySQL (cấu hình mặc định của XAMPP)
$servername = "localhost";
$username   = "root";
$password   = "";          // XAMPP mặc định không có mật khẩu
$dbname     = "hairstore";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Kết nối CSDL thất bại: " . mysqli_connect_error());
}

// Đảm bảo tiếng Việt hiển thị đúng
mysqli_set_charset($conn, "utf8mb4");

// Hàm dùng chung + tự nâng cấp CSDL nếu còn bản cũ
require_once __DIR__ . "/chung.php";
require_once __DIR__ . "/nangcap_csdl.php";
