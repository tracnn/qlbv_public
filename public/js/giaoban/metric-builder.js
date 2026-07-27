/* Form builder chi tieu giao ban. Render field dong tu MetricSchema — khong hard-code type nao. */
var MetricBuilder = (function ($) {
  var SCHEMA = {}, ROUTES = {}, CSRF = '', BLOCK_LABELS = {};
  var st = { cfg: null, metrics: [], onSaved: null, templates: [], configs: [] };
  var CATALOGS = null; // null = chua tai
  var REMOTE = []; // danh sach key danh muc lon (tim qua AJAX thay vi tai tron goi)
  // Chi so card dang duoc keo (HTML5 drag & drop). null = khong co keo nao dang dien ra.
  var dragSrcIndex = null;

  /** Y nghia cac canh bao tra ve tu route tinh-thu — phai giai thich ro, khong chi to mau. */
  var CANH_BAO = {
    no_scope: ['danger', 'Chưa có phạm vi khoa — số 0 là do cấu hình, không phải do không có dịch vụ'],
    no_dept:  ['danger', 'Cấu hình chưa gán khoa HIS nào'],
    manual:   ['info', 'Chỉ tiêu nhập tay — không có số tự động']
  };

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
    REMOTE = opts.remoteCatalogs || [];
    bind();
  }

  /** Danh muc lon (tim qua AJAX) hay danh muc nho (du lieu co san trong CATALOGS). */
  function laDanhMucLon(key) {
    return REMOTE.indexOf(key) >= 0;
  }

  /** Cac type dung duoc voi block hien tai. */
  function typesForBlock(block) {
    var out = [];
    for (var k in SCHEMA) {
      if (SCHEMA[k].blocks.indexOf(block) >= 0) out.push(k);
    }
    return out;
  }

  /** Tai danh muc nhom nho (HIS) mot lan roi cache lai cho ca phien lam viec modal. */
  function taiDanhMuc(xong) {
    if (CATALOGS) { xong(); return; }
    $.get(ROUTES.catalogs, function (res) {
      CATALOGS = res.catalogs || {};
      xong();
    }).fail(function () {
      CATALOGS = {};                       // HIS loi -> van mo duoc modal, dropdown rong
      $('#mb-save-msg').text('Không tải được danh mục HIS — các ô chọn sẽ trống.');
      xong();
    });
  }

  function open(cfg, onSaved, data) {
    st.cfg = cfg;
    st.onSaved = onSaved;
    st.templates = (data && data.templates) || [];
    st.configs = (data && data.configs) || [];
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
    taiDanhMuc(function () {
      renderAddMenu();
      renderTplMenu();
      renderCloneMenu();
      render();
      $('#mb-modal').modal('show');
    });
  }

  /** Menu "Nạp mẫu": chi cac mau cung khoi voi khoa dang sua. */
  function renderTplMenu() {
    var $m = $('#mb-tpl-menu').empty();
    var co = false;
    st.templates.forEach(function (t) {
      if (t.block_type !== st.cfg.block_type) return;
      co = true;
      $m.append('<li><a href="#" class="mb-tpl" data-id="' + t.id + '">' + esc(t.name) + '</a></li>');
    });
    if (co) $m.append('<li class="divider"></li>');
    $m.append('<li><a href="#" id="mb-tpl-save"><i class="fa fa-save"></i> Lưu bộ này thành mẫu…</a></li>');
    if (!co) $m.prepend('<li class="disabled"><a href="#">(chưa có mẫu cho khối này)</a></li>');
  }

  /** Menu "Nhân bản từ khoa khác": chi cac khoa cung khoi, khong gom chinh no. */
  function renderCloneMenu() {
    var $m = $('#mb-clone-menu').empty();
    var co = false;
    st.configs.forEach(function (c) {
      if (c.id === st.cfg.id || c.block_type !== st.cfg.block_type) return;
      co = true;
      $m.append('<li><a href="#" class="mb-clone" data-id="' + c.id + '">' + esc(c.display_name) + '</a></li>');
    });
    if (!co) $m.append('<li class="disabled"><a href="#">(không có khoa cùng khối)</a></li>');
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
      var ghiChu = '';
      var d = SCHEMA[m.type] || {};
      for (var ff in (d.filter || {})) {
        var ok = (d.filter[ff] || {}).other_key;
        if (ok && (m.filter || {})[ok]) ghiChu = ' <small class="text-muted">(nhóm Khác)</small>';
      }
      $l.append(
        '<div class="panel panel-default mb-card" data-i="' + i + '" style="margin-bottom:6px">' +
          '<div class="panel-heading" style="padding:6px 10px">' +
            '<span class="mb-handle text-muted" draggable="true" title="Kéo để đổi thứ tự" style="margin-right:8px;cursor:move">&#x283F;</span>' +
            '<code>' + esc(m.code) + '</code> ' +
            '<b class="mb-name-view">' + esc(m.name) + '</b> ' +
            '<span class="label label-default">' + esc(def.label) + '</span>' + ghiChu + ' ' +
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

    ganSelect2($l);
    $('#mb-json').val(JSON.stringify(st.metrics, null, 2));
  }

  /** Doc gia tri cua mot field theo vi tri khai bao: goc / filter / input. */
  function layGiaTri(m, noi, ten) {
    if (noi === 'filter') return (m.filter || {})[ten];
    if (noi === 'input') return (m.input || {})[ten];
    return m[ten];
  }

  function datGiaTri(m, noi, ten, v) {
    var rong = v === '' || v === null || v === undefined ||
               (Array.isArray(v) && v.length === 0);
    if (noi === 'goc') {
      if (rong) delete m[ten]; else m[ten] = v;
      return;
    }
    if (!m[noi]) m[noi] = {};
    if (rong) delete m[noi][ten]; else m[noi][ten] = v;
    if (Object.keys(m[noi]).length === 0) delete m[noi];
  }

  /**
   * Co nen hien o nay khong, theo khai bao show_if trong MetricSchema.
   * show_if: { <ten_khoa_khac>: [cac gia tri cho phep] } — doc gia tri hien tai cua khoa do
   * trong CUNG vung (goc / filter / input). Co che tong quat, khong biet gi ve 'text'.
   */
  function hienO(m, noi, meta) {
    if (!meta.show_if) return true;
    for (var khoa in meta.show_if) {
      var hienTai = layGiaTri(m, noi, khoa);
      if (hienTai === undefined || hienTai === null || hienTai === '') {
        // chua chon thi lay mac dinh khai trong schema cua khoa do
        var d = (SCHEMA.manual && SCHEMA.manual.fields[khoa]) ? SCHEMA.manual.fields[khoa].default : undefined;
        hienTai = d;
      }
      if (meta.show_if[khoa].indexOf(hienTai) < 0) return false;
    }
    return true;
  }

  /** Co o nao khac dang phu thuoc vao khoa nay qua show_if khong. */
  function laKhoaDieuKhien(m, ten) {
    var def = SCHEMA[m.type];
    if (!def) return false;
    var nhom = [def.fields || {}, def.filter || {}];
    for (var n = 0; n < nhom.length; n++) {
      for (var k in nhom[n]) {
        var si = nhom[n][k].show_if;
        if (si && si[ten]) return true;
      }
    }
    return false;
  }

  /** Mot o nhap, dua tren khai bao widget trong MetricSchema. */
  function renderField(m, i, noi, ten, meta) {
    if (!hienO(m, noi, meta)) return '';
    var id = 'mb-' + i + '-' + noi + '-' + ten;
    var v = layGiaTri(m, noi, ten);
    var nhan = esc(meta.label || ten);
    var attr = 'id="' + id + '" data-i="' + i + '" data-noi="' + noi + '" data-ten="' + ten + '"';
    var h = '';

    if (meta.widget === 'catalog_multi') {
      var kieu = meta.value === 'string' ? 'string' : 'int';
      h = '<select class="form-control mb-cat" multiple ' + attr +
          ' data-catalog="' + meta.catalog + '" data-kieu="' + kieu + '"' +
          (meta.other_key ? ' data-other-key="' + meta.other_key + '"' : '') + '></select>';
      if (meta.other_key) {
        var laKhac = !!((m.filter || {})[meta.other_key]);
        h += '<div class="checkbox" style="margin:2px 0"><label>' +
             '<input type="checkbox" class="mb-other" data-i="' + i + '" data-ten="' + ten +
             '" data-other="' + meta.other_key + '"' + (laKhac ? ' checked' : '') + '> ' +
             '<small>Là nhóm <b>Khác</b> — lấy phần còn lại ngoài các loại đã chọn</small></label></div>';
      }
    } else if (meta.widget === 'bool') {
      h = '<div class="checkbox" style="margin-top:0"><label>' +
          '<input type="checkbox" class="mb-w" ' + attr + (v ? ' checked' : '') + '> ' + nhan +
          '</label></div>';
      return '<div class="col-md-3">' + h + '</div>';   // bool tu chua nhan
    } else if (meta.widget === 'select') {
      h = '<select class="form-control mb-w" ' + attr + '>';
      (meta.options || []).forEach(function (o) {
        h += '<option value="' + esc(o) + '"' + (v === o ? ' selected' : '') + '>' + esc(o) + '</option>';
      });
      h += '</select>';
    } else if (meta.widget === 'number' || meta.widget === 'int') {
      h = '<input type="number" class="form-control mb-w" ' + attr +
          ' value="' + (v === undefined || v === null ? '' : esc(v)) + '">';
    } else {
      h = '<input type="text" class="form-control mb-w" ' + attr +
          ' value="' + (v === undefined || v === null ? '' : esc(v)) +
          '" maxlength="' + (meta.max || 255) + '">';
    }

    var batBuoc = meta.required ? ' <span class="text-red">*</span>' : '';
    return '<div class="col-md-4" style="margin-bottom:8px">' +
             '<label style="font-weight:normal">' + nhan + batBuoc + '</label>' + h +
           '</div>';
  }

  function renderBody(m, i) {
    var def = SCHEMA[m.type] || { fields: {}, filter: {} };
    var noiFields = def.group === 'input' ? 'input' : 'goc';

    var h = '<div class="row">' +
      '<div class="col-md-4"><label style="font-weight:normal">Mã chỉ tiêu</label>' +
        '<input class="form-control mb-f" data-k="code" value="' + esc(m.code) + '"></div>' +
      '<div class="col-md-8"><label style="font-weight:normal">Tên hiển thị</label>' +
        '<input class="form-control mb-f" data-k="name" value="' + esc(m.name) + '"></div>' +
      '</div>';

    var oField = '';
    for (var ten in (def.fields || {})) {
      oField += renderField(m, i, noiFields, ten, def.fields[ten]);
    }
    if (oField) h += '<div class="row" style="margin-top:6px">' + oField + '</div>';

    if (def.scope === 'service_dept') h += renderPhamVi(m, i, def);

    var PHAM_VI_FIELDS = ['execute_room_ids', 'service_ids'];
    var oFilter = '';
    for (var f in (def.filter || {})) {
      if (def.scope === 'service_dept' && PHAM_VI_FIELDS.indexOf(f) >= 0) continue;
      oFilter += renderField(m, i, 'filter', f, def.filter[f]);
    }
    if (oFilter) {
      h += '<div style="margin-top:6px"><b class="text-muted">Điều kiện lọc</b></div>' +
           '<div class="row">' + oFilter + '</div>';
    }

    return h;
  }

  // Cac metric dang o pham vi "explicit" nhung chua chon phong/dich vu nao. Khi filter rong,
  // phamViHienTai khong the phan biet "explicit chua nhap gi" voi "request" (ca hai deu khong
  // co khoa nao) — neu khong nho tam trang thai nay thi bam radio "explicit" se bi bat nguoc
  // lai "request" ngay sau render() va 2 o chon phong/dich vu khong bao gio hien ra duoc.
  var mbExplicitPending = [];

  /** Suy ra pham vi hien tai tu filter da luu. */
  function phamViHienTai(m) {
    var f = m.filter || {};
    if (f.execute_department_id_self) return 'self';
    if (f.execute_room_ids || f.service_ids || f.execute_department_ids ||
        f.execute_department_id || f.request_department_ids || f.request_department_id) return 'explicit';
    if (mbExplicitPending.indexOf(m) >= 0) return 'explicit';
    return 'request';
  }

  /** Widget "Phạm vi khoa" cho chi tieu co scope service_dept — thay 6 khoa tho bang 3 lua chon. */
  function renderPhamVi(m, i, def) {
    var pv = phamViHienTai(m);
    var r = function (v, nhan) {
      return '<div class="radio" style="margin:2px 0"><label>' +
        '<input type="radio" name="mb-pv-' + i + '" class="mb-pv" data-i="' + i + '" value="' + v + '"' +
        (pv === v ? ' checked' : '') + '> ' + nhan + '</label></div>';
    };
    var h = '<div style="margin-top:6px"><b class="text-muted">Phạm vi khoa</b></div>' +
      r('self', 'Dịch vụ do <b>khoa này thực hiện</b>') +
      r('request', 'Dịch vụ do <b>khoa này chỉ định</b>') +
      r('explicit', 'Chỉ định <b>phòng / dịch vụ cụ thể</b>');

    if (pv === 'explicit') {
      h += '<div class="row">' +
        renderField(m, i, 'filter', 'execute_room_ids', def.filter.execute_room_ids) +
        renderField(m, i, 'filter', 'service_ids', def.filter.service_ids) +
        '</div>';
    }
    return h;
  }

  /** Gan handler ghi gia tri khi nguoi dung doi lua chon select2 (dung chung cho 2 nhanh). */
  function ganDoiGiaTri($s, i, noi, ten, kieu) {
    $s.on('change', function () {
      var v = ($s.val() || []).map(function (x) { return kieu === 'int' ? parseInt(x, 10) : String(x); });
      var otherKey = $s.data('other-key');
      var dangKhac = otherKey && $('#mb-list .mb-other[data-i="' + i + '"][data-ten="' + ten + '"]').is(':checked');
      datGiaTri(st.metrics[i], noi, dangKhac ? otherKey : ten, v);
      $('#mb-json').val(JSON.stringify(st.metrics, null, 2));
    });
  }

  /**
   * Gan select2 cho o danh muc.
   * Nhom nho: du lieu tai tron goi san trong CATALOGS.
   * Nhom lon (service/room/bed): tim qua AJAX, va tra nguoc ten cho gia tri da luu.
   */
  function ganSelect2($goc) {
    $goc.find('.mb-cat').each(function () {
      var $s = $(this);
      if ($s.data('select2')) return;
      var key = $s.data('catalog');
      var kieu = $s.data('kieu');
      var i = $s.data('i'), noi = $s.data('noi'), ten = $s.data('ten');
      var daChon = layGiaTri(st.metrics[i], noi, ten) || [];
      var otherKey = $s.data('other-key');
      if (!daChon.length && otherKey) daChon = (st.metrics[i].filter || {})[otherKey] || [];

      if (!laDanhMucLon(key)) {
        // nhom nho: du lieu co san
        var daChonStr = daChon.map(String);
        (CATALOGS[key] || []).forEach(function (o) {
          var chon = daChonStr.indexOf(String(o.id)) >= 0;
          $s.append('<option value="' + esc(o.id) + '"' + (chon ? ' selected' : '') + '>' + esc(o.name) + '</option>');
        });
        $s.select2({ width: '100%', placeholder: 'Chọn...', dropdownParent: $('#mb-modal') });
        ganDoiGiaTri($s, i, noi, ten, kieu);
        return;
      }

      // nhom lon: tim qua AJAX
      $s.select2({
        width: '100%',
        placeholder: 'Gõ ≥ 2 ký tự để tìm...',
        dropdownParent: $('#mb-modal'),
        minimumInputLength: 2,
        ajax: {
          url: ROUTES.catalog.replace('__KEY__', key),
          dataType: 'json',
          delay: 300,
          data: function (params) { return { q: params.term }; },
          processResults: function (rows) {
            return { results: (rows || []).map(function (o) { return { id: o.id, text: o.name }; }) };
          }
        }
      });
      ganDoiGiaTri($s, i, noi, ten, kieu);

      // tra nguoc ID -> ten cho gia tri da luu, neu khong select2 hien so tran
      if (daChon.length) {
        $.get(ROUTES.catalog.replace('__KEY__', key), { ids: daChon.join(',') }, function (rows) {
          (rows || []).forEach(function (o) {
            $s.append(new Option(o.name, o.id, true, true));
          });
          $s.trigger('change.select2');   // change.select2 KHONG kich handler ghi gia tri
        });
      }
    });
  }

  /** Nap mot bo chi tieu (tu mau hoac tu khoa khac) — hoi thay the hay noi them. */
  function napBoChiTieu(ds) {
    if (!ds.length) return;
    if (st.metrics.length &&
        !confirm('Thay thế toàn bộ ' + st.metrics.length + ' chỉ tiêu hiện có?\n\n' +
                 'OK = thay thế, Cancel = nối thêm vào cuối')) {
      ds.forEach(function (m) {
        var b = JSON.parse(JSON.stringify(m));   // deep-copy: tranh dung tham chieu voi du lieu goc
        b.code = maDuyNhat(b.code);               // noi them phai doi ma trung, khong thi validate chan luc luu
        st.metrics.push(b);
      });
    } else {
      st.metrics = JSON.parse(JSON.stringify(ds)); // deep-copy: khong de tham chieu chung voi mau/khoa nguon
    }
    mbExplicitPending = [];   // st.metrics la mang object hoan toan moi, cac tham chieu cu thanh rac
    render();
  }

  function themChiTieu(type) {
    var def = SCHEMA[type];
    var ten = def.label;
    st.metrics.push({ code: maDuyNhat(slug(ten)), name: ten, type: type });
    render();
    $('#mb-list .mb-card').last().find('.mb-body').show();
  }

  function luu() {
    if ($('#mb-tab-json').hasClass('active')) {
      var a = docJson();
      if (a === null) return;
      st.metrics = a;
    }

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

  /** To do dung card sai + hien thong bao (Task 15 mo rong them cho tab JSON).
   *  Chiu duoc ca hai dang loi tra ve tu server:
   *  - loi schema tu MetricValidator (vd luu(), tinh-thu voi metrics sai): errors la mang [{index, field, message}]
   *  - loi validate chuan cua Laravel (vd tinh-thu voi from/to sai dinh dang): errors la object {ten_truong: [thong bao]}
   *  Khong bao gio duoc goi .forEach tren thu khong phai mang — neu khong, exception nay se chan
   *  luon .always() cua nguoi goi (xem tinhThu()), kien nut #mb-preview ket disabled vinh vien.
   */
  function hienLoi(xhr) {
    var res = xhr.responseJSON || {};
    var loi = res.errors;

    // Dang object cua Laravel -> gop thanh mot dong thong bao, khong to card nao (khong co index).
    if (loi && !$.isArray(loi)) {
      var dsach = [];
      $.each(loi, function (truong, msgs) {
        dsach.push(truong + ': ' + ($.isArray(msgs) ? msgs.join(' ') : msgs));
      });
      $('#mb-save-msg').text(dsach.length ? dsach.join(' | ') : (res.message || 'Dữ liệu gửi lên không hợp lệ.'));
      return;
    }

    $('#mb-save-msg').text(res.message || 'Lỗi lưu chỉ tiêu');
    (loi || []).forEach(function (e) {
      if (e.index < 0) return;
      var $card = $('#mb-list .mb-card').eq(e.index);
      $card.removeClass('panel-default').addClass('panel-danger');
      $card.find('.mb-body').show();
      $card.find('.mb-warn').html(' <span class="text-red">' + esc(e.field + ': ' + e.message) + '</span>');
    });
  }

  /** JSON hong -> vien do + khoa nut Luu. Tra ve mang metrics hoac null. */
  function docJson() {
    var raw = $('#mb-json').val();
    try {
      var a = JSON.parse(raw);
      if (!Array.isArray(a)) throw new Error('Phải là một mảng chỉ tiêu.');
      $('#mb-json').css('border-color', '');
      $('#mb-json-msg').text('').removeClass('text-red');
      $('#mb-save').prop('disabled', false);
      return a;
    } catch (e) {
      $('#mb-json').css('border-color', '#dd4b39');
      $('#mb-json-msg').text('JSON không hợp lệ: ' + e.message).addClass('text-red');
      $('#mb-save').prop('disabled', true);
      return null;
    }
  }

  /** So < 10 thi them so 0 phia truoc — dung khi ghep chuoi ngay/gio. */
  function haiChuSo(n) { return (n < 10 ? '0' : '') + n; }

  /** Moc thoi gian mac dinh cho tinh thu: 7h hom qua -> 7h hom nay (khop ca dinh giao ban). */
  function mocThoiGianMacDinh() {
    var nay = new Date();
    var hn = nay.getFullYear() + '-' + haiChuSo(nay.getMonth() + 1) + '-' + haiChuSo(nay.getDate());
    var hq = new Date(nay.getTime() - 86400000);
    var hqs = hq.getFullYear() + '-' + haiChuSo(hq.getMonth() + 1) + '-' + haiChuSo(hq.getDate());
    return { from: hqs + ' 07:00:00', to: hn + ' 07:00:00' };
  }

  /**
   * Tinh thu bo chi tieu dang soan (chua luu) tren HIS that. Khong ghi gi vao DB.
   * Khoa nut trong luc chay de tranh ban lien tuc chong request len HIS (vd service_count
   * voi diim_type_other_of phai quet his_sere_serv, khong nhe).
   */
  function tinhThu() {
    var moc = mocThoiGianMacDinh();
    var from = prompt('Tính thử từ (YYYY-MM-DD HH:MM:SS):', moc.from);
    if (!from) return;
    var to = prompt('đến (YYYY-MM-DD HH:MM:SS):', moc.to);
    if (!to) return;

    var $b = $('#mb-preview-box').show()
      .html('<div class="text-muted"><i class="fa fa-spinner fa-spin"></i> Đang tính…</div>');
    $('#mb-preview').prop('disabled', true);

    $.post(ROUTES.preview.replace('__ID__', st.cfg.id), {
      _token: CSRF,
      metrics: JSON.stringify(st.metrics),
      block_type: st.cfg.block_type,
      his_department_ids: st.cfg.his_department_ids || '[]',
      from: from, to: to
    }).done(function (res) {
      var h = '<table class="table table-condensed table-bordered" style="margin-bottom:4px">' +
              '<thead><tr><th>Chỉ tiêu</th><th style="width:100px">Giá trị</th><th>Ghi chú</th></tr></thead><tbody>';
      (res.rows || []).forEach(function (r) {
        var cb = CANH_BAO[r.warning];
        h += '<tr' + (cb && cb[0] === 'danger' ? ' class="danger"' : '') + '>' +
             '<td>' + esc(r.name) + ' <code>' + esc(r.code) + '</code></td>' +
             '<td class="text-right">' + (r.value === null ? '—' : esc(r.value)) + '</td>' +
             '<td><small>' + (cb ? esc(cb[1]) : '') + '</small></td></tr>';
      });
      h += '</tbody></table><small class="text-muted">Tính trong ' + res.ms + ' ms. ' +
           'Đây là số tính thử, chưa ghi vào báo cáo.</small>';
      $b.html(h);
    }).fail(function (xhr) {
      var r = xhr.responseJSON || {};
      $b.html('<div class="text-red">' + esc(r.message || 'Không tính thử được.') + '</div>');
      // Bọc try/catch: loi bat ngo trong hienLoi() khong duoc chan mat .always() phia duoi,
      // neu khong nut #mb-preview se ket disabled vinh vien.
      try { hienLoi(xhr); } catch (e) { /* khong de loi hien thi lam ket nut Tinh thu */ }
    }).always(function () {
      $('#mb-preview').prop('disabled', false);
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
      // Dong bo lai textarea JSON: tab JSON la nguon su that khi roi tab hoac khi luu(),
      // khong ghi lai o day se lam mat sua doi ma/ten vua go tren tab Form.
      $('#mb-json').val(JSON.stringify(st.metrics, null, 2));
    });
    $(document).on('click', '#mb-save', luu);
    $(document).on('click', '#mb-preview', tinhThu);

    // Nap mau tu DB vao bo chi tieu dang sua
    $(document).on('click', '.mb-tpl', function (e) {
      e.preventDefault();
      var id = $(this).data('id'), t = null;
      st.templates.forEach(function (x) { if (x.id === id) t = x; });
      if (!t) return;
      try { napBoChiTieu(JSON.parse(t.metrics || '[]')); } catch (err) { alert('Mẫu hỏng JSON.'); }
    });

    // Nhan ban bo chi tieu tu mot khoa khac cung khoi
    $(document).on('click', '.mb-clone', function (e) {
      e.preventDefault();
      var id = $(this).data('id'), c = null;
      st.configs.forEach(function (x) { if (x.id === id) c = x; });
      if (!c) return;
      try { napBoChiTieu(JSON.parse(c.metrics || '[]')); } catch (err) { alert('Cấu hình nguồn hỏng JSON.'); }
    });

    // Luu bo chi tieu hien tai thanh mau moi trong DB
    $(document).on('click', '#mb-tpl-save', function (e) {
      e.preventDefault();
      var ten = prompt('Tên mẫu:', st.cfg.display_name);
      if (!ten) return;
      $.post(ROUTES.templateStore, {
        _token: CSRF, name: ten, block_type: st.cfg.block_type,
        metrics: JSON.stringify(st.metrics), sort_order: st.templates.length + 1
      }).done(function () {
        alert('Đã lưu mẫu.');
      }).fail(function (xhr) {
        var r = xhr.responseJSON || {};
        alert(r.message || 'Không lưu được mẫu.');
      });
    });

    $(document).on('input', '#mb-json', function () { docJson(); });

    // roi tab JSON -> dung lai danh sach card tu JSON vua go
    $(document).on('shown.bs.tab', 'a[href="#mb-tab-form"]', function () {
      var a = docJson();
      if (a === null) return;          // JSON hong: giu nguyen card cu, khong nuot im lang
      mbExplicitPending = [];          // st.metrics se la mang object hoan toan moi, cac tham chieu cu thanh rac
      st.metrics = a;
      render();
    });

    // mo modal lai thi go khoa nut Luu
    $('#mb-modal').on('show.bs.modal', function () { $('#mb-save').prop('disabled', false); });

    // Doi radio pham vi khoa: don sach cac khoa cu de khong sinh JSON mau thuan.
    $(document).on('change', '#mb-list .mb-pv', function () {
      var i = $(this).data('i');
      var m = st.metrics[i];
      var f = m.filter || {};
      ['execute_department_id_self', 'execute_room_ids', 'service_ids',
       'execute_department_ids', 'execute_department_id',
       'request_department_ids', 'request_department_id'].forEach(function (k) { delete f[k]; });

      var val = $(this).val();
      if (val === 'self') f.execute_department_id_self = true;
      // 'request' = khong khai gi, computeAll tu gan theo khoa cua config
      var viTriCho = mbExplicitPending.indexOf(m);
      if (val === 'explicit') {
        if (viTriCho < 0) mbExplicitPending.push(m);
      } else if (viTriCho >= 0) {
        mbExplicitPending.splice(viTriCho, 1);
      }
      m.filter = Object.keys(f).length ? f : undefined;
      if (!m.filter) delete m.filter;
      render();
      $('#mb-list .mb-card').eq(i).find('.mb-body').show();
    });

    // Bat/tat nhom "Khac": chuyen gia tri dang chon giua khoa *_ids va *_other_of.
    $(document).on('change', '#mb-list .mb-other', function () {
      var i = $(this).data('i');
      var ten = $(this).data('ten'), other = $(this).data('other');
      var m = st.metrics[i];
      var f = m.filter || {};
      if ($(this).is(':checked')) {
        if (f[ten]) { f[other] = f[ten]; delete f[ten]; }
      } else {
        if (f[other]) { f[ten] = f[other]; delete f[other]; }
      }
      m.filter = Object.keys(f).length ? f : undefined;
      if (!m.filter) delete m.filter;
      render();
      $('#mb-list .mb-card').eq(i).find('.mb-body').show();
    });

    // Ghi gia tri cho moi widget dong (text/number/int/select/bool) sinh boi renderField.
    $(document).on('change input', '#mb-list .mb-w', function () {
      var $e = $(this);
      var m = st.metrics[$e.data('i')];
      var v;
      if ($e.attr('type') === 'checkbox') {
        v = $e.is(':checked') ? true : '';          // '' -> datGiaTri xoa khoa
      } else if ($e.attr('type') === 'number') {
        v = $e.val() === '' ? '' : Number($e.val());
      } else {
        v = $e.val();
      }
      var noi = $e.data('noi'), ten = $e.data('ten'), i = $e.data('i');
      datGiaTri(m, noi, ten, v);
      $('#mb-json').val(JSON.stringify(st.metrics, null, 2));

      // Neu o vua doi duoc show_if cua o khac tro toi thi phai ve lai card,
      // khong thi cac o phu thuoc van hien/an theo gia tri cu.
      if (laKhoaDieuKhien(m, ten)) {
        render();
        $('#mb-list .mb-card').eq(i).find('.mb-body').show();
      }
    });

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
      // Bu tru vi tri: sau khi rut phan tu o dragSrcIndex, moi chi so tu do ve sau
      // deu lui 1 o. Khong bu se lam phan tu roi lech huong so voi cho nguoi dung tha.
      if (dragSrcIndex < dichI) dichI -= 1;
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
