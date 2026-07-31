@extends('adminlte::page')

@section('title', 'Danh mục Khoa phòng giường BHYT')

@section('content_header')
  <h1>
    Danh mục
    <small>Khoa phòng giường BHYT</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="box box-primary">
  <div class="box-body">
    <div class="row">
      {{-- Khuon giong bo loc man order-check: box box-primary + select2.
           Chon mot co so se hien danh muc CO HIEU LUC cho co so do, tuc la dong cua
           co so do LAN dong dung chung. --}}
      @include('partials.ma_cskcb', ['colClass' => 'col-md-3', 'formGroup' => false])
    </div>
  </div>
</div>

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="department-bed-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã khoa</th>
                    <th>Tên khoa</th>
                    <th>Mã loại KCB</th>
                    <th>Bàn khám</th>
                    <th>Giường PD</th>
                    <th>Giường 2015</th>
                    <th>Giường TK</th>
                    <th>Giường HSTC</th>
                    <th>Giường HSCC</th>
                    <th>Từ ngày</th>
                    <th>Đến ngày</th>
                    <th>MA_CSKCB</th>
                    <th>Xem</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('category.bhyt._chi_tiet')

@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại
    var table = null;

    function fetchData() {
        // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        table = $('#department-bed-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('category-bhyt.fetch-department-bed-catalog') }}",
                data: function (d) { d.ma_cskcb = $("#ma_cskcb").val(); },
                beforeSend: function(xhr) {
                    currentAjaxRequest = xhr;
                },
                complete: function(xhr, status) {
                    currentAjaxRequest = null;
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            },
            "columns": [
                { "data": "ma_khoa" },
                { "data": "ten_khoa" },
                { "data": "ma_loai_kcb" },
                { "data": "ban_kham" },
                { "data": "giuong_pd" },
                { "data": "giuong_2015" },
                { "data": "giuong_tk" },
                { "data": "giuong_hstc" },
                { "data": "giuong_hscc" },
                { "data": "tu_ngay" },
                { "data": "den_ngay" },
                // O trong nghia la dong danh muc dung chung cho MOI co so, khong phai thieu
                // du lieu - hien chu de nguoi doc khong hieu nham.
                { "data": "ma_cskcb", "render": function (d) { return d ? d : 'Dùng chung'; } },
                { "data": "id", "orderable": false, "searchable": false, "render": function (d) {
                    return '<button type="button" class="btn btn-xs btn-default nut-chi-tiet" data-loai="department_bed" data-id="' + d + '">Xem</button>';
                } },
            ],
        });

        table.ajax.reload();
    }

    $(document).ready(function() {
        fetchData();

        // Thieu loi goi nay thi partial chi la mot <select> tho, khong ra select2.
        $('.select2').select2({width: '100%'});

        $('#ma_cskcb').on('change', function () { table.ajax.reload(); });
    });
</script>
@endpush