@extends('adminlte::page')

@section('title', 'Chi tiết hồ sơ')

@section('content_header')
  <h1>
    Chi tiết
    <small>hồ sơ</small>
  </h1>
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
    .group-header {
        transition: background-color 0.2s ease;
    }
    .group-header:hover {
        background-color: #e0e0e0 !important;
    }
    .group-toggle {
        transition: transform 0.2s ease;
    }
</style>
@endpush

@section('content')
    @include('bhxh.partials.search-detail')
    <div class="panel panel-default">
        <div class="panel-heading">
            CĐHA
        </div>
        <div class="panel-body table-responsive">
            <table id="service-cdha" class="table display table-hover responsive wrap datatable dtr-inline" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên dịch vụ</th>
                        <th>Tác vụ</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            Chi tiết hồ sơ
            <div class="pull-right">
                <label style="font-weight: normal; margin-bottom: 0;">
                    <input type="checkbox" id="groupByDocumentType" style="margin-right: 5px;">
                    Nhóm theo Loại văn bản
                </label>
            </div>
        </div>
        <div class="panel-body table-responsive">
            <table id="emr-detail" class="table display table-hover responsive wrap datatable dtr-inline" width="100%">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên văn bản</th>
                        <th>Loại văn bản</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null; // Biến để lưu trữ yêu cầu AJAX hiện tại
    var table = null;
    var allDocumentData = []; // Lưu tất cả dữ liệu để nhóm
    var isGrouped = false;

    function fetchData() {
        // Kiểm tra và hủy yêu cầu AJAX trước đó (nếu có)
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        if (table != null && !isGrouped) {
            table.ajax.reload(); // Nếu đã khởi tạo thì chỉ cần reload
            return;
        }

        table = $('#emr-detail').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: '{{ route('bhxh.emr-checker-document-list') }}',
                data: function(d) {
                    d.document_type = $('#document_type').val();
                    d.treatment_code = '{{ $treatment_code }}';
                    // Nếu cần lấy tất cả dữ liệu để nhóm
                    if (isGrouped) {
                        d.length = -1; // Lấy tất cả records
                    }
                },
                beforeSend: function(xhr) {
                    currentAjaxRequest = xhr;
                },
                complete: function(xhr, status) {
                    currentAjaxRequest = null;
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            },
            "columns": [
                { 
                    "data": null, 
                    "orderable": false, 
                    "searchable": false, 
                    "render": function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { "data": "document_name" }, // Tên văn bản
                { "data": "document_type_name" }, // Loại văn bản
                { "data": "create_date" }, // Ngày tạo
                { "data": "action" }, // Hành động
            ],
            "drawCallback": function(settings) {
                // Lưu dữ liệu khi DataTable được vẽ
                if (!isGrouped) {
                    var api = this.api();
                    allDocumentData = api.rows({page: 'all'}).data().toArray();
                }
            }
        });
    }

    // Hàm để fetch tất cả dữ liệu và nhóm
    function fetchAllDataAndGroup() {
        var tbody = $('#emr-detail tbody');
        
        // Phá hủy DataTable
        if (table) {
            table.destroy();
            table = null;
        }
        
        // Hiển thị loading
        tbody.html('<tr><td colspan="5" class="text-center">Đang tải dữ liệu...</td></tr>');
        
        // Fetch tất cả dữ liệu với format DataTables server-side
        $.ajax({
            url: '{{ route('bhxh.emr-checker-document-list') }}',
            type: 'GET',
            data: {
                document_type: $('#document_type').val(),
                treatment_code: '{{ $treatment_code }}',
                length: 10000, // Lấy nhiều records (hoặc có thể dùng -1 nếu server hỗ trợ)
                start: 0,
                draw: 1
            },
            success: function(response) {
                // Xử lý cả format DataTables và format thông thường
                var data = [];
                if (response.data && Array.isArray(response.data)) {
                    data = response.data;
                } else if (Array.isArray(response)) {
                    data = response;
                }
                
                if (data.length === 0) {
                    tbody.html('<tr><td colspan="5" class="text-center">Không có dữ liệu</td></tr>');
                    return;
                }
                
                // Nhóm dữ liệu theo document_type_name
                var groupedData = {};
                data.forEach(function(row) {
                    var documentType = row.document_type_name || 'Không xác định';
                    if (!groupedData[documentType]) {
                        groupedData[documentType] = [];
                    }
                    groupedData[documentType].push(row);
                });
                
                // Xóa tbody và hiển thị dữ liệu đã nhóm
                tbody.empty();
                var stt = 1;
                var groupIndex = 0;
                
                Object.keys(groupedData).sort().forEach(function(documentType) {
                    var groupId = 'group-' + groupIndex;
                    
                    // Thêm header cho nhóm với khả năng click để collapse/expand
                    var groupRow = $('<tr class="group-header" style="background-color: #f5f5f5; font-weight: bold; cursor: pointer;" data-group-id="' + groupId + '">');
                    groupRow.append('<td colspan="5" style="padding: 10px 15px;">');
                    groupRow.find('td').html(
                        '<span class="group-toggle glyphicon glyphicon-chevron-down" style="margin-right: 5px; color: #337ab7;"></span>' +
                        '<span class="glyphicon glyphicon-folder-open" style="margin-right: 5px;"></span>' + 
                        documentType + ' <span class="badge">' + groupedData[documentType].length + '</span>'
                    );
                    tbody.append(groupRow);
                    
                    // Thêm các row trong nhóm với class để có thể ẩn/hiện
                    groupedData[documentType].forEach(function(row) {
                        var newRow = $('<tr class="group-row" data-group-id="' + groupId + '">');
                        newRow.append('<td>' + stt + '</td>');
                        newRow.append('<td>' + (row.document_name || '') + '</td>');
                        newRow.append('<td>' + (row.document_type_name || '') + '</td>');
                        newRow.append('<td>' + (row.create_date || '') + '</td>');
                        newRow.append('<td>' + (row.action || '') + '</td>');
                        tbody.append(newRow);
                        stt++;
                    });
                    
                    groupIndex++;
                });
                
                // Thêm nút Expand All / Collapse All nếu có nhiều nhóm
                if (groupIndex > 1) {
                    var controlRow = $('<tr class="group-control" style="background-color: #e8e8e8;">');
                    controlRow.append('<td colspan="5" style="padding: 8px 15px; text-align: right;">');
                    controlRow.find('td').html(
                        '<button type="button" class="btn btn-xs btn-default expand-all-groups" style="margin-right: 5px;">' +
                        '<span class="glyphicon glyphicon-resize-full"></span> Mở tất cả</button>' +
                        '<button type="button" class="btn btn-xs btn-default collapse-all-groups">' +
                        '<span class="glyphicon glyphicon-resize-small"></span> Đóng tất cả</button>'
                    );
                    tbody.prepend(controlRow);
                }
                
                $('#emr-detail').addClass('table-hover');
            },
            error: function(xhr, error, code) {
                console.log('Error fetching data:', error);
                tbody.html('<tr><td colspan="5" class="text-center text-danger">Có lỗi xảy ra khi tải dữ liệu</td></tr>');
                // Nếu lỗi, có thể khởi tạo lại DataTable bình thường
                // isGrouped = false;
                // fetchData();
            }
        });
    }

    // Xử lý checkbox nhóm
    $(document).on('change', '#groupByDocumentType', function() {
        isGrouped = $(this).is(':checked');
        
        if (isGrouped) {
            fetchAllDataAndGroup();
        } else {
            // Khởi tạo lại DataTable bình thường
            fetchData();
        }
    });

    // Xử lý click vào group header để collapse/expand
    $(document).on('click', '.group-header', function(e) {
        e.preventDefault();
        var groupId = $(this).data('group-id');
        var toggleIcon = $(this).find('.group-toggle');
        var groupRows = $('.group-row[data-group-id="' + groupId + '"]');
        
        if (groupRows.is(':visible')) {
            // Collapse: ẩn các row trong nhóm
            groupRows.hide();
            toggleIcon.removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-right');
        } else {
            // Expand: hiển thị các row trong nhóm
            groupRows.show();
            toggleIcon.removeClass('glyphicon-chevron-right').addClass('glyphicon-chevron-down');
        }
    });

    // Xử lý nút Expand All
    $(document).on('click', '.expand-all-groups', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.group-row').show();
        $('.group-toggle').removeClass('glyphicon-chevron-right').addClass('glyphicon-chevron-down');
    });

    // Xử lý nút Collapse All
    $(document).on('click', '.collapse-all-groups', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('.group-row').hide();
        $('.group-toggle').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-right');
    });

    // Gọi khi trang load
    $(document).ready(function () {
        fetchData();

        // Nếu có lọc document_type, bạn có thể thêm:
        $('#document_type').on('change', function () {
            if (!isGrouped) {
                fetchData();
            } else {
                // Nếu đang ở chế độ nhóm, fetch lại và nhóm lại
                fetchAllDataAndGroup();
            }
        });

        table = $('#service-cdha').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true, // Destroy any existing DataTable before reinitializing
            "responsive": true, // Giữ responsive
            "scrollX": true, // Đảm bảo cuộn ngang khi bảng quá rộng
            "ajax": {
                url: '{{ route('bhxh.service-cdha-list') }}',
                data: function(d) {
                    d.treatment_code = '{{ $treatment_code }}';
                },
                beforeSend: function(xhr) {
                    currentAjaxRequest = xhr;
                },
                complete: function(xhr, status) {
                    currentAjaxRequest = null;
                },
                error: function(xhr, error, code) {
                    console.log('Error:', error);
                    console.log('Code:', code);
                    console.log('XHR:', xhr);
                }
            },
            "columns": [
                { 
                    "data": null, 
                    "orderable": false, 
                    "searchable": false, 
                    "render": function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { "data": "tdl_service_name" },
                { "data": "action" }, // Hành động
            ],
        });
    });
</script>
@endpush