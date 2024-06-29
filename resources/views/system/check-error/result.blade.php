@if(isset($result) && $result->count())
<div class="panel panel-default">
    <div class="panel-body">
        <!-- title -->
        <div>
            <b>Tổng số bản ghi: {{$result->count()}}</b>
        </div>
        <!-- /title -->
        <div class="table table-responsive">
            <table class="table table-condensed table-hover">
                <thead>
                    <th>Mã liên kết</th>
                    <th>Điều trị</th>
                    <th>Mã biên lai</th>
                    <th>Mã quyển thu</th>
                    <th>Số biên lai</th>
                    <th>Mã lần khám</th>
                </thead>
                <tbody>
                @switch($params['loai_hoso'])
                @case(1)
                    @foreach($result as $key => $value)
                        <tr>
                            <td>{{$value->sophieu}}</td>
                            <td>{{$value->dm_phongkham ? $value->dm_phongkham->tenpk : ''}}</td>
                            <td>
                                {{$value->bn_vienphipk ? $value->bn_vienphipk->mabienlai : ''}}
                            </td>
                            <td>
                                {{$value->bn_vienphipk ? $value->bn_vienphipk->quyenso : ''}}
                            </td>
                            <td>
                                {{$value->bn_vienphipk ? $value->bn_vienphipk->sobienlai : ''}}
                            </td>
                            <td>{{$value->malankham}}</td>
                        </tr>
                    @endforeach
                    @break
                @case(2)
                    @foreach($result as $key => $value)
                        <tr>
                            <td>{{$value->malankham}}</td>
                            <td>{{$value->dm_khoaph ? $value->dm_khoaph->tenkhp : ''}}</td>
                            <td>{{$value->hoadonravien ? $value->hoadonravien->idhoadon : ''}}</td>
                            <td>{{$value->hoadonravien ? $value->hoadonravien->maquyenthu : ''}}</td>
                            <td>{{$value->hoadonravien ? $value->hoadonravien->sobienlai : ''}}</td>
                            <td>{{$value->malankham}}
                            </td>
                        </tr>  
                    @endforeach
                    @break
                @endswitch
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif