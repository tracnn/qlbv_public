{{-- Mot trang cua mot nhom XML5 (dien bien lam sang). --}}
<table class="table table-hover responsive" cellspacing="0" width="100%">
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
        @foreach($rows as $value_xml5)
        @php
            $errorDescriptions = $chiMucLoi->moTa('XML5', $value_xml5->stt);
        @endphp
        <tr @if($errorDescriptions) class="highlight-red" data-toggle="tooltip" title="{{ $errorDescriptions }}" @endif>
            <td>{{ $value_xml5->stt }}</td>
            <td>{{ $value_xml5->dien_bien_ls }}</td>
            <td>{{ $value_xml5->hoi_chan }}</td>
            <td>{{ $value_xml5->phau_thuat }}</td>
            <td>{{ strtodatetime($value_xml5->thoi_diem_dbls) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@include('bhyt.xml3176.detail-xml-phan-trang')
