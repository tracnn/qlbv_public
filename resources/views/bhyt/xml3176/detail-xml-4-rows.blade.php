{{-- Mot trang cua mot nhom XML4 (chi so CLS). --}}
<table class="table table-hover responsive" cellspacing="0" width="100%">
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
        @foreach($rows as $value_xml4)
        @php
            $errorDescriptions = $chiMucLoi->moTa('XML4', $value_xml4->stt);
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

@include('bhyt.xml3176.detail-xml-phan-trang')
