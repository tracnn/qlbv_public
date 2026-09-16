@extends('adminlte::page')

@section('title', 'Danh sách hồ sơ danh mục TT12/2026/BTC')

@section('content_header')
<h1>Danh sách <small>hồ sơ danh mục TT12/2026/BTC</small></h1>
@stop

@push('after-styles')
<style>
    .table-responsive { display: block; width: 100%; overflow-x: auto; }
    .nhan-canh-bao { color: #b94a48; font-weight: bold; }
</style>
@endpush

@section('content')
@include('includes.message')

@include('bhyt.tt12.partials.search')

<div class="panel panel-default">
    <div class="panel-body">
        <div style="margin-bottom: 10px;">
            <button type="button" id="btn-xuat-danh-sach" class="btn btn-success btn-sm">
                <i class="fa fa-file-excel-o"></i> Xuất danh sách
            </button>
            <button type="button" id="btn-xuat-loi" class="btn btn-warning btn-sm">
                <i class="fa fa-file-excel-o"></i> Xuất bảng lỗi
            </button>
            <button type="button" id="btn-xuat-nhat-ky" class="btn btn-default btn-sm">
                <i class="fa fa-file-excel-o"></i> Xuất nhật ký gửi
            </button>
            <button type="button" id="btn-xuat-xml" class="btn btn-info btn-sm" disabled>
                <i class="fa fa-file-code-o"></i> Xuất XML đã chọn (<span id="so-da-chon-xml">0</span>)
            </button>
            <button type="button" id="btn-gui-nhieu" class="btn btn-primary btn-sm" disabled>
                <i class="fa fa-paper-plane"></i> Ký và gửi đã chọn (<span id="so-da-chon">0</span>)
            </button>
        </div>
        <div id="ket-qua-gui-nhieu" style="display:none; margin-bottom:10px;"></div>
        <div class="table-responsive">
            <table class="table display table-hover responsive wrap datatable dtr-inline" width="100%" id="tt12-list" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:28px">
                            <input type="checkbox" id="chon-het-trang" title="Chọn hết các dòng trong trang này">
                        </th>
                        <th>Mã hồ sơ</th>
                        <th>Mẫu</th>
                        <th>Tên tệp</th>
                        <th>Mã CSKCB</th>
                        <th>Số dòng</th>
                        <th>Số lỗi</th>
                        <th>Lỗi nạp</th>
                        <th>Đã kiểm</th>
                        <th>Đã ký</th>
                        <th>Mã giao dịch</th>
                        <th>Mã kết quả</th>
                        <th>Thời gian tiếp nhận</th>
                        <th>Đã đồng bộ</th>
                        <th>Nạp lúc</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Modal chi tiet ho so. Than duoc nap bang AJAX tu bhyt.tt12.detail.than.

     Chi co MOT than chi tiet tren trang nay - than dung dinh danh (#tt12-tabs,
     #noi-dung-tab, #btn-ky-va-gui) nen khong duoc mo hai modal chi tiet cung luc. --}}
<div class="modal fade" id="modal-tt12" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xxl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Chi tiết hồ sơ <small id="modal-tt12-ma"></small></h4>
            </div>
            <div class="modal-body" id="modal-tt12-than"></div>
        </div>
    </div>
</div>
@stop

@push('after-scripts')
@include('bhyt.tt12.partials.js-chi-tiet')
<script>
// Khoi tao select2 cho MOI o chon tren trang, ke ca cac o do partial dung chung sinh ra
// (default_range, ma_cskcb). Cac o deu mang class 'select2' nhung class do chi la DANH
// DAU - khong goi .select2() thi chung hien nhu <select> tron: mat o tim kiem trong danh
// sach, va khac han man CTDT / XML3176.
//
// Rieng imported_by tu goi .select2() cua no SAU khi nap xong AJAX - phai vay vi luc
// trang tai xong o do con rong. Goi hai lan la vo hai.
$(function () {
    $('.select2').select2({ width: '100%' });
});

// ---------------------------------------------------------------------------------------
// Chon dong va gui hang loat. Uy nhiem su kien tren #tt12-list: DataTables ve lai toan bo
// tbody moi lan tai/phan trang, nen handler gan truc tiep se mat sau lan tai dau tien.
// ---------------------------------------------------------------------------------------
function tt12DaChon() {
    return $('#tt12-list tbody input.chon-ho-so:checked').map(function () {
        return this.value;
    }).get();
}

function tt12CapNhatSoDaChon() {
    var n = tt12DaChon().length;

    $('#so-da-chon').text(n);
    $('#so-da-chon-xml').text(n);
    $('#btn-gui-nhieu').prop('disabled', n === 0);
    $('#btn-xuat-xml').prop('disabled', n === 0);

    var tong = $('#tt12-list tbody input.chon-ho-so').length;
    $('#chon-het-trang').prop('checked', tong > 0 && n === tong);
}

$(document).on('change', '#tt12-list tbody input.chon-ho-so', tt12CapNhatSoDaChon);

$(document).on('change', '#chon-het-trang', function () {
    $('#tt12-list tbody input.chon-ho-so').prop('checked', this.checked);
    tt12CapNhatSoDaChon();
});

function tt12VeKetQuaLo(kq) {
    var $khoi = $('#ket-qua-gui-nhieu').empty().show();

    $khoi.append(
        $('<div>')
            .addClass('alert ' + (kq.thanh_cong ? 'alert-success' : 'alert-warning'))
            .css('margin-bottom', '6px')
            .text(kq.thong_diep || '')
    );

    if (kq.bo_qua && kq.bo_qua.length) {
        var $bang = $('<ul>');

        $.each(kq.bo_qua, function (i, dong) {
            $bang.append($('<li>').text(dong));
        });

        $khoi.append($bang);
    }
}

$(document).on('click', '#btn-gui-nhieu', function () {
    var ds = tt12DaChon();

    if (!ds.length) {
        return;
    }

    // Chan som cho de chiu, KHONG phai chot an toan: chot that nam o
    // BHYTTt12Controller::kyVaGuiNhieu(). Doc thang hang so cua controller de hai lop
    // khong the lech nhau.
    if (ds.length > {{ \App\Http\Controllers\BHYT\BHYTTt12Controller::TRAN_GUI_NHIEU }}) {
        Swal.fire(
            'Chọn quá nhiều hồ sơ',
            'Mỗi lượt chỉ gửi tối đa {{ \App\Http\Controllers\BHYT\BHYTTt12Controller::TRAN_GUI_NHIEU }} hồ sơ. Đang chọn ' + ds.length + ' hồ sơ.',
            'info'
        );
        return;
    }

    var $nut = $(this);

    // Hoi kem CON SO. Cong BHXH nhan la nhan that, khong co duong rut lai.
    Swal.fire({
        title: 'Xác nhận gửi',
        text: 'Ký số và gửi ' + ds.length + ' hồ sơ lên cổng BHXH? '
              + 'Cổng đã nhận thì không rút lại được.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ký và gửi',
        cancelButtonText: 'Hủy'
    }).then(function (chon) {
        if (!chon.value) {
            return;
        }

        $nut.prop('disabled', true);

        // Ket qua van do vao panel #ket-qua-gui-nhieu chu KHONG vao Swal: no liet ke tung
        // ho so bi bo qua kem ly do, nhet vao 'text' la ep ca danh sach thanh mot khoi chu
        // lien - va danh sach do chinh la thu nguoi dung can doc ky nhat.
        $.ajax({
            url: "{{ route('bhyt.tt12.ky-va-gui-nhieu') }}",
            method: 'POST',
            data: { _token: "{{ csrf_token() }}", ma_ho_so: ds },
            dataType: 'json'
        }).done(function (kq) {
            tt12VeKetQuaLo(kq);

            if (tt12Bang) {
                tt12Bang.ajax.reload(null, false);
            }
        }).fail(function (xhr) {
            var kq = xhr.responseJSON;

            tt12VeKetQuaLo(kq || {
                thanh_cong: false,
                thong_diep: 'Không gửi được yêu cầu. Kiểm tra kết nối rồi thử lại.'
            });
        }).always(function () {
            tt12CapNhatSoDaChon();
        });
    });
});

// Bo loc dang xem tren man hinh - dung chung cho ca ajax.data cua DataTable lan nut xuat
// Excel. ANH CHUP luc tai, khong doc lai DOM luc bam xuat: nguoi dung doi o loc ma chua
// bam "Tai du lieu" roi bam "Xuat danh sach" van nhan tep khop voi bang dang hien.
var tt12LocDaTai = null;

// Khoang ngay dang chon, do partials.date_range truyen sang qua fetchData(). Giu o bien
// rieng chu khong doc lai #date_range: o do la mot chuoi da dinh dang cua daterangepicker
// ("01/09/2026 - 30/09/2026"), khong phai hai gia tri may chu doc duoc.
var tt12TuNgay = null;
var tt12DenNgay = null;

// ── Bo loc tu URL (phuc vu bam o tren dashboard TT12) ───────────────────────
// Cung khuon voi bhyt.xml3176.index: lan goi fetchData() DAU TIEN den tu
// partials.load_data_button (tu goi ngay khi trang tai xong), truoc ca
// $(document).ready cua chinh tep nay (khoi select2 ben tren). Doc query string
// NGAY LUC SCRIPT NAY DUOC PARSE (khong doi ready), roi ap dung THAT SU o lan goi
// dau tien ben trong fetchData() - nhu vay dung bat ke fetchData() duoc goi tu dau
// truoc, va khong phu thuoc thu tu cac handler ready.
var tt12UrlFilters = (function () {
    if (!window.URLSearchParams) { return null; }
    var qs = new URLSearchParams(window.location.search);
    return qs.toString() ? qs : null;
})();

function tt12ThamSoLoc() {
    return {
        mau:         $('#mau').val(),
        ma_cskcb:    $('#ma_cskcb').val(),
        imported_by: $('#imported_by').val(),
        trang_thai:  $('#trang_thai').val(),
        tim:         $('#tim').val(),
        tu_ngay:     tt12TuNgay,
        den_ngay:    tt12DenNgay
    };
}

// Khai bao RONG. Tao bang o cap cao nhat thi DataTables ban AJAX ngay luc parse - luc do
// partials.date_range (chay trong document.ready) chua kip gan khoang ngay mac dinh, nen
// luot dau di len may chu voi tu_ngay/den_ngay RONG va quet toan bo bang tt12_ho_so khong
// gioi han ngay. Ngay sau do load_data_button goi fetchData() lan nua - thanh hai truy van
// moi lan mo trang, cai dau vo ich va nang nhat.
var tt12Bang = null;

// So the he cua lan mo modal. Tang moi lan bam; phan hoi cua mot luot CU quay ve muon thi
// bi bo qua. Xem chu thich trong .done() de biet vi sao phep kiem nay la bat buoc.
var luotMoModal = 0;

// Hop dong cua partials.load_data_button: no goi ham TOAN CUC nay, va tu goi mot lan ngay
// khi trang tai xong. Dat ten khac (vi du tt12FetchData) la nut bam se khong tim thay ham
// va man hinh khong bao gio tai du lieu.
//
// Tao bang LUOI o lan goi dau, cac lan sau chi reload - dung khuon ctdtBang cua man chung
// tu dien tu.
function fetchData(startDate, endDate) {
    // Ap dung bo loc tu URL (neu co) - chi ap dung DUNG MOT LAN, cho lan goi dau
    // tien cua fetchData() sau khi trang tai xong.
    if (tt12UrlFilters) {
        var qs = tt12UrlFilters;
        tt12UrlFilters = null; // dam bao khong ap dung lai o cac lan sau

        // Cac o chon don gian: ten tham so trung id element. .trigger('change') de
        // select2 (neu da khoi tao) ve lai chu hien thi cho khop - o chon van dung
        // gia tri that ngay ca khi select2 chua kip khoi tao, vi .val() ghi thang
        // vao <select> goc, con select2 khi khoi tao sau se doc lai gia tri do.
        ['mau', 'ma_cskcb', 'trang_thai'].forEach(function (key) {
            if (qs.has(key)) {
                $('#' + key).val(qs.get(key)).trigger('change');
            }
        });

        // Khoang ngay: dashboard gui 'YYYY-MM-DD'. Man danh sach nay loc theo
        // IMPORTED_AT (thoi diem nap), khac voi THOI_GIAN_TIEP_NHAN ma o luoi tren
        // dashboard hien thi - dashboard da gui san mot khoang du rong (xem
        // TU_NGAY_VO_HAN/DEN_NGAY_VO_HAN trong dashboard.tt12) de bam mot o xanh
        // luon ra it nhat ho so cua chinh o do, bat ke ho so duoc nap tu bao gio.
        if (qs.has('tu_ngay') && qs.has('den_ngay')) {
            var urlStart = moment(qs.get('tu_ngay'), 'YYYY-MM-DD').startOf('day');
            var urlEnd = moment(qs.get('den_ngay'), 'YYYY-MM-DD').endOf('day');

            var picker = $('#date_range').data('daterangepicker');
            if (picker) {
                picker.setStartDate(urlStart);
                picker.setEndDate(urlEnd);
            }

            // Ghi de truc tiep tham so dung cho lan tai NAY, khong phu thuoc viec
            // picker co san sang dung luc hay khong.
            startDate = urlStart.format('YYYY-MM-DD HH:mm:ss');
            endDate = urlEnd.format('YYYY-MM-DD HH:mm:ss');
        }
    }

    tt12TuNgay = startDate;
    tt12DenNgay = endDate;

    if (tt12Bang) {
        tt12Bang.ajax.reload();
        return;
    }

    tt12Bang = $('#tt12-list').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        scrollX: true,
        order: [[1, 'desc']],
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        ajax: {
            url: "{{ route('bhyt.tt12.fetch-data') }}",
            data: function (d) {
                tt12LocDaTai = tt12ThamSoLoc();
                $.extend(d, tt12LocDaTai);
            }
        },
        columns: [
            {
                "data": "ma_ho_so",
                orderable: false,
                searchable: false,
                className: "text-center",
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data ? 1 : 0;
                    }

                    return $('<input>')
                        .attr('type', 'checkbox')
                        .addClass('chon-ho-so')
                        .attr('value', row.ma_ho_so)[0].outerHTML;
                }
            },
            {
                "data": "ma_ho_so",
                render: function (data, type) {
                    if (type !== 'display') {
                        return data;
                    }

                    var url = "{{ route('bhyt.tt12.detail', ['ma_ho_so' => '__MA__']) }}"
                              .replace('__MA__', encodeURIComponent(data));

                    // .attr() chu KHONG noi chuoi: $('<div>').text(x).html() chi thoat '&',
                    // '<', '>' - khong thoat dau nhay kep, nen mot ma ho so dang
                    // 'A" onmouseover=... x="' se thoat ra khoi thuoc tinh va chay ngay khi
                    // mo man danh sach, voi phien cua chinh nguoi co quyen bam "Ky va gui".
                    return $('<a>')
                        .addClass('tt12-mo-chi-tiet')
                        .attr('href', url)
                        .attr('data-ma-ho-so', data)
                        .text(data)[0].outerHTML;
                }
            },
            { "data": "mau", render: $.fn.dataTable.render.text() },
            { "data": "ten_tep", render: $.fn.dataTable.render.text() },
            { "data": "ma_cskcb", render: $.fn.dataTable.render.text() },
            { "data": "so_dong", render: $.fn.dataTable.render.text() },
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
            {
                // Ho so co import_error nam lai CO Y de nguoi dung nhin thay va xoa. Khong co
                // cot nay thi ho chi thay mot ho so 0 dong, "Chua kiem", khong ky duoc.
                "data": "co_loi_nap",
                render: function (d) {
                    return Number(d) ? '<span class="nhan-canh-bao">Có</span>' : '—';
                }
            },
            { "data": "da_kiem", render: function (d) { return Number(d) ? 'Đã kiểm' : 'Chưa kiểm'; } },
            { "data": "da_ky", render: function (d) { return Number(d) ? 'Đã ký' : '—'; } },
            { "data": "ma_gd", render: $.fn.dataTable.render.text() },
            { "data": "ma_ket_qua", render: $.fn.dataTable.render.text() },
            { "data": "thoi_gian_tiep_nhan", render: $.fn.dataTable.render.text() },
            { "data": "da_dong_bo", render: function (d) { return Number(d) ? 'Đã đồng bộ' : '—'; } },
            { "data": "imported_at", render: $.fn.dataTable.render.text() },
            {
                "data": "ma_ho_so",
                orderable: false,
                searchable: false,
                render: function (data, type) {
                    if (type !== 'display') {
                        return data;
                    }

                    var url = "{{ route('bhyt.tt12.detail', ['ma_ho_so' => '__MA__']) }}"
                              .replace('__MA__', encodeURIComponent(data));

                    return $('<a>')
                        .addClass('btn btn-xs btn-default tt12-mo-chi-tiet')
                        .attr('href', url)
                        .attr('data-ma-ho-so', data)
                        .text('Chi tiết')[0].outerHTML;
                }
            }
        ],
            columnDefs: [{ targets: '_all', defaultContent: '' }]
    });
}

