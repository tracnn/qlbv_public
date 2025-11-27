<div class="col-sm-2">
    <div class="form-group row">
        <label for="detail_summary_type">Loại</label>
        <select id="detail_summary_type" class="form-control select2">
            <option value="">Tất cả</option>
            <option value="1">Chi tiết</option>
            <option value="2">Tổng hợp</option>
        </select>
    </div> 
</div>
@push('after-scripts-detail-summary-type')
<script type="text/javascript">
    $(document).ready(function() {
        // Get saved default range from localStorage or set to 'day' if not found
        var savedDetail_summary_type = localStorage.getItem('detail_summary_type');
        
        // Set the select value to the saved range
        $('#detail_summary_type').val(savedDetail_summary_type);

        // Update default dates and save to localStorage when the user changes the selection
        $('#detail_summary_type').change(function() {
            var selectedDetail_summary_type = $(this).val();
            localStorage.setItem('detail_summary_type', selectedDetail_summary_type);
        });
    });
</script>
@endpush