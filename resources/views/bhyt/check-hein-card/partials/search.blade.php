{{-- Bo loc man ket qua tra cuu the BHYT.

     Bien vao:
       $danhSachCoSo — mang ma => nhan, tu DanhSachCoSo::danhSach()

     Dung lai cac partial dung chung giong man XML3176 va chung tu dien tu, de ba man cung
     nghiep vu lien thong BHXH co cung dang va cung cach van hanh - nguoi dung khong phai
     hoc lai o moi man.

     LUU Y VE HOP DONG JAVASCRIPT: partials.load_data_button goi ham TOAN CUC
     fetchData(startDate, endDate) va tu goi mot lan ngay khi trang tai xong. Man danh sach
     (index.blade.php) phai dinh nghia ham do o pham vi window - xem ghi chu o day.

     showExport = false: man nay da co nut "Xuat Excel" rieng o index, bat nut cua partial
     nua la hai nut lam mot viec. --}}
<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range', ['showExport' => false])

        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="trang_thai">Trạng thái</label>
                        <select id="trang_thai" class="form-control select2">
                            <option value="">Tất cả</option>
                            <option value="loi">Chỉ lỗi</option>
                            <option value="hop_le">Chỉ hợp lệ</option>
                        </select>
                    </div>
                </div>

                {{-- Dung khuon mac dinh cua partial (col-sm-2 + form-group row), giong ho
                     partial cua XML3176. Truoc day man nay truyen col-md-3 va formGroup=false
                     vi khoi loc con la mot <div class="row"> tu che. --}}
                @include('partials.ma_cskcb')

                <div class="col-sm-4">
                    <div class="form-group row">
                        <label for="tim">Tìm hồ sơ/thẻ/họ tên</label>
                        <input type="text" id="tim" class="form-control"
                               placeholder="mã hồ sơ, số thẻ, họ tên...">
                    </div>
                </div>
            </div>
        </div>

        @include('partials.load_data_button')
    </div>
</div>

@push('after-scripts')
    {{-- Thu tu KHONG quan trong giua hai stack nay, nhung ca hai PHAI duoc day ra: moi
         partial tu dat script cua no vao mot stack rieng, khong day thi partial im lang
         khong hoat dong - o chon khoang thoi gian se thanh mot o text tron, va nut "Tai du
         lieu" bam khong ra gi. Khong loi console, khong dau hieu gi. --}}
    @stack('after-scripts-date-range')
    @stack('after-scripts-load-data-button')
@endpush
