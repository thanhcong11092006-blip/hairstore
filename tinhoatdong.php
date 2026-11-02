<?php session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tin hoạt động | Fashionista</title>
  <link rel="icon" href="Media/Logo.PNG" type="image/png">
  <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
    <?php include "header.php"?>


<section class="container news-page">
  <h1>Tin hoạt động</h1>
  <article class="news">
    <p>BST mới mang phong cách thể thao, nam tính</p>
  </article>

  <article class="news">
    <h3>Nhạc nền cửa hàng</h3>
  </article>
<div class="video-wrap"><iframe src="https://www.youtube.com/embed/YZkAVtIkBxI?si=oHRGs7wpVnIkwzNO&amp;start=3" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>
  <p>Xem thêm tại: 
    <a href="https://www.facebook.com/Tuniah.06" target="_blank">Facebook</a> 
  </p>
</section>

    <?php include "footer.php"?>

</body>
</html>
