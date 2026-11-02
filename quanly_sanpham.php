<?php
// Quản lý sản phẩm & tồn kho — nhân viên và admin
session_start();
require_once "conn.php";
yeu_cau_dang_nhap($conn, ['nhanvien', 'admin']);

$loi = "";

// Lưu ảnh tải lên vào thư mục Media, trả về đường dẫn (hoặc null nếu lỗi)
function luu_anh($file, &$loi) {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        $loi = "Ảnh tải lên bị lỗi hoặc lớn hơn 5MB.";
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    $duoi = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2] ?? 0] ?? null;
    if (!$duoi) {
        $loi = "Chỉ nhận ảnh JPG, PNG, WEBP hoặc GIF.";
        return null;
    }
    $ten = "Media/sp_" . date("YmdHis") . "_" . bin2hex(random_bytes(3)) . ".$duoi";
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . "/" . $ten)) {
        $loi = "Không lưu được ảnh vào thư mục Media.";
        return null;
    }
    return $ten;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hanhDong = $_POST['hanhdong'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    // ----- Thêm / sửa sản phẩm -----
    if ($hanhDong === 'luu') {
        $ten     = trim($_POST['ten'] ?? '');
        $gia     = (int)($_POST['gia'] ?? 0);
        $danhmuc = trim($_POST['danhmuc'] ?? '');
        $soluong = (int)($_POST['soluong'] ?? 0);
        $dangban = isset($_POST['dangban']) ? 1 : 0;

        if ($ten === '' || $gia <= 0 || $soluong < 0) {
            $loi = "Vui lòng nhập tên, giá lớn hơn 0 và tồn kho không âm.";
        } else {
            $anh = luu_anh($_FILES['hinhanh'] ?? [], $loi);
            if (!$loi && $id === 0 && !$anh) {
                $loi = "Vui lòng chọn ảnh cho sản phẩm mới.";
            }
        }

        if (!$loi) {
            if ($id === 0) {
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO sanpham (ten, gia, hinhanh, danhmuc, soluong, dangban) VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sissii", $ten, $gia, $anh, $danhmuc, $soluong, $dangban);
                mysqli_stmt_execute($stmt);
                chuyen_trang("quanly_sanpham.php", "Đã thêm sản phẩm \"$ten\".");
            }
            if ($anh) {
                $stmt = mysqli_prepare($conn,
                    "UPDATE sanpham SET ten = ?, gia = ?, hinhanh = ?, danhmuc = ?, soluong = ?, dangban = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sissiii", $ten, $gia, $anh, $danhmuc, $soluong, $dangban, $id);
            } else {
                $stmt = mysqli_prepare($conn,
                    "UPDATE sanpham SET ten = ?, gia = ?, danhmuc = ?, soluong = ?, dangban = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "sisiii", $ten, $gia, $danhmuc, $soluong, $dangban, $id);
            }
            mysqli_stmt_execute($stmt);
            chuyen_trang("quanly_sanpham.php", "Đã lưu thay đổi cho \"$ten\".");
        }
    }

    // ----- Cập nhật nhanh tồn kho -----
    if ($hanhDong === 'kho') {
        $soluong = max(0, (int)($_POST['soluong'] ?? 0));
        $stmt = mysqli_prepare($conn, "UPDATE sanpham SET soluong = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "ii", $soluong, $id);
        mysqli_stmt_execute($stmt);
        chuyen_trang("quanly_sanpham.php#sp-$id", "Đã cập nhật tồn kho sản phẩm #$id thành $soluong.");
    }

    // ----- Ngừng bán / mở bán lại -----
    if ($hanhDong === 'dangban') {
        $stmt = mysqli_prepare($conn, "UPDATE sanpham SET dangban = 1 - dangban WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        chuyen_trang("quanly_sanpham.php#sp-$id", "Đã đổi trạng thái bán của sản phẩm #$id.");
    }

    // ----- Xóa (chỉ khi chưa có trong đơn hàng nào) -----
    if ($hanhDong === 'xoa') {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM chitietdonhang WHERE sanpham_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        if (mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0] > 0) {
            chuyen_trang("quanly_sanpham.php#sp-$id",
                "Sản phẩm #$id đã có trong đơn hàng nên không xóa được (để giữ lịch sử). Hãy chọn \"Ngừng bán\".");
        }
        $stmt = mysqli_prepare($conn, "DELETE FROM sanpham WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        chuyen_trang("quanly_sanpham.php", "Đã xóa sản phẩm #$id.");
    }
}

$message = lay_thong_bao();

// Sản phẩm đang sửa (nếu có)
$sua = null;
if (isset($_GET['sua'])) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM sanpham WHERE id = ?");
    $idSua = (int)$_GET['sua'];
    mysqli_stmt_bind_param($stmt, "i", $idSua);
    mysqli_stmt_execute($stmt);
    $sua = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}
// Giữ lại dữ liệu đã nhập khi có lỗi
$f = $loi ? array_merge($sua ?? [], $_POST, ['dangban' => isset($_POST['dangban']) ? 1 : 0]) : ($sua ?? ['dangban' => 1]);
$dangSua = !empty($f['id']) && (int)$f['id'] > 0;
$moForm  = $loi || $sua || isset($_GET['them']);

$dssp = mysqli_fetch_all(mysqli_query($conn,
    "SELECT sp.*, (SELECT COALESCE(SUM(ct.soluong), 0) FROM chitietdonhang ct
                   JOIN donhang dh ON dh.id = ct.donhang_id
                   WHERE ct.sanpham_id = sp.id AND dh.trangthai = 'Đã giao') AS daban
     FROM sanpham sp ORDER BY sp.dangban DESC, sp.soluong ASC, sp.id"), MYSQLI_ASSOC);
$dsDanhMuc = array_column(mysqli_fetch_all(mysqli_query($conn,
    "SELECT DISTINCT danhmuc FROM sanpham WHERE danhmuc <> '' ORDER BY danhmuc"), MYSQLI_ASSOC), 'danhmuc');
$sapHet = count(array_filter($dssp, fn($s) => $s['dangban'] && $s['soluong'] <= 5));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sản phẩm & tồn kho | hair.store</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<?php include "header.php" ?>

<section class="container order-section">
  <?php include "quanly_nav.php"; ?>
  <div class="page-head">
    <h1>Sản phẩm & tồn kho</h1>
    <?php if (!$moForm): ?>
      <a href="quanly_sanpham.php?them=1" class="btn-dark">+ Thêm sản phẩm</a>
    <?php endif; ?>
  </div>

  <?php if ($message): ?>
    <p class="cart-message"><?= e($message) ?></p>
  <?php endif; ?>
  <?php if ($sapHet): ?>
    <p class="cart-message warn"><?= $sapHet ?> sản phẩm đang bán sắp hết hàng (còn ≤ 5). Chúng được xếp lên đầu danh sách.</p>
  <?php endif; ?>

  <?php if ($moForm): ?>
  <div class="account-card form-card">
    <h2><?= $dangSua ? 'Sửa sản phẩm #' . (int)$f['id'] : 'Thêm sản phẩm mới' ?></h2>
    <?php if ($loi): ?><p class="cart-message error"><?= e($loi) ?></p><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="auth-form form-grid">
      <input type="hidden" name="hanhdong" value="luu">
      <input type="hidden" name="id" value="<?= (int)($f['id'] ?? 0) ?>">
      <label class="span-2">Tên sản phẩm
        <input type="text" name="ten" value="<?= e($f['ten'] ?? '') ?>" maxlength="255" required>
      </label>
      <label>Giá bán (đồng)
        <input type="number" name="gia" value="<?= e($f['gia'] ?? '') ?>" min="1000" step="1000" required>
      </label>
      <label>Tồn kho
        <input type="number" name="soluong" value="<?= e($f['soluong'] ?? 0) ?>" min="0" required>
      </label>
      <label>Danh mục
        <input type="text" name="danhmuc" value="<?= e($f['danhmuc'] ?? '') ?>" list="ds-danhmuc" maxlength="100">
        <datalist id="ds-danhmuc">
          <?php foreach ($dsDanhMuc as $dm): ?><option value="<?= e($dm) ?>"><?php endforeach; ?>
        </datalist>
      </label>
      <label>Ảnh sản phẩm <?= $dangSua ? '<span class="field-hint">(để trống nếu giữ ảnh cũ)</span>' : '' ?>
        <input type="file" name="hinhanh" accept="image/jpeg,image/png,image/webp,image/gif" <?= $dangSua ? '' : 'required' ?>>
      </label>
      <?php if ($dangSua && !empty($f['hinhanh'])): ?>
        <img src="<?= e($f['hinhanh']) ?>" alt="Ảnh hiện tại" class="thumb-lg">
      <?php endif; ?>
      <label class="check span-2">
        <input type="checkbox" name="dangban" value="1" <?= !empty($f['dangban']) ? 'checked' : '' ?>>
        Đang bán (hiện trên website)
      </label>
      <div class="form-actions span-2">
        <a href="quanly_sanpham.php" class="btn-outline">Hủy</a>
        <button type="submit" class="btn-submit"><?= $dangSua ? 'Lưu thay đổi' : 'Thêm sản phẩm' ?></button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div class="table-scroll">
  <table class="order-table">
    <tr>
      <th>Sản phẩm</th>
      <th>Danh mục</th>
      <th>Giá</th>
      <th>Tồn kho</th>
      <th>Đã bán</th>
      <th>Trạng thái</th>
      <th></th>
    </tr>
    <?php foreach ($dssp as $sp):
      $trangThai = !$sp['dangban'] ? ['Ngừng bán', 'badge-bad'] : ($sp['soluong'] <= 0 ? ['Hết hàng', 'badge-bad'] : ($sp['soluong'] <= 5 ? ['Sắp hết', 'badge-wait'] : ['Đang bán', 'badge-ok'])); ?>
    <tr id="sp-<?= $sp['id'] ?>" class="<?= $sp['dangban'] ? '' : 'row-muted' ?>">
      <td>
        <div class="order-item">
          <img src="<?= e($sp['hinhanh']) ?>" alt="" width="56" height="56" style="width:56px;height:56px;object-fit:cover;">
          <div class="order-item-info">
            <span class="order-item-name"><?= e($sp['ten']) ?></span>
            <span class="order-item-meta">#<?= $sp['id'] ?></span>
          </div>
        </div>
      </td>
      <td><?= e($sp['danhmuc']) ?></td>
      <td><?= tien($sp['gia']) ?></td>
      <td>
        <form method="post" class="stock-form">
          <input type="hidden" name="hanhdong" value="kho">
          <input type="hidden" name="id" value="<?= $sp['id'] ?>">
          <input type="number" name="soluong" value="<?= $sp['soluong'] ?>" min="0" aria-label="Tồn kho <?= e($sp['ten']) ?>">
          <button type="submit" class="btn-outline btn-sm">Lưu</button>
        </form>
      </td>
      <td><?= (int)$sp['daban'] ?></td>
      <td><span class="badge <?= $trangThai[1] ?>"><?= $trangThai[0] ?></span></td>
      <td>
        <div class="order-actions">
          <a href="quanly_sanpham.php?sua=<?= $sp['id'] ?>" class="btn-outline btn-sm">Sửa</a>
          <form method="post">
            <input type="hidden" name="hanhdong" value="dangban">
            <input type="hidden" name="id" value="<?= $sp['id'] ?>">
            <button type="submit" class="btn-outline btn-sm"><?= $sp['dangban'] ? 'Ngừng bán' : 'Mở bán' ?></button>
          </form>
          <form method="post">
            <input type="hidden" name="hanhdong" value="xoa">
            <input type="hidden" name="id" value="<?= $sp['id'] ?>">
            <button type="submit" class="btn-danger btn-sm" onclick="return confirm('Xóa hẳn sản phẩm #<?= $sp['id'] ?>?')">Xóa</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
</section>

<?php include "footer.php" ?>
</body>
</html>
