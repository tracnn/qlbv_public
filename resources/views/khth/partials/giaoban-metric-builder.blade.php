<style>
  /* Goi y thi giac khi keo card qua vi tri co the tha (HTML5 drag & drop, khong dung jQuery UI). */
  #mb-list .mb-card.mb-drag-over { outline: 2px dashed #3c8dbc; outline-offset: -2px; }
</style>
<div class="modal fade" id="mb-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Tiêu chí — <span id="mb-dept-name"></span>
          <small id="mb-block-label" class="text-muted"></small></h4>
      </div>
      <div class="modal-body">
        <div class="btn-toolbar" style="margin-bottom:8px">
          <div class="btn-group">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
              <i class="fa fa-plus"></i> Thêm tiêu chí <span class="caret"></span></button>
            <ul class="dropdown-menu" id="mb-add-menu"></ul>
          </div>
          <div class="btn-group">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Nạp mẫu <span class="caret"></span></button>
            <ul class="dropdown-menu" id="mb-tpl-menu"></ul>
          </div>
          <div class="btn-group">
            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Nhân bản từ khoa <span class="caret"></span></button>
            <ul class="dropdown-menu" id="mb-clone-menu"></ul>
          </div>
          <button class="btn btn-info" id="mb-preview"><i class="fa fa-bolt"></i> Tính thử</button>
        </div>

        <ul class="nav nav-tabs">
          <li class="active"><a href="#mb-tab-form" data-toggle="tab">Form</a></li>
          <li><a href="#mb-tab-json" data-toggle="tab">JSON (nâng cao)</a></li>
        </ul>
        <div class="tab-content" style="padding-top:10px">
          <div class="tab-pane active" id="mb-tab-form">
            <div id="mb-preview-box" style="display:none;margin-bottom:10px"></div>
            <div id="mb-list"></div>
            <p class="text-muted" id="mb-empty" style="display:none">
              <i>Chưa có tiêu chí nào. Bấm "Thêm tiêu chí" hoặc "Nạp mẫu".</i></p>
          </div>
          <div class="tab-pane" id="mb-tab-json">
            <textarea id="mb-json" class="form-control" rows="18" spellcheck="false"></textarea>
            <p class="help-block" id="mb-json-msg"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <span id="mb-save-msg" class="text-danger pull-left" style="text-align:left"></span>
        <button class="btn btn-default" data-dismiss="modal">Huỷ</button>
        <button class="btn btn-primary" id="mb-save">Lưu tiêu chí</button>
      </div>
    </div>
  </div>
</div>
