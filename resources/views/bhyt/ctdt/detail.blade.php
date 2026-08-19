@extends('adminlte::page')

@section('title', 'Chi tiết hồ sơ ' . $hoSo->ma_ho_so)

@section('content_header')
<h1>Chi tiết hồ sơ <small>{{ $hoSo->ma_ho_so }}</small></h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-3"><strong>Dịch vụ:</strong>
                {{ array_get(config('ctdt.dich_vu'), $hoSo->dich_vu . '.ten', $hoSo->dich_vu) }}
            </div>
            <div class="col-sm-2"><strong>Mã CSKCB:</strong> {{ $hoSo->macskcb }}</div>
            <div class="col-sm-2"><strong>Số chứng từ:</strong> {{ $hoSo->so_chung_tu }}</div>
            <div class="col-sm-2"><strong>Số lỗi:</strong> {{ $hoSo->so_loi }}</div>
            <div class="col-sm-3"><strong>Nạp lúc:</strong> {{ $hoSo->imported_at }}</div>
        </div>
        <div class="row" style="margin-top:8px">
            <div class="col-sm-3"><strong>Ký số:</strong> {{ $hoSo->is_signed ? 'Đã ký' : 'Chưa ký' }}</div>
            <div class="col-sm-3"><strong>MaGD:</strong> {{ $hoSo->ma_gd ?: '—' }}</div>
            <div class="col-sm-3"><strong>Mã kết quả:</strong> {{ $hoSo->ma_ket_qua ?: '—' }}</div>
            <div class="col-sm-3"><strong>Tiếp nhận:</strong> {{ $hoSo->thoi_gian_tiep_nhan ?: '—' }}</div>
        </div>
        @if ($hoSo->lich_su_gui)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                <strong>Lịch sử gửi:</strong>
                <pre style="white-space:pre-wrap">{{ $hoSo->lich_su_gui }}</pre>
            </div>
        </div>
        @endif
        @if (auth()->check() && auth()->user()->hasRole('superadministrator'))
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12 text-right">
                <button type="button" id="btn-xoa-ho-so" class="btn btn-danger btn-sm">
                    <i class="fa fa-trash"></i> Xóa hồ sơ
                </button>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="nav-tabs-custom">
    <ul class="nav nav-tabs" id="ctdt-tabs">
        @foreach ($tabs as $i => $tab)
        <li class="{{ $i === 0 ? 'active' : '' }}">
            <a href="#" data-loai="{{ $tab['ma'] }}">
                {{ $tab['nhan'] }}
                @if ($tab['so_luong'] > 1)<span class="badge">{{ $tab['so_luong'] }}</span>@endif
            </a>
        </li>
        @endforeach
    </ul>
    <div class="tab-content">
        <div id="noi-dung-tab"><p class="text-muted">Đang tải…</p></div>
    </div>
</div>
@stop

@push('after-scripts')
<script>
$(function () {
    // ma_ho_so co the chua dau '#' (nhanh lui GUID). Khong ma hoa thi trinh duyet cat tu
    // dau '#' va yeu cau tro sai ho so.
    var goc = "{{ route('bhyt.ctdt.detail.tab', ['ma_ho_so' => '__MA__', 'loai' => '__LOAI__']) }}"
              .replace('__MA__', encodeURIComponent(@json($hoSo->ma_ho_so)));

    function napTab(loai) {
        $('#noi-dung-tab').html('<p class="text-muted">Đang tải…</p>');

        $.get(goc.replace('__LOAI__', encodeURIComponent(loai)))
            .done(function (html) { $('#noi-dung-tab').html(html); })
            .fail(function () {
                $('#noi-dung-tab').html('<p class="text-danger">Không tải được nội dung tab.</p>');
            });
    }

    $('#ctdt-tabs a').on('click', function (e) {
        e.preventDefault();
        $('#ctdt-tabs li').removeClass('active');
        $(this).closest('li').addClass('active');
        napTab($(this).data('loai'));
    });

    var dau = $('#ctdt-tabs a').first();

    if (dau.length) {
        napTab(dau.data('loai'));
    }

    // Nut "Xoa ho so": chi hien voi superadministrator (Blade @if o tren), nhung van hoi
    // lai truoc khi xoa - dinh xoa nham mot ho so co MaGD la mat dau vet doi soat voi BHXH.
    $('#btn-xoa-ho-so').on('click', function () {
        var maHoSo = @json($hoSo->ma_ho_so);
        var maGd = @json($hoSo->ma_gd);

        // Swal.fire 'text' hien thi nhu van ban thuan (khong dien giai HTML), nen maHoSo/
        // maGd - von la du lieu tu XML/phan hoi BHXH ben ngoai - khong the bien thanh the
        // HTML du khong tu tay thoat them.
        var noiDung = 'Xóa hồ sơ ' + maHoSo + '? ' +
            (maGd ? 'Nếu hồ sơ đã gửi lên cổng BHXH thì dấu vết đối soát (MaGD ' + maGd + ') cũng mất theo. ' : '') +
            'Việc này không hoàn tác được.';

        Swal.fire({
            title: 'Xác nhận xóa hồ sơ',
            text: noiDung,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#d33',
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            // ma_ho_so co the chua dau '#' - phai ma hoa truoc khi ghep vao URL, giong
            // cach lam voi tab o tren.
            var url = "{{ route('bhyt.ctdt.delete', ['ma_ho_so' => '__MA__']) }}"
                      .replace('__MA__', encodeURIComponent(maHoSo));

            $.ajax({
                url: url,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function () {
                    window.location.href = "{{ route('bhyt.ctdt.index') }}";
                },
                error: function () {
                    Swal.fire('Có lỗi xảy ra', 'Không xóa được hồ sơ. Vui lòng thử lại.', 'error');
                },
            });
        });
    });
});
</script>
@endpush
