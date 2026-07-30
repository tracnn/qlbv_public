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
            {{-- O chon co so PHAI nam trong form: luong quet QR tu goi $('#target').submit(),
                 o nam ngoai form se khong duoc gui kem. --}}
            <div class="col-sm-4">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="ma_cskcb">{{ __('insurance.backend.labels.ma_cskcb') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <select class="form-control" name="ma_cskcb" id="ma_cskcb">
                            <option value="">-- Chọn cơ sở --</option>
                            @foreach ($danhSachCoSo as $ma => $nhan)
                                <option value="{{ $ma }}" {{ (string) (old('ma_cskcb') ? old('ma_cskcb') : $params['ma_cskcb']) === (string) $ma ? 'selected' : '' }}>{{ $nhan }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-sm-12"></div>

            <div class="col-sm-4">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="card-number">{{ __('insurance.backend.labels.card-number') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control card-number" type="text" name="card-number" placeholder="{{ __('insurance.backend.labels.card-number') }}" value="{{ old('card-number') ? old('card-number') : $params['card-number'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="name">{{ __('insurance.backend.labels.name') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control card-number" type="text" name="name" placeholder="{{ __('insurance.backend.labels.name') }}" value="{{ old('name') ?  old('name') : $params['name'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group row">
                    <div class="col-sm-3">
                        <label for="birthday">{{ __('insurance.backend.labels.birthday') }}</label>
                    </div>
                    <div class="col-sm-9">
                        <input class="form-control" type="text" name="birthday" placeholder="{{ __('insurance.backend.labels.type-birthday') }}" value="{{ old('birthday') ? old('birthday') : $params['birthday'] }}">
                    </div>
                </div>
            </div>

            <div class="col-sm-12">
                <button class="btn btn-info">
                <i class="glyphicon glyphicon-search"></i>
                    {{ __('insurance.backend.labels.search') }}
                </button>
            </div>              
        </form>

    </div>
</div>

@push('after-scripts')
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
</script>
@endpush