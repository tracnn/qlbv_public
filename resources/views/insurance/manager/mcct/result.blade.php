@if (isset($ketQua) && $ketQua->thanhCong())
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group"><b>Thông tin thẻ</b></div>
        <table class="table table-condensed">
            <tr>
                <td class="col-md-3">Họ tên: <b>{{ array_get($ketQua->thongTinThe, 'ho_ten') }}</b></td>
                <td class="col-md-3">Ngày sinh: {{ array_get($ketQua->thongTinThe, 'ngay_sinh') }}</td>
                <td class="col-md-3">Mã số BHXH: {{ array_get($ketQua->thongTinThe, 'ma_bhxh') }}</td>
                <td class="col-md-3">Thẻ hết hạn: {{ array_get($ketQua->thongTinThe, 'ngay_ket_thuc') }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="panel {{ $duDieuKien ? 'panel-success' : 'panel-warning' }}">
    <div class="panel-body">
        <table class="table table-condensed">
            <tr>
                <td class="col-md-4">Lũy kế cùng chi trả:
                    <b>{{ number_format($ketQua->luyKeLonNhat(), 0, ',', '.') }} đ</b></td>
                {{-- Tong nguong CA NAM chu khong phai so tien con phai dong: chi con so nay moi
                     so sanh duoc truc tiep voi luy ke ben canh, vi ca hai cung tinh tu 01/01. --}}
                <td class="col-md-4">Ngưỡng cả năm:
                    <b>{{ number_format($muc['tong_nguong_ca_nam'], 0, ',', '.') }} đ</b></td>
                <td class="col-md-4">
                    @if ($duDieuKien)
                        <span class="label label-success">ĐỦ NGƯỠNG 6 THÁNG LƯƠNG CƠ SỞ</span>
                    @else
                        <span class="label label-warning">CÒN THIẾU
                            {{ number_format($muc['con_thieu'], 0, ',', '.') }} đ</span>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Chi hien khi DAT nguong: API MCCT khong tra ve du kien 5 nam lien tuc, nen man
             hinh nay KHONG duoc phep ket luan thay ca dieu kien do. Nhan o tren vi vay chi ghi
             "du nguong", con ve con lai phai co nguoi kiem. --}}
        @if ($duDieuKien)
        <div class="alert alert-info" style="padding: 6px 10px; margin-bottom: 8px;">
            Mới chỉ đạt <b>ngưỡng tiền</b>. Cần kiểm tra thêm điều kiện <b>tham gia BHYT đủ 5 năm
            liên tục</b> mới đủ điều kiện miễn cùng chi trả — dữ kiện này cổng không trả về.
        </div>
        @endif

        {{-- Chi hien khi trong nam CO moc doi luong co so. Khong co dong nay, nguoi dung se tu
             tinh 6 x luong hien hanh tru luy ke roi tuong phan mem sai - con so cua ta khac,
             va khac la DUNG theo diem c khoan 2 Dieu 18 ND 188/2025. --}}
        @if ($muc['co_doi_luong'])
        <div class="text-muted" style="margin-bottom: 6px;">
            Lương cơ sở đổi ngày {{ date('d/m/Y', strtotime($muc['moc_doi_luong'])) }}. Đã cùng chi
            trả {{ number_format($muc['da_dong_truoc_moc'], 0, ',', '.') }} đ trước mốc, tương đương
            {{ number_format($muc['luong_truoc_moc'] > 0 ? $muc['da_dong_truoc_moc'] / $muc['luong_truoc_moc'] : 0, 2, ',', '.') }}
            tháng lương cũ ({{ number_format($muc['luong_truoc_moc'], 0, ',', '.') }} đ); còn phải
            cùng chi trả {{ number_format($muc['so_thang_con_lai'], 2, ',', '.') }} tháng ×
            {{ number_format($muc['luong_hien_tai'], 0, ',', '.') }} đ =
            <b>{{ number_format($muc['so_tien_con_phai_dong'], 0, ',', '.') }} đ</b>; cộng phần đã
            đóng trước mốc thành ngưỡng cả năm
            <b>{{ number_format($muc['tong_nguong_ca_nam'], 0, ',', '.') }} đ</b>.
        </div>
        @endif

        {{-- GhiChu NGUYEN VAN: no ghi du lieu cong "tinh den" thoi diem nao. So lieu cong co
             do tre, nguoi dung phai thay moc do TRUOC khi ket luan voi nguoi benh. --}}
        <small class="text-muted">{{ $ketQua->ghiChu }}</small>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group"><b>Chi tiết các đợt khám chữa bệnh</b></div>
        <table class="table table-condensed table-hover">
            <tr>
                <th>Mã CSKCB</th>
                <th>Ngày vào</th>
                <th>Ngày ra</th>
                <th>Đối tượng</th>
                <th class="text-right">Tiền CCT thuộc diện miễn</th>
                <th class="text-right">Lũy kế</th>
                <th>Ngày nhận</th>
            </tr>
            {{-- Giu NGUYEN thu tu cong tra (da giam dan theo ngay ra vien), khong sap lai --}}
            @foreach ($ketQua->dong as $d)
            <tr>
                <td>{{ $d['ma_cskcb'] }}</td>
                <td>{{ $d['ngay_vao'] }}</td>
                <td>{{ $d['ngay_ra'] }}</td>
                <td>{{ $d['ma_doi_tuong_kcb'] }}</td>
                <td class="text-right">{{ number_format($d['t_bn_cct_mcct'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($d['t_bn_cct_luy_ke'], 0, ',', '.') }}</td>
                <td>{{ $d['ngay_nhan'] }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endif

@if (isset($lichSu) && count($lichSu) > 0)
<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group"><b>Lịch sử tra cứu thẻ này</b></div>
        <table class="table table-condensed">
            <tr>
                <th>Thời điểm</th>
                <th>Cơ sở</th>
                <th>Kết quả</th>
                <th class="text-right">Lũy kế</th>
                <th class="text-right">Ngưỡng khi tra</th>
                <th>Người tra</th>
            </tr>
            @foreach ($lichSu as $ls)
            <tr>
                <td>{{ $ls->tra_luc }}</td>
                <td>{{ $ls->ma_cskcb }}</td>
                <td>{{ $ls->ma_ket_qua }}</td>
                <td class="text-right">{{ number_format($ls->luy_ke_lon_nhat, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($ls->nguong_ap_dung, 0, ',', '.') }}</td>
                <td>{{ $ls->tra_boi }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endif
