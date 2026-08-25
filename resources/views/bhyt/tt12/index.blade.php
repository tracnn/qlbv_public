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

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-3">
                <label for="mau">Mẫu</label>
                <select id="mau" class="form-control">
                    <option value="">-- Tất cả --</option>
                    @foreach ($danhSachMau as $ma => $ten)
                        <option value="{{ $ma }}">{{ $ten }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="ma_cskcb">Cơ sở KCB</label>
                <select id="ma_cskcb" class="form-control">
                    <option value="">-- Tất cả --</option>
                    @foreach ($danhSachCoSo as $ma => $nhan)
                        <option value="{{ $ma }}">{{ $nhan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="trang_thai">Trạng thái</label>
                <select id="trang_thai" class="form-control">
                    <option value="">-- Tất cả --</option>
                    @foreach ($cacTrangThai as $ma => $nhan)
                        <option value="{{ $ma }}">{{ $nhan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="tim">Tìm (mã hồ sơ / tên tệp / mã giao dịch)</label>
                <input type="text" id="tim" class="form-control">
            </div>
        </div>
        <div class="row" style="margin-top:10px">
            <div class="col-md-3">
                <label for="tu_ngay">Nạp từ ngày</label>
                <input type="date" id="tu_ngay" class="form-control">
            </div>
            <div class="col-md-3">
                <label for="den_ngay">Đến ngày</label>
                <input type="date" id="den_ngay" class="form-control">
            </div>
            <div class="col-md-3" style="margin-top:22px">
                <button type="button" id="btn-loc" class="btn btn-primary">
                    <i class="fa fa-search"></i> Lọc
                </button>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <div style="margin-bottom: 10px;">
            <button type="button" id="btn-xuat-danh-sach" class="btn btn-success btn-sm">
                <i class="fa fa-file-excel-o"></i> Xuất danh sách
            </button>
            <button type="button" id="btn-xuat-loi" class="btn btn-warning btn-sm">
                <i class="fa fa-file-excel-o"></i> Xuất bảng lỗi
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
@stop

@push('after-scripts')
<script>
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
    $('#btn-gui-nhieu').prop('disabled', n === 0);

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

    if (!confirm('Ký số và gửi ' + ds.length + ' hồ sơ lên cổng BHXH?\n\n'
                 + 'Cổng đã nhận thì không rút lại được.')) {
        return;
    }

    var $nut = $(this);
    $nut.prop('disabled', true);

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

// Bo loc dang xem tren man hinh - dung chung cho ca ajax.data cua DataTable lan nut xuat
// Excel. ANH CHUP luc tai, khong doc lai DOM luc bam xuat: nguoi dung doi o loc ma chua
// bam "Loc" roi bam "Xuat danh sach" van nhan tep khop voi bang dang hien.
var tt12LocDaTai = null;

function tt12ThamSoLoc() {
    return {
        mau:        $('#mau').val(),
        ma_cskcb:   $('#ma_cskcb').val(),
        trang_thai: $('#trang_thai').val(),
        tim:        $('#tim').val(),
        tu_ngay:    $('#tu_ngay').val(),
        den_ngay:   $('#den_ngay').val()
    };
}

var tt12Bang = $('#tt12-list').DataTable({
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

                return $('<a>').attr('href', url).text(data)[0].outerHTML;
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

                return $('<a>').addClass('btn btn-xs btn-default').attr('href', url).text('Chi tiết')[0].outerHTML;
            }
        }
    ],
    columnDefs: [{ targets: '_all', defaultContent: '' }]
});

$(function () {
    $('#btn-loc').on('click', function () {
        tt12Bang.ajax.reload();
    });

    $('#btn-xuat-danh-sach').on('click', function () {
        window.location = '{{ route('bhyt.tt12.xuat.danh-sach') }}?' + $.param(tt12LocDaTai || {});
    });

    $('#btn-xuat-loi').on('click', function () {
        window.location = '{{ route('bhyt.tt12.xuat.loi') }}?' + $.param(tt12LocDaTai || {});
    });
});
</script>
@endpush
