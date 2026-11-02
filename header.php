<?php
// Phần đầu trang (được include vào mọi trang) — chỉ chứa <header>, không phải cả trang HTML
require_once __DIR__ . "/chung.php";

$trangHienTai = basename($_SERVER['PHP_SELF']);
$tuKhoa = $_GET['q'] ?? '';
$vaiTroMenu = $_SESSION['user']['vaitro'] ?? '';

// Các trang thuộc khu "Quản lý" (để tô sáng mục Quản lý trên menu)
$trangQuanLy = ['view.php', 'quanly_sanpham.php', 'quanly_danhgia.php', 'doanhthu.php', 'quanly_taikhoan.php'];

function menu_link($href, $ten, $dangChon, $title = '') {
    $active = $dangChon ? ' aria-current="page"' : '';
    $title  = $title !== '' ? ' title="' . htmlspecialchars($title) . '"' : '';
    echo "<li><a href=\"$href\"$active$title>$ten</a></li>";
}
?>
<header>
  <div class="container header-content">
    <a href="index.php" class="logo-link" aria-label="Trang chủ hair.store">
      <img src="Media/Logo.PNG" alt="hair.store" class="logo">
    </a>

    <nav aria-label="Menu chính">
      <ul class="menu">
        <?php
        menu_link('index.php', 'Trang chủ', $trangHienTai === 'index.php');
        menu_link('sanpham.php', 'Sản phẩm', $trangHienTai === 'sanpham.php');
        if (in_array($vaiTroMenu, ['', 'khachhang'], true)) {          // chỉ khách mới có giỏ hàng
            menu_link('giohang.php', 'Giỏ hàng', $trangHienTai === 'giohang.php');
        }
        menu_link('tinhoatdong.php', 'Tin hoạt động', $trangHienTai === 'tinhoatdong.php');
        menu_link('lienhe.php', 'Liên hệ', $trangHienTai === 'lienhe.php');

        if ($vaiTroMenu === 'khachhang') {
            menu_link('view.php', 'Đơn hàng', $trangHienTai === 'view.php');
        } elseif ($vaiTroMenu === 'nhanvien') {
            menu_link('view.php', 'Quản lý', in_array($trangHienTai, $trangQuanLy, true));
        } elseif ($vaiTroMenu === 'admin') {
            menu_link('quanly_taikhoan.php', 'Quản lý', in_array($trangHienTai, $trangQuanLy, true));
        }

        if ($vaiTroMenu !== '') {
            if ($vaiTroMenu !== 'admin') {
                menu_link('toi.php', 'Tôi', $trangHienTai === 'toi.php', 'Tài khoản: ' . $_SESSION['user']['hoten']);
            }
            menu_link('dangxuat.php', 'Đăng xuất', false);
        } else {
            menu_link('dangnhap.php', 'Đăng nhập', $trangHienTai === 'dangnhap.php');
            menu_link('dangki.php', 'Đăng kí', $trangHienTai === 'dangki.php');
        }
        ?>
      </ul>
    </nav>

    <div class="search-wrap">
      <form class="search-form" action="sanpham.php" method="get" role="search">
        <input type="search" name="q" placeholder="Tìm kiếm sản phẩm"
               value="<?= htmlspecialchars($tuKhoa) ?>" aria-label="Tìm kiếm sản phẩm">
        <button type="submit">Tìm</button>
      </form>
    </div>
  </div>
</header>
