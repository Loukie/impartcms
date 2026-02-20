/* ImpartCMS Forms Builder (admin)
 * No dependencies, no template literals (avoids Vite import-analysis parse edge cases).
 * Supports:
 * - Drag from palette into columns (clone)
 * - Drag existing fields to reorder/move
 * - Rows (1-4 cols), Sections, Page breaks (add/remove)
 * - Field inspector: label, name, placeholder, required
 * - Options editor for select/cards/cards_multi:
 *   - label/value
 *   - pick image via ImpartMediaPicker (type=images)
 *   - pick icon via ImpartMediaPicker (type=icons)
 *   - copy shortcode/value
 * - Pricing editor (Phase 1): pricing options list + per-option price mapping (stores config)
 */

(function () {
  function ready(fn) {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }

  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }

  function uid() {
    return 'f_' + Math.random().toString(36).slice(2, 9) + Math.random().toString(36).slice(2, 6);
  }

  function escapeHtml(s) {
    var str = String(s == null ? '' : s);
    return str.replace(/[&<>"']/g, function (ch) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
    });
  }

  function slugifyKey(input) {
    return String(input || '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9\s_]+/g, '')
      .replace(/\s+/g, '_')
      .replace(/_+/g, '_')
      .replace(/^_+|_+$/g, '')
      .slice(0, 64);
  }

  function parseJsonSafe(raw, fallback) {
    try { return JSON.parse(raw); } catch (e) { return fallback; }
  }

  function el(tag, attrs) {
    var node = document.createElement(tag);
    if (attrs) {
      Object.keys(attrs).forEach(function (k) {
        if (k === 'class') node.className = attrs[k];
        else if (k === 'text') node.textContent = attrs[k];
        else if (k === 'html') node.innerHTML = attrs[k];
        else if (k.startsWith('data-')) node.setAttribute(k, attrs[k]);
        else if (k === 'type') node.type = attrs[k];
        else if (k === 'value') node.value = attrs[k];
        else if (k === 'checked') node.checked = !!attrs[k];
        else node.setAttribute(k, attrs[k]);
      });
    }
    return node;
  }

  function deepClone(obj) { return JSON.parse(JSON.stringify(obj)); }

  function normaliseInitial(initial) {
    var fields = initial && initial.fields ? initial.fields : [];
    var settings = initial && initial.settings ? initial.settings : {};

    // Normalise fields into map by id
    var fieldMap = {};
    var order = [];

    if (Array.isArray(fields)) {
      fields.forEach(function (f) {
        var id = f && f.id ? String(f.id) : uid();
        var nf = Object.assign({}, f, { id: id });
        fieldMap[id] = nf;
        order.push(id);
      });
    } else if (fields && typeof fields === 'object') {
      Object.keys(fields).forEach(function (id) {
        fieldMap[id] = Object.assign({}, fields[id], { id: id });
        order.push(id);
      });
    }

    settings = settings && typeof settings === 'object' ? settings : {};
    settings.layout = Array.isArray(settings.layout) ? settings.layout : null;

    // If no layout, build a default single-row layout containing all fields
    if (!settings.layout) {
      settings.layout = [{
        id: 'row_' + uid(),
        type: 'row',
        columns: 1,
        cols: [{ id: 'col_' + uid(), fields: order.slice() }]
      }];
    } else {
      // Ensure blocks have ids + shape
      settings.layout = settings.layout.map(function (b) {
        var nb = Object.assign({}, b);
        nb.id = nb.id || (nb.type || 'blk') + '_' + uid();
        if (nb.type === 'row' || nb.type === 'section') {
          nb.columns = Math.max(1, Math.min(4, parseInt(nb.columns || (nb.cols ? nb.cols.length : 1), 10) || 1));
          nb.cols = Array.isArray(nb.cols) ? nb.cols : [];
          while (nb.cols.length < nb.columns) nb.cols.push({ id: 'col_' + uid(), fields: [] });
          nb.cols = nb.cols.slice(0, nb.columns);
          nb.cols.forEach(function (c) {
            c.id = c.id || ('col_' + uid());
            c.fields = Array.isArray(c.fields) ? c.fields : [];
          });
        }
        return nb;
      });
    }

    // Ensure each referenced field exists
    settings.layout.forEach(function (b) {
      if (b.type === 'row' || b.type === 'section') {
        b.cols.forEach(function (c) {
          c.fields = (c.fields || []).filter(function (fid) { return !!fieldMap[fid]; });
        });
      }
    });

    // Pricing base
    settings.pricing = settings.pricing && typeof settings.pricing === 'object' ? settings.pricing : {};
    settings.pricing.enabled = !!settings.pricing.enabled;
    settings.pricing.options = Array.isArray(settings.pricing.options) ? settings.pricing.options : [];
    settings.pricing.default = settings.pricing.default || '';

    return { fields: fieldMap, settings: settings };
  }

  function locateField(state, fieldId) {
    var loc = null;
    state.settings.layout.forEach(function (b, bi) {
      if (b.type !== 'row' && b.type !== 'section') return;
      b.cols.forEach(function (c, ci) {
        var idx = c.fields.indexOf(fieldId);
        if (idx !== -1) loc = { blockIndex: bi, colIndex: ci, index: idx };
      });
    });
    return loc;
  }

  function removeFieldFromLayout(state, fieldId) {
    state.settings.layout.forEach(function (b) {
      if (b.type !== 'row' && b.type !== 'section') return;
      b.cols.forEach(function (c) {
        c.fields = c.fields.filter(function (id) { return id !== fieldId; });
      });
    });
  }

  function persist(root, state) {
    var fieldsInput = qs('input[name="fields_json"]', root);
    var settingsInput = qs('input[name="settings_json"]', root);
    if (fieldsInput) fieldsInput.value = JSON.stringify(state.fields);
    if (settingsInput) settingsInput.value = JSON.stringify(state.settings);
  }

  function setDragData(e, kind, payload) {
    try {
      e.dataTransfer.setData('text/plain', kind + ':' + payload);
      e.dataTransfer.setData('application/x-impart-fb', kind + ':' + payload);
      e.dataTransfer.effectAllowed = 'copyMove';
    } catch (_) { /* ignore */ }
  }

  function getDragData(e) {
    var s = '';
    try { s = e.dataTransfer.getData('application/x-impart-fb') || e.dataTransfer.getData('text/plain') || ''; } catch (_) {}
    if (!s) return null;
    var parts = s.split(':');
    if (parts.length < 2) return null;
    return { kind: parts[0], value: parts.slice(1).join(':') };
  }

  function defaultField(type) {
    var id = uid();
    var base = { id: id, type: type, label: '', name: '', required: false, placeholder: '' };
    if (type === 'text') { base.label = 'Text'; base.name = 'text_' + id.slice(-4); }
    else if (type === 'email') { base.label = 'Email'; base.name = 'email_' + id.slice(-4); }
    else if (type === 'phone') { base.label = 'Phone'; base.name = 'phone_' + id.slice(-4); }
    else if (type === 'textarea') { base.label = 'Message'; base.name = 'message_' + id.slice(-4); base.placeholder = 'Type here...'; }
    else if (type === 'select') { base.label = 'Select'; base.name = 'select_' + id.slice(-4); base.options = [{ label: 'Option 1', value: 'option_1' }]; }
    else if (type === 'cards') { base.label = 'Choose one'; base.name = 'cards_' + id.slice(-4); base.options = [{ label: 'Option 1', value: 'option_1' }]; }
    else if (type === 'cards_multi') { base.label = 'Choose options'; base.name = 'cards_multi_' + id.slice(-4); base.options = [{ label: 'Option 1', value: 'option_1' }]; }
    else if (type === 'page_break') { base.label = 'Page break'; base.name = 'page_break_' + id.slice(-4); }
    return base;
  }

  function buildFieldCard(state, fieldId) {
    var field = state.fields[fieldId];
    var card = el('div', { class: 'rounded-lg border border-slate-200 bg-white p-3 mb-2 cursor-pointer hover:border-slate-300', 'data-fb-field': '1', 'data-field-id': fieldId });
    card.draggable = true;

    var top = el('div', { class: 'flex items-start justify-between gap-3' });
    var left = el('div', { class: 'min-w-0' });
    var lbl = el('div', { class: 'text-sm font-semibold text-slate-900 truncate', 'data-fb-label': '1', text: field.label || field.name || 'Field' });
    var metaTxt = (field.type || '') + (field.required ? ' * required' : '');
    var meta = el('div', { class: 'text-xs text-slate-500', 'data-fb-meta': '1', text: metaTxt });
    left.appendChild(lbl); left.appendChild(meta);
    var grip = el('div', { class: 'text-xs text-slate-400', text: '::' });
    top.appendChild(left); top.appendChild(grip);
    card.appendChild(top);

    card.addEventListener('dragstart', function (e) {
      setDragData(e, 'field', fieldId);
      card.classList.add('opacity-60');
    });
    card.addEventListener('dragend', function () { card.classList.remove('opacity-60'); });

    return card;
  }

  function highlightSelection(root, state) {
    qsa('[data-fb-field]', root).forEach(function (c) {
      var id = c.getAttribute('data-field-id');
      if (id && id === state.selectedFieldId) c.classList.add('ring-2', 'ring-slate-900');
      else c.classList.remove('ring-2', 'ring-slate-900');
    });
  }

  function renderCanvas(root, state) {
    var canvas = qs('[data-fb-canvas]', root);
    if (!canvas) return;
    canvas.innerHTML = '';

    state.settings.layout.forEach(function (blk) {
      if (blk.type === 'page_break') {
        var pb = el('div', { class: 'rounded-xl border border-slate-200 bg-white p-3 mb-3' });
        var row = el('div', { class: 'flex items-center justify-between' });
        row.appendChild(el('div', { class: 'text-sm font-semibold text-slate-900', text: 'Page break' }));
        var btn = el('button', { type: 'button', class: 'px-3 py-1.5 rounded-md border bg-white hover:bg-gray-50 text-xs font-semibold text-red-700', text: 'Remove' });
        btn.addEventListener('click', function () {
          state.settings.layout = state.settings.layout.filter(function (b) { return b.id !== blk.id; });
          renderCanvas(root, state);
          persist(root, state);
        });
        row.appendChild(btn);
        pb.appendChild(row);
        canvas.appendChild(pb);
        return;
      }

      if (blk.type !== 'row' && blk.type !== 'section') return;

      var wrap = el('div', { class: 'rounded-xl border border-slate-200 bg-white p-3 mb-3' });
      var header = el('div', { class: 'flex items-center justify-between gap-3 mb-3' });

      var hLeft = el('div', { class: 'min-w-0' });
      var title = (blk.type === 'section') ? (blk.title || 'Section') : 'Row';
      var titleEl = el('div', { class: 'text-sm font-semibold text-slate-900', text: title });
      hLeft.appendChild(titleEl);

      if (blk.type === 'section') {
        var titleInput = el('input', { type: 'text', class: 'mt-1 w-full rounded-md border-gray-300 text-sm', value: blk.title || '' });
        titleInput.placeholder = 'Section title (optional)';
        titleInput.addEventListener('input', function () {
          blk.title = titleInput.value;
          titleEl.textContent = blk.title || 'Section';
          persist(root, state);
        });
        hLeft.appendChild(titleInput);
      }

      header.appendChild(hLeft);

      var hRight = el('div', { class: 'flex items-center gap-2 flex-wrap' });

      var colSel = el('select', { class: 'rounded-md border-gray-300 text-sm' });
      [1,2,3,4].forEach(function(n){
        var opt = el('option', { value: String(n), text: String(n) + ' col' + (n>1?'s':'') });
        if (n === (blk.columns || 1)) opt.selected = true;
        colSel.appendChild(opt);
      });
      colSel.addEventListener('change', function(){
        var n = Math.max(1, Math.min(4, parseInt(colSel.value,10)||1));
        var prevCols = blk.cols || [];
        var flattened = [];
        prevCols.forEach(function(c){ (c.fields||[]).forEach(function(fid){ flattened.push(fid); }); });
        blk.columns = n;
        blk.cols = [];
        for (var i=0;i<n;i++) blk.cols.push({ id: 'col_' + uid(), fields: [] });
        // repack existing fields into first col (stable, predictable)
        blk.cols[0].fields = flattened;
        renderCanvas(root, state);
        persist(root, state);
      });

      var del = el('button', { type: 'button', class: 'px-3 py-1.5 rounded-md border bg-white hover:bg-gray-50 text-xs font-semibold text-red-700', text: 'Remove' });
      del.addEventListener('click', function(){
        // remove block and its fields stay in state (not deleted)
        state.settings.layout = state.settings.layout.filter(function(b){ return b.id !== blk.id; });
        renderCanvas(root, state);
        persist(root, state);
      });

      hRight.appendChild(colSel);
      hRight.appendChild(del);
      header.appendChild(hRight);
      wrap.appendChild(header);

      var grid = el('div', { class: 'grid gap-3' });
      grid.style.gridTemplateColumns = 'repeat(' + (blk.columns||1) + ', minmax(0, 1fr))';

      blk.cols.forEach(function(col, colIndex){
        var dz = el('div', { class: 'rounded-lg border-2 border-dashed border-slate-200 bg-slate-50 p-3 min-h-[80px]', 'data-fb-dropzone': '1' });
        dz.setAttribute('data-block-id', blk.id);
        dz.setAttribute('data-col-index', String(colIndex));

        // Existing field cards
        (col.fields || []).forEach(function(fid){
          var card = buildFieldCard(state, fid);
          dz.appendChild(card);
        });

        dz.addEventListener('click', function(e){
          // selecting empty space clears selection
          if (e.target === dz) {
            state.selectedFieldId = null;
            highlightSelection(root, state);
            renderInspector(root, state);
          }
        });

        dz.addEventListener('dragover', function(e){
          e.preventDefault();
          dz.classList.add('border-slate-400');
        });
        dz.addEventListener('dragleave', function(){ dz.classList.remove('border-slate-400'); });
        dz.addEventListener('drop', function(e){
          e.preventDefault();
          dz.classList.remove('border-slate-400');

          var d = getDragData(e);
          if (!d) return;

          if (d.kind === 'palette') {
            var type = d.value;
            var f = defaultField(type);
            state.fields[f.id] = f;
            // ensure layout arrays exist
            blk.cols[colIndex].fields.push(f.id);
            state.selectedFieldId = f.id;
            renderCanvas(root, state);
            persist(root, state);
            renderInspector(root, state);
            highlightSelection(root, state);
            return;
          }

          if (d.kind === 'field') {
            var movingId = d.value;
            if (!state.fields[movingId]) return;
            // move field to this column (append)
            removeFieldFromLayout(state, movingId);
            blk.cols[colIndex].fields.push(movingId);
            state.selectedFieldId = movingId;
            renderCanvas(root, state);
            persist(root, state);
            renderInspector(root, state);
            highlightSelection(root, state);
          }
        });

        // allow drop on cards for reorder (insert before)
        dz.addEventListener('drop', function(){ /* handled above */ });

        grid.appendChild(dz);
      });

      wrap.appendChild(grid);
      canvas.appendChild(wrap);
    });

    // Bind card click handlers after render
    qsa('[data-fb-field]', root).forEach(function(card){
      var fid = card.getAttribute('data-field-id');
      card.addEventListener('click', function(e){
        e.stopPropagation();
        state.selectedFieldId = fid;
        highlightSelection(root, state);
        renderInspector(root, state);
      });
    });
  }

  function bindPalette(root) {
    qsa('[data-fb-palette-item]', root).forEach(function(item){
      if (item.dataset.bound === '1') return;
      item.draggable = true;
      item.addEventListener('dragstart', function(e){
        var type = item.getAttribute('data-type') || '';
        setDragData(e, 'palette', type);
        try { e.dataTransfer.setDragImage(item, 10, 10); } catch(_) {}
      });
      item.dataset.bound = '1';
    });
  }

  function renderInspector(root, state) {
    var inspector = qs('[data-fb-inspector]', root);
    if (!inspector) return;

    var empty = qs('[data-fb-inspector-empty]', inspector);
    var form = qs('[data-fb-inspector-form]', inspector);

    if (!state.selectedFieldId || !state.fields[state.selectedFieldId]) {
      if (empty) empty.classList.remove('hidden');
      if (form) form.classList.add('hidden');
      // still render pricing section bindings
      bindPricing(root, state);
      return;
    }

    if (empty) empty.classList.add('hidden');
    if (form) form.classList.remove('hidden');

    var field = state.fields[state.selectedFieldId];

    var t = qs('[data-fb-i-type]', inspector);
    if (t) t.textContent = field.type || '';

    var iLabel = qs('[data-fb-i-label]', inspector);
    var iName = qs('[data-fb-i-name]', inspector);
    var iPlaceholderWrap = qs('[data-fb-i-placeholder-wrap]', inspector);
    var iPlaceholder = qs('[data-fb-i-placeholder]', inspector);
    var iRequired = qs('[data-fb-i-required]', inspector);
    var iDelete = qs('[data-fb-i-delete]', inspector);

    // fill without rebinding churn
    if (iLabel) iLabel.value = field.label || '';
    if (iName) iName.value = field.name || '';
    if (iRequired) iRequired.checked = !!field.required;

    var hasPlaceholder = (field.type === 'text' || field.type === 'email' || field.type === 'phone' || field.type === 'textarea');
    if (iPlaceholderWrap) iPlaceholderWrap.style.display = hasPlaceholder ? '' : 'none';
    if (iPlaceholder && hasPlaceholder) iPlaceholder.value = field.placeholder || '';

    // options wrap
    var optWrap = qs('[data-fb-i-options-wrap]', inspector);
    var optList = qs('[data-fb-i-options]', inspector);
    var optAdd = qs('[data-fb-i-options-add]', inspector);

    var isOptions = (field.type === 'select' || field.type === 'cards' || field.type === 'cards_multi');
    if (optWrap) optWrap.classList[isOptions ? 'remove' : 'add']('hidden');

    function updateCardLabelMeta() {
      qsa('[data-fb-field]', root).forEach(function(c){
        if (c.getAttribute('data-field-id') !== field.id) return;
        var lbl = qs('[data-fb-label]', c);
        var meta = qs('[data-fb-meta]', c);
        if (lbl) lbl.textContent = field.label || field.name || 'Field';
        if (meta) meta.textContent = (field.type || '') + (field.required ? ' * required' : '');
      });
    }

    // bind once: use dataset.bound keys tied to current selected field id
    function bindInputOnce(node, key, handler) {
      if (!node) return;
      var marker = key + ':' + field.id;
      if (node.dataset.boundKey === marker) return;
      node.oninput = handler;
      node.onchange = handler;
      node.dataset.boundKey = marker;
    }

    bindInputOnce(iLabel, 'label', function(){
      field.label = iLabel.value;
      if (!field.name) {
        field.name = slugifyKey(field.label);
        if (iName) iName.value = field.name;
      }
      updateCardLabelMeta();
      persist(root, state);
    });

    bindInputOnce(iName, 'name', function(){
      field.name = slugifyKey(iName.value);
      iName.value = field.name;
      updateCardLabelMeta();
      persist(root, state);
    });

    bindInputOnce(iPlaceholder, 'placeholder', function(){
      field.placeholder = iPlaceholder.value;
      persist(root, state);
    });

    if (iRequired) {
      var markerReq = 'required:' + field.id;
      if (iRequired.dataset.boundKey !== markerReq) {
        iRequired.addEventListener('change', function(){
          field.required = !!iRequired.checked;
          updateCardLabelMeta();
          persist(root, state);
        });
        iRequired.dataset.boundKey = markerReq;
      }
    }

    if (iDelete) {
      var markerDel = 'delete:' + field.id;
      if (iDelete.dataset.boundKey !== markerDel) {
        iDelete.addEventListener('click', function(){
          // remove from layout and fields map
          removeFieldFromLayout(state, field.id);
          delete state.fields[field.id];
          state.selectedFieldId = null;
          renderCanvas(root, state);
          persist(root, state);
          renderInspector(root, state);
        });
        iDelete.dataset.boundKey = markerDel;
      }
    }

    // options editor
    function renderOptions() {
      if (!isOptions || !optList) return;
      optList.innerHTML = '';
      field.options = Array.isArray(field.options) ? field.options : [];
      field.options.forEach(function(opt, idx){
        var row = el('div', { class: 'rounded-lg border border-slate-200 bg-white p-3' });

        var top = el('div', { class: 'grid grid-cols-1 gap-2' });

        var lab = el('input', { type: 'text', class: 'w-full rounded-md border-gray-300 text-sm', value: opt.label || '' });
        lab.placeholder = 'Label';
        lab.addEventListener('input', function(){
          opt.label = lab.value;
          if (!opt.value) {
            opt.value = slugifyKey(opt.label);
            val.value = opt.value;
          }
          persist(root, state);
          renderPricingOptionSelects(root, state); // keep price selectors updated
        });

        var val = el('input', { type: 'text', class: 'w-full rounded-md border-gray-300 text-sm', value: opt.value || '' });
        val.placeholder = 'Value';
        val.addEventListener('input', function(){
          opt.value = slugifyKey(val.value);
          val.value = opt.value;
          persist(root, state);
        });

        top.appendChild(lab);
        top.appendChild(val);

        // media/icon preview + actions
        var actions = el('div', { class: 'flex items-center gap-2 flex-wrap mt-2' });

        var prev = el('div', { class: 'w-10 h-10 rounded border bg-slate-50 flex items-center justify-center overflow-hidden' });
        var prevInner = el('div', { class: 'text-xs text-slate-400', text: '-' });
        prev.appendChild(prevInner);

        function applyPreview() {
          prev.innerHTML = '';
          if (opt.media_url) {
            var img = el('img', { class: 'w-full h-full object-contain' });
            img.src = opt.media_url;
            prev.appendChild(img);
            return;
          }
          if (opt.icon && opt.icon.value) {
            var kind = String(opt.icon.kind || '').toLowerCase();
            var sizePx = parseInt(opt.icon.size || 24, 10) || 24;
            var colour = opt.icon.colour || '#111827';

            // Prefer inline SVG for Font Awesome (renders even if FA fonts/CSS are missing)
            if (kind === 'fa' && opt.icon.svg && String(opt.icon.svg).trim().indexOf('<svg') === 0) {
              prev.innerHTML = opt.icon.svg;
              var s = prev.querySelector('svg');
              if (s) {
                try { s.removeAttribute('width'); s.removeAttribute('height'); } catch (_) {}
                s.setAttribute('aria-hidden', 'true');
                s.setAttribute('focusable', 'false');
                s.style.width = sizePx + 'px';
                s.style.height = sizePx + 'px';
                s.style.display = 'block';
                s.style.color = colour;
                return;
              }
            }

            if (kind === 'lucide') {
              var li = el('i');
              li.setAttribute('data-lucide', opt.icon.value);
              li.style.width = sizePx + 'px';
              li.style.height = sizePx + 'px';
              li.style.color = colour;
              li.style.display = 'inline-block';
              prev.appendChild(li);
              if (window.ImpartLucide && typeof window.ImpartLucide.render === 'function') {
                window.ImpartLucide.render(prev);
              }
              return;
            }

            // Fallback: classic FA class-based render
            var i = el('i');
            i.className = opt.icon.value;
            i.style.fontSize = String(sizePx) + 'px';
            i.style.color = colour;
            i.style.lineHeight = '1';
            prev.appendChild(i);
            return;
          }
          prev.appendChild(el('div', { class: 'text-xs text-slate-400', text: '-' }));
        }

        applyPreview();

        var btnImg = el('button', { type: 'button', class: 'px-3 py-1.5 rounded-md border bg-white hover:bg-gray-50 text-xs font-semibold', text: 'Image' });
        btnImg.addEventListener('click', function(){
          if (!window.ImpartMediaPicker || typeof window.ImpartMediaPicker.open !== 'function') return;
          window.ImpartMediaPicker.open({
            url: '/admin/media/picker?type=images&tab=library&allow=images',
            onSelect: function(payload){
              if (!payload || !payload.url) return;
              opt.media_id = payload.id || null;
              opt.media_url = payload.url;
              opt.icon = null;
              applyPreview();
              persist(root, state);
            }
          });
        });

        var btnIcon = el('button', { type: 'button', class: 'px-3 py-1.5 rounded-md border bg-white hover:bg-gray-50 text-xs font-semibold', text: 'Icon' });
        btnIcon.addEventListener('click', function(){
          if (!window.ImpartMediaPicker || typeof window.ImpartMediaPicker.open !== 'function') return;
          window.ImpartMediaPicker.open({
            url: '/admin/media/picker?type=icons&tab=library&allow=icons',
            onSelect: function(payload){
              if (!payload) return;
              // icon library sends {kind:'icon', icon:{...}}
              if (payload.kind === 'icon' && payload.icon) {
                opt.icon = payload.icon;
                opt.media_id = null;
                opt.media_url = null;
                applyPreview();
                persist(root, state);
              }
            }
          });
        });

        var btnClear = el('button', { type: 'button', class: 'px-3 py-1.5 rounded-md border bg-white hover:bg-gray-50 text-xs font-semibold', text: 'Clear' });
        btnClear.addEventListener('click', function(){
          opt.media_id = null; opt.media_url = null; opt.icon = null;
          applyPreview();
          persist(root, state);
        });

        var btnCopy = el('button', { type: 'button', class: 'px-3 py-1.5 rounded-md bg-gray-900 text-white hover:bg-gray-800 text-xs font-semibold', text: 'Copy shortcode' });
        btnCopy.addEventListener('click', async function(){
          var sc = '';
          if (opt.icon && (opt.icon.kind || opt.icon.value)) {
            var kind = String(opt.icon.kind || '').toLowerCase();
            var value = String(opt.icon.value || '').trim();
            var size = parseInt(opt.icon.size || 24, 10) || 24;
            var colour = String(opt.icon.colour || opt.icon.color || '#111827');
            sc = `[icon kind="${kind}" value="${value}" size="${size}" colour="${colour}"]`;
          }
          else if (opt.media_url) sc = opt.media_url;
          else sc = opt.value || opt.label || '';
          try {
            await navigator.clipboard.writeText(sc);
            btnCopy.textContent = 'Copied';
            setTimeout(function(){ btnCopy.textContent = 'Copy shortcode'; }, 900);
          } catch (_) { /* ignore */ }
        });

        // price mapping (Phase 1): choose pricing option id
        var priceSel = el('select', { class: 'rounded-md border-gray-300 text-xs' });
        priceSel.setAttribute('data-fb-opt-price', '1');
        priceSel.setAttribute('data-field-id', field.id);
        priceSel.setAttribute('data-opt-idx', String(idx));
        actions.appendChild(prev);
        actions.appendChild(btnImg);
        actions.appendChild(btnIcon);
        actions.appendChild(btnClear);
        actions.appendChild(btnCopy);
        actions.appendChild(priceSel);

        // remove option
        var btnDel = el('button', { type: 'button', class: 'ml-auto px-3 py-1.5 rounded-md border bg-white hover:bg-gray-50 text-xs font-semibold text-red-700', text: 'Remove' });
        btnDel.addEventListener('click', function(){
          field.options.splice(idx, 1);
          renderOptions();
          persist(root, state);
          renderPricingOptionSelects(root, state);
        });

        row.appendChild(top);
        row.appendChild(actions);
        row.appendChild(btnDel);
        optList.appendChild(row);
      });

      renderPricingOptionSelects(root, state);
    }

    if (isOptions) renderOptions();

    if (optAdd) {
      var markerAdd = 'optadd:' + field.id;
      if (optAdd.dataset.boundKey !== markerAdd) {
        optAdd.addEventListener('click', function(){
          field.options = Array.isArray(field.options) ? field.options : [];
          var n = field.options.length + 1;
          field.options.push({ label: 'Option ' + n, value: 'option_' + n });
          renderOptions();
          persist(root, state);
        });
        optAdd.dataset.boundKey = markerAdd;
      }
    }

    // always bind pricing below
    bindPricing(root, state);
  }

  function bindPricing(root, state) {
    var pricingWrap = qs('[data-fb-pricing]', root);
    if (!pricingWrap) return;

    var enabled = qs('[data-fb-pricing-enabled]', pricingWrap);
    var body = qs('[data-fb-pricing-body]', pricingWrap);
    var addBtn = qs('[data-fb-pricing-add]', pricingWrap);
    var optList = qs('[data-fb-pricing-options]', pricingWrap);
    var defSel = qs('[data-fb-pricing-default]', pricingWrap);

    function renderPricing() {
      state.settings.pricing = state.settings.pricing && typeof state.settings.pricing === 'object' ? state.settings.pricing : {};
      var pr = state.settings.pricing;
      pr.enabled = !!pr.enabled;
      pr.options = Array.isArray(pr.options) ? pr.options : [];
      pr.default = pr.default || '';

      if (enabled) enabled.checked = pr.enabled;
      if (body) body.style.display = pr.enabled ? '' : 'none';

      // pricing options list
      if (optList) {
        optList.innerHTML = '';
        pr.options.forEach(function(p, idx){
          var row = el('div', { class: 'rounded-md border bg-white p-2 flex items-center gap-2 flex-wrap' });
          var name = el('input', { type: 'text', class: 'rounded-md border-gray-300 text-sm', value: p.label || '' });
          name.placeholder = 'Label (e.g. Standard)';
          var amt = el('input', { type: 'number', class: 'rounded-md border-gray-300 text-sm w-28', value: p.amount || 0 });
          amt.placeholder = 'ZAR';
          var del = el('button', { type: 'button', class: 'px-3 py-1.5 rounded-md border bg-white hover:bg-gray-50 text-xs font-semibold text-red-700', text: 'Remove' });

          name.addEventListener('input', function(){ p.label = name.value; persist(root, state); renderPricingOptionSelects(root, state); renderPricingDefaultSelect(root, state); });
          amt.addEventListener('input', function(){ p.amount = parseFloat(amt.value || '0') || 0; persist(root, state); });
          del.addEventListener('click', function(){
            pr.options.splice(idx, 1);
            if (pr.default === p.id) pr.default = '';
            persist(root, state);
            renderPricing();
            renderPricingOptionSelects(root, state);
            renderPricingDefaultSelect(root, state);
          });

          row.appendChild(name);
          row.appendChild(amt);
          row.appendChild(del);
          optList.appendChild(row);
        });
      }

      renderPricingDefaultSelect(root, state);
      renderPricingOptionSelects(root, state);
    }

    if (enabled && enabled.dataset.bound !== '1') {
      enabled.addEventListener('change', function(){
        state.settings.pricing.enabled = !!enabled.checked;
        persist(root, state);
        renderPricing();
      });
      enabled.dataset.bound = '1';
    }

    if (addBtn && addBtn.dataset.bound !== '1') {
      addBtn.addEventListener('click', function(){
        state.settings.pricing.options = state.settings.pricing.options || [];
        var id = 'p_' + uid();
        state.settings.pricing.options.push({ id: id, label: 'Price ' + (state.settings.pricing.options.length + 1), amount: 0 });
        persist(root, state);
        renderPricing();
      });
      addBtn.dataset.bound = '1';
    }

    renderPricing();
  }

  function renderPricingDefaultSelect(root, state) {
    var pricingWrap = qs('[data-fb-pricing]', root);
    if (!pricingWrap) return;
    var defSel = qs('[data-fb-pricing-default]', pricingWrap);
    if (!defSel) return;

    var pr = state.settings.pricing || {};
    var opts = Array.isArray(pr.options) ? pr.options : [];
    defSel.innerHTML = '';
    defSel.appendChild(el('option', { value: '', text: 'None' }));
    opts.forEach(function(p){
      var o = el('option', { value: p.id, text: (p.label || p.id) + ' (R' + (p.amount || 0) + ')' });
      if (p.id === pr.default) o.selected = true;
      defSel.appendChild(o);
    });

    if (defSel.dataset.bound !== '1') {
      defSel.addEventListener('change', function(){
        state.settings.pricing.default = defSel.value || '';
        persist(root, state);
      });
      defSel.dataset.bound = '1';
    }
  }

  function renderPricingOptionSelects(root, state) {
    var pr = state.settings.pricing || {};
    var enabled = !!pr.enabled;
    var opts = Array.isArray(pr.options) ? pr.options : [];

    qsa('select[data-fb-opt-price]', root).forEach(function(sel){
      // hide if pricing disabled
      sel.style.display = enabled ? '' : 'none';
      var fid = sel.getAttribute('data-field-id');
      var idxStr = sel.getAttribute('data-opt-idx');
      var idx = parseInt(idxStr || '0', 10);
      var field = state.fields[fid];
      if (!field || !Array.isArray(field.options) || !field.options[idx]) return;
      var opt = field.options[idx];

      sel.innerHTML = '';
      sel.appendChild(el('option', { value: '', text: 'No price' }));
      opts.forEach(function(p){
        var o = el('option', { value: p.id, text: (p.label || p.id) + ' (R' + (p.amount || 0) + ')' });
        if (opt.price_id && opt.price_id === p.id) o.selected = true;
        sel.appendChild(o);
      });

      if (sel.dataset.boundKey !== ('price:' + fid + ':' + idx)) {
        sel.addEventListener('change', function(){
          opt.price_id = sel.value || '';
          persist(root, state);
        });
        sel.dataset.boundKey = 'price:' + fid + ':' + idx;
      }
    });
  }

  function bindTopButtons(root, state) {
    var btnRow = qs('[data-fb-add-row]', root);
    var btnSection = qs('[data-fb-add-section]', root);
    var btnPb = qs('[data-fb-add-pagebreak]', root);

    if (btnRow && btnRow.dataset.bound !== '1') {
      btnRow.addEventListener('click', function(){
        state.settings.layout.push({
          id: 'row_' + uid(),
          type: 'row',
          columns: 1,
          cols: [{ id: 'col_' + uid(), fields: [] }]
        });
        renderCanvas(root, state);
        persist(root, state);
      });
      btnRow.dataset.bound = '1';
    }

    if (btnSection && btnSection.dataset.bound !== '1') {
      btnSection.addEventListener('click', function(){
        state.settings.layout.push({
          id: 'sec_' + uid(),
          type: 'section',
          title: '',
          columns: 1,
          cols: [{ id: 'col_' + uid(), fields: [] }]
        });
        renderCanvas(root, state);
        persist(root, state);
      });
      btnSection.dataset.bound = '1';
    }

    if (btnPb && btnPb.dataset.bound !== '1') {
      btnPb.addEventListener('click', function(){
        state.settings.layout.push({ id: 'pb_' + uid(), type: 'page_break' });
        renderCanvas(root, state);
        persist(root, state);
      });
      btnPb.dataset.bound = '1';
    }
  }

  ready(function(){
    var root = document.getElementById('impart-forms-builder');
    if (!root) return;

    var initialRaw = root.getAttribute('data-initial') || '{}';
    var initial = parseJsonSafe(initialRaw, {});
    var state = normaliseInitial(initial);
    state.selectedFieldId = null;

    // Palette drag
    bindPalette(root);

    // Top buttons
    bindTopButtons(root, state);

    // Initial render
    renderCanvas(root, state);
    renderInspector(root, state);
    persist(root, state);

    // Re-bind palette on any DOM updates (cheap)
    var obs = new MutationObserver(function(){ bindPalette(root); });
    obs.observe(root, { subtree: true, childList: true });
  });
})();
