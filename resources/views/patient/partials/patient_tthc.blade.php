<div id="tthc" class="tab-pane fade in active">
    <div class="panel panel-primary">
      <div class="panel-body">
        @if(isset($treatment) && !empty($treatment->id))
          <table class="table display table-hover dtr-inline" width="100%">
            <tr>
              <td align="center" class="label-warning">
                <strong>Thông tin hành chính</strong>
              </td>
            </tr>
            <tr>
                <td><strong>Họ và tên:</strong> {{$treatment->tdl_patient_name}}</td>
            </tr>
            <tr>
                <td><strong>Năm sinh:</strong> {{$treatment->vir_dob_year}}</td>
            </tr>
            <tr>
                <td><strong>Giới tính:</strong> {{$treatment->tdl_patient_gender_name}}</td>
            </tr>
            <tr>
                <td><strong>Địa chỉ:</strong> {{$treatment->tdl_patient_address}}</td>
            </tr>
            <tr>
                <td><strong>Nghề nghiệp:</strong> {{$treatment->tdl_patient_career_name}}</td>
            </tr>
            <tr>
                <td><strong>Số điện thoại:</strong> {{$treatment->phone}}</td>
            </tr>
            <tr>
                <td><strong>Giấy tờ tùy thân:</strong> 
                  {{$treatment->tdl_patient_cmnd_number .$treatment->tdl_patient_cccd_number .$treatment->tdl_patient_passport_number}}
                </td>
            </tr>
            <tr>
                <td><strong>Số thẻ BHYT:</strong> {{$treatment->tdl_hein_card_number}}</td>
            </tr>
            
            <tr>
                <td><strong>Diện đối tượng:</strong> {{$treatment->patient_type_name}}</td>
            </tr>
            <tr>
                <td><strong>Diện điều trị:</strong> {{$treatment->treatment_type_name}}</td>
            </tr>

            <tr>
              <td align="center" class="label-warning">
                <strong>Thông tin KCB</strong>
              </td>
            </tr>
            <tr>
                <td><strong>Thời gian vào:</strong> {{strtodatetime($treatment->in_time)}}</td>
            </tr>
            <tr>
                <td><strong>Thời gian ra:</strong> 
                  {{$treatment->out_time ? strtodatetime($treatment->out_time) : ''}}
                </td>
            </tr>
            <tr>
                <td><strong>Kết quả:</strong> 
                  {{$treatment->treatment_result_name}}
                </td>
            </tr>
            <tr>
                <td><strong>Loại ra viện:</strong> 
                  {{$treatment->treatment_end_type_name}}
                </td>
            </tr>
            <tr>
                <td><strong>Chẩn đoán:</strong> 
                  {{$treatment->icd_name}}
                </td>
            </tr>
            <tr>
                <td><strong>CĐ kèm theo:</strong> 
                  {{$treatment->icd_text}}
                </td>
            </tr>
          </table>
        @else
          <center>{{__('insurance.backend.labels.no_information')}}</center>
        @endif 
      </div>
    </div>
</div>