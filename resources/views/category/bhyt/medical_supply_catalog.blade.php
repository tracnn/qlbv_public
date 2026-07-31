@extends('adminlte::page')

@section('title', 'Danh mục Vật tư y tế BHYT')

@section('content_header')
  <h1>
    Danh mục
    <small>Vật tư y tế BHYT</small>
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
        <table id="medical-supply-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã vật tư</th>
                    <th>Tên vật tư</th>
                    <th>Nhóm vật tư</th>
                    <th>Đơn vị tính</th>
                    <th>Đơn giá</th>
                    <th>Nhà thầu</th>
                    <th>TT Thầu</th>
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

        table = $('#medical-supply-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('category-bhyt.fetch-medical-supply-catalog') }}",
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
                { "data": "ma_vat_tu" },
                { "data": "ten_vat_tu" },
                { "data": "nhom_vat_tu" },
                { "data": "don_vi_tinh" },
                { "data": "don_gia" },
                { "data": "nha_thau" },
                { "data": "tt_thau" },
                { "data": "tu_ngay" },
                { "data": "den_ngay" },
                { "data": "ma_cskcb", "render": function (d) { return d ? d : 'Dùng chung'; } },
                { "data": "id", "orderable": false, "searchable": false, "render": function (d) {
                    return '<button type="button" class="btn btn-xs btn-default nut-chi-tiet" data-loai="medical_supply" data-id="' + d + '">Xem</button>';
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