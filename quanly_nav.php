<?php
// Thanh chuyển giữa các trang quản lý (nhân viên + admin)
$trangHienTai = basename($_SERVER['PHP_SELF']);
$tabs = [
    'view.php'            => 'Đơn hàng',
    'quanly_sanpham.php'  => 'Sản phẩm & tồn kho',
    'quanly_danhgia.php'  => 'Đánh giá',
    'doanhthu.php'        => 'Doanh thu',
];
if (vai_tro() === 'admin') {
    $tabs = ['quanly_taikhoan.php' => 'Tài khoản'] + $tabs;
}
?>
<nav class="manage-tabs" aria-label="Quản lý">
  <?php foreach ($tabs as $href => $ten): ?>
    <a href="<?= $href ?>" <?= $href === $trangHienTai ? 'aria-current="page"' : '' ?>><?= $ten ?></a>
  <?php endforeach; ?>
</nav>
