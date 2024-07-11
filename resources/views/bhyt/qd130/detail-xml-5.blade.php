@php
    $groupedDataXml5 = $xml1->Qd130Xml5->sortBy('thoi_diem_dbls')->groupBy(function ($item) {
        return substr($item->thoi_diem_dbls, 0, 8); // Group by YYYYMMDD
    });
@endphp

<div id="menu5" class="tab-pane fade">
    <ul class="nav nav-tabs">
        @foreach($groupedDataXml5 as $date => $group)
            <li class="{{ $loop->first ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_dienbien_{{ $date }}">
                    Ngày: {{ strtodate($date) }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($groupedDataXml5 as $date => $group)
            <div id="tab_dienbien_{{ $date }}" class="tab-pane fade {{ $loop->first ? 'in active' : '' }}">
                <div class="panel panel-default">
                    <div class="panel-body table-responsive">
                        <table class="table table-hover responsive datatable" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Diễn biến</th>
                                    <th>Hội chẩn</th>
                                    <th>Phẫu thuật</th>
                                    <th>Ngày YL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group as $value_xml5)
                                @php
                                    $errorDescriptions = $value_xml5
                                    ->errorResult()
                                    ->where('stt', $value_xml5->stt)
                                    ->pluck('description')
                                    ->implode('; ');
                                @endphp
                                <tr @if($errorDescriptions) class="highlight-red" data-toggle="tooltip" title="{{ $errorDescriptions }}" @endif>
                                <tr>
                                    <td>{{ $value_xml5->stt }}</td>
                                    <td>{{ $value_xml5->dien_bien_ls }}</td>
                                    <td>{{ $value_xml5->hoi_chan }}</td>
                                    <td>{{ $value_xml5->phau_thuat }}</td>
                                    <td>{{ strtodatetime($value_xml5->thoi_diem_dbls) }}</td>
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