<!-- resources/views/partials/xml_error_catalog.blade.php -->
<div class="col-sm-2">
    <div class="form-group row">
        <label for="xml_error_catalog">Lỗi XML</label>
        <select id="xml_error_catalog" class="form-control select2">
            <option value="">Tất cả</option>
        </select>
    </div> 
</div>

@push('after-scripts-xml-error-catalog')
<script>
    $(document).ready(function() {
        $.ajax({
            url: '{{ route("category-bhyt.fetch-xml-error-catalog") }}',
            method: 'GET',
            success: function(data) {
                var select = $('#xml_error_catalog');
                select.empty();
                select.append('<option value="">Tất cả</option>');
                $.each(data, function(index, catalog) {
                    select.append('<option value="' + catalog.id + '">' + catalog.xml + ' / ' +
                        catalog.error_name + '</option>');
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