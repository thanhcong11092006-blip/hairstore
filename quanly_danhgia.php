<?php
// Quản lý đánh giá — nhân viên / admin trả lời đánh giá của khách
session_start();
require_once "conn.php";
$tk = yeu_cau_dang_nhap($conn, ['nhanvien', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanhdong'] ?? '') === 'traloi') {
    $id      = (int)($_POST['id'] ?? 0);
    $phanhoi = trim($_POST['phanhoi'] ?? '');
    $loc     = $_POST['loc'] ?? '';
    if ($phanhoi === '') {
        chuyen_trang("quanly_danhgia.php?loc=" . urlencode($loc) . "#dg-$id", "Nội dung trả lời không được để trống.");
    }
    $stmt = mysqli_prepare($conn,
        "UPDATE danhgia SET phanhoi = ?, nguoiphanhoi_id = ?, ngayphanhoi = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "sii", $phanhoi, $tk['id'], $id);
    mysqli_stmt_execute($stmt);
    chuyen_trang("quanly_danhgia.php?loc=" . urlencode($loc), "Đã trả lời đánh giá #$id. Khách hàng sẽ thấy phản hồi trong mục Đơn hàng.");
}

$message = lay_thong_bao();
$loc = ($_GET['loc'] ?? 'chuatraloi') === 'tatca' ? 'tatca' : 'chuatraloi';

$where = $loc === 'chuatraloi' ? "WHERE dg.phanhoi IS NULL" : "";
$dsdg = mysqli_fetch_all(mysqli_query($conn,
    "SELECT dg.*, sp.ten AS tensp, sp.hinhanh, tk.hoten AS tenkhach, nv.hoten AS tennv
     FROM danhgia dg
     JOIN sanpham sp ON sp.id = dg.sanpham_id
     JOIN taikhoan tk ON tk.id = dg.taikhoan_id
     LEFT JOIN taikhoan nv ON nv.id = dg.nguoiphanhoi_id
     $where
     ORDER BY dg.phanhoi IS NULL DESC, dg.ngaydanhgia DESC"), MYSQLI_ASSOC);

$tong = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*), SUM(phanhoi IS NULL), ROUND(AVG(sosao), 1) FROM danhgia"));
[$tongDG, $chuaTraLoi, $saoTB] = [(int)$tong[0], (int)$tong[1], $tong[2] ?? 0];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đánh giá | hair.store</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<?php include "header.php" ?>

<section class="container order-section">
  <?php include "quanly_nav.php"; ?>
  <div class="page-head">
    <h1>Đánh giá của khách hàng</h1>
    <p class="muted"><?= $tongDG ?> đánh giá · trung bình <span class="stars"><?= sao(round($saoTB)) ?></span> <?= $saoTB ?>/5</p>
  </div>

  <?php if ($message): ?>
    <p class="cart-message"><?= e($message) ?></p>
  <?php endif; ?>

  <div class="filter-chips" role="group" aria-label="Lọc đánh giá">
    <a href="quanly_danhgia.php?loc=chuatraloi" <?= $loc === 'chuatraloi' ? 'aria-current="true"' : '' ?>>Chưa trả lời <span><?= $chuaTraLoi ?></span></a>
    <a href="quanly_danhgia.php?loc=tatca" <?= $loc === 'tatca' ? 'aria-current="true"' : '' ?>>Tất cả <span><?= $tongDG ?></span></a>
  </div>

  <?php if (!$dsdg): ?>
    <div class="empty-state">
      <p><?= $loc === 'chuatraloi' ? 'Không còn đánh giá nào chờ trả lời.' : 'Chưa có đánh giá nào.' ?></p>
    </div>
  <?php endif; ?>

  <div class="review-list">
    <?php foreach ($dsdg as $dg): ?>
    <article class="review-card" id="dg-<?= $dg['id'] ?>">
      <img src="<?= e($dg['hinhanh']) ?>" alt="" class="review-thumb">
      <div class="review-body">
        <div class="review-meta">
          <strong><?= e($dg['tensp']) ?></strong>
          <span class="muted">· <?= e($dg['tenkhach']) ?> · đơn #<?= $dg['donhang_id'] ?> · <?= date("d/m/Y", strtotime($dg['ngaydanhgia'])) ?></span>
        </div>
        <div class="stars" aria-label="<?= $dg['sosao'] ?> sao"><?= sao($dg['sosao']) ?></div>
        <p class="review-text"><?= nl2br(e($dg['noidung'])) ?></p>

        <?php if ($dg['phanhoi']): ?>
          <div class="shop-reply">
            <strong><?= e($dg['tennv'] ?? 'Cửa hàng') ?></strong>
            <span class="muted">· <?= date("d/m/Y", strtotime($dg['ngayphanhoi'])) ?></span><br>
            <?= nl2br(e($dg['phanhoi'])) ?>
          </div>
        <?php endif; ?>

        <details class="review-form" <?= $dg['phanhoi'] ? '' : 'open' ?>>
          <summary><?= $dg['phanhoi'] ? 'Sửa câu trả lời' : 'Trả lời' ?></summary>
          <form method="post">
            <input type="hidden" name="hanhdong" value="traloi">
            <input type="hidden" name="id" value="<?= $dg['id'] ?>">
            <input type="hidden" name="loc" value="<?= $loc ?>">
            <textarea name="phanhoi" rows="3" maxlength="1000" required
                      placeholder="Cảm ơn bạn đã ủng hộ shop…"><?= e($dg['phanhoi'] ?? '') ?></textarea>
            <button type="submit" class="btn-dark btn-sm">Gửi trả lời</button>
          </form>
        </details>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<?php include "footer.php" ?>
</body>
</html>
