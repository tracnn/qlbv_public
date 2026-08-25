@extends('adminlte::page')

@section('title', 'Nạp danh mục TT12/2026/BTC')

@section('content_header')
<h1>Nạp <small>danh mục TT12/2026/BTC</small></h1>
@stop

@push('after-styles')
<style>
    .canh-bao-o-dien { color: #b94a48; }
</style>
@endpush

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="mau">Mẫu</label>
                <select id="mau" name="mau" class="form-control">
                    <option value="">-- Tự nhận diện từ hàng tiêu đề --</option>
                    @foreach ($danhSachMau as $ma => $ten)
                        <option value="{{ $ma }}">{{ $ten }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    Chọn mẫu để hệ thống đối chiếu với nội dung tệp. Nếu tệp không khớp mẫu bạn
                    chọn, tệp sẽ bị từ chối thay vì nạp nhầm danh mục.
                </small>
            </div>
            <div class="col-md-6">
                <label for="ma_cskcb">Cơ sở khám chữa bệnh <span class="text-danger">*</span></label>
                <select id="ma_cskcb" name="ma_cskcb" class="form-control" required>
                    <option value="">-- Chọn cơ sở --</option>
                    @foreach ($danhSachCoSo as $ma => $nhan)
                        <option value="{{ $ma }}">{{ $nhan }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    Hồ sơ sẽ được ký và gửi bằng tài khoản cổng BHXH của chính cơ sở này.
                    <b>Mọi dòng trong tệp phải có <code>MA_CSKCB</code> trùng cơ sở này</b> — lệch
                    dù một dòng thì cả tệp bị từ chối, không nạp gì. Dòng để trống cột
                    <code>MA_CSKCB</code> sẽ được điền theo ô này.
                    @if (empty($danhSachCoSo))
                        <br><span class="text-danger">Chưa đọc được danh sách cơ sở từ HIS.</span>
                    @endif
                </small>
            </div>
        </div>
        <form action="{{ route('bhyt.tt12.upload') }}" class="dropzone" id="tt12UploadForm">
            {{ csrf_field() }}
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <div class="h4">Kết quả nạp</div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="ketQuaNap">
                <thead>
                    <tr>
                        <th>Tên tệp</th>
                        <th>Mẫu</th>
                        <th>Số dòng</th>
                        <th>Mã hồ sơ</th>
                        <th>Kết quả</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@stop

@push('after-scripts')
<script src="{{ asset('js/dropzone.min.js') }}"></script>
<script>
Dropzone.options.tt12UploadForm = {
    paramName: "tepExcel",
    maxFilesize: 20,
    acceptedFiles: ".xlsx,.xls",
    timeout: 600000,
    previewTemplate: '<div></div>',
    init: function () {
        var dz = this;

        dz.on("sending", function (file, xhr, formData) {
            formData.append('mau', document.getElementById('mau').value);
            formData.append('ma_cskcb', document.getElementById('ma_cskcb').value);
        });

        dz.on("success", function (file, phanHoi) {
            veKetQua(phanHoi);
        });

        dz.on("error", function (file, loi, xhr) {
            // Loi tang HTTP (413, 500, het gio): Dropzone dua vao day chu khong vao success.
            // Bo qua thi nguoi dung thay bang trong va tuong tep da vao.
            var thongDiep = (loi && loi.thong_diep) ? loi.thong_diep
                : (typeof loi === 'string' ? loi : 'Tải lên thất bại');

            themDong({
                ten_tep: file.name,
                mau: null,
                so_dong: 0,
                ma_ho_so: null,
                thanh_cong: false,
                loi: thongDiep,
                so_o_dien_them: 0
            });
        });
    }
};

function veKetQua(phanHoi) {
    if (!phanHoi || !phanHoi.chi_tiet) {
        return;
    }

    phanHoi.chi_tiet.forEach(function (ct) {
        themDong(ct);
    });
}

function themDong(ct) {
    var ketQua = ct.thanh_cong
        ? '<span class="label label-success">Thành công</span>'
        : '<span class="label label-danger">Thất bại</span>'
            + (ct.loi ? '<br>' + $('<div>').text(ct.loi).html() : '');

    if (ct.so_o_dien_them && ct.so_o_dien_them > 0) {
        // Tu dien khong duoc im lang: nguoi dung phai biet he thong da tu ghi vao bao
        // nhieu o MA_CSKCB tep goc bo trong.
        ketQua += '<br><span class="canh-bao-o-dien">Đã điền MA_CSKCB cho '
            + ct.so_o_dien_them + ' dòng bỏ trống</span>';
    }

    $('#ketQuaNap tbody').append(
        '<tr>'
        + '<td>' + $('<div>').text(ct.ten_tep).html() + '</td>'
        + '<td>' + (ct.mau ? $('<div>').text(ct.mau).html() : '') + '</td>'
        + '<td>' + (ct.so_dong || 0) + '</td>'
        + '<td>' + (ct.ma_ho_so ? $('<div>').text(ct.ma_ho_so).html() : '') + '</td>'
        + '<td>' + ketQua + '</td>'
        + '</tr>'
    );
}
</script>
@endpush
