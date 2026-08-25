@extends('adminlte::page')

@section('title', e('Chi tiết hồ sơ ' . $hoSo->ma_ho_so))

@section('content_header')
<h1>Chi tiết hồ sơ <small>{{ $hoSo->ma_ho_so }}</small></h1>
@stop

@section('content')
@include('includes.message')

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-3"><strong>Mẫu:</strong> {{ $hoSo->mau }}</div>
            <div class="col-sm-3"><strong>Mã CSKCB:</strong> {{ $hoSo->ma_cskcb }}</div>
            <div class="col-sm-3"><strong>Tên tệp:</strong> {{ $hoSo->ten_tep }}</div>
            <div class="col-sm-3"><strong>Số dòng:</strong> {{ $hoSo->so_dong }}</div>
        </div>
        <div class="row" style="margin-top:8px">
            <div class="col-sm-3"><strong>Số lỗi:</strong>
                {{ empty($hoSo->checked_at) ? 'Chưa kiểm' : $hoSo->so_loi }}
            </div>
            <div class="col-sm-3"><strong>Ký số:</strong> {{ $hoSo->is_signed ? 'Đã ký' : 'Chưa ký' }}</div>
            <div class="col-sm-3"><strong>Mã giao dịch:</strong> {{ $hoSo->ma_gd ?: '—' }}</div>
            <div class="col-sm-3"><strong>Mã kết quả:</strong> {{ $hoSo->ma_ket_qua ?: '—' }}</div>
        </div>
        <div class="row" style="margin-top:8px">
            <div class="col-sm-3"><strong>Tiếp nhận lúc:</strong> {{ $hoSo->thoi_gian_tiep_nhan ?: '—' }}</div>
            <div class="col-sm-3"><strong>Nạp lúc:</strong> {{ $hoSo->imported_at }}</div>
        </div>
        @if ($hoSo->signed_error)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                {{-- signed_error chua thong diep tu dich vu ky - du lieu ben ngoai. --}}
                <div class="alert alert-danger" style="margin-bottom:4px">
                    <strong>Lỗi ký số:</strong> {{ $hoSo->signed_error }}
                </div>
            </div>
        </div>
        @endif
        @if ($hoSo->submit_error)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                <div class="alert alert-warning" style="margin-bottom:4px">
                    <strong>Lỗi gửi:</strong> {{ $hoSo->submit_error }}
                </div>
            </div>
        </div>
        @endif
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                <button type="button" id="btn-ky-va-gui" class="btn btn-primary btn-sm"
                        data-ma-ho-so="{{ $hoSo->ma_ho_so }}">
                    <i class="fa fa-paper-plane"></i> Ký và gửi
                </button>
                <a href="{{ route('bhyt.tt12.index') }}" class="btn btn-default btn-sm">Quay lại danh sách</a>
            </div>
        </div>
    </div>
</div>

<div class="nav-tabs-custom" id="tt12-chi-tiet" data-ma-ho-so="{{ $hoSo->ma_ho_so }}">
    <ul class="nav nav-tabs" id="tt12-tabs">
        @foreach ($cacTab as $ma => $nhan)
        <li class="{{ $loop->first ? 'active' : '' }}">
            <a href="#" data-tab="{{ $ma }}">{{ $nhan }}</a>
        </li>
        @endforeach
    </ul>
    <div class="tab-content">
        <div id="noi-dung-tab" style="padding:15px"><p class="text-muted">Đang tải…</p></div>
    </div>
</div>
@stop

@push('after-scripts')
<script>
$(function () {
    var mauUrlTab = "{{ route('bhyt.tt12.detail.tab', ['ma_ho_so' => '__MA__', 'tab' => '__TAB__']) }}";
    var mauUrlGui = "{{ route('bhyt.tt12.ky-va-gui', ['ma_ho_so' => '__MA__']) }}";
    var maHoSo = $('#tt12-chi-tiet').data('ma-ho-so');
    var token = "{{ csrf_token() }}";

    function ghepUrl(mau) {
        return mau.replace('__MA__', encodeURIComponent(maHoSo));
    }

    function napTab(tab) {
        $('#noi-dung-tab').html('<p class="text-muted">Đang tải…</p>');

        $.get(ghepUrl(mauUrlTab).replace('__TAB__', encodeURIComponent(tab)))
            .done(function (html) { $('#noi-dung-tab').html(html); })
            .fail(function () {
                $('#noi-dung-tab').html('<p class="text-danger">Không tải được nội dung tab.</p>');
            });
    }

    $(document).on('click', '#tt12-tabs a', function (e) {
        e.preventDefault();
        $('#tt12-tabs li').removeClass('active');
        $(this).closest('li').addClass('active');
        napTab($(this).data('tab'));
    });

    napTab($('#tt12-tabs a').first().data('tab'));

    $('#btn-ky-va-gui').on('click', function () {
        if (!confirm('Ký số và gửi hồ sơ ' + maHoSo + ' lên cổng BHXH?\n\n'
                     + 'Cổng nhận là nhận thật, việc này không hoàn tác được.')) {
            return;
        }

        var $nut = $(this).prop('disabled', true);

        $.post(ghepUrl(mauUrlGui), { _token: token })
            .done(function (kq) {
                alert(kq.thong_diep || '');
                location.reload();
            })
            .fail(function (xhr) {
                var kq = xhr.responseJSON;
                alert((kq && kq.thong_diep) || 'Không gọi được máy chủ. Thử lại sau.');
            })
            .always(function () {
                $nut.prop('disabled', false);
            });
    });
});
</script>
@endpush
