@php
    $groupedErrors = $xml1->Qd130XmlErrorResult->sortBy('xml')->groupBy('xml');
@endphp

<div id="menu-xml-errors" class="tab-pane fade">

    @if($groupedErrors->isNotEmpty())
    <ul class="nav nav-tabs">
        @foreach($groupedErrors as $xml => $errors)
            @php
                $hasCriticalError = $errors->contains(function($error) {
                    return $error->critical_error;
                });
            @endphp
            <li class="{{ $loop->first ? 'active' : '' }}">
                <a data-toggle="tab" href="#tab_{{ $xml }}">
                    Lỗi: {{ $xml }}
                    @if($hasCriticalError)
                        <i class="fa fa-exclamation-triangle text-danger" aria-hidden="true" title="Critical Error"></i>
                    @else
                        <i class="fa fa-exclamation-triangle text-primary" aria-hidden="true" title="Warning Error"></i>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach($groupedErrors as $xml => $errors)
            <div id="tab_{{ $xml }}" class="tab-pane fade {{ $loop->first ? 'in active' : '' }}">
                <table id="xmlErrorChecks_{{ $xml }}" class="table table-hover responsive datatable-xml-errors" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>Error Code</th>
                            <th>Loại</th>
                            <th>XML</th>
                            <th>STT</th>
                            <th>Ngày y lệnh</th>
                            <th>Ngày kết quả</th>
                            <th>Description</th>
                            <th>Hướng xử lý</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($errors as $error)
                        <tr>
                            <td>{{ $error->Qd130XmlErrorCatalog->error_name }}</td>
                            <td>
                                @if($error->critical_error)
                                <i class="fa fa-exclamation-triangle text-danger" aria-hidden="true" title="Critical Error"></i>
                                @else
                                <i class="fa fa-exclamation-triangle text-primary" aria-hidden="true" title="Warning Error"></i>
                                @endif
                            </td>
                            <td>{{ $error->xml }}</td>
                            <td>{{ $error->stt }}</td>
                            <td>{{ strtodatetime($error->ngay_yl) }}</td>
                            <td>{{ strtodatetime($error->ngay_kq) }}</td>
                            <td>{{ $error->description }}</td>
                            <td>
                                @php
                                    $placeholders = [
                                        'ma_dieu_tri' => $error->ma_lk ?? 'N/A',
                                    ];

                                    // Sử dụng helper getFormattedSuggestion() để lấy và thay thế nội dung
                                    $suggestion = getFormattedSuggestion($error->error_code, $placeholders);
                                @endphp
                                {{ $suggestion }}
                            </td>
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
<!-- DataTables initialization script -->
<script>
    $(document).ready(function() {
        // Khởi tạo DataTables cho tất cả các bảng có class datatable-xml-errors
        $('.datatable-xml-errors').each(function() {
            $(this).DataTable({
                responsive: true,
                autoWidth: false,
                paging: false,
                searching: true,
                ordering: true,
            });
        });
    });
</script>
