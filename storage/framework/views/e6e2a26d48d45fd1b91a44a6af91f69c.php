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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                AI Site Builder
            </h2>

            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('admin.pages.index')); ?>"
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                    Back to Pages
                </a>
            </div>
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-6">

                    <?php if(($step ?? 'input') === 'input'): ?>
                        <div>
                            <div class="text-sm text-gray-600">
                                Generate a blueprint (sitemap + per-page briefs), review it, then build pages. Everything defaults to <span class="font-semibold">draft</span> so you can review safely ✅
                            </div>
                        </div>

                        <form method="POST" action="<?php echo e(route('admin.site-builder.blueprint')); ?>" id="site-builder-input-form" class="space-y-5">
                            <?php echo csrf_field(); ?>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Site name *</label>
                                    <input name="site_name" value="<?php echo e(old('site_name', $input['site_name'] ?? '')); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. Acme Consulting" required>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Industry</label>
                                    <input name="industry" value="<?php echo e(old('industry', $input['industry'] ?? '')); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. Accounting, Construction, SaaS">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Location</label>
                                    <input name="location" value="<?php echo e(old('location', $input['location'] ?? '')); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. George, South Africa">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Audience</label>
                                    <input name="audience" value="<?php echo e(old('audience', $input['audience'] ?? '')); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. SMEs, homeowners, HR teams">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tone</label>
                                    <input name="tone" value="<?php echo e(old('tone', $input['tone'] ?? 'clear, modern, confident')); ?>" class="mt-1 w-full rounded-md border-gray-300">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Primary CTA</label>
                                    <input name="primary_cta" value="<?php echo e(old('primary_cta', $input['primary_cta'] ?? 'Get in touch')); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="e.g. Book a call">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Page preset</label>
                                    <select name="page_preset" class="mt-1 w-full rounded-md border-gray-300">
                                        <?php $preset = old('page_preset', $input['page_preset'] ?? 'business'); ?>
                                        <option value="basic" <?php echo e($preset === 'basic' ? 'selected' : ''); ?>>Basic (5–6 pages)</option>
                                        <option value="business" <?php echo e($preset === 'business' ? 'selected' : ''); ?>>Business (7–9 pages)</option>
                                        <option value="full" <?php echo e($preset === 'full' ? 'selected' : ''); ?>>Full (10–14 pages)</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Extra notes (optional)</label>
                                <textarea id="site-builder-notes" name="notes" rows="5" class="mt-1 w-full rounded-md border-gray-300" placeholder="Any must-have pages, services, offers, brand personality, keywords, etc."><?php echo e(old('notes', $input['notes'] ?? '')); ?></textarea>
                                <div id="notes-counter" class="text-xs text-gray-500 mt-1"></div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button id="blueprint-btn" type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    Generate Blueprint
                                </button>
                                <div class="text-xs text-gray-500">Rate limit: 3 per minute</div>
                            </div>
                        </form>

                    <?php elseif(($step ?? '') === 'blueprint'): ?>
                        <div class="space-y-2">
                            <div class="text-sm text-gray-600">
                                Blueprint generated ✅ Review/edit the JSON if needed, then build the site.
                            </div>
                        </div>

                        <?php
                            $pages = is_array($blueprint['pages'] ?? null) ? $blueprint['pages'] : [];
                        ?>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm font-semibold text-gray-900 mb-2">Blueprint JSON</div>
                                <form method="POST" action="<?php echo e(route('admin.site-builder.build')); ?>" class="space-y-4">
                                    <?php echo csrf_field(); ?>
                                    <textarea name="blueprint_json" rows="18" class="w-full font-mono text-xs rounded-md border-gray-300"><?php echo e(old('blueprint_json', $blueprintJson ?? '')); ?></textarea>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Style mode</label>
                                            <select name="style_mode" class="mt-1 w-full rounded-md border-gray-300">
                                                <?php $sm = old('style_mode', 'inline'); ?>
                                                <option value="inline" <?php echo e($sm === 'inline' ? 'selected' : ''); ?>>Inline styles (recommended)</option>
                                                <option value="classes" <?php echo e($sm === 'classes' ? 'selected' : ''); ?>>Classes</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Default template</label>
                                            <select name="template" class="mt-1 w-full rounded-md border-gray-300">
                                                <?php $tpl = old('template', 'blank'); ?>
                                                <option value="blank" <?php echo e($tpl === 'blank' ? 'selected' : ''); ?>>blank</option>
                                                <option value="" <?php echo e($tpl === '' ? 'selected' : ''); ?>>(theme default)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Build mode</label>
                                            <select name="action" class="mt-1 w-full rounded-md border-gray-300">
                                                <?php $act = old('action', 'draft'); ?>
                                                <option value="draft" <?php echo e($act === 'draft' ? 'selected' : ''); ?>>Create drafts</option>
                                                <option value="publish" <?php echo e($act === 'publish' ? 'selected' : ''); ?>>Publish all pages</option>
                                            </select>
                                        </div>

                                        <div class="flex items-end">
                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="publish_homepage" value="1" class="rounded border-gray-300" <?php echo e(old('publish_homepage') ? 'checked' : ''); ?>>
                                                Publish homepage (even if drafts)
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="set_homepage" value="1" class="rounded border-gray-300" <?php echo e(old('set_homepage', '1') ? 'checked' : ''); ?>>
                                            Set homepage as active (updates Settings)
                                        </label>
                                        <div class="text-xs text-gray-500 mt-1">Requires the homepage to be published.</div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <button id="build-btn" type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                            Build Site
                                        </button>
                                        <a href="<?php echo e(route('admin.site-builder.create')); ?>" class="text-sm text-gray-600 hover:text-gray-900">Start over</a>
                                    </div>
                                </form>
                            </div>

                            <div>
                                <div class="text-sm font-semibold text-gray-900 mb-2">Pages preview</div>
                                <div class="rounded border border-gray-200 overflow-hidden">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50 text-gray-700">
                                        <tr>
                                            <th class="text-left px-3 py-2">Title</th>
                                            <th class="text-left px-3 py-2">Slug</th>
                                            <th class="text-left px-3 py-2">Home</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php $__empty_1 = true; $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <?php
                                                $t = is_array($p) ? (string)($p['title'] ?? '') : '';
                                                $s = is_array($p) ? (string)($p['slug'] ?? '') : '';
                                                $h = is_array($p) ? (bool)($p['is_homepage'] ?? false) : false;
                                            ?>
                                            <tr class="border-t">
                                                <td class="px-3 py-2 font-medium text-gray-900"><?php echo e($t); ?></td>
                                                <td class="px-3 py-2 text-gray-700"><?php echo e($s); ?></td>
                                                <td class="px-3 py-2"><?php echo $h ? '<span class="inline-flex items-center px-2 py-0.5 rounded bg-green-50 text-green-800 border border-green-200 text-xs">Yes</span>' : '<span class="text-xs text-gray-500">—</span>'; ?></td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr><td colspan="3" class="px-3 py-4 text-gray-500">No pages found in blueprint.</td></tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-xs text-gray-500 mt-3">
                                    Tip: keep service detail pages focused on one service each. You can always delete drafts later.
                                </div>
                            </div>
                        </div>

                    <?php elseif(($step ?? '') === 'report'): ?>
                        <?php
                            $r = is_array($report ?? null) ? $report : null;
                            $rows = is_array($r['pages'] ?? null) ? $r['pages'] : [];
                            $warnings = is_array($r['warnings'] ?? null) ? $r['warnings'] : [];
                            $homepageId = isset($r['homepage_id']) ? (int)$r['homepage_id'] : 0;
                        ?>

                        <div class="space-y-3">
                            <div class="text-sm text-gray-700">
                                Build complete ✅ Created <span class="font-semibold"><?php echo e(count($rows)); ?></span> pages.
                            </div>

                            <?php if($homepageId > 0): ?>
                                <div class="text-sm">
                                    Homepage published: <a class="text-blue-700 hover:underline" href="<?php echo e(route('admin.pages.edit', $homepageId)); ?>">Edit homepage</a>
                                </div>
                            <?php endif; ?>

                            <?php if(count($warnings) > 0): ?>
                                <div class="p-3 rounded bg-yellow-50 border border-yellow-200 text-yellow-900 text-sm">
                                    <div class="font-semibold mb-1">Warnings</div>
                                    <ul class="list-disc ml-5 space-y-1">
                                        <?php $__currentLoopData = $warnings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $w): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <li><?php echo e($w); ?></li>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="rounded border border-gray-200 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-700">
                                <tr>
                                    <th class="text-left px-3 py-2">Page</th>
                                    <th class="text-left px-3 py-2">Slug</th>
                                    <th class="text-left px-3 py-2">Status</th>
                                    <th class="text-left px-3 py-2">Result</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $id = (int)($row['id'] ?? 0);
                                        $title = (string)($row['title'] ?? '');
                                        $slug = (string)($row['slug'] ?? '');
                                        $status = (string)($row['status'] ?? 'draft');
                                        $err = $row['error'] ?? null;
                                    ?>
                                    <tr class="border-t">
                                        <td class="px-3 py-2 font-medium text-gray-900">
                                            <?php if($id > 0): ?>
                                                <a class="text-blue-700 hover:underline" href="<?php echo e(route('admin.pages.edit', $id)); ?>"><?php echo e($title); ?></a>
                                            <?php else: ?>
                                                <?php echo e($title); ?>

                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2 text-gray-700"><?php echo e($slug); ?></td>
                                        <td class="px-3 py-2">
                                            <?php if($status === 'published'): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-50 text-green-800 border border-green-200 text-xs">published</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-50 text-gray-800 border border-gray-200 text-xs">draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-2">
                                            <?php if($err): ?>
                                                <span class="text-red-700"><?php echo e($err); ?></span>
                                            <?php else: ?>
                                                <span class="text-green-700">OK</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="<?php echo e(route('admin.pages.index')); ?>" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                Go to Pages
                            </a>

                            <a href="<?php echo e(route('admin.site-builder.create')); ?>" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                                Build another site
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            // disable buttons during submit
            const inputForm = document.getElementById('site-builder-input-form');
            if(inputForm){
                const btn = document.getElementById('blueprint-btn');
                inputForm.addEventListener('submit', function(){
                    if(btn){
                        btn.disabled = true;
                        btn.dataset.orig = btn.innerHTML;
                        btn.innerHTML = 'Working…';
                    }
                });
            }

            const buildForm = document.querySelector('form[action$="site-builder.build"]');
            if(buildForm){
                const btn = document.getElementById('build-btn');
                buildForm.addEventListener('submit', function(){
                    if(btn){
                        btn.disabled = true;
                        btn.dataset.orig = btn.innerHTML;
                        btn.innerHTML = 'Working…';
                    }
                });
            }

            // notes counter
            const notes = document.getElementById('site-builder-notes');
            const counter = document.getElementById('notes-counter');
            if(notes && counter){
                function update(){ counter.textContent = notes.value.length + ' characters'; }
                notes.addEventListener('input', update);
                update();
            }
        });
    </script>
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/pages/ai-site-builder.blade.php ENDPATH**/ ?>