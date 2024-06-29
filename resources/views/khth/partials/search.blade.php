@push('after-styles')
<!-- Include daterangepicker CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endpush

<div class="panel panel-default">
    <div class="panel-body">
        <div class="col-sm-12">
            <div class="form-group row">
                <div class="col-sm-4">
                    <div class="form-group row">
                        <label for="date_range">Chọn khoảng thời gian</label>
                        <input class="form-control" type="text" id="date_range">
                    </div>
                </div>
                <div class="input-group-append">
                    <button type="button" class="btn btn-primary form-control" id="load_data_button">Tải dữ liệu...</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('after-scripts')

<!-- Include daterangepicker JS -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script type="text/javascript">
    $(document).ready(function() {
        setDefaultDates();

        function setDefaultDates() {
            var today = new Date();
            var formattedDate = formatDate(today);
            var startDate = moment().startOf('month').format('YYYY-MM-DD');
            var endDate = moment().endOf('month').format('YYYY-MM-DD');

            $('#date_range').daterangepicker({
                startDate: startDate,
                endDate: endDate,
                locale: {
                    format: 'YYYY-MM-DD'
                }
            });
        }

        function formatDate(date) {
            var d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2) 
                month = '0' + month;
            if (day.length < 2) 
                day = '0' + day;

            return [year, month, day].join('-');
        }
    });
</script>

@endpush