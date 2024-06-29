@include('flash::message')
@if ($errors->any())
    <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
    </div>
@endif

@push('after-scripts')
<script>
    $('#flash-overlay-modal').modal();
</script>
@endpush