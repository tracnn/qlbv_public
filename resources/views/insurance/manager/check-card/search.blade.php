<div class="panel panel-default">
    <div class="panel-body">
        <div class="form-group">
            <b>{{ __('insurance.backend.labels.check-card') }}</b>
        </div>

        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-1">
                    <label for="qrcode">{{ __('insurance.backend.labels.qrcode') }}</label>
                </div>
                <div class="col-sm-11">
                    <input class="form-control" type="text" name="qrcode" placeholder="{{ __('insurance.backend.labels.qrcode') }}" value="{{ $params['qrcode'] }}" autofocus>
                </div>
            </div>
        </div>

        <form type="GET" action="{{route('insurance.check-card.search')}}" id="target">
            {{-- Bon o nhap tren MOT hang, nhan nam TREN o - giong man tra cuu tien cung chi
                 tra. Bo cuc cu dat nhan ben trai o nen moi o chi con 3/4 be rong, va o chon
                 co so bi day rieng xuong mot hang bang mot the <div class="col-sm-12"> rong. --}}
            {{-- O chon co so PHAI nam trong form: luong quet QR tu goi $('#target').submit(),
                 o nam ngoai form se khong duoc gui kem. --}}
            <div class="col-sm-3">
                <div class="form-group">
                    <label for="ma_cskcb">{{ __('insurance.backend.labels.ma_cskcb') }}</label>
                    <select class="form-control" name="ma_cskcb" id="ma_cskcb">
                        <option value="">-- Chọn cơ sở --</option>
                        @foreach ($danhSachCoSo as $ma => $nhan)
                            <option value="{{ $ma }}" {{ (string) (old('ma_cskcb') ? old('ma_cskcb') : $params['ma_cskcb']) === (string) $ma ? 'selected' : '' }}>{{ $nhan }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="form-group">
                    <label for="card-number">Mã thẻ BHYT/CCCD</label>
                    <input class="form-control card-number" type="text" name="card-number" placeholder="10, 12, 15 hoặc 17 ký tự" value="{{ old('card-number') ? old('card-number') : $params['card-number'] }}">
                </div>
            </div>

            <div class="col-sm-3">
                <div class="form-group">
                    <label for="name">{{ __('insurance.backend.labels.name') }}</label>
                    <input class="form-control card-number" type="text" name="name" placeholder="{{ __('insurance.backend.labels.name') }}" value="{{ old('name') ?  old('name') : $params['name'] }}">
                </div>
            </div>

            <div class="col-sm-3">
                <div class="form-group">
                    <label for="birthday">{{ __('insurance.backend.labels.birthday') }}</label>
                    <input class="form-control" type="text" name="birthday" placeholder="{{ __('insurance.backend.labels.type-birthday') }}" value="{{ old('birthday') ? old('birthday') : $params['birthday'] }}">
                </div>
            </div>

            {{-- Hai nut xuong hang rieng: nut "Tra tien cung chi tra" chi hien khi tra the
                 thanh cong, nhet no vao cung hang voi cac o nhap se lam hang co gian moi lan
                 no xuat hien. --}}
            <div class="col-sm-12">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-search"></i>
                    {{ __('insurance.backend.labels.search') }}
                </button>

                {{-- type="button" LA BAT BUOC: nut khong co type trong form se mac dinh la
                     submit, bam vao se gui lai form tra the thay vi mo modal. --}}
                {{-- Chi hien khi da tra the THANH CONG: tra sai/khong thay thi ngay sinh co
                     the rong, goi MCCT se truot luat required. --}}
                @if(isset($result_insurance) && $result_insurance['maKetQua'] == '000')
                <button type="button" class="btn btn-success" id="btn-mcct"
                    data-url="{{ route('insurance.mcct.api', [
                        'ma_cskcb' => $params['ma_cskcb'],
                        'ma_the' => $params['card-number'],
                        'ho_ten' => $params['name'],
                        'ngay_sinh' => $params['birthday'],
                    ]) }}"><i class="fa fa-money"></i>&nbsp;Tra tiền cùng chi trả</button>
                @endif
            </div>
        </form>

    </div>
</div>

@if(isset($result_insurance) && $result_insurance['maKetQua'] == '000')
{{-- Modal tra cuu MCCT. Dat NGOAI form tra the: modal cua Bootstrap duoc di chuyen ra cuoi
     <body> khi mo, va mot the <form> bi ngat giua chung se lam hong form cha. --}}
