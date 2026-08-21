@extends('adminlte::page')

@section('title', e('Chi tiết hồ sơ ' . $hoSo->ma_ho_so))

@section('content_header')
<h1>Chi tiết hồ sơ <small>{{ $hoSo->ma_ho_so }}</small></h1>
@stop

@section('content')
@include('includes.message')
@include('bhyt.ctdt.partials.than-chi-tiet', ['hoSo' => $hoSo, 'tabs' => $tabs])
@stop

@push('after-scripts')
@include('bhyt.ctdt.partials.js-chi-tiet')
<script>
$(function () {
    // THU TU QUAN TRONG: @@include o tren dat window.ctdtNapTabDau ben trong mot $(function)
    // cua rieng no. jQuery chay cac ham san sang theo DUNG thu tu dang ky, va @@include dung
    // truoc khoi nay, nen toi day ham da ton tai. Dao hai khoi la goi mot ham chua co.
    window.ctdtNapTabDau();

    // Trang rieng tu quyet dieu huong - xem chu thich trong js-chi-tiet ve vi sao JS dung
    // chung khong tu lam viec nay.
    $(document).on('ctdt:da-xep-hang', function () {
        location.reload();
    });

    $(document).on('ctdt:da-xoa', function () {
        window.location.href = "{{ route('bhyt.ctdt.index') }}";
    });
});
</script>
@endpush
