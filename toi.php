<?php
session_start();
require_once "conn.php";

// Trang "Tôi" dành cho khách hàng và nhân viên (admin không dùng)
yeu_cau_dang_nhap($conn, ['khachhang', 'nhanvien']);

$id = (int)$_SESSION['user']['id'];

// Lấy thông tin mới nhất từ CSDL
function lay_tai_khoan($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM taikhoan WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

$tk = lay_tai_khoan($conn, $id);
if (!$tk) {                       // tài khoản đã bị xóa
    unset($_SESSION['user']);
    header("Location: dangnhap.php");
    exit;
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$loiThongTin = "";
$loiMatKhau  = "";
$hoten = $tk['hoten'];
$email = $tk['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ===== ĐỔI THÔNG TIN CÁ NHÂN =====
    if ($action === 'thongtin') {
        $hoten = trim($_POST['hoten'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($hoten === '' || $email === '') {
            $loiThongTin = "Vui lòng nhập đầy đủ họ tên và email.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $loiThongTin = "Email không hợp lệ.";
        } else {
            // Email không được trùng với tài khoản khác
            $stmt = mysqli_prepare($conn, "SELECT id FROM taikhoan WHERE email = ? AND id <> ?");
            mysqli_stmt_bind_param($stmt, "si", $email, $id);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                $loiThongTin = "Email này đã được tài khoản khác sử dụng.";
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE taikhoan SET hoten = ?, email = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ssi", $hoten, $email, $id);
                mysqli_stmt_execute($stmt);

                $_SESSION['user']['hoten'] = $hoten;
                $_SESSION['user']['email'] = $email;
                $_SESSION['message'] = "Đã lưu thông tin cá nhân.";
                header("Location: toi.php");
                exit;
            }
        }
    }

    // ===== ĐỔI MẬT KHẨU =====
    if ($action === 'matkhau') {
        $cu      = $_POST['matkhau_cu'] ?? '';
        $moi     = $_POST['matkhau_moi'] ?? '';
        $nhaplai = $_POST['nhaplai'] ?? '';

        if (!password_verify($cu, $tk['matkhau'])) {
            $loiMatKhau = "Mật khẩu hiện tại không đúng.";
        } elseif (strlen($moi) < 6) {
            $loiMatKhau = "Mật khẩu mới phải có ít nhất 6 kí tự.";
        } elseif ($moi !== $nhaplai) {
            $loiMatKhau = "Mật khẩu nhập lại không khớp.";
        } elseif ($moi === $cu) {
            $loiMatKhau = "Mật khẩu mới phải khác mật khẩu hiện tại.";
        } else {
            $mahoa = password_hash($moi, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE taikhoan SET matkhau = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "si", $mahoa, $id);
            mysqli_stmt_execute($stmt);

            session_regenerate_id(true);
            $_SESSION['message'] = "Đã đổi mật khẩu thành công.";
            header("Location: toi.php");
            exit;
        }
    }
}

$laQuanLy = la_quan_ly();
$chuCaiDau = mb_strtoupper(mb_substr($tk['hoten'], 0, 1, 'UTF-8'), 'UTF-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tài khoản của tôi | hair.store</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<?php include "header.php" ?>

<section class="container account-page">
  <div class="account-hero">
    <div class="avatar" aria-hidden="true"><?= htmlspecialchars($chuCaiDau) ?></div>
    <div>
      <h1><?= htmlspecialchars($tk['hoten']) ?></h1>
      <p class="account-meta">
        <?= htmlspecialchars($tk['email']) ?> ·
        <?= ten_vai_tro($tk['vaitro']) ?> ·
        Tham gia <?= date("d/m/Y", strtotime($tk['ngaytao'])) ?>
      </p>
    </div>
    <a href="view.php" class="btn-outline account-orders"><?= $laQuanLy ? 'Quản lý cửa hàng' : 'Đơn hàng của tôi' ?></a>
  </div>

  <?php if ($message): ?>
    <p class="cart-message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <div class="account-layout">
    <!-- Thông tin cá nhân -->
    <div class="account-card">
      <h2>Thông tin cá nhân</h2>
      <?php if ($loiThongTin): ?>
        <p class="cart-message error"><?= htmlspecialchars($loiThongTin) ?></p>
      <?php endif; ?>
      <form method="post" class="auth-form">
        <input type="hidden" name="action" value="thongtin">
        <label>Họ tên
          <input type="text" name="hoten" value="<?= htmlspecialchars($hoten) ?>" maxlength="150" required autocomplete="name">
        </label>
        <label>Email
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" maxlength="150" required autocomplete="email">
          <span class="field-hint">Email này dùng để đăng nhập.</span>
        </label>
        <button type="submit" class="btn-submit">Lưu thay đổi</button>
      </form>
    </div>

    <!-- Đổi mật khẩu -->
    <div class="account-card">
      <h2>Đổi mật khẩu</h2>
      <?php if ($loiMatKhau): ?>
        <p class="cart-message error"><?= htmlspecialchars($loiMatKhau) ?></p>
      <?php endif; ?>
      <form method="post" class="auth-form">
        <input type="hidden" name="action" value="matkhau">
        <input type="email" name="username" value="<?= htmlspecialchars($tk['email']) ?>" autocomplete="username" hidden>
        <label>Mật khẩu hiện tại
          <input type="password" name="matkhau_cu" required autocomplete="current-password">
        </label>
        <label>Mật khẩu mới
          <input type="password" name="matkhau_moi" minlength="6" required autocomplete="new-password">
          <span class="field-hint">Ít nhất 6 kí tự.</span>
        </label>
        <label>Nhập lại mật khẩu mới
          <input type="password" name="nhaplai" minlength="6" required autocomplete="new-password">
        </label>
        <button type="submit" class="btn-submit">Đổi mật khẩu</button>
      </form>
    </div>
  </div>
</section>

<?php include "footer.php" ?>
</body>
</html>
