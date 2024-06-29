<!-- resources/views/partials/chiphi_table.blade.php -->
<table class="table display table-hover responsive nowrap datatable dtr-inline" width="100%">
    <thead>
        <tr>
            <th>STT</th>
            <th>Khoa chỉ định</th>
            <th>XN kỳ này</th>
            <th>% Thay đổi</th>
            <th>CDHA kỳ này</th>
            <th>% Thay đổi</th>
            <th>Thuốc kỳ này</th>
            <th>% Thay đổi</th>
            <th>Máu kỳ này</th>
            <th>% Thay đổi</th>
            <th>TT kỳ này</th>
            <th>% Thay đổi</th>
            <th>VTYT kỳ này</th>
            <th>% Thay đổi</th>
            <th>TDCN kỳ này</th>
            <th>% Thay đổi</th>
            <th>PT kỳ này</th>
            <th>% Thay đổi</th>
            <th>Khám kỳ này</th>
            <th>% Thay đổi</th>
            <th>Giường kỳ này</th>
            <th>% Thay đổi</th>
        </tr>
    </thead>
    <tbody>
        @php
            use Illuminate\Support\Collection;

            // Chuyển đổi mảng thành Collection nếu chưa phải là Collection
            $dataCollection = $data instanceof Collection ? $data : collect($data);

            $totals = [
                't_xn' =>           $dataCollection->sum('cost_this_data_t_xn'),
                't_cdha' =>         $dataCollection->sum('cost_this_data_t_cdha'),
                't_thuoc' =>        $dataCollection->sum('cost_this_data_t_thuoc'),
                't_mau' =>          $dataCollection->sum('cost_this_data_t_mau'),
                't_tt' =>           $dataCollection->sum('cost_this_data_t_tt'),
                't_vtyt' =>         $dataCollection->sum('cost_this_data_t_vtyt'),
                't_tdcn' =>         $dataCollection->sum('cost_this_data_t_tdcn'),
                't_pt' =>           $dataCollection->sum('cost_this_data_t_pt'),
                't_kh' =>           $dataCollection->sum('cost_this_data_t_kh'),
                't_gi' =>           $dataCollection->sum('cost_this_data_t_gi'),
                't_xn_last' =>      $dataCollection->sum('cost_last_data_t_xn'),
                't_cdha_last' =>    $dataCollection->sum('cost_last_data_t_cdha'),
                't_thuoc_last' =>   $dataCollection->sum('cost_last_data_t_thuoc'),
                't_mau_last' =>     $dataCollection->sum('cost_last_data_t_mau'),
                't_tt_last' =>      $dataCollection->sum('cost_last_data_t_tt'),
                't_vtyt_last' =>    $dataCollection->sum('cost_last_data_t_vtyt'),
                't_tdcn_last' =>    $dataCollection->sum('cost_last_data_t_tdcn'),
                't_pt_last' =>      $dataCollection->sum('cost_last_data_t_pt'),
                't_kh_last' =>      $dataCollection->sum('cost_last_data_t_kh'),
                't_gi_last' =>      $dataCollection->sum('cost_last_data_t_gi'),
            ];

            $percent_change = function ($current, $previous) {
                if ($previous == 0) {
                    return $current > 0 ? 100 : ($current < 0 ? -100 : 0);
                }
                return (($current - $previous) / $previous) * 100;
            };

            $total_percent_changes = [
                't_xn' => $percent_change($totals['t_xn'], $totals['t_xn_last']),
                't_cdha' => $percent_change($totals['t_cdha'], $totals['t_cdha_last']),
                't_thuoc' => $percent_change($totals['t_thuoc'], $totals['t_thuoc_last']),
                't_mau' => $percent_change($totals['t_mau'], $totals['t_mau_last']),
                't_tt' => $percent_change($totals['t_tt'], $totals['t_tt_last']),
                't_vtyt' => $percent_change($totals['t_vtyt'], $totals['t_vtyt_last']),
                't_tdcn' => $percent_change($totals['t_tdcn'], $totals['t_tdcn_last']),
                't_pt' => $percent_change($totals['t_pt'], $totals['t_pt_last']),
                't_kh' => $percent_change($totals['t_kh'], $totals['t_kh_last']),
                't_gi' => $percent_change($totals['t_gi'], $totals['t_gi_last']),
            ];
        @endphp

        <!-- Dòng tổng cộng -->
        <tr>
            <td colspan="2"><strong>Tổng cộng</strong></td>
            <td><strong>{{ number_format(round($totals['t_xn'])) }}</strong></td>
            <td><strong style="color: {{ $total_percent_changes['t_xn'] > 0 ? 'red' : ($total_percent_changes['t_xn'] < 0 ? 'green' : 'black') }}">{{ number_format(round($total_percent_changes['t_xn'])) }}%</strong></td>
            <td><strong>{{ number_format(round($totals['t_cdha'])) }}</strong></td>
            <td><strong style="color: {{ $total_percent_changes['t_cdha'] > 0 ? 'red' : ($total_percent_changes['t_cdha'] < 0 ? 'green' : 'black') }}">{{ number_format(round($total_percent_changes['t_cdha'])) }}%</strong></td>
            <td><strong>{{ number_format(round($totals['t_thuoc'])) }}</strong></td>
            <td><strong style="color: {{ $total_percent_changes['t_thuoc'] > 0 ? 'red' : ($total_percent_changes['t_thuoc'] < 0 ? 'green' : 'black') }}">{{ number_format(round($total_percent_changes['t_thuoc'])) }}%</strong></td>
            <td><strong>{{ number_format(round($totals['t_mau'])) }}</strong></td>
            <td><strong style="color: {{ $total_percent_changes['t_mau'] > 0 ? 'red' : ($total_percent_changes['t_mau'] < 0 ? 'green' : 'black') }}">{{ number_format(round($total_percent_changes['t_mau'])) }}%</strong></td>
            <td><strong>{{ number_format(round($totals['t_tt'])) }}</strong></td>
            <td><strong style="color: {{ $total_percent_changes['t_tt'] > 0 ? 'red' : ($total_percent_changes['t_tt'] < 0 ? 'green' : 'black') }}">{{ number_format(round($total_percent_changes['t_tt'])) }}%</strong></td>
            <td><strong>{{ number_format(round($totals['t_vtyt'])) }}</strong></td>
            <td><strong style="color: {{ $total_percent_changes['t_vtyt'] > 0 ? 'red' : ($total_percent_changes['t_vtyt'] < 0 ? 'green' : 'black') }}">{{ number_format(round($total_percent_changes['t_vtyt'])) }}%</strong></td>
            <td><strong>{{ number_format(round($totals['t_tdcn'])) }}</strong></td>
            <td><strong style="color: {{ $total_percent_changes['t_tdcn'] > 0 ? 'red' : ($total_percent_changes['t_tdcn'] < 0 ? 'green' : 'black') }}">{{ number_format(round($total_percent_changes['t_tdcn'])) }}%</strong></td>
            <td><strong>{{ number_format(round($totals['t_pt'])) }}</strong></td>
            <td><strong style="color: {{ $total_percent_changes['t_pt'] > 0 ? 'red' : ($total_percent_changes['t_pt'] < 0 ? 'green' : 'black') }}">{{ number_format(round($total_percent_changes['t_pt'])) }}%</strong></td>
            <td><strong>{{ number_format(round($totals['t_kh'])) }}</strong></td>
            <td><strong style="color: {{ $total_percent_changes['t_kh'] > 0 ? 'red' : ($total_percent_changes['t_kh'] < 0 ? 'green' : 'black') }}">{{ number_format(round($total_percent_changes['t_kh'])) }}%</strong></td>
            <td><strong>{{ number_format(round($totals['t_gi'])) }}</strong></td>
            <td><strong style="color: {{ $total_percent_changes['t_gi'] > 0 ? 'red' : ($total_percent_changes['t_gi'] < 0 ? 'green' : 'black') }}">{{ number_format(round($total_percent_changes['t_gi'])) }}%</strong></td>
        </tr>

        @foreach ($dataCollection as $row)
            @php
                // Tính toán tỷ lệ % thay đổi
                $percent_changes = [
                    't_xn' => $percent_change($row->cost_this_data_t_xn, $row->cost_last_data_t_xn),
                    't_cdha' => $percent_change($row->cost_this_data_t_cdha, $row->cost_last_data_t_cdha),
                    't_thuoc' => $percent_change($row->cost_this_data_t_thuoc, $row->cost_last_data_t_thuoc),
                    't_mau' => $percent_change($row->cost_this_data_t_mau, $row->cost_last_data_t_mau),
                    't_tt' => $percent_change($row->cost_this_data_t_tt, $row->cost_last_data_t_tt),
                    't_vtyt' => $percent_change($row->cost_this_data_t_vtyt, $row->cost_last_data_t_vtyt),
                    't_tdcn' => $percent_change($row->cost_this_data_t_tdcn, $row->cost_last_data_t_tdcn),
                    't_pt' => $percent_change($row->cost_this_data_t_pt, $row->cost_last_data_t_pt),
                    't_kh' => $percent_change($row->cost_this_data_t_kh, $row->cost_last_data_t_kh),
                    't_gi' => $percent_change($row->cost_this_data_t_gi, $row->cost_last_data_t_gi)
                ];
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row->deptname }}</td>
                <td>{{ number_format(round($row->cost_this_data_t_xn)) }}</td>
                <td style="color: {{ $percent_changes['t_xn'] > 0 ? 'red' : ($percent_changes['t_xn'] < 0 ? 'green' : 'black') }}">{{ number_format(round($percent_changes['t_xn'])) }}%</td>
                <td>{{ number_format(round($row->cost_this_data_t_cdha)) }}</td>
                <td style="color: {{ $percent_changes['t_cdha'] > 0 ? 'red' : ($percent_changes['t_cdha'] < 0 ? 'green' : 'black') }}">{{ number_format(round($percent_changes['t_cdha'])) }}%</td>
                <td>{{ number_format(round($row->cost_this_data_t_thuoc)) }}</td>
                <td style="color: {{ $percent_changes['t_thuoc'] > 0 ? 'red' : ($percent_changes['t_thuoc'] < 0 ? 'green' : 'black') }}">{{ number_format(round($percent_changes['t_thuoc'])) }}%</td>
                <td>{{ number_format(round($row->cost_this_data_t_mau)) }}</td>
                <td style="color: {{ $percent_changes['t_mau'] > 0 ? 'red' : ($percent_changes['t_mau'] < 0 ? 'green' : 'black') }}">{{ number_format(round($percent_changes['t_mau'])) }}%</td>
                <td>{{ number_format(round($row->cost_this_data_t_tt)) }}</td>
                <td style="color: {{ $percent_changes['t_tt'] > 0 ? 'red' : ($percent_changes['t_tt'] < 0 ? 'green' : 'black') }}">{{ number_format(round($percent_changes['t_tt'])) }}%</td>
                <td>{{ number_format(round($row->cost_this_data_t_vtyt)) }}</td>
                <td style="color: {{ $percent_changes['t_vtyt'] > 0 ? 'red' : ($percent_changes['t_vtyt'] < 0 ? 'green' : 'black') }}">{{ number_format(round($percent_changes['t_vtyt'])) }}%</td>
                <td>{{ number_format(round($row->cost_this_data_t_tdcn)) }}</td>
                <td style="color: {{ $percent_changes['t_tdcn'] > 0 ? 'red' : ($percent_changes['t_tdcn'] < 0 ? 'green' : 'black') }}">{{ number_format(round($percent_changes['t_tdcn'])) }}%</td>
                <td>{{ number_format(round($row->cost_this_data_t_pt)) }}</td>
                <td style="color: {{ $percent_changes['t_pt'] > 0 ? 'red' : ($percent_changes['t_pt'] < 0 ? 'green' : 'black') }}">{{ number_format(round($percent_changes['t_pt'])) }}%</td>
                <td>{{ number_format(round($row->cost_this_data_t_kh)) }}</td>
                <td style="color: {{ $percent_changes['t_kh'] > 0 ? 'red' : ($percent_changes['t_kh'] < 0 ? 'green' : 'black') }}">{{ number_format(round($percent_changes['t_kh'])) }}%</td>
                <td>{{ number_format(round($row->cost_this_data_t_gi)) }}</td>
                <td style="color: {{ $percent_changes['t_gi'] > 0 ? 'red' : ($percent_changes['t_gi'] < 0 ? 'green' : 'black') }}">{{ number_format(round($percent_changes['t_gi'])) }}%</td>
            </tr>
        @endforeach
    </tbody>
</table>