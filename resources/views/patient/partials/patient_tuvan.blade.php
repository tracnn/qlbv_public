<div id="tuvan" class="tab-pane fade">
  <div class="panel panel-primary">
    <div class="panel-body">
    @if(isset($service_kham))
      {!!nl2br(e($service_kham->treatment_instruction)) 
      .'<br>'
      .nl2br(e($service_kham->next_treatment_instruction))!!}
    @else
    <center>{{__('insurance.backend.labels.no_information')}}</center>
    @endif
    </div>
  </div>
</div>