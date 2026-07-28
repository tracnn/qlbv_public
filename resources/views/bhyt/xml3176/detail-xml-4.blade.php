@php
    $cauHinhXml4 = App\Services\Xml3176\Xml3176DetailTabs::BANG_NHIEU_DONG['XML4'];
    $nhomXml4 = App\Services\Xml3176\Xml3176DetailTabs::khoaNhom($dsNhom['XML4'], $cauHinhXml4['cat']);
@endphp

<div id="menu4" class="tab-pane fade">
    <ul class="nav nav-tabs">
        @foreach($nhomXml4 as $i => $ngay_kq)
            <li class="{{ $i === 0 ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_xml4_{{ $i }}">
                    Ngày: {{ strtodate($ngay_kq) }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($nhomXml4 as $i => $ngay_kq)
            <div id="tab_xml4_{{ $i }}"
                 class="tab-pane fade xml3176-lazy {{ $i === 0 ? 'in active' : '' }}"
                 data-url="{{ route('bhyt.xml3176.detail-xml.rows', ['ma_lk' => $xml1->ma_lk, 'xml' => 'XML4']) }}?nhom={{ urlencode($ngay_kq) }}">
                <div class="panel panel-default">
                    <div class="panel-body table-responsive">
                        <i class="fa fa-spinner fa-spin"></i> Đang tải…
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
