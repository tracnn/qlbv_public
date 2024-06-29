<div id="xemnhanh" class="tab-pane fade">
  <div class="panel panel-primary">
    <div class="panel-body">
    @if(isset($service_kham))
        <table class="table display table-hover dtr-inline" width="100%">
        @if($service_kham->pulse && $service_kham->blood_pressure_max && $service_kham->blood_pressure_min)
          <tr>
            <td align="center" class="label-warning">
              <strong>Thể lực</strong>
            </td>
          </tr>
          <tr>
            <td>
              <strong>Mạch:</strong> {{$service_kham->pulse}} lần/phút.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Huyết áp:</strong> {{$service_kham->blood_pressure_max}}/{{$service_kham->blood_pressure_min}}.
            </td>
          </tr>
          @if($service_kham->note)
          <tr>
            <td>
              <strong>Phân loại:</strong> {{$service_kham->note ? $service_kham->note : 'Chưa phân loại'}}.
            </td>
          </tr>
          @endif
        @endif
          <tr>
            <td align="center" class="label-warning">
              <strong>Tiền sử & Quá trình bệnh lý</strong>
            </td>
          </tr>
          <tr>
            <td>
              <strong>Tiền sử gia đình:</strong> {{$service_kham->pathological_history_family ? $service_kham->pathological_history_family : 'Không có dữ liệu'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Tiền sử bản thân:</strong> {{$service_kham->pathological_history ? $service_kham->pathological_history : 'Không có dữ liệu'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Quá trình bệnh lý:</strong> {{$service_kham->pathological_process ? $service_kham->pathological_process : 'Không có dữ liệu'}}.
            </td>
          </tr>
          <tr>
            <td align="center" class="label-warning">
              <strong>Nội, ngoại, da liễu</strong>
            </td>
          </tr>
          <tr>
            <td>
              <strong>Ngoại chung:</strong> {{$service_kham->part_exam ? $service_kham->part_exam : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Tuần hoàn:</strong> {{$service_kham->part_exam_circulation ? $service_kham->part_exam_circulation : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Hô hấp:</strong> {{$service_kham->part_exam_respiratory ? $service_kham->part_exam_respiratory : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Tiêu hóa:</strong> {{$service_kham->part_exam_digestion ? $service_kham->part_exam_digestion : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Thận tiết niệu:</strong> {{$service_kham->part_exam_kidney_urology ? $service_kham->part_exam_kidney_urology : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Thần kinh:</strong> {{$service_kham->part_exam_neurological ? $service_kham->part_exam_neurological : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Cơ xương khớp:</strong> {{$service_kham->part_exam_muscle_bone ? $service_kham->part_exam_muscle_bone : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Tiết niệu:</strong> {{$service_kham->part_exam_oend ? $service_kham->part_exam_oend : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Nội tiết:</strong> {{$service_kham->part_exam_oend ? $service_kham->part_exam_oend : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Tâm thần:</strong> {{$service_kham->part_exam_mental ? $service_kham->part_exam_mental : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Dinh dưỡng:</strong> {{$service_kham->part_exam_nutrition ? $service_kham->part_exam_nutrition : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Vận động:</strong> {{$service_kham->part_exam_motion ? $service_kham->part_exam_motion : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Da liễu:</strong> {{$service_kham->part_exam_dermatology ? $service_kham->part_exam_dermatology : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td align="center" class="label-warning">
              <strong>Răng hàm mặt</strong>
            </td>
          </tr>
          <tr>
            <td>
              <strong>Hàm trên:</strong> {{$service_kham->part_exam_upper_jaw ? $service_kham->part_exam_upper_jaw : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Hàm dưới:</strong> {{$service_kham->part_exam_lower_jaw ? $service_kham->part_exam_lower_jaw : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Bệnh RHM (nếu có):</strong> {{$service_kham->part_exam_stomatology ? $service_kham->part_exam_stomatology : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td align="center" class="label-warning">
              <strong>Tai mũi họng</strong>
            </td>
          </tr>
          <tr>
            <td>
              <strong>Tai phải (m):</strong> Nói thường {{$service_kham->part_exam_ear_right_normal ? $service_kham->part_exam_ear_right_normal : 'Không khám'}}; Nói thầm {{$service_kham->part_exam_ear_right_whisper ? $service_kham->part_exam_ear_right_whisper : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Tai trái (m):</strong> Nói thường {{$service_kham->part_exam_ear_left_normal ? $service_kham->part_exam_ear_left_normal : 'Không khám'}}; Nói thầm {{$service_kham->part_exam_ear_left_whisper ? $service_kham->part_exam_ear_left_whisper : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Bệnh TMH (nếu có):</strong> {{$service_kham->part_exam_ear}}. {{$service_kham->part_exam_nose}}. {{$service_kham->part_exam_throat}}
            </td>
          </tr>  
          <tr>
            <td align="center" class="label-warning">
              <strong>Mắt</strong>
            </td>
          </tr>
          <tr>
            <td>
              <strong>Nhãn áp (P/T):</strong> {{($service_kham->part_exam_eye_tension_left && $service_kham->part_exam_eye_tension_right) ? ($service_kham->part_exam_eye_tension_right .'/' .$service_kham->part_exam_eye_tension_left) : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Thị lực không kính (P/T):</strong> {{($service_kham->part_exam_eyesight_right && $service_kham->part_exam_eyesight_left) ? ($service_kham->part_exam_eyesight_right .'/' .$service_kham->part_exam_eyesight_left) : 'Không khám'}}.
            </td>
          </tr>
          <tr>
            <td>
              <strong>Thị lực có kính (P/T):</strong> {{($service_kham->part_exam_eyesight_glass_right && $service_kham->part_exam_eyesight_glass_left) ? ($service_kham->part_exam_eyesight_glass_right .'/' .$service_kham->part_exam_eyesight_glass_left) : 'Không khám'}}.
            </td>
          </tr> 
          <tr>
            <td>
              <strong>Bệnh về mắt (nếu có):</strong> {{$service_kham->part_exam_eye ? $service_kham->part_exam_eye : 'Không khám'}}
            </td>
          </tr>   
          <tr>
            <td align="center" class="label-warning">
              <strong>Sản phụ khoa</strong>
            </td>
          </tr>
          <tr>
            <td>
              {{$service_kham->part_exam_obstetric ? $service_kham->part_exam_obstetric : 'Không khám'}}
            </td>
          </tr>                                    
        </table>
    @else
    <center>{{__('insurance.backend.labels.no_information')}}</center>
    @endif
    </div>
  </div>
</div>