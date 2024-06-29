<div class="col-sm-2">
    <div class="form-group row">
        <label for="treatment_code">Mã điều trị</label>
        <input class="form-control" type="text" id="treatment_code" pattern="\d*">
    </div>
</div>

@push('after-scripts-treatment-code')

<script type="text/javascript">
    $(document).ready(function() {
        $('#treatment_code').focus();

        $('#treatment_code').on('blur', function() {
            var code = $(this).val().trim(); // Remove whitespace from both ends of the input
            if (code.length > 0 && code.length < 12) { // Check if the code has less than 10 characters
                while (code.length < 12) {
                    code = '0' + code; // Add zeros to the start until it has exactly 10 characters
                }
            }
            $(this).val(code); // Update the input field with the padded code
        });

        // Hàm kiểm tra ký tự nhập vào có phải là số không
        function isNumberKey(evt) {
          var charCode = (evt.which) ? evt.which : evt.keyCode;
          // Chỉ cho phép nhập số
          if (charCode < 48 || charCode > 57) {
              evt.preventDefault();
              return false;
          }
          return true;
        }

        // Áp dụng hàm kiểm tra cho các trường nhập số
        $('#treatment_code').on('keypress', function(evt) {
          return isNumberKey(evt);
        });

        // Xử lý sự kiện bấm phím Enter và Tab
        $('#treatment_code').on('keydown', function(evt) {
            if (evt.key === 'Enter' || evt.key === 'Tab') {
                evt.preventDefault();
                $(this).trigger('blur'); // Gọi sự kiện blur để xử lý định dạng số
                $('#load_data_button').trigger('click'); // Kích hoạt nút load_data_button
            }
        });

    });
</script>

@endpush