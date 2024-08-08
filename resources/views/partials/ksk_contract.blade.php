<!-- resources/views/partials/xml_error_catalog.blade.php -->
<div class="col-sm-2">
    <div class="form-group row">
        <label for="ksk_contract">Hợp đồng KSK</label>
        <select id="ksk_contract" class="form-control select2">
            <option value="">Tất cả</option>
        </select>
    </div> 
</div>

@push('after-scripts-ksk-contract')
<script>
    $(document).ready(function() {
        $.ajax({
            url: '{{ route("category-his.fetch-ksk-contract") }}',
            method: 'GET',
            success: function(data) {
                var select = $('#ksk_contract');
                select.empty();
                select.append('<option value="">Tất cả</option>');
                $.each(data, function(index, catalog) {
                    var optionText = catalog.ksk_contract_code + ' - ' + catalog.work_place_name;
                    var optionElement = $('<option></option>').val(catalog.id).text(optionText);

                    select.append(optionElement);
                });

                select.select2();
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
            }
        });
    });
</script>
@endpush