@extends('adminlte::page')

@section('title', 'Theo dõi sinh XML3176')

@section('content_header')
  <h1>
    Theo dõi sinh
    <small>hồ sơ XML3176</small>
  </h1>
{{ Breadcrumbs::render('bhyt.sinh-xml3176.index') }}
@stop

@push('after-styles')
<style>
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    .nhan-loi { color: #b94a48; font-weight: bold; }
    #ssx-tong-hop .ssx-o { display: inline-block; margin-right: 18px; }
</style>
@endpush

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

{{-- Bien vao: $homNay (Y-m-d), $duocXoa. Bo cuc theo man Danh sach ho so XML3176 (bhyt/xml3176/index):
     panel loc dung partial dung chung, nut "Tai du lieu" goi fetchData(startDate, endDate). --}}
<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range', ['showDateType' => false, 'showExport' => false])
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="ssx-kieu-ngay">Lọc theo</label>
                        <select id="ssx-kieu-ngay" class="form-control select2">
                            <option value="sinh">Ngày sinh tệp</option>
                            <option value="ra_vien">Ngày ra viện</option>
                            <option value="khoa_vp">Ngày khoá viện phí</option>
                        </select>
                    </div>
                </div>
                @include('partials.treatment_code')
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="ssx-trang-thai" title="Trạng thái của lần sinh mới nhất">Trạng thái</label>
                        <select id="ssx-trang-thai" class="form-control select2">
                            <option value="">Tất cả</option>
                            <option value="da_ghi">Đã ghi</option>
                            <option value="bi_loai">Bị loại</option>
                            <option value="loi">Lỗi</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="form-group row">
                        <label for="ssx-loai-ban">Loại bản</label>
                        <select id="ssx-loai-ban" class="form-control select2">
                            <option value="">Tất cả</option>
                            <option value="chinh_thuc">Chính thức</option>
                            <option value="thong_tuyen">Thông tuyến</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @include('partials.load_data_button')
        <div class="col-sm-12">
            <div id="ssx-thong-bao" class="alert alert-warning" style="display:none; margin:0;"></div>
        </div>
    </div>
</div>

@if (!empty($duocXoa))
{{-- Chi superadministrator. Doc lap voi bo loc: chon ngay SINH TEP (thu muc ngay luu tru). --}}
<button id="ssx-xoa-ngay-mo" type="button" class="btn btn-danger">
    <i class="fa fa-trash" aria-hidden="true"></i> Xoá XML lưu trữ của ngày
</button>

<div class="modal fade" id="ssx-xoa-ngay-modal" tabindex="-1" role="dialog" aria-labelledby="ssx-xoa-ngay-tieu-de" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ssx-xoa-ngay-tieu-de">Xoá XML lưu trữ của ngày</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="ssx-xoa-ngay-gt">Ngày sinh tệp</label>
                    <input type="date" id="ssx-xoa-ngay-gt" class="form-control" value="{{ $homNay }}">
                </div>
                <p class="text-muted">Chỉ xoá tệp lưu trữ; dòng theo dõi vẫn giữ. Không phụ thuộc bộ lọc ở trên.</p>
                <div id="ssx-xoa-ngay-kq" class="alert" style="display:none; margin:0;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                <button type="button" id="ssx-xoa-ngay" class="btn btn-danger"><i class="fa fa-trash"></i> Xoá</button>
            </div>
        </div>
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
        <table id="ssx-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
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
    @stack('after-scripts-date-range')
    @stack('after-scripts-treatment-code')
    @stack('after-scripts-load-data-button')
<script>
    var URL_DATA = "{{ route('bhyt.sinh-xml3176.fetch-data') }}";
    var NHAN_TRANG_THAI = @json(\App\Services\SinhXml3176\NhanHienThi::TRANG_THAI);
    var NHAN_LOAI_BAN = @json(\App\Services\SinhXml3176\NhanHienThi::LOAI_BAN);
    var NHAN_THU_MUC = @json(\App\Services\SinhXml3176\NhanHienThi::THU_MUC);
    var ssxBang = null;
    // Khoang ngay do partials.date_range dat (YYYY-MM-DD HH:mm:ss); may chu chi nhan Y-m-d.
    var ssxTu = '{{ $homNay }}';
    var ssxDen = '{{ $homNay }}';

    // yyyyMMddHHmmss -> dd/MM/yyyy HH:mm:ss; khong du 14 so thi giu nguyen.
    function gio14(s) {
        if (!s || String(s).length !== 14) { return s || ''; }
        s = String(s);
        return s.substr(6, 2) + '/' + s.substr(4, 2) + '/' + s.substr(0, 4) + ' ' + s.substr(8, 2) + ':' + s.substr(10, 2) + ':' + s.substr(12, 2);
    }

    function thamSo(d) {
        d.tu = ssxTu;
        d.den = ssxDen;
        d.kieu_ngay = $('#ssx-kieu-ngay').val();
        d.trang_thai = $('#ssx-trang-thai').val();
        d.loai_ban = $('#ssx-loai-ban').val();
        d.ma = $.trim($('#treatment_code').val());
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

    function taoBang() {
        return $('#ssx-list').DataTable({
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
    }

    // partials.load_data_button goi fetchData(startDate, endDate) khi nap trang va khi bam "Tai du lieu".
    function fetchData(startDate, endDate) {
        ssxTu = String(startDate).substr(0, 10);
        ssxDen = String(endDate).substr(0, 10);
        if (ssxBang === null) {
            ssxBang = taoBang();
        } else {
            ssxBang.ajax.reload();
        }
    }

    $(document).ready(function () {
        $('.select2').select2({
            width: '100%'
        });

        @if (!empty($duocXoa))
        $('#ssx-xoa-ngay-mo').on('click', function () {
            $('#ssx-xoa-ngay-kq').hide();
            $('#ssx-xoa-ngay-modal').modal('show');
        });
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
                    if (ssxBang !== null) { ssxBang.ajax.reload(null, false); }
                }).fail(function (x) {
                    bao('danger', (x.responseJSON && x.responseJSON.thong_diep) || 'Không xoá được (lỗi ' + x.status + ').');
                }).always(function () { $nut.prop('disabled', false); });
            }).fail(function (x) {
                bao('danger', (x.responseJSON && x.responseJSON.thong_diep) || 'Không đếm được số tệp (lỗi ' + x.status + ').');
            });
        });
        @endif
    });
</script>
@endpush
