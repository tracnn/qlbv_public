@extends('adminlte::page')

@section('title', 'Vật phẩm kèm DVKT mẹ')

@section('content_header')
  <h1>
    Danh mục
    <small>Vật phẩm (VTYT/thuốc) thanh toán ngoài giá kèm DVKT mẹ — XML3176</small>
  </h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body">
        <button id="vpkd-hoc-lai" class="btn btn-primary" @if($lanHoc && $lanHoc['dang_chay']) disabled @endif>
            <i class="fa fa-refresh"></i> Học lại
        </button>
        <span id="vpkd-trang-thai" class="text-muted" style="margin-left:8px">{{ $lanHoc ? $lanHoc['nhan'] : 'Chưa có lượt học nào' }}</span>
        <div class="pull-right">
            <a href="{{ route('category-bhyt.vat-pham-kem-dvkt.xuat') }}" class="btn btn-default"><i class="fa fa-download"></i> Xuất Excel</a>
            <button class="btn btn-default" data-toggle="modal" data-target="#vpkd-modal-nhap"><i class="fa fa-upload"></i> Nhập Excel</button>
            <button class="btn btn-success" data-toggle="modal" data-target="#vpkd-modal-them"><i class="fa fa-plus"></i> Thêm dòng tay</button>
        </div>
    </div>
</div>

<ul class="nav nav-tabs">
    <li class="active"><a href="#vpkd-tab-bang-tra" data-toggle="tab">Bảng tra</a></li>
    <li><a href="#vpkd-tab-ung-vien" data-toggle="tab">Ứng viên chờ duyệt</a></li>
    <li><a href="#vpkd-tab-bat-buoc" data-toggle="tab">DVKT bắt buộc kèm vật phẩm</a></li>
</ul>
<div class="tab-content">
    <div class="tab-pane active" id="vpkd-tab-bang-tra">
        <div class="panel panel-default"><div class="panel-body">
            <div class="form-inline" style="margin-bottom:10px">
                <select id="loc-loai" class="form-control input-sm"><option value="">Loại: tất cả</option><option>VTYT</option><option>THUOC</option></select>
                <select id="loc-nguon" class="form-control input-sm"><option value="">Nguồn: tất cả</option><option>HOC</option><option>TAY</option></select>
                <select id="loc-hieu-luc" class="form-control input-sm"><option value="">Hiệu lực: tất cả</option><option value="1">Đang dùng</option><option value="0">Đã tắt</option></select>
            </div>
            <table id="vpkd-bang-tra" class="table display table-hover nowrap" width="100%">
                <thead><tr>
                    <th>Loại</th><th>Mã vật phẩm</th><th>Tên vật phẩm</th><th>Mã DVKT</th><th>Tên DVKT</th>
                    <th>Nguồn</th><th>Số lần gắn</th><th>Tỷ lệ gắn</th><th>Hiệu lực</th><th>Ghi chú</th><th>Người sửa</th><th></th>
                </tr></thead>
            </table>
        </div></div>
    </div>
    <div class="tab-pane" id="vpkd-tab-ung-vien">
        <div class="panel panel-default"><div class="panel-body">
            <div class="form-inline" style="margin-bottom:10px">
                <select id="loc-uv-loai" class="form-control input-sm"><option value="">Loại: tất cả</option><option>VTYT</option><option>THUOC</option></select>
            </div>
            <table id="vpkd-ung-vien" class="table display table-hover" width="100%">
                <thead><tr>
                    <th>Loại</th><th>Mã</th><th>Tên</th><th>Số dòng BHYT</th><th>Số liên kết</th><th>Tỷ lệ gắn</th><th>DVKT từng gắn</th><th></th>
                </tr></thead>
            </table>
        </div></div>
    </div>
    <div class="tab-pane" id="vpkd-tab-bat-buoc">
        <div class="panel panel-default"><div class="panel-body">
            <p class="text-muted">DVKT mà hầu hết hồ sơ BHYT đều kèm vật phẩm (học tự động). Hồ sơ có DVKT này được BHYT trả mà không có vật phẩm đi kèm sẽ bị cảnh báo <code>XMLComplete_DVKT_THIEU_VAT_PHAM_KEM</code>.</p>
            <div class="form-inline" style="margin-bottom:10px">
                <select id="loc-bb-nguon" class="form-control input-sm"><option value="">Nguồn: tất cả</option><option>HOC</option><option>TAY</option></select>
                <select id="loc-bb-hieu-luc" class="form-control input-sm"><option value="">Hiệu lực: tất cả</option><option value="1">Đang dùng</option><option value="0">Đã tắt</option></select>
                <select id="bb-them-ma" class="form-control input-sm" style="width:320px"><option></option></select>
                <input id="bb-them-ghi-chu" class="form-control input-sm" maxlength="1024" placeholder="Ghi chú (tuỳ chọn)">
                <button id="bb-them" class="btn btn-success btn-sm"><i class="fa fa-plus"></i> Thêm DVKT tay</button>
            </div>
            <table id="vpkd-bat-buoc" class="table display table-hover nowrap" width="100%">
                <thead><tr>
                    <th>Mã DVKT</th><th>Tên DVKT</th><th>Số HS</th><th>Số HS có kèm</th><th>Tỷ lệ</th>
                    <th>Nguồn</th><th>Hiệu lực</th><th>Ghi chú</th><th>Người sửa</th><th></th>
                </tr></thead>
            </table>
        </div></div>
    </div>
