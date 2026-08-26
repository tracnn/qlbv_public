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
        <div style="margin-bottom: 10px;">
            <button type="button" id="btn-xuat-danh-sach" class="btn btn-success btn-sm">
                <i class="fa fa-file-excel-o"></i> Xuất danh sách
            </button>
            <button type="button" id="btn-xuat-loi" class="btn btn-warning btn-sm">
                <i class="fa fa-file-excel-o"></i> Xuất bảng lỗi
            </button>
            <button type="button" id="btn-xuat-nhat-ky" class="btn btn-default btn-sm">
                <i class="fa fa-history"></i> Xuất nhật ký gửi
            </button>
            <button type="button" id="btn-gui-nhieu" class="btn btn-primary btn-sm" disabled>
                <i class="fa fa-paper-plane"></i> Ký và gửi đã chọn (<span id="so-da-chon">0</span>)
            </button>
        </div>
        <div id="ket-qua-gui-nhieu" style="display:none; margin-bottom:10px;"></div>
        <div class="table-responsive">
            <table class="table display table-hover responsive wrap datatable dtr-inline" width="100%" id="ctdt-list" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:28px">
                            {{-- Chi chon trong TRANG DANG XEM. Khong lam "chon tat ca ho so
                                 khop bo loc": nguoi bam se gui nhung ho so minh chua tung
                                 nhin, va sai bo loc mot chut la gui sai hang loat len cong
                                 BHXH - noi khong co duong rut lai. --}}
                            <input type="checkbox" id="chon-het-trang" title="Chọn hết các dòng trong trang này">
                        </th>
                        <th>Mã hồ sơ</th>
                        <th>Dịch vụ</th>
                        <th>Mã CSKCB</th>
                        <th>Họ tên</th>
                        <th>Mã thẻ</th>
                        <th>Số CCCD</th>
                        <th>Mã BHXH</th>
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

