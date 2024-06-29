@extends('adminlte::page')

@section('title', 'Tải lên hồ sơ XML')

@section('content_header')
<h1>
    Nhập khẩu
    <small>hồ sơ XML</small>
</h1>
<ol class="breadcrumb">
    <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
    <li class="active">Dashboard</li>
</ol>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->
<div class="panel panel-default">
    <div class="panel-body">
        <form action="{{ route('bhyt.qd130.upload-data') }}" class="dropzone" id="xmlUploadForm">
            {{ csrf_field() }}
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <div id="uploadStatus" class="text-center h3 text-primary">Hồ sơ đã tải lên</div>
        <div class="table-responsive">
            <table class="table display table-hover responsive" id="fileListTable">
                <thead>
                    <tr>
                        <th>Tên tệp</th>
                        <th>Kích thước</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>    
    </div>
</div>

@stop

@push('after-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.2/dropzone.min.js"></script>
<script>
    Dropzone.options.xmlUploadForm = {
        paramName: "xmls", // Match the input name expected in the Laravel controller
        maxFilesize: 100, // MB
        acceptedFiles: ".xml",
        timeout: 300000, // 5 minutes
        previewTemplate: '<div></div>', // Use an empty template to disable the default preview
        init: function() {
            var totalFiles = 0;
            var uploadedFiles = 0;
            var isUploading = false;

            this.on("addedfile", function(file) {
                totalFiles++;
                updateProgress();
                addFileToTable(file);
                isUploading = true;
            });

            this.on("success", function(file, response) {
                uploadedFiles++;
                updateProgress();
                updateFileStatus(file, 'success', response.message);
                if (uploadedFiles === totalFiles) {
                    isUploading = false;
                    toastr.success("Đã hoàn thành việc tải lên hồ sơ!");
                }
            });

            this.on("error", function(file, response) {
                console.log(response);
                updateFileStatus(file, 'error', response.message);
                isUploading = false;
            });

            $(window).on('beforeunload', function() {
                if (isUploading) {
                    return 'Hồ sơ đang được tải lên. Bạn có muốn rời trang không?';
                }
            });

            function updateProgress() {
                var progressText = uploadedFiles + '/' + totalFiles + ' Hồ sơ đã tải lên';
                $('#uploadStatus').html(progressText);
            }

            function addFileToTable(file) {
                var table = document.getElementById('fileListTable').getElementsByTagName('tbody')[0];
                var existingRow = findFileRow(file.name);
                if (existingRow) {
                    updateFileRow(existingRow, file, 'pending');
                } else {
                    var newRow = table.insertRow();
                    var nameCell = newRow.insertCell(0);
                    var sizeCell = newRow.insertCell(1);
                    var statusCell = newRow.insertCell(2);

                    nameCell.innerText = file.name;
                    sizeCell.innerText = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                    statusCell.innerHTML = '<i class="fa fa-spinner fa-spin"></i>'; // Add a spinner while uploading
                    statusCell.classList.add('status');
                }
            }

            function updateFileStatus(file, status, message = '') {
                var row = findFileRow(file.name);
                if (row) {
                    updateFileRow(row, file, status, message);
                }
            }

            function findFileRow(fileName) {
                var rows = document.getElementById('fileListTable').getElementsByTagName('tbody')[0].rows;
                for (var i = 0; i < rows.length; i++) {
                    if (rows[i].cells[0].innerText === fileName) {
                        return rows[i];
                    }
                }
                return null;
            }

            function updateFileRow(row, file, status, message = '') {
                if (status === 'success') {
                    row.cells[2].innerHTML = '<i class="fa fa-check-circle" style="color: green;"></i>' + message;
                } else if (status === 'error') {
                    row.cells[2].innerHTML = '<i class="fa fa-times-circle" style="color: red;"></i>' + message;
                } else if (status === 'pending') {
                    row.cells[2].innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                }
            }
        }
    };
</script>
@endpush