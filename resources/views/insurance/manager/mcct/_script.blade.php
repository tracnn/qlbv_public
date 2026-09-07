{{-- The <script> nap bo tra cuu MCCT dung chung. Ca man rieng lan modal tren man tra cuu the
     deu @include tep nay - de mot chuoi duong dan chi ton tai o MOT cho.

     PHAI co tham so phien ban: ngay 07/9/2026 prod bao "mcct.moDau is not a function" vi
     trinh duyet giu ban js cu trong cache trong khi blade da goi ham moi. Duong dan khong doi
     thi trinh duyet khong co ly do gi tai lai.

     Dung filemtime chu khong dung mot hang so tu tang: hang so phai NHO tang moi lan sua, va
     quen mot lan la loi nay quay lai y het. filemtime tu dung, khong the quen. --}}
@php
    $mcctJs = public_path('js/mcct-tra-cuu.js');
    // Tep khong ton tai (deploy thieu) thi van nap - de trinh duyet bao 404 ro rang o tab
    // Network, thay vi bien mat lang le.
    $mcctV = is_file($mcctJs) ? filemtime($mcctJs) : '0';
@endphp
<script src="{{ asset('js/mcct-tra-cuu.js') }}?v={{ $mcctV }}"></script>
