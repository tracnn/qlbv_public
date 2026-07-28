@php
    $cauHinhXml5 = App\Services\Xml3176\Xml3176DetailTabs::BANG_NHIEU_DONG['XML5'];
    $nhomXml5 = App\Services\Xml3176\Xml3176DetailTabs::khoaNhom($dsNhom['XML5'], $cauHinhXml5['cat']);
@endphp

<div id="menu5" class="tab-pane fade">
    <ul class="nav nav-tabs">
        @foreach($nhomXml5 as $i => $date)
            <li class="{{ $i === 0 ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_xml5_{{ $i }}">
                    Ngày: {{ strtodate($date) }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($nhomXml5 as $i => $date)
            <div id="tab_xml5_{{ $i }}"
                 class="tab-pane fade xml3176-lazy {{ $i === 0 ? 'in active' : '' }}"
                 data-url="{{ route('bhyt.xml3176.detail-xml.rows', ['ma_lk' => $xml1->ma_lk, 'xml' => 'XML5']) }}?nhom={{ urlencode($date) }}">
                <div class="panel panel-default">
                    <div class="panel-body table-responsive">
                        <i class="fa fa-spinner fa-spin"></i> Đang tải…
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
