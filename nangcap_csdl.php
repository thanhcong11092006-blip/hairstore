<?php
// Tự nâng cấp CSDL cũ lên phiên bản có Nhân viên, Đánh giá, khóa tài khoản.
// Chỉ chạy khi chưa có bảng danhgia (1 truy vấn nhỏ mỗi lần tải trang).
// Bảng danhgia được tạo cuối cùng: nếu bước trước lỗi, lần sau sẽ chạy lại.

$kqKiemTra = mysqli_query($conn, "SHOW TABLES LIKE 'danhgia'");
if (mysqli_num_rows($kqKiemTra) === 0) {
    $cauLenh = [
        // Khóa / mở khóa tài khoản
        "ALTER TABLE taikhoan ADD COLUMN IF NOT EXISTS trangthai VARCHAR(20) NOT NULL DEFAULT 'hoatdong' AFTER vaitro",

        // Sản phẩm: đang bán / ngừng bán
        "ALTER TABLE sanpham ADD COLUMN IF NOT EXISTS dangban TINYINT(1) NOT NULL DEFAULT 1",

        // Đơn hàng: trạng thái mới theo quy trình xác nhận
        "ALTER TABLE donhang ADD COLUMN IF NOT EXISTS taikhoan_id INT NULL AFTER id",
        "ALTER TABLE donhang ALTER COLUMN trangthai SET DEFAULT 'Chờ xác nhận'",
        "UPDATE donhang SET trangthai = 'Chờ xác nhận' WHERE trangthai = 'Chờ xử lý'",

        // Tài khoản nhân viên mẫu: nhanvien@hairstore.vn / nhanvien123
        "INSERT IGNORE INTO taikhoan (hoten, email, matkhau, vaitro) VALUES
         ('Nhân viên cửa hàng', 'nhanvien@hairstore.vn', '" . password_hash('nhanvien123', PASSWORD_DEFAULT) . "', 'nhanvien')",

        // Bảng đánh giá (tạo cuối cùng)
        "CREATE TABLE IF NOT EXISTS danhgia (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            sanpham_id      INT NOT NULL,
            donhang_id      INT NOT NULL,
            taikhoan_id     INT NOT NULL,
            sosao           TINYINT NOT NULL,
            noidung         TEXT NOT NULL,
            ngaydanhgia     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            phanhoi         TEXT NULL,
            nguoiphanhoi_id INT NULL,
            ngayphanhoi     DATETIME NULL,
            UNIQUE KEY mot_lan (donhang_id, sanpham_id),
            FOREIGN KEY (sanpham_id)  REFERENCES sanpham(id)  ON DELETE CASCADE,
            FOREIGN KEY (donhang_id)  REFERENCES donhang(id)  ON DELETE CASCADE,
            FOREIGN KEY (taikhoan_id) REFERENCES taikhoan(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
    ];
    try {
        foreach ($cauLenh as $sql) {
            mysqli_query($conn, $sql);
        }
    } catch (mysqli_sql_exception $e) {
        // Dừng trước khi tạo bảng danhgia → lần tải trang sau sẽ thử lại.
        // Nếu vẫn lỗi: import lại file database.sql bằng phpMyAdmin.
        error_log("Nâng cấp CSDL hairstore lỗi: " . $e->getMessage());
    }
}
