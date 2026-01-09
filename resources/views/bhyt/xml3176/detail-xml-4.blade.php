@php
    $groupedDataXml4 = $xml1->Xml3176Xml4->groupBy(function ($item) {
        return substr($item->ngay_kq, 0, 8); // Group by YYYYMMDD
    })->sortBy(function ($group, $key) {
        return $key; // Sort by the grouped keys (YYYYMMDD)
    });
@endphp

<div id="menu4" class="tab-pane fade">
    <ul class="nav nav-tabs">
        @foreach($groupedDataXml4 as $ngay_kq => $group)
            <li class="{{ $loop->first ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_kq_{{ $ngay_kq }}">
                    Ngày: {{ strtodate($ngay_kq) }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($groupedDataXml4 as $ngay_kq => $group)
            <div id="tab_kq_{{ $ngay_kq }}" class="tab-pane fade {{ $loop->first ? 'in active' : '' }}">
                <div class="panel panel-default">
                    <div class="panel-body table-responsive">
                        <table class="table table-hover responsive datatable" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã DV</th>
                                    <th>Mã chỉ số</th>
                                    <th>Tên chỉ số</th>
                                    <th>Giá trị</th>
                                    <th>Mã máy</th>
                                    <th>Kết luận</th>
                                    <th>Ngày KQ</th>
                                    <th>BS đọc KQ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group as $value_xml4)
                                @php
                                    $errorDescriptions = $value_xml4
                                    ->errorResult()
                                    ->where('stt', $value_xml4->stt)
                                    ->pluck('description')
                                    ->implode('; ');
                                @endphp
                                <tr @if($errorDescriptions) class="highlight-red" data-toggle="tooltip" title="{{ $errorDescriptions }}" @endif>
                                    <td align="right">{{ $value_xml4->stt }}</td>
                                    <td>{{ $value_xml4->ma_dich_vu }}</td>
                                    <td>{{ $value_xml4->ma_chi_so }}</td>
                                    <td>{{ $value_xml4->ten_chi_so }}</td>
                                    <td>{{ $value_xml4->gia_tri }}</td>
                                    <td>{{ $value_xml4->ma_may }}</td> 
                                    <td>{{ $value_xml4->ket_luan }}</td>  
                                    <td>{{ strtodatetime($value_xml4->ngay_kq) }}</td>
                                    <td>{{ $value_xml4->ma_bs_doc_kq }}</td>         
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