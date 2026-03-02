<?php if (isset($component)) { $__componentOriginale0f1cdd055772eb1d4a99981c240763e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale0f1cdd055772eb1d4a99981c240763e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin-layout','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">AI Visual Audit</h2>
            <a href="<?php echo e(route('admin.pages.index')); ?>" class="underline text-sm text-gray-600 hover:text-gray-900">Back to Pages</a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <?php if(session('status')): ?>
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    <?php echo e($errors->first()); ?>

                </div>
            <?php endif; ?>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="prose max-w-none">
                        <p>
                            This tool captures a screenshot of one of your pages and a screenshot of a reference site,
                            then asks the AI to redesign your page to match the reference’s hierarchy and vibe.
                        </p>
                        <ul>
                            <li>✅ Saves as <strong>draft</strong> (safe)</li>
                            <li>✅ Keeps your existing page system intact</li>
                            <li>⚠️ Requires Chrome/Edge installed (no Node)</li>
                        </ul>
                    </div>

                    <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <div class="text-sm font-semibold text-gray-900">Setup (one-time)</div>
                        <div class="mt-2 text-xs text-gray-700">
                            Ensure <strong>Google Chrome</strong> or <strong>Microsoft Edge</strong> is installed. No Node required ✅<br>
                            Optional: if your browser is in a custom path, add this to your <code>.env</code>:
                            <div class="mt-2 font-mono text-[11px] bg-white border rounded p-3 overflow-auto">
                                AI_SCREENSHOT_BIN=C:\Path\To\chrome.exe
                            </div>
                            Quick check in CMD:
                            <div class="mt-2 font-mono text-[11px] bg-white border rounded p-3 overflow-auto">
                                where chrome<br>
                                where msedge
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="<?php echo e(route('admin.ai.visual-audit.redesign')); ?>" class="mt-6 space-y-4">
                        <?php echo csrf_field(); ?>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Select a page</label>
                            <select name="page_id" class="mt-1 w-full rounded-md border-gray-300">
                                <option value="">— choose —</option>
                                <?php $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p->id); ?>"><?php echo e($p->title); ?> (<?php echo e($p->slug); ?>) · <?php echo e(strtoupper($p->status)); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reference site URL</label>
                            <input type="url" name="reference_url" class="mt-1 w-full rounded-md border-gray-300" placeholder="https://example.com">
                            <p class="mt-1 text-xs text-gray-500">Tip: use the specific page URL that best matches your target look.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Extra instruction (optional)</label>
                            <textarea name="instruction" rows="3" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. Use a bold hero, lots of whitespace, and a clean card layout."></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                Capture &amp; Redesign (Draft)
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale0f1cdd055772eb1d4a99981c240763e)): ?>
<?php $attributes = $__attributesOriginale0f1cdd055772eb1d4a99981c240763e; ?>
<?php unset($__attributesOriginale0f1cdd055772eb1d4a99981c240763e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale0f1cdd055772eb1d4a99981c240763e)): ?>
<?php $component = $__componentOriginale0f1cdd055772eb1d4a99981c240763e; ?>
<?php unset($__componentOriginale0f1cdd055772eb1d4a99981c240763e); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/ai/visual-audit.blade.php ENDPATH**/ ?>