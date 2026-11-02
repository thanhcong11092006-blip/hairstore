<?php
session_start();
require_once "conn.php";

$loi = "";
$email = "";
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Đã đăng nhập rồi thì chuyển sang trang đơn hàng
if (!empty($_SESSION['user'])) {
    header("Location: " . trang_chu_theo_vai_tro($_SESSION['user']['vaitro']));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = trim($_POST['email'] ?? '');
    $matkhau = $_POST['matkhau'] ?? '';

    $stmt = mysqli_prepare($conn, "SELECT * FROM taikhoan WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $tk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($tk && password_verify($matkhau, $tk['matkhau']) && ($tk['trangthai'] ?? '') === 'khoa') {
        $loi = "Tài khoản này đã bị khóa. Vui lòng liên hệ cửa hàng.";
    } elseif ($tk && password_verify($matkhau, $tk['matkhau'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'     => $tk['id'],
            'hoten'  => $tk['hoten'],
            'email'  => $tk['email'],
            'vaitro' => $tk['vaitro'],
        ];
        $_SESSION['message'] = "Xin chào " . $tk['hoten'] . "!";
        header("Location: " . trang_chu_theo_vai_tro($tk['vaitro']));
        exit;
    } else {
        $loi = "Email hoặc mật khẩu không đúng.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập | Fashionista</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<?php include "header.php" ?>

<section class="container auth-section">
  <h1>Đăng nhập</h1>

  <?php if ($message): ?>
    <p class="cart-message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>
  <?php if ($loi): ?>
    <p class="cart-message error"><?= htmlspecialchars($loi) ?></p>
  <?php endif; ?>

  <form method="post" class="auth-form">
    <label>Email
      <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
    </label>
    <label>Mật khẩu
      <input type="password" name="matkhau" required>
    </label>
    <button type="submit" class="btn-submit">Đăng nhập</button>
    <p>Chưa có tài khoản? <a href="dangki.php">Đăng kí ngay</a></p>
  </form>
</section>

<?php include "footer.php" ?>
</body>
</html>
