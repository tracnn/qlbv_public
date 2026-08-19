{{-- Bo loc man danh sach chung tu dien tu.

     Bien vao: $danhSachCoSo — mang ma => nhan, tu DanhSachCoSo::danhSach() --}}
<div class="panel panel-default">
    <div class="panel-body">
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-2">
                    <label for="tu_ngay">Từ ngày nạp</label>
                    <input type="date" id="tu_ngay" class="form-control">
                </div>
                <div class="col-sm-2">
                    <label for="den_ngay">Đến ngày nạp</label>
                    <input type="date" id="den_ngay" class="form-control">
                </div>
                <div class="col-sm-2">
                    <label for="dich_vu">Dịch vụ</label>
                    <select id="dich_vu" class="form-control">
                        <option value="">Tất cả dịch vụ</option>
                        @foreach (config('ctdt.dich_vu') as $ma => $cauHinh)
                            <option value="{{ $ma }}">{{ $cauHinh['ten'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2">
                    <label for="loai_ho_so">Loại chứng từ</label>
                    <select id="loai_ho_so" class="form-control">
                        <option value="">Tất cả loại</option>
                        @foreach ($danhSachLoai as $ma => $ten)
                            <option value="{{ $ma }}">{{ $ten }}</option>
                        @endforeach
                    </select>
                </div>
                @include('partials.ma_cskcb', ['danhSachCoSo' => $danhSachCoSo])
                <div class="col-sm-2">
                    <label for="trang_thai_gui">Trạng thái gửi</label>
                    <select id="trang_thai_gui" class="form-control">
                        <option value="">Tất cả trạng thái</option>
                        @foreach ($danhSachTrangThai as $ma => $nhan)
                            <option value="{{ $ma }}">{{ $nhan }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-4">
                    <label for="tim">Tìm mã hồ sơ / mã thẻ / họ tên</label>
                    <input type="text" id="tim" class="form-control" placeholder="Nhập rồi bấm Tải dữ liệu">
                </div>
                <div class="col-sm-2">
                    <label for="chi_con_loi">&nbsp;</label>
                    <div class="checkbox">
                        <label><input type="checkbox" id="chi_con_loi"> Chỉ hồ sơ còn lỗi</label>
                    </div>
                </div>
                <div class="col-sm-2">
                    <label for="btn_tai_du_lieu">&nbsp;</label>
                    <button id="btn_tai_du_lieu" class="btn btn-primary form-control">
                        <i class="fa fa-refresh"></i> Tải dữ liệu
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
