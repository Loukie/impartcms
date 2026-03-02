

<div id="impart-ai-assist" class="fixed bottom-5 right-5 z-[9999]" data-admin-base="<?php echo e(url('/' . trim(config('cms.admin_path', 'admin'), '/'))); ?>">
    <button type="button"
            id="impart-ai-assist-btn"
            class="inline-flex items-center gap-2 rounded-full bg-slate-950 text-white px-4 py-3 shadow-lg hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-400">
        <span class="text-sm font-semibold">AI</span>
        <span class="text-xs text-white/70">Assistant</span>
    </button>

    <div id="impart-ai-assist-modal" class="hidden fixed inset-0 z-[10000]">
        <div class="absolute inset-0 bg-black/40" data-impart-ai-close></div>
        <div class="absolute inset-0 flex items-end sm:items-center justify-center p-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-sm font-semibold text-slate-900">AI Assistant</div>
                        <div id="impart-ai-assist-subtitle" class="text-xs text-slate-500 truncate">Pick a page and describe what you want changed.</div>
                    </div>
                    <button type="button" class="text-slate-500 hover:text-slate-900" data-impart-ai-close aria-label="Close">
                        ✕
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Target page</label>
                        <div class="mt-1 flex flex-col sm:flex-row gap-2">
                            <input id="impart-ai-page-search" type="text" placeholder="Search pages by title/slug…"
                                   class="w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                            <select id="impart-ai-page-select"
                                    class="w-full sm:w-72 rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                                <option value="">— Select a page —</option>
                            </select>
                        </div>
                        <p class="mt-1 text-[11px] text-slate-500">Tip: if you’re editing a page, it will auto-select.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Instruction</label>
                        <textarea id="impart-ai-instruction" rows="4"
                                  class="mt-1 w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500"
                                  placeholder="e.g. Make this page more premium, reduce clutter, add a strong hero CTA, and rewrite the copy to be concise."></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Mode</label>
                            <select id="impart-ai-mode" class="mt-1 w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                                <option value="tweak">Tweak existing HTML</option>
                                <option value="rewrite">Rewrite from scratch</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Save as</label>
                            <select id="impart-ai-save" class="mt-1 w-full rounded-lg border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                                <option value="draft">Draft (safe default)</option>
                                <option value="keep">Keep current status</option>
                            </select>
                        </div>
                    </div>

                    <div id="impart-ai-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800"></div>
                    <div id="impart-ai-success" class="hidden rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800"></div>

                    <div class="flex flex-col sm:flex-row gap-2 sm:items-center sm:justify-between">
                        <div class="text-[11px] text-slate-500" id="impart-ai-hint"></div>

                        <div class="flex gap-2 justify-end">
                            <button type="button" data-impart-ai-close
                                    class="px-4 py-2 rounded-lg border border-slate-300 text-slate-800 text-sm font-semibold hover:bg-slate-50">
                                Close
                            </button>
                            <button type="button" id="impart-ai-run"
                                    class="px-4 py-2 rounded-lg bg-slate-950 text-white text-sm font-semibold hover:bg-slate-900 disabled:opacity-60 disabled:cursor-not-allowed">
                                Generate
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/partials/ai-popup.blade.php ENDPATH**/ ?>