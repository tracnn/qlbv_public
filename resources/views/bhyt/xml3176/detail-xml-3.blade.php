@php
    $cauHinhXml3 = App\Services\Xml3176\Xml3176DetailTabs::BANG_NHIEU_DONG['XML3'];
    // XML3 nhom theo ma_nhom (cat = 0), khong phai theo ngay nhu ba bang kia.
    $nhomXml3 = App\Services\Xml3176\Xml3176DetailTabs::khoaNhom($dsNhom['XML3'], $cauHinhXml3['cat']);
@endphp

<div id="menu3" class="tab-pane fade">
    <ul class="nav nav-tabs">
        @foreach($nhomXml3 as $i => $ma_nhom)
            <li class="{{ $i === 0 ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_xml3_{{ $i }}">
                    Nhóm: {{ config('__tech.pl6_4210')[$ma_nhom] }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($nhomXml3 as $i => $ma_nhom)
            <div id="tab_xml3_{{ $i }}"
                 class="tab-pane fade xml3176-lazy {{ $i === 0 ? 'in active' : '' }}"
                 data-url="{{ route('bhyt.xml3176.detail-xml.rows', ['ma_lk' => $xml1->ma_lk, 'xml' => 'XML3']) }}?nhom={{ urlencode($ma_nhom) }}">
                <div class="panel panel-default">
                    <div class="panel-body table-responsive">
                        <i class="fa fa-spinner fa-spin"></i> Đang tải…
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
