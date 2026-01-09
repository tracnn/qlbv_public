<!-- resources/views/partials/xml_error_catalog.blade.php -->
<div class="col-sm-2">
    <div class="form-group row">
        <label for="xml3176_error_catalog">Lỗi XML</label>
        <select id="xml3176_error_catalog" class="form-control select2">
            <option value="">Tất cả</option>
        </select>
    </div> 
</div>

@push('after-scripts-xml3176-error-catalog')
<script>
    $(document).ready(function() {
        $.ajax({
            url: '{{ route("category-bhyt.fetch-xml3176-error-catalog") }}',
            method: 'GET',
            success: function(data) {
                var select = $('#xml3176_error_catalog');
                select.empty();
                select.append('<option value="">Tất cả</option>');
                $.each(data, function(index, catalog) {
                    var optionText = catalog.xml + ' / ' + catalog.error_name;
                    var optionElement = $('<option></option>').val(catalog.id).text(optionText);

                    if (catalog.critical_error) {
                        optionElement.attr('data-icon', 'fa fa-exclamation-triangle text-danger');
                    }

                    select.append(optionElement);
                });

                select.select2({
                    templateResult: formatState,
                    templateSelection: formatState,
                    width: '100%'
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("AJAX error: " + textStatus + ' : ' + errorThrown);
            }
        });
    });

    function formatState(state) {
        if (!state.id) {
            return state.text;
        }
        var $state = $(
            '<span>' + state.text + (state.element.getAttribute('data-icon') ? ' <i class="' + state.element.getAttribute('data-icon') + '" aria-hidden="true"></i>' : '') + '</span>'
        );
        return $state;
    }
</script>
@endpush