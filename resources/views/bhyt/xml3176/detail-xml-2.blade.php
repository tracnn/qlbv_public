@php
    $groupedDataXml2 = $xml1->Xml3176Xml2->groupBy(function ($item) {
        return substr($item->ngay_yl, 0, 8); // Group by YYYYMMDD
    })->sortBy(function ($group, $key) {
        return $key; // Sort by the grouped keys (YYYYMMDD)
    });
@endphp

<div id="menu2" class="tab-pane fade">
    <ul class="nav nav-tabs">
        @foreach($groupedDataXml2 as $ngay_yl => $group)
            <li class="{{ $loop->first ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_ngay_{{ $ngay_yl }}">
                    Ngày: {{ strtodate($ngay_yl) }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($groupedDataXml2 as $ngay_yl => $group)
            <div id="tab_ngay_{{ $ngay_yl }}" class="tab-pane fade {{ $loop->first ? 'in active' : '' }}">
                <div class="panel panel-default">
                    <div class="panel-body table-responsive">
                        <table class="table table-hover responsive datatable" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã thuốc</th>
                                    <th>Tên thuốc</th>
                                    <th>Hàm lượng</th>
                                    <th>Số đăng ký</th>
                                    <th>Giá</th>
                                    <th>TT thầu</th>
                                    <th>SL</th>
                                    <th>Khoa</th>
                                    <th>Bác sĩ</th>
                                    <th>Mã bệnh</th>
                                    <th>Ngày YL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group as $value_xml2)
                                @php
                                    $errorDescriptions = $value_xml2
                                    ->errorResult()
                                    ->where('stt', $value_xml2->stt)
                                    ->pluck('description')
                                    ->implode('; ');
                                @endphp
                                <tr @if($errorDescriptions) class="highlight-red" data-toggle="tooltip" title="{{ $errorDescriptions }}" @endif>
                                    <td align="right">{{ $value_xml2->stt }}</td>
                                    <td>{{ $value_xml2->ma_thuoc }}</td>
                                    <td>{{ $value_xml2->ten_thuoc }}</td>
                                    <td>{{ $value_xml2->ham_luong }}</td>
                                    <td>{{ $value_xml2->so_dang_ky }}</td>
                                    <td align="right">{{ number_format($value_xml2->don_gia, 2) ?: '' }}</td>
                                    <td>{{ $value_xml2->tt_thau }}</td>
                                    <td align="right">{{ number_format($value_xml2->so_luong, 2) ?: '' }}</td>
                                    <td>{{ $value_xml2->ma_khoa }}</td>
                                    <td>{{ $value_xml2->ma_bac_si }}</td>
                                    <td>{{ $value_xml2->ma_benh }}</td>
                                    <td>{{ strtodatetime($value_xml2->ngay_yl) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>