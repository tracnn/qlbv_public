@extends('adminlte::page')

@section('title', 'Danh sách hồ sơ XML')

@section('content_header')
  <h1>
    Danh sách
    <small>hồ sơ XML</small>
  </h1>
{{ Breadcrumbs::render('bhyt.index') }}
@stop

@push('after-styles')
<style>
    .modal-body {
        max-height: 500px; /* Set a fixed height for the modal body */
        overflow-y: auto; /* Enable vertical scrolling */
        overflow-x: auto; /* Enable horizontal scrolling */
    }
    .table-responsive {
        display: block;
        width: 100%;
        overflow-x: auto;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    .highlight-row {
        background-color: #f0f8ff !important; /* Light blue background for highlighted row */
    }
    .job-status-icon {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
        background: white;
        padding: 10px;
        border-radius: 50%;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
@include('bhyt.qd130.partials.search')

<button id="bulk-action-btn" class="btn btn-primary" disabled>
    <i class="fa fa-download" aria-hidden="true"></i> Xuất XML4750
</button>

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="xml-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã điều trị</th>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>Export</th>
                    <th>Mã BN</th>
                    <th>Họ tên</th>
                    <th>Mã thẻ</th>
                    <th>Ngày sinh</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Ngày t.toán</th>
                    <th>Ngày gửi</th>
                    <th>Ngày sửa</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div id="job-status-icon" class="job-status-icon" style="display: none;">
    <i class="fa fa-spinner fa-spin"></i>
    <span id="job-count" style="display: none;"></span>
</div>

<!-- Modal hiển thị chi tiết -->
<div id="infoModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xxl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label>Chi tiết hồ sơ</label>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalContent" class="table-responsive">
                    <!-- Nội dung chi tiết sẽ được tải ở đây -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại
    var table = null;
    var selectedRecords = [];

    function fetchData(startDate, endDate) {
        // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        table = $('#xml-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "ajax": {
                url: "{{ route('bhyt.qd130.fetch-data') }}",
                data: function(d) {
                    d.date_from = startDate;
                    d.date_to = endDate;
                    d.date_type = $('#date_type').val();
                    d.treatment_code = $('#treatment_code').val();
                    d.xml_filter_status = $('#xml_filter_status').val();
                    d.qd130_xml_error_catalog = $('#qd130_xml_error_catalog').val();
                    d.hein_card_filter = $('#hein_card_filter').val();
                    d.payment_date_filter = $('#payment_date_filter').val();
                    d.treatment_type_fillter = $('#treatment_type_fillter').val();
                    d.xml_export_status = $('#xml_export_status').val();
                },
                beforeSend: function(xhr) {
                    currentAjaxRequest = xhr;
                },
                complete: function(xhr, status) {
                    currentAjaxRequest = null;
                    //populateErrorCodeDropdown(xhr.responseJSON.errorCodes);
                    applySelectedCheckboxes(); // Áp dụng lại trạng thái checkbox
                    toggleBulkActionBtn();
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            },
            "lengthMenu": [
                [10, 25, 50, 100, 200, 500, 1000, 2000], 
                [10, 25, 50, 100, 200, 500, 1000, 2000]
            ],
            "columns": [
                { "data": "ma_lk" },
                { 
                    "data": null, 
                    "render": function (data, type, row) {
                        return '<input type="checkbox" class="row-select" value="' + row.ma_lk + '">';
                    }, 
                    "orderable": false,
                    "searchable": false 
                },
                { "data": "exported_at", "orderable": false, "searchable": false },
                { "data": "ma_bn" },
                { "data": "ho_ten" },
                { "data": "ma_the_bhyt" },
                { "data": "ngay_sinh" },
                { "data": "ngay_vao" },
                { "data": "ngay_ra" },
                { "data": "ngay_ttoan" },
                { "data": "created_at" },
                { "data": "updated_at" },
                { "data": "action" },
            ],
        });

        table.ajax.reload();
        
        // Kiểm tra trạng thái job
        checkJobStatus();
    }

    function deleteXML(ma_lk) {
        if (confirm('Chắc chắn xóa?')) {
            $.ajax({
                url: '{{ route('bhyt.qd130.delete-xml', ['ma_lk' => '']) }}/' + ma_lk,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        table.ajax.reload();
                        // Kiểm tra trạng thái job
                        checkJobStatus();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(response) {
                    alert('Có lỗi xảy ra, vui lòng thử lại.');
                }
            });
        }
    }

    $(document).ready(function() {
        $('.select2').select2();
        $('#select-all').on('click', function(){
            var rows = table.rows({ 'search': 'applied' }).nodes();
            $('input[type="checkbox"]', rows).prop('checked', this.checked);
            updateSelectedRecords();
            toggleBulkActionBtn();
        });

        $('#xml-list tbody').on('change', '.row-select', function() {
            updateSelectedRecords();
            if (!this.checked) {
                $('#select-all').prop('checked', false);
            }
            toggleBulkActionBtn();
        });

        $('#xml-list tbody').on('dblclick', 'tr', function () {
            let data = table.row(this).data();
            // Remove highlight from any previously highlighted row
            $('#xml-list tbody tr').removeClass('highlight-row');
            // Add highlight to the current row
            $(this).addClass('highlight-row');
            // Tải chi tiết hồ sơ bằng AJAX
            $.ajax({
                url: '{{ route('bhyt.qd130.detail-xml', '') }}/' + data.ma_lk,
                type: 'GET',
                success: function(response) {
                    $('#infoModal').modal('show');
                    $('#modalContent').html(response);
                    initializeModalDataTables();
                    // Kiểm tra trạng thái job
                    checkJobStatus();
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            });
        });

        $('#bulk-action-btn').on('click', function(){
            var selectedRecords = [];
            $('.row-select:checked').each(function() {
                selectedRecords.push($(this).val());
            });
            
            if (selectedRecords.length > 0) {
                exportSelectedRecordsToXml(selectedRecords);
            } else {
                alert('Vui lòng chọn ít nhất một hồ sơ.');
            }
        });

        $('#export_qd130_xml_error').click(function() {
            var dateRange = $('#date_range').data('daterangepicker');

            var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
            var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');
            var xml_filter_status = $('#xml_filter_status').val();
            var date_type = $('#date_type').val();
            var qd130_xml_error_catalog = $('#qd130_xml_error_catalog').val();
            
            // Tạo URL với các tham số query
            var href = '{{ route("bhyt.qd130.export-qd130-xml-errors") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate,
                'xml_filter_status': xml_filter_status,
                'date_type': date_type,
                'qd130_xml_error_catalog': qd130_xml_error_catalog
            });

            // Chuyển hướng tới URL với các tham số
            window.location.href = href;
        });

        $('#export_xlsx').click(function() {
            var dateRange = $('#date_range').data('daterangepicker');

            var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
            var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');
            var xml_filter_status = $('#xml_filter_status').val();
            var date_type = $('#date_type').val();
            var qd130_xml_error_catalog = $('#qd130_xml_error_catalog').val();
            var xml_export_status = $('#xml_export_status').val();
            
            // Tạo URL với các tham số query
            var href = '{{ route("bhyt.qd130.export-qd130-xml-xlsx") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate,
                'xml_filter_status': xml_filter_status,
                'date_type': date_type,
                'qd130_xml_error_catalog': qd130_xml_error_catalog,
                'xml_export_status': xml_export_status
            });

            // Chuyển hướng tới URL với các tham số
            window.location.href = href;
        });
    });

    function updateSelectedRecords() {
        selectedRecords = [];
        $('.row-select:checked').each(function() {
            selectedRecords.push($(this).val());
        });
    }

    function applySelectedCheckboxes() {
        var rows = table.rows().nodes();
        $('input[type="checkbox"]', rows).each(function() {
            if (selectedRecords.includes($(this).val())) {
                $(this).prop('checked', true);
            }
        });
    }

    function toggleBulkActionBtn() {
        if ($('.row-select:checked').length > 0) {
            $('#bulk-action-btn').prop('disabled', false);
        } else {
            $('#bulk-action-btn').prop('disabled', true);
        }
    }

    function exportSelectedRecordsToXml(selectedRecords) {
        $.ajax({
            url: '{{ route("bhyt.qd130.export-xml") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                records: selectedRecords
            },
            success: function(response) {
                if (response.success) {
                    //console.log(response.records);
                    table.ajax.reload();
                    window.location.href = response.file; // Chuyển hướng để tải file
                } else {
                    alert('Có lỗi xảy ra, vui lòng thử lại.');
                }
            },
            error: function(xhr, error, code) {
                if (xhr.status === 403) {
                    alert('Bạn không có quyền thực hiện chức năng này.');
                } else {
                    alert('Có lỗi xảy ra, vui lòng thử lại.');
                }
            }
        });
    }

    function initializeModalDataTables() {
        $('#thuocvt').DataTable();
        $('#dvkt').DataTable();
        $('#cls').DataTable();
        $('#dienbien').DataTable();
        $('#checkHeinCard').DataTable();
        $('#xmlErrorChecks').DataTable();
    }

    function checkJobStatus() {
        $.ajax({
            url: '{{ route('bhyt.qd130.jobs.status') }}',
            type: 'GET',
            success: function(response) {
                if (response.jobs_count > 0) {
                    $('#job-status-icon').show();
                    $('#job-count').text(response.jobs_count).show(); // Hiển thị số lượng job
                } else {
                    $('#job-status-icon').hide();
                    $('#job-count').hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching job status:', error);
            }
        });
    }
</script>
@endpush