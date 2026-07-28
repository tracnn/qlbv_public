{{-- Mot trang cua mot nhom XML3 (DVKT). Nhom theo ma_nhom nen day la bang de vuot
     100 dong nhat - cho thuc su can phan trang. --}}
<table class="table table-hover responsive" cellspacing="0" width="100%">
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
            <th>Mã giường</th>
            <th>Mã máy</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $value_xml3)
        @php
            $errorDescriptions = $chiMucLoi->moTa('XML3', $value_xml3->stt);
        @endphp
        <tr @if($errorDescriptions) class="highlight-red" data-toggle="tooltip" title="{{ $errorDescriptions }}" @endif>
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
            <td>{{ $value_xml3->ma_giuong }}</td>
            <td>{{ $value_xml3->ma_may }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@include('bhyt.xml3176.detail-xml-phan-trang')