$(function () {
    // Khong con nut "Loc" tu lam o day: partials.load_data_button da lo viec do qua
    // fetchData(), kem phep kiem khoang ngay va hieu ung cho. Hai nut cung goi mot viec
    // theo hai duong khac nhau la hai hanh vi phai giu dong bo mai mai.

    // Xuat XML cua cac dong da tich, kem chu ky so neu ho so da ky.
    //
    // POST bang FORM AN chu khong $.ajax: phan hoi la mot TEP tai ve (.xml hoac .zip), ma
    // XMLHttpRequest khong lam trinh duyet hien hop thoai luu tep. Ba nut xuat Excel ben
    // duoi dung window.location duoc vi chung la GET; o day danh sach ma ho so co the dai
    // hon gioi han do dai URL nen phai POST.
    $('#btn-xuat-xml').on('click', function () {
        var ds = tt12DaChon();

        if (!ds.length) {
            return;
        }

        if (ds.length > {{ \App\Http\Controllers\BHYT\BHYTTt12Controller::TRAN_XUAT_XML }}) {
            alert('Mỗi lượt chỉ xuất tối đa {{ \App\Http\Controllers\BHYT\BHYTTt12Controller::TRAN_XUAT_XML }} hồ sơ. Đang chọn ' + ds.length + ' hồ sơ.');
            return;
        }

        var $form = $('<form>')
            .attr('method', 'POST')
            .attr('action', '{{ route('bhyt.tt12.xuat.xml') }}')
            .css('display', 'none');

        $form.append($('<input>').attr({ type: 'hidden', name: '_token', value: '{{ csrf_token() }}' }));

        // .attr('value', ...) chu khong noi chuoi HTML: ma ho so den tu ten tep nguoi dung
        // tai len, co the chua dau nhay kep.
        $.each(ds, function (i, ma) {
            $form.append($('<input>').attr({ type: 'hidden', name: 'ma_ho_so[]', value: ma }));
        });

        $form.appendTo('body').submit().remove();
    });

    $('#btn-xuat-danh-sach').on('click', function () {
        window.location = '{{ route('bhyt.tt12.xuat.danh-sach') }}?' + $.param(tt12LocDaTai || {});
    });

    $('#btn-xuat-loi').on('click', function () {
        window.location = '{{ route('bhyt.tt12.xuat.loi') }}?' + $.param(tt12LocDaTai || {});
    });

    // Dung lai tt12LocDaTai (tu_ngay/den_ngay cua partials.date_range) thay vi mo them
    // man chon ngay rieng: man danh sach da co san o do, bat nguoi dung chon lai la bat
    // ho lam hai lan mot viec.
    $('#btn-xuat-nhat-ky').on('click', function () {
        window.location = '{{ route('bhyt.tt12.xuat.nhat-ky') }}?' + $.param(tt12LocDaTai || {});
    });

    // Mo modal chi tiet. Chan click THUONG thoi - the <a> van giu href that nen ctrl+click
    // van mo tab moi nhu cu.
    $(document).on('click', '.tt12-mo-chi-tiet', function (e) {
        if (e.ctrlKey || e.metaKey || e.shiftKey) {
            return;
        }

        e.preventDefault();

        var luot = ++luotMoModal;
        var maHoSo = $(this).data('ma-ho-so');

        // Xoa sach than cu TRUOC khi goi mang: de nguyen la moi nguoi dung doc nham ho so
        // truoc trong luc cho.
        $('#modal-tt12-than').html('<p class="text-muted">Đang tải…</p>');
        $('#modal-tt12-ma').text(maHoSo);
        $('#modal-tt12').modal('show');

        // href da duoc sinh san kem encodeURIComponent, va route than la duong dan chi tiet
        // cong '/than'. Neu duong dan chi tiet doi thi phai sua ca route lan cho nay -
        // Tt12ChiTietModalTest canh phep ghep do.
        $.get($(this).attr('href') + '/than')
            .done(function (html) {
                // Phan hoi cua mot luot CU ve muon: bo qua. Khong co phep kiem nay thi than
                // ho so A co the de len khung dang mang ten ho so B - va nut "Ky va gui"
                // trong than do se POST HO SO A len cong BHXH. Khoa phia may chu khong do
                // duoc vi A la ho so khac, hoan toan chua bi khoa.
                if (luot !== luotMoModal) {
                    return;
                }

                $('#modal-tt12-than').html(html);
                window.tt12NapTabDau();
            })
            .fail(function (xhr) {
                if (luot !== luotMoModal) {
                    return;
                }

                var loi = xhr.status === 404
                    ? 'Không tìm thấy hồ sơ này. Có thể nó vừa bị xóa.'
                    : 'Không tải được chi tiết hồ sơ. Thử lại sau.';

                $('#modal-tt12-than').html('<p class="text-danger">' + loi + '</p>');
            });
    });

    // Man danh sach tu quyet phan ung - xem chu thich trong js-chi-tiet.
    // ajax.reload(null, false): GIU nguyen bo loc va trang dang xem. Truyen true (hoac bo
    // tham so) se nhay ve trang 1, va nguoi xu nhieu ho so lien tiep phai loc lai tu dau.
    $(document).on('tt12:da-xep-hang tt12:da-cuu-ho', function () {
        $('#modal-tt12').modal('hide');

        // tt12Bang duoc tao LUOI trong fetchData(), nen no con null cho toi khi nguoi dung
        // bam Loc lan dau. Phep kiem nay theo dung khuon o khoi gui nhieu ben tren.
        if (tt12Bang) {
            tt12Bang.ajax.reload(null, false);
        }
    });
});
</script>
@endpush
