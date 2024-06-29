@extends('adminlte::page')

@section('title', __('insurance.backend.labels.list'))

@section('content_header')
<h1>
    Tra cứu thông tin
    <small>Thuốc - Thầu</small>
</h1>
{{ Breadcrumbs::render('insurance.medicine-search') }}
@stop

@section('content')

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="medicine-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã thuốc</th>
                    <th>Tên thuốc</th>
                    <th>Mã hoạt chất</th>
                    <th>Tên hoạt chất</th>
                    <th>Mã đường dùng</th>
                    <th>Tên đường dùng</th>
                    <th>Hàm lượng</th>
                    <th>Số đăng ký</th>
                    <th>Nhóm thuốc</th>
                    <th>ĐVT</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Hãng SX</th>
                    <th>Nước SX</th>
                    <th>Nhà thầu</th>
                    <th>Quyết định</th>
                    <th>Công bố</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@stop

@push('after-scripts')
<script>
$(document).ready( function () {
    $('#medicine-list').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            url: "{{ route('insurance.medicine-search.get-data') }}",
        },
        "columns": [
            { "data": "ma_thuoc", "name": "ma_thuoc" },
            { "data": "ten_thuoc", "name": "ten_thuoc" },
            { "data": "ma_hoat_chat", "name": "ma_hoat_chat" },
            { "data": "ten_hoat_chat", "name": "ten_hoat_chat" },
            { "data": "ma_duong_dung", "name": "ma_duong_dung" },
            { "data": "ten_duong_dung", "name": "ten_duong_dung" },
            { "data": "ham_luong", "name": "ham_luong" },
            { "data": "so_dang_ky", "name": "so_dang_ky" },
            { "data": "nhom_thuoc", "name": "nhom_thuoc" },
            { "data": "don_vi_tinh", "name": "don_vi_tinh" },
            { "data": "don_gia", "name": "don_gia" },
            { "data": "so_luong", "name": "so_luong" },
            { "data": "hang_san_xuat", "name": "hang_san_xuat" },
            { "data": "nuoc_san_xuat", "name": "nuoc_san_xuat" },
            { "data": "nha_thau", "name": "nha_thau" },
            { "data": "quyet_dinh", "name": "quyet_dinh" },
            { "data": "cong_bo", "name": "cong_bo" },
        ],
    });
});
</script>
@endpush