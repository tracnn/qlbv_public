@php
    $groupedDataXml3 = $xml1->Qd130Xml3->groupBy('ma_nhom');
@endphp
<div id="menu3" class="tab-pane fade">
    <ul class="nav nav-tabs">
        @foreach($groupedDataXml3 as $ma_nhom => $group)
            <li class="{{ $loop->first ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_{{ $ma_nhom }}">
                    Nhóm: {{ config('__tech.pl6_4210')[$ma_nhom] }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($groupedDataXml3 as $ma_nhom => $group)
            <div id="tab_{{ $ma_nhom }}" class="tab-pane fade {{ $loop->first ? 'in active' : '' }}">
                <div class="panel panel-default">
                    <div class="panel-body table-responsive">
                        <table class="table table-hover responsive datatable" cellspacing="0" width="100%">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Mã DV</th>
                                    <th>Mã VT</th>
                                    <th>Tên DV</th>
                                    <th>Tên VT</th>
                                    <th>Nhóm</th>
                                    <th>ĐVT</th>
                                    <th>Trần BHTT</th>
                                    <th>SL</th>
                                    <th>Giá</th>
                                    <th>TT thầu</th>
                                    <th>Khoa</th>
                                    <th>Bác sĩ</th>
                                    <th>Ngày YL</th>
                                    <th>Ngày KQ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($group as $value_xml3)
                                <tr>
                                    <td align="right">{{ $value_xml3->stt }}</td>
                                    <td>{{ $value_xml3->ma_dich_vu }}</td>
                                    <td>{{ $value_xml3->ma_vat_tu }}</td>
                                    <td>{{ $value_xml3->ten_dich_vu }}</td>
                                    <td>{{ $value_xml3->ten_vat_tu }}</td>
                                    <td>{{ config('__tech.pl6_4210')[$value_xml3->ma_nhom] }}</td>
                                    <td>{{ $value_xml3->don_vi_tinh }}</td>
                                    <td align="right">{{ $value_xml3->t_trantt ? number_format($value_xml3->t_trantt, 2) : '' }}</td>
                                    <td align="right">{{ number_format($value_xml3->so_luong, 2) ?: '' }}</td>
                                    <td align="right">{{ number_format($value_xml3->don_gia_bh, 2) ?: '' }}</td>
                                    <td>{{ $value_xml3->tt_thau }}</td>
                                    <td>{{ $value_xml3->ma_khoa }}</td>
                                    <td>{{ $value_xml3->ma_bac_si }}</td>
                                    <td>{{ strtodatetime($value_xml3->ngay_yl) }}</td>
                                    <td>{{ strtodatetime($value_xml3->ngay_kq) }}</td>
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