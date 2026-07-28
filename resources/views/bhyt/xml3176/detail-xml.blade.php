<!-- resources/views/bhyt/detail-xml.blade.php -->
<label>Hồ Sơ - {{ $xml1->ma_lk }}; Mã BN - {{ $xml1->ma_bn }}; 
    Họ tên - {{ $xml1->ho_ten }}; Ngày sinh - {{ dob($xml1->ngay_sinh) }};
    Mã thẻ - {{ $xml1->ma_the_bhyt }}; Nơi ĐKBĐ - {{ $xml1->ma_dkbd }}; 
    Ngày vào: {{ strtodatetime($xml1->ngay_vao) }} - 
    Ngày ra: {{ strtodatetime($xml1->ngay_ra) }}
</label>
{{-- Da xoa tai day mot khoi code chet bi boc trong comment HTML. Comment HTML KHONG
     vo hieu hoa Blade: chi thi ben trong van duoc bien dich va van chay truy van that.
     Muon vo hieu hoa thi dung comment cua Blade nhu khoi nay. --}}
<ul class="nav nav-tabs">
    <li class="active">
        <a data-toggle="tab" href="#menu1">XML1
            @php
                $errorCountXml = $chiMucLoi->demLoi('XML1');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif       
        </a>
    </li>
    @if($soDong['XML2'] > 0)
    <li>
        <a data-toggle="tab" href="#menu2">XML2
             @php
                $errorCountXml = $chiMucLoi->demTheoStt($dsStt['XML2'], 'XML2');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML3'] > 0)
    <li>
        <a data-toggle="tab" href="#menu3">XML3
            @php
                $errorCountXml = $chiMucLoi->demTheoStt($dsStt['XML3'], 'XML3');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML4'] > 0)
    <li>
        <a data-toggle="tab" href="#menu4">XML4
            @php
                $errorCountXml = $chiMucLoi->demTheoStt($dsStt['XML4'], 'XML4');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML5'] > 0)
    <li>
        <a data-toggle="tab" href="#menu5">XML5
            @php
                $errorCountXml = $chiMucLoi->demTheoStt($dsStt['XML5'], 'XML5');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML7'] > 0)
    <li>
        <a data-toggle="tab" href="#menu7">XML7
            @php
                $errorCountXml = $chiMucLoi->demTheoXml($soDong['XML7'], 'XML7');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML8'] > 0)
    <li>
        <a data-toggle="tab" href="#menu8">XML8
            @php
                $errorCountXml = $chiMucLoi->demTheoXml($soDong['XML8'], 'XML8');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML9'] > 0)
    <li>
        <a data-toggle="tab" href="#menu9">XML9
            @php
                $errorCountXml = $chiMucLoi->demTheoXml($soDong['XML9'], 'XML9');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML10'] > 0)
    <li>
        <a data-toggle="tab" href="#menu10">XML10
            @php
                $errorCountXml = $chiMucLoi->demTheoXml($soDong['XML10'], 'XML10');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML11'] > 0)
    <li>
        <a data-toggle="tab" href="#menu11">XML11
            @php
                $errorCountXml = $chiMucLoi->demTheoXml($soDong['XML11'], 'XML11');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML13'] > 0)
    <li>
        <a data-toggle="tab" href="#menu13">XML13
            @php
                $errorCountXml = $chiMucLoi->demTheoXml($soDong['XML13'], 'XML13');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML14'] > 0)
    <li>
        <a data-toggle="tab" href="#menu14">XML14
            @php
                $errorCountXml = $chiMucLoi->demTheoXml($soDong['XML14'], 'XML14');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($soDong['XML15'] > 0)
    <li>
        <a data-toggle="tab" href="#menu15">XML15
            @php
                $errorCountXml = $chiMucLoi->demTheoXml($soDong['XML15'], 'XML15');
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    <li class="{{ ($xml1->check_hein_card && (in_array($xml1->check_hein_card->ma_tracuu, config('xml3176.hein_card_invalid.result_code')) || in_array($xml1->check_hein_card->ma_kiemtra, config('xml3176.hein_card_invalid.check_code')))) ? 'highlight-red' : '' }}">
        <a data-toggle="tab" href="#menu-hein-card">Thẻ BHYT</a>
    </li>
    @if($xml1->Xml3176ErrorResult->isNotEmpty())
        @php
            $hasCriticalError = $xml1->Xml3176ErrorResult->contains(function ($error) {
                return $error->critical_error;
            });
        @endphp
        <li class="{{ $xml1->Xml3176ErrorResult->isNotEmpty() ? 'highlight-red' : '' }}">
            <a data-toggle="tab" href="#menu-xml-errors">
                Lỗi XML
                @if($hasCriticalError)
                    <i class="fa fa-exclamation-triangle text-danger" aria-hidden="true" title="Critical Error"></i>
                @endif
            </a>
        </li>
    @endif
</ul>

<div class="tab-content">
    
    @include('bhyt.xml3176.detail-xml-1')
    @include('bhyt.xml3176.detail-xml-2')
    @include('bhyt.xml3176.detail-xml-3')
    @include('bhyt.xml3176.detail-xml-4')
    @include('bhyt.xml3176.detail-xml-5')

    {{-- Muoi tab con lai nap khi nguoi dung bam vao. Khung o day so huu id va class
         .tab-pane; endpoint chi tra ve phan noi dung ben trong. --}}
    @foreach(['XML7' => 'menu7', 'XML8' => 'menu8', 'XML9' => 'menu9',
              'XML10' => 'menu10', 'XML11' => 'menu11', 'XML13' => 'menu13',
              'XML14' => 'menu14', 'XML15' => 'menu15',
              'HEIN' => 'menu-hein-card', 'ERR' => 'menu-xml-errors'] as $ma => $id)
        <div id="{{ $id }}" class="tab-pane fade xml3176-lazy"
             data-url="{{ route('bhyt.xml3176.detail-xml.tab', ['ma_lk' => $xml1->ma_lk, 'xml' => $ma]) }}">
            <i class="fa fa-spinner fa-spin"></i> Đang tải…
        </div>
    @endforeach

</div>

<script>
    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip(); 
    });
</script>