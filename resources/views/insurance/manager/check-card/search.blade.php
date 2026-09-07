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
                        Đã chờ <b id="mcct-giay">0</b> giây. Cổng thường trả lời trong 5–20 giây.
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
     * API cong BHXH CHAM (thuong 5-20 giay, co khi hon) va cong co danh sach tai khoan bi han
     * che tra cuu - nen moi luot goi la tai nguyen co han. Toan bo phan duoi day xoay quanh
     * mot muc tieu: nguoi dung khong bao gio phai DOAN xem he thong con dang chay hay da hong,
     * vi doan sai thi ho bam lai va tieu them mot luot.
     */
    function khoiTaoMcct() {
        var $nut = $('#btn-mcct');

        if ($nut.length === 0) {
            return;
        }

        var $modal = $('#modal-mcct');
        var URL = $nut.data('url');

        // Dai hon timeout tong 30 giay cua may chu. Neu javascript bo cuoc TRUOC thi nguoi
        // dung nhan thong bao chung chung cua trinh duyet thay vi thong bao that cua may chu
        // ("cong bao loi", "tai khoan bi han che tra cuu"...).
        var TIMEOUT_MS = 40000;

        // Sau moc nay thi doi cau chu: nguoi dung can biet CHAM la binh thuong, khong phai hong.
        var MOC_CHAM_GIAY = 15;

        var demGio = null;
        var yeuCau = null;

        function tien(x) {
            var n = Math.round(Number(x) || 0);

            return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function thoat(s) {
            return $('<div>').text(s === null || s === undefined ? '' : s).html();
        }

        function batDauDemGio() {
            var batDau = Date.now();

            $('#mcct-giay').text('0');
            $('#mcct-cau-cho').text('Đang hỏi cổng BHXH…');

            demGio = setInterval(function () {
                var giay = Math.floor((Date.now() - batDau) / 1000);

                $('#mcct-giay').text(giay);

                if (giay === MOC_CHAM_GIAY) {
                    $('#mcct-cau-cho').text('Cổng BHXH đang phản hồi chậm, vẫn đang chờ…');
                }
            }, 1000);
        }

        function dungDemGio() {
            if (demGio !== null) {
                clearInterval(demGio);
                demGio = null;
            }
        }

        /* Khoa trong luc goi: bam hai lan la tieu HAI luot goi cong. */
        function khoa(dangKhoa) {
            $nut.prop('disabled', dangKhoa);
            $('#target').find('input, select, button').prop('disabled', dangKhoa);
            $('#mcct-dong').prop('disabled', dangKhoa);
            $('#mcct-dong-x').prop('disabled', dangKhoa);

            // static: khong dong nham modal giua chung roi mat luot goi da tieu.
            $modal.data('bs.modal').options.backdrop = dangKhoa ? 'static' : true;
            $modal.data('bs.modal').options.keyboard = !dangKhoa;
        }

        function hienKhoi(ten) {
            $('#mcct-dang-tai').toggle(ten === 'tai');
            $('#mcct-loi').toggle(ten === 'loi');
            $('#mcct-ket-qua').toggle(ten === 'ket-qua');
        }

        function hienLoi(thongBao) {
            $('#mcct-loi-noi-dung').text(thongBao);
            hienKhoi('loi');
        }

        function dungBangChiPhi(dong) {
            if (!dong || dong.length === 0) {
                return '';
            }

            var h = '<div class="table-responsive"><table class="table table-condensed table-hover">'
                + '<tr><th>Mã CSKCB</th><th>Ngày vào</th><th>Ngày ra</th><th>Đối tượng</th>'
                + '<th class="text-right">Tiền CCT thuộc diện miễn</th>'
                + '<th class="text-right">Lũy kế</th><th>Ngày nhận</th></tr>';

            // Giu NGUYEN thu tu cong tra (da giam dan theo ngay ra vien), khong sap lai.
            for (var i = 0; i < dong.length; i++) {
                var d = dong[i];

                h += '<tr><td>' + thoat(d.ma_cskcb) + '</td>'
                    + '<td>' + thoat(d.ngay_vao) + '</td>'
                    + '<td>' + thoat(d.ngay_ra) + '</td>'
                    + '<td>' + thoat(d.ma_doi_tuong_kcb) + '</td>'
                    + '<td class="text-right">' + tien(d.t_bn_cct_mcct) + '</td>'
                    + '<td class="text-right">' + tien(d.t_bn_cct_luy_ke) + '</td>'
                    + '<td>' + thoat(d.ngay_nhan) + '</td></tr>';
            }

            return h + '</table></div>';
        }

        function hienKetQua(kq) {
            var the = kq.thong_tin_the || {};
            var h = '';

            if (the.ho_ten) {
                h += '<table class="table table-condensed"><tr>'
                    + '<td>Họ tên: <b>' + thoat(the.ho_ten) + '</b></td>'
                    + '<td>Ngày sinh: ' + thoat(the.ngay_sinh) + '</td>'
                    + '<td>Mã số BHXH: ' + thoat(the.ma_bhxh) + '</td>'
                    + '<td>Thẻ hết hạn: ' + thoat(the.ngay_ket_thuc) + '</td>'
                    + '</tr></table>';
            }

            var nhan = kq.du_dieu_kien
                ? '<span class="label label-success">ĐỦ ĐIỀU KIỆN MIỄN CÙNG CHI TRẢ</span>'
                : '<span class="label label-warning">CHƯA ĐỦ ĐIỀU KIỆN MIỄN CÙNG CHI TRẢ</span>';

            h += '<div class="well well-sm"><table class="table table-condensed"><tr>'
                + '<td>Lũy kế cùng chi trả: <b>' + tien(kq.luy_ke) + ' đ</b></td>'
                + '<td>Ngưỡng: <b>' + tien(kq.nguong) + ' đ</b></td>'
                + '<td>' + nhan + '</td></tr></table>'
                // GhiChu NGUYEN VAN: no ghi du lieu cong "tinh den" thoi diem nao. So lieu
                // cong co do tre, nguoi dung phai thay moc do TRUOC khi ket luan voi nguoi benh.
                + '<small class="text-muted">' + thoat(kq.ghi_chu) + '</small></div>';

            h += dungBangChiPhi(kq.dong);

            if (kq.loi_luu) {
                h += '<div class="alert alert-warning">' + thoat(kq.loi_luu) + '</div>';
            }

            $('#mcct-ket-qua').html(h);
            hienKhoi('ket-qua');
        }

        function goi() {
            hienKhoi('tai');
            khoa(true);
            batDauDemGio();

            yeuCau = $.ajax({
                type: 'GET',
                url: URL,
                dataType: 'json',
                timeout: TIMEOUT_MS
            }).done(function (kq) {
                if (!kq || typeof kq.ok === 'undefined') {
                    hienLoi('Máy chủ trả về dữ liệu không đọc được.');

                    return;
                }

                // Ma 204/400/500 KHONG phai loi he thong: hien dung thong bao cua may chu.
                if (!kq.ok) {
                    hienLoi(kq.thong_bao || 'Tra cứu không thành công.');

                    return;
                }

                hienKetQua(kq);
            }).fail(function (xhr, trangThai) {
                if (trangThai === 'abort') {
                    return;
                }

                if (trangThai === 'timeout') {
                    hienLoi('Cổng BHXH không trả lời sau ' + (TIMEOUT_MS / 1000)
                        + ' giây. Thử lại sau ít phút.');

                    return;
                }

                // 422: McctRequest chan dau vao. Hien dung cau bao loi cua no.
                if (xhr.status === 422 && xhr.responseJSON) {
                    var ds = [];

                    $.each(xhr.responseJSON.errors || xhr.responseJSON, function (k, v) {
                        ds.push($.isArray(v) ? v.join(' ') : v);
                    });

                    hienLoi(ds.join(' ') || 'Thông tin tra cứu không hợp lệ.');

                    return;
                }

                hienLoi('Lỗi khi gọi máy chủ (' + xhr.status + ').');
            }).always(function () {
                dungDemGio();
                khoa(false);
                yeuCau = null;
            });
        }

        $nut.on('click', function () {
            // Mo modal NGAY, khong doi phan hoi: nguoi dung phai thay he thong da nhan lenh.
            $modal.modal('show');
            goi();
        });

        $('#mcct-thu-lai').on('click', goi);

        $modal.on('hidden.bs.modal', function () {
            // Dong modal khi dang goi (chi xay ra neu khoa bi go bang cach nao do): huy yeu cau
            // de khong con mot callback ban vao mot modal da dong.
            if (yeuCau !== null) {
                yeuCau.abort();
            }

            dungDemGio();
            khoa(false);
        });
    }
</script>
@endpush