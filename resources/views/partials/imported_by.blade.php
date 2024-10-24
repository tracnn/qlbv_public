<!-- resources/views/partials/imported_by.blade.php -->
<div class="col-sm-2">
    <div class="form-group row">
        <label for="imported_by">Imported by</label>
        <select id="imported_by" class="form-control select2">
            <option value="">Tất cả</option>
        </select>
    </div> 
</div>

@push('after-scripts-imported-by')
<script>
    $(document).ready(function() {
        $.ajax({
            url: '{{ route("category-his.fetch-imported-by") }}',
            method: 'GET',
            success: function(data) {
                var select = $('#imported_by');
                select.empty();
                select.append('<option value="">Tất cả</option>');
                $.each(data, function(index, catalog) {
                    select.append('<option value="' + catalog.loginname + '">' + catalog.username + '</option>');
                });

                select.select2({
                    width: '100%' // Đặt chiều rộng của Select2 là 100%
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
            }
        });
    });
</script>
@endpush