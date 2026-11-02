-- CSDL cho website hairstore (Fashionista)
-- Cách dùng: mở phpMyAdmin (http://localhost/phpmyadmin) > tab SQL > dán toàn bộ file này > Go
-- hoặc tab Import > chọn file database.sql

CREATE DATABASE IF NOT EXISTS hairstore
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hairstore;
SET NAMES utf8mb4;

DROP TABLE IF EXISTS danhgia;
DROP TABLE IF EXISTS chitietdonhang;
DROP TABLE IF EXISTS donhang;
DROP TABLE IF EXISTS sanpham;
DROP TABLE IF EXISTS taikhoan;

-- Bảng sản phẩm
CREATE TABLE sanpham (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  ten       VARCHAR(255) NOT NULL,
  gia       INT NOT NULL,              -- đơn vị: đồng
  hinhanh   VARCHAR(255) NOT NULL,
  danhmuc   VARCHAR(100) DEFAULT NULL,
  soluong   INT NOT NULL DEFAULT 100,  -- tồn kho
  dangban   TINYINT(1) NOT NULL DEFAULT 1   -- 1 = đang bán, 0 = ngừng bán
) ENGINE=InnoDB;

-- Bảng tài khoản
CREATE TABLE taikhoan (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  hoten     VARCHAR(150) NOT NULL,
  email     VARCHAR(150) NOT NULL UNIQUE,
  matkhau   VARCHAR(255) NOT NULL,          -- lưu dạng mã hóa password_hash()
  vaitro    VARCHAR(20)  NOT NULL DEFAULT 'khachhang',   -- khachhang / nhanvien / admin
  trangthai VARCHAR(20)  NOT NULL DEFAULT 'hoatdong',    -- hoatdong / khoa
  ngaytao   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bảng đơn hàng
CREATE TABLE donhang (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  taikhoan_id INT NULL,                    -- NULL nếu khách không đăng nhập
  hoten       VARCHAR(150) NOT NULL,
  email       VARCHAR(150) NOT NULL,
  diachi      VARCHAR(255) NOT NULL,
  thanhtoan   VARCHAR(50)  NOT NULL,   -- COD / ChuyenKhoan
  tongtien    INT NOT NULL,
  trangthai   VARCHAR(50)  NOT NULL DEFAULT 'Chờ xác nhận',  -- Chờ xác nhận / Đã xác nhận / Đang giao / Đã giao / Bị từ chối / Đã hủy
  ngaydat     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bảng chi tiết đơn hàng
CREATE TABLE chitietdonhang (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  donhang_id  INT NOT NULL,
  sanpham_id  INT NOT NULL,
  soluong     INT NOT NULL,
  dongia      INT NOT NULL,
  FOREIGN KEY (donhang_id) REFERENCES donhang(id) ON DELETE CASCADE,
  FOREIGN KEY (sanpham_id) REFERENCES sanpham(id)
) ENGINE=InnoDB;

-- Bảng đánh giá: khách đánh giá sản phẩm trong đơn đã giao, nhân viên trả lời
CREATE TABLE danhgia (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  sanpham_id      INT NOT NULL,
  donhang_id      INT NOT NULL,
  taikhoan_id     INT NOT NULL,
  sosao           TINYINT NOT NULL,          -- 1..5
  noidung         TEXT NOT NULL,
  ngaydanhgia     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  phanhoi         TEXT NULL,                 -- câu trả lời của cửa hàng
  nguoiphanhoi_id INT NULL,
  ngayphanhoi     DATETIME NULL,
  UNIQUE KEY mot_lan (donhang_id, sanpham_id),
  FOREIGN KEY (sanpham_id)  REFERENCES sanpham(id)  ON DELETE CASCADE,
  FOREIGN KEY (donhang_id)  REFERENCES donhang(id)  ON DELETE CASCADE,
  FOREIGN KEY (taikhoan_id) REFERENCES taikhoan(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Dữ liệu mẫu (khớp với ảnh trong thư mục Media)
INSERT INTO sanpham (ten, gia, hinhanh, danhmuc) VALUES
('Bộ Adidas logo ngực',      450000, 'Media/anh1.JPG',  'Bộ cộc Adidas'),
('Bộ Adidas Fear of god',    500000, 'Media/anh2.JPG',  'Bộ cộc Adidas'),
('Bộ Mancheter United',      600000, 'Media/anh3.JPG',  'Bộ cộc clb'),
('Bộ Adidas VRCT',           499000, 'Media/anh4.JPG',  'Bộ cộc Adidas'),
('Bộ Nike họa tiết rêu',     599000, 'Media/anh7.JPG',  'Bộ cộc Nike'),
('Bộ Manchetser City',       600000, 'Media/anh6.JPG',  'Bộ cộc clb'),
('Bộ Pickleball Nike',       699000, 'Media/anh8.JPG',  'Bộ cộc Nike'),
('Bộ Adidas họa tiết camo',  450000, 'Media/anh11.JPG', 'Bộ cộc Adidas'),
('Áo polo Lacoste chữ cổ',   299000, 'Media/anh10.JPG', 'Áo cộc');

-- Tài khoản quản trị mặc định: admin@hairstore.vn / admin123 (nhớ đổi mật khẩu)
INSERT INTO taikhoan (hoten, email, matkhau, vaitro) VALUES
('Quản trị viên', 'admin@hairstore.vn', '$2y$10$N3ge6bOKTxSdW7MY5k5Wi.HV7jhg69kDPppPv2XTgIE7kGg2bf3XG', 'admin');

-- Tài khoản nhân viên mẫu: nhanvien@hairstore.vn / nhanvien123
INSERT INTO taikhoan (hoten, email, matkhau, vaitro) VALUES
('Nhân viên cửa hàng', 'nhanvien@hairstore.vn', '$2y$10$VvqioUHt1OcMCTa3mVO6/uVP.khX601NYo30ls88cx1EnCAxHRnHa', 'nhanvien');
