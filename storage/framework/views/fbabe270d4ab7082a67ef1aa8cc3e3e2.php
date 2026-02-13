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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Settings</h2>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="sm:px-6 lg:px-8">
            <?php if(session('status')): ?>
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    <div class="font-semibold">Please fix the following:</div>
                    <ul class="list-disc pl-5 mt-2 space-y-1">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="<?php echo e(route('admin.settings.update')); ?>" enctype="multipart/form-data" class="p-6 space-y-10">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    
                    <section>
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">General</h3>
                            <p class="text-xs text-gray-500">Admin branding + defaults</p>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Site name</label>
                                <input
                                    type="text"
                                    name="site_name"
                                    value="<?php echo e(old('site_name', $siteName)); ?>"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                                >
                                <?php $__errorArgs = ['site_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        name="admin_show_name_with_logo"
                                        value="1"
                                        <?php echo e(old('admin_show_name_with_logo', $showNameWithLogo) ? 'checked' : ''); ?>

                                        class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                                    >
                                    Show site name text next to logo (if a logo is set)
                                </label>
                            </div>
                        </div>

                        
                        <div class="mt-8 border-t pt-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Logo</div>
                                    <p class="text-xs text-gray-500 mt-1">If no logo → show site name text. If logo → logo-only by default (optional logo + text).</p>
                                </div>
                            </div>

                            <?php
                                $hasLogo = !empty($logoMediaUrl) || !empty($logoPath);
                                $logoPreviewUrl = !empty($logoMediaUrl)
                                    ? $logoMediaUrl
                                    : (!empty($logoPath) ? asset('storage/' . $logoPath) : null);
                            ?>

                            <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div>
                                    <?php if (isset($component)) { $__componentOriginal78e2226de3aca9b0c13f2dda29d8d009 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.media-picker','data' => ['label' => 'Choose from Media library','name' => 'site_logo_media_id','value' => old('site_logo_media_id', $logoMediaId),'previewUrl' => $logoPreviewUrl,'type' => 'images','buttonText' => 'Choose logo','clearText' => 'Clear selection','clearCheckboxId' => 'remove_logo']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.media-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Choose from Media library','name' => 'site_logo_media_id','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('site_logo_media_id', $logoMediaId)),'preview-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($logoPreviewUrl),'type' => 'images','button-text' => 'Choose logo','clear-text' => 'Clear selection','clear-checkbox-id' => 'remove_logo']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009)): ?>
