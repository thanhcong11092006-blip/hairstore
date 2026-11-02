<?php
session_start();
unset($_SESSION['user']);   // giữ lại giỏ hàng, chỉ đăng xuất tài khoản
session_regenerate_id(true);
header("Location: dangnhap.php");
exit;
