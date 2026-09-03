<!-- resources/views/partials/xml3176_ma_khoa_filter.blade.php -->
<div class="col-sm-2">
    <div class="form-group row">
        <label for="ma_khoa">Khoa (XML1)</label>
        <select id="ma_khoa" class="form-control select2">
            <option value="">Tất cả</option>
        </select>
    </div>
</div>

@push('after-scripts-xml3176-ma-khoa')
<script>
    $(document).ready(function() {
        $.ajax({
            url: '{{ route("bhyt.xml3176.department-options") }}',
            method: 'GET',
            success: function(data) {
                var select = $('#ma_khoa');
                select.empty();
                select.append('<option value="">Tất cả</option>');
                $.each(data, function(index, khoa) {
                    var text = khoa.ten_khoa ? (khoa.ma_khoa + ' - ' + khoa.ten_khoa) : khoa.ma_khoa;
                    select.append($('<option></option>').val(khoa.ma_khoa).text(text));
                });
                select.select2({ width: '100%' });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("AJAX error (ma_khoa): " + textStatus + ' : ' + errorThrown);
            }
        });
    });
</script>
@endpush
