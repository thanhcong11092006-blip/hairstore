<?php
session_start();
require_once "conn.php";
$laAdmin = !duoc_mua_hang();   // nhân viên / admin không mua hàng
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$tuKhoa = trim($_GET['q'] ?? '');
if ($tuKhoa !== '') {
    $stmt = mysqli_prepare($conn, "SELECT * FROM sanpham WHERE dangban = 1 AND ten LIKE ? ORDER BY id");
    $mau = "%$tuKhoa%";
    mysqli_stmt_bind_param($stmt, "s", $mau);
    mysqli_stmt_execute($stmt);
    $dssp = mysqli_stmt_get_result($stmt);
} else {
    $dssp = mysqli_query($conn, "SELECT * FROM sanpham WHERE dangban = 1 ORDER BY id");
}
$soSanPham = mysqli_num_rows($dssp);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sản phẩm | Fashionista</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<?php include "header.php"?>

  <!-- Sản phẩm -->
    <section class="featured">
    <div class="container">
      <h2><?= $tuKhoa !== '' ? 'Kết quả cho “' . htmlspecialchars($tuKhoa) . '”' : 'Tất cả sản phẩm' ?></h2>
      <?php if ($message): ?>
        <p class="cart-message"><?= htmlspecialchars($message) ?> <a href="giohang.php">Xem giỏ hàng</a></p>
      <?php endif; ?>
      <?php if ($soSanPham === 0): ?>
        <p>Không tìm thấy sản phẩm nào. <a href="sanpham.php">Xem tất cả sản phẩm</a></p>
      <?php endif; ?>
      <div class="products">
        <?php while ($sp = mysqli_fetch_assoc($dssp)): ?>
        <div class="product-card">
          <img src="<?= htmlspecialchars($sp['hinhanh']) ?>" alt="<?= htmlspecialchars($sp['ten']) ?>">
          <h3><?= htmlspecialchars($sp['ten']) ?></h3>
          <p>₫<?= number_format($sp['gia']) ?></p>
          <?php if (!$laAdmin): ?>
            <?php if ($sp['soluong'] > 0): ?>
            <form action="admin.php" method="post">
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="id" value="<?= $sp['id'] ?>">
              <button type="submit">Thêm vào giỏ</button>
            </form>
            <?php else: ?>
            <button type="button" disabled>Hết hàng</button>
            <?php endif; ?>
          <?php elseif ($sp['soluong'] <= 5): ?>
            <p class="stock-note">Tồn kho: <?= (int)$sp['soluong'] ?></p>
          <?php endif; ?>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
  </section>

    <?php include "footer.php"?>

</body>
</html>
