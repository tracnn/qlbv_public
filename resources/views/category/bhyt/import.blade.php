@extends('adminlte::page')

@section('title', 'Nhập khẩu danh mục')

@section('content_header')
<h1>
    Nhập khẩu
    <small>danh mục</small>
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
        <form action="{{ route('category-bhyt.import') }}" method="POST" class="dropzone" id="my-dropzone">
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
<script src="{{ asset('js/dropzone.min.js') }}"></script>
<script>
    Dropzone.autoDiscover = false; // Disable auto discover to prevent multiple instances
    // Khởi tạo Dropzone
    var myDropzone = new Dropzone("#my-dropzone", {
        paramName: "import_file", // Tên của field gửi lên server
        maxFilesize: 10, // MB
        acceptedFiles: ".xls,.xlsx", // Giới hạn các loại file
        timeout: 300000, // 5 phút
        uploadMultiple: false, // Upload từng file một, để đơn giản hóa việc xử lý
        parallelUploads: 1, // Chỉ tải lên một file tại một thời điểm
        previewTemplate: '<div></div>', // Tắt giao diện preview mặc định
        init: function () {
            var totalFiles = 0;
            var uploadedFiles = 0;
            var isUploading = false;

            // Khi file được thêm vào Dropzone
            this.on("addedfile", function (file) {
                totalFiles++;
                updateProgress();
                addFileToTable(file);
                isUploading = true;
            });

            // Khi upload thành công
            this.on("success", function (file, response) {
                uploadedFiles++;
                updateProgress();
                updateFileStatus(file, 'success', response.message || "Tải lên thành công");
                if (uploadedFiles === totalFiles) {
                    isUploading = false;
                    Swal.fire({
                        title: 'Thành công!',
                        text: 'Đã hoàn thành việc tải lên hồ sơ!',
                        icon: 'success',
                    });
                }
            });

            // Khi gặp lỗi trong quá trình upload
            this.on("error", function (file, response) {
                updateFileStatus(file, 'error', response.message || "Lỗi khi tải lên");
                isUploading = false;
            });

            // Cảnh báo khi người dùng cố gắng thoát trang trong quá trình upload
            window.onbeforeunload = function () {
                if (isUploading) {
                    return "Hồ sơ đang được tải lên. Bạn có chắc chắn muốn rời khỏi trang này?";
                }
            };

            // Cập nhật tiến trình tải lên
            function updateProgress() {
                var progressText = uploadedFiles + '/' + totalFiles + ' Hồ sơ đã tải lên';
                document.getElementById('uploadStatus').innerText = progressText;
            }

            // Thêm file vào bảng trạng thái
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
                    sizeCell.innerText = (file.size / 1024).toFixed(2) + ' KB'; // Hiển thị kích thước theo KB
                    statusCell.innerHTML = '<i class="fa fa-spinner fa-spin"></i>'; // Thêm icon spinner trong khi upload
                    statusCell.classList.add('status');
                }
            }

            // Cập nhật trạng thái của file (thành công hoặc lỗi)
            function updateFileStatus(file, status, message = '') {
                var row = findFileRow(file.name);
                if (row) {
                    updateFileRow(row, file, status, message);
                }
            }

            // Tìm hàng tương ứng với tên file
            function findFileRow(fileName) {
                var rows = document.getElementById('fileListTable').getElementsByTagName('tbody')[0].rows;
                for (var i = 0; i < rows.length; i++) {
                    if (rows[i].cells[0].innerText === fileName) {
                        return rows[i];
                    }
                }
                return null;
            }

            // Cập nhật thông tin của hàng tương ứng với trạng thái tải lên
            function updateFileRow(row, file, status, message = '') {
                if (status === 'success') {
                    row.cells[2].innerHTML = '<i class="fa fa-check-circle" style="color: green;"></i> ' + message;
                } else if (status === 'error') {
                    row.cells[2].innerHTML = '<i class="fa fa-times-circle" style="color: red;"></i> ' + message;
                } else if (status === 'pending') {
                    row.cells[2].innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
                }
            }
        }
    });
</script>
@endpush