{{-- Modal chi tiet ho so. Than duoc nap bang AJAX tu bhyt.ctdt.detail.than.

     Chi co MOT than chi tiet tren trang nay - than dung dinh danh (#ctdt-tabs,
     #noi-dung-tab, #btn-ky-va-gui) nen khong duoc mo hai modal chi tiet cung luc. --}}
<div class="modal fade" id="modal-ctdt" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xxl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Chi tiết hồ sơ <small id="modal-ctdt-ma"></small></h4>
            </div>
            <div class="modal-body" id="modal-ctdt-than"></div>
        </div>
    </div>
</div>
@stop

@push('after-scripts')
@include('bhyt.ctdt.partials.js-chi-tiet')
<script>
// Khoi tao select2 cho MOI o chon tren trang, ke ca cac o do partial dung chung sinh ra
// (ma_cskcb). Cac o deu mang class 'select2' nhung class do chi la danh dau - khong goi
// .select2() thi chung hien nhu <select> tron: mat o tim kiem, va khac han man XML3176.
// Rieng imported_by tu goi .select2() cua no sau khi nap xong AJAX; goi hai lan la vo hai.
$(function () {
    $('.select2').select2({ width: '100%' });
});

// ---------------------------------------------------------------------------------------
// Gui hang loat.
//
// Uy nhiem su kien tren #ctdt-list chu khong gan truc tiep vao tung o tich: DataTables ve
// lai toan bo tbody moi lan tai/phan trang/sap xep, nen moi handler gan truc tiep se bien
// mat im lang sau lan tai dau tien.
// ---------------------------------------------------------------------------------------
function ctdtDaChon() {
    return $('#ctdt-list tbody input.chon-ho-so:checked').map(function () {
        return this.value;
    }).get();
}

function ctdtCapNhatSoDaChon() {
    var n = ctdtDaChon().length;

    $('#so-da-chon').text(n);
    $('#btn-gui-nhieu').prop('disabled', n === 0);

    // Dong bo o tich tieu de: chi tich khi CA CAC DONG CHON DUOC cua trang deu da tich.
    var tong = $('#ctdt-list tbody input.chon-ho-so').length;
    $('#chon-het-trang').prop('checked', tong > 0 && n === tong);
}

$(document).on('change', '#ctdt-list tbody input.chon-ho-so', ctdtCapNhatSoDaChon);

$(document).on('change', '#chon-het-trang', function () {
    $('#ctdt-list tbody input.chon-ho-so').prop('checked', this.checked);
    ctdtCapNhatSoDaChon();
});

$(document).on('click', '#btn-gui-nhieu', function () {
    var ds = ctdtDaChon();

    if (!ds.length) {
        return;
    }

    if (ds.length > {{ \App\Http\Controllers\BHYT\BHYTCtdtController::TRAN_GUI_NHIEU }}) {
        Swal.fire(
            'Chọn quá nhiều hồ sơ',
            'Mỗi lượt chỉ gửi tối đa {{ \App\Http\Controllers\BHYT\BHYTCtdtController::TRAN_GUI_NHIEU }} hồ sơ. Đang chọn ' + ds.length + ' hồ sơ.',
            'info'
        );
        return;
    }

    var $nut = $(this);

    // Hoi kem CON SO. Cong BHXH nhan la nhan that, khong co duong rut lai, va chung tu PL02
    // khong mang ma giao dich phia nguoi gui nen cong khong the nhan ra ban trung.
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
            url: "{{ route('bhyt.ctdt.ky-va-gui-nhieu') }}",
            method: 'POST',
            data: { _token: "{{ csrf_token() }}", ma_ho_so: ds },
            dataType: 'json'
        }).done(function (kq) {
            ctdtVeKetQuaLo(kq);

            // Tai lai danh sach de trang thai moi hien ra, va de moi o tich duoc xoa - giu o
            // tich cu lai la moi nguoi dung bam gui lan hai cho cung nhung ho so vua gui.
            if (ctdtBang) {
                ctdtBang.ajax.reload(null, false);
            }
        }).fail(function (xhr) {
            var kq = xhr.responseJSON;

            ctdtVeKetQuaLo(kq || {
                thanh_cong: false,
                thong_diep: 'Không gửi được yêu cầu. Kiểm tra kết nối rồi thử lại.'
            });
        }).always(function () {
            ctdtCapNhatSoDaChon();
        });
    });
});

function ctdtVeKetQuaLo(kq) {
    var $khoi = $('#ket-qua-gui-nhieu').empty().show();

    $khoi.append(
        $('<div>')
            .addClass('alert ' + (kq.thanh_cong ? 'alert-success' : 'alert-warning'))
            .css('margin-bottom', '6px')
            .text(kq.thong_diep || '')
    );

    if (kq.bo_qua && kq.bo_qua.length) {
        var $bang = $('<table>').addClass('table table-bordered table-condensed');

        $bang.append($('<thead>').append(
            $('<tr>')
                .append($('<th>').text('Hồ sơ bị bỏ qua'))
                .append($('<th>').text('Lý do'))
        ));

        var $than = $('<tbody>');

        // .text() cho CA HAI o: ma_ho_so den tu XML ben ngoai, va ly_do co the mang nguyen
        // van phan hoi cua cong.
        $.each(kq.bo_qua, function (i, dong) {
            $than.append(
                $('<tr>')
                    .append($('<td>').text(dong.ma_ho_so))
                    .append($('<td>').text(dong.ly_do))
            );
        });

        $bang.append($than);
        $khoi.append($bang);
    }
}

// KHONG boc trong $(function(){...}): partials.load_data_button goi ham TOAN CUC
// fetchData(startDate, endDate) va tu goi mot lan ngay khi trang tai xong. Ham nay phai
// nam o pham vi window de no thay duoc.
var ctdtBang = null;

