@extends('adminlte::page')

@section('title', e('Chi tiết hồ sơ ' . $hoSo->ma_ho_so))

@section('content_header')
<h1>Chi tiết hồ sơ <small>{{ $hoSo->ma_ho_so }}</small></h1>
@stop

@section('content')
@include('includes.message')
@include('bhyt.tt12.partials.than-chi-tiet', ['hoSo' => $hoSo, 'cacTab' => $cacTab])

{{-- "Quay lai danh sach" nam o TRANG chu khong o than: trong modal thi nut nay vo nghia
     (dong modal la da ve danh sach), va mot nut dieu huong trong than se buoc than phai
     biet no dang o dau - dung cai ma viec tach partial nay tranh. --}}
<a href="{{ route('bhyt.tt12.index') }}" class="btn btn-default btn-sm">Quay lại danh sách</a>
@stop

@push('after-scripts')
@include('bhyt.tt12.partials.js-chi-tiet')
<script>
$(function () {
    // THU TU QUAN TRONG: @@include o tren dat window.tt12NapTabDau ben trong mot $(function)
    // cua rieng no. jQuery chay cac ham san sang theo DUNG thu tu dang ky, va @@include dung
    // truoc khoi nay, nen toi day ham da ton tai. Dao hai khoi la goi mot ham chua co.
    window.tt12NapTabDau();

    // Trang rieng tu quyet dieu huong - xem chu thich trong js-chi-tiet ve vi sao JS dung
    // chung khong tu lam viec nay.
    $(document).on('tt12:da-xep-hang tt12:da-cuu-ho', function () {
        location.reload();
    });
});
</script>
@endpush
