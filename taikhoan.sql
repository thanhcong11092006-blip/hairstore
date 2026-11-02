
USE hairstore;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS taikhoan (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  hoten     VARCHAR(150) NOT NULL,
  email     VARCHAR(150) NOT NULL UNIQUE,
  matkhau   VARCHAR(255) NOT NULL,          -- lưu dạng mã hóa password_hash()
  vaitro    VARCHAR(20)  NOT NULL DEFAULT 'khachhang',   -- khachhang / admin
  ngaytao   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE donhang ADD COLUMN IF NOT EXISTS taikhoan_id INT NULL AFTER id;

-- Tài khoản quản trị mặc định: admin@hairstore.vn / admin123 (nhớ đổi mật khẩu)
INSERT IGNORE INTO taikhoan (hoten, email, matkhau, vaitro) VALUES
('Quản trị viên', 'admin@hairstore.vn', '$2y$10$N3ge6bOKTxSdW7MY5k5Wi.HV7jhg69kDPppPv2XTgIE7kGg2bf3XG', 'admin');
