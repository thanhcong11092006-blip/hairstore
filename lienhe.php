<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - hair.store22</title>
    <link rel="icon" href="Media/Logo.PNG" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- style.css nạp SAU Bootstrap để giao diện riêng không bị ghi đè -->
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
    <?php include "header.php"?>

    <section class="banner-small">
        <h1>Liên hệ với chúng tôi</h1>
    </section>

    <main class="contact-section">
        <div class="container">
            <div class="contact-info">
                <h2>Thông tin liên hệ</h2>
                <p><strong>Địa chỉ:</strong> Khu Đô Thị Louis Hoàng Mai, TP.Hà Nội</p>
                <p><strong>Email:</strong> hairnguyentuan@gmail.com</p>
                <p><strong>Điện thoại:</strong> 0974177106</p>
                <p><strong>Giờ làm việc:</strong> 8:00 - 22:00 (Thứ 2 - CN)</p>
            </div>

            <div class="contact-form">
                <h2>Gửi tin nhắn cho chúng tôi</h2>
                <form>
                    <label for="name">Họ và tên</label>
                    <input type="text" id="name" name="name" placeholder="Nhập họ tên của bạn" required>

                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="example@gmail.com" required>

                    <label for="subject">Chủ đề</label>
                    <input type="text" id="subject" name="subject" placeholder="Nhập chủ đề">

                    <label for="message">Nội dung</label>
                    <textarea id="message" name="message" rows="5" placeholder="Nhập nội dung..." required></textarea>

                    <button type="submit" class="btn-submit">Gửi tin nhắn</button>
                </form>
            </div>
        </div>
    </main>

       <?php include "footer.php"?>

</body>
</html>
