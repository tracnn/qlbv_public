@extends('adminlte::page')

@section('title', 'Danh sách hồ sơ chứng từ điện tử')

@section('content_header')
<h1>Danh sách <small>hồ sơ chứng từ điện tử</small></h1>
@stop

@push('after-styles')
<style>
    .table-responsive { display: block; width: 100%; overflow-x: auto; }
    .nhan-canh-bao { color: #b94a48; font-weight: bold; }
</style>
@endpush

@section('content')
@include('includes.message')
@include('bhyt.ctdt.partials.search')

<div class="panel panel-default">
    <div class="panel-body">
        <div class="table-responsive">
            <table class="table table-hover table-bordered" id="ctdt-list" style="width:100%">
                <thead>
                    <tr>
                        <th>Mã hồ sơ</th>
                        <th>Dịch vụ</th>
                        <th>Mã CSKCB</th>
                        <th>Họ tên</th>
                        <th>Mã thẻ</th>
                        <th>Số CT</th>
                        <th>Số lỗi</th>
                        <th>Ký số</th>
                        <th>Trạng thái gửi</th>
                        <th>MaGD</th>
                        <th>Thời điểm tiếp nhận</th>
                        <th>Nạp lúc</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@stop

@push('after-scripts')
<script>
$(function () {
    var bang = null;

    function nhanDichVu(ma) {
        var nhan = @json(collect(config('ctdt.dich_vu'))->map(function ($c) { return $c['ten']; }));

        return nhan[ma] ? nhan[ma] : ma;
    }

    function taiDuLieu() {
        if (bang) {
            bang.ajax.reload();
            return;
        }

        bang = $('#ctdt-list').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            order: [[11, 'desc']],
            lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            ajax: {
                url: "{{ route('bhyt.ctdt.fetch-data') }}",
                data: function (d) {
                    d.tu_ngay        = $('#tu_ngay').val();
                    d.den_ngay       = $('#den_ngay').val();
                    d.dich_vu        = $('#dich_vu').val();
                    d.loai_ho_so     = $('#loai_ho_so').val();
                    d.macskcb        = $('#ma_cskcb').val();
                    d.tim            = $('#tim').val();
                    d.chi_con_loi    = $('#chi_con_loi').is(':checked') ? 1 : 0;
                    d.trang_thai_gui = $('#trang_thai_gui').val();
                }
            },
            columns: [
                {
                    // Ho ten, dich vu... deu doc thang tu tep XML ben ngoai (khong qua kiem
                    // duyet). Moi gia tri lot vao chuoi HTML PHAI qua $('<div>').text(x).html()
                    // truoc khi noi - khong thi mot ho ten dang "<img src=x onerror=...>" chay
                    // ngay khi mo man danh sach.
                    "data": "ma_ho_so",
                    render: function (data, type, row) {
                        if (type !== 'display') {
                            return data;
                        }

                        // ma_ho_so o nhanh lui chua dau '#' (vd Id-abc#1). Khong ma hoa thi
                        // trinh duyet cat tu dau '#' va URL tro sai ho so.
                        var url = "{{ route('bhyt.ctdt.detail', ['ma_ho_so' => '__MA__']) }}"
                                  .replace('__MA__', encodeURIComponent(data));
                        var canhBao = row.khong_co_ma_yte
                            ? ' <span class="nhan-canh-bao" title="Hồ sơ không có mã y tế — nạp lại sẽ tạo bản ghi mới, không ghi đè">⚠</span>'
                            : '';

                        return '<a href="' + url + '">' + $('<div>').text(data).html() + '</a>' + canhBao;
                    }
                },
                {
                    "data": "dich_vu",
                    render: function (d, type) {
                        var nhan = nhanDichVu(d);

                        return type === 'display' ? $('<div>').text(nhan).html() : nhan;
                    }
                },
                { "data": "macskcb", render: $.fn.dataTable.render.text() },
                { "data": "ho_ten", render: $.fn.dataTable.render.text() },
                { "data": "ma_the", render: $.fn.dataTable.render.text() },
                { "data": "so_chung_tu", render: $.fn.dataTable.render.text() },
                {
                    "data": "so_loi",
                    render: function (d, type) {
                        if (type !== 'display') {
                            return d;
                        }

                        var an = $('<div>').text(d).html();

                        return Number(d) > 0 ? '<span class="nhan-canh-bao">' + an + '</span>' : an;
                    }
                },
                { "data": "is_signed", render: function (d) { return Number(d) ? 'Đã ký' : '—'; } },
                { "data": "trang_thai_nhan", render: $.fn.dataTable.render.text() },
                { "data": "ma_gd", render: $.fn.dataTable.render.text() },
                { "data": "thoi_gian_tiep_nhan", render: $.fn.dataTable.render.text() },
                { "data": "imported_at", render: $.fn.dataTable.render.text() },
                {
                    "data": "action",
                    orderable: false,
                    searchable: false,
                    render: function (data, type) {
                        if (type !== 'display') {
                            return data;
                        }

                        var url = "{{ route('bhyt.ctdt.detail', ['ma_ho_so' => '__MA__']) }}"
                                  .replace('__MA__', encodeURIComponent(data));

                        return '<a class="btn btn-xs btn-default" href="' + url + '">Chi tiết</a>';
                    }
                }
            ],
            columnDefs: [{ targets: '_all', defaultContent: '' }]
        });
    }

    $('#btn_tai_du_lieu').on('click', function (e) {
        e.preventDefault();
        taiDuLieu();
    });

    taiDuLieu();
});
</script>
@endpush