<?php $attributes = $__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009; ?>
<?php unset($__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal78e2226de3aca9b0c13f2dda29d8d009)): ?>
<?php $component = $__componentOriginal78e2226de3aca9b0c13f2dda29d8d009; ?>
<?php unset($__componentOriginal78e2226de3aca9b0c13f2dda29d8d009); ?>
<?php endif; ?>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Selecting a Media image as your logo will <span class="font-semibold">not</span> delete it when removed from Settings.
                                    </p>
                                    <?php $__errorArgs = ['site_logo_media_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Upload logo (optional)</label>
                                    <input type="file" name="site_logo" accept="image/*,.svg" class="mt-1 block w-full text-sm text-gray-700">
                                    <p class="mt-1 text-xs text-gray-500">Uploads are stored under <code class="font-mono">storage/app/public/settings</code>.</p>
                                    <?php $__errorArgs = ['site_logo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                    <div class="mt-4">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input id="remove_logo" type="checkbox" name="remove_logo" value="1"
                                                   class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                            Remove logo (clears logo setting only)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <div class="mt-8 border-t pt-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Favicon</div>
                                    <p class="text-xs text-gray-500 mt-1">Shown in browser tabs and bookmarks.</p>
                                </div>
                            </div>

                            <?php
                                $hasFavicon = !empty($faviconMediaUrl) || !empty($faviconPath);
                                $faviconPreviewUrl = !empty($faviconMediaUrl)
                                    ? $faviconMediaUrl
                                    : (!empty($faviconPath) ? asset('storage/' . $faviconPath) : null);
                            ?>

                            <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <div>
                                    <?php if (isset($component)) { $__componentOriginal78e2226de3aca9b0c13f2dda29d8d009 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.media-picker','data' => ['label' => 'Choose from Media library','name' => 'site_favicon_media_id','value' => old('site_favicon_media_id', $faviconMediaId),'previewUrl' => $faviconPreviewUrl,'type' => '','buttonText' => 'Choose favicon','clearText' => 'Clear selection','clearCheckboxId' => 'remove_favicon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.media-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Choose from Media library','name' => 'site_favicon_media_id','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('site_favicon_media_id', $faviconMediaId)),'preview-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($faviconPreviewUrl),'type' => '','button-text' => 'Choose favicon','clear-text' => 'Clear selection','clear-checkbox-id' => 'remove_favicon']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009)): ?>
<?php $attributes = $__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009; ?>
<?php unset($__attributesOriginal78e2226de3aca9b0c13f2dda29d8d009); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal78e2226de3aca9b0c13f2dda29d8d009)): ?>
<?php $component = $__componentOriginal78e2226de3aca9b0c13f2dda29d8d009; ?>
<?php unset($__componentOriginal78e2226de3aca9b0c13f2dda29d8d009); ?>
<?php endif; ?>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Recommended: <span class="font-semibold">ICO</span> or <span class="font-semibold">PNG</span> (32×32 / 48×48). Media items are never deleted via Settings.
                                    </p>
                                    <?php $__errorArgs = ['site_favicon_media_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Upload favicon (optional)</label>
                                    <input type="file" name="site_favicon" accept=".ico,image/*,.svg" class="mt-1 block w-full text-sm text-gray-700">
                                    <p class="mt-1 text-xs text-gray-500">Uploads are stored under <code class="font-mono">storage/app/public/settings</code>.</p>
                                    <?php $__errorArgs = ['site_favicon'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                    <div class="mt-4">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input id="remove_favicon" type="checkbox" name="remove_favicon" value="1"
                                                   class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                            Remove favicon (clears favicon setting only)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    
                    <section class="border-t pt-10">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Homepage</h3>
                            <p class="text-xs text-gray-500">Select which published page resolves as “/”</p>
                        </div>

                        <div class="mt-5 max-w-xl">
                            <label class="block text-sm font-medium text-gray-700">Homepage page</label>
                            <select name="homepage_page_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                <option value="">— Select a published page —</option>
                                <?php $__currentLoopData = $homepagePages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($p->id); ?>" <?php echo e((int) old('homepage_page_id', $homepagePageId) === (int) $p->id ? 'selected' : ''); ?>>
                                        <?php echo e($p->title); ?> (/<?php echo e($p->slug); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['homepage_page_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </section>

                    
                    <section class="border-t pt-10">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Shortcodes</h3>
                            <p class="text-xs text-gray-500">Manual embeds (forms + icons)</p>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="bg-slate-50 border rounded-xl p-4">
                                <div class="text-xs font-semibold text-gray-800 uppercase tracking-wide">Icon shortcode</div>
                                <div class="mt-2 text-sm text-gray-700">
                                    Use this inside any page body.
                                </div>
                                <pre class="mt-3 text-xs bg-white border rounded-lg p-3 overflow-auto"><code>[icon kind="fa" value="fa-solid fa-house" size="24" colour="#111827"]
[icon kind="lucide" value="home" size="24" colour="#111827"]</code></pre>
                                <div class="mt-3 text-xs text-gray-600">
                                    Alternative JSON form (use <span class="font-semibold">single quotes</span> so JSON quotes don’t break the shortcode):
                                </div>
                                <pre class="mt-2 text-xs bg-white border rounded-lg p-3 overflow-auto"><code>[icon data='{"kind":"fa","value":"fa-solid fa-house","size":24,"colour":"#111827"}']</code></pre>
                            </div>

                            <div class="bg-slate-50 border rounded-xl p-4">
                                <div class="text-xs font-semibold text-gray-800 uppercase tracking-wide">Form shortcode</div>
                                <div class="mt-2 text-sm text-gray-700">
                                    Embed an active form by slug.
                                </div>
                                <pre class="mt-3 text-xs bg-white border rounded-lg p-3 overflow-auto"><code>[form slug="contact"]
[form slug="contact" to="hello@example.com"]</code></pre>
                            </div>
                        </div>
                    </section>

                    <div class="pt-6 border-t flex items-center justify-end gap-3">
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-gray-800">
                            Save settings
                        </button>
                    </div>
                </form>
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/settings/edit.blade.php ENDPATH**/ ?>