// Khoang ngay cua lan tai HIEN TAI. Phai o pham vi module chu khong phai tham so cua
// fetchData(): DataTable chi duoc dung MOT lan, nen closure "data" ben trong no phai doc
// duoc gia tri moi nhat o cac lan tai sau.
var ctdtRange = { from: null, to: null };

// ANH CHUP bo loc cua lan TAI DU LIEU gan nhat - khac han thamSoLoc(), va hai bien nay
// KHONG thua.
//
// thamSoLoc() doc DOM TAI THOI DIEM GOI. DataTable goi no luc ajax.reload(); nut xuat thi
// goi luc bam. Nguoi dung doi o "Dich vu" hay "Trang thai gui" ma CHUA bam "Tai du lieu"
// roi bam "Xuat danh sach" se nhan mot tep mang bo loc MOI trong khi man hinh van la ket
// qua CU - khong mot canh bao nao, va nguoi doi chieu se khong hieu vi sao hai ben lech.
//
// Dung chung mot HAM getter la chua du; phai dung chung mot ANH CHUP.
var ctdtLocDaTai = null;

function ctdtNhanDichVu(ma) {
    var nhan = @json(collect(config('ctdt.dich_vu'))->map(function ($c) { return $c['ten']; }));

    return nhan[ma] ? nhan[ma] : ma;
}

