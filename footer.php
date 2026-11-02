<footer>
  <p>© hair.store22</p>
</footer>

<script>
  // iOS Safari chỉ hiện trạng thái :active khi trang có lắng nghe touchstart
  document.addEventListener('touchstart', function () {}, { passive: true });

  // Khi gửi form: báo "đang xử lý" ngay và chặn bấm 2 lần (ví dụ đặt hàng 2 lần)
  document.addEventListener('submit', function (e) {
    var nut = e.submitter || e.target.querySelector('[type="submit"]');
    if (!nut) return;
    if (nut.getAttribute('aria-busy') === 'true') { e.preventDefault(); return; }
    nut.setAttribute('aria-busy', 'true');
  });

  // Menu cuộn ngang trên điện thoại: đưa mục trang hiện tại vào giữa
  document.querySelectorAll('.menu [aria-current="page"], .manage-tabs [aria-current="page"]').forEach(function (muc) {
    var thanh = muc.parentElement.closest('.menu, .manage-tabs') || muc.parentElement;
    thanh.scrollLeft = muc.offsetLeft - thanh.offsetLeft - (thanh.clientWidth - muc.offsetWidth) / 2;
  });

  // Quay lại bằng nút Back: bỏ trạng thái "đang xử lý" cũ
  window.addEventListener('pageshow', function () {
    document.querySelectorAll('[aria-busy="true"]').forEach(function (n) {
      n.removeAttribute('aria-busy');
    });
  });
</script>
