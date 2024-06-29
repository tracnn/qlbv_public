<div class="col-sm-2">
    <div class="form-group row">
        <label for="drug_req_type">Loại đơn thuốc</label>
        <select id="drug_req_type" class="form-control">
            <option value="">Tất cả</option>
            <option value="6">Đơn phòng khám</option>
            <option value="15">Đơn điều trị</option>
            <option value="14">Đơn tủ trực</option>
        </select>
    </div> 
</div>
@push('after-scripts-drug-req-type')
<script type="text/javascript">
    $(document).ready(function() {
        // Get saved default range from localStorage or set to 'day' if not found
        var savedDrug_req_type = localStorage.getItem('drug_req_type');
        
        // Set the select value to the saved range
        $('#drug_req_type').val(savedDrug_req_type);

        // Update default dates and save to localStorage when the user changes the selection
        $('#drug_req_type').change(function() {
            var selectedDrug_req_type = $(this).val();
            localStorage.setItem('drug_req_type', selectedDrug_req_type);
        });
    });
</script>
@endpush