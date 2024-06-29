{{-- resources/views/emr/partials/payment_info.blade.php --}}
<div class="card">
    <div id="paymentData" 
        data-treatment-code="{{ $treatment_code }}"
        data-tdl-patient-name="{{ $tdl_patient_name }}"
        data-tdl-patient-dob="{{ $tdl_patient_dob }}"
        data-tdl-patient-address="{{ $tdl_patient_address }}"
        data-tdl-patient-mobile="{{ $tdl_patient_mobile ?: $tdl_patient_phone ?: '' }}"
        data-tdl-patient-relative-mobile="{{ $tdl_patient_relative_mobile ?: $tdl_patient_relative_phone ?: '' }}"
        data-can-thanh-toan="{{ $can_thanh_toan }}"
        data-is-payment="{{ $is_payment }}"
        data-total-price="{{ $total_price }}"
        data-total-hein-price="{{ $total_hein_price }}"
        data-total-patient-price="{{ $total_patient_price }}"
        data-tam-ung="{{ $tam_ung }}"
        data-hoan-ung="{{ $hoan_ung }}"
        data-da-thanh-toan="{{ $da_thanh_toan }}"
        data-department-name="{{ $department_name }}"
        style="display: none;">
    </div>
    <div class="card-body">
        <p><strong>Tên: </strong>{{ $tdl_patient_name }}</p>
        <p><strong>Ngày sinh: </strong>{{ strtodate($tdl_patient_dob) }}</p>
        <p><strong>Địa chỉ: </strong>{{ $tdl_patient_address }}</p>
        <p><strong>Điện thoại: </strong>{{ $tdl_patient_mobile ?: $tdl_patient_phone ?: ''}}</p>
        <p><strong>Điện thoại người thân: </strong>{{ $tdl_patient_relative_mobile ?: $tdl_patient_relative_phone ?: ''}}</p>
        <p align="center">{!! QrCode::size(200)->generate($qrString) !!}</p>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tbody>
                <tr>
                    <td><h3>
                        @if($is_payment)
                            Cần Thanh Toán:
                        @else
                            Cần Tạm Thu:
                        @endif
                    </h3></td>
                    <td class="text-right {{ $can_thanh_toan < 0 ? 'text-primary' : 'text-danger' }}"><h3>{{ number_format($can_thanh_toan) }}₫</h3></td>
                </tr>
                <tr>
                    <td>Tổng Chi Phí:</td>
                    <td class="text-right"><strong>{{ number_format($total_price) }}₫</strong></td>
                </tr>
                <tr>
                    <td>BHYT Chi Trả:</td>
                    <td class="text-right"><strong>{{ number_format($total_hein_price) }}₫</strong></td>
                </tr>
                <tr>
                    <td>Người Bệnh Chi Trả:</td>
                    <td class="text-right"><strong>{{ number_format($total_patient_price) }}₫</strong></td>
                </tr>
                <tr>
                    <td>Đã Tạm Ứng:</td>
                    <td class="text-right"><strong>{{ number_format($tam_ung) }}₫</strong></td>
                </tr>
                <tr>
                    <td>Đã Hoàn Ứng:</td>
                    <td class="text-right"><strong>{{ number_format($hoan_ung) }}₫</strong></td>
                </tr>
                <tr>
                    <td>Đã Thanh Toán:</td>
                    <td class="text-right"><strong>{{ number_format($da_thanh_toan) }}₫</strong></td>
                </tr>
                <tr class="highlight-red">
                    <td>Chi Phí Khác:</td>
                    <td class="text-right"><strong>{{ number_format($tu_nhap) }}₫</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>