@extends('adminlte::page')

@section('title', 'Xem XML3176 đã lưu')

@section('content_header')
  <h1>
    Xem XML đã lưu
    <small>hồ sơ {{ $dong->treatment_code }}, lần sinh #{{ $dong->id }}</small>
  </h1>
{{ Breadcrumbs::render('bhyt.sinh-xml3176.xem', $dong->treatment_code, $dong->treatment_id, $dong->id) }}
@stop

@section('content')
@include('includes.message')

{{-- Bien vao: $dong (LanSinh), $tinhTrang (co|da_xoa|mat_tep|khong_co), $loiTep, $cacTab[], $urlTai, $urlLichSu. --}}
<div class="panel panel-default">
    <div class="panel-heading clearfix">
        <strong>{{ $dong->ten_tep }}</strong>
        <span class="text-muted">
            {{ $dong->created_at ? $dong->created_at->format('d/m/Y H:i:s') : '' }} - {{ \App\Services\SinhXml3176\NhanHienThi::loaiBan($dong->loai_ban) }} - {{ \App\Services\SinhXml3176\NhanHienThi::trangThai($dong->trang_thai) }}
        </span>
        <span class="pull-right">
            @if ($urlTai)
                <a href="{{ $urlTai }}" class="btn btn-primary btn-xs"><i class="fa fa-download"></i> Tải tệp gốc</a>
            @endif
            <a href="{{ $urlLichSu }}" class="btn btn-default btn-xs"><i class="fa fa-arrow-left"></i> Về lịch sử</a>
        </span>
    </div>
    <div class="panel-body">
        @if ($tinhTrang === 'da_xoa')
            <div class="alert alert-warning">XML lưu trữ đã xoá lúc {{ $dong->xoa_luu_luc }}.</div>
        @elseif ($tinhTrang === 'chua_cau_hinh')
            <div class="alert alert-warning">Chưa cấu hình thư mục lưu trữ (thu_muc_luu).</div>
        @elseif ($tinhTrang === 'mat_tep')
            <div class="alert alert-warning">Lưu trữ: tệp không còn trên đĩa. Dòng theo dõi vẫn giữ nguyên, không tự sửa.</div>
        @elseif ($tinhTrang === 'khong_co')
            <div class="alert alert-info">Lần sinh này không có XML lưu trữ.</div>
        @elseif ($loiTep)
            <div class="alert alert-danger">{{ $loiTep }}</div>
        @elseif (empty($cacTab))
            <div class="alert alert-info">Tệp lưu trữ không chứa bảng nào.</div>
        @else
            <ul class="nav nav-tabs" id="ssx-tab-bang">
                @foreach ($cacTab as $i => $tab)
                    <li class="{{ $i === 0 ? 'active' : '' }}">
                        <a href="#bang-{{ $tab['loai'] }}" data-toggle="tab"
                           class="{{ $tab['loi'] ? 'tab-loi text-danger' : '' }}">
                            {{ $tab['loai'] }}
                            @if ($tab['loi'])<i class="fa fa-exclamation-triangle"></i>@endif
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="tab-content" style="padding-top:10px;">
                @foreach ($cacTab as $i => $tab)
                    <div class="tab-pane {{ $i === 0 ? 'active' : '' }}" id="bang-{{ $tab['loai'] }}">
                        @include('bhyt.sinh-xml3176._bang', ['tab' => $tab])
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@stop
