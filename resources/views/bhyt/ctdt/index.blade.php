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
        </div>
        <div class="table-responsive">
            <table class="table display table-hover responsive wrap datatable dtr-inline" width="100%" id="ctdt-list" style="width:100%">
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
        scrollX: true,
        order: [[11, 'desc']],
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        ajax: {
            url: "{{ route('bhyt.ctdt.fetch-data') }}",
            data: function (d) {
                // Khoang ngay den tu partials.date_range qua partials.load_data_button,
                // dang 'YYYY-MM-DD HH:mm:ss'. CtdtDanhSach nhan ca dang co gio lan dang
                // chi co ngay.
                $.extend(d, thamSoLoc());
            }
        },
        columns: [
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
                    var canhBao = row.khong_co_ma_yte
                        ? ' <span class="nhan-canh-bao" title="Hồ sơ không có mã y tế — nạp lại sẽ tạo bản ghi mới, không ghi đè">⚠</span>'
                        : '';

                    var the = $('<a>')
                        .addClass('ctdt-mo-chi-tiet')
                        .attr('href', url)
                        .attr('data-ma-ho-so', data)
                        .text(data);

                    return the[0].outerHTML + canhBao;
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
    $('#btn-xuat-danh-sach').on('click', function () {
        window.location = '{{ route('bhyt.ctdt.xuat.danh-sach') }}?' + $.param(thamSoLoc());
    });

    $('#btn-xuat-loi').on('click', function () {
        window.location = '{{ route('bhyt.ctdt.xuat.loi') }}?' + $.param(thamSoLoc());
    });

    // Nhat ky chi nhan khoang ngay, KHONG nhan cac o loc khac: bang nay khong co cot dich
    // vu / co so, nen truyen chung vao chi tao ao giac da loc.
    $('#btn-xuat-nhat-ky').on('click', function () {
        window.location = '{{ route('bhyt.ctdt.xuat.nhat-ky') }}'
            + '?tu_ngay=' + encodeURIComponent(ctdtRange.from || '')
            + '&den_ngay=' + encodeURIComponent(ctdtRange.to || '');
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
