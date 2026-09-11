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
    var mauUrlSuaXml = "{{ route('bhyt.ctdt.sua-xml', ['ma_ho_so' => '__MA__', 'chung_tu_id' => '__CTID__']) }}";
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
                    // Bao thanh cong TRUOC khi phat su kien. Tren trang rieng nguoi dung con
                    // thay minh bi chuyen trang; trong modal thi modal dong va bang nap lai,
                    // khong co dau hieu nao noi "da xoa". Xoa la thao tac khong hoan tac -
                    // phai co xac nhan da xong.
                    Swal.fire({
                        title: 'Đã xóa',
                        text: 'Hồ sơ ' + maHoSo + ' đã được xóa.',
                        icon: 'success'
                    }).then(function () {
                        $(document).trigger('ctdt:da-xoa', [maHoSo]);
                    });
                },
                error: function () {
                    Swal.fire('Có lỗi xảy ra', 'Không xóa được hồ sơ. Vui lòng thử lại.', 'error');
                }
            });
        });
    });

    // ----------------------------------------------------------------- sua XML goc
    //
    // Cac nut nay CHI ton tai khi tab XML goc duoc render cho tai khoan co quyen
    // ctdt-sua-xml (xem tab-xml-goc.blade.php). Uy nhiem tren document nen van chay voi
    // than chi tiet nap bang AJAX.
    //
    // An nut o day chi la trang tri - endpoint co middleware checkrole rieng.

    function khoiXml(phanTu) {
        return $(phanTu).closest('.ctdt-khoi-xml');
    }

    function doiCheDoSua($khoi, dangSua) {
        $khoi.find('.ctdt-xml-xem').toggle(!dangSua);
        $khoi.find('.ctdt-xml-sua').toggle(dangSua);
        $khoi.find('.ctdt-mo-sua-xml').toggle(!dangSua);
        $khoi.find('.ctdt-luu-xml, .ctdt-huy-sua-xml').toggle(dangSua);
    }

    $(document).on('click', '.ctdt-mo-sua-xml', function () {
        var $khoi = khoiXml(this);

        // Nap lai o nhap tu ban DANG HIEN THI moi lan mo: lan sua truoc co the da bi huy
        // giua chung, va o nhap con giu van ban do do.
        $khoi.find('.ctdt-xml-sua').val($khoi.find('.ctdt-xml-xem').text());
        $khoi.find('.ctdt-xml-thong-bao').empty();

        doiCheDoSua($khoi, true);
    });

    $(document).on('click', '.ctdt-huy-sua-xml', function () {
        var $khoi = khoiXml(this);

        $khoi.find('.ctdt-xml-thong-bao').empty();
        doiCheDoSua($khoi, false);
    });

    $(document).on('click', '.ctdt-luu-xml', function () {
        var $nut = $(this);
        var $khoi = khoiXml(this);
        var maHoSo = maHoSoCua(this);
        var chungTuId = $khoi.data('chung-tu-id');
        var noiDung = $khoi.find('.ctdt-xml-sua').val();

        luuXml($nut, $khoi, maHoSo, chungTuId, noiDung, false);
    });

    function luuXml($nut, $khoi, maHoSo, chungTuId, noiDung, daXacNhan) {
        var url = ghepUrl(mauUrlSuaXml, maHoSo).replace('__CTID__', encodeURIComponent(chungTuId));

        $nut.prop('disabled', true);

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                _token: token,
                noi_dung: noiDung,
                xac_nhan_da_gui: daXacNhan ? 1 : 0
            },
            dataType: 'json'
        }).done(function (kq) {
            if (kq.thanh_cong) {
                // Ban chi doc phai doi theo NGAY, khong cho tai lai tab: nguoi vua sua can
                // thay ban moi de biet minh sua dung chua. .text() chu khong .html() - noi
                // dung nay do nguoi dung go.
                $khoi.find('.ctdt-xml-xem').text(noiDung);
                doiCheDoSua($khoi, false);
                $khoi.find('.ctdt-xml-thong-bao').empty();

                Swal.fire('Đã lưu', kq.thong_diep, 'success');

                // Chu ky cu da bi vo hieu va so loi co the doi - man danh sach phia sau
                // dang hien so cu. Dung chinh su kien ma nut Ky va gui dang dung.
                $(document).trigger('ctdt:da-xep-hang', [maHoSo]);

                return;
            }

            if (kq.can_xac_nhan) {
                Swal.fire({
                    title: 'Cổng đã tiếp nhận hồ sơ này',
                    text: kq.thong_diep,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Vẫn sửa',
                    cancelButtonText: 'Huỷ'
                }).then(function (kq2) {
                    if (kq2.value) {
                        luuXml($nut, $khoi, maHoSo, chungTuId, noiDung, true);
                    }
                });

                return;
            }

            veLoiXml($khoi, kq.thong_diep);
        }).fail(function (xhr) {
            var kq = xhr.responseJSON;

            veLoiXml($khoi, (kq && kq.thong_diep)
                ? kq.thong_diep
                : 'Không lưu được. Kiểm tra kết nối rồi thử lại.');
        }).always(function () {
            $nut.prop('disabled', false);
        });
    }

    // .text() chu khong .html(): thong diep co the mang nguyen van thong bao cua bo phan
    // giai XML, trong do co ca doan van ban nguoi dung vua go.
    function veLoiXml($khoi, thongDiep) {
        $khoi.find('.ctdt-xml-thong-bao').empty().append(
            $('<div>').addClass('alert alert-danger').css('margin-bottom', 0).text(thongDiep)
        );
    }
});
</script>
