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

@stop

@push('after-scripts')
<script src="{{ asset('js/dropzone.min.js') }}"></script>
<script type="text/javascript">
    $(document).ready(function() {
        // Disable auto discover for Dropzone
        Dropzone.autoDiscover = false;

        // Initialize Dropzone
        var myDropzone = new Dropzone("#my-dropzone", {
            paramName: "import_file", // The name that will be used to transfer the file
            maxFilesize: 10, // Maximum file size in MB
            acceptedFiles: ".xls,.xlsx", // Accept only Excel files
            autoProcessQueue: false, // Don't auto upload, we'll trigger it manually
        });

        // Handle the button click for file upload
        $('#uploadButton').on('click', function() {
            // Process all queued files
            myDropzone.processQueue();
        });

        // Handle success response
        myDropzone.on("success", function(file, response) {
            alert('File đã upload thành công!');
        });

        // Handle error response
        myDropzone.on("error", function(file, response) {
            alert('Có lỗi xảy ra khi upload file: ' + response);
        });
    });
</script>
@endpush