<!-- resources/views/bhyt/detail-xml.blade.php -->
<label>Hồ Sơ - {{ $xml1->ma_lk }}; Mã BN - {{ $xml1->ma_bn }}; 
    Họ tên - {{ $xml1->ho_ten }}; Ngày sinh - {{ dob($xml1->ngay_sinh) }}
    Mã thẻ - {{ $xml1->ma_the_bhyt }}; Nơi ĐKBĐ - {{ $xml1->ma_dkbd }}
</label>
<ul class="nav nav-tabs">
    <li class="active"><a data-toggle="tab" href="#menu1">XML1</a></li>
    @if($xml1->Qd130Xml2->isNotEmpty())
    <li><a data-toggle="tab" href="#menu2">XML2</a></li>
    @endif
    @if($xml1->Qd130Xml3->isNotEmpty())
    <li><a data-toggle="tab" href="#menu3">XML3</a></li>
    @endif
    @if($xml1->Qd130Xml4->isNotEmpty())
    <li><a data-toggle="tab" href="#menu4">XML4</a></li>
    @endif
    @if($xml1->Qd130Xml5->isNotEmpty())
    <li><a data-toggle="tab" href="#menu5">XML5</a></li>
    @endif
    @if($xml1->Qd130Xml7->isNotEmpty())
    <li><a data-toggle="tab" href="#menu7">XML7</a></li>
    @endif
    @if($xml1->Qd130Xml8->isNotEmpty())
    <li><a data-toggle="tab" href="#menu8">XML8</a></li>
    @endif
    @if($xml1->Qd130Xml9->isNotEmpty())
    <li><a data-toggle="tab" href="#menu9">XML9</a></li>
    @endif
    @if($xml1->Qd130Xml11->isNotEmpty())
    <li><a data-toggle="tab" href="#menu11">XML11</a></li>
    @endif
    @if($xml1->Qd130Xml13->isNotEmpty())
    <li><a data-toggle="tab" href="#menu13">XML13</a></li>
    @endif
    @if($xml1->Qd130Xml14->isNotEmpty())
    <li><a data-toggle="tab" href="#menu14">XML14</a></li>
    @endif
    <li class="{{ ($xml1->check_hein_card && ($xml1->check_hein_card->ma_tracuu != '000' || 
        $xml1->check_hein_card->ma_kiemtra != '00')) ? 'highlight-red' : '' }}">
        <a data-toggle="tab" href="#menu-hein-card">Thẻ BHYT</a>
    </li>
    @if($xml1->Qd130XmlErrorResult->isNotEmpty())
    <li class="{{ $xml1->Qd130XmlErrorResult->isNotEmpty() ? 'highlight-red' : '' }}">
        <a data-toggle="tab" href="#menu-xml-errors">Lỗi XML</a>
    </li>
    @endif
</ul>

<div class="tab-content">
    
    @include('bhyt.qd130.detail-xml-1')
    @include('bhyt.qd130.detail-xml-2')
    @include('bhyt.qd130.detail-xml-3')
    @include('bhyt.qd130.detail-xml-4')
    @include('bhyt.qd130.detail-xml-5')
    @include('bhyt.qd130.detail-xml-7')
    @include('bhyt.qd130.detail-xml-8')
    @include('bhyt.qd130.detail-xml-9')
    @include('bhyt.qd130.detail-xml-11')
    @include('bhyt.qd130.detail-xml-13')
    @include('bhyt.qd130.detail-xml-14')
    @include('bhyt.qd130.detail-xml-hein-card')
    @include('bhyt.qd130.detail-xml-errors')

</div>