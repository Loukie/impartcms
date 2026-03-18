<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Visual Editor — {{ $title }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; font-family: system-ui, sans-serif; background: #1e1e1e; }

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
        }

        /* Force GrapesJS to fill the container */
        #ve-editor .gjs-editor {
            height: 100% !important;
        }
        #ve-editor .gjs-editor-cont {
            height: 100% !important;
        }

        /* Canvas area fills remaining width */
        #ve-editor .gjs-cv-canvas {
            top: 40px !important;
            height: calc(100% - 40px) !important;
            background: #f0f0f0;
        }

        /* Left panel (blocks/layers) — scrollable */
        #ve-editor .gjs-pn-panel.gjs-pn-views-container {
            height: calc(100% - 40px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }

        /* Right panel icons row */
        #ve-editor .gjs-pn-panel.gjs-pn-views {
            height: 40px;
        }

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

    </style>
</head>
<body>
    <div id="ve-toolbar">
        <a href="{{ $backUrl }}" class="ve-btn ve-btn-back">← Back</a>
        <span class="ve-title">{{ $title }}</span>
        <span class="ve-context">{{ $context === 'page' ? 'Page Body' : 'Layout Block' }}</span>
        <span id="ve-status"></span>
        <button id="ve-save" class="ve-btn ve-btn-save">Save</button>
    </div>

    <div id="ve-editor"></div>

    <script>
        window.__VE__ = {
            html:         @json($html),
            extractedCSS: @json($extractedCSS),
            saveUrl:      @json($saveUrl),
            assetsUrl:    @json($assetsUrl),
            canvasCSS:    @json($canvasCSS),
            csrfToken:    document.querySelector('meta[name="csrf-token"]').content,
        };
    </script>
    @vite(['resources/js/visual-editor.js'])
</body>
</html>
