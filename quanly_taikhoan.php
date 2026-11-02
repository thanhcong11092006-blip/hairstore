<?php
// Quản lý tài khoản khách hàng & nhân viên — chỉ admin
session_start();
require_once "conn.php";
$admin = yeu_cau_dang_nhap($conn, ['admin']);

$loi = "";
$VAI_TRO_QL = ['khachhang', 'nhanvien'];   // admin chỉ quản lý 2 loại này

// Lấy tài khoản (không phải admin) theo id
function lay_tk($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM taikhoan WHERE id = ? AND vaitro <> 'admin'");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanhDong = $_POST['hanhdong'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    // Mọi thao tác trên tài khoản có sẵn đều không áp dụng cho tài khoản admin
    if ($id > 0 && !lay_tk($conn, $id)) {
        chuyen_trang("quanly_taikhoan.php", "Không tìm thấy tài khoản hoặc không được phép sửa tài khoản quản trị.");
    }

    // ----- Thêm / sửa -----
    if ($hanhDong === 'luu') {
        $hoten   = trim($_POST['hoten'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $vaitro  = $_POST['vaitro'] ?? 'khachhang';
        $matkhau = $_POST['matkhau'] ?? '';

        if ($hoten === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $loi = "Vui lòng nhập họ tên và email hợp lệ.";
        } elseif (!in_array($vaitro, $VAI_TRO_QL, true)) {
            $loi = "Vai trò không hợp lệ.";
        } elseif ($id === 0 && strlen($matkhau) < 6) {
            $loi = "Mật khẩu cho tài khoản mới phải có ít nhất 6 kí tự.";
        } elseif ($id > 0 && $matkhau !== '' && strlen($matkhau) < 6) {
            $loi = "Mật khẩu mới phải có ít nhất 6 kí tự (để trống nếu không đổi).";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id FROM taikhoan WHERE email = ? AND id <> ?");
            mysqli_stmt_bind_param($stmt, "si", $email, $id);
            mysqli_stmt_execute($stmt);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
                $loi = "Email này đã được tài khoản khác sử dụng.";
            }
        }

        if (!$loi) {
            if ($id === 0) {
                $mahoa = password_hash($matkhau, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "INSERT INTO taikhoan (hoten, email, matkhau, vaitro) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssss", $hoten, $email, $mahoa, $vaitro);
                mysqli_stmt_execute($stmt);
                chuyen_trang("quanly_taikhoan.php", "Đã tạo tài khoản " . ten_vai_tro($vaitro) . " \"$email\".");
            }
            $stmt = mysqli_prepare($conn, "UPDATE taikhoan SET hoten = ?, email = ?, vaitro = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $hoten, $email, $vaitro, $id);
            mysqli_stmt_execute($stmt);
            if ($matkhau !== '') {
                $mahoa = password_hash($matkhau, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, "UPDATE taikhoan SET matkhau = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "si", $mahoa, $id);
                mysqli_stmt_execute($stmt);
            }
            chuyen_trang("quanly_taikhoan.php#tk-$id", "Đã lưu tài khoản \"$email\"" . ($matkhau !== '' ? " và đặt lại mật khẩu." : "."));
        }
    }

    // ----- Khóa / mở khóa -----
    if ($hanhDong === 'khoa') {
        $stmt = mysqli_prepare($conn,
            "UPDATE taikhoan SET trangthai = IF(trangthai = 'khoa', 'hoatdong', 'khoa') WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $tk = lay_tk($conn, $id);
        chuyen_trang("quanly_taikhoan.php#tk-$id",
            $tk['trangthai'] === 'khoa' ? "Đã khóa \"{$tk['email']}\". Tài khoản bị đăng xuất ở lần thao tác tiếp theo."
                                       : "Đã mở khóa \"{$tk['email']}\".");
    }

    // ----- Xóa (chỉ khi chưa có đơn hàng / đánh giá) -----
    if ($hanhDong === 'xoa') {
        $tk = lay_tk($conn, $id);
        $stmt = mysqli_prepare($conn,
            "SELECT (SELECT COUNT(*) FROM donhang WHERE taikhoan_id = ?) + (SELECT COUNT(*) FROM danhgia WHERE taikhoan_id = ?)");
        mysqli_stmt_bind_param($stmt, "ii", $id, $id);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0] > 0) {
            chuyen_trang("quanly_taikhoan.php#tk-$id",
                "\"{$tk['email']}\" đã có đơn hàng hoặc đánh giá nên không xóa được (để giữ lịch sử). Hãy dùng \"Khóa\".");
        }
        $stmt = mysqli_prepare($conn, "DELETE FROM taikhoan WHERE id = ? AND vaitro <> 'admin'");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        chuyen_trang("quanly_taikhoan.php", "Đã xóa tài khoản \"{$tk['email']}\".");
    }
}

$message = lay_thong_bao();

// Bộ lọc
$locVT = in_array($_GET['vaitro'] ?? '', $VAI_TRO_QL, true) ? $_GET['vaitro'] : '';
$tim   = trim($_GET['tim'] ?? '');

$dieuKien = ["tk.vaitro <> 'admin'"];
$thamSo = [];
$kieu = "";
if ($locVT !== '') { $dieuKien[] = "tk.vaitro = ?"; $thamSo[] = $locVT; $kieu .= "s"; }
if ($tim !== '')   { $dieuKien[] = "(tk.hoten LIKE ? OR tk.email LIKE ?)"; $thamSo[] = "%$tim%"; $thamSo[] = "%$tim%"; $kieu .= "ss"; }

$stmt = mysqli_prepare($conn,
    "SELECT tk.*, (SELECT COUNT(*) FROM donhang dh WHERE dh.taikhoan_id = tk.id) AS sodon
     FROM taikhoan tk WHERE " . implode(" AND ", $dieuKien) . " ORDER BY tk.vaitro DESC, tk.ngaytao DESC");
if ($thamSo) {
    mysqli_stmt_bind_param($stmt, $kieu, ...$thamSo);
}
mysqli_stmt_execute($stmt);
$dstk = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$dem = [];
foreach (mysqli_fetch_all(mysqli_query($conn,
    "SELECT vaitro, COUNT(*) n FROM taikhoan WHERE vaitro <> 'admin' GROUP BY vaitro"), MYSQLI_ASSOC) as $r) {
    $dem[$r['vaitro']] = (int)$r['n'];
}

// Form thêm / sửa
$sua = isset($_GET['sua']) ? lay_tk($conn, (int)$_GET['sua']) : null;
$f = $loi ? array_merge($sua ?? [], $_POST) : ($sua ?? ['vaitro' => $_GET['them'] ?? 'nhanvien']);
$dangSua = (int)($f['id'] ?? 0) > 0;
$moForm  = $loi || $sua || isset($_GET['them']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý tài khoản | hair.store</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<?php include "header.php" ?>

<section class="container order-section">
  <?php include "quanly_nav.php"; ?>
  <div class="page-head">
    <h1>Tài khoản</h1>
    <?php if (!$moForm): ?>
      <div class="order-actions">
        <a href="quanly_taikhoan.php?them=nhanvien" class="btn-dark">+ Thêm nhân viên</a>
        <a href="quanly_taikhoan.php?them=khachhang" class="btn-outline">+ Thêm khách hàng</a>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($message): ?>
    <p class="cart-message"><?= e($message) ?></p>
  <?php endif; ?>

  <?php if ($moForm): ?>
  <div class="account-card form-card">
    <h2><?= $dangSua ? 'Sửa tài khoản' : 'Thêm tài khoản' ?></h2>
    <?php if ($loi): ?><p class="cart-message error"><?= e($loi) ?></p><?php endif; ?>
    <form method="post" class="auth-form form-grid" autocomplete="off">
      <input type="hidden" name="hanhdong" value="luu">
      <input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
      <label>Họ tên
        <input type="text" name="hoten" value="<?= e($f['hoten'] ?? '') ?>" maxlength="150" required>
      </label>
      <label>Email
        <input type="email" name="email" value="<?= e($f['email'] ?? '') ?>" maxlength="150" required>
      </label>
      <label>Vai trò
        <select name="vaitro">
          <?php foreach ($VAI_TRO_QL as $vt): ?>
            <option value="<?= $vt ?>" <?= ($f['vaitro'] ?? '') === $vt ? 'selected' : '' ?>><?= ten_vai_tro($vt) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><?= $dangSua ? 'Đặt lại mật khẩu' : 'Mật khẩu' ?>
        <input type="password" name="matkhau" minlength="6" autocomplete="new-password" <?= $dangSua ? '' : 'required' ?>>
        <span class="field-hint"><?= $dangSua ? 'Để trống nếu không đổi.' : 'Ít nhất 6 kí tự.' ?></span>
      </label>
      <div class="form-actions span-2">
        <a href="quanly_taikhoan.php" class="btn-outline">Hủy</a>
        <button type="submit" class="btn-submit"><?= $dangSua ? 'Lưu thay đổi' : 'Tạo tài khoản' ?></button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="toolbar">
    <div class="filter-chips" role="group" aria-label="Lọc theo vai trò">
      <a href="quanly_taikhoan.php" <?= $locVT === '' ? 'aria-current="true"' : '' ?>>Tất cả <span><?= array_sum($dem) ?></span></a>
      <a href="quanly_taikhoan.php?vaitro=nhanvien" <?= $locVT === 'nhanvien' ? 'aria-current="true"' : '' ?>>Nhân viên <span><?= $dem['nhanvien'] ?? 0 ?></span></a>
      <a href="quanly_taikhoan.php?vaitro=khachhang" <?= $locVT === 'khachhang' ? 'aria-current="true"' : '' ?>>Khách hàng <span><?= $dem['khachhang'] ?? 0 ?></span></a>
    </div>
    <form class="search-form" method="get" role="search">
      <?php if ($locVT): ?><input type="hidden" name="vaitro" value="<?= $locVT ?>"><?php endif; ?>
      <input type="search" name="tim" value="<?= e($tim) ?>" placeholder="Tìm theo tên, email" aria-label="Tìm tài khoản">
      <button type="submit">Tìm</button>
    </form>
  </div>

  <?php if (!$dstk): ?>
    <div class="empty-state"><p>Không có tài khoản nào phù hợp.</p></div>
  <?php else: ?>
  <div class="table-scroll">
  <table class="order-table">
    <tr><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Ngày tạo</th><th>Số đơn</th><th></th></tr>
    <?php foreach ($dstk as $tk): $biKhoa = $tk['trangthai'] === 'khoa'; ?>
    <tr id="tk-<?= $tk['id'] ?>" class="<?= $biKhoa ? 'row-muted' : '' ?>">
      <td><strong><?= e($tk['hoten']) ?></strong></td>
      <td><?= e($tk['email']) ?></td>
      <td><span class="badge <?= $tk['vaitro'] === 'nhanvien' ? 'badge-info' : '' ?>"><?= ten_vai_tro($tk['vaitro']) ?></span></td>
      <td><span class="badge <?= $biKhoa ? 'badge-bad' : 'badge-ok' ?>"><?= $biKhoa ? 'Bị khóa' : 'Hoạt động' ?></span></td>
      <td class="nowrap"><?= date("d/m/Y", strtotime($tk['ngaytao'])) ?></td>
      <td><?= $tk['vaitro'] === 'khachhang' ? (int)$tk['sodon'] : '—' ?></td>
      <td>
        <div class="order-actions">
          <a href="quanly_taikhoan.php?sua=<?= $tk['id'] ?>" class="btn-outline btn-sm">Sửa</a>
          <form method="post">
            <input type="hidden" name="hanhdong" value="khoa">
            <input type="hidden" name="id" value="<?= $tk['id'] ?>">
            <button type="submit" class="btn-sm <?= $biKhoa ? 'btn-outline' : 'btn-danger' ?>"><?= $biKhoa ? 'Mở khóa' : 'Khóa' ?></button>
          </form>
          <form method="post">
            <input type="hidden" name="hanhdong" value="xoa">
            <input type="hidden" name="id" value="<?= $tk['id'] ?>">
            <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Xóa hẳn tài khoản #<?= $tk['id'] ?>?')">Xóa</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <?php endif; ?>
</section>

<?php include "footer.php" ?>
</body>
</html>
