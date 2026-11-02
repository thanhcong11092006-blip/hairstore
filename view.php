<?php
session_start();
require_once "conn.php";

$user     = yeu_cau_dang_nhap($conn);
$laQuanLy = la_quan_ly();
$message  = lay_thong_bao();

// Bộ lọc trạng thái (chỉ cho quản lý)
$loc = $laQuanLy && in_array($_GET['loc'] ?? '', DS_TRANG_THAI, true) ? $_GET['loc'] : '';

if ($laQuanLy) {
    if ($loc !== '') {
        $stmt = mysqli_prepare($conn, "SELECT * FROM donhang WHERE trangthai = ? ORDER BY ngaydat DESC");
        mysqli_stmt_bind_param($stmt, "s", $loc);
        mysqli_stmt_execute($stmt);
        $kq = mysqli_stmt_get_result($stmt);
    } else {
        $kq = mysqli_query($conn, "SELECT * FROM donhang ORDER BY ngaydat DESC");
    }
    // Đếm số đơn theo trạng thái để hiện trên bộ lọc
    $demTT = [];
    foreach (mysqli_fetch_all(mysqli_query($conn, "SELECT trangthai, COUNT(*) n FROM donhang GROUP BY trangthai"), MYSQLI_ASSOC) as $r) {
        $demTT[$r['trangthai']] = (int)$r['n'];
    }
} else {
    $stmt = mysqli_prepare($conn,
        "SELECT * FROM donhang WHERE taikhoan_id = ? OR email = ? ORDER BY ngaydat DESC");
    mysqli_stmt_bind_param($stmt, "is", $user['id'], $user['email']);
    mysqli_stmt_execute($stmt);
    $kq = mysqli_stmt_get_result($stmt);
}
$donhangs = mysqli_fetch_all($kq, MYSQLI_ASSOC);

