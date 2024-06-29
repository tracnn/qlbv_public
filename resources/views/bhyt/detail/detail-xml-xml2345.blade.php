<ul class="nav nav-tabs">
  <li class="active"><a data-toggle="tab" href="#menu1">Thuốc, VT - XML2</a></li>
  <li><a data-toggle="tab" href="#menu2">DVKT - XML3</a></li>
  <li><a data-toggle="tab" href="#menu3">CLS - XML4</a></li>
  <li><a data-toggle="tab" href="#menu4">Diễn biến - XML5</a></li>
</ul>

<div class="tab-content">
  <div id="menu1" class="tab-pane fade in active">
    <div class="panel panel-default">
      <div class="panel-body">
          <table id="thuocvt" class="table table-hover responsive datatable dtr-inline" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã thuốc</th>
                    <th>Tên thuốc</th>
                    <th>Nhóm</th>
                    <th>Liều dùng</th>
                    <th>TT thầu</th>
                    <th>SL</th>
                    <th>Khoa</th>
                    <th>Bác sĩ</th>
                    <th>Mã bệnh</th>
                    <th>Ngày YL</th>
                </tr>
            </thead>
            <tbody>
              @foreach($xml2 as $value_xml2)
                <tr>
                  <td align="right">{{$value_xml2->STT}}</td>
                  <td>{{$value_xml2->MA_THUOC}}</td>
                  <td>{{$value_xml2->TEN_THUOC}}</td>
                  <td>{{config('__tech.pl6_4210')[$value_xml2->MA_NHOM]}}</td>
                  <td>{{$value_xml2->LIEU_DUNG}}</td>
                  <td>{{$value_xml2->TT_THAU}}</td>
                  <td align="right">{{number_format($value_xml2->SO_LUONG)}}</td>
                  <td>{{$value_xml2->MA_KHOA}}</td>
                  <td>{{$value_xml2->MA_BAC_SI}}</td>
                  <td>{{$value_xml2->MA_BENH}}</td>                                    
                  <td>{{strtodatetime($value_xml2->NGAY_YL)}}</td>
                </tr>
              @endforeach
            </tbody>
        </table>
      </div>
    </div>

  </div>
  <div id="menu2" class="tab-pane fade">
    <div class="panel panel-default">
      <div class="panel-body">
        <table id="dvkt" class="table table-hover responsive datatable dtr-inline" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã DV</th>
                    <th>Mã VT</th>
                    <th>Tên DV</th>
                    <th>Tên VT</th>
                    <th>Nhóm</th>
                    <th>ĐVT</th>
                    <th>Trần BHTT</th>
                    <th>SL</th>
                    <th>Giá</th>
                    <th>TT thầu</th>
                    <th>Khoa</th>
                    <th>Bác sĩ</th>
                    <th>Mã bệnh</th>
                    <th>Ngày YL</th>
                </tr>
            </thead>
            <tbody>
              @foreach($xml3 as $value_xml3)
                <tr>
                  <td align="right">{{$value_xml3->STT}}</td>
                  <td>{{$value_xml3->MA_DICH_VU}}</td>
                  <td>{{$value_xml3->MA_VAT_TU}}</td>
                  <td>{{$value_xml3->TEN_DICH_VU}}</td>
                  <td>{{$value_xml3->TEN_VAT_TU}}</td>
                  <td>{{config('__tech.pl6_4210')[$value_xml3->MA_NHOM]}}</td>
                  <td>{{$value_xml3->DON_VI_TINH}}</td>
                  <td align="right">{{number_format($value_xml3->T_TRANTT,2)}}</td> 
                  <td align="right">{{number_format($value_xml3->SO_LUONG)}}</td> 
                  <td align="right">{{number_format($value_xml3->DON_GIA)}}</td> 
                  <td>{{$value_xml3->TT_THAU}}</td> 
                  <td>{{$value_xml3->MA_KHOA}}</td> 
                  <td>{{$value_xml3->MA_BAC_SI}}</td> 
                  <td>{{$value_xml3->MA_BENH}}</td> 
                  <td>{{strtodatetime($value_xml3->NGAY_YL)}}</td>                
                </tr>
              @endforeach
            </tbody>
        </table>
      </div>
    </div>
  </div>
  <div id="menu3" class="tab-pane fade">
    <div class="panel panel-default">
      <div class="panel-body">
        <table id="cls" class="table table-hover responsive datatable dtr-inline" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Mã DV</th>
                    <th>Mã chỉ số</th>
                    <th>Tên chỉ số</th>
                    <th>Giá trị</th>
                    <th>Mã máy</th>
                    <th>Mô tả</th>
                    <th>Kết luận</th>
                    <th>Ngày KQ</th>
                </tr>
            </thead>
              <tbody>
              @foreach($xml4 as $value_xml4)
                <tr>
                  <td align="right">{{$value_xml4->STT}}</td>
                  <td>{{$value_xml4->MA_DICH_VU}}</td>
                  <td>{{$value_xml4->MA_CHI_SO}}</td>
                  <td>{{$value_xml4->TEN_CHI_SO}}</td>
                  <td>{{$value_xml4->GIA_TRI}}</td>
                  <td>{{$value_xml4->MA_MAY}}</td> 
                  <td>{{$value_xml4->MO_TA}}</td> 
                  <td>{{$value_xml4->KET_LUAN}}</td>  
                  <td>{{$value_xml4->NGAY_KQ}}</td>         
                </tr>
              @endforeach
            </tbody>
        </table>
      </div>
    </div>
  </div>
  <div id="menu4" class="tab-pane fade">
    <div class="panel panel-default">
      <div class="panel-body">
        <table id="dienbien" class="table table-hover responsive datatable dtr-inline" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Diễn biến</th>
                    <th>Hội chẩn</th>
                    <th>Phẫu thuật</th>
                    <th>Ngày YL</th>
                </tr>
            </thead>
            <tbody>
              @foreach($xml5 as $value_xml5)
              <tr>
                <td>{{$value_xml5->STT}}</td>
                <td>{{$value_xml5->DIEN_BIEN}}</td>
                <td>{{$value_xml5->HOI_CHAN}}</td>
                <td>{{$value_xml5->PHAU_THUAT}}</td>
                <td>{{strtodatetime($value_xml5->NGAY_YL)}}</td>
              </tr>
              @endforeach
            </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@push('after-scripts')
<script>
$(document).ready( function () {
    $('#thuocvt').DataTable({
      "stateSave": true,
    });
    $('#dvkt').DataTable({
      "stateSave": true,
    });
    $('#cls').DataTable({
      "stateSave": true,
    });
    $('#dienbien').DataTable({
      "stateSave": true,
    });
});
</script>
@endpush