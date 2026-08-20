{{-- Nut "Ky va gui" tren man chi tiet.

     Bien vao: $hoSo — ban ghi CtdtHoSo

     LUU Y: tep nay chua khoi <script>. Moi chi thi Blade nam trong chu thich JavaScript
     VAN duoc Blade dich va sinh ra PHP hong - dung lop loi da lam man chi tiet khong render
     duoc suot nhieu ngay. Trong chu thich JS, viet @@if neu can nhac toi chi thi. --}}
<div class="row" style="margin-top:8px">
    <div class="col-sm-12 text-right">
        <button type="button" id="btn-ky-va-gui" class="btn btn-primary btn-sm">
            <i class="fa fa-paper-plane"></i>
            {{ $hoSo->ma_gd ? 'Ký và gửi lại' : 'Ký và gửi' }}
        </button>
    </div>
</div>

@push('after-scripts')
<script>
$(function () {
    $('#btn-ky-va-gui').on('click', function () {
        var maHoSo = @json($hoSo->ma_ho_so);
        var maGd = @json($hoSo->ma_gd);

        // Swal.fire 'text' hien thi nhu van ban thuan (khong dien giai HTML), nen maHoSo va
        // maGd - von la du lieu tu XML va tu phan hoi cong - khong the bien thanh the HTML.
        var noiDung = 'Ký số và gửi hồ sơ ' + maHoSo + ' lên cổng BHXH? ' +
            (maGd ? 'Hồ sơ này đã gửi (MaGD ' + maGd + '); gửi lại sẽ ghi đè kết quả cũ. ' : '') +
            'Cổng nhận là nhận thật, việc này không hoàn tác được.';

        Swal.fire({
            title: 'Xác nhận gửi',
            text: noiDung,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ký và gửi',
            cancelButtonText: 'Hủy'
        }).then(function (kq) {
            if (!kq.value) {
                return;
            }

            var nut = $('#btn-ky-va-gui');
            nut.prop('disabled', true);

            // ma_ho_so co the chua dau '#' (nhanh lui GUID). Khong ma hoa thi trinh duyet
            // cat tu dau '#' va yeu cau tro sai ho so.
            var url = "{{ route('bhyt.ctdt.ky-va-gui', ['ma_ho_so' => '__MA__']) }}"
                      .replace('__MA__', encodeURIComponent(maHoSo));

            $.post(url, { _token: "{{ csrf_token() }}" })
                .done(function (data) {
                    Swal.fire({
                        title: data.thanh_cong ? 'Đã xếp hàng' : 'Chưa gửi được',
                        text: data.thong_diep,
                        icon: data.thanh_cong ? 'success' : 'warning'
                    }).then(function () {
                        if (data.thanh_cong) {
                            location.reload();
                        }
                    });
                })
                .fail(function () {
                    Swal.fire('Lỗi', 'Không gọi được máy chủ. Thử lại sau.', 'error');
                })
                .always(function () {
                    nut.prop('disabled', false);
                });
        });
    });
});
</script>
@endpush
