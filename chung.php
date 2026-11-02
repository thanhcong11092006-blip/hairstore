<?php
// Các hàm dùng chung: phân quyền, trạng thái đơn hàng, định dạng
// Vai trò: khachhang / nhanvien / admin

// Giờ Việt Nam (XAMPP mặc định để giờ châu Âu → sai ngày trong báo cáo doanh thu)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// ===== TRẠNG THÁI ĐƠN HÀNG (theo biểu đồ trạng thái) =====
const TT_CHO_XAC_NHAN = 'Chờ xác nhận';
const TT_DA_XAC_NHAN  = 'Đã xác nhận';
const TT_DANG_GIAO    = 'Đang giao';
const TT_DA_GIAO      = 'Đã giao';
const TT_BI_TU_CHOI   = 'Bị từ chối';
const TT_DA_HUY       = 'Đã hủy';

const DS_TRANG_THAI = [TT_CHO_XAC_NHAN, TT_DA_XAC_NHAN, TT_DANG_GIAO, TT_DA_GIAO, TT_BI_TU_CHOI, TT_DA_HUY];

// Các bước chuyển hợp lệ cho nhân viên: trạng thái hiện tại => [trạng thái mới => tên nút]
const CHUYEN_TRANG_THAI_NV = [
    TT_CHO_XAC_NHAN => [TT_DA_XAC_NHAN => 'Xác nhận', TT_BI_TU_CHOI => 'Từ chối'],
    TT_DA_XAC_NHAN  => [TT_DANG_GIAO => 'Bắt đầu giao'],
    TT_DANG_GIAO    => [TT_DA_GIAO => 'Đã giao xong', TT_DA_HUY => 'Giao thất bại'],
];

// Trạng thái kết thúc mà hàng được trả lại kho
const TT_HOAN_KHO = [TT_BI_TU_CHOI, TT_DA_HUY];

function lop_trang_thai($tt) {
    return [
        TT_CHO_XAC_NHAN => 'badge-wait',
        TT_DA_XAC_NHAN  => 'badge-info',
        TT_DANG_GIAO    => 'badge-info',
        TT_DA_GIAO      => 'badge-ok',
        TT_BI_TU_CHOI   => 'badge-bad',
        TT_DA_HUY       => 'badge-bad',
    ][$tt] ?? '';
}

// ===== PHÂN QUYỀN =====
function vai_tro() {
    return $_SESSION['user']['vaitro'] ?? '';
}

// Khách vãng lai hoặc tài khoản khách hàng mới được mua hàng
function duoc_mua_hang() {
    return in_array(vai_tro(), ['', 'khachhang'], true);
}

function la_quan_ly() {
    return in_array(vai_tro(), ['nhanvien', 'admin'], true);
}

function ten_vai_tro($vt) {
    return ['khachhang' => 'Khách hàng', 'nhanvien' => 'Nhân viên', 'admin' => 'Quản trị viên'][$vt] ?? $vt;
}

// Bắt buộc đăng nhập (và đúng vai trò nếu có). Đọc lại tài khoản từ CSDL
// để tài khoản vừa bị khóa / đổi vai trò không còn dùng được phiên cũ.
function yeu_cau_dang_nhap($conn, array $dsVaiTro = []) {
    if (empty($_SESSION['user'])) {
        $_SESSION['message'] = "Vui lòng đăng nhập để tiếp tục.";
        header("Location: dangnhap.php");
        exit;
    }
    $stmt = mysqli_prepare($conn, "SELECT id, hoten, email, vaitro, trangthai FROM taikhoan WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user']['id']);
    mysqli_stmt_execute($stmt);
    $tk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$tk || $tk['trangthai'] === 'khoa') {
        unset($_SESSION['user']);
        $_SESSION['message'] = $tk ? "Tài khoản của bạn đã bị khóa." : "Tài khoản không còn tồn tại.";
        header("Location: dangnhap.php");
        exit;
    }
    $_SESSION['user'] = ['id' => $tk['id'], 'hoten' => $tk['hoten'], 'email' => $tk['email'], 'vaitro' => $tk['vaitro']];

    if ($dsVaiTro && !in_array($tk['vaitro'], $dsVaiTro, true)) {
        $_SESSION['message'] = "Bạn không có quyền truy cập trang này.";
        header("Location: " . trang_chu_theo_vai_tro($tk['vaitro']));
        exit;
    }
    return $tk;
}

function trang_chu_theo_vai_tro($vt) {
    return ['admin' => 'quanly_taikhoan.php', 'nhanvien' => 'view.php'][$vt] ?? 'index.php';
}

// ===== TIỆN ÍCH =====
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function tien($so) {
    return '₫' . number_format((int)$so, 0, ',', '.');
}

function sao($so) {
    $so = max(0, min(5, (int)$so));
    return str_repeat('★', $so) . str_repeat('☆', 5 - $so);
}

function lay_thong_bao() {
    $m = $_SESSION['message'] ?? '';
    unset($_SESSION['message']);
    return $m;
}

function chuyen_trang($trang, $thongbao = '') {
    if ($thongbao !== '') {
        $_SESSION['message'] = $thongbao;
    }
    header("Location: $trang");
    exit;
}
