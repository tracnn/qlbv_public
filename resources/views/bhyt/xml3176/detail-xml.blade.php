<!-- resources/views/bhyt/detail-xml.blade.php -->
<label>Hồ Sơ - {{ $xml1->ma_lk }}; Mã BN - {{ $xml1->ma_bn }}; 
    Họ tên - {{ $xml1->ho_ten }}; Ngày sinh - {{ dob($xml1->ngay_sinh) }};
    Mã thẻ - {{ $xml1->ma_the_bhyt }}; Nơi ĐKBĐ - {{ $xml1->ma_dkbd }}; 
    Ngày vào: {{ strtodatetime($xml1->ngay_vao) }} - 
    Ngày ra: {{ strtodatetime($xml1->ngay_ra) }}
</label>
<!-- @php
    $errorDescriptions = $xml1
    ->Xml3176XmlErrorResult()
    ->where('xml', 'XML1')
    ->pluck('description')
    ->implode('; ');
@endphp
<ul class="nav nav-tabs" @if($errorDescriptions) class="highlight-red" data-toggle="tooltip" title="{{ $errorDescriptions }}" @endif> -->
<ul class="nav nav-tabs">
    <li class="active">
        <a data-toggle="tab" href="#menu1">XML1
            @php
                $errorCountXml = $xml1->Xml3176XmlErrorResult()
                    ->where('xml', 'XML1')
                    ->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif       
        </a>
    </li>
    @if($xml1->Xml3176Xml2->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu2">XML2
             @php
                $errorCountXml = $xml1->Xml3176Xml2->filter(function($item) {
                    return $item->errorResult()->where('xml', 'XML2')
                    ->where('ma_lk', $item->ma_lk)
                    ->where('stt', $item->stt)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml3->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu3">XML3
            @php
                $errorCountXml = $xml1->Xml3176Xml3->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML3')
                    ->where('ma_lk', $item->ma_lk)
                    ->where('stt', $item->stt)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml4->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu4">XML4
            @php
                $errorCountXml = $xml1->Xml3176Xml4->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML4')
                    ->where('ma_lk', $item->ma_lk)
                    ->where('stt', $item->stt)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml5->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu5">XML5
            @php
                $errorCountXml = $xml1->Xml3176Xml5->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML5')
                    ->where('ma_lk', $item->ma_lk)
                    ->where('stt', $item->stt)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml7->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu7">XML7
            @php
                $errorCountXml = $xml1->Xml3176Xml7->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML7')
                    ->where('ma_lk', $item->ma_lk)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml8->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu8">XML8
            @php
                $errorCountXml = $xml1->Xml3176Xml8->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML8')
                    ->where('ma_lk', $item->ma_lk)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml9->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu9">XML9
            @php
                $errorCountXml = $xml1->Xml3176Xml9->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML9')
                    ->where('ma_lk', $item->ma_lk)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml10->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu10">XML10
            @php
                $errorCountXml = $xml1->Xml3176Xml10->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML10')
                    ->where('ma_lk', $item->ma_lk)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml11->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu11">XML11
            @php
                $errorCountXml = $xml1->Xml3176Xml11->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML11')
                    ->where('ma_lk', $item->ma_lk)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml13->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu13">XML13
            @php
                $errorCountXml = $xml1->Xml3176Xml13->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML13')
                    ->where('ma_lk', $item->ma_lk)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml14->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu14">XML14
            @php
                $errorCountXml = $xml1->Xml3176Xml14->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML14')
                    ->where('ma_lk', $item->ma_lk)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    @if($xml1->Xml3176Xml15->isNotEmpty())
    <li>
        <a data-toggle="tab" href="#menu15">XML15
            @php
                $errorCountXml = $xml1->Xml3176Xml15->filter(function($item) {
                    return $item->errorResult()
                    ->where('xml', 'XML15')
                    ->where('ma_lk', $item->ma_lk)
                    ->exists();
                })->count();
            @endphp
            @if($errorCountXml > 0)
                <span class="badge badge-error">{{ $errorCountXml }}</span>
            @endif
        </a>
    </li>
    @endif
    <li class="{{ ($xml1->check_hein_card && (in_array($xml1->check_hein_card->ma_tracuu, config('xml3176xml.hein_card_invalid.result_code')) || in_array($xml1->check_hein_card->ma_kiemtra, config('xml3176xml.hein_card_invalid.check_code')))) ? 'highlight-red' : '' }}">
        <a data-toggle="tab" href="#menu-hein-card">Thẻ BHYT</a>
    </li>
    @if($xml1->Xml3176XmlErrorResult->isNotEmpty())
        @php
            $hasCriticalError = $xml1->Xml3176XmlErrorResult->contains(function ($error) {
                return $error->critical_error;
            });
        @endphp
        <li class="{{ $xml1->Xml3176XmlErrorResult->isNotEmpty() ? 'highlight-red' : '' }}">
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
    @include('bhyt.xml3176.detail-xml-7')
    @include('bhyt.xml3176.detail-xml-8')
    @include('bhyt.xml3176.detail-xml-9')
    @include('bhyt.xml3176.detail-xml-10')
    @include('bhyt.xml3176.detail-xml-11')
    @include('bhyt.xml3176.detail-xml-13')
    @include('bhyt.xml3176.detail-xml-14')
    @include('bhyt.xml3176.detail-xml-15')
    @include('bhyt.xml3176.detail-xml-hein-card')
    @include('bhyt.xml3176.detail-xml-errors')

</div>

<script>
    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip(); 
    });
</script>