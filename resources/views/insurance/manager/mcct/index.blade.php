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
<script src="{{ asset('js/mcct-tra-cuu.js') }}"></script>
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

        function tra(dungLaiKetQuaCu) {
            // Doi duong dan tren thanh dia chi de trang nay VAN gui cho nhau duoc, va bam F5
            // ra dung ket qua do. replaceState chu khong pushState: mot lan tra khong dang
            // mot buoc lui trong lich su trinh duyet.
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '',
                    '{{ route('insurance.mcct.search') }}?' + $.param(thamSo()));
            }

            mcct.capNhat({
                url: '{{ route('insurance.mcct.api') }}?' + $.param(thamSo()),
                urlGanNhat: '{{ route('insurance.mcct.gan-nhat') }}?'
                    + $.param({ ma_the: $('#ma_the').val() })
            });

            // Bam nut Tra cuu = hoi ket qua da luu truoc cho nhanh; chi khi vao thang bang
            // duong dan moi tu goi cong neu chua co du lieu cu.
            dungLaiKetQuaCu ? mcct.moDau() : mcct.goi();
        }

        // Bam Tra cuu: uu tien hien ngay ket qua lan truoc, nguoi dung tu bam Tra cuu lai.
        $nut.on('click', function () { tra(true); });

        // Nut Thu lai o khoi bao loi: goi thang cong, vi vua that bai chu khong phai chua tra.
        $('#mcct-thu-lai').on('click', function () { tra(false); });

        // Enter trong o nhap = bam Tra cuu. Khong con <form> nen phai tu noi lai hanh vi nay.
        // Truyen true de giong HET nut Tra cuu: thieu tham so o day se lam Enter goi thang
        // cong trong khi nut thi khong - hai duong vao cung mot viec ma xu su khac nhau.
        $('.mcct-nhap').on('keydown', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                tra(true);
            }
        });

        @if ($traNgay)
        // Vao trang bang duong dan da co du tham so (chia se, hoac tu man tra cuu the sang):
        // hien ngay ket qua lan truoc neu co, chua co thi goi cong.
        tra(true);
        @endif
    });
</script>
@endpush