</div>

<div class="modal fade" id="vpkd-modal-them" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Thêm dòng tay</h4></div>
    <div class="modal-body">
        <div class="form-group"><label>Loại</label>
            <select id="them-loai" class="form-control"><option value="VTYT">VTYT</option><option value="THUOC">Thuốc</option></select></div>
        <div class="form-group"><label>Mã vật phẩm</label>
            <select id="them-ma" class="form-control" style="width:100%"><option></option></select></div>
        <div id="them-khop" class="form-group"></div>
        <div class="form-group"><label>Tên vật phẩm</label>
            <input id="them-ten" class="form-control" placeholder="Đúng như tên trên XML"></div>
        <div class="form-group"><label>DVKT mẹ</label>
            <select id="them-dvkt" class="form-control" multiple style="width:100%"></select></div>
        <div class="form-group"><label>Ghi chú</label><input id="them-ghi-chu" class="form-control" maxlength="1024"></div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-default" data-dismiss="modal">Đóng</button>
        <button id="them-luu" class="btn btn-success">Lưu</button>
    </div>
  </div></div>
</div>

<div class="modal fade" id="vpkd-modal-nhap" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Nhập Excel bảng tra</h4></div>
    <div class="modal-body">
        <p class="text-muted">Tệp .xlsx đúng định dạng của nút "Xuất Excel". Cột mã phải ở định dạng Text. Dòng mới thành nguồn TAY; cột hieu_luc bật/tắt dòng; không xoá dòng nào.</p>
        <form action="{{ route('category-bhyt.vat-pham-kem-dvkt.nhap') }}" class="dropzone" id="vpkd-dropzone">
            {{ csrf_field() }}
        </form>
        <div id="nhap-ket-qua" style="margin-top:10px"></div>
    </div>
  </div></div>
</div>
@stop

