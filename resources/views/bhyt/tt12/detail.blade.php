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
        @if ($hoSo->import_error)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                {{-- Tt12Importer CO Y giu lai ho so do dang kem ly do "de nguoi dung nhin
                     thay va xoa". Khong in ra day thi nguoi dung thay mot ho so 0 dong,
                     "Chua kiem", khong ky duoc, va khong mot chu giai thich. --}}
                <div class="alert alert-danger" style="margin-bottom:4px">
                    <strong>Lỗi nạp tệp:</strong> {{ $hoSo->import_error }}
                    <br><small>Hồ sơ này không sửa được trên màn hình: sửa tệp Excel rồi nạp lại, và xoá hồ sơ này.</small>
                </div>
            </div>
        </div>
        @endif
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
                @if (empty($hoSo->checked_at) && !$hoSo->is_signed && !\App\Services\Tt12\Tt12QuyetDinhGui::daTiepNhan($hoSo->ma_ket_qua))
                {{-- Hàng đợi tắt lúc nạp hoặc job hết lượt thử thì hồ sơ nằm mãi ở
                     "Chưa kiểm" - không ký được và không có đường nào kiểm lại. --}}
                <button type="button" id="btn-kiem-lai" class="btn btn-default btn-sm">
                    <i class="fa fa-check-square-o"></i> Kiểm lại
                </button>
                @endif
                @if (\App\Services\Tt12\Tt12QuyetDinhGui::daTiepNhan($hoSo->ma_ket_qua) && empty($hoSo->dong_bo_at))
                {{-- Cổng đã tiếp nhận mà chưa đồng bộ: bước đồng bộ đã hỏng giữa chừng và
                     lần thử lại của hàng đợi sẽ dừng sớm vì hồ sơ đã tiếp nhận. --}}
                <button type="button" id="btn-dong-bo-lai" class="btn btn-warning btn-sm">
                    <i class="fa fa-refresh"></i> Đồng bộ lại danh mục
                </button>
                @endif
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
    var mauUrlDongBoLai = "{{ route('bhyt.tt12.dong-bo-lai', ['ma_ho_so' => '__MA__']) }}";
    var mauUrlKiemLai = "{{ route('bhyt.tt12.kiem-lai', ['ma_ho_so' => '__MA__']) }}";
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

    // Hai nut cuu ho dung chung mot khuon goi: POST, bao thong diep, tai lai trang.
    function bamCuuHo($nut, mauUrl, hoi) {
        if (hoi && !confirm(hoi)) {
            return;
        }

        $nut.prop('disabled', true);

        $.post(ghepUrl(mauUrl), { _token: token })
            .done(function (kq) {
                alert((kq && kq.thong_diep) || '');
                location.reload();
            })
            .fail(function (xhr) {
                var kq = xhr.responseJSON;
                alert((kq && kq.thong_diep) || 'Không gọi được máy chủ. Thử lại sau.');
                $nut.prop('disabled', false);
            });
    }

    $('#btn-dong-bo-lai').on('click', function () {
        bamCuuHo($(this), mauUrlDongBoLai,
            'Ghi lại toàn bộ dòng của hồ sơ ' + maHoSo + ' sang bảng danh mục?');
    });

    $('#btn-kiem-lai').on('click', function () {
        bamCuuHo($(this), mauUrlKiemLai, null);
    });
});
</script>
@endpush
