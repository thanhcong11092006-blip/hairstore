<?php
// Doanh thu theo ngày / tuần / tháng — nhân viên / admin
// Doanh thu = tổng tiền các đơn "Đã giao", tính theo ngày đặt hàng
session_start();
require_once "conn.php";
yeu_cau_dang_nhap($conn, ['nhanvien', 'admin']);

$ky = in_array($_GET['ky'] ?? '', ['ngay', 'tuan', 'thang'], true) ? $_GET['ky'] : 'ngay';

// Tạo danh sách các kỳ (cũ → mới) để kỳ không có đơn vẫn hiện số 0
$homNay = new DateTimeImmutable('today');
$dsKy = [];
if ($ky === 'ngay') {
    for ($i = 13; $i >= 0; $i--) {
        $d = $homNay->modify("-$i day");
        $dsKy[$d->format('Y-m-d')] = $d->format('d/m') . ($i === 0 ? ' (hôm nay)' : '');
    }
    $sqlKhoa = "DATE(dh.ngaydat)";
    $tenKy = ['hôm nay', 'hôm qua'];
} elseif ($ky === 'tuan') {
    $thu2 = $homNay->modify('monday this week');
    for ($i = 11; $i >= 0; $i--) {
        $d = $thu2->modify("-$i week");
        $dsKy[$d->format('Y-m-d')] = 'Tuần ' . $d->format('W') . ' (' . $d->format('d/m') . '–' . $d->modify('+6 day')->format('d/m') . ')';
    }
    $sqlKhoa = "DATE_SUB(DATE(dh.ngaydat), INTERVAL WEEKDAY(dh.ngaydat) DAY)";
    $tenKy = ['tuần này', 'tuần trước'];
} else {
    $dau = $homNay->modify('first day of this month');
    for ($i = 11; $i >= 0; $i--) {
        $d = $dau->modify("-$i month");
        $dsKy[$d->format('Y-m')] = 'Tháng ' . $d->format('m/Y');
    }
    $sqlKhoa = "DATE_FORMAT(dh.ngaydat, '%Y-%m')";
    $tenKy = ['tháng này', 'tháng trước'];
}
$khoaDau = array_key_first($dsKy);
$tuNgay  = strlen($khoaDau) === 7 ? "$khoaDau-01" : $khoaDau;
$daGiao  = TT_DA_GIAO;

// Doanh thu theo kỳ
$stmt = mysqli_prepare($conn,
    "SELECT $sqlKhoa AS khoa, COUNT(*) AS sodon, SUM(dh.tongtien) AS doanhthu
     FROM donhang dh
     WHERE dh.trangthai = ? AND dh.ngaydat >= ?
     GROUP BY khoa");
mysqli_stmt_bind_param($stmt, "ss", $daGiao, $tuNgay);
mysqli_stmt_execute($stmt);
$theoKy = [];
foreach (mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC) as $r) {
    $theoKy[$r['khoa']] = $r;
}

$tongDT = 0; $tongDon = 0; $maxDT = 0;
foreach ($dsKy as $k => $_) {
    $tongDT  += (int)($theoKy[$k]['doanhthu'] ?? 0);
    $tongDon += (int)($theoKy[$k]['sodon'] ?? 0);
    $maxDT    = max($maxDT, (int)($theoKy[$k]['doanhthu'] ?? 0));
}
$cacKhoa = array_keys($dsKy);
$dtNay   = (int)($theoKy[end($cacKhoa)]['doanhthu'] ?? 0);
$dtTruoc = (int)($theoKy[prev($cacKhoa)]['doanhthu'] ?? 0);
$bienDong = $dtTruoc > 0 ? round(($dtNay - $dtTruoc) / $dtTruoc * 100) : null;

// Đơn chờ xác nhận (việc cần làm)
$choXacNhan = (int)mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM donhang WHERE trangthai = '" . TT_CHO_XAC_NHAN . "'"))[0];

// Sản phẩm bán chạy trong khoảng thời gian
$stmt = mysqli_prepare($conn,
    "SELECT sp.ten, sp.hinhanh, SUM(ct.soluong) AS sl, SUM(ct.soluong * ct.dongia) AS tien
     FROM chitietdonhang ct
     JOIN donhang dh ON dh.id = ct.donhang_id
     JOIN sanpham sp ON sp.id = ct.sanpham_id
     WHERE dh.trangthai = ? AND dh.ngaydat >= ?
     GROUP BY sp.id ORDER BY sl DESC, tien DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, "ss", $daGiao, $tuNgay);
