{{-- Bo loc man danh sach ho so danh muc TT12/2026/BTC.

     Dung lai cac partial dung chung giong man XML3176 va man chung tu dien tu, de ba man
     cung nghiep vu lien thong BHXH co cung dang va cung cach van hanh - nguoi dung khong
     phai hoc lai.

     Bien vao:
       $danhSachMau  — mang MAU_0x => ten hien thi, tu Tt12MauRegistry
       $danhSachCoSo — mang ma => nhan, tu DanhSachCoSo::danhSach()
       $cacTrangThai — mang ma => nhan, tu Tt12DanhSach::cacTrangThai()

     LUU Y VE HOP DONG JAVASCRIPT: partials.load_data_button goi ham TOAN CUC
     fetchData(startDate, endDate) va tu goi mot lan ngay khi trang tai xong. Man danh
     sach (index.blade.php) phai dinh nghia ham do.

     showExport = false: TT12 co HAI nut xuat rieng (danh sach va bang loi) dat tren bang.
     Bat them nut export_xlsx cua date_range se thanh nut thu ba khong noi vao dau. --}}
<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range', ['showExport' => false])

        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="mau">Mẫu</label>
                        <select id="mau" class="form-control select2">
                            <option value="">Tất cả mẫu</option>
                            @foreach ($danhSachMau as $ma => $ten)
                                <option value="{{ $ma }}">{{ $ten }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @include('partials.ma_cskcb', ['danhSachCoSo' => $danhSachCoSo])
                @include('partials.imported_by')
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="trang_thai">Trạng thái</label>
                        <select id="trang_thai" class="form-control select2">
                            <option value="">Tất cả trạng thái</option>
                            @foreach ($cacTrangThai as $ma => $nhan)
                                <option value="{{ $ma }}">{{ $nhan }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12">
            <div class="form-group row">
                @include('partials.o_tim', [
                    'nhan' => 'Tìm mã hồ sơ / tên tệp / mã giao dịch',
                ])
            </div>
        </div>

        @include('partials.load_data_button')
    </div>
</div>

@push('after-scripts')
    {{-- Thu tu KHONG quan trong giua bon stack nay, nhung ca bon PHAI duoc day ra: moi
         partial tu dat script cua no vao mot stack rieng, khong day thi partial im lang
         khong hoat dong - o chon khoang thoi gian se thanh mot o text tron, con Enter
         trong o Tim se khong lam gi. --}}
    @stack('after-scripts-date-range')
    @stack('after-scripts-imported-by')
    @stack('after-scripts-o-tim')
    @stack('after-scripts-load-data-button')
@endpush
