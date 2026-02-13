<div class="bg-white border rounded-xl p-4">
    <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="text-sm font-semibold text-gray-900">Font Awesome Icons</div>
        <div class="text-xs text-gray-500">
            @if(request()->routeIs('admin.media.index'))
                Click an icon to copy its shortcode (e.g. <span class="font-mono">[icon kind=&quot;fa&quot; value=&quot;fa-solid fa-house&quot; size=&quot;24&quot; colour=&quot;#4CBB17&quot;]</span>)
            @else
                Click an icon to select it (or switch to “Copy shortcode”).
            @endif
        </div>
    </div>

    <div id="impart-icon-library" class="mt-4"
         data-mode="{{ request()->routeIs('admin.media.index') ? 'copy' : 'select' }}">
        <div class="flex flex-col lg:flex-row gap-3 lg:items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-slate-700">Search</label>
                <input id="impart-icon-search" type="text" placeholder="Search icons…"
                       class="mt-1 w-full rounded-md border-slate-300" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Style</label>
                <select id="impart-fa-style" class="mt-1 rounded-md border-slate-300">
                    <option value="all">All</option>
                    <option value="solid">Solid</option>
                    <option value="regular">Regular</option>
                    <option value="brands">Brands</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Size</label>
                <input id="impart-icon-size" type="number" min="8" max="256" value="24"
                       class="mt-1 w-28 rounded-md border-slate-300" />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Colour</label>
                <div class="mt-1 flex items-center gap-2">
                    <input id="impart-icon-colour" type="color" value="#111827"
                           class="h-10 w-12 rounded-md border border-slate-300 p-1" />
                    <input id="impart-icon-colour-text" type="text" value="#111827"
                           class="w-28 rounded-md border-slate-300" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Action</label>
                <select id="impart-icon-action" class="mt-1 rounded-md border-slate-300">
                    <option value="copy">Copy shortcode</option>
                    <option value="select">Select</option>
                </select>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-3 sm:grid-cols-5 md:grid-cols-8 lg:grid-cols-10 gap-3" id="impart-fa-grid"></div>

        <div class="mt-4">
            <button type="button" id="impart-icon-loadmore-fa"
                    class="px-3 py-2 rounded-md border bg-white text-sm font-semibold hover:bg-slate-50 hidden">
                Load more
            </button>
        </div>

        <div id="impart-icon-toast" class="fixed bottom-4 right-4 hidden px-3 py-2 rounded-md bg-slate-900 text-white text-sm shadow-lg"></div>
    </div>
</div>
