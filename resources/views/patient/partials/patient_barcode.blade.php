@if(isset($barcode))
<div style="text-align: center;">
    <img src="data:image/png;base64,{{ $barcode }}" alt="Barcode">
    <p><span><b>{{ $treatment_code }}</b></span></p> <!-- Hiển thị số dưới mã vạch -->
</div>
@endif