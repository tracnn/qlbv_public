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

        <div id="bh01-xem-truoc" style="display:none; margin-top:15px;"></div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading"><strong>Các bản đã lập</strong>
        <small class="text-muted">(lọc theo cơ sở/kỳ ở khối trên; bỏ trống cơ sở để xem tất cả)</small>
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
    var URL_DATA = "{{ route('bhyt.tt12.bh01.fetch-data') }}";
    // Mau URL co tham so: thay __MA__ bang ma ban da encodeURIComponent.
    var MAU_KY_LAI = "{{ route('bhyt.tt12.bh01.ky-lai', ['ma_ban' => '__MA__']) }}";
    var MAU_GUI = "{{ route('bhyt.tt12.bh01.gui', ['ma_ban' => '__MA__']) }}";
    var MAU_TAI = "{{ route('bhyt.tt12.bh01.tai', ['ma_ban' => '__MA__']) }}";
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
        return (kq && kq.thong_diep) ? kq.thong_diep : 'Không gửi được yêu cầu. Kiểm tra kết nối rồi thử lại.';
    }

    function thamSoKy() {
        return { ma_cskcb: $('#bh01-co-so').val(), nam: $('#bh01-nam').val(), thang: $('#bh01-thang').val() };
    }

    var bang = null;
    var hen = null;
    // Luc bat dau thay ban cho_ky lien tuc; null khi khong con ban nao cho ky.
    var choKyTu = null;
    // Ngung tu lam moi sau ~11 phut cho ky lien tuc: job ky mat thi ban nam cho_ky mai, hoi
    // 5 giay mot lan suot ngay chi ton may chu. Lon hon KY_LAI_SAU_PHUT (10) mot chut de lan
    // lam moi cuoi da thay nut "Ky lai" (coTheKyLai) hien ra.
    var CHO_KY_TOI_DA_MS = 11 * 60 * 1000;

    // Co ban dang cho ky thi lam moi 5 giay/lan cho toi khi het (dich vu ky chay o hang doi).
    function henLamMoi(coChoKy) {
        if (hen) { clearTimeout(hen); hen = null; }
        if (!coChoKy) { choKyTu = null; return; }
        if (choKyTu === null) { choKyTu = Date.now(); }
        if (Date.now() - choKyTu < CHO_KY_TOI_DA_MS) {
            hen = setTimeout(function () { bang.ajax.reload(null, false); }, 5000);
        }
    }

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
                },
                dataSrc: function (json) {
                    var coChoKy = (json.data || []).some(function (r) { return r.trang_thai_ky === 'cho_ky'; });
                    henLamMoi(coChoKy);
                    return json.data;
                }
            },
            columns: [
                { data: 'ma_ban', render: $.fn.dataTable.render.text() },
                { data: 'ma_cskcb', render: $.fn.dataTable.render.text() },
                { data: 'ky', render: $.fn.dataTable.render.text() },
                { data: 'so_ban', render: $.fn.dataTable.render.text() },
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

    function xemTruoc() {
        var t = thamSoKy();
        $('#bh01-btn-tao').prop('disabled', true);

        if (!t.ma_cskcb) {
            $('#bh01-xem-truoc').show().empty().append($('<div>').addClass('alert alert-warning').text('Chưa chọn cơ sở khám chữa bệnh.'));
            return;
        }

        $.getJSON(URL_XEM_TRUOC, t).done(function (kq) {
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

        $('#bh01-btn-tao').on('click', function () {
            var $nut = $(this).prop('disabled', true);
            $.ajax({ url: URL_TAO, method: 'POST', dataType: 'json', data: $.extend({ _token: CSRF }, thamSoKy()) })
                .done(function (kq) { thongBao(true, kq.thong_diep); choKyTu = null; bang.ajax.reload(); })
                .fail(function (xhr) { thongBao(false, loiTuXhr(xhr)); $nut.prop('disabled', false); });
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
            // Vua xep ky lai: tinh lai moc 10 phut tu lan nay.
            .done(function (kq) { thongBao(true, kq.thong_diep); choKyTu = null; bang.ajax.reload(null, false); })
            .fail(function (xhr) { thongBao(false, loiTuXhr(xhr)); });
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
