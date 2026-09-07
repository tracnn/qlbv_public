{{-- Cac vung hien thi cua luong tra cuu AJAX. Noi dung do public/js/mcct-tra-cuu.js dung -
     CUNG mot bo ma voi modal tren man tra cuu the, de hai man khong bao gio hien khac nhau. --}}

{{-- Trang thai DANG TAI. Cong tra ve cham (5-60 giay) nen o day phai co dong ho chay: mot
     vong xoay dung yen sau 30 giay trong y het mot trang chet, nguoi dung se bam lai - va moi
     lan bam la mot luot goi cong. --}}
<div id="mcct-dang-tai" style="display: none;" class="panel panel-default">
    <div class="panel-body text-center">
        <p style="font-size: 15px;">
            <i class="fa fa-spinner fa-spin"></i>&nbsp;
            <span id="mcct-cau-cho">Đang hỏi cổng BHXH…</span>
        </p>
        <div class="progress progress-striped active" style="max-width: 420px; margin: 0 auto;">
            <div class="progress-bar progress-bar-info" style="width: 100%"></div>
        </div>
        <p class="text-muted" style="margin-top: 10px;">
            Đã chờ <b id="mcct-giay">0</b> giây. Cổng thường trả lời trong 5–30 giây.
        </p>
    </div>
</div>

<div id="mcct-loi" style="display: none;" class="panel panel-default">
    <div class="panel-body">
        <div class="alert alert-danger" id="mcct-loi-noi-dung"></div>
        <div class="text-center">
            <button type="button" class="btn btn-primary" id="mcct-thu-lai">
                <i class="fa fa-refresh"></i>&nbsp;Thử lại
            </button>
        </div>
    </div>
</div>

{{-- De TRONG va khong boc them lop nao: javascript ghi de toan bo noi dung o day bang
     .html(), nen mot lop boc san se bi xoa ngay lan tra dau tien. --}}
<div id="mcct-ket-qua" style="display: none;"></div>
