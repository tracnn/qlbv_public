@php
    use Illuminate\Support\Facades\Crypt;
@endphp

<style>
.active, .table .active>td, .table .active>th {
    background-color: #f5f5f5; /* Màu nền cho tiêu đề loại tài liệu */
}
.info, .table .info>td, .table .info>th {
    background-color: #d9edf7; /* Màu nền cho tiêu đề bảng */
}
</style>
<div id="xemkq" class="tab-pane fade">
  <div class="panel panel-primary">
    <div class="panel-body">
    @if(isset($emr_document) && count($emr_document))
      <div class="table">
          <table id="service_req" class="table table-striped table-bordered table-hover" width="100%">
              <thead>
                  <tr class="info">
                      <th style="text-align:center;">STT</th>
                      <th style="text-align:center;">Tên văn bản</th>
                      <th style="text-align:center;">Tác vụ</th>
                  </tr>
              </thead>
              <tbody>
                  @foreach($emr_document->groupBy('document_type_name') as $key_emr => $value_emr)
                    <tr class="active">
                        <td colspan="3"><strong>{{$key_emr ? $key_emr : 'Khác'}}</strong></td>
                    </tr>
                    @foreach($value_emr as $key => $value)
                        @php
                            $token = Crypt::encryptString($value->document_code . '|' . $value->treatment_code);
                        @endphp
                        <tr>
                            <td align="center">
                                {{ $key + 1 }}
                            </td>
                            <td>
                                {{ $value->document_name }}
                            </td>
                            <td align="center">
                                <!-- <a href="{{ route('view-doc', ['document_code'=>($value->document_code), 'treatment_code' => $value->treatment_code]) }}" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="glyphicon glyphicon-eye-open"></i> Xem
                                </a> -->
                                <a href="{{ route('secure-view-doc', ['token' => $token]) }}" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="glyphicon glyphicon-eye-open"></i> Xem
                                </a>
                            </td>
                        </tr>
                    @endforeach
                  @endforeach
              </tbody>
          </table>
      </div>
    @else
    <center>{{__('insurance.backend.labels.no_information')}}</center>
    @endif
    </div>
  </div>
</div>