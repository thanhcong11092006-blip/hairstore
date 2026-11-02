<?php
session_start();
require_once "conn.php";

$loi = "";
$hoten = $email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoten    = trim($_POST['hoten'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $matkhau  = $_POST['matkhau'] ?? '';
    $nhaplai  = $_POST['nhaplai'] ?? '';

    if ($hoten === '' || $email === '' || $matkhau === '') {
        $loi = "Vui lòng nhập đầy đủ thông tin.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loi = "Email không hợp lệ.";
    } elseif (strlen($matkhau) < 6) {
        $loi = "Mật khẩu phải có ít nhất 6 kí tự.";
    } elseif ($matkhau !== $nhaplai) {
        $loi = "Mật khẩu nhập lại không khớp.";
    } else {
        // Kiểm tra email đã tồn tại chưa
        $stmt = mysqli_prepare($conn, "SELECT id FROM taikhoan WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            $loi = "Email này đã được đăng kí.";
        } else {
            $mahoa = password_hash($matkhau, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "INSERT INTO taikhoan (hoten, email, matkhau) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sss", $hoten, $email, $mahoa);
            mysqli_stmt_execute($stmt);

            $_SESSION['message'] = "Đăng kí thành công! Mời bạn đăng nhập.";
            header("Location: dangnhap.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng kí | Fashionista</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<?php include "header.php" ?>

<section class="container auth-section">
  <h1>Đăng kí tài khoản</h1>

  <?php if ($loi): ?>
    <p class="cart-message error"><?= htmlspecialchars($loi) ?></p>
  <?php endif; ?>

  <form method="post" class="auth-form">
    <label>Họ tên
      <input type="text" name="hoten" value="<?= htmlspecialchars($hoten) ?>" required>
    </label>
    <label>Email
      <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
    </label>
    <label>Mật khẩu
      <input type="password" name="matkhau" minlength="6" required>
    </label>
    <label>Nhập lại mật khẩu
      <input type="password" name="nhaplai" minlength="6" required>
    </label>
    <button type="submit" class="btn-submit">Đăng kí</button>
    <p>Đã có tài khoản? <a href="dangnhap.php">Đăng nhập</a></p>
  </form>
</section>

<?php include "footer.php" ?>
</body>
</html>
