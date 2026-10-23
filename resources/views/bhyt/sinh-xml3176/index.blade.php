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

@if (!empty($duocXoa))
{{-- Chi superadministrator. Doc lap voi bo loc: chon ngay SINH TEP (thu muc ngay luu tru). --}}
<div class="panel panel-danger">
    <div class="panel-heading"><strong>Xoá XML lưu trữ của ngày</strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-3">
                <label for="ssx-xoa-ngay-gt">Ngày sinh tệp</label>
                <input type="date" id="ssx-xoa-ngay-gt" class="form-control" value="{{ $homNay }}">
            </div>
            <div class="col-sm-9">
                <label>&nbsp;</label><br>
                <button type="button" id="ssx-xoa-ngay" class="btn btn-danger"><i class="fa fa-trash"></i> Xoá XML lưu trữ của ngày</button>
                <span class="text-muted">Chỉ xoá tệp lưu trữ; dòng theo dõi vẫn giữ. Không phụ thuộc bộ lọc ở trên.</span>
            </div>
        </div>
        <div id="ssx-xoa-ngay-kq" class="alert" style="display:none; margin:10px 0 0;"></div>
    </div>
</div>
@endif

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
    var NHAN_TRANG_THAI = @json(\App\Services\SinhXml3176\NhanHienThi::TRANG_THAI);
    var NHAN_LOAI_BAN = @json(\App\Services\SinhXml3176\NhanHienThi::LOAI_BAN);
    var NHAN_THU_MUC = @json(\App\Services\SinhXml3176\NhanHienThi::THU_MUC);

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
    @if (!empty($duocXoa))
    $('#ssx-xoa-ngay').on('click', function () {
        var ngay = $('#ssx-xoa-ngay-gt').val();
        var $kq = $('#ssx-xoa-ngay-kq');
        function bao(loai, nd) { $kq.removeClass('alert-success alert-danger alert-warning').addClass('alert-' + loai).text(nd).show(); }
        if (!ngay) { bao('warning', 'Hãy chọn ngày sinh tệp.'); return; }
        $.get("{{ route('bhyt.sinh-xml3176.dem-theo-ngay') }}", { ngay: ngay }).done(function (r) {
            if (!r.so_tep) { bao('warning', 'Ngày ' + ngay + ' không có XML lưu trữ nào để xoá.'); return; }
            if (!window.confirm('Xoá ' + r.so_tep + ' tệp XML lưu trữ sinh ngày ' + ngay + '? Thao tác không hoàn tác được; dòng theo dõi vẫn giữ.')) { return; }
            var $nut = $('#ssx-xoa-ngay').prop('disabled', true);
            $.post("{{ route('bhyt.sinh-xml3176.xoa-theo-ngay') }}", { _token: "{{ csrf_token() }}", ngay: ngay }).done(function (k) {
                bao('success', 'Đã xoá ' + k.xoa + ' tệp' + (k.bo_qua ? ' (bỏ qua ' + k.bo_qua + ')' : '') + '.');
                bang.ajax.reload(null, false);
            }).fail(function (x) {
                bao('danger', (x.responseJSON && x.responseJSON.thong_diep) || 'Không xoá được (lỗi ' + x.status + ').');
            }).always(function () { $nut.prop('disabled', false); });
        }).fail(function (x) {
            bao('danger', (x.responseJSON && x.responseJSON.thong_diep) || 'Không đếm được số tệp (lỗi ' + x.status + ').');
        });
    });
    @endif
    $('#ssx-ma').on('keypress', function (e) { if (e.which === 13) { bang.ajax.reload(); } });
})();
</script>
@endpush
