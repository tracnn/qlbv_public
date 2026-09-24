@extends('adminlte::page')

@section('title', 'Kết quả tra cứu thẻ BHYT')

@section('content_header')
  <h1>
    Kết quả tra cứu thẻ
    <small>BHYT</small>
  </h1>
@stop

@section('content')
@include('includes.message')

@include('bhyt.check-hein-card.partials.search')

<div class="row" style="margin-bottom:10px">
  <div class="col-md-12">
    <a id="btn-xuat" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</a>
  </div>
</div>

<div class="panel panel-default">
  <div class="panel-body table-responsive">
    <table id="check-hein-card-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
      <thead>
        <tr>
          <th>Mã hồ sơ</th>
          <th>Số thẻ</th>
          <th>Họ tên</th>
          <th>Ngày sinh</th>
          <th>Cơ sở</th>
          <th>Mã tra cứu</th>
          <th>Mã kiểm tra</th>
          <th>Ghi chú</th>
          <th>Thời gian</th>
          <th>Thao tác</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

{{-- Modal chi tiet: dung THANG tu du lieu dong ma DataTables da co, khong goi them endpoint. --}}
<div class="modal fade" id="modal-the" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Chi tiết kết quả tra cứu thẻ</h4>
      </div>
      <div class="modal-body table-responsive">
        <table class="table table-bordered table-condensed">
          <tbody id="modal-the-body"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    // KHONG boc trong $(document).ready(...): partials.load_data_button goi ham TOAN CUC
    // fetchData(startDate, endDate) va tu goi mot lan ngay khi trang tai xong. Ham do phai
    // nam o pham vi window thi no moi thay duoc.
    var table = null;

    // Khoang ngay cua lan tai HIEN TAI. Phai o pham vi module chu khong phai bien cuc bo cua
    // fetchData(): closure "data" cua DataTables doc lai no o MOI lan reload sau do.
    var khoangNgay = { from: null, to: null };

    // Cac truong dua vao modal chi tiet. Danh sach cot hien tren bang da du cho viec luot;
    // 17 truong con lai chi can khi soi mot ho so cu the.
    var TRUONG_CHI_TIET = [
        ['ma_lk', 'Mã hồ sơ'],
        ['ma_cskcb', 'Cơ sở KCB'],
        ['nhan_tracuu', 'Mã tra cứu'],
        ['nhan_kiemtra', 'Mã kiểm tra'],
        ['ma_ketqua', 'Mã kết quả'],
        ['ghi_chu', 'Ghi chú'],
        ['ma_the', 'Số thẻ'],
        ['ho_ten', 'Họ tên'],
        ['ngay_sinh', 'Ngày sinh'],
        ['ma_the_gui', 'Số thẻ đã gửi'],
        ['ho_ten_gui', 'Họ tên đã gửi'],
        ['ngay_sinh_gui', 'Ngày sinh đã gửi'],
        ['ma_dkbd_gui', 'Nơi ĐKBĐ đã gửi'],
        ['gioi_tinh', 'Giới tính'],
        ['dia_chi', 'Địa chỉ'],
        ['ma_the_cu', 'Thẻ cũ'],
        ['ma_the_moi', 'Thẻ mới'],
        ['ma_dkbd', 'Nơi ĐKBĐ'],
        ['ma_dkbd_moi', 'Nơi ĐKBĐ mới'],
        ['ten_dkbd_moi', 'Tên nơi ĐKBĐ mới'],
        ['cq_bhxh', 'Cơ quan BHXH'],
        ['gt_the_tu', 'Thẻ giá trị từ'],
        ['gt_the_den', 'Thẻ giá trị đến'],
        ['gt_the_tumoi', 'Thẻ mới giá trị từ'],
        ['gt_the_denmoi', 'Thẻ mới giá trị đến'],
        ['ma_kv', 'Mã khu vực'],
        ['ngay_du5nam', 'Ngày đủ 5 năm'],
        ['maso_bhxh', 'Mã số BHXH'],
        ['updated_at', 'Thời gian tra cứu']
    ];

    // MOT nguon tham so cho ca DataTables lan nut xuat: neu tach doi thi them mot bo loc ma
    // quen ben kia se lam tep xuat khac han man hinh.
    function thamSoLoc() {
        return {
            tu_ngay: khoangNgay.from,
            den_ngay: khoangNgay.to,
            trang_thai: $('#trang_thai').val(),
            ma_cskcb: $('#ma_cskcb').val(),
            tim: $('#tim').val()
        };
    }

    function thamSo(d) {
        $.extend(d, thamSoLoc());
    }

    // Render cot quan sat: gia tri lay tu gia tri DA GUI (cong khong tra) thi in nghieng xam de
    // khong nham la du lieu cong xac nhan. .text() truoc .html(): du lieu tu cong/HIS.
    function hienGiaTri(cotNguon) {
        return function (d, type, row) {
            var t = $('<div>').text(d === null || d === undefined ? '' : d).html();

            return row[cotNguon] === 'gui'
                ? '<i class="text-muted" title="Theo HIS (cổng không trả về)">' + t + '</i>'
                : t;
        };
    }

    function fetchData(startDate, endDate) {
        khoangNgay.from = startDate;
        khoangNgay.to = endDate;

        table = $('#check-hein-card-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "order": [[8, 'desc']],
            "ajax": {
                url: "{{ route('bhyt.check-hein-card.fetch-data') }}",
                data: thamSo
            },
            "columns": [
                { "data": "ma_lk" },
                // Khong phai cot SQL: sap xep theo chung se lam truy van Datatables vo.
                { "data": "hien_ma_the", "orderable": false, "searchable": false, "render": hienGiaTri('nguon_ma_the') },
                { "data": "hien_ho_ten", "orderable": false, "searchable": false, "render": hienGiaTri('nguon_ho_ten') },
                { "data": "hien_ngay_sinh", "orderable": false, "searchable": false, "render": hienGiaTri('nguon_ngay_sinh') },
                { "data": "ma_cskcb" },
                { "data": "nhan_tracuu" },
                { "data": "nhan_kiemtra" },
                { "data": "ghi_chu" },
                { "data": "updated_at" },
                { "data": "id", "orderable": false, "searchable": false, "render": function (d, type, row) {
                    var h = '<button type="button" class="btn btn-xs btn-default nut-xem" data-id="' + d + '">Xem</button>';

                    // Chi dong LOI moi co nut tra lai - dong hop le tra lai chi ton luot goi cong.
                    if (row.co_loi) {
                        h += ' <button type="button" class="btn btn-xs btn-warning nut-tra-lai">Tra lại</button>';
                    }

                    return h;
                } }
            ],
            // To nen do nhat cho dong co van de. Dung co_loi may chu tinh san, khong lap lai
            // dieu kien o day - de chi co MOT dinh nghia "loi" trong he thong.
            "createdRow": function (row, data) {
                if (data.co_loi) {
                    $(row).css('background-color', '#f9e3e3');
                }
            }
        });
    }

    $(document).ready(function() {
        // Thieu loi goi nay thi partial chi la mot <select> tho, khong ra select2.
        $('.select2').select2({width: '100%'});

        // KHONG goi fetchData() o day: partials.load_data_button da tu goi mot lan khi trang
        // tai xong, kem theo khoang ngay. Goi them o day la nap bang HAI LAN, va lan cua ta
        // se khong co khoang ngay.

        // Doi o loc thi nap lai ngay; rieng khoang ngay phai bam "Tai du lieu" - do la khuon
        // chung cua partial, va no co chu dich: nguoi dung thuong chinh ca hai dau khoang
        // truoc khi muon tai.
        $('#trang_thai, #ma_cskcb').on('change', function () {
            if (table) {
                table.ajax.reload();
            }
        });

        // Gui DUNG bo tham so ma DataTables dang dung: tep xuat ra bang thu dang hien tren man.
        $('#btn-xuat').on('click', function () {
            // Chua tai lan nao thi khoangNgay con rong, va tep xuat se la TOAN BO bang - tren
            // may chu gioi han PHP 128MB do la mot yeu cau chet giua chung.
            if (!khoangNgay.from) {
                alert('Bấm "Tải dữ liệu" trước khi xuất, để tệp xuất khớp đúng khoảng đang xem.');

                return;
            }

            window.location = "{{ route('bhyt.check-hein-card.export') }}?" + $.param(thamSoLoc());
        });

        var hen = null;
        $('#tim').on('keyup', function () {
            clearTimeout(hen);
            hen = setTimeout(function () { table.ajax.reload(); }, 400);
        });

        $('#check-hein-card-list tbody').on('click', '.nut-xem', function () {
            var d = table.row($(this).closest('tr')).data();
            var html = '';

            for (var i = 0; i < TRUONG_CHI_TIET.length; i++) {
                var khoa = TRUONG_CHI_TIET[i][0];
                var nhan = TRUONG_CHI_TIET[i][1];
                var gt = (d[khoa] === null || d[khoa] === undefined) ? '' : d[khoa];

                // .text() truoc roi .html(): du lieu nay den tu cong BHXH, khong duoc chen
                // thang vao DOM.
                html += '<tr><th style="width:34%">' + $('<div>').text(nhan).html() + '</th>'
                      + '<td>' + $('<div>').text(gt).html() + '</td></tr>';
            }

            $('#modal-the-body').html(html);
            $('#modal-the').modal('show');
        });

        $('#check-hein-card-list tbody').on('click', '.nut-tra-lai', function () {
            var nut = $(this);
            var d = table.row(nut.closest('tr')).data();

            nut.prop('disabled', true).text('Đang gửi...');

            $.post("{{ route('bhyt.check-hein-card.tra-lai') }}", {
                _token: '{{ csrf_token() }}', ma_lk: d.ma_lk
            }).done(function (r) {
                alert(r.message);
                // Doi job chay xong roi nap lai DUNG trang dang xem, giu bo loc (null, false).
                setTimeout(function () { table.ajax.reload(null, false); }, 5000);
            }).fail(function (x) {
                alert((x.responseJSON && x.responseJSON.message) || 'Không gửi được yêu cầu tra lại');
                nut.prop('disabled', false).text('Tra lại');
            });
        });
    });
</script>

@endpush
