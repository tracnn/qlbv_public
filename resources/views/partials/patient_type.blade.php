<!-- resources/views/partials/patient_type.blade.php -->
<div class="col-sm-2">
    <div class="form-group row">
        <label for="patient_type">Đối tượng</label>
        <select id="patient_type" class="form-control select2">
            <option value="">Tất cả</option>
        </select>
    </div> 
</div>

@push('after-scripts-patient-type')
<script>
    $(document).ready(function() {
        $.ajax({
            url: '{{ route("category-his.fetch-patient-type") }}',
            method: 'GET',
            success: function(data) {
                var select = $('#patient_type');
                select.empty();
                select.append('<option value="">Tất cả</option>');
                $.each(data, function(index, catalog) {
                    select.append('<option value="' + catalog.id + '">' + catalog.patient_type_name + '</option>');
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