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
                <td class="col-md-4">Ngưỡng {{ config('mcct.so_thang_luong_co_so') }} tháng lương cơ sở:
                    <b>{{ number_format($nguong, 0, ',', '.') }} đ</b></td>
                <td class="col-md-4">
                    @if ($duDieuKien)
                        <span class="label label-success">ĐỦ ĐIỀU KIỆN MIỄN CÙNG CHI TRẢ</span>
                    @else
                        <span class="label label-warning">CHƯA ĐỦ ĐIỀU KIỆN MIỄN CÙNG CHI TRẢ</span>
                    @endif
                </td>
            </tr>
        </table>

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
            </tr>
            @foreach ($lichSu as $ls)
            <tr>
                <td>{{ $ls->tra_luc }}</td>
                <td>{{ $ls->ma_cskcb }}</td>
                <td>{{ $ls->ma_ket_qua }}</td>
                <td class="text-right">{{ number_format($ls->luy_ke_lon_nhat, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($ls->nguong_ap_dung, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endif