// Chi tiết các đơn (+ đánh giá nếu có)
$chitiet = [];
if ($donhangs) {
    $ids = implode(",", array_map('intval', array_column($donhangs, 'id')));
    $kq = mysqli_query($conn,
        "SELECT ct.*, sp.ten, sp.hinhanh,
                dg.id AS dg_id, dg.sosao, dg.noidung, dg.phanhoi
         FROM chitietdonhang ct
         JOIN sanpham sp ON sp.id = ct.sanpham_id
         LEFT JOIN danhgia dg ON dg.donhang_id = ct.donhang_id AND dg.sanpham_id = ct.sanpham_id
         WHERE ct.donhang_id IN ($ids)");
    while ($row = mysqli_fetch_assoc($kq)) {
        $chitiet[$row['donhang_id']][] = $row;
    }
}

$tenThanhToan = ['COD' => 'Thanh toán khi nhận hàng', 'ChuyenKhoan' => 'Chuyển khoản'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $laQuanLy ? 'Quản lý đơn hàng' : 'Đơn hàng của tôi' ?> | hair.store</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<?php include "header.php" ?>

<section class="container order-section">
  <?php if ($laQuanLy) include "quanly_nav.php"; ?>
  <h1><?= $laQuanLy ? "Quản lý đơn hàng" : "Đơn hàng của tôi" ?></h1>

  <?php if ($message): ?>
    <p class="cart-message"><?= e($message) ?></p>
  <?php endif; ?>

  <?php if ($laQuanLy): ?>
    <div class="filter-chips" role="group" aria-label="Lọc theo trạng thái">
      <a href="view.php" <?= $loc === '' ? 'aria-current="true"' : '' ?>>Tất cả <span><?= array_sum($demTT) ?></span></a>
      <?php foreach (DS_TRANG_THAI as $tt): ?>
        <a href="view.php?loc=<?= urlencode($tt) ?>" <?= $loc === $tt ? 'aria-current="true"' : '' ?>>
          <?= $tt ?> <span><?= $demTT[$tt] ?? 0 ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$donhangs): ?>
    <div class="empty-state">
      <?php if ($laQuanLy): ?>
        <p>Không có đơn hàng nào<?= $loc ? " ở trạng thái \"$loc\"" : "" ?>.</p>
      <?php else: ?>
        <p>Bạn chưa có đơn hàng nào.</p>
        <a href="sanpham.php" class="btn-dark">Mua sắm ngay</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
  <div class="table-scroll">
  <table class="order-table">
    <tr>
      <th>Mã đơn</th>
      <th>Ngày đặt</th>
      <?php if ($laQuanLy): ?><th>Khách hàng</th><?php endif; ?>
      <th>Sản phẩm</th>
      <th>Tổng tiền</th>
      <th>Trạng thái</th>
    </tr>
    <?php foreach ($donhangs as $dh): $tt = $dh['trangthai']; ?>
    <tr>
      <td><strong>#<?= $dh['id'] ?></strong></td>
      <td><?= date("d/m/Y H:i", strtotime($dh['ngaydat'])) ?></td>
      <?php if ($laQuanLy): ?>
      <td>
        <strong><?= e($dh['hoten']) ?></strong><br>
        <?= e($dh['email']) ?><br>
        <span class="muted"><?= e($dh['diachi']) ?></span>
      </td>
      <?php endif; ?>
      <td>
        <?php foreach ($chitiet[$dh['id']] ?? [] as $ct): ?>
          <div class="order-item">
            <img src="<?= e($ct['hinhanh']) ?>" alt="<?= e($ct['ten']) ?>"
                 width="56" height="56" style="width:56px;height:56px;object-fit:cover;">
            <div class="order-item-info">
              <span class="order-item-name"><?= e($ct['ten']) ?></span>
              <span class="order-item-meta">SL: <?= $ct['soluong'] ?> × <?= tien($ct['dongia']) ?></span>

              <?php if (!$laQuanLy && $tt === TT_DA_GIAO): ?>
                <?php if ($ct['dg_id']): ?>
                  <div class="review-mine">
                    <span class="stars" aria-label="<?= $ct['sosao'] ?> sao"><?= sao($ct['sosao']) ?></span>
                    <span><?= e($ct['noidung']) ?></span>
                    <?php if ($ct['phanhoi']): ?>
                      <div class="shop-reply"><strong>Cửa hàng:</strong> <?= e($ct['phanhoi']) ?></div>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <details class="review-form">
                    <summary>Đánh giá sản phẩm</summary>
                    <form action="admin.php" method="post">
                      <input type="hidden" name="action" value="danhgia">
                      <input type="hidden" name="donhang_id" value="<?= $dh['id'] ?>">
                      <input type="hidden" name="sanpham_id" value="<?= $ct['sanpham_id'] ?>">
                      <fieldset class="star-input">
                        <legend>Số sao</legend>
                        <?php for ($s = 5; $s >= 1; $s--): ?>
                          <input type="radio" id="sao-<?= $dh['id'] ?>-<?= $ct['sanpham_id'] ?>-<?= $s ?>" name="sosao" value="<?= $s ?>" <?= $s === 5 ? 'checked' : '' ?> required>
                          <label for="sao-<?= $dh['id'] ?>-<?= $ct['sanpham_id'] ?>-<?= $s ?>" title="<?= $s ?> sao">★</label>
                        <?php endfor; ?>
                      </fieldset>
                      <textarea name="noidung" rows="3" maxlength="1000" required placeholder="Sản phẩm thế nào? Chất vải, size, giao hàng…"></textarea>
                      <button type="submit" class="btn-dark btn-sm">Gửi đánh giá</button>
                    </form>
                  </details>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <span class="order-item-meta"><?= $tenThanhToan[$dh['thanhtoan']] ?? e($dh['thanhtoan']) ?></span>
      </td>
      <td><strong><?= tien($dh['tongtien']) ?></strong></td>
      <td>
        <span class="badge <?= lop_trang_thai($tt) ?>"><?= e($tt) ?></span>

        <?php if ($laQuanLy && isset(CHUYEN_TRANG_THAI_NV[$tt])): ?>
          <div class="order-actions">
            <?php foreach (CHUYEN_TRANG_THAI_NV[$tt] as $moi => $tenNut):
              $nguyHiem = in_array($moi, TT_HOAN_KHO, true); ?>
              <form action="admin.php" method="post">
                <input type="hidden" name="action" value="chuyen_trang_thai">
                <input type="hidden" name="id" value="<?= $dh['id'] ?>">
                <input type="hidden" name="trangthai" value="<?= e($moi) ?>">
                <input type="hidden" name="loc" value="<?= e($loc) ?>">
                <button type="submit" class="btn-sm <?= $nguyHiem ? 'btn-danger' : 'btn-dark' ?>"
                  <?= $nguyHiem ? "onclick=\"return confirm('" . e($tenNut) . " đơn #{$dh['id']}? Hàng sẽ được trả lại kho.')\"" : '' ?>>
                  <?= e($tenNut) ?>
                </button>
              </form>
            <?php endforeach; ?>
          </div>
        <?php elseif (!$laQuanLy && $tt === TT_CHO_XAC_NHAN): ?>
          <form action="admin.php" method="post" class="order-actions">
            <input type="hidden" name="action" value="chuyen_trang_thai">
            <input type="hidden" name="id" value="<?= $dh['id'] ?>">
            <input type="hidden" name="trangthai" value="<?= TT_DA_HUY ?>">
            <button type="submit" class="btn-sm btn-danger" onclick="return confirm('Hủy đơn #<?= $dh['id'] ?>?')">Hủy đơn</button>
          </form>
        <?php endif; ?>
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
