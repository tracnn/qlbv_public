@extends('adminlte::page')

@section('title', 'Nạp hồ sơ chứng từ điện tử')

@section('content_header')
<h1>Nạp <small>hồ sơ chứng từ điện tử</small></h1>
@stop

@push('after-styles')
<style>
    .canh-bao-ghi-de { color: #b94a48; }
</style>
@endpush

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-4">
                <label for="macskcb_nap">Cơ sở KCB</label>
                <select id="macskcb_nap" class="form-control">
                    <option value="">Lấy theo gói, hoặc cấu hình đơn vị</option>
                    @foreach ($danhSachCoSo as $ma => $nhan)
                        <option value="{{ $ma }}">{{ $nhan }}</option>
                    @endforeach
                </select>
                <p class="help-block">
                    Gói giấy chứng sinh không mang mã cơ sở — chọn ở đây nếu muốn ghi đè
                    giá trị mặc định của đơn vị.
                </p>
            </div>
        </div>
        <form action="{{ route('bhyt.ctdt.upload') }}" class="dropzone" id="ctdtUploadForm">
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
                        <th>Tệp</th>
                        <th>Hồ sơ vào được</th>
                        <th>Hồ sơ hỏng</th>
                        <th>Ghi chú</th>
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
Dropzone.options.ctdtUploadForm = {
    paramName: "xmls",
    maxFilesize: 100,
    acceptedFiles: ".xml",
    timeout: 600000,
    previewTemplate: '<div></div>',
    init: function () {
        var dz = this;

        dz.on("sending", function (file, xhr, formData) {
            formData.append("macskcb", $('#macskcb_nap').val());
        });

        dz.on("success", function (file, phanHoi) {
            veKetQua(phanHoi);
        });

        dz.on("error", function (file, loi, xhr) {
            // Loi tang HTTP (413, 500, het gio): Dropzone dua vao day chu khong vao success.
            // Bo qua thi nguoi dung thay bang trong va tuong tep da vao.
            var thongDiep = (loi && loi.thong_diep) ? loi.thong_diep
                : (typeof loi === 'string' ? loi : 'Tải lên thất bại');

            themDong(file.name, 0, 0, thongDiep, []);
        });
    }
};

function veKetQua(phanHoi) {
    if (!phanHoi || !phanHoi.chi_tiet) {
        return;
    }

    phanHoi.chi_tiet.forEach(function (ct) {
        themDong(ct.tep, ct.so_thanh_cong, ct.so_that_bai, ct.ly_do, ct.ghi_de_da_gui);
    });
}

function themDong(tep, soThanhCong, soThatBai, lyDo, ghiDe) {
    var ghiChu = lyDo ? $('<div>').text(lyDo).html() : '';

    if (ghiDe && ghiDe.length) {
        // Nguoi dung duoc phep ghi de, nhung phai BIET ho so nao vua mat trang thai gui.
        var dong = ghiDe.map(function (g) {
            return $('<div>').text('Hồ sơ ' + g.ma_ho_so + ' đã gửi (MaGD ' + g.ma_gd
                + ') vừa bị ghi đè').html();
        }).join('<br>');

        ghiChu += (ghiChu ? '<br>' : '') + '<span class="canh-bao-ghi-de">' + dong + '</span>';
    }

    $('#ketQuaNap tbody').append(
        '<tr>'
        + '<td>' + $('<div>').text(tep).html() + '</td>'
        + '<td>' + soThanhCong + '</td>'
        + '<td>' + soThatBai + '</td>'
        + '<td>' + ghiChu + '</td>'
        + '</tr>'
    );
}
</script>
@endpush
