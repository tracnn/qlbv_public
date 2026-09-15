@extends('adminlte::page')

@section('title', 'Tra cứu tiền cùng chi trả')

@section('content_header')
<h1>
    Tra cứu
    <small>tiền cùng chi trả / miễn cùng chi trả</small>
</h1>
@stop

@section('content')
@include('includes.message')
@include('insurance.manager.mcct.search')
@include('insurance.manager.mcct.result')
@stop

@push('after-scripts')
@include('insurance.manager.mcct._script')
<script type="text/javascript">
    $(document).ready(function () {
        var $nut = $('#mcct-tra');

        var mcct = McctTraCuu.tao({
            // Suy ra TU cau hinh may chu, cong them 10 giay dem. Neu javascript bo cuoc TRUOC
            // thi nguoi dung nhan thong bao chung chung cua trinh duyet thay vi thong bao that
            // cua may chu ("cong bao loi", "tai khoan bi han che tra cuu"...).
            timeoutMs: {{ ((int) config('mcct.timeout_tong', 60) + 10) * 1000 }},

            o: {
                dangTai: '#mcct-dang-tai',
                giay: '#mcct-giay',
                cauCho: '#mcct-cau-cho',
                loi: '#mcct-loi',
                loiNoiDung: '#mcct-loi-noi-dung',
                ketQua: '#mcct-ket-qua'
            },

            /* Khoa trong luc goi: bam hai lan la tieu HAI luot goi cong. */
            khoa: function (dangKhoa) {
                $nut.prop('disabled', dangKhoa);
                $('.mcct-nhap').prop('disabled', dangKhoa);
            }
        });

        function thamSo() {
            return {
                ma_cskcb: $('#ma_cskcb').val(),
                ma_the: $('#ma_the').val(),
                ho_ten: $('#ho_ten').val(),
                ngay_sinh: $('#ngay_sinh').val()
            };
        }

        // Moi lan tra la mot lan goi cong THAT - khong hien ket qua da luu (15/9/2026).
        function tra() {
            // Doi duong dan tren thanh dia chi de trang nay VAN gui cho nhau duoc, va bam F5
            // ra dung ket qua do. replaceState chu khong pushState: mot lan tra khong dang
            // mot buoc lui trong lich su trinh duyet.
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '',
                    '{{ route('insurance.mcct.search') }}?' + $.param(thamSo()));
            }

            mcct.capNhat({
                url: '{{ route('insurance.mcct.api') }}?' + $.param(thamSo())
            });

            mcct.goi();
        }

        $nut.on('click', function () { tra(); });

        // Nut Thu lai o khoi bao loi.
        $('#mcct-thu-lai').on('click', function () { tra(); });

        // Enter trong o nhap = bam Tra cuu. Khong con <form> nen phai tu noi lai hanh vi nay.
        $('.mcct-nhap').on('keydown', function (e) {
            if (e.which === 13) {
                e.preventDefault();

                // Nut dang khoa = dang cho cong. Enter luc do KHONG duoc sinh them mot luot
                // goi: o nhap bi khoa thi khong nhan phim, nhung phim Enter giu lau van co the
                // lot vao truoc khi khoa kip ap dung.
                if (mcct.dangGoi()) {
                    return;
                }

                tra();
            }
        });

        /* ---- Ho ten luon viet hoa ---- */

        // Viet hoa NGAY KHI GO chu khong doi luc gui: nguoi dung phai thay dung cai se duoc
        // gui di. May chu van mb_strtoupper nhu cu, day chi la phan hien thi.
        $('#ho_ten').on('input', function () {
            var o = this;
            var viTri = o.selectionStart;
            var hoa = o.value.toUpperCase();

            if (o.value === hoa) {
                return;
            }

            o.value = hoa;

            // Dat lai con tro: khong lam thi no nhay ve cuoi o moi lan go, va nguoi dung
            // khong sua duoc mot chu o giua ten.
            try { o.setSelectionRange(viTri, viTri); } catch (e) {}
        });

        /* ---- Nho co so da chon ---- */

        // DUNG CHUNG khoa voi man tra cuu the BHYT: cung mot nguoi dung, cung mot co so. Khoa
        // rieng chi tao ra tinh huong hai man hien hai co so khac nhau ma khong ai giai thich
        // duoc vi sao. Chi nho LUA CHON, khong nho tai khoan hay bat ky thu gi nhay cam.
        var KHOA_CO_SO = 'bhyt_tra_cuu_ma_cskcb';
        var $coSo = $('#ma_cskcb');

        // Gia tri may chu vua tra ve THANG gia tri nho: ket qua dang hien tren man phai khop
        // voi o chon. Chi lay tu localStorage khi o dang trong.
        if ($coSo.val() === '') {
            var daNho = null;

            try { daNho = localStorage.getItem(KHOA_CO_SO); } catch (e) { daNho = null; }

            // Chi chon neu ma do CON trong danh sach. Co so bi go khoi cau hinh thi bo qua gia
            // tri cu va de trong - khong chon bua mot co so khac, vi tra nham co so la dung
            // thu ma viec chon co so sinh ra de chan.
            if (daNho && $coSo.find('option[value="' + daNho + '"]').length > 0) {
                $coSo.val(daNho);
            }
        }

        // Chi co mot co so thi chon san.
        if ($coSo.val() === '' && $coSo.find('option[value!=""]').length === 1) {
            $coSo.val($coSo.find('option[value!=""]').first().val());
        }

        // Ghi ngay khi doi, khong doi bam tra cuu: nguoi dung doi co so roi bo di thi lan sau
        // van nho.
        $coSo.on('change', function () {
            try { localStorage.setItem(KHOA_CO_SO, $(this).val()); } catch (e) {}
        });

        /* ---- Quet ma QR ---- */

        /*
         * Chuan hoa ngay sinh tu ma QR.
         *
         * VI SAO CAN: man tra cuu the KHONG kiem dinh dang ngay sinh (InsuranceRequest chi doi
         * `required`), con man nay co regex chat chi nhan dd/mm/yyyy, mm/yyyy hoac yyyy. Truong
         * thu ba trong ma QR the BHYT thuong la 8 chu so lien (01011980), nen quet QR o day se
         * bi chan ngay o luat kiem trong khi man tra the van chay - dung kieu loi khien nguoi
         * dung nghi chuc nang hong.
         *
         * Chiu duoc CA HAI dang thay vi doan mot: khong co the that de quet thi khong xac minh
         * duoc dinh dang nao moi la dinh dang that.
         */
        function chuanHoaNgaySinh(s) {
            s = String(s || '').trim();

            if (s.indexOf('/') !== -1) {
                return s;
            }

            if (/^\d{8}$/.test(s)) {
                return s.substring(0, 2) + '/' + s.substring(2, 4) + '/' + s.substring(4);
            }

            if (/^\d{6}$/.test(s)) {
                return s.substring(0, 2) + '/' + s.substring(2);
            }

            return s;
        }

        $('#qrcode').on('change', function (event) {
            event.preventDefault();

            $.ajax({
                type: 'GET',
                data: { qrcode: $('#qrcode').val() },
                // Dung lai endpoint cua man tra cuu the - KHONG viet bo giai ma QR thu hai.
                url: '{{ route('insurance.check-card.getqrcode') }}',
                success: function (kq) {
                    if (!kq) {
                        return;
                    }

                    $('#ma_the').val(kq['card-number'] || '');
                    $('#ho_ten').val(String(kq['name'] || '').toUpperCase());
                    $('#ngay_sinh').val(chuanHoaNgaySinh(kq['birthday']));

                    // Tra luon, khong bat bam them.
                    tra();
                }
            });
        });

        @if ($traNgay)
        // Vao trang bang duong dan da co du tham so (chia se, hoac tu man tra cuu the sang):
        // goi cong ngay. Bam F5 tren trang nay cung la mot lan goi cong.
        tra();
        @endif
    });
</script>
@endpush
