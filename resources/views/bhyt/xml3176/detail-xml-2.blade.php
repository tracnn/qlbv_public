@php
    $cauHinhXml2 = App\Services\Xml3176\Xml3176DetailTabs::BANG_NHIEU_DONG['XML2'];
    $nhomXml2 = App\Services\Xml3176\Xml3176DetailTabs::khoaNhom($dsNhom['XML2'], $cauHinhXml2['cat']);
@endphp

<div id="menu2" class="tab-pane fade">
    <ul class="nav nav-tabs">
        @foreach($nhomXml2 as $i => $ngay_yl)
            <li class="{{ $i === 0 ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_xml2_{{ $i }}">
                    Ngày: {{ strtodate($ngay_yl) }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        {{-- Id tab con dung CHI SO chu khong nhung thang khoa nhom: khoa ngay thi an toan
             nhung ma_nhom cua XML3 khong co gi bao dam, va dung chi so thi ca bon bang
             theo cung mot quy uoc. Nhan hien thi khong doi. --}}
        @foreach($nhomXml2 as $i => $ngay_yl)
            <div id="tab_xml2_{{ $i }}"
                 class="tab-pane fade xml3176-lazy {{ $i === 0 ? 'in active' : '' }}"
                 data-url="{{ route('bhyt.xml3176.detail-xml.rows', ['ma_lk' => $xml1->ma_lk, 'xml' => 'XML2']) }}?nhom={{ urlencode($ngay_yl) }}">
                <div class="panel panel-default">
                    <div class="panel-body table-responsive">
                        <i class="fa fa-spinner fa-spin"></i> Đang tải…
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
