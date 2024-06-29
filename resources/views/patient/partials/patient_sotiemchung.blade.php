<div id="sotiemchung" class="tab-pane fade">
  <div class="panel panel-primary">
    <div class="panel-body">
      @if(isset($vaccinations) && count($vaccinations))
        <table id="sotiemchung_table" class="table table-striped table-bordered table-hover" width="100%">
          <thead>
            <tr class="info">
              <th style="text-align:center;">STT</th>
              <th style="text-align:center;">Ngày Tiêm</th>
              <th style="text-align:center;">Liều số</th>
              <th style="text-align:center;">Người tiêm</th>
              <th style="text-align:center;">Tác dụng phụ</th>
            </tr>
          </thead>
          <tbody>
            @php 
              $counter = 1;
              $today = now()->format('Y-m-d'); 
            @endphp
            @foreach($vaccinations->groupBy('vaccine.name') as $vaccineName => $vaccineGroup)
              <tr>
                <td colspan="5" style="font-weight:bold;">{{ $vaccineName }}</td>
              </tr>
              @foreach ($vaccineGroup->sortBy('dose_number') as $index => $vaccination)
              @php
                $highlight = empty($vaccination->administered_by) || $vaccination->date_of_vaccination > $today;
              @endphp
                <tr class="{{ $highlight ? 'highlight-red' : '' }}">
                  <td>{{ $counter++ }}</td>
                  <td>{{ $vaccination->date_of_vaccination }}</td>
                  <td style="text-align:right;">{{ $vaccination->dose_number }}</td>
                  <td>{{ $vaccination->administered_by }}</td>
                  <td>{{ $vaccination->description_effect }}</td>
                </tr>
              @endforeach
            @endforeach
          </tbody>
        </table>       
      @else
        <center>{{ __('insurance.backend.labels.no_information') }}</center>
      @endif
    </div>
  </div>
</div>