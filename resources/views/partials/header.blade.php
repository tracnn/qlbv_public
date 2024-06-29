<style>
.navbar-header {
    height: 50px; /* Đặt chiều cao cụ thể cho navbar-header nếu cần */
}

.navbar-brand {
    padding: 2px 2px; /* Điều chỉnh padding để logo không bị ép sát vào các cạnh */
    height: 100%; /* Đặt chiều cao cụ thể cho .navbar-brand để logo có không gian hiển thị đầy đủ */
    display: flex; /* Sử dụng flexbox để căn chỉnh logo */
    align-items: center; /* Căn giữa logo theo chiều dọc */
}

.navbar-brand img {
    height: auto; /* Đặt chiều cao tự động để giữ tỷ lệ */
    width: auto; /* Đặt chiều rộng tự động để giữ tỷ lệ */
    max-height: 100%; /* Đảm bảo logo không vượt quá chiều cao của .navbar-brand */
    max-width: 100%; /* Ngăn logo không vượt quá chiều rộng của .navbar-brand */
}
.navbar-brand .hospital-name {
    margin-left: 10px;
    font-size: 14px; /* Điều chỉnh kích thước font chữ cho phù hợp */
    color: #337ab7; /* Màu sắc có thể thay đổi tùy theo sở thích */
    white-space: nowrap; /* Đảm bảo tên không bị wrap nếu không cần thiết */
    overflow: hidden; /* Ngăn chặn text bị tràn nếu quá dài */
    text-overflow: ellipsis; /* Thêm dấu ... nếu text quá dài */
}
</style>
<div class="container">
  <!-- Brand and toggle get grouped for better mobile display -->
  <nav class="navbar navbar-default">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="#"><img src="{{asset('images/logo.png')}}" alt="Logo" title="Hospital Logi">
        <span class="hospital-name">Bệnh Viện Đa Khoa Nông Nghiệp</span>
      </a>
      
    </div>

    <!-- Collect the nav links, forms, and other content for toggling -->
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
      <ul class="nav navbar-nav">
        <li class="active"><a href="#">Trang chủ <span class="sr-only">(current)</span></a></li>
        <li><a href="#">Các dịch vụ</a></li>
        <li><a href="#">Bảng giá</a></li>
        <li><a href="#">Liên hệ</a></li>
      </ul>
    </div><!-- /.navbar-collapse -->
  </nav>
</div>