// Bo loc dang xem tren man hinh - MOT ban duy nhat cho ca ajax.data cua DataTable lan nut
// tai Excel. Neu hai noi tu dung mot ban rieng thi them mot o loc ma quen ben kia se lam
// tep xuat khac han bang dang hien, va khong co dau hieu gi cho toi luc ai do ngoi doi
// chieu tung dong voi ban cua BHXH.
function thamSoLoc() {
    return {
        tu_ngay:        ctdtRange.from,
        den_ngay:       ctdtRange.to,
        dich_vu:        $('#dich_vu').val(),
        loai_ho_so:     $('#loai_ho_so').val(),
        macskcb:        $('#ma_cskcb').val(),
        imported_by:    $('#imported_by').val(),
        tim:            $('#tim').val(),
        chi_con_loi:    $('#chi_con_loi').val(),
        trang_thai_gui: $('#trang_thai_gui').val()
    };
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
        // TAT o Search mac dinh cua DataTables: yajra ap dieu kien do len truy van phia may
        // chu, nen go vao do lam MAN HINH thu hep con TEP XUAT thi khong - dung kieu lech
        // am tham ma ca dot sua nay dang chua. Man loc da co o #tim rieng lam dung viec do
        // va di kem duoc vao URL xuat.
        searching: false,
        scrollX: true,
        order: [[11, 'desc']],
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        ajax: {
            url: "{{ route('bhyt.ctdt.fetch-data') }}",
            data: function (d) {
                // Khoang ngay den tu partials.date_range qua partials.load_data_button,
                // dang 'YYYY-MM-DD HH:mm:ss'. CtdtDanhSach nhan ca dang co gio lan dang
                // chi co ngay.
                // Chup bo loc NGAY TAI DAY: day la thoi diem duy nhat man hinh va tep xuat
                // chac chan nhin thay cung mot bo loc.
                ctdtLocDaTai = thamSoLoc();

                $.extend(d, ctdtLocDaTai);
            }
        },
        columns: [
            {
                // Cot o tich. KHONG suy dieu kien gui o day - doc thang row.co_the_gui do
                // server tinh bang CtdtDuDieuKienGui. Suy lai o trinh duyet la ban chep thu
                // hai cua cung mot luat, va hai ban se lech.
                "data": "co_the_gui",
                orderable: false,
                searchable: false,
                className: "text-center",
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data ? 1 : 0;
                    }

                    // Ho so khong du dieu kien thi KHONG co o tich. Hien o tich roi vo hieu
                    // hoa cung duoc, nhung mot bang 25 dong toan o tich mo khien nguoi dung
                    // di tim xem minh bam sai cho nao.
                    if (!data) {
                        return '';
                    }

                    // DOM API chu khong noi chuoi: ma_ho_so den tu XML ben ngoai va co the
                    // chua dau nhay kep - xem chu thich dai o cot Ma ho so ben duoi.
                    return $('<input>')
                        .attr('type', 'checkbox')
                        .addClass('chon-ho-so')
                        .attr('value', row.ma_ho_so)[0].outerHTML;
                }
            },
            {
                // Ho ten, dich vu... deu doc thang tu tep XML ben ngoai (khong qua kiem
                // duyet). Moi gia tri lot vao chuoi HTML PHAI duoc thoat - khong thi mot ho
                // ten dang "<img src=x onerror=...>" chay ngay khi mo man danh sach.
                //
                // $('<div>').text(x).html() CHI thoat '&', '<', '>' - KHONG thoat dau nhay
                // kep. No du cho ngu canh NUT VAN BAN, va KHONG du cho ngu canh THUOC TINH:
                // mot MA_YTE dang 'A" onmouseover=alert(1) x="' se thoat ra khoi thuoc tinh.
                // CtdtMaHoSo::cua() lay MA_YTE nguyen van tu XML, chi kiem do dai, khong kiem
                // bo ky tu - nen gia tri do vao duoc that.
                //
                // Vi vay o dau co THUOC TINH thi dung DOM API (jQuery .attr()/.text()) roi
                // lay outerHTML: moi ngu canh duoc thoat theo dung luat cua no, va khong con
                // cho noi chuoi nao de nguoi sau sao chep sai.
                "data": "ma_ho_so",
                render: function (data, type, row) {
                    if (type !== 'display') {
                        return data;
                    }

                    // ma_ho_so o nhanh lui chua dau '#' (vd Id-abc#1). Khong ma hoa thi
                    // trinh duyet cat tu dau '#' va URL tro sai ho so.
                    var url = "{{ route('bhyt.ctdt.detail', ['ma_ho_so' => '__MA__']) }}"
                              .replace('__MA__', encodeURIComponent(data));
                    var the = $('<a>')
                        .addClass('ctdt-mo-chi-tiet')
                        .attr('href', url)
                        .attr('data-ma-ho-so', data)
                        .text(data);

                    return the[0].outerHTML;
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
            { "data": "so_cccd", orderable: false, searchable: false, render: $.fn.dataTable.render.text() },
            { "data": "ma_bhxh", orderable: false, searchable: false, render: $.fn.dataTable.render.text() },
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
                    // Dung DOM API chu khong noi chuoi, cung ly do nhu cot ma_ho_so:
                    // $('<div>').text(x).html() khong thoat dau nhay kep nen khong dung
                    // duoc cho ngu canh thuoc tinh.
                    var the = $('<a>')
                        .addClass('btn btn-xs btn-default ctdt-mo-chi-tiet')
                        .attr('href', url)
                        .attr('data-ma-ho-so', data)
                        .text('Chi tiết');

                    return the[0].outerHTML;
                }
            }
        ],
        columnDefs: [{ targets: '_all', defaultContent: '' }]
    });
}

// So the he cua lan mo modal. Tang moi lan bam; phan hoi cua mot luot CU quay ve muon thi
// bi bo qua. Xem chu thich trong .done() de biet vi sao phep kiem nay la bat buoc.
var luotMoModal = 0;

