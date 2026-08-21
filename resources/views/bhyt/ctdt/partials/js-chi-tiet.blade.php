{{-- Hanh vi cua man chi tiet ho so, dung chung cho TRANG RIENG va MODAL.

     UY NHIEM SU KIEN tren document: gan MOT LAN luc trang tai, va van chay voi than chi
     tiet duoc nap bang AJAX sau do. Do la ly do chon uy nhiem chu khong gan truc tiep.

     TEP NAY KHONG BIET no dang o trang rieng hay trong modal. Xong viec no chi PHAT SU KIEN:

       ctdt:da-xep-hang  - may chu da nhan lenh ky va gui
       ctdt:da-xoa       - ho so da bi xoa

     Chu nha tu quyet phan ung: trang rieng nap lai trang, man danh sach dong modal va nap
     lai rieng bang. KHONG truyen co $trongModal xuong day - mot tham so nhu the buoc moi
     hanh vi them sau nay phai re nhanh theo no, va so nhanh chi co tang.

     LUU Y: moi chi thi Blade nam trong chu thich JavaScript VAN duoc Blade dich. Trong chu
     thich JS, viet @@if neu can nhac toi chi thi. --}}
<script>
$(function () {
    var mauUrlTab = "{{ route('bhyt.ctdt.detail.tab', ['ma_ho_so' => '__MA__', 'loai' => '__LOAI__']) }}";
    var mauUrlGui = "{{ route('bhyt.ctdt.ky-va-gui', ['ma_ho_so' => '__MA__']) }}";
    var mauUrlXoa = "{{ route('bhyt.ctdt.delete', ['ma_ho_so' => '__MA__']) }}";
    var token = "{{ csrf_token() }}";

    /** Ma ho so doc tu DOM, khong nhung vao JS: luc gan handler chua biet ho so nao se mo. */
    function maHoSoCua(phanTu) {
        return $(phanTu).closest('.ctdt-chi-tiet').data('ma-ho-so');
    }

    function maGdCua(phanTu) {
        return $(phanTu).closest('.ctdt-chi-tiet').data('ma-gd');
    }

    // ma_ho_so co the chua dau '#' (nhanh lui GUID). Khong ma hoa thi trinh duyet cat tu
    // dau '#' va yeu cau tro sai ho so.
    function ghepUrl(mau, maHoSo) {
        return mau.replace('__MA__', encodeURIComponent(maHoSo));
    }

    function napTab(maHoSo, loai) {
        $('#noi-dung-tab').html('<p class="text-muted">Đang tải…</p>');

        $.get(ghepUrl(mauUrlTab, maHoSo).replace('__LOAI__', encodeURIComponent(loai)))
            .done(function (html) { $('#noi-dung-tab').html(html); })
            .fail(function () {
                $('#noi-dung-tab').html('<p class="text-danger">Không tải được nội dung tab.</p>');
            });
    }

    $(document).on('click', '#ctdt-tabs a', function (e) {
        e.preventDefault();
        $('#ctdt-tabs li').removeClass('active');
        $(this).closest('li').addClass('active');
        napTab(maHoSoCua(this), $(this).data('loai'));
    });

    /**
     * Nap tab dau tien. Chu nha goi ham nay sau khi da do than vao DOM - tren trang rieng
     * la ngay luc tai, trong modal la sau khi AJAX ve.
     */
    window.ctdtNapTabDau = function () {
        var dau = $('#ctdt-tabs a').first();

        if (dau.length) {
            napTab(maHoSoCua(dau), dau.data('loai'));
        }
    };

    $(document).on('click', '#btn-ky-va-gui', function () {
        var nut = $(this);
        var maHoSo = maHoSoCua(this);
        var maGd = maGdCua(this);

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

            var url = ghepUrl(mauUrlGui, maHoSo);

            // xacNhanGuiLai = 1 o lan goi THU HAI. May chu tra can_xac_nhan khi ho so da
            // tung duoc gui len cong nhung dau vet (ma_gd) da bi mot lan nap lai xoa.
            function gui(xacNhanGuiLai) {
                nut.prop('disabled', true);

                var duLieu = { _token: token };

                if (xacNhanGuiLai) {
                    duLieu.xac_nhan_gui_lai = 1;
                }

                $.post(url, duLieu)
                    .done(function (data) {
                        // Hop xac nhan THU HAI: chi hien khi may chu doi xac nhan va lan goi
                        // nay chua mang co. Khong kiem xacNhanGuiLai thi mot phan hoi
                        // can_xac_nhan lap lai se sinh vong hoi vo tan.
                        if (data.can_xac_nhan && !xacNhanGuiLai) {
                            Swal.fire({
                                title: 'Hồ sơ đã từng được gửi lên cổng',
                                text: data.thong_diep,
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Vẫn gửi lại',
                                cancelButtonText: 'Hủy'
                            }).then(function (kq2) {
                                if (kq2.value) {
                                    gui(true);
                                }
                            });

                            return;
                        }

                        Swal.fire({
                            title: data.thanh_cong ? 'Đã xếp hàng' : 'Chưa gửi được',
                            text: data.thong_diep,
                            icon: data.thanh_cong ? 'success' : 'warning'
                        }).then(function () {
                            if (data.thanh_cong) {
                                $(document).trigger('ctdt:da-xep-hang', [maHoSo]);
                            }
                        });
                    })
                    .fail(function () {
                        Swal.fire('Lỗi', 'Không gọi được máy chủ. Thử lại sau.', 'error');
                    })
                    .always(function () {
                        nut.prop('disabled', false);
                    });
            }

            gui(false);
        });
    });

    // Nut "Xoa ho so" chi hien voi superadministrator, nhung van hoi lai truoc khi xoa:
    // xoa nham mot ho so co MaGD la mat dau vet doi soat voi BHXH.
    $(document).on('click', '#btn-xoa-ho-so', function () {
        var maHoSo = maHoSoCua(this);
        var maGd = maGdCua(this);

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
            confirmButtonColor: '#d33'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: ghepUrl(mauUrlXoa, maHoSo),
                type: 'DELETE',
                data: { _token: token },
                success: function () {
                    $(document).trigger('ctdt:da-xoa', [maHoSo]);
                },
                error: function () {
                    Swal.fire('Có lỗi xảy ra', 'Không xóa được hồ sơ. Vui lòng thử lại.', 'error');
                }
            });
        });
    });
});
</script>
