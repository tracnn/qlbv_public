/* Form builder chi tieu giao ban. Render field dong tu MetricSchema — khong hard-code type nao. */
var MetricBuilder = (function ($) {
  var SCHEMA = {}, ROUTES = {}, CSRF = '', BLOCK_LABELS = {};
  var st = { cfg: null, metrics: [], onSaved: null };
  // Chi so card dang duoc keo (HTML5 drag & drop). null = khong co keo nao dang dien ra.
  var dragSrcIndex = null;

  function esc(s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function init(opts) {
    SCHEMA = opts.schema || {};
    ROUTES = opts.routes || {};
    CSRF = opts.csrf || '';
    BLOCK_LABELS = opts.blockLabels || {};
    bind();
  }

  /** Cac type dung duoc voi block hien tai. */
  function typesForBlock(block) {
    var out = [];
    for (var k in SCHEMA) {
      if (SCHEMA[k].blocks.indexOf(block) >= 0) out.push(k);
    }
    return out;
  }

  function open(cfg, onSaved) {
    st.cfg = cfg;
    st.onSaved = onSaved;
    try {
      var parsed = JSON.parse(cfg.metrics || '[]');
      st.metrics = Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      st.metrics = [];
    }
    $('#mb-dept-name').text(cfg.display_name);
    $('#mb-block-label').text('[' + (BLOCK_LABELS[cfg.block_type] || cfg.block_type) + ']');
    $('#mb-save-msg').text('');
    $('#mb-preview-box').hide().empty();
    renderAddMenu();
    render();
    $('#mb-modal').modal('show');
  }

  function renderAddMenu() {
    var $m = $('#mb-add-menu').empty();
    typesForBlock(st.cfg.block_type).forEach(function (t) {
      $m.append('<li><a href="#" class="mb-add" data-type="' + t + '">' + esc(SCHEMA[t].label) + '</a></li>');
    });
  }

  /** Ma goi y tu ten: bo dau, thay khoang trang bang _. */
  function slug(name) {
    var s = String(name || '').toLowerCase()
      .replace(/[àáạảãâầấậẩẫăằắặẳẵ]/g, 'a').replace(/[èéẹẻẽêềếệểễ]/g, 'e')
      .replace(/[ìíịỉĩ]/g, 'i').replace(/[òóọỏõôồốộổỗơờớợởỡ]/g, 'o')
      .replace(/[ùúụủũưừứựửữ]/g, 'u').replace(/[ỳýỵỷỹ]/g, 'y').replace(/đ/g, 'd')
      .replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    if (!s || !/^[a-z]/.test(s)) s = 'ct_' + s;
    return s.substring(0, 32);
  }

  function maDuyNhat(goc) {
    var ma = goc, i = 2;
    while (st.metrics.some(function (m) { return m.code === ma; })) {
      ma = goc.substring(0, 29) + '_' + i;
      i++;
    }
    return ma;
  }

  function render() {
    var $l = $('#mb-list').empty();
    $('#mb-empty').toggle(st.metrics.length === 0);

    st.metrics.forEach(function (m, i) {
      var def = SCHEMA[m.type] || { label: m.type };
      $l.append(
        '<div class="panel panel-default mb-card" data-i="' + i + '" style="margin-bottom:6px">' +
          '<div class="panel-heading" style="padding:6px 10px">' +
            '<span class="mb-handle text-muted" draggable="true" title="Kéo để đổi thứ tự" style="margin-right:8px;cursor:move">&#x283F;</span>' +
            '<code>' + esc(m.code) + '</code> ' +
            '<b class="mb-name-view">' + esc(m.name) + '</b> ' +
            '<span class="label label-default">' + esc(def.label) + '</span> ' +
            '<span class="mb-warn"></span>' +
            '<span class="pull-right">' +
              '<a href="#" class="mb-toggle" title="Mở/đóng"><i class="fa fa-chevron-down"></i></a> ' +
              '<a href="#" class="mb-del text-red" title="Xoá"><i class="fa fa-trash"></i></a>' +
            '</span>' +
          '</div>' +
          '<div class="panel-body mb-body" style="display:none">' + renderBody(m, i) + '</div>' +
        '</div>'
      );
    });

    $('#mb-json').val(JSON.stringify(st.metrics, null, 2));
  }

  /** Task 12 se thay ham nay bang render field dong tu schema. */
  function renderBody(m, i) {
    return '<div class="row">' +
      '<div class="col-md-4"><label>Mã chỉ tiêu</label>' +
        '<input class="form-control mb-f" data-k="code" value="' + esc(m.code) + '"></div>' +
      '<div class="col-md-8"><label>Tên hiển thị</label>' +
        '<input class="form-control mb-f" data-k="name" value="' + esc(m.name) + '"></div>' +
      '</div>';
  }

  function themChiTieu(type) {
    var def = SCHEMA[type];
    var ten = def.label;
    st.metrics.push({ code: maDuyNhat(slug(ten)), name: ten, type: type });
    render();
    $('#mb-list .mb-card').last().find('.mb-body').show();
  }

  function luu() {
    $('#mb-save-msg').text('');
    $('#mb-list .mb-card').removeClass('panel-danger').addClass('panel-default');

    $.post(ROUTES.update.replace('__ID__', st.cfg.id), {
      _token: CSRF,
      metrics: JSON.stringify(st.metrics),
      block_type: st.cfg.block_type
    }).done(function () {
      $('#mb-modal').modal('hide');
      if (typeof st.onSaved === 'function') st.onSaved(JSON.stringify(st.metrics));
    }).fail(function (xhr) {
      hienLoi(xhr);
    });
  }

  /** To do dung card sai + hien thong bao (Task 15 mo rong them cho tab JSON). */
  function hienLoi(xhr) {
    var res = xhr.responseJSON || {};
    $('#mb-save-msg').text(res.message || 'Lỗi lưu chỉ tiêu');
    (res.errors || []).forEach(function (e) {
      if (e.index < 0) return;
      var $card = $('#mb-list .mb-card').eq(e.index);
      $card.removeClass('panel-default').addClass('panel-danger');
      $card.find('.mb-body').show();
      $card.find('.mb-warn').html(' <span class="text-red">' + esc(e.field + ': ' + e.message) + '</span>');
    });
  }

  function bind() {
    $(document).on('click', '.mb-add', function (e) {
      e.preventDefault();
      themChiTieu($(this).data('type'));
    });
    $(document).on('click', '.mb-toggle', function (e) {
      e.preventDefault();
      $(this).closest('.mb-card').find('.mb-body').toggle();
    });
    $(document).on('click', '.mb-del', function (e) {
      e.preventDefault();
      var i = $(this).closest('.mb-card').data('i');
      if (!confirm('Xoá chỉ tiêu "' + st.metrics[i].name + '"?')) return;
      st.metrics.splice(i, 1);
      render();
    });
    $(document).on('input', '#mb-list .mb-f', function () {
      var $c = $(this).closest('.mb-card');
      st.metrics[$c.data('i')][$(this).data('k')] = $(this).val();
      $c.find('.mb-name-view').text($(this).data('k') === 'name' ? $(this).val() : $c.find('.mb-name-view').text());
    });
    $(document).on('click', '#mb-save', luu);

    // Du an khong nap thu vien keo-tha-sap-xep ngoai (khong co file lien quan "jQuery UI"
    // trong public/), nen keo tha thu tu card dung thang HTML5 Drag and Drop API cua trinh duyet.
    $(document).on('dragstart', '.mb-handle', function (e) {
      dragSrcIndex = $(this).closest('.mb-card').data('i');
      var ev = e.originalEvent || e;
      if (ev.dataTransfer) {
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', String(dragSrcIndex));
      }
    });
    $(document).on('dragover', '.mb-card', function (e) {
      e.preventDefault(); // bat buoc: thieu dong nay trinh duyet se khong cho tha (drop)
      var ev = e.originalEvent || e;
      if (ev.dataTransfer) ev.dataTransfer.dropEffect = 'move';
      $(this).addClass('mb-drag-over');
    });
    $(document).on('dragleave', '.mb-card', function () {
      $(this).removeClass('mb-drag-over');
    });
    $(document).on('drop', '.mb-card', function (e) {
      e.preventDefault();
      $(this).removeClass('mb-drag-over');
      var dichI = $(this).data('i');
      if (dragSrcIndex === null || dragSrcIndex === undefined || dragSrcIndex === dichI) {
        dragSrcIndex = null;
        return;
      }
      var item = st.metrics.splice(dragSrcIndex, 1)[0];
      st.metrics.splice(dichI, 0, item);
      dragSrcIndex = null;
      render();
    });
    $(document).on('dragend', '.mb-card', function () {
      $(this).removeClass('mb-drag-over');
      dragSrcIndex = null;
    });
  }

  return { init: init, open: open };
})(jQuery);
