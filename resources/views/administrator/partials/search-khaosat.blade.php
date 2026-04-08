<div class="panel panel-default">
    <div class="panel-body">
        @include('partials.date_range')
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label for="execute_room_id">Phòng khám</label>
                        <select id="execute_room_id" class="form-control select2">
                            <option value="">-- Tất cả --</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @include('partials.load_data_button')
    </div>
</div>

@push('after-scripts')
    @stack('after-scripts-date-range')
    @stack('after-scripts-load-data-button')
    <script type="text/javascript">
        $(document).ready(function() {
            $.ajax({
                url: "{{ route('reports-administrator.fetch-execute-rooms') }}",
                type: 'GET',
                success: function(data) {
                    $.each(data, function(key, item) {
                        $('#execute_room_id').append(
                            '<option value="' + item.room_id + '">' + item.execute_room_name + '</option>'
                        );
                    });
                }
            });
        });
    </script>
@endpush
