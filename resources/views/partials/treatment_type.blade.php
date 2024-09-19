<!-- resources/views/partials/treatment_type.blade.php -->
<div class="col-sm-2">
    <div class="form-group row">
        <label for="treatment_type">Diện điều trị</label>
        <select id="treatment_type" class="form-control select2">
            <option value="">Tất cả</option>
        </select>
    </div> 
</div>

@push('after-scripts-treatment-type')
<script>
    $(document).ready(function() {
        $.ajax({
            url: '{{ route("category-his.fetch-treatment-type") }}',
            method: 'GET',
            success: function(data) {
                var select = $('#treatment_type');
                select.empty();
                select.append('<option value="">Tất cả</option>');
                $.each(data, function(index, catalog) {
                    select.append('<option value="' + catalog.id + '">' + catalog.treatment_type_name + '</option>');
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