mysqli_stmt_execute($stmt);
$banChay = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);

$nhanKy = ['ngay' => 'Theo ngày', 'tuan' => 'Theo tuần', 'thang' => 'Theo tháng'];
$moTaKhoang = ['ngay' => '14 ngày gần nhất', 'tuan' => '12 tuần gần nhất', 'thang' => '12 tháng gần nhất'][$ky];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doanh thu | hair.store</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<?php include "header.php" ?>

<section class="container order-section">
  <?php include "quanly_nav.php"; ?>
  <div class="page-head">
    <h1>Doanh thu</h1>
    <div class="segmented" role="group" aria-label="Xem doanh thu">
      <?php foreach ($nhanKy as $k => $ten): ?>
        <a href="doanhthu.php?ky=<?= $k ?>" <?= $k === $ky ? 'aria-current="true"' : '' ?>><?= $ten ?></a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat">
      <span class="stat-label">Doanh thu <?= $tenKy[0] ?></span>
      <span class="stat-value"><?= tien($dtNay) ?></span>
      <span class="stat-note">
        <?php if ($bienDong === null): ?>
          <?= $tenKy[1] ?>: <?= tien($dtTruoc) ?>
        <?php else: ?>
          <span class="<?= $bienDong >= 0 ? 'up' : 'down' ?>"><?= $bienDong >= 0 ? '▲' : '▼' ?> <?= abs($bienDong) ?>%</span> so với <?= $tenKy[1] ?>
        <?php endif; ?>
      </span>
    </div>
    <div class="stat">
      <span class="stat-label">Tổng <?= $moTaKhoang ?></span>
      <span class="stat-value"><?= tien($tongDT) ?></span>
      <span class="stat-note"><?= $tongDon ?> đơn đã giao</span>
    </div>
    <div class="stat">
      <span class="stat-label">Giá trị trung bình / đơn</span>
      <span class="stat-value"><?= tien($tongDon ? $tongDT / $tongDon : 0) ?></span>
      <span class="stat-note"><?= $moTaKhoang ?></span>
    </div>
    <a class="stat stat-link" href="view.php?loc=<?= urlencode(TT_CHO_XAC_NHAN) ?>">
      <span class="stat-label">Đơn chờ xác nhận</span>
      <span class="stat-value"><?= $choXacNhan ?></span>
      <span class="stat-note">Xử lý ngay →</span>
    </a>
  </div>

  <div class="revenue-layout">
    <div class="account-card">
      <h2>Doanh thu <?= mb_strtolower($nhanKy[$ky]) ?></h2>
      <div class="table-scroll flat">
      <table class="order-table revenue-table">
        <tr><th><?= ['ngay' => 'Ngày', 'tuan' => 'Tuần', 'thang' => 'Tháng'][$ky] ?></th><th>Số đơn</th><th>Doanh thu</th></tr>
        <?php foreach (array_reverse($dsKy, true) as $k => $nhan):
          $dt = (int)($theoKy[$k]['doanhthu'] ?? 0); ?>
        <tr class="<?= $dt ? '' : 'row-muted' ?>">
          <td class="nowrap"><?= e($nhan) ?></td>
          <td><?= (int)($theoKy[$k]['sodon'] ?? 0) ?></td>
          <td>
            <div class="bar-cell">
              <span class="bar" style="--w: <?= $maxDT ? round($dt / $maxDT * 100) : 0 ?>%"></span>
              <span><?= tien($dt) ?></span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
      </div>
      <p class="field-hint">Chỉ tính các đơn đã giao thành công, theo ngày khách đặt hàng.</p>
    </div>

    <div class="account-card">
      <h2>Bán chạy</h2>
      <p class="field-hint"><?= $moTaKhoang ?></p>
      <?php if (!$banChay): ?>
        <p class="muted">Chưa có đơn đã giao trong khoảng này.</p>
      <?php endif; ?>
      <ol class="top-list">
        <?php foreach ($banChay as $sp): ?>
        <li>
          <img src="<?= e($sp['hinhanh']) ?>" alt="">
          <div>
            <span class="order-item-name"><?= e($sp['ten']) ?></span>
            <span class="order-item-meta"><?= (int)$sp['sl'] ?> sản phẩm · <?= tien($sp['tien']) ?></span>
          </div>
        </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </div>
</section>

<?php include "footer.php" ?>
</body>
</html>
