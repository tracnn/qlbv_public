@php
    $groupedErrors = $xml1->Qd130XmlErrorResult->sortBy('xml')->groupBy('xml');
@endphp

<div id="menu-xml-errors" class="tab-pane fade">

    @if($groupedErrors->isNotEmpty())
    <ul class="nav nav-tabs">
        @foreach($groupedErrors as $xml => $errors)
            <li class="{{ $loop->first ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_{{ $xml }}">
                    Lỗi: {{ $xml }}
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($groupedErrors as $xml => $errors)
            <div id="tab_{{ $xml }}" class="tab-pane fade {{ $loop->first ? 'in active' : '' }}">
                <table id="xmlErrorChecks_{{ $xml }}" class="table table-hover responsive datatable" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>Error Code</th>
                            <th>XML</th>
                            <th>STT</th>
                            <th>Ngày y lệnh</th>
                            <th>Ngày kết quả</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($errors as $error)
                        <tr>
                            <td>{{ $error->Qd130XmlErrorCatalog->error_name }}</td>
                            <td>{{ $error->xml }}</td>
                            <td>{{ $error->stt }}</td>
                            <td>{{ strtodatetime($error->ngay_yl) }}</td>
                            <td>{{ strtodatetime($error->ngay_kq) }}</td>
                            <td>{{ $error->description }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
    @else
    <p>Không có lỗi XML nào.</p>
    @endif

</div>