$(function () {
    // Ghep DUNG bo loc dang xem vao URL tai: nut tai ma bo qua bo loc se cho ra mot tep
    // khac han bang dang hien, va nguoi dung se tuong man hinh sai.
    //
    // Dung ctdtLocDaTai (ANH CHUP luc tai) chu KHONG goi lai thamSoLoc(): goi lai la doc
    // DOM o mot thoi diem khac, tuc lai lech voi bang dang hien.
    $('#btn-xuat-danh-sach').on('click', function () {
        window.location = '{{ route('bhyt.ctdt.xuat.danh-sach') }}?' + $.param(ctdtLocDaTai || {});
    });

    $('#btn-xuat-loi').on('click', function () {
        window.location = '{{ route('bhyt.ctdt.xuat.loi') }}?' + $.param(ctdtLocDaTai || {});
    });

    // Nhat ky chi nhan khoang ngay, KHONG nhan cac o loc khac: bang nay khong co cot dich
    // vu / co so, nen truyen chung vao chi tao ao giac da loc.
    $('#btn-xuat-nhat-ky').on('click', function () {
        var daTai = ctdtLocDaTai || {};

        window.location = '{{ route('bhyt.ctdt.xuat.nhat-ky') }}'
            + '?tu_ngay=' + encodeURIComponent(daTai.tu_ngay || '')
            + '&den_ngay=' + encodeURIComponent(daTai.den_ngay || '');
    });

    // Mo modal chi tiet. Chan click THUONG thoi - the <a> van giu href that nen ctrl+click
    // van mo tab moi nhu cu.
    //
    // e.which === 2 la phep kiem CHET: chuot giua khong phat su kien 'click' (no phat
    // 'auxclick'), nen nhanh do khong bao gio chay. Giu lai cho vo hai. Chuot giua VAN mo
    // duoc tab moi, nhung la nho the <a> co href THAT chu khong nho phep kiem nay.
    $(document).on('click', '.ctdt-mo-chi-tiet', function (e) {
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) {
            return;
        }

        e.preventDefault();

        var luot = ++luotMoModal;
        var maHoSo = $(this).data('ma-ho-so');

        // Xoa sach than cu TRUOC khi goi mang: de nguyen la moi nguoi dung doc nham ho so
        // truoc trong luc cho.
        $('#modal-ctdt-than').html('<p class="text-muted">Đang tải…</p>');
        $('#modal-ctdt-ma').text(maHoSo);
        $('#modal-ctdt').modal('show');

        // href da duoc Blade sinh san (encodeURIComponent da ap dung) va route than la
        // duong dan chi tiet cong '/than'. Neu duong dan chi tiet doi thi phai sua ca route
        // ben Task 2 lan cho nay.
        $.get($(this).attr('href') + '/than')
            .done(function (html) {
                // Phan hoi cua mot luot CU ve muon: bo qua. Khong co phep kiem nay thi than
                // ho so A co the de len khung dang mang ten ho so B - va nut "Ky va gui"
                // trong than do se POST HO SO A len cong BHXH. Khoa phia may chu khong do
                // duoc vi A la ho so khac, hoan toan chua bi khoa.
                if (luot !== luotMoModal) {
                    return;
                }

                $('#modal-ctdt-than').html(html);
                window.ctdtNapTabDau();
            })
            .fail(function (xhr) {
                if (luot !== luotMoModal) {
                    return;
                }

                var loi = xhr.status === 404
                    ? 'Không tìm thấy hồ sơ này. Có thể nó vừa bị xóa.'
                    : 'Không tải được chi tiết hồ sơ. Thử lại sau.';

                $('#modal-ctdt-than').html('<p class="text-danger">' + loi + '</p>');
            });
    });

    // Man danh sach tu quyet phan ung - xem chu thich trong js-chi-tiet.
    // ajax.reload(null, false): GIU nguyen bo loc va trang dang xem. Truyen true (hoac bo
    // tham so) se nhay ve trang 1, va nguoi xu nhieu ho so lien tiep phai loc lai tu dau.
    $(document).on('ctdt:da-xep-hang ctdt:da-xoa', function () {
        $('#modal-ctdt').modal('hide');
        // Dung lai bien ctdtBang da co san trong tep nay (gan o khoang dong 82). Neu no
        // khong con trong pham vi thi dung $('#ctdt-list').DataTable() - cung mot doi tuong.
        ctdtBang.ajax.reload(null, false);
    });
});
</script>
@endpush
