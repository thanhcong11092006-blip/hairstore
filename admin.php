<?php
// Xử lý các tác vụ: giỏ hàng, đặt hàng, chuyển trạng thái đơn, đánh giá sản phẩm
// Giỏ hàng lưu trong session dạng: $_SESSION['cart'][id_san_pham] = so_luong
session_start();
require_once "conn.php";

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function quay_lai($trang, $thongbao) {
    chuyen_trang($trang, $thongbao);
}

// Nhân viên / admin không dùng giỏ hàng (chặn cả khi gửi request trực tiếp)
if (!duoc_mua_hang() && in_array($action, ['add', 'update', 'remove', 'clear', 'checkout', ''], true)) {
    quay_lai(trang_chu_theo_vai_tro(vai_tro()), "Tài khoản quản lý không sử dụng giỏ hàng.");
}

// Lấy tồn kho của 1 sản phẩm đang bán (null nếu không có / ngừng bán)
function ton_kho($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT id, ten, soluong FROM sanpham WHERE id = ? AND dangban = 1");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

switch ($action) {

    // ===== THÊM VÀO GIỎ =====
    case 'add':
        $id = (int)($_POST['id'] ?? 0);
        $sl = max(1, (int)($_POST['soluong'] ?? 1));
        $trangtruoc = $_SERVER['HTTP_REFERER'] ?? "sanpham.php";

        $sp = ton_kho($conn, $id);
        if (!$sp) {
            quay_lai("sanpham.php", "Sản phẩm không tồn tại hoặc đã ngừng bán.");
        }
        $dangCo = $_SESSION['cart'][$id] ?? 0;
        if ($dangCo + $sl > $sp['soluong']) {
            quay_lai($trangtruoc, "\"{$sp['ten']}\" chỉ còn {$sp['soluong']} sản phẩm trong kho.");
        }

        $_SESSION['cart'][$id] = $dangCo + $sl;
        quay_lai($trangtruoc, "Đã thêm \"" . $sp['ten'] . "\" vào giỏ hàng.");
        break;

    // ===== CẬP NHẬT SỐ LƯỢNG =====
    case 'update':
        $canhBao = [];
        foreach ($_POST['soluong'] ?? [] as $id => $sl) {
            $id = (int)$id;
            $sl = (int)$sl;
            if ($sl <= 0) {
                unset($_SESSION['cart'][$id]);
                continue;
            }
            $sp = ton_kho($conn, $id);
            if (!$sp) {
                unset($_SESSION['cart'][$id]);
                continue;
            }
            if ($sl > $sp['soluong']) {
                $sl = $sp['soluong'];
                $canhBao[] = "\"{$sp['ten']}\" chỉ còn {$sp['soluong']}";
            }
            if ($sl > 0) {
                $_SESSION['cart'][$id] = $sl;
            } else {
                unset($_SESSION['cart'][$id]);
            }
        }
        quay_lai("giohang.php", $canhBao ? "Đã cập nhật. Lưu ý: " . implode("; ", $canhBao) . "." : "Đã cập nhật giỏ hàng.");
        break;

    // ===== XÓA 1 SẢN PHẨM =====
    case 'remove':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        unset($_SESSION['cart'][$id]);
        quay_lai("giohang.php", "Đã xóa sản phẩm khỏi giỏ.");
        break;

    // ===== XÓA TOÀN BỘ GIỎ =====
    case 'clear':
        $_SESSION['cart'] = [];
        quay_lai("giohang.php", "Đã xóa toàn bộ giỏ hàng.");
        break;

    // ===== ĐẶT HÀNG =====
    case 'checkout':
        if (empty($_SESSION['cart'])) {
            quay_lai("giohang.php", "Giỏ hàng đang trống.");
        }

        $hoten     = trim($_POST['hoten'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $diachi    = trim($_POST['diachi'] ?? '');
        $thanhtoan = $_POST['thanhtoan'] ?? 'COD';

        if ($hoten === '' || $diachi === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            quay_lai("giohang.php", "Vui lòng nhập đầy đủ và đúng thông tin thanh toán.");
        }
        if (!in_array($thanhtoan, ['COD', 'ChuyenKhoan'])) {
            $thanhtoan = 'COD';
        }

        // Lấy giá từ CSDL (không tin giá gửi từ trình duyệt)
        $ids = implode(",", array_map('intval', array_keys($_SESSION['cart'])));
        $kq  = mysqli_query($conn, "SELECT id, ten, gia FROM sanpham WHERE id IN ($ids) AND dangban = 1");
        $tongtien = 0;
        $items = [];
        while ($row = mysqli_fetch_assoc($kq)) {
            $sl = $_SESSION['cart'][$row['id']];
            $items[] = ['id' => $row['id'], 'ten' => $row['ten'], 'sl' => $sl, 'gia' => $row['gia']];
            $tongtien += $sl * $row['gia'];
        }
        if (!$items) {
            $_SESSION['cart'] = [];
            quay_lai("giohang.php", "Các sản phẩm trong giỏ đã ngừng bán.");
        }

        // Lưu đơn hàng + chi tiết + trừ kho trong 1 transaction
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO donhang (taikhoan_id, hoten, email, diachi, thanhtoan, tongtien, trangthai) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $taikhoan_id = $_SESSION['user']['id'] ?? null;   // NULL nếu chưa đăng nhập
            $tt = TT_CHO_XAC_NHAN;
            mysqli_stmt_bind_param($stmt, "issssis", $taikhoan_id, $hoten, $email, $diachi, $thanhtoan, $tongtien, $tt);
            mysqli_stmt_execute($stmt);
            $donhang_id = mysqli_insert_id($conn);

            $stmt = mysqli_prepare($conn,
                "INSERT INTO chitietdonhang (donhang_id, sanpham_id, soluong, dongia) VALUES (?, ?, ?, ?)");
            // Chỉ trừ kho khi còn đủ hàng — tránh tồn kho âm khi 2 người cùng mua
            $stmtKho = mysqli_prepare($conn,
                "UPDATE sanpham SET soluong = soluong - ? WHERE id = ? AND soluong >= ?");
            foreach ($items as $it) {
                mysqli_stmt_bind_param($stmtKho, "iii", $it['sl'], $it['id'], $it['sl']);
                mysqli_stmt_execute($stmtKho);
                if (mysqli_stmt_affected_rows($stmtKho) !== 1) {
                    throw new RuntimeException("\"{$it['ten']}\" không còn đủ hàng.");
                }
                mysqli_stmt_bind_param($stmt, "iiii", $donhang_id, $it['id'], $it['sl'], $it['gia']);
                mysqli_stmt_execute($stmt);
            }

            mysqli_commit($conn);
            $_SESSION['cart'] = [];
            quay_lai("giohang.php", "Đặt hàng thành công! Mã đơn hàng của bạn: #$donhang_id. Tổng tiền: "
                     . tien($tongtien) . ". Cửa hàng sẽ xác nhận đơn sớm.");
        } catch (RuntimeException $e) {
            mysqli_rollback($conn);
            quay_lai("giohang.php", $e->getMessage() . " Vui lòng cập nhật lại giỏ hàng.");
        } catch (Exception $e) {
            mysqli_rollback($conn);
            quay_lai("giohang.php", "Có lỗi khi đặt hàng, vui lòng thử lại.");
        }
        break;

    // ===== CHUYỂN TRẠNG THÁI ĐƠN =====
    // Nhân viên/admin: xác nhận, từ chối, giao hàng... Khách hàng: hủy đơn đang chờ xác nhận.
    case 'chuyen_trang_thai':
        yeu_cau_dang_nhap($conn);
        $id  = (int)($_POST['id'] ?? 0);
        $moi = $_POST['trangthai'] ?? '';

        $stmt = mysqli_prepare($conn, "SELECT * FROM donhang WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $dh = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$dh) {
            quay_lai("view.php", "Không tìm thấy đơn hàng #$id.");
        }
        $cu = $dh['trangthai'];

        if (la_quan_ly()) {
            $hopLe = isset(CHUYEN_TRANG_THAI_NV[$cu][$moi]);
        } else {
            $cuaToi = $dh['taikhoan_id'] == $_SESSION['user']['id'] || $dh['email'] === $_SESSION['user']['email'];
            $hopLe  = $cuaToi && $cu === TT_CHO_XAC_NHAN && $moi === TT_DA_HUY;
        }
        if (!$hopLe) {
            quay_lai("view.php", "Không thể chuyển đơn #$id từ \"$cu\" sang \"$moi\".");
        }

        mysqli_begin_transaction($conn);
        try {
            // Điều kiện "AND trangthai = cũ" tránh 2 người cùng xử lý 1 đơn
            $stmt = mysqli_prepare($conn, "UPDATE donhang SET trangthai = ? WHERE id = ? AND trangthai = ?");
            mysqli_stmt_bind_param($stmt, "sis", $moi, $id, $cu);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                throw new RuntimeException("Đơn #$id vừa được người khác cập nhật, vui lòng xem lại.");
            }
            // Đơn bị từ chối / hủy: trả hàng về kho
            if (in_array($moi, TT_HOAN_KHO, true)) {
                $stmt = mysqli_prepare($conn,
                    "UPDATE sanpham sp JOIN chitietdonhang ct ON ct.sanpham_id = sp.id
                     SET sp.soluong = sp.soluong + ct.soluong WHERE ct.donhang_id = ?");
                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
            }
            mysqli_commit($conn);
        } catch (RuntimeException $e) {
            mysqli_rollback($conn);
            quay_lai("view.php", $e->getMessage());
        }

        $thongBao = "Đơn #$id: $cu → $moi.";
        if (in_array($moi, TT_HOAN_KHO, true)) {
            $thongBao .= " Hàng đã được trả lại kho.";
        }
        $quayVe = la_quan_ly() ? "view.php?loc=" . urlencode($_POST['loc'] ?? '') : "view.php";
        quay_lai($quayVe, $thongBao);
        break;

    // ===== KHÁCH ĐÁNH GIÁ SẢN PHẨM (đơn đã giao) =====
    case 'danhgia':
        $tk = yeu_cau_dang_nhap($conn, ['khachhang']);
        $donhang_id = (int)($_POST['donhang_id'] ?? 0);
        $sanpham_id = (int)($_POST['sanpham_id'] ?? 0);
        $sosao      = (int)($_POST['sosao'] ?? 0);
        $noidung    = trim($_POST['noidung'] ?? '');

        if ($sosao < 1 || $sosao > 5 || $noidung === '') {
            quay_lai("view.php", "Vui lòng chọn số sao và viết nhận xét.");
        }
        if (mb_strlen($noidung) > 1000) {
            quay_lai("view.php", "Nhận xét tối đa 1000 kí tự.");
        }

        // Đơn phải của khách này, đã giao, và có chứa sản phẩm được đánh giá
        $stmt = mysqli_prepare($conn,
            "SELECT 1 FROM donhang dh JOIN chitietdonhang ct ON ct.donhang_id = dh.id
             WHERE dh.id = ? AND ct.sanpham_id = ? AND dh.trangthai = ?
               AND (dh.taikhoan_id = ? OR dh.email = ?)");
        $daGiao = TT_DA_GIAO;
        mysqli_stmt_bind_param($stmt, "iisis", $donhang_id, $sanpham_id, $daGiao, $tk['id'], $tk['email']);
        mysqli_stmt_execute($stmt);
        if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))) {
            quay_lai("view.php", "Chỉ đánh giá được sản phẩm trong đơn đã giao của bạn.");
        }

        try {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO danhgia (sanpham_id, donhang_id, taikhoan_id, sosao, noidung) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiiis", $sanpham_id, $donhang_id, $tk['id'], $sosao, $noidung);
            mysqli_stmt_execute($stmt);
        } catch (mysqli_sql_exception $e) {
            quay_lai("view.php", "Bạn đã đánh giá sản phẩm này trong đơn #$donhang_id rồi.");
        }
        quay_lai("view.php", "Cảm ơn bạn đã đánh giá! Cửa hàng sẽ phản hồi sớm.");
        break;

    default:
        header("Location: " . (duoc_mua_hang() ? "giohang.php" : trang_chu_theo_vai_tro(vai_tro())));
        exit;
}
