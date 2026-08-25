{{-- Bo loc man danh sach chung tu dien tu.

     Dung lai cac partial dung chung giong man XML3176, de hai man cung nghiep vu lien
     thong BHXH co cung dang va cung cach van hanh - nguoi dung khong phai hoc lai.

     Bien vao:
       $danhSachCoSo      — mang ma => nhan, tu DanhSachCoSo::danhSach()
       $danhSachLoai      — mang LOAIHOSO => nhan tab, tu CtdtLoaiRegistry
       $danhSachTrangThai — mang ma => nhan, tu CtdtTrangThaiGui::NHAN

     LUU Y VE HOP DONG JAVASCRIPT: partials.load_data_button goi ham TOAN CUC
     fetchData(startDate, endDate) va tu goi mot lan ngay khi trang tai xong. Man
     danh sach (index.blade.php) phai dinh nghia ham do - xem ghi chu o day.

     showExport = false: module nay chua co duong xuat Excel (thuoc Giai doan 5), de
     nut Export cua partial hien ra se la mot nut chet. --}}
<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range', ['showExport' => false])

        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="dich_vu">Dịch vụ</label>
                        <select id="dich_vu" class="form-control select2">
                            <option value="">Tất cả dịch vụ</option>
                            @foreach (config('ctdt.dich_vu') as $ma => $cauHinh)
                                <option value="{{ $ma }}">{{ $cauHinh['ten'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="loai_ho_so">Loại chứng từ</label>
                        <select id="loai_ho_so" class="form-control select2">
                            <option value="">Tất cả loại</option>
                            @foreach ($danhSachLoai as $ma => $ten)
                                <option value="{{ $ma }}">{{ $ten }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @include('partials.ma_cskcb', ['danhSachCoSo' => $danhSachCoSo])
                @include('partials.imported_by')
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="trang_thai_gui">Trạng thái gửi</label>
                        <select id="trang_thai_gui" class="form-control select2">
                            <option value="">Tất cả trạng thái</option>
                            @foreach ($danhSachTrangThai as $ma => $nhan)
                                <option value="{{ $ma }}">{{ $nhan }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="chi_con_loi">Chỉ hồ sơ còn lỗi</label>
                        <select id="chi_con_loi" class="form-control select2">
                            <option value="0">Không</option>
                            <option value="1">Có</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12">
            <div class="form-group row">
                @include('partials.o_tim', [
                    'nhan' => 'Tìm mã hồ sơ / mã thẻ / họ tên / số CCCD / mã BHXH',
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
