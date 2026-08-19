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
// KHONG boc trong $(function(){...}): partials.load_data_button goi ham TOAN CUC
// fetchData(startDate, endDate) va tu goi mot lan ngay khi trang tai xong. Ham nay phai
// nam o pham vi window de no thay duoc.
var ctdtBang = null;

// Khoang ngay cua lan tai HIEN TAI. Phai o pham vi module chu khong phai tham so cua
// fetchData(): DataTable chi duoc dung MOT lan, nen closure "data" ben trong no phai doc
// duoc gia tri moi nhat o cac lan tai sau.
var ctdtRange = { from: null, to: null };

function ctdtNhanDichVu(ma) {
    var nhan = @json(collect(config('ctdt.dich_vu'))->map(function ($c) { return $c['ten']; }));

    return nhan[ma] ? nhan[ma] : ma;
}

function fetchData(startDate, endDate) {
    ctdtRange.from = startDate;
    ctdtRange.to = endDate;

    if (ctdtBang) {
        ctdtBang.ajax.reload();
        return;
    }

    ctdtBang = $('#ctdt-list').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        order: [[11, 'desc']],
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        ajax: {
            url: "{{ route('bhyt.ctdt.fetch-data') }}",
            data: function (d) {
                // Khoang ngay den tu partials.date_range qua partials.load_data_button,
                // dang 'YYYY-MM-DD HH:mm:ss'. CtdtDanhSach nhan ca dang co gio lan dang
                // chi co ngay.
                d.tu_ngay        = ctdtRange.from;
                d.den_ngay       = ctdtRange.to;
                d.dich_vu        = $('#dich_vu').val();
                d.loai_ho_so     = $('#loai_ho_so').val();
                d.macskcb        = $('#ma_cskcb').val();
                d.imported_by    = $('#imported_by').val();
                d.tim            = $('#tim').val();
                d.chi_con_loi    = $('#chi_con_loi').val();
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
                    var nhan = ctdtNhanDichVu(d);

                    return type === 'display' ? $('<div>').text(nhan).html() : nhan;
                }
            },
            { "data": "macskcb", render: $.fn.dataTable.render.text() },
            { "data": "ho_ten", orderable: false, searchable: false, render: $.fn.dataTable.render.text() },
            { "data": "ma_the", orderable: false, searchable: false, render: $.fn.dataTable.render.text() },
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
            { "data": "trang_thai_nhan", orderable: false, searchable: false, render: $.fn.dataTable.render.text() },
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
</script>
@endpush
