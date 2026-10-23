@extends('adminlte::page')

@section('title', 'So hai lần sinh XML3176')

@section('content_header')
  <h1>
    So hai lần sinh
    <small>hồ sơ {{ $lanA->treatment_code }}</small>
  </h1>
{{ Breadcrumbs::render('bhyt.sinh-xml3176.so-sanh', $lanA->treatment_code, $lanA->treatment_id) }}
@stop

@section('content')
@include('includes.message')

{{-- Bien vao: $lanA, $lanB, $khacLoai, $tinhTrangA/B, $loiTepA/B, $ketQua[loai => loi_a, loi_b, chi_a, chi_b, nhom], $urlLichSu. --}}
@php
    $chuTinhTrang = [
        'da_xoa'   => 'XML lưu trữ đã xoá',
        'mat_tep'  => 'tệp không còn trên đĩa',
        'chua_cau_hinh' => 'chưa cấu hình thư mục lưu trữ (thu_muc_luu)',
        'khong_co' => 'không có XML lưu trữ',
    ];
@endphp
<div class="panel panel-default">
    <div class="panel-heading clearfix">
        <strong>A:</strong> #{{ $lanA->id }} {{ $lanA->created_at ? $lanA->created_at->format('d/m/Y H:i:s') : '' }} ({{ \App\Services\SinhXml3176\NhanHienThi::loaiBan($lanA->loai_ban) }})
        &nbsp;&rarr;&nbsp;
        <strong>B:</strong> #{{ $lanB->id }} {{ $lanB->created_at ? $lanB->created_at->format('d/m/Y H:i:s') : '' }} ({{ \App\Services\SinhXml3176\NhanHienThi::loaiBan($lanB->loai_ban) }})
        <a href="{{ $urlLichSu }}" class="btn btn-default btn-xs pull-right"><i class="fa fa-arrow-left"></i> Về lịch sử</a>
    </div>
    <div class="panel-body">
        @if ($khacLoai)
            <div class="alert alert-warning">Hai lần sinh khác loại bản ({{ \App\Services\SinhXml3176\NhanHienThi::loaiBan($lanA->loai_ban) }} và {{ \App\Services\SinhXml3176\NhanHienThi::loaiBan($lanB->loai_ban) }}): khác loại bản nên chênh lệch có thể là bình thường.</div>
        @endif

        @if ($tinhTrangA !== 'co' || $tinhTrangB !== 'co')
            <div class="alert alert-warning">
                Không so được:
                @if ($tinhTrangA !== 'co') lần A {{ $chuTinhTrang[$tinhTrangA] }}. @endif
                @if ($tinhTrangB !== 'co') lần B {{ $chuTinhTrang[$tinhTrangB] }}. @endif
            </div>
        @elseif ($loiTepA || $loiTepB)
            <div class="alert alert-danger">
                @if ($loiTepA) Lần A: {{ $loiTepA }} @endif
                @if ($loiTepB) Lần B: {{ $loiTepB }} @endif
            </div>
        @elseif (empty($ketQua))
            <div class="alert alert-info">Cả hai tệp không có bảng nào để so.</div>
        @else
            @foreach ($ketQua as $loai => $kq)
                <h4>{{ $loai }}</h4>
                @if ($kq['loi_a'] || $kq['loi_b'])
                    <div class="alert alert-danger">
                        @if ($kq['loi_a']) Lần A: {{ $kq['loi_a'] }} @endif
                        @if ($kq['loi_b']) Lần B: {{ $kq['loi_b'] }} @endif
                    </div>
                @elseif (empty($kq['nhom']))
                    <p class="text-success"><i class="fa fa-check"></i> giống nhau</p>
                @else
                    @if ($kq['chi_a'])
                        <p class="text-muted">Bảng này chỉ có ở lần A.</p>
                    @elseif ($kq['chi_b'])
                        <p class="text-muted">Bảng này chỉ có ở lần B.</p>
                    @endif
                    <table class="table table-bordered table-condensed">
                        <thead><tr><th>Khoá dòng</th><th>Kết quả</th><th>Trường</th><th>Lần A</th><th>Lần B</th></tr></thead>
                        <tbody>
                            @foreach ($kq['nhom'] as $khoa => $nhom)
                                @if ($nhom['kieu'] === 'chi_co_a')
                                    <tr class="danger"><td>{{ $khoa }}</td><td colspan="4">chỉ có ở lần A</td></tr>
                                @elseif ($nhom['kieu'] === 'chi_co_b')
                                    <tr class="success"><td>{{ $khoa }}</td><td colspan="4">chỉ có ở lần B</td></tr>
                                @else
                                    @foreach ($nhom['truong'] as $t)
                                        <tr class="warning">
                                            <td>{{ $khoa }}</td><td>khác giá trị</td>
                                            <td>{{ $t['ten'] }}</td><td>{{ $t['a'] }}</td><td>{{ $t['b'] }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endforeach
        @endif
    </div>
</div>
@stop
