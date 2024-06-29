<div id="xemchiphi" class="tab-pane fade">
  <div class="panel panel-primary">
    <div class="panel-body">
    <label>Hóa đơn điện tử</label>
    @if($tracuuhoadon)
      <div class="table">
        <table id="einvoice_table" class="table table-striped table-bordered table-hover" width="100%">
          <thead>
              <tr class="info">
                  <th style="text-align:center;">STT</th>
                  <th style="text-align:center;">Ngày hóa đơn</th>
                  <th style="text-align:center;">BN t.toán</th>
                  <th style="text-align:center;">Tra cứu</th>
              </tr>
          </thead>
          <tbody>
            @foreach($tracuuhoadon as $key => $value)
              <tr> <!-- Mở một hàng mới cho mỗi bản ghi -->
                <td>
                  {{ $loop->iteration }}
                </td>
                <td>
                  {{ date('d-m-Y', strtotime($value->einvoice_time)) }} <!-- Định dạng ngày tháng cho dễ đọc -->
                </td>
                <td style="text-align:right;">
                  {{ number_format(floor($value->amount)) }}₫ <!-- Định dạng số tiền -->
                </td>
                <td>
                  <a href="https://tchd.ehoadon.vn/TCHD?MTC={{ $value->invoice_lookup_code }}" 
                    class="btn btn-sm btn-primary" target="_blank">
                    <span class="glyphicon glyphicon-eye-open"></span> Tra cứu</a>
                </td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr>
              <th colspan="2" style="text-align:center;">Tổng cộng</th>
              <th style="text-align:right;">
                {{ number_format(floor($tracuuhoadon->sum('amount'))) }}₫
              </th>
              <th style="text-align:right; color:red;">
                (Khác: {{ number_format(floor($sere_serv_chiphi->sum('thanh_tien') - $tracuuhoadon->sum('amount'))) }}₫)
              </th>
            </tr>
          </tfoot>
        </table>        
      </div>

    @else
    <center>{{__('insurance.backend.labels.no_information')}}</center>
    @endif
    </div>
    <div class="panel-body">
      <label>Chi phí KCB</label>
	  @if($sere_serv_total && $transactions)
    <div class="card-body">
        <table class="table table-borderless table-hover">
            <tbody>
                <tr>
                    <td><label>Cần Thanh Toán:</label></td>
                    <td class="text-right {{ $sere_serv_total->total_patient_price - 
            ($transactions->tam_ung - $transactions->hoan_ung + $transactions->da_thanh_toan) <= 0 ? 'text-primary' : 'text-danger' }}"><strong>{{ number_format(floor($sere_serv_total->total_patient_price - 
            ($transactions->tam_ung - $transactions->hoan_ung + $transactions->da_thanh_toan))) }}₫</strong></td>
                </tr>
                <tr>
                    <td>Tổng Chi Phí:</td>
                    <td class="text-right"><strong>{{ number_format(floor($sere_serv_total->total_price)) }}₫</strong></td>
                </tr>
                <tr>
                    <td>BHYT Chi Trả:</td>
                    <td class="text-right"><strong>{{ number_format(floor($sere_serv_total->total_hein_price)) }}₫</strong></td>
                </tr>
                <tr>
                    <td>Người Bệnh Chi Trả:</td>
                    <td class="text-right"><strong>{{ number_format(floor($sere_serv_total->total_patient_price)) }}₫</strong></td>
                </tr>
                <tr>
                    <td>Đã Tạm Ứng:</td>
                    <td class="text-right"><strong>{{ number_format($transactions->tam_ung) }}₫</strong></td>
                </tr>
                <tr>
                    <td>Đã Hoàn Ứng:</td>
                    <td class="text-right"><strong>{{ number_format($transactions->hoan_ung) }}₫</strong></td>
                </tr>
                <tr>
                    <td>Đã Thanh Toán:</td>
                    <td class="text-right"><strong>{{ number_format($transactions->da_thanh_toan) }}₫</strong></td>
                </tr>
                <tr class="highlight-red">
                    <td>Chi Phí Khác:</td>
                    <td class="text-right"><strong>{{ number_format($transactions->tu_nhap) }}₫</strong></td>
                </tr>
            </tbody>
        </table>
    </div>
	@endif
    @if($sere_serv_chiphi)
    <div class="table">
      <table id="costs_table" class="table table-striped table-bordered table-hover">
        <thead>
          <tr class="info">
            <th style="text-align:center;">STT</th>
            <th style="text-align:center;">Loại dịch vụ</th>
            <th style="text-align:center;">SL</th>
            <th style="text-align:center;">Thành tiền</th>
          </tr>
        </thead>
        <tbody>
          @foreach($sere_serv_chiphi as $key => $value)
          <tr>
            <td align="center">
              {{ $loop->iteration }}
            </td>
            <td>
              {{ $value->service_type_name }}
            </td>
            <td align="right">
              {{ number_format($value->so_luong) }}
            </td>
            <td align="right">
              {{ number_format($value->thanh_tien) }}₫
            </td>
          </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <th colspan="3" style="text-align:center;">Tổng cộng</th>
            <th style="text-align:right;">
              {{ number_format(floor($sere_serv_chiphi->sum('thanh_tien'))) }}₫
            </th>
          </tr>
        </tfoot>
      </table>
    </div>
    @else
    <center>{{__('insurance.backend.labels.no_information')}}</center>
    @endif
    </div>
  </div>
</div>