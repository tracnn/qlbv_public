{{-- Hanh vi cua man chi tiet ho so TT12, dung chung cho TRANG RIENG va MODAL.

     UY NHIEM SU KIEN tren document: gan MOT LAN luc trang tai, va van chay voi than chi
     tiet duoc nap bang AJAX sau do. Do la ly do chon uy nhiem chu khong gan truc tiep -
     gan truc tiep chi thay nhung phan tu DA CO trong DOM luc chay, nen nut trong modal se
     khong co handler nao va bam vao khong co gi xay ra.

     TEP NAY KHONG BIET no dang o trang rieng hay trong modal. Xong viec no chi PHAT SU KIEN:

       tt12:da-xep-hang  - may chu da nhan lenh ky va gui
       tt12:da-cuu-ho    - da kiem lai hoac da dong bo lai danh muc

     Chu nha tu quyet phan ung: trang rieng nap lai trang, man danh sach dong modal va nap
     lai rieng bang. KHONG truyen co $trongModal xuong day - mot tham so nhu the buoc moi
     hanh vi them sau nay phai re nhanh theo no, va so nhanh chi co tang.

     HAI nut cuu ho phat CUNG MOT su kien vi phan ung cua ca hai chu nha la nhu nhau. Tach
     doi chi tao hai nhanh phai giu dong bo mai mai ma khong ai duoc loi.

     LUU Y: moi chi thi Blade nam trong chu thich JavaScript VAN duoc Blade dich. Trong chu
     thich JS, viet @@if neu can nhac toi chi thi. --}}
<script>
$(function () {
    var mauUrlTab = "{{ route('bhyt.tt12.detail.tab', ['ma_ho_so' => '__MA__', 'tab' => '__TAB__']) }}";
    var mauUrlGui = "{{ route('bhyt.tt12.ky-va-gui', ['ma_ho_so' => '__MA__']) }}";
    var mauUrlDongBoLai = "{{ route('bhyt.tt12.dong-bo-lai', ['ma_ho_so' => '__MA__']) }}";
    var mauUrlKiemLai = "{{ route('bhyt.tt12.kiem-lai', ['ma_ho_so' => '__MA__']) }}";
    var token = "{{ csrf_token() }}";

    /** Ma ho so doc tu DOM, khong nhung vao JS: luc gan handler chua biet ho so nao se mo. */
    function maHoSoCua(phanTu) {
        return $(phanTu).closest('.tt12-chi-tiet').data('ma-ho-so');
    }

    // Ma ho so do Tt12MaHoSo sinh nen khong chua ky tu la, nhung van ma hoa: neu sau nay co
    // nhanh lui dat ten khac di thi cho nay khong phai la cho phat hien ra.
    function ghepUrl(mau, maHoSo) {
        return mau.replace('__MA__', encodeURIComponent(maHoSo));
    }

    function napTab(maHoSo, tab) {
        $('#noi-dung-tab').html('<p class="text-muted">Đang tải…</p>');

        $.get(ghepUrl(mauUrlTab, maHoSo).replace('__TAB__', encodeURIComponent(tab)))
            .done(function (html) { $('#noi-dung-tab').html(html); })
            .fail(function () {
                $('#noi-dung-tab').html('<p class="text-danger">Không tải được nội dung tab.</p>');
            });
    }

    $(document).on('click', '#tt12-tabs a', function (e) {
        e.preventDefault();
        $('#tt12-tabs li').removeClass('active');
        $(this).closest('li').addClass('active');
        napTab(maHoSoCua(this), $(this).data('tab'));
    });

    /**
     * Nap tab dau tien. Chu nha goi ham nay sau khi da do than vao DOM - tren trang rieng
     * la ngay luc tai, trong modal la sau khi AJAX ve.
     */
    window.tt12NapTabDau = function () {
        var dau = $('#tt12-tabs a').first();

        if (dau.length) {
            napTab(maHoSoCua(dau), dau.data('tab'));
        }
    };

    $(document).on('click', '#btn-ky-va-gui', function () {
        var $nut = $(this);
        var maHoSo = maHoSoCua(this);

        if (!confirm('Ký số và gửi hồ sơ ' + maHoSo + ' lên cổng BHXH?\n\n'
                     + 'Cổng nhận là nhận thật, việc này không hoàn tác được.')) {
            return;
        }

        $nut.prop('disabled', true);

        $.post(ghepUrl(mauUrlGui, maHoSo), { _token: token })
            .done(function (kq) {
                alert((kq && kq.thong_diep) || '');
                $(document).trigger('tt12:da-xep-hang', [maHoSo]);
            })
            .fail(function (xhr) {
                var kq = xhr.responseJSON;
                alert((kq && kq.thong_diep) || 'Không gọi được máy chủ. Thử lại sau.');
            })
            .always(function () {
                $nut.prop('disabled', false);
            });
    });

    /**
     * Khuon goi chung cua hai nut cuu ho: POST, bao thong diep, phat tt12:da-cuu-ho.
     *
     * Chi phat su kien khi may chu tra ve THANH CONG. Phat ca khi that bai thi man danh
     * sach dong modal va nap lai bang nhu the vua xong viec, trong khi ho so khong doi gi -
     * nguoi dung mat luon thong diep loi vua hien.
     */
    function bamCuuHo($nut, mauUrl, maHoSo, hoi) {
        if (hoi && !confirm(hoi)) {
            return;
        }

        $nut.prop('disabled', true);

        $.post(ghepUrl(mauUrl, maHoSo), { _token: token })
            .done(function (kq) {
                alert((kq && kq.thong_diep) || '');
                $(document).trigger('tt12:da-cuu-ho', [maHoSo]);
            })
            .fail(function (xhr) {
                var kq = xhr.responseJSON;
                alert((kq && kq.thong_diep) || 'Không gọi được máy chủ. Thử lại sau.');
                $nut.prop('disabled', false);
            });
    }

    $(document).on('click', '#btn-dong-bo-lai', function () {
        var maHoSo = maHoSoCua(this);

        bamCuuHo($(this), mauUrlDongBoLai, maHoSo,
            'Ghi lại toàn bộ dòng của hồ sơ ' + maHoSo + ' sang bảng danh mục?');
    });

    $(document).on('click', '#btn-kiem-lai', function () {
        bamCuuHo($(this), mauUrlKiemLai, maHoSoCua(this), null);
    });
});
</script>