@push('after-scripts')
<script src="{{ asset('js/dropzone.min.js') }}"></script>
<script type="text/javascript">
    Dropzone.autoDiscover = false;
    toastr.options.escapeHtml = true;
    var TOKEN = "{{ csrf_token() }}";
    var URL_ID = {
        hieuLuc: "{{ route('category-bhyt.vat-pham-kem-dvkt.hieu-luc', ['id' => '__ID__']) }}",
        ghiChu: "{{ route('category-bhyt.vat-pham-kem-dvkt.ghi-chu', ['id' => '__ID__']) }}",
        xoa: "{{ route('category-bhyt.vat-pham-kem-dvkt.xoa', ['id' => '__ID__']) }}",
        bbHieuLuc: "{{ route('category-bhyt.vat-pham-kem-dvkt.bat-buoc-hieu-luc', ['id' => '__ID__']) }}",
        bbGhiChu: "{{ route('category-bhyt.vat-pham-kem-dvkt.bat-buoc-ghi-chu', ['id' => '__ID__']) }}",
        bbXoa: "{{ route('category-bhyt.vat-pham-kem-dvkt.bat-buoc-xoa', ['id' => '__ID__']) }}",
        uvThem: "{{ route('category-bhyt.vat-pham-kem-dvkt.ung-vien-them', ['id' => '__ID__']) }}"
    };
    function url(mau, id) { return mau.replace('__ID__', id); }
    function baoLoi(xhr) {
        toastr.error(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi xảy ra khi gửi yêu cầu.');
    }
    var bangTra, ungVien, batBuoc, hoiTrangThai = null, kiemKhopHienTai = null;

    function taiLai() { bangTra.ajax.reload(null, false); ungVien.ajax.reload(null, false); batBuoc.ajax.reload(null, false); }

    // ---- Bang tra ----
    bangTra = $('#vpkd-bang-tra').DataTable({
        processing: true, serverSide: true, scrollX: true, order: [[1, 'asc']],
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.datatable') }}",
                data: function (d) { d.loai = $('#loc-loai').val(); d.nguon = $('#loc-nguon').val(); d.hieu_luc = $('#loc-hieu-luc').val(); } },
        columns: [
            { data: 'loai', name: 'xml3176_vat_pham_kem_dvkt.loai' },
            { data: 'ma_vat_pham', name: 'xml3176_vat_pham_kem_dvkt.ma_vat_pham' },
            { data: 'ten_vat_pham', name: 'xml3176_vat_pham_kem_dvkt.ten_vat_pham' },
            { data: 'ma_dvkt', name: 'xml3176_vat_pham_kem_dvkt.ma_dvkt' },
            { data: 'ten_dvkt', name: 'xml3176_vat_pham_kem_dvkt.ten_dvkt' },
            { data: 'nguon', name: 'xml3176_vat_pham_kem_dvkt.nguon', searchable: false },
            { data: 'so_lan_gan', name: 'xml3176_vat_pham_kem_dvkt.so_lan_gan', searchable: false },
            { data: 'ty_le_gan', name: 'xml3176_vat_pham_kem_dvkt.ty_le_gan', searchable: false },
            { data: 'hieu_luc', name: 'xml3176_vat_pham_kem_dvkt.hieu_luc', searchable: false },
            { data: 'ghi_chu', name: 'xml3176_vat_pham_kem_dvkt.ghi_chu', searchable: false },
            { data: 'nguoi_sua_luc', name: 'xml3176_vat_pham_kem_dvkt.sua_luc', searchable: false },
            { data: 'thao_tac', orderable: false, searchable: false }
        ]
    });
    $('#loc-loai, #loc-nguon, #loc-hieu-luc').on('change', function () { bangTra.ajax.reload(); });

    $('#vpkd-bang-tra').on('change', '.vpkd-hieu-luc', function () {
        var o = $(this), bat = o.is(':checked');
        var chuyenTay = bat && o.attr('data-nguon') === 'HOC' && o.attr('data-ghi-chu') === 'Học lại: dưới ngưỡng';
        Swal.fire({
            title: bat ? 'Bật lại cặp này?' : 'Tắt cặp này?', icon: 'warning', showCancelButton: true,
            text: chuyenTay ? 'Dòng học tự động đang bị tắt do dưới ngưỡng sẽ chuyển thành dòng bổ sung tay (TAY) để lần học sau không tắt lại.' : undefined,
            input: 'text', inputValue: bat ? (o.attr('data-ghi-chu') || '') : '', inputPlaceholder: bat ? 'Ghi chú (tuỳ chọn)' : 'Lý do tắt (bắt buộc)',
            inputValidator: function (v) { if (!bat && !$.trim(v)) { return 'Cần ghi lý do khi tắt'; } }
        }).then(function (r) {
            if (!r.isConfirmed) { o.prop('checked', !bat); return; }
            $.post(url(URL_ID.hieuLuc, o.attr('data-id')), { _token: TOKEN, hieu_luc: bat ? 1 : 0, ghi_chu: r.value })
                .done(function () { toastr.success('Đã cập nhật.'); bangTra.ajax.reload(null, false); })
                .fail(function (x) { o.prop('checked', !bat); baoLoi(x); });
        });
    });

    $('#vpkd-bang-tra').on('click', '.vpkd-ghi-chu', function (e) {
        e.preventDefault();
        var o = $(this);
        Swal.fire({ title: 'Ghi chú', input: 'text', inputValue: o.attr('data-ghi-chu') || '', showCancelButton: true })
            .then(function (r) {
                if (!r.isConfirmed) { return; }
                $.post(url(URL_ID.ghiChu, o.attr('data-id')), { _token: TOKEN, ghi_chu: r.value })
                    .done(function () { toastr.success('Đã lưu ghi chú.'); bangTra.ajax.reload(null, false); })
                    .fail(baoLoi);
            });
    });

    $('#vpkd-bang-tra').on('click', '.vpkd-xoa', function () {
        var id = $(this).attr('data-id');
        Swal.fire({ title: 'Bạn có chắc chắn?', text: 'Xoá dòng bổ sung tay này.', icon: 'warning', showCancelButton: true })
            .then(function (r) {
                if (!r.isConfirmed) { return; }
                $.post(url(URL_ID.xoa, id), { _token: TOKEN })
                    .done(function () { toastr.success('Đã xoá.'); taiLai(); })
                    .fail(baoLoi);
            });
    });

    // ---- Ung vien ----
    ungVien = $('#vpkd-ung-vien').DataTable({
        processing: true, serverSide: true, order: [[3, 'desc']],
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.ung-vien-datatable') }}",
                data: function (d) { d.loai = $('#loc-uv-loai').val(); } },
        columns: [
            { data: 'loai', name: 'loai' },
            { data: 'ma_vat_pham', name: 'ma_vat_pham' },
            { data: 'ten_vat_pham', name: 'ten_vat_pham' },
            { data: 'so_dong', name: 'so_dong', searchable: false },
            { data: 'so_lien_ket', name: 'so_lien_ket', searchable: false },
            { data: 'ty_le_gan', name: 'ty_le_gan', searchable: false },
            { data: 'dvkt', orderable: false, searchable: false },
            { data: 'thao_tac', orderable: false, searchable: false }
        ]
    });
    $('#loc-uv-loai').on('change', function () { ungVien.ajax.reload(); });
    $('a[href="#vpkd-tab-ung-vien"]').on('shown.bs.tab', function () { ungVien.columns.adjust(); });

    $('#vpkd-ung-vien').on('click', '.vpkd-uv-them', function () {
        var nut = $(this), id = nut.attr('data-id');
        var ma = $('.vpkd-uv-dv[data-uv="' + id + '"]:checked:not(:disabled)').map(function () { return this.value; }).get();
        if (!ma.length) { toastr.warning('Hãy tick ít nhất một DVKT chưa có.'); return; }
        nut.prop('disabled', true);
        $.post(url(URL_ID.uvThem, id), { _token: TOKEN, ma_dvkt: ma })
            .done(function (r) { toastr.success(r.message); taiLai(); })
            .fail(baoLoi)
            .always(function () { nut.prop('disabled', false); });
    });

    // ---- DVKT bat buoc kem vat pham ----
    batBuoc = $('#vpkd-bat-buoc').DataTable({
        processing: true, serverSide: true, scrollX: true, order: [[2, 'desc']],
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.bat-buoc-datatable') }}",
                data: function (d) { d.nguon = $('#loc-bb-nguon').val(); d.hieu_luc = $('#loc-bb-hieu-luc').val(); } },
        columns: [
            { data: 'ma_dvkt', name: 'xml3176_vpkd_dvkt_bat_buoc.ma_dvkt' },
            { data: 'ten_dvkt', name: 'xml3176_vpkd_dvkt_bat_buoc.ten_dvkt' },
            { data: 'so_ho_so', name: 'xml3176_vpkd_dvkt_bat_buoc.so_ho_so', searchable: false },
            { data: 'so_ho_so_co_kem', name: 'xml3176_vpkd_dvkt_bat_buoc.so_ho_so_co_kem', searchable: false },
            { data: 'ty_le', name: 'xml3176_vpkd_dvkt_bat_buoc.ty_le', searchable: false },
            { data: 'nguon', name: 'xml3176_vpkd_dvkt_bat_buoc.nguon', searchable: false },
            { data: 'hieu_luc', name: 'xml3176_vpkd_dvkt_bat_buoc.hieu_luc', searchable: false },
            { data: 'ghi_chu', name: 'xml3176_vpkd_dvkt_bat_buoc.ghi_chu', searchable: false },
            { data: 'nguoi_sua_luc', name: 'xml3176_vpkd_dvkt_bat_buoc.sua_luc', searchable: false },
            { data: 'thao_tac', orderable: false, searchable: false }
        ]
    });
    $('#loc-bb-nguon, #loc-bb-hieu-luc').on('change', function () { batBuoc.ajax.reload(); });
    $('a[href="#vpkd-tab-bat-buoc"]').on('shown.bs.tab', function () { batBuoc.columns.adjust(); });

    $('#vpkd-bat-buoc').on('change', '.vpkd-bb-hieu-luc', function () {
        var o = $(this), bat = o.is(':checked');
        Swal.fire({
            title: bat ? 'Bật lại DVKT này?' : 'Tắt DVKT này?', icon: 'warning', showCancelButton: true,
            input: 'text', inputValue: bat ? (o.attr('data-ghi-chu') || '') : '',
            inputPlaceholder: bat ? 'Ghi chú (tuỳ chọn)' : 'Lý do tắt (bắt buộc)',
            inputValidator: function (v) { if (!bat && !$.trim(v)) { return 'Cần ghi lý do khi tắt'; } }
        }).then(function (r) {
            if (!r.isConfirmed) { o.prop('checked', !bat); return; }
            $.post(url(URL_ID.bbHieuLuc, o.attr('data-id')), { _token: TOKEN, hieu_luc: bat ? 1 : 0, ghi_chu: r.value })
                .done(function () { toastr.success('Đã cập nhật.'); batBuoc.ajax.reload(null, false); })
                .fail(function (x) { o.prop('checked', !bat); baoLoi(x); });
        });
    });

    $('#vpkd-bat-buoc').on('click', '.vpkd-bb-ghi-chu', function (e) {
        e.preventDefault();
        var o = $(this);
        Swal.fire({ title: 'Ghi chú', input: 'text', inputValue: o.attr('data-ghi-chu') || '', showCancelButton: true })
            .then(function (r) {
                if (!r.isConfirmed) { return; }
                $.post(url(URL_ID.bbGhiChu, o.attr('data-id')), { _token: TOKEN, ghi_chu: r.value })
                    .done(function () { toastr.success('Đã lưu ghi chú.'); batBuoc.ajax.reload(null, false); })
                    .fail(baoLoi);
            });
    });

    $('#vpkd-bat-buoc').on('click', '.vpkd-bb-xoa', function () {
        var id = $(this).attr('data-id');
        Swal.fire({ title: 'Bạn có chắc chắn?', text: 'Xoá DVKT bổ sung tay này.', icon: 'warning', showCancelButton: true })
            .then(function (r) {
                if (!r.isConfirmed) { return; }
                $.post(url(URL_ID.bbXoa, id), { _token: TOKEN })
                    .done(function () { toastr.success('Đã xoá.'); batBuoc.ajax.reload(null, false); })
                    .fail(baoLoi);
            });
    });

    $('#bb-them-ma').select2({
        tags: true, placeholder: 'Mã hoặc tên DVKT', width: 'resolve',
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.tim-dvkt') }}", delay: 300,
                data: function (p) { return { q: p.term }; } }
    });
    $('#bb-them').on('click', function () {
        var btn = $(this), ma = $('#bb-them-ma').val();
        if (!ma) { toastr.warning('Chọn mã DVKT.'); return; }
        btn.prop('disabled', true);
        $.post("{{ route('category-bhyt.vat-pham-kem-dvkt.bat-buoc-them') }}", { _token: TOKEN, ma_dvkt: ma, ghi_chu: $('#bb-them-ghi-chu').val() })
            .done(function (r) { toastr.success(r.message); $('#bb-them-ma').val(null).trigger('change'); $('#bb-them-ghi-chu').val(''); batBuoc.ajax.reload(null, false); })
            .fail(baoLoi)
            .always(function () { btn.prop('disabled', false); });
    });

    // ---- Them dong tay ----
    $('#them-ma').select2({
        dropdownParent: $('#vpkd-modal-them'), tags: true, placeholder: 'Gõ mã hoặc tên',
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.tim-vat-pham') }}", delay: 300,
                data: function (p) { return { q: p.term, loai: $('#them-loai').val() }; } }
    });
    $('#them-dvkt').select2({
        dropdownParent: $('#vpkd-modal-them'), tags: true, placeholder: 'Gõ mã hoặc tên DVKT',
        ajax: { url: "{{ route('category-bhyt.vat-pham-kem-dvkt.tim-dvkt') }}", delay: 300,
                data: function (p) { return { q: p.term }; } }
    });
    $('#them-loai').on('change', function () { $('#them-ten').val('').prop('readonly', false); $('#them-ma').val(null).trigger('change'); $('#them-khop').empty(); kiemKhopHienTai = null; });

    $('#them-ma').on('change', function () {
        var ma = $(this).val(), maGui = ma;
        $('#them-ten').val('').prop('readonly', false);
        $('#them-khop').empty(); kiemKhopHienTai = null;
        if (!ma) { return; }
        $.get("{{ route('category-bhyt.vat-pham-kem-dvkt.kiem-khop') }}", { loai: $('#them-loai').val(), ma: ma })
            .done(function (r) {
                if ($('#them-ma').val() !== maGui) { return; }
                kiemKhopHienTai = r;
                if (!r.ds.length) {
                    $('#them-khop').html(r.ma_nhom_chung
                        ? '<div class="alert alert-danger">Mã nhóm chung chưa xuất hiện trên XML 12 tháng — không thể khớp</div>'
                        : '<div class="alert alert-warning">Chưa thấy trên XML 12 tháng</div>');
                    $('#them-ten').prop('readonly', false);
                    return;
                }
                var h = '<label>Tên trên XML 12 tháng</label><table class="table table-condensed"><tr><th></th><th>Tên</th><th>Số dòng</th><th>BHYT trả</th><th>Hồ sơ</th></tr>';
                $.each(r.ds, function (i, d) {
                    h += '<tr><td><input type="radio" name="them-ten-xml" data-idx="' + i + '"' + (i === 0 ? ' checked' : '') + '></td><td>'
                        + $('<div>').text(d.ten).html() + '</td><td>' + d.so_dong + '</td><td>' + d.so_dong_bhyt + '</td><td>' + d.so_ho_so + '</td></tr>';
                });
                $('#them-khop').html(h + '</table>');
                $('#them-ten').val(r.ds[0].ten).prop('readonly', r.ma_nhom_chung);
            })
            .fail(baoLoi);
    });
    $('#them-khop').on('change', 'input[name="them-ten-xml"]', function () {
        if (kiemKhopHienTai && kiemKhopHienTai.ds[$(this).data('idx')]) { $('#them-ten').val(kiemKhopHienTai.ds[$(this).data('idx')].ten); }
    });

    $('#them-luu').on('click', function () {
        if (kiemKhopHienTai && kiemKhopHienTai.ma_nhom_chung && !kiemKhopHienTai.ds.length) {
            toastr.error('Mã nhóm chung chưa xuất hiện trên XML 12 tháng — không thể khớp'); return;
        }
        var nut = $(this).prop('disabled', true);
        $.post("{{ route('category-bhyt.vat-pham-kem-dvkt.them') }}", {
            _token: TOKEN, loai: $('#them-loai').val(), ma_vat_pham: $('#them-ma').val(),
            ten_vat_pham: $('#them-ten').val(), ma_dvkt: $('#them-dvkt').val() || [], ghi_chu: $('#them-ghi-chu').val()
        }).done(function (r) {
            toastr.success(r.message);
            $('#vpkd-modal-them').modal('hide');
            $('#them-ma, #them-dvkt').val(null).trigger('change'); $('#them-ten, #them-ghi-chu').val(''); $('#them-khop').empty();
            taiLai();
        }).fail(baoLoi).always(function () { nut.prop('disabled', false); });
    });

    // ---- Hoc lai ----
    function capNhatTrangThai(lan) {
        $('#vpkd-trang-thai').text(lan ? lan.nhan : 'Chưa có lượt học nào');
        var dang = !!(lan && lan.dang_chay);
        $('#vpkd-hoc-lai').prop('disabled', dang);
        if (dang && hoiTrangThai === null) {
            hoiTrangThai = setInterval(function () {
                $.get("{{ route('category-bhyt.vat-pham-kem-dvkt.trang-thai') }}").done(function (r) {
                    var xong = !(r.lan_hoc && r.lan_hoc.dang_chay);
                    capNhatTrangThai(r.lan_hoc);
                    if (xong) {
                        clearInterval(hoiTrangThai); hoiTrangThai = null;
                        if (r.lan_hoc && r.lan_hoc.trang_thai === 'XONG') { toastr.success('Học lại xong.'); taiLai(); }
                        else if (r.lan_hoc) { toastr.error(r.lan_hoc.nhan); }
                    }
                }).fail(function () { console.warn('Không lấy được trạng thái lượt học, sẽ thử lại.'); });
            }, 10000);
        }
    }
    capNhatTrangThai(@json($lanHoc));

    $('#vpkd-hoc-lai').on('click', function () {
        Swal.fire({ title: 'Học lại bảng tra?', text: 'Chạy nền 2–5 phút. Dòng TAY và dòng bạn đã tắt được giữ nguyên.', icon: 'question', showCancelButton: true })
            .then(function (r) {
                if (!r.isConfirmed) { return; }
                $.post("{{ route('category-bhyt.vat-pham-kem-dvkt.hoc-lai') }}", { _token: TOKEN })
                    .done(function (r) {
                        toastr.info(r.moi ? 'Đã xếp lượt học vào hàng đợi.' : 'Đang có lượt học chạy — theo dõi trạng thái.');
                        capNhatTrangThai(r.lan_hoc);
                    }).fail(baoLoi);
            });
    });

    // ---- Nhap Excel ----
    new Dropzone('#vpkd-dropzone', {
        paramName: 'file', uploadMultiple: false, maxFiles: 1, acceptedFiles: '.xlsx',
        dictDefaultMessage: 'Kéo thả tệp .xlsx vào đây hoặc bấm để chọn',
        success: function (file, r) {
            var $kq = $('#nhap-ket-qua').empty();
            $kq.append($('<div>').addClass('alert alert-' + (r.ket_qua.loi.length ? 'warning' : 'success')).text(r.message));
            if (r.ket_qua.loi.length) {
                var $f = $('<form method="POST">').attr('action', "{{ route('category-bhyt.vat-pham-kem-dvkt.nhap-loi') }}");
                $f.append($('<input type="hidden">').attr('name', '_token').val(TOKEN));
                $f.append($('<input type="hidden">').attr('name', 'ten_tep').val(file.name));
                $f.append($('<input type="hidden">').attr('name', 'dong_loi').val(JSON.stringify(r.ket_qua.loi)));
                $f.append($('<button class="btn btn-default btn-sm"></button>').html('<i class="fa fa-download"></i> ').append(document.createTextNode('Tải tệp lỗi (' + r.ket_qua.loi.length + ')')));
                $kq.append($f);
            }
            this.removeAllFiles();
            taiLai();
        },
        error: function (file, r) {
            $('#nhap-ket-qua').html('<div class="alert alert-danger">' + $('<div>').text(r && r.message ? r.message : r).html() + '</div>');
            this.removeAllFiles();
        }
    });
</script>
@endpush
