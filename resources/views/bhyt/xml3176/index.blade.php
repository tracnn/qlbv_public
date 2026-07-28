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
@include('bhyt.xml3176.partials.search')

<button id="bulk-action-btn" class="btn btn-primary" disabled>
    <i class="fa fa-download" aria-hidden="true"></i> Xuất XML3176
</button>
<!-- Button to trigger the modal -->
<button id="openDownloadModalBtn" class="btn btn-primary">
    <i class="fa fa-download" aria-hidden="true"></i> Tải xuống 7980a/19/20/21
</button>

<!-- Modal chứa các nút tải xuống -->
<div class="modal fade" id="downloadModal" tabindex="-1" role="dialog" aria-labelledby="downloadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="downloadModalLabel">Tùy chọn tải xuống</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Nút 7980a -->
                <button id="bulk-7980a-btn" class="btn btn-primary">
                    <i class="fa fa-download" aria-hidden="true"></i> Hồ sơ 79/80a
                </button>

                <!-- Thêm các nút khác tương tự -->
                <button id="bulk-19-btn" class="btn btn-info">
                    <i class="fa fa-download" aria-hidden="true"></i> Hồ sơ 19/BHYT
                </button>

                <button id="bulk-20-btn" class="btn btn-info">
                    <i class="fa fa-download" aria-hidden="true"></i> Hồ sơ 20/BHYT
                </button>
                <button id="bulk-21-btn" class="btn btn-info">
                    <i class="fa fa-download" aria-hidden="true"></i> Hồ sơ 21/BHYT
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Cảnh báo bộ lọc không áp dụng được (drill-down từ dashboard XML3176) -->
<div id="xml3176-filter-warning" class="alert alert-danger" style="display: none; margin-top: 10px;"></div>

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="xml-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>Mã điều trị</th>
                    <th><input type="checkbox" id="select-all"></th>
                    <th>Exp</th>
                    <th>Sub</th>
                    <th>Mã BN</th>
                    <th>Họ tên</th>
                    <th>Mã thẻ</th>
                    <th>Ngày sinh</th>
                    <th>Ngày vào</th>
                    <th>Ngày ra</th>
                    <th>Ngày t.toán</th>
                    <th>Ngày gửi</th>
                    <th>Ngày sửa</th>
                    <th>Ký XML</th>
                    <th>Người nhập</th>
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

    // Khoang ngay dung cho lan tai hien tai. Phai o pham vi module chu khong phai
    // tham so cua fetchData(): DataTable chi con duoc dung MOT lan, nen closure
    // "data" ben trong no phai doc duoc gia tri moi nhat o cac lan tai sau.
    var xml3176Range = { from: null, to: null };

    // ── Bộ lọc từ URL (phục vụ drill-down từ dashboard XML3176) ──────────────
    // QUAN TRỌNG: lần gọi fetchData() ĐẦU TIÊN không xuất phát từ
    // $(document).ready của file này, mà từ partials.load_data_button
    // (validateAndFetchData() được gọi tự động khi trang tải xong). Script
    // đó được render/đăng ký ready TRƯỚC script này (nó nằm trong
    // partials.search, được include phía trên), nên $(document).ready ở
    // đây luôn chạy SAU khi lần fetch đầu tiên đã xảy ra — đặt code prefill
    // trong $(document).ready của file này sẽ luôn bị trễ một nhịp.
    // Giải pháp: đọc query string ngay khi script này được parse (không đợi
    // ready), rồi áp dụng thật sự bên TRONG fetchData() ở lần gọi đầu tiên -
    // như vậy áp dụng đúng bất kể fetchData() được gọi từ đâu/khi nào.
    var xml3176UrlFilters = (function () {
        if (!window.URLSearchParams) { return null; }
        var qs = new URLSearchParams(window.location.search);
        return qs.toString() ? qs : null;
    })();

    function fetchData(startDate, endDate) {
        // Áp dụng bộ lọc từ URL (nếu có) - chỉ áp dụng đúng 1 lần, cho lần
        // gọi đầu tiên của fetchData() sau khi trang tải xong.
        if (xml3176UrlFilters) {
            var qs = xml3176UrlFilters;
            xml3176UrlFilters = null; // đảm bảo không áp dụng lại ở các lần sau

            // Các select đơn giản: tên param trùng id element
            ['date_type', 'xml_filter_status', 'xml3176_error_catalog', 'xml_export_status',
             'xml_submit_status', 'xml_sign_status', 'imported_by', 'treatment_type_fillter',
             'hein_card_filter', 'payment_date_filter'].forEach(function (key) {
                if (qs.has(key)) {
                    $('#' + key).val(qs.get(key));
                }
            });

            // Khoảng ngày: dashboard gửi 'YYYY-MM-DD'
            if (qs.has('date_from') && qs.has('date_to')) {
                var urlStart = moment(qs.get('date_from'), 'YYYY-MM-DD').startOf('day');
                var urlEnd = moment(qs.get('date_to'), 'YYYY-MM-DD').endOf('day');

                // Cập nhật daterangepicker để hiển thị đúng trên UI (nếu đã
                // được khởi tạo - trong luồng tải trang bình thường thì có,
                // vì partials.date_range khởi tạo picker trước khi
                // partials.load_data_button tự gọi fetchData()).
                var picker = $('#date_range').data('daterangepicker');
                if (picker) {
                    picker.setStartDate(urlStart);
                    picker.setEndDate(urlEnd);
                }

                // Ghi đè trực tiếp tham số dùng cho lần tải NÀY, không phụ
                // thuộc việc picker có sẵn sàng đúng lúc hay không (tránh
                // "stale date" - lần fetch đầu tiên dùng nhầm ngày mặc định).
                startDate = urlStart.format('YYYY-MM-DD HH:mm:ss');
                endDate = urlEnd.format('YYYY-MM-DD HH:mm:ss');
            }
        }

        // Ghi sau khoi xml3176UrlFilters vi khoi do co the ghi de startDate/endDate.
        xml3176Range.from = startDate;
        xml3176Range.to = endDate;

        // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        // DataTable voi serverSide TU GOI ajax mot lan khi khoi tao. Truoc day code
        // con goi table.ajax.reload() ngay sau -> hai request nang chay chong nhau
        // moi lan tai. Gio chi dung bang o lan dau, cac lan sau chi reload.
        if (table) {
            table.ajax.reload();
            checkJobStatus();
            return;
        }

        table = $('#xml-list').DataTable({
            "processing": true,
            "serverSide": true,
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: "{{ route('bhyt.xml3176.fetch-data') }}",
                data: function(d) {
                    d.date_from = xml3176Range.from;
                    d.date_to = xml3176Range.to;
                    d.date_type = $('#date_type').val();
                    d.treatment_code = $('#treatment_code').val();
                    d.xml_filter_status = $('#xml_filter_status').val();
                    d.xml3176_error_catalog = $('#xml3176_error_catalog').val();
                    d.hein_card_filter = $('#hein_card_filter').val();
                    d.payment_date_filter = $('#payment_date_filter').val();
                    d.treatment_type_fillter = $('#treatment_type_fillter').val();
                    d.xml_export_status = $('#xml_export_status').val();
                    d.patient_code = $('#patient_code').val();
                    d.imported_by = $('#imported_by').val();
                    d.xml_submit_status = $('#xml_submit_status').val();
                    d.xml_sign_status = $('#xml_sign_status').val();
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
                { "data": "submitted_at", "orderable": false, "searchable": false },
                { "data": "ma_bn" },
                { "data": "ho_ten" },
                { "data": "ma_the_bhyt" },
                { "data": "ngay_sinh" },
                { "data": "ngay_vao" },
                { "data": "ngay_ra" },
                { "data": "ngay_ttoan" },
                { "data": "created_at" },
                { "data": "updated_at" },
                { "data": "is_signed", "orderable": false, "searchable": false },
                { "data": "imported_by", "orderable": false, "searchable": false },
                { "data": "action" },
            ],
        });

        // Khong goi table.ajax.reload() o day: DataTable() ben tren da tu gui
        // request khoi tao roi.

        // Kiểm tra trạng thái job
        checkJobStatus();
    }

    // ── Hỗ trợ drill-down từ dashboard XML3176 ───────────────────────────────
    // Nạp lại bảng để áp bộ lọc vừa được set (dùng cho các danh mục nạp bất
    // đồng bộ - xem partials/xml3176_error_catalog.blade.php).
    // Callback "data" của DataTables đọc giá trị các select tại thời điểm gửi
    // request, nên chỉ cần ajax.reload() là bộ lọc mới được áp - không cần
    // gọi lại fetchData() (tránh dựng lại toàn bộ DataTable).
    // Nếu bảng chưa kịp khởi tạo thì KHÔNG cần làm gì: lần fetchData() đầu
    // tiên diễn ra sau đó sẽ tự đọc giá trị select đã set.
    window.xml3176ReloadTable = function () {
        if (table) {
            table.ajax.reload();
        }
    };

    // Hiện cảnh báo nhìn thấy được khi một bộ lọc từ URL KHÔNG áp dụng được.
    // Im lặng bỏ qua là nguy hiểm: danh sách sẽ hiện nhiều dòng hơn con số
    // người dùng đã bấm trên dashboard.
    window.xml3176ShowFilterWarning = function (message) {
        $('#xml3176-filter-warning').html(
            '<i class="fa fa-exclamation-triangle" aria-hidden="true"></i> ' + message
        ).show();
    };

    function deleteXML(ma_lk) {
        Swal.fire({
            title: 'Bạn có chắc chắn muốn xóa?',
            icon: 'warning',
            showCancelButton: true,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route('bhyt.xml3176.delete-xml', ['ma_lk' => '']) }}/' + ma_lk,
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
                        Swal.fire('Có lỗi xảy ra', 'Vui lòng thử lại.', 'error');
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        setInterval(checkJobStatus, 5000);
        // Mở modal khi người dùng bấm vào nút "Tải xuống"
        $('#openDownloadModalBtn').on('click', function() {
            $('#downloadModal').modal('show'); // Hiển thị modal
        });
        $('.select2').select2({
            width: '100%' // Đặt chiều rộng của Select2 là 100%
        });
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
            $("#loading_center").show();
            let data = table.row(this).data();
            // Remove highlight from any previously highlighted row
            $('#xml-list tbody tr').removeClass('highlight-row');
            // Add highlight to the current row
            $(this).addClass('highlight-row');
            // Show loading spinner
            $("#loading_center").show();
            // Tải chi tiết hồ sơ bằng AJAX
            $.ajax({
                url: '{{ route('bhyt.xml3176.detail-xml', '') }}/' + data.ma_lk,
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
                },
                complete: function() {
                    // Hide loading spinner
                    $("#loading_center").hide();
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

        $('#bulk-7980a-btn').on('click', function(){
            var dateRange = $('#date_range').data('daterangepicker');

            var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
            var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');
            var xml_filter_status = $('#xml_filter_status').val();
            var date_type = $('#date_type').val();
            var xml3176_error_catalog = $('#xml3176_error_catalog').val();
            var payment_date_filter = $('#payment_date_filter').val();
            var treatment_code = $('#treatment_code').val();
            var imported_by = $('#imported_by').val();
            var xml_submit_status = $('#xml_submit_status').val();
            var xml_sign_status = $('#xml_sign_status').val();
            // Xml3176Xml7980aExport CO doc tham so nay. Truoc day nut khong gui, nen
            // loc "Da xuat XML" tren man hinh xong tai 79/80a van nhan ca ho so chua
            // xuat - sai am tham, khong bao loi.
            var xml_export_status = $('#xml_export_status').val();

            // Tạo URL với các tham số query
            var href = '{{ route("bhyt.xml3176.export-7980a-data") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate,
                'xml_filter_status': xml_filter_status,
                'date_type': date_type,
                'xml3176_error_catalog': xml3176_error_catalog,
                'payment_date_filter': payment_date_filter,
                'xml_export_status': xml_export_status,
                'treatment_code': treatment_code,
                'imported_by': imported_by,
                'xml_submit_status': xml_submit_status,
                'xml_sign_status': xml_sign_status
            });

            // Chuyển hướng tới URL với các tham số
            window.location.href = href;
        });

        $('#export_xml3176_xml_error').click(function() {
            var dateRange = $('#date_range').data('daterangepicker');

            var startDate = dateRange.startDate.format('YYYY-MM-DD HH:mm:ss');
            var endDate = dateRange.endDate.format('YYYY-MM-DD HH:mm:ss');
            var xml_filter_status = $('#xml_filter_status').val();
            var date_type = $('#date_type').val();
            var xml3176_error_catalog = $('#xml3176_error_catalog').val();
            var payment_date_filter = $('#payment_date_filter').val();
            var imported_by = $('#imported_by').val();
            var xml_submit_status = $('#xml_submit_status').val();
            var xml_sign_status = $('#xml_sign_status').val();
            // Tạo URL với các tham số query
            var href = '{{ route("bhyt.xml3176.export-xml3176-xml-errors") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate,
                'xml_filter_status': xml_filter_status,
                'date_type': date_type,
                'xml3176_error_catalog': xml3176_error_catalog,
                'payment_date_filter': payment_date_filter,
                'imported_by': imported_by,
                'xml_submit_status': xml_submit_status,
                'xml_sign_status': xml_sign_status
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
            var xml3176_error_catalog = $('#xml3176_error_catalog').val();
            var xml_export_status = $('#xml_export_status').val();
            var payment_date_filter = $('#payment_date_filter').val();
            var imported_by = $('#imported_by').val();
            var xml_submit_status = $('#xml_submit_status').val();
            var xml_sign_status = $('#xml_sign_status').val();
            // Tạo URL với các tham số query
            var href = '{{ route("bhyt.xml3176.export-xml3176-xml-xlsx") }}?' + $.param({
                'date_from': startDate,
                'date_to': endDate,
                'xml_filter_status': xml_filter_status,
                'date_type': date_type,
                'xml3176_error_catalog': xml3176_error_catalog,
                'xml_export_status': xml_export_status,
                'payment_date_filter': payment_date_filter,
                'imported_by': imported_by,
                'xml_submit_status': xml_submit_status,
                'xml_sign_status': xml_sign_status
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
        $("#loading_center").show();
        $.ajax({
            url: '{{ route("bhyt.xml3176.export-xml") }}',
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
            },
            complete: function() {
                // Hide loading spinner
                $("#loading_center").hide();
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
            url: '{{ route('bhyt.xml3176.jobs.status') }}',
            type: 'GET',
            success: function(response) {
                if (response.jobs_count > 0) {
                    $('#job-status-icon').show();
                    // Format the jobs_count with commas
                    var formattedCount = response.jobs_count.toLocaleString();
                    
                    $('#job-count').text(formattedCount).show(); // Hiển thị số lượng job
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

    // Xử lý copy tooltip khi click vào icon copy
    $(document).on('click', '.copy-tooltip-btn', function(e) {
        e.stopPropagation();
        e.preventDefault();
        
        var $icon = $(this);
        // Dùng attr để lấy giá trị trực tiếp từ data attribute
        var textToCopy = $icon.attr('data-copy-text');
        
        if (!textToCopy) {
            return;
        }
        
        // Hàm hiển thị thông báo thành công
        function showSuccess() {
            var originalClass = $icon.attr('class');
            $icon.removeClass('fa-copy').addClass('fa-check text-success');
            setTimeout(function() {
                $icon.attr('class', originalClass);
            }, 1000);
        }
        
        // Thử dùng Clipboard API (hiện đại hơn)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(function() {
                showSuccess();
            }).catch(function(err) {
                console.error('Failed to copy:', err);
                // Fallback về phương pháp cũ
                fallbackCopy(textToCopy, showSuccess);
            });
        } else {
            // Fallback cho trình duyệt không hỗ trợ Clipboard API
            fallbackCopy(textToCopy, showSuccess);
        }
    });
    
    // Hàm fallback copy sử dụng textarea
    function fallbackCopy(text, successCallback) {
        var $temp = $('<textarea>');
        $temp.css({
            position: 'fixed',
            left: '-9999px',
            top: '-9999px',
            opacity: '0'
        });
        $temp.val(text);
        $('body').append($temp);
        
        // Focus và select
        $temp[0].focus();
        $temp[0].select();
        $temp[0].setSelectionRange(0, 99999); // Đảm bảo select toàn bộ text
        
        try {
            var successful = document.execCommand('copy');
            if (successful && successCallback) {
                successCallback();
            }
        } catch (err) {
            console.error('Fallback copy failed:', err);
        }
        
        $temp.remove();
    }
</script>
@endpush