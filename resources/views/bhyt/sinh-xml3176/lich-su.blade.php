@extends('adminlte::page')

@section('title', 'Lịch sử sinh XML3176')

@section('content_header')
<h1>Lịch sử sinh XML3176 <small>hồ sơ {{ $treatmentCode }}</small></h1>
{{ Breadcrumbs::render('bhyt.sinh-xml3176.lich-su', $treatmentCode, $treatmentId) }}
@stop

@section('content')
@include('includes.message')

{{-- Bien vao: $treatmentId, $treatmentCode, $hang[] (stt, dong LanSinh, tinh_trang, thu_muc_loai, url_xem, url_so_sanh). --}}
<div class="alert alert-info">
    Mỗi dòng là một lần module ghi nhận hồ sơ này (cả hai loại bản, theo thứ tự sinh).
    Lần quét thấy nội dung không đổi (<code>khong_doi</code>) không có dòng riêng:
    hệ thống chỉ cập nhật mốc sửa của dòng cuối cùng nên không hiện ở đây.
    "Số lần sinh" ở danh sách chỉ đếm các dòng đã ghi tệp.
</div>

<div class="panel panel-default">
    <div class="panel-heading clearfix">
        <strong>Hồ sơ {{ $treatmentCode }}</strong>
        <a href="{{ route('bhyt.sinh-xml3176.index') }}" class="btn btn-default btn-xs pull-right">
            <i class="fa fa-arrow-left"></i> Về danh sách
        </a>
    </div>
    <div class="panel-body table-responsive">
        <table class="table table-hover table-bordered" id="ssx-lich-su">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Giờ</th>
                    <th>Loại bản</th>
                    <th>Trạng thái</th>
                    <th>Lý do</th>
                    <th>Các bảng</th>
                    <th>Thư mục</th>
                    <th>XML lưu trữ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($hang as $h)
                    @php($d = $h['dong'])
                    <tr data-id="{{ $d->id }}">
                        <td>{{ $h['stt'] }}</td>
                        <td>{{ $d->created_at ? $d->created_at->format('d/m/Y H:i:s') : '' }}</td>
                        <td>{{ $d->loai_ban }}</td>
                        <td>
                            @if ($d->trang_thai === 'loi')
                                <span class="text-danger"><strong>{{ $d->trang_thai }}</strong></span>
                            @else
                                {{ $d->trang_thai }}
                            @endif
                        </td>
                        <td>{{ $d->ly_do }}</td>
                        <td>{{ $d->cac_bang }}</td>
                        <td>
                            <span title="{{ $d->thu_muc }}">
                                {{ ['that' => 'Thật', 'thu' => 'Thử', 'khac' => 'Khác'][$h['thu_muc_loai']] }}
                            </span>
                        </td>
                        <td>
                            @if ($h['tinh_trang'] === 'co')
                                có
                            @elseif ($h['tinh_trang'] === 'da_xoa')
                                đã xoá lúc {{ $d->xoa_luu_luc }}{{ $d->xoa_luu_boi ? ' bởi người dùng #' . $d->xoa_luu_boi : '' }}
                            @elseif ($h['tinh_trang'] === 'mat_tep')
                                <span class="text-warning">tệp không còn trên đĩa</span>
                            @else
                                không có XML lưu trữ
                            @endif
                        </td>
                        <td>
                            @if ($h['url_xem'])
                                <a class="btn btn-xs btn-default" href="{{ $h['url_xem'] }}"><i class="fa fa-eye"></i> Xem XML</a>
                            @endif
                            @if ($h['url_so_sanh'])
                                <a class="btn btn-xs btn-default" href="{{ $h['url_so_sanh'] }}"><i class="fa fa-exchange"></i> So với lần trước</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop
