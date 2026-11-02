<?php
session_start();
require_once "conn.php";
$laAdmin = !duoc_mua_hang();   // nhân viên / admin không mua hàng
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$dssp = mysqli_query($conn, "SELECT * FROM sanpham WHERE dangban = 1 ORDER BY id LIMIT 6");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>hairstore</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
  <?php include "header.php" ?>
  <!-- BANNER -->
  
  <main class="home-layout">
    <!-- Cột trái: Sidebar -->
    <aside class="sidebar">
        <h2>Danh mục</h2>
        <ul>
            <li><a href="#">Bộ cộc Adidas</a></li>
            <li><a href="#">Bộ cộc Nike</a></li>
            <li><a href="#">Bộ cộc clb</a></li>
            <li><a href="#">Áo cộc</a></li>
        </ul>

        <h3>Khuyến mãi hot</h3>
        <div class="promo-box">
            <p>Giảm <strong>30%</strong></p>
            <a href="sanpham.php" class="btn-sidebar">Mua ngay</a>
        </div>
    </aside>

    <!-- Cột phải: Sản phẩm nổi bật -->
    <section class="featured">
    <div class="container">
      <h2>Bộ sưu tập nổi bật</h2>
      <?php if ($message): ?>
        <p class="cart-message"><?= htmlspecialchars($message) ?> <a href="giohang.php">Xem giỏ hàng</a></p>
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
</main>
<?php include "footer.php"?>
</body>
</html>
