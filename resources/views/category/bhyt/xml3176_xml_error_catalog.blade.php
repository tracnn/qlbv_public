@extends('adminlte::page')

@section('title', 'Danh mục lỗi Xml 3176')

@section('content_header')
  <h1>
    Danh mục
    <small>Lỗi Xml 3176</small>
  </h1>
@stop

@section('content')
<!-- Messages -->
@include('includes.message')
<!-- /Messages -->

<div class="panel panel-default">
    <div class="panel-body table-responsive">
        <table id="xml3176-xml-error-catalog-list" class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
            <thead>
                <tr>
                    <th>XML</th>
                    <th>Mã lỗi</th>
                    <th>Tên lỗi</th>
                    <th>Mô tả</th>
                    <th>Nghiêm trọng</th>
                    <th>Có kiểm tra</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@stop

@push('after-scripts')
<script type="text/javascript">
    var currentAjaxRequest = null;
    var table = null;

    function fetchData() {
        if (currentAjaxRequest != null) {
            currentAjaxRequest.abort();
        }

        table = $('#xml3176-xml-error-catalog-list').DataTable({
            "processing": true,
            "serverSide": true,
            "destroy": true,
            "responsive": true,
            "scrollX": true,
            "ajax": {
                url: "{{ route('category-bhyt.fetch-xml3176-xml-error-catalog-datatable') }}",
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
                { "data": "xml" },
                { "data": "error_code" },
                { "data": "error_name" },
                { "data": "description" },
                { "data": "critical_error" },
                { "data": "is_check" },
            ],
        });

        table.ajax.reload();
    }

    $(document).ready(function() {
        fetchData();

        $('#xml3176-xml-error-catalog-list').on('change', '.is-check-toggle', function() {
            var isChecked = $(this).is(':checked');
            var catalogId = $(this).data('id');
            var self = $(this);

            Swal.fire({
                title: 'Bạn có chắc chắn?',
                icon: 'warning',
                showCancelButton: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('category-bhyt.update-xml3176-xml-error-catalog') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: catalogId,
                            is_check: isChecked ? 1 : 0
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success('Cập nhật thành công.');
                            } else {
                                toastr.error('Có lỗi xảy ra khi cập nhật.');
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error('Có lỗi xảy ra khi gửi yêu cầu: ' + error);
                            console.log('Error:', error);
                        }
                    });
                } else {
                    self.prop('checked', !isChecked);
                }
            });
        });

        $('#xml3176-xml-error-catalog-list').on('change', '.critical-error-toggle', function() {
            var isChecked = $(this).is(':checked');
            var catalogId = $(this).data('id');
            var self = $(this);

            Swal.fire({
                title: 'Bạn có chắc chắn?',
                icon: 'warning',
                showCancelButton: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('category-bhyt.update-xml3176-xml-error-catalog') }}",
                        method: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: catalogId,
                            critical_error: isChecked ? 1 : 0
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success('Cập nhật thành công.');
                            } else {
                                toastr.error('Có lỗi xảy ra khi cập nhật.');
                            }
                        },
                        error: function(xhr, status, error) {
                            toastr.error('Có lỗi xảy ra khi gửi yêu cầu: ' + error);
                            console.log('Error:', error);
                        }
                    });
                } else {
                    self.prop('checked', !isChecked);
                }
            });
        });
        
    });
</script>
@endpush
