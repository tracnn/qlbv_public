{{-- Nut "Ky va gui" tren man chi tiet.

     Bien vao: $hoSo — ban ghi CtdtHoSo

     TEP NAY CHI CHUA DANH DAU. Hanh vi cua nut nam o bhyt.ctdt.partials.js-chi-tiet.
     Truoc day tep nay tu day JS bang chi thi push doi tren stack 'after-scripts', va do la
     mot cai bay: khi than chi tiet duoc nap bang AJAX vao modal, khoi do bi bo di khong mot
     loi bao - nut van hien, van bam duoc, va khong co gi xay ra.

     LUU Y: moi chi thi Blade nam trong chu thich JavaScript VAN duoc Blade dich va sinh ra
     PHP hong. Trong chu thich JS, viet @@if neu can nhac toi chi thi. --}}
<div class="row" style="margin-top:8px">
    <div class="col-sm-12 text-right">
        <button type="button" id="btn-ky-va-gui" class="btn btn-primary btn-sm">
            <i class="fa fa-paper-plane"></i>
            {{ $hoSo->ma_gd ? 'Ký và gửi lại' : 'Ký và gửi' }}
        </button>
    </div>
</div>
