@extends('adminlte::page')

@section('title', 'Hồ sơ tổng hợp 01/BH')

@section('content_header')
<h1>Hồ sơ tổng hợp <small>Mẫu 01/BH (TT12/2026/TT-BTC)</small></h1>
@stop

@push('after-styles')
<style>
    .table-responsive { display: block; width: 100%; overflow-x: auto; }
    .nhan-canh-bao { color: #b94a48; font-weight: bold; }
    #bh01-xem-truoc dl { margin-bottom: 0; }
    /* Loi nhap Excel co nhieu dong (moi dong mot loi): giu xuong dong khi hien bang .text(). */
    #bh01-thong-bao .alert { white-space: pre-line; }
</style>
@endpush

@section('content')
@include('includes.message')

{{-- Bien vao: $danhSachCoSo (ma => nhan), $guiBat (bool, organization.tt12.submit_enabled).
     O co so KHONG co gia tri mac dinh (xem BHYTBh01Controller::index). --}}
<div class="panel panel-default">
    <div class="panel-heading"><strong>Lập bản mới</strong></div>
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-4">
                <label for="bh01-co-so">Cơ sở KCB</label>
                <select id="bh01-co-so" class="form-control">
                    <option value="">— Chọn cơ sở —</option>
                    @foreach ($danhSachCoSo as $ma => $nhan)
                        <option value="{{ $ma }}">{{ $nhan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-2">
                <label for="bh01-thang">Tháng</label>
                <select id="bh01-thang" class="form-control">
                    @for ($t = 1; $t <= 12; $t++)
                        <option value="{{ $t }}" {{ (int) date('n') === $t ? 'selected' : '' }}>{{ sprintf('%02d', $t) }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-sm-2">
                <label for="bh01-nam">Năm</label>
                <input type="number" id="bh01-nam" class="form-control" min="2026" max="2100" value="{{ date('Y') }}">
            </div>
            <div class="col-sm-4">
                <label>&nbsp;</label><br>
                <button type="button" id="bh01-btn-xem-truoc" class="btn btn-info">
                    <i class="fa fa-search"></i> Xem trước
                </button>
                <button type="button" id="bh01-btn-tao" class="btn btn-primary" disabled>
                    <i class="fa fa-plus"></i> Tạo bản
                </button>
            </div>
        </div>

        <div class="row" style="margin-top:15px;">
            <div class="col-sm-8">
                <label for="bh01-tep-excel">Hoặc nhập từ tệp Excel (theo cơ sở và kỳ đã chọn)</label>
                <input type="file" id="bh01-tep-excel" class="form-control" accept=".xlsx">
            </div>
            <div class="col-sm-4">
                <label>&nbsp;</label><br>
                <button type="button" id="bh01-btn-nhap-excel" class="btn btn-success">
                    <i class="fa fa-upload"></i> Nhập từ Excel
                </button>
                <a href="{{ route('bhyt.tt12.bh01.tep-mau') }}" class="btn btn-link">
                    <i class="fa fa-file-excel-o"></i> Tải tệp mẫu
                </a>
            </div>
        </div>

        <div id="bh01-xem-truoc" style="display:none; margin-top:15px;"></div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading clearfix"><strong>Các bản đã lập</strong>
        <small class="text-muted">(lọc theo cơ sở/kỳ ở khối trên; bỏ trống cơ sở để xem tất cả)</small>
        <button type="button" id="bh01-btn-lam-moi" class="btn btn-default btn-xs pull-right"
                title="Tải lại danh sách để xem trạng thái ký / gửi mới nhất">
            <i class="fa fa-refresh"></i> Làm mới
        </button>
    </div>
    <div class="panel-body">
        <div id="bh01-thong-bao" style="display:none; margin-bottom:10px;"></div>
        <div class="table-responsive">
            <table class="table display table-hover wrap" width="100%" id="bh01-list" style="width:100%">
                <thead>
                    <tr>
                        <th>Mã bản</th>
                        <th>Cơ sở</th>
                        <th>Kỳ</th>
                        <th>Bản</th>
                        <th>Nguồn</th>
                        <th>Số hồ sơ</th>
                        <th>Tổng BHTT</th>
                        <th>Cảnh báo</th>
                        <th>Ký</th>
                        <th>Kết quả gửi</th>
                        <th>Mã GD</th>
                        <th>Tiếp nhận lúc</th>
                        <th>Dữ liệu</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="bh01-modal-lich-su" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Nhật ký gửi <small id="bh01-lich-su-ma"></small></h4>
            </div>
            <div class="modal-body" id="bh01-lich-su-than"></div>
        </div>
    </div>
</div>
@stop

@push('after-scripts')
<script>
(function () {
    var GUI_BAT = {{ $guiBat ? 'true' : 'false' }};
    var CSRF = "{{ csrf_token() }}";
    var URL_XEM_TRUOC = "{{ route('bhyt.tt12.bh01.xem-truoc') }}";
    var URL_TAO = "{{ route('bhyt.tt12.bh01.tao') }}";
    var URL_NHAP_EXCEL = "{{ route('bhyt.tt12.bh01.nhap-excel') }}";
    var URL_DATA = "{{ route('bhyt.tt12.bh01.fetch-data') }}";
    // Mau URL co tham so: thay __MA__ bang ma ban da encodeURIComponent.
    var MAU_KY_LAI = "{{ route('bhyt.tt12.bh01.ky-lai', ['ma_ban' => '__MA__']) }}";
    var MAU_GUI = "{{ route('bhyt.tt12.bh01.gui', ['ma_ban' => '__MA__']) }}";
    var MAU_TAI = "{{ route('bhyt.tt12.bh01.tai', ['ma_ban' => '__MA__']) }}";
    var MAU_XUAT_EXCEL = "{{ route('bhyt.tt12.bh01.xuat-excel', ['ma_ban' => '__MA__']) }}";
    var MAU_DONG = "{{ route('bhyt.tt12.bh01.dong', ['ma_ban' => '__MA__']) }}";
    var MAU_LICH_SU = "{{ route('bhyt.tt12.bh01.lich-su', ['ma_ban' => '__MA__']) }}";

    function url(mau, ma) { return mau.replace('__MA__', encodeURIComponent(ma)); }

    function thongBao(thanhCong, noiDung) {
        $('#bh01-thong-bao').empty().show().append(
            $('<div>').addClass('alert ' + (thanhCong ? 'alert-success' : 'alert-warning'))
                .css('margin-bottom', '0').text(noiDung)
        );
    }

    function loiTuXhr(xhr) {
        var kq = xhr.responseJSON;
        if (kq && kq.thong_diep) {
            return kq.thong_diep;
        }
        // Tach "khong toi duoc may chu" (status 0) khoi "may chu bao loi": gop chung thi mot lan
        // tao ban qua thoi gian xu ly (HTTP 500) hien thanh "kiem tra ket noi" va dan sai huong.
        if (xhr.status === 0) {
            return 'Không gửi được yêu cầu. Kiểm tra kết nối rồi thử lại.';
        }
        // Tep vuot post_max_size: middleware ValidatePostSize nem PostTooLargeException (HTTP 413)
        // TRUOC controller, nen khong co thong_diep JSON - phai tu dich o day.
        if (xhr.status === 413) {
            return 'Tệp vượt cỡ cho phép của máy chủ. Hãy chia nhỏ tệp hoặc liên hệ quản trị.';
        }
        return 'Máy chủ báo lỗi (HTTP ' + xhr.status + '). Xem log hệ thống để biết chi tiết.';
    }

    function thamSoKy() {
        return { ma_cskcb: $('#bh01-co-so').val(), nam: $('#bh01-nam').val(), thang: $('#bh01-thang').val() };
    }

    // Bang KHONG tu lam moi theo hen gio (tung lam moi 5 giay/lan khi co ban cho ky - bang
    // nhay, mat vi tri dang xem). Ky chay o hang doi: nguoi dung bam "Lam moi" de xem trang thai.
    var bang = null;

    function so(n) {
        return (n === null || n === undefined || n === '') ? '' : Number(n).toLocaleString('vi-VN');
    }

    function nhanKy(row) {
        if (row.trang_thai_ky === 'da_ky') { return 'Đã ký'; }
        if (row.trang_thai_ky === 'ky_loi') {
            return $('<span>').addClass('nhan-canh-bao').attr('title', row.signed_error || '').text('Ký lỗi')[0].outerHTML;
        }
        return 'Chờ ký…';
    }

    function nutHanhDong(row) {
        var $o = $('<div>');
        var ma = row.ma_ban;

        function nut(lop, bieuTuong, chu) {
            return $('<button type="button">').addClass('btn btn-xs ' + lop).attr('data-ma-ban', ma)
                .append($('<i>').addClass('fa ' + bieuTuong)).append(' ' + chu);
        }

        if (row.trang_thai_ky === 'da_ky') {
            $o.append($('<a>').addClass('btn btn-xs btn-default').attr('href', url(MAU_TAI, ma))
                .append($('<i>').addClass('fa fa-download')).append(' Tải tệp')).append(' ');

            var $gui = nut('btn-primary bh01-gui', 'fa-paper-plane', 'Gửi cổng');
            if (!GUI_BAT) {
                $gui.prop('disabled', true).attr('title', 'Chức năng gửi cổng đang tắt (organization.tt12.submit_enabled).');
            } else if (row.dang_gui) {
                $gui.prop('disabled', true).attr('title', 'Bản đang được gửi.');
            }
            $o.append($gui).append(' ');
        }

        // Co tinh o may chu (Bh01XepHang::coTheKyLai) - mot luat duy nhat cho nut va cho xepKy.
        if (row.co_the_ky_lai) {
            $o.append(nut('btn-warning bh01-ky-lai', 'fa-refresh', 'Ký lại')).append(' ');
        }

        $o.append($('<a>').addClass('btn btn-xs btn-default').attr('href', url(MAU_DONG, ma)).attr('target', '_blank')
            .append($('<i>').addClass('fa fa-list')).append(' Xem dòng')).append(' ');
        $o.append(nut('btn-default bh01-xuat-excel', 'fa-file-excel-o', 'Xuất Excel')).append(' ');
        $o.append(nut('btn-default bh01-lich-su', 'fa-history', 'Nhật ký'));

        return $o.html();
    }

    function khoiTaoBang() {
        bang = $('#bh01-list').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            ordering: false,
            scrollX: true,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            ajax: {
                url: URL_DATA,
                data: function (d) {
                    // Chi loc theo co so/ky khi nguoi dung da chon co so; khong thi xem het.
                    var t = thamSoKy();
                    if (t.ma_cskcb) { $.extend(d, t); }
                }
            },
            columns: [
                { data: 'ma_ban', render: $.fn.dataTable.render.text() },
                { data: 'ma_cskcb', render: $.fn.dataTable.render.text() },
                { data: 'ky', render: $.fn.dataTable.render.text() },
                { data: 'so_ban', render: $.fn.dataTable.render.text() },
                {
                    // Ten tep nguon gan bang .attr(): ten tep do nguoi dung dat, khong noi vao chuoi HTML.
                    data: 'nguon',
                    render: function (d, t, row) {
                        var chu = d === 'excel' ? 'Excel' : 'XML3176';
                        if (t !== 'display') { return chu; }
                        var $s = $('<span>').text(chu);
                        if (d === 'excel' && row.ten_tep_nguon) { $s.attr('title', row.ten_tep_nguon); }
                        return $s[0].outerHTML;
                    }
                },
                { data: 'so_dong', render: function (d, t) { return t === 'display' ? so(d) : d; } },
                { data: 't_bhtt', render: function (d, t) { return t === 'display' ? so(d) : d; } },
                {
                    data: 'so_canh_bao',
                    render: function (d, t) {
                        if (t !== 'display') { return d; }
                        return Number(d) > 0 ? $('<span>').addClass('nhan-canh-bao').text(d)[0].outerHTML : '0';
                    }
                },
                { data: null, render: function (d, t, row) { return t === 'display' ? nhanKy(row) : row.trang_thai_ky; } },
                {
                    data: null,
                    render: function (d, t, row) {
                        var chu = row.ma_ket_qua ? (row.ma_ket_qua + (row.thong_diep ? ' — ' + row.thong_diep : '')) : (row.dang_gui ? 'Đang gửi…' : '');
                        return $('<div>').text(chu).html();
                    }
                },
                { data: 'ma_gd', render: $.fn.dataTable.render.text() },
                { data: 'thoi_gian_tiep_nhan', render: $.fn.dataTable.render.text() },
                {
                    data: 'du_lieu_da_doi',
                    render: function (d, t, row) {
                        if (t !== 'display') { return d ? 1 : 0; }
                        return d ? $('<span>').addClass('nhan-canh-bao')
                            .text('Dữ liệu XML3176 của kỳ đã thay đổi so với bản ' + row.so_ban)[0].outerHTML : '';
                    }
                },
                { data: null, orderable: false, render: function (d, t, row) { return t === 'display' ? nutHanhDong(row) : ''; } }
            ],
            columnDefs: [{ targets: '_all', defaultContent: '' }]
        });
    }

    /**
     * Bao "dang xu ly" cho mot thao tac dai (xem truoc/tao ban quet ca chuc nghin ho so).
     *
     * Khoa CA khoi chon co so/ky lan hai nut, khong chi nut vua bam: doi ky giua chung thi
     * ket qua tra ve se gan nham ky moi, con bam Tao trong luc dang Xem truoc thi tao ban tu
     * mot ket qua chua kiem xong. Dem giay de nguoi dung biet he thong van dang chay chu
     * khong treo.
     *
     * $noiHien: khoi hien thong bao dang chay. Tao ban dung khoi thong bao rieng de KHONG
     * xoa ket qua xem truoc - tao hong thi nguoi dung van can doc lai ket qua do.
     *
     * @return function goi khi xong de tra lai trang thai cu
     */
    function batDauChay($nut, $noiHien, chu) {
        var nhanCu = $nut.html();
        // Khoa ca nut/o chon tep nhap Excel: bam dup trong luc dang nhap se tao ban trung.
        var $khoa = $('#bh01-co-so, #bh01-thang, #bh01-nam, #bh01-btn-xem-truoc, #bh01-btn-tao, '
            + '#bh01-btn-nhap-excel, #bh01-tep-excel');
        var tatTruoc = $khoa.map(function () { return $(this).prop('disabled'); }).get();
        var batDau = Date.now();
        var $dem = $('<span>').text('0');

        $khoa.prop('disabled', true);
        $nut.html('<i class="fa fa-spinner fa-spin"></i> ').append(document.createTextNode(chu));
        $noiHien.show().empty().append(
            $('<div>').addClass('alert alert-info').css('margin-bottom', 0)
                .append($('<i>').addClass('fa fa-spinner fa-spin'))
                .append(document.createTextNode(' ' + chu + ' Kỳ có nhiều hồ sơ có thể mất vài phút — đã chạy '))
                .append($dem).append(document.createTextNode(' giây.'))
        );
        var dongHo = setInterval(function () { $dem.text(Math.round((Date.now() - batDau) / 1000)); }, 1000);

        return function () {
            clearInterval(dongHo);
            $noiHien.empty().hide();
            $nut.html(nhanCu);
            $khoa.each(function (i) { $(this).prop('disabled', tatTruoc[i]); });
        };
    }

    function xemTruoc() {
        var t = thamSoKy();
        $('#bh01-btn-tao').prop('disabled', true);

        if (!t.ma_cskcb) {
            $('#bh01-xem-truoc').show().empty().append($('<div>').addClass('alert alert-warning').text('Chưa chọn cơ sở khám chữa bệnh.'));
            return;
        }

        var xong = batDauChay($('#bh01-btn-xem-truoc'), $('#bh01-xem-truoc'), 'Đang kiểm tra hồ sơ của kỳ…');

        $.getJSON(URL_XEM_TRUOC, t).always(xong).done(function (kq) {
            var $k = $('#bh01-xem-truoc').empty().show();
            var $dl = $('<dl>').addClass('dl-horizontal');
            function dong(nhan, gt) { $dl.append($('<dt>').text(nhan)).append($('<dd>').text(gt)); }

            dong('Số hồ sơ', so(kq.so_ho_so));
            dong('Theo mục I / II / III / IV / khác', [kq.theo_muc[1], kq.theo_muc[2], kq.theo_muc[3], kq.theo_muc[4], kq.theo_muc.khac].join(' / '));
            dong('Tổng chi BV', so(kq.t_tongchi_bv));
            dong('Tổng chi BH', so(kq.t_tongchi_bh));
            dong('Tổng BHTT', so(kq.t_bhtt));
            dong('Tổng BN CCT', so(kq.t_bncct));
            dong('Tổng BN tự trả', so(kq.t_bntt));
            dong('Tổng nguồn khác', so(kq.t_nguonkhac));
            dong('Hồ sơ cảnh báo', so(kq.so_dong_canh_bao));
            dong('Hồ sơ bị chặn', so(kq.so_dong_chan));
            $k.append($dl);

            if (kq.so_dong_chan > 0) {
                var $ul = $('<ul>');
                $.each(kq.loi_chan_mau || [], function (i, m) { $ul.append($('<li>').text(m)); });
                $k.append($('<div>').addClass('alert alert-danger').css('margin-top', '10px')
                    .append($('<strong>').text('Có ' + kq.so_dong_chan + ' hồ sơ bị chặn, chưa tạo được bản. Các lỗi đầu tiên:'))
                    .append($ul));
            } else if (kq.so_ho_so === 0) {
                $k.append($('<div>').addClass('alert alert-warning').text('Kỳ này không có hồ sơ BHYT nào.'));
            }

            $('#bh01-btn-tao').prop('disabled', kq.so_dong_chan > 0 || kq.so_ho_so === 0);
            if (bang) { bang.ajax.reload(); }
        }).fail(function (xhr) {
            $('#bh01-xem-truoc').show().empty().append($('<div>').addClass('alert alert-danger').text(loiTuXhr(xhr)));
        });
    }

    $(function () {
        khoiTaoBang();

        $('#bh01-btn-xem-truoc').on('click', xemTruoc);
        // Doi co so/ky thi ket qua xem truoc cu khong con dung: tat nut Tao cho toi khi xem truoc lai.
        $('#bh01-co-so, #bh01-thang, #bh01-nam').on('change', function () {
            $('#bh01-btn-tao').prop('disabled', true);
            $('#bh01-xem-truoc').hide().empty();
        });

        // Giu trang dang xem (false): chi lay lai trang thai ky / gui moi nhat.
        $('#bh01-btn-lam-moi').on('click', function () { bang.ajax.reload(null, false); });

        $('#bh01-btn-nhap-excel').on('click', function () {
            var $nut = $(this);
            var t = thamSoKy();
            var tep = $('#bh01-tep-excel')[0].files[0];
            if (!t.ma_cskcb) { thongBao(false, 'Chưa chọn cơ sở khám chữa bệnh.'); return; }
            if (!tep) { thongBao(false, 'Chưa chọn tệp Excel.'); return; }

            var du = new FormData();
            du.append('_token', CSRF);
            du.append('ma_cskcb', t.ma_cskcb);
            du.append('nam', t.nam);
            du.append('thang', t.thang);
            du.append('tep', tep);

            var xong = batDauChay($nut, $('#bh01-thong-bao'), 'Đang nhập tệp Excel…');
            $.ajax({ url: URL_NHAP_EXCEL, method: 'POST', dataType: 'json', data: du, processData: false, contentType: false })
                .always(xong)
                .done(function (kq) {
                    thongBao(true, kq.thong_diep);
                    $('#bh01-tep-excel').val('');
                    bang.ajax.reload();
                })
                .fail(function (xhr) { thongBao(false, loiTuXhr(xhr)); });
        });

        $('#bh01-btn-tao').on('click', function () {
            var $nut = $(this);
            var xong = batDauChay($nut, $('#bh01-thong-bao'), 'Đang tạo bản…');
            $.ajax({ url: URL_TAO, method: 'POST', dataType: 'json', data: $.extend({ _token: CSRF }, thamSoKy()) })
                .always(xong)
                .done(function (kq) {
                    thongBao(true, kq.thong_diep);
                    // Giu nut Tao TAT sau khi tao xong: bam lan nua la tao them mot ban moi cho
                    // cung ky. Muon tao lai thi phai Xem truoc lai.
                    $nut.prop('disabled', true);
                    $('#bh01-xem-truoc').hide().empty();
                    bang.ajax.reload();
                })
                .fail(function (xhr) { thongBao(false, loiTuXhr(xhr)); });
        });
    });

    function guiBan(ma, xacNhan) {
        var du = { _token: CSRF };
        if (xacNhan) { du.xac_nhan = 1; }
        $.ajax({ url: url(MAU_GUI, ma), method: 'POST', dataType: 'json', data: du })
            .done(function (kq) { thongBao(true, kq.thong_diep); bang.ajax.reload(null, false); })
            .fail(function (xhr) {
                var kq = xhr.responseJSON;
                if (xhr.status === 409 && kq && kq.can_xac_nhan) {
                    // Ky da duoc cong tiep nhan: hoi lai truoc khi gui them (spec 5.4), dong y moi gui lai voi xac_nhan=1.
                    if (confirm(kq.thong_diep)) { guiBan(ma, true); }
                    return;
                }
                thongBao(false, loiTuXhr(xhr));
            });
    }

    $(document).on('click', '.bh01-gui', function () {
        var ma = $(this).data('ma-ban');
        if (confirm('Gửi bản ' + ma + ' lên cổng BHXH? Cổng đã nhận thì không rút lại được.')) {
            guiBan(ma, false);
        }
    });

    $(document).on('click', '.bh01-ky-lai', function () {
        $.ajax({ url: url(MAU_KY_LAI, $(this).data('ma-ban')), method: 'POST', dataType: 'json', data: { _token: CSRF } })
            .done(function (kq) { thongBao(true, kq.thong_diep); bang.ajax.reload(null, false); })
            .fail(function (xhr) { thongBao(false, loiTuXhr(xhr)); });
    });

    /**
     * Xuat Excel mot ban bang XHR chu khong bang lien ket thuong.
     *
     * VI SAO: ban 21 nghin dong mat ~8 giay de dung tep; lien ket thuong khong cho trang biet
     * khi nao xong, nguoi dung nhin man hinh khong thay gi va bam lai nhieu lan (moi lan dung
     * lai ca tep). XHR cho phep quay nut + dem giay, xong moi luu tep; loi may chu (JSON) hien
     * ra thong bao thay vi mo mot trang JSON tran.
     */
    function xuatExcel($nut) {
        var ma = $nut.data('ma-ban');
        var nhanCu = $nut.html();
        var batDau = Date.now();

        $nut.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Đang xuất…');
        thongBaoDangChay('Đang xuất Excel bản ' + ma + '…', batDau);

        var xhr = new XMLHttpRequest();
        xhr.open('GET', url(MAU_XUAT_EXCEL, ma));
        xhr.responseType = 'blob';
        xhr.onload = function () {
            dungDongHo();
            $nut.prop('disabled', false).html(nhanCu);

            if (xhr.status === 200) {
                $('#bh01-thong-bao').empty().hide();
                luuBlob(xhr.response, tenTepTuHeader(xhr.getResponseHeader('Content-Disposition')) || (ma + '.xlsx'));
                return;
            }

            // Than loi la JSON {thong_diep} nhung da nhan duoi dang blob: doc lai thanh chu.
            var doc = new FileReader();
            doc.onload = function () {
                var kq = null;
                try { kq = JSON.parse(doc.result); } catch (e) { kq = null; }
                thongBao(false, loiTuXhr({ status: xhr.status, responseJSON: kq }));
            };
            doc.readAsText(xhr.response);
        };
        xhr.onerror = function () {
            dungDongHo();
            $nut.prop('disabled', false).html(nhanCu);
            thongBao(false, loiTuXhr({ status: 0 }));
        };
        xhr.send();
    }

    var dongHoXuat = null;

    function thongBaoDangChay(chu, batDau) {
        var $dem = $('<span>').text('0');
        $('#bh01-thong-bao').empty().show().append(
            $('<div>').addClass('alert alert-info').css('margin-bottom', 0)
                .append($('<i>').addClass('fa fa-spinner fa-spin'))
                .append(document.createTextNode(' ' + chu + ' Đã chạy '))
                .append($dem).append(document.createTextNode(' giây.'))
        );
        dungDongHo();
        dongHoXuat = setInterval(function () { $dem.text(Math.round((Date.now() - batDau) / 1000)); }, 1000);
    }

    function dungDongHo() {
        if (dongHoXuat !== null) {
            clearInterval(dongHoXuat);
            dongHoXuat = null;
        }
    }

    function tenTepTuHeader(h) {
        if (!h) { return null; }
        var m = /filename\*=UTF-8''([^;]+)/i.exec(h) || /filename="?([^";]+)"?/i.exec(h);
        return m ? decodeURIComponent(m[1]) : null;
    }

    function luuBlob(blob, ten) {
        if (window.navigator && window.navigator.msSaveOrOpenBlob) {
            window.navigator.msSaveOrOpenBlob(blob, ten);
            return;
        }
        var duongDan = URL.createObjectURL(blob);
        var $a = $('<a>').attr({ href: duongDan, download: ten }).css('display', 'none').appendTo('body');
        $a[0].click();
        setTimeout(function () { URL.revokeObjectURL(duongDan); $a.remove(); }, 1000);
    }

    $(document).on('click', '.bh01-xuat-excel', function () {
        xuatExcel($(this));
    });

    $(document).on('click', '.bh01-lich-su', function () {
        var ma = $(this).data('ma-ban');
        $('#bh01-lich-su-ma').text(ma);
        $('#bh01-lich-su-than').empty().text('Đang tải…');
        $('#bh01-modal-lich-su').modal('show');
        $.getJSON(url(MAU_LICH_SU, ma)).done(function (kq) {
            var $t = $('<table>').addClass('table table-bordered table-striped table-condensed');
            $t.append('<thead><tr><th>Lúc</th><th>Người gửi</th><th>Mã KQ</th><th>Mã GD</th><th>Tiếp nhận</th><th>Thông điệp</th><th>Lỗi</th></tr></thead>');
            var $tb = $('<tbody>');
            $.each(kq.data, function (i, r) {
                $tb.append($('<tr>')
                    .append($('<td>').text(r.gui_luc || '')).append($('<td>').text(r.gui_boi || ''))
                    .append($('<td>').text(r.ma_ket_qua || '')).append($('<td>').text(r.ma_gd || ''))
                    .append($('<td>').text(r.thoi_gian_tiep_nhan || '')).append($('<td>').text(r.thong_diep || ''))
                    .append($('<td>').text(r.loi || '')));
            });
            if (!kq.data.length) { $tb.append('<tr><td colspan="7" class="text-center">Chưa gửi lần nào.</td></tr>'); }
            $('#bh01-lich-su-than').empty().append($t.append($tb));
        }).fail(function (xhr) {
            $('#bh01-lich-su-than').empty().append($('<div>').addClass('alert alert-danger').text(loiTuXhr(xhr)));
        });
    });
})();
</script>
@endpush
