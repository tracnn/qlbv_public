{{-- Than man chi tiet ho so danh muc TT12.

     Bien vao: $hoSo (Tt12HoSo), $cacTab (Tt12DetailTabs::cacTab())

     TEP NAY KHONG DUOC CHUA THE SCRIPT. No duoc nap bang AJAX vao modal tren man danh sach,
     va moi khoi @@push trong mot fragment nap kieu do se bi bo di KHONG MOT LOI BAO - nut
     van hien, van bam duoc, va khong co gi xay ra. Moi hanh vi nam o
     bhyt.tt12.partials.js-chi-tiet, gan bang uy nhiem su kien.

     CHI CO MOT THAN CHI TIET TREN MOI TRANG: tep nay dung dinh danh co dinh
     (#noi-dung-tab, #tt12-tabs, #btn-ky-va-gui), nen nhung no hai lan tren cung mot trang
     se lam tab nap nham cho.

     Tt12ChiTietModalTest canh dieu do. --}}
<div class="tt12-chi-tiet" data-ma-ho-so="{{ $hoSo->ma_ho_so }}">

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-3"><strong>Mẫu:</strong> {{ $hoSo->mau }}</div>
            <div class="col-sm-3"><strong>Mã CSKCB:</strong> {{ $hoSo->ma_cskcb }}</div>
            <div class="col-sm-3"><strong>Tên tệp:</strong> {{ $hoSo->ten_tep }}</div>
            <div class="col-sm-3"><strong>Số dòng:</strong> {{ $hoSo->so_dong }}</div>
        </div>
        <div class="row" style="margin-top:8px">
            <div class="col-sm-3"><strong>Số lỗi:</strong>
                {{ empty($hoSo->checked_at) ? 'Chưa kiểm' : $hoSo->so_loi }}
            </div>
            <div class="col-sm-3"><strong>Ký số:</strong> {{ $hoSo->is_signed ? 'Đã ký' : 'Chưa ký' }}</div>
            <div class="col-sm-3"><strong>Mã giao dịch:</strong> {{ $hoSo->ma_gd ?: '—' }}</div>
            <div class="col-sm-3"><strong>Mã kết quả:</strong> {{ $hoSo->ma_ket_qua ?: '—' }}</div>
        </div>
        <div class="row" style="margin-top:8px">
            <div class="col-sm-3"><strong>Tiếp nhận lúc:</strong> {{ $hoSo->thoi_gian_tiep_nhan ?: '—' }}</div>
            <div class="col-sm-3"><strong>Nạp lúc:</strong> {{ $hoSo->imported_at }}</div>
        </div>
        @if ($hoSo->import_error)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                {{-- Tt12Importer CO Y giu lai ho so do dang kem ly do "de nguoi dung nhin
                     thay va xoa". Khong in ra day thi nguoi dung thay mot ho so 0 dong,
                     "Chua kiem", khong ky duoc, va khong mot chu giai thich. --}}
                <div class="alert alert-danger" style="margin-bottom:4px">
                    <strong>Lỗi nạp tệp:</strong> {{ $hoSo->import_error }}
                    <br><small>Hồ sơ này không sửa được trên màn hình: sửa tệp Excel rồi nạp lại, và xoá hồ sơ này.</small>
                </div>
            </div>
        </div>
        @endif
        @if ($hoSo->signed_error)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                {{-- signed_error chua thong diep tu dich vu ky - du lieu ben ngoai. --}}
                <div class="alert alert-danger" style="margin-bottom:4px">
                    <strong>Lỗi ký số:</strong> {{ $hoSo->signed_error }}
                </div>
            </div>
        </div>
        @endif
        @if ($hoSo->submit_error)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                <div class="alert alert-warning" style="margin-bottom:4px">
                    <strong>Lỗi gửi:</strong> {{ $hoSo->submit_error }}
                </div>
            </div>
        </div>
        @endif
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                <button type="button" id="btn-ky-va-gui" class="btn btn-primary btn-sm">
                    <i class="fa fa-paper-plane"></i> Ký và gửi
                </button>
                @if (empty($hoSo->checked_at) && !$hoSo->is_signed && !\App\Services\Tt12\Tt12QuyetDinhGui::daTiepNhan($hoSo->ma_ket_qua))
                {{-- Hàng đợi tắt lúc nạp hoặc job hết lượt thử thì hồ sơ nằm mãi ở
                     "Chưa kiểm" - không ký được và không có đường nào kiểm lại. --}}
                <button type="button" id="btn-kiem-lai" class="btn btn-default btn-sm">
                    <i class="fa fa-check-square-o"></i> Kiểm lại
                </button>
                @endif
                @if (\App\Services\Tt12\Tt12QuyetDinhGui::daTiepNhan($hoSo->ma_ket_qua) && empty($hoSo->dong_bo_at))
                {{-- Cổng đã tiếp nhận mà chưa đồng bộ: bước đồng bộ đã hỏng giữa chừng và
                     lần thử lại của hàng đợi sẽ dừng sớm vì hồ sơ đã tiếp nhận. --}}
                <button type="button" id="btn-dong-bo-lai" class="btn btn-warning btn-sm">
                    <i class="fa fa-refresh"></i> Đồng bộ lại danh mục
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Khong dat id="tt12-chi-tiet" o day nua: ma ho so gio nam tren the boc ngoai cung
     (class tt12-chi-tiet) va JS doc bang .closest(). Giu ca hai la de hai thu cung ten tro
     hai phan tu khac nhau. --}}
<div class="nav-tabs-custom">
    <ul class="nav nav-tabs" id="tt12-tabs">
        @foreach ($cacTab as $ma => $nhan)
        <li class="{{ $loop->first ? 'active' : '' }}">
            <a href="#" data-tab="{{ $ma }}">{{ $nhan }}</a>
        </li>
        @endforeach
    </ul>
    <div class="tab-content">
        <div id="noi-dung-tab" style="padding:15px"><p class="text-muted">Đang tải…</p></div>
    </div>
</div>

</div>
