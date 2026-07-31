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

<div class="box box-primary">
  <div class="box-body">
    <div class="row">
      <div class="col-md-2"><label>Từ ngày</label>
        <input type="date" id="tu_ngay" class="form-control">
      </div>
      <div class="col-md-2"><label>Đến ngày</label>
        <input type="date" id="den_ngay" class="form-control">
      </div>
      <div class="col-md-2"><label>Trạng thái</label>
        <select id="trang_thai" class="form-control select2">
          <option value="">Tất cả</option>
          <option value="loi">Chỉ lỗi</option>
          <option value="hop_le">Chỉ hợp lệ</option>
        </select>
      </div>
      @include('partials.ma_cskcb', ['colClass' => 'col-md-3', 'formGroup' => false])
      <div class="col-md-3"><label>Tìm hồ sơ/thẻ/họ tên</label>
        <input type="text" id="tim" class="form-control" placeholder="mã hồ sơ, số thẻ, họ tên...">
      </div>
    </div>
    <div class="row" style="margin-top:10px">
      <div class="col-md-12">
        <a id="btn-xuat" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Xuất Excel</a>
      </div>
    </div>
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
          <th>Xem</th>
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
    var table = null;

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
            tu_ngay: $('#tu_ngay').val(),
            den_ngay: $('#den_ngay').val(),
            trang_thai: $('#trang_thai').val(),
            ma_cskcb: $('#ma_cskcb').val(),
            tim: $('#tim').val()
        };
    }

    function thamSo(d) {
        $.extend(d, thamSoLoc());
    }

    function fetchData() {
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
                { "data": "ma_the" },
                { "data": "ho_ten" },
                { "data": "ngay_sinh" },
                { "data": "ma_cskcb" },
                { "data": "nhan_tracuu" },
                { "data": "nhan_kiemtra" },
                { "data": "ghi_chu" },
                { "data": "updated_at" },
                { "data": "id", "orderable": false, "searchable": false, "render": function (d) {
                    return '<button type="button" class="btn btn-xs btn-default nut-xem" data-id="' + d + '">Xem</button>';
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

        fetchData();

        $('#tu_ngay, #den_ngay, #trang_thai, #ma_cskcb').on('change', function () {
            table.ajax.reload();
        });

        // Gui DUNG bo tham so ma DataTables dang dung: tep xuat ra bang thu dang hien tren man.
        $('#btn-xuat').on('click', function () {
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
    });
</script>
@endpush
