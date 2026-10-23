@extends('adminlte::page')

@section('title', 'Theo dõi sinh XML3176')

@section('content_header')
<h1>Theo dõi sinh XML3176 <small>mỗi dòng một hồ sơ, theo lần sinh mới nhất</small></h1>
{{ Breadcrumbs::render('bhyt.sinh-xml3176.index') }}
@stop

@push('after-styles')
<style>
    .table-responsive { display: block; width: 100%; overflow-x: auto; }
    .nhan-loi { color: #b94a48; font-weight: bold; }
    #ssx-tong-hop .ssx-o { display: inline-block; margin-right: 18px; }
</style>
@endpush

@section('content')
@include('includes.message')

{{-- Bien vao: $homNay (Y-m-d). Du lieu lay tu route fetch-data (server-side DataTables). --}}
<div class="panel panel-default">
    <div class="panel-heading"><strong>Bộ lọc</strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-2">
                <label for="ssx-tu">Từ ngày</label>
                <input type="date" id="ssx-tu" class="form-control" value="{{ $homNay }}">
            </div>
            <div class="col-sm-2">
                <label for="ssx-den">Đến ngày</label>
                <input type="date" id="ssx-den" class="form-control" value="{{ $homNay }}">
            </div>
            <div class="col-sm-2">
                <label for="ssx-kieu-ngay">Kiểu ngày</label>
                <select id="ssx-kieu-ngay" class="form-control">
                    <option value="sinh">Ngày sinh tệp</option>
                    <option value="ra_vien">Ngày ra viện</option>
                    <option value="khoa_vp">Ngày khoá viện phí</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label for="ssx-trang-thai">Trạng thái mới nhất</label>
                <select id="ssx-trang-thai" class="form-control">
                    <option value="">Tất cả</option>
                    <option value="da_ghi">Đã ghi</option>
                    <option value="bi_loai">Bị loại</option>
                    <option value="loi">Lỗi</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label for="ssx-loai-ban">Loại bản</label>
                <select id="ssx-loai-ban" class="form-control">
                    <option value="">Tất cả</option>
                    <option value="chinh_thuc">Chính thức</option>
                    <option value="thong_tuyen">Thông tuyến</option>
                </select>
            </div>
            <div class="col-sm-2">
                <label for="ssx-ma">Mã điều trị</label>
                <input type="text" id="ssx-ma" class="form-control" maxlength="50" placeholder="Nhập đủ mã">
            </div>
        </div>
        <div class="row" style="margin-top:10px;">
            <div class="col-sm-12">
                <button type="button" id="ssx-loc" class="btn btn-primary"><i class="fa fa-search"></i> Lọc</button>
                <span class="text-muted">Khoảng ngày tối đa 31 ngày.</span>
            </div>
        </div>
        <div id="ssx-thong-bao" class="alert alert-warning" style="display:none; margin:10px 0 0;"></div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body" id="ssx-tong-hop">
        <span class="text-muted">Chưa có dữ liệu.</span>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table class="table display table-hover wrap" width="100%" id="ssx-list" style="width:100%">
            <thead>
                <tr>
                    <th>Mã điều trị</th>
                    <th>Lần sinh mới nhất</th>
                    <th>Loại bản</th>
                    <th>Trạng thái</th>
                    <th>Lý do</th>
                    <th>Các bảng</th>
                    <th>Thư mục</th>
                    <th>Số lần sinh</th>
                    <th>Ngày ra viện</th>
                    <th>Ngày khoá viện phí</th>
                    <th></th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@push('after-scripts')
<script>
(function () {
    var URL_DATA = "{{ route('bhyt.sinh-xml3176.fetch-data') }}";
    var NHAN_TRANG_THAI = { da_ghi: 'Đã ghi', bi_loai: 'Bị loại', loi: 'Lỗi' };
    var NHAN_LOAI_BAN = { chinh_thuc: 'Chính thức', thong_tuyen: 'Thông tuyến' };
    var NHAN_THU_MUC = { that: 'Thật', thu: 'Thử', khac: 'Khác' };

    // yyyyMMddHHmmss -> dd/MM/yyyy HH:mm:ss; khong du 14 so thi giu nguyen.
    function gio14(s) {
        if (!s || String(s).length !== 14) { return s || ''; }
        s = String(s);
        return s.substr(6, 2) + '/' + s.substr(4, 2) + '/' + s.substr(0, 4) + ' ' + s.substr(8, 2) + ':' + s.substr(10, 2) + ':' + s.substr(12, 2);
    }

    function thamSo(d) {
        d.tu = $('#ssx-tu').val();
        d.den = $('#ssx-den').val();
        d.kieu_ngay = $('#ssx-kieu-ngay').val();
        d.trang_thai = $('#ssx-trang-thai').val();
        d.loai_ban = $('#ssx-loai-ban').val();
        d.ma = $.trim($('#ssx-ma').val());
    }

    function veTongHop(t) {
        var $o = $('#ssx-tong-hop').empty();
        function o(nhan, gt) {
            $o.append($('<span>').addClass('ssx-o').append($('<strong>').text(nhan + ': ')).append(document.createTextNode(gt)));
        }
        o('Hồ sơ trong khoảng lọc', t.tong);
        o('Đã ghi', t.da_ghi);
        o('Bị loại', t.bi_loai);
        o('Lỗi', t.loi);
        $.each({ chinh_thuc: 'Chính thức', thong_tuyen: 'Thông tuyến' }, function (k, nhan) {
            var m = t.moc[k];
            o('Mốc quét ' + nhan, (m && m.moc !== null) ? gio14(String(m.moc)) + (m.cap_nhat ? ' (cập nhật ' + m.cap_nhat + ')' : '') : 'chưa có');
        });
    }

    var bang = $('#ssx-list').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ordering: false,
        scrollX: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: URL_DATA,
            data: thamSo,
            dataSrc: function (json) {
                $('#ssx-thong-bao').hide();
                if (json.tong_hop) { veTongHop(json.tong_hop); }
                return json.data;
            },
            error: function (xhr) {
                var kq = xhr.responseJSON;
                $('#ssx-thong-bao').text(kq && kq.thong_diep ? kq.thong_diep : 'Máy chủ báo lỗi (HTTP ' + xhr.status + ').').show();
            }
        },
        columns: [
            {
                data: 'treatment_code',
                render: function (d, t, row) {
                    if (t !== 'display') { return d; }
                    return $('<a>').attr('href', row.url_lich_su).text(d)[0].outerHTML;
                }
            },
            { data: 'created_at', render: $.fn.dataTable.render.text() },
            { data: 'loai_ban', render: function (d) { return $('<div>').text(NHAN_LOAI_BAN[d] || d).html(); } },
            {
                data: 'trang_thai',
                render: function (d, t) {
                    if (t !== 'display') { return d; }
                    var $s = $('<span>').text(NHAN_TRANG_THAI[d] || d);
                    if (d === 'loi') { $s.addClass('nhan-loi'); }
                    return $s[0].outerHTML;
                }
            },
            { data: 'ly_do', render: $.fn.dataTable.render.text() },
            { data: 'cac_bang', render: $.fn.dataTable.render.text() },
            {
                data: 'thu_muc_loai',
                render: function (d, t, row) {
                    if (t !== 'display') { return d; }
                    return $('<span>').attr('title', row.thu_muc || '').text(NHAN_THU_MUC[d] || d)[0].outerHTML;
                }
            },
            { data: 'so_lan_sinh' },
            { data: 'out_time', render: function (d) { return $('<div>').text(gio14(d)).html(); } },
            { data: 'fee_lock_time', render: function (d) { return $('<div>').text(gio14(d)).html(); } },
            {
                data: null,
                render: function (d, t, row) {
                    if (t !== 'display') { return ''; }
                    return $('<a>').addClass('btn btn-xs btn-default').attr('href', row.url_lich_su)
                        .append($('<i>').addClass('fa fa-history')).append(' Lịch sử')[0].outerHTML;
                }
            }
        ],
        columnDefs: [{ targets: '_all', defaultContent: '' }]
    });

    $('#ssx-loc').on('click', function () { bang.ajax.reload(); });
    $('#ssx-ma').on('keypress', function (e) { if (e.which === 13) { bang.ajax.reload(); } });
})();
</script>
@endpush
