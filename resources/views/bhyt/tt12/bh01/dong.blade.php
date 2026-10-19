@extends('adminlte::page')

@section('title', 'Dòng của bản ' . $ban->ma_ban)

@section('content_header')
<h1>Dòng của bản <small>{{ $ban->ma_ban }}</small></h1>
@stop

@section('content')
{{-- Bien vao: $ban (Bh01Ban), $cacDong (Paginator Bh01Dong, BHYTBh01Controller::DONG_MOI_TRANG dong/trang).
     Day la anh chup dong bang luc tao ban, khong phai XML1 hien tai. --}}
<div class="panel panel-default">
    <div class="panel-body">
        <p>
            <a href="{{ route('bhyt.tt12.bh01.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Quay lại</a>
            Cơ sở {{ $ban->ma_cskcb }} · kỳ {{ $ban->thang_qt }}/{{ $ban->nam_qt }} · bản {{ $ban->so_ban }}
            · {{ $cacDong->total() }} dòng · {{ (int) $ban->so_canh_bao }} dòng cảnh báo
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Mã LK</th>
                        <th>Họ tên</th>
                        <th>Ngày sinh</th>
                        <th>Giới</th>
                        <th>Mã thẻ BHYT</th>
                        <th>Mã bệnh</th>
                        <th>Ngày vào</th>
                        <th>Ngày ra</th>
                        <th>Loại KCB</th>
                        <th class="text-right">Tổng chi BV</th>
                        <th class="text-right">Tổng chi BH</th>
                        <th class="text-right">BHTT</th>
                        <th class="text-right">BN CCT</th>
                        <th class="text-right">BN tự trả</th>
                        <th class="text-right">Nguồn khác</th>
                        <th>Cảnh báo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cacDong as $d)
                        <tr>
                            <td>{{ $d->stt }}</td>
                            <td>{{ $d->ma_lk }}</td>
                            <td>{{ $d->ho_ten }}</td>
                            <td>{{ $d->ngay_sinh }}</td>
                            <td>{{ $d->gioi_tinh }}</td>
                            <td>{{ $d->ma_the_bhyt }}</td>
                            <td>{{ $d->ma_benh_chinh }}</td>
                            <td>{{ $d->ngay_vao }}</td>
                            <td>{{ $d->ngay_ra }}</td>
                            <td>{{ $d->ma_loai_kcb }}</td>
                            <td class="text-right">{{ $d->t_tongchi_bv }}</td>
                            <td class="text-right">{{ $d->t_tongchi_bh }}</td>
                            <td class="text-right">{{ $d->t_bhtt }}</td>
                            <td class="text-right">{{ $d->t_bncct }}</td>
                            <td class="text-right">{{ $d->t_bntt }}</td>
                            <td class="text-right">{{ $d->t_nguonkhac }}</td>
                            <td>@if ($d->canh_bao)<span style="color:#b94a48">{{ $d->canh_bao }}</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="17" class="text-center">Bản không có dòng nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $cacDong->links() }}
    </div>
</div>
@stop
