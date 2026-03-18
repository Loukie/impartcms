<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Visual Editor — {{ $title }}</title>
    <style>
        html, body { height: 100%; overflow: hidden; margin: 0; padding: 0; font-family: system-ui, sans-serif; background: #1e1e1e; }
        #ve-toolbar, #ve-toolbar * { box-sizing: border-box; margin: 0; padding: 0; }

        /* Toolbar */
        #ve-toolbar {
            display: flex;
            align-items: center;
            gap: 10px;
            height: 48px;
            padding: 0 16px;
            background: #111;
            border-bottom: 1px solid rgba(255,255,255,.08);
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 9999;
        }
        #ve-toolbar .ve-title {
            font-size: .8rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-right: auto;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 300px;
        }
        #ve-toolbar .ve-context {
            font-size: .7rem;
            color: #64748b;
            background: rgba(255,255,255,.06);
            padding: 2px 8px;
            border-radius: 4px;
        }
        .ve-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: opacity .15s;
        }
        .ve-btn:hover { opacity: .85; }
        .ve-btn-back { background: rgba(255,255,255,.08); color: #cbd5e1; }
        .ve-btn-save { background: #2563eb; color: #fff; }
        .ve-btn-save.saving { background: #1d4ed8; }
        .ve-btn-typo { background: rgba(255,255,255,.08); color: #cbd5e1; }
        .ve-btn-typo.active { background: #7c3aed; color: #fff; }
        #ve-status {
            font-size: .75rem;
            color: #64748b;
            min-width: 80px;
            text-align: right;
        }

        /* GrapesJS fills remaining height below toolbar */
        #ve-editor {
            position: fixed;
            top: 48px; left: 0; right: 0; bottom: 0;
            overflow: hidden;
            transition: right .25s ease;
        }
        #ve-editor.typo-open { right: 420px; }

        /* Force GrapesJS to fill the container */
        #ve-editor .gjs-editor { height: 100% !important; }
        #ve-editor .gjs-editor-cont { height: 100% !important; }

        /* Canvas area fills remaining width */
        #ve-editor .gjs-cv-canvas {
            top: 40px !important;
            height: calc(100% - 40px) !important;
            background: #f0f0f0 !important;
        }

        /* Left panel (blocks/layers) — scrollable */
        #ve-editor .gjs-pn-panel.gjs-pn-views-container {
            height: calc(100% - 40px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        /* Right panel icons row */
        #ve-editor .gjs-pn-panel.gjs-pn-views { height: 40px; }

        /* Block manager and layer manager scroll */
        #ve-editor .gjs-blocks-c,
        #ve-editor .gjs-layer-manager,
        #ve-editor .gjs-sm-sectors,
        #ve-editor .gjs-trt-traits {
            overflow-y: auto !important;
            height: 100% !important;
        }

        /* GrapesJS theme — standard dark grey */
        .gjs-one-bg  { background: #383838 !important; }
        .gjs-two-bg  { background: #2e2e2e !important; }
        .gjs-three-bg{ background: #222 !important; }
        .gjs-four-bg { background: #111 !important; }
        .gjs-pn-panel{ background: #2e2e2e; }

        /* ── Typography Panel ─────────────────────────────────── */
        #ve-typo-panel {
            display: none;
            position: fixed;
            top: 48px; right: 0; bottom: 0;
            width: 420px;
            background: #1a1a1a;
            border-left: 1px solid rgba(255,255,255,.1);
            z-index: 9998;
            overflow-y: auto;
            font-size: .8rem;
            color: #e2e8f0;
            box-sizing: border-box;
        }
        #ve-typo-panel.open { display: block; }

        .ve-typo-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,.1);
            position: sticky; top: 0; background: #1a1a1a; z-index: 2;
        }
        .ve-typo-header span { font-weight: 700; font-size: .85rem; }
        .ve-typo-close { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1.3rem; line-height: 1; padding: 0; }

        .ve-typo-section { padding: 14px 16px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .ve-typo-section-title {
            font-size: .7rem; font-weight: 700; letter-spacing: .08em;
            text-transform: uppercase; color: #64748b; margin-bottom: 10px;
        }

        .ve-typo-tabs { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 14px; }
        .ve-typo-tab {
            padding: 4px 10px; border-radius: 4px; font-size: .75rem; font-weight: 600;
            cursor: pointer; border: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.05); color: #94a3b8;
            transition: all .15s;
        }
        .ve-typo-tab:hover { color: #e2e8f0; background: rgba(255,255,255,.1); }
        .ve-typo-tab.active { background: #7c3aed; border-color: #7c3aed; color: #fff; }

        .ve-typo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .ve-typo-field { display: flex; flex-direction: column; gap: 3px; }
        .ve-typo-field.full { grid-column: 1 / -1; }
        .ve-typo-field label { font-size: .68rem; color: #64748b; font-weight: 500; }
        .ve-typo-field input,
        .ve-typo-field select {
            background: #2a2a2a; border: 1px solid rgba(255,255,255,.12);
            color: #e2e8f0; border-radius: 4px; padding: 5px 8px;
            font-size: .78rem; width: 100%; box-sizing: border-box;
        }
        .ve-typo-field input:focus,
        .ve-typo-field select:focus { outline: none; border-color: #7c3aed; }
        .ve-typo-field input:disabled,
        .ve-typo-field select:disabled { opacity: .35; cursor: not-allowed; }

        .ve-typo-color-row { display: flex; gap: 6px; align-items: center; }
        .ve-typo-color-row input[type="color"] {
            width: 32px; height: 30px; padding: 2px; border-radius: 4px; cursor: pointer; flex-shrink: 0;
        }
        .ve-typo-color-row input[type="text"] { flex: 1; }

        .ve-typo-override {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 12px; padding: 8px; border-radius: 6px;
            background: rgba(124,58,237,.12); border: 1px solid rgba(124,58,237,.2);
        }
        .ve-typo-override label { font-size: .78rem; color: #c4b5fd; cursor: pointer; }
        .ve-typo-override input[type="checkbox"] { cursor: pointer; accent-color: #7c3aed; }

        .ve-typo-font-wrap { position: relative; }
        .ve-typo-font-wrap input { width: 100%; box-sizing: border-box; }
        .ve-typo-ac-list {
            display: none; position: absolute; top: 100%; left: 0; right: 0;
            background: #2a2a2a; border: 1px solid rgba(124,58,237,.4);
            border-top: none; border-radius: 0 0 6px 6px;
            max-height: 180px; overflow-y: auto; z-index: 99999;
        }
        .ve-typo-ac-list.open { display: block; }
        .ve-typo-ac-item {
            padding: 6px 10px; font-size: .78rem; color: #cbd5e1;
            cursor: pointer; transition: background .1s;
        }
        .ve-typo-ac-item:hover, .ve-typo-ac-item.active { background: rgba(124,58,237,.3); color: #e2e8f0; }

        .ve-typo-save-row {
            padding: 14px 16px;
            position: sticky; bottom: 0; background: #1a1a1a;
            border-top: 1px solid rgba(255,255,255,.1);
        }
        .ve-typo-save-btn {
            width: 100%; padding: 9px; background: #7c3aed; color: #fff;
            border: none; border-radius: 6px; font-weight: 600; font-size: .8rem;
            cursor: pointer; transition: opacity .15s;
        }
        .ve-typo-save-btn:hover { opacity: .9; }
        .ve-typo-save-btn:disabled { opacity: .5; cursor: not-allowed; }
        .ve-typo-msg { font-size: .72rem; color: #22c55e; text-align: center; margin-top: 6px; min-height: 18px; }

    </style>
</head>
<body>
    <div id="ve-toolbar">
        <a href="{{ $backUrl }}" class="ve-btn ve-btn-back">← Back</a>
        <span class="ve-title">{{ $title }}</span>
        <span class="ve-context">{{ $context === 'page' ? 'Page Body' : 'Layout Block' }}</span>
        <span id="ve-status"></span>
        @if($context === 'page')
            <button id="ve-typo-btn" class="ve-btn ve-btn-typo" title="Typography">Tₐ Typography</button>
        @endif
        <button id="ve-save" class="ve-btn ve-btn-save">Save</button>
    </div>

    <div id="ve-editor"></div>

    @if($context === 'page')
    {{-- ── Typography Panel ──────────────────────────────────── --}}
    <div id="ve-typo-panel">
        <div class="ve-typo-header">
            <span>Typography</span>
            <button class="ve-typo-close" id="ve-typo-close">×</button>
        </div>

        {{-- Global section --}}
        <div class="ve-typo-section">
            <div class="ve-typo-section-title">Global Typography</div>
            <div class="ve-typo-tabs" id="ve-typo-global-tabs"></div>
            <div id="ve-typo-global-fields"></div>
        </div>

        {{-- Page override section --}}
        <div class="ve-typo-section">
            <div class="ve-typo-section-title">Page Typography</div>
            <div class="ve-typo-tabs" id="ve-typo-page-tabs"></div>
            <div id="ve-typo-page-fields"></div>
        </div>

        <div class="ve-typo-save-row">
            <button class="ve-typo-save-btn" id="ve-typo-save">Save Typography</button>
            <div class="ve-typo-msg" id="ve-typo-msg"></div>
        </div>
    </div>

    @endif

    <script>
        window.__VE__ = {
            html:           @json($html),
            extractedCSS:   @json($extractedCSS),
            saveUrl:        @json($saveUrl),
            assetsUrl:      @json($assetsUrl),
            canvasCSS:      @json($canvasCSS),
            csrfToken:      document.querySelector('meta[name="csrf-token"]').content,
            @if($context === 'page')
            typographyUrl:  @json($typographyUrl),
            fontUploadUrl:  @json(route('admin.visual-editor.fonts.upload')),
            @endif
        };
    </script>
    @vite(['resources/js/visual-editor.js'])
</body>
</html>
