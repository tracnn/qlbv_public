{{-- Than man chi tiet ho so chung tu dien tu.

     Bien vao: $hoSo (CtdtHoSo), $tabs (CtdtDetailTabs::cua($hoSo))

     TEP NAY KHONG DUOC CHUA THE SCRIPT. No duoc nap bang AJAX vao modal tren man danh sach,
     va moi khoi @push trong mot fragment nap kieu do se bi bo di KHONG MOT LOI BAO - nut
     van hien, van bam duoc, va khong co gi xay ra. Moi hanh vi nam o
     bhyt.ctdt.partials.js-chi-tiet, gan bang uy nhiem su kien.

     CHI CO MOT THAN CHI TIET TREN MOI TRANG: tep nay dung dinh danh co dinh
     (#noi-dung-tab, #ctdt-tabs, #btn-ky-va-gui), nen nhung no hai lan tren cung mot trang
     se lam tab nap nham cho.

     CtdtChiTietModalTest canh dieu do. --}}
<div class="ctdt-chi-tiet"
     data-ma-ho-so="{{ $hoSo->ma_ho_so }}"
     data-ma-gd="{{ $hoSo->ma_gd }}">

<div class="panel panel-default">
    <div class="panel-body">
        <div class="row">
            <div class="col-sm-3"><strong>Dịch vụ:</strong>
                {{ array_get(config('ctdt.dich_vu'), $hoSo->dich_vu . '.ten', $hoSo->dich_vu) }}
            </div>
            <div class="col-sm-2"><strong>Mã CSKCB:</strong> {{ $hoSo->macskcb }}</div>
            <div class="col-sm-2"><strong>Số chứng từ:</strong> {{ $hoSo->so_chung_tu }}</div>
            {{-- Nhan "Loi chan gui" chu khong phai "So loi": so_loi CHI dem loi muc chan,
                 con badge tren tab Loi dem ca canh bao - hai con so khac nhau ma cung mot
                 nhan la de nguoi doc tuong mot trong hai cho bi hong. Va khi checked_at
                 rong thi con so 0 khong co nghia "sach" ma la "chua ai kiem". --}}
            <div class="col-sm-2"><strong>Lỗi chặn gửi:</strong>
                {{ empty($hoSo->checked_at) ? 'Chưa kiểm' : $hoSo->so_loi }}
            </div>
            <div class="col-sm-3"><strong>Nạp lúc:</strong> {{ $hoSo->imported_at }}</div>
        </div>
        <div class="row" style="margin-top:8px">
            <div class="col-sm-3"><strong>Ký số:</strong> {{ $hoSo->is_signed ? 'Đã ký' : 'Chưa ký' }}</div>
            <div class="col-sm-3"><strong>MaGD:</strong> {{ $hoSo->ma_gd ?: '—' }}</div>
            <div class="col-sm-3"><strong>Mã kết quả:</strong> {{ $hoSo->ma_ket_qua ?: '—' }}</div>
            <div class="col-sm-3"><strong>Tiếp nhận:</strong> {{ $hoSo->thoi_gian_tiep_nhan ?: '—' }}</div>
        </div>
        @if ($hoSo->lich_su_gui)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                <strong>Lịch sử gửi:</strong>
                <pre style="white-space:pre-wrap">{{ $hoSo->lich_su_gui }}</pre>
            </div>
        </div>
        @endif
        @if ($hoSo->signed_error)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                {{-- signed_error chua thong diep tu dich vu ky (USB token / HSM) - du lieu
                     ben ngoai, nen dung {{ }}. Hien no TRUOC loi gui: khi ky hong thi loi
                     gui chi la he qua ("Ho so chua ky so"), con ly do that nam o day. --}}
                <div class="alert alert-danger" style="margin-bottom:4px">
                    <strong>Lỗi ký số:</strong> {{ $hoSo->signed_error }}
                </div>
            </div>
        </div>
        @endif
        @if ($hoSo->submit_error)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                {{-- submit_error va submitted_message chua nguyen van phan hoi cua cong -
                     du lieu ben ngoai. Moi cho hien deu dung {{ }}. --}}
                <div class="alert alert-warning" style="margin-bottom:4px">
                    <strong>Lỗi gửi:</strong> {{ $hoSo->submit_error }}
                </div>
            </div>
        </div>
        @endif
        {{-- submitted_message tach rieng khoi khoi submit_error o tren: phan hoi nguyen van
             cua MOT LAN GUI THANH CONG cung phai hien duoc, khong chi khi gui that bai. --}}
        @if ($hoSo->submitted_message)
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12">
                <pre style="white-space:pre-wrap">{{ $hoSo->submitted_message }}</pre>
            </div>
        </div>
        @endif
        @include('bhyt.ctdt.partials.nut-ky-va-gui', ['hoSo' => $hoSo])
        @if (auth()->check() && auth()->user()->hasRole('superadministrator'))
        <div class="row" style="margin-top:8px">
            <div class="col-sm-12 text-right">
                <button type="button" id="btn-xoa-ho-so" class="btn btn-danger btn-sm">
                    <i class="fa fa-trash"></i> Xóa hồ sơ
                </button>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="nav-tabs-custom">
    <ul class="nav nav-tabs" id="ctdt-tabs">
        @foreach ($tabs as $i => $tab)
        <li class="{{ $i === 0 ? 'active' : '' }}">
            <a href="#" data-loai="{{ $tab['ma'] }}">
                {{ $tab['nhan'] }}
                @if ($tab['so_luong'] > 1)<span class="badge">{{ $tab['so_luong'] }}</span>@endif
            </a>
        </li>
        @endforeach
    </ul>
    <div class="tab-content">
        <div id="noi-dung-tab"><p class="text-muted">Đang tải…</p></div>
    </div>
</div>

</div>
