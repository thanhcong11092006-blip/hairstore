<?php
session_start();
require_once "conn.php";

// Tài khoản admin không dùng giỏ hàng
if (!duoc_mua_hang()) {
    $_SESSION['message'] = "Tài khoản quản lý không sử dụng giỏ hàng.";
    header("Location: view.php");
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

$items = [];
$tongtien = 0;
$tongsl = 0;
if (!empty($cart)) {
    $ids = implode(",", array_map('intval', array_keys($cart)));
    $kq = mysqli_query($conn, "SELECT * FROM sanpham WHERE id IN ($ids)");
    while ($row = mysqli_fetch_assoc($kq)) {
        $row['sl'] = $cart[$row['id']];
        $row['thanhtien'] = $row['sl'] * $row['gia'];
        $tongtien += $row['thanhtien'];
        $tongsl += $row['sl'];
        $items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giỏ hàng | Fashionista</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
    <?php include "header.php"?>

<section class="container cart-page">
  <h1 class="cart-title">Giỏ hàng của bạn
    <?php if ($tongsl): ?><span class="cart-count">(<?= $tongsl ?> sản phẩm)</span><?php endif; ?>
  </h1>

  <?php if ($message): ?>
    <p class="cart-message"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <!-- Giỏ hàng trống -->
    <div class="cart-empty">
      <p>Giỏ hàng của bạn đang trống.</p>
      <a href="sanpham.php" class="btn-dark">Tiếp tục mua sắm</a>
    </div>
  <?php else: ?>

  <div class="cart-layout">

    <!-- CỘT TRÁI: danh sách sản phẩm -->
    <form action="admin.php" method="post" class="cart-box">
      <input type="hidden" name="action" value="update">

      <div class="cart-row cart-head">
        <span>Sản phẩm</span>
        <span>Số lượng</span>
        <span>Thành tiền</span>
        <span></span>
      </div>

      <?php foreach ($items as $sp): ?>
      <div class="cart-row">
        <div class="cart-product">
          <img src="<?= htmlspecialchars($sp['hinhanh']) ?>" alt="<?= htmlspecialchars($sp['ten']) ?>"
               width="80" height="80" style="width:80px;height:80px;object-fit:cover;">
          <div>
            <p class="cart-name"><?= htmlspecialchars($sp['ten']) ?></p>
            <p class="cart-price">₫<?= number_format($sp['gia']) ?></p>
          </div>
        </div>
        <input type="number" class="cart-qty" name="soluong[<?= $sp['id'] ?>]"
               value="<?= $sp['sl'] ?>" min="0" aria-label="Số lượng">
        <span class="cart-line-total">₫<?= number_format($sp['thanhtien']) ?></span>
        <a href="admin.php?action=remove&id=<?= $sp['id'] ?>" class="cart-remove" title="Xóa sản phẩm" aria-label="Xóa <?= htmlspecialchars($sp['ten']) ?> khỏi giỏ">✕</a>
      </div>
      <?php endforeach; ?>

      <div class="cart-actions">
        <a href="sanpham.php" class="link-back">← Tiếp tục mua sắm</a>
        <div>
          <a href="admin.php?action=clear" class="btn-outline"
             onclick="return confirm('Xóa toàn bộ giỏ hàng?')">Xóa tất cả</a>
          <button type="submit" class="btn-outline">Cập nhật giỏ hàng</button>
        </div>
      </div>
    </form>

    <!-- CỘT PHẢI: tổng tiền + thanh toán -->
    <aside class="cart-summary">
      <h3>Tóm tắt đơn hàng</h3>
      <div class="summary-line"><span>Tạm tính</span><span>₫<?= number_format($tongtien) ?></span></div>
      <div class="summary-line"><span>Phí vận chuyển</span><span>Miễn phí</span></div>
      <div class="summary-line summary-total"><span>Tổng cộng</span><span>₫<?= number_format($tongtien) ?></span></div>

      <form action="admin.php" method="post" class="checkout-form">
        <input type="hidden" name="action" value="checkout">
        <h3>Thông tin thanh toán</h3>

        <label>Họ tên
          <input type="text" name="hoten" value="<?= htmlspecialchars($_SESSION['user']['hoten'] ?? '') ?>" required>
        </label>
        <label>Email
          <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?>" required>
        </label>
        <label>Địa chỉ nhận hàng
          <input type="text" name="diachi" placeholder="Số nhà, đường, phường/xã, tỉnh/thành" required>
        </label>
        <label>Phương thức thanh toán
          <select name="thanhtoan">
            <option value="COD">Thanh toán khi nhận hàng</option>
            <option value="ChuyenKhoan">Chuyển khoản ngân hàng</option>
          </select>
        </label>

        <button type="submit" class="btn-dark btn-block">Xác nhận đặt hàng</button>
      </form>
    </aside>
  </div>
  <?php endif; ?>
</section>

    <?php include "footer.php"?>

</body>
</html>
