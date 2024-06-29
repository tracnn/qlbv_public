<!-- Modal form to add a category -->
<div id="addModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">×</button>
                <h4 class="modal-title"></h4>
            </div>
            <div class="modal-body">
                <form class="form-horizontal" role="form">
                    <div class="form-group">
                        <label class="control-label col-sm-2" for="title">Tiêu đề:</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="title_add" autofocus required="">
                            <p class="errorTitle text-center alert alert-danger hidden"></p>
                        </div>
                    </div>                 
                </form>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success add" data-dismiss="modal" onclick="addEntry()">
                        <span id="" class='glyphicon glyphicon-check'></span> Add
                    </button>
                    <button type="button" class="btn btn-warning" data-dismiss="modal">
                        <span class='glyphicon glyphicon-remove'></span> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Modal form to add a category -->