<div class="modal fade" id="modal-mcct" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" id="mcct-dong-x">
                    <span>&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-money"></i>&nbsp;Tiền cùng chi trả
                    <small>{{ $params['name'] }} &mdash; {{ $params['card-number'] }}</small>
                </h4>
            </div>

            <div class="modal-body">
                {{-- Trang thai DANG TAI. API cong cham, nen o day phai noi ro dieu do va phai
                     co dong ho chay: mot vong xoay dung yen sau 20 giay trong y het mot trang
                     chet, nguoi dung se bam lai - va moi lan bam la mot luot goi cong. --}}
                <div id="mcct-dang-tai" style="display: none;" class="text-center">
                    <p style="font-size: 15px;">
                        <i class="fa fa-spinner fa-spin"></i>&nbsp;
                        <span id="mcct-cau-cho">Đang hỏi cổng BHXH…</span>
                    </p>
                    <div class="progress progress-striped active" style="max-width: 420px; margin: 0 auto;">
                        <div class="progress-bar progress-bar-info" style="width: 100%"></div>
                    </div>
                    <p class="text-muted" style="margin-top: 10px;">
                        Đã chờ <b id="mcct-giay">0</b> giây. Cổng thường trả lời trong 5–30 giây.
                    </p>
                </div>

                {{-- Trang thai LOI --}}
                <div id="mcct-loi" style="display: none;">
                    <div class="alert alert-danger" id="mcct-loi-noi-dung"></div>
                    <div class="text-center">
                        <button type="button" class="btn btn-primary" id="mcct-thu-lai">
                            <i class="fa fa-refresh"></i>&nbsp;Thử lại
                        </button>
                    </div>
                </div>

                {{-- Trang thai KET QUA --}}
                <div id="mcct-ket-qua" style="display: none;"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" id="mcct-dong">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('after-scripts')
@include('insurance.manager.mcct._script')
<script type="text/javascript">
    // Nho co so da chon giua cac lan vao man. Chi nho LUA CHON, khong nho tai khoan hay
    // bat ky thu gi nhay cam.
    var KHOA_CO_SO = 'bhyt_tra_cuu_ma_cskcb';

    $(document).ready(function() {
        var $coSo = $('#ma_cskcb');

        // Gia tri may chu vua tra ve THANG gia tri nho: ket qua dang hien tren man phai khop
        // voi o chon. Chi lay tu localStorage khi o dang trong.
        if ($coSo.val() === '') {
            var daNho = null;
            try { daNho = localStorage.getItem(KHOA_CO_SO); } catch (e) { daNho = null; }

            // Chi chon neu ma do CON trong danh sach. Co so bi go khoi cau hinh thi bo qua
            // gia tri cu va de trong - khong chon bua mot co so khac, vi tra nham co so la
            // dung thu ma tinh nang nay sinh ra de chan.
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
        $coSo.on('change', function() {
            try { localStorage.setItem(KHOA_CO_SO, $(this).val()); } catch (e) {}
        });

        khoiTaoMcct();

        $('[name="qrcode"]').on('change', function(event) {
            event.preventDefault();
            $.ajax({
                type: "GET",
                data: {
                    'qrcode': $('[name="qrcode"]').val(),
                },
                url: "{{ route('insurance.check-card.getqrcode') }}", 
                success: function(result){
                    $('[name="card-number"]').val(result['card-number']);
                    $('[name="name"]').val(result['name']);
                    $('[name="birthday"]').val(result['birthday']);
                    $( "#target" ).submit();
                }
            });
        });
    });

    /*
     * Tra cuu tien cung chi tra (MCCT) trong modal.
     *
     * Toan bo phan dung ket qua va xu ly cho nam o public/js/mcct-tra-cuu.js - DUNG CHUNG voi
     * man tra cuu MCCT rieng. O day chi con phan rieng cua modal: mo/dong va khoa cai gi.
     */
    function khoiTaoMcct() {
        var $nut = $('#btn-mcct');

        if ($nut.length === 0) {
            return;
        }

        var $modal = $('#modal-mcct');

        var mcct = McctTraCuu.tao({
            url: $nut.data('url'),

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
                $('#target').find('input, select, button').prop('disabled', dangKhoa);
                $('#mcct-dong').prop('disabled', dangKhoa);
                $('#mcct-dong-x').prop('disabled', dangKhoa);

                // static: khong dong nham modal giua chung roi mat luot goi da tieu.
                $modal.data('bs.modal').options.backdrop = dangKhoa ? 'static' : true;
                $modal.data('bs.modal').options.keyboard = !dangKhoa;
            }
        });

        $nut.on('click', function () {
            // Mo modal NGAY, khong doi phan hoi: nguoi dung phai thay he thong da nhan lenh.
            $modal.modal('show');

            // Moi lan mo la mot lan goi cong: so lieu luon moi nhat, khong hien ket qua da luu.
            mcct.goi();
        });

        // Nut Thu lai o khoi bao loi: goi thang cong, vi vua that bai chu khong phai chua tra.
        $('#mcct-thu-lai').on('click', function () {
            mcct.goi();
        });

        // Dong modal khi dang goi: huy yeu cau de khong con mot callback ban vao modal da dong.
        $modal.on('hidden.bs.modal', function () {
            mcct.huy();
        });
    }
</script>
@endpush