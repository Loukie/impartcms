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
                                    <p class="text-xs text-gray-500 mt-1">
                                        If no logo → show site name text · If logo → logo-only by default (optional logo + text).
                                    </p>
                                </div>
                            </div>

                            <?php
                                $hasLogo = !empty($logoMediaUrl) || !empty($logoPath);
                                $logoPreviewUrl = !empty($logoMediaUrl)
                                    ? $logoMediaUrl
                                    : (!empty($logoPath) ? asset('storage/' . $logoPath) : null);
                            ?>

                            <div class="mt-4">
                                <?php if (isset($component)) { $__componentOriginal8704d840cb70e3dce5479facb8ad9f63 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8704d840cb70e3dce5479facb8ad9f63 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.media-icon-picker','data' => ['label' => 'Choose from Media library','mediaName' => 'site_logo_media_id','iconName' => 'site_logo_icon_json','clearName' => 'site_logo_clear','mediaId' => $logoMediaId,'mediaUrl' => $logoPreviewUrl,'iconJson' => $logoIconJson,'allow' => 'images,icons','help' => 'Pick an image OR an icon. Choosing one clears the other.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.media-icon-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Choose from Media library','media-name' => 'site_logo_media_id','icon-name' => 'site_logo_icon_json','clear-name' => 'site_logo_clear','media-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($logoMediaId),'media-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($logoPreviewUrl),'icon-json' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($logoIconJson),'allow' => 'images,icons','help' => 'Pick an image OR an icon. Choosing one clears the other.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8704d840cb70e3dce5479facb8ad9f63)): ?>
<?php $attributes = $__attributesOriginal8704d840cb70e3dce5479facb8ad9f63; ?>
<?php unset($__attributesOriginal8704d840cb70e3dce5479facb8ad9f63); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8704d840cb70e3dce5479facb8ad9f63)): ?>
<?php $component = $__componentOriginal8704d840cb70e3dce5479facb8ad9f63; ?>
<?php unset($__componentOriginal8704d840cb70e3dce5479facb8ad9f63); ?>
<?php endif; ?>
                                <p class="mt-1 text-xs text-gray-500">
                                    Selecting a Media image as your logo will <span class="font-semibold">not</span> delete it when removed from Settings.
                                </p>
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

                            <div class="mt-4">
                                <?php if (isset($component)) { $__componentOriginal8704d840cb70e3dce5479facb8ad9f63 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8704d840cb70e3dce5479facb8ad9f63 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.media-icon-picker','data' => ['label' => 'Choose from Media library','mediaName' => 'site_favicon_media_id','iconName' => 'site_favicon_icon_json','clearName' => 'site_favicon_clear','mediaId' => $faviconMediaId,'mediaUrl' => $faviconPreviewUrl,'iconJson' => $faviconIconJson,'allow' => 'images,icons','help' => 'Pick an image OR an icon. Icon favicons are served as /favicon.svg.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.media-icon-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Choose from Media library','media-name' => 'site_favicon_media_id','icon-name' => 'site_favicon_icon_json','clear-name' => 'site_favicon_clear','media-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($faviconMediaId),'media-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($faviconPreviewUrl),'icon-json' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($faviconIconJson),'allow' => 'images,icons','help' => 'Pick an image OR an icon. Icon favicons are served as /favicon.svg.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8704d840cb70e3dce5479facb8ad9f63)): ?>
<?php $attributes = $__attributesOriginal8704d840cb70e3dce5479facb8ad9f63; ?>
<?php unset($__attributesOriginal8704d840cb70e3dce5479facb8ad9f63); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8704d840cb70e3dce5479facb8ad9f63)): ?>
<?php $component = $__componentOriginal8704d840cb70e3dce5479facb8ad9f63; ?>
<?php unset($__componentOriginal8704d840cb70e3dce5479facb8ad9f63); ?>
<?php endif; ?>
                                <p class="mt-1 text-xs text-gray-500">Recommended: ICO or PNG (32×32 / 48×48). Media items are never deleted via Settings.</p>
                            </div>
                        </div>

                        
                        <div class="mt-8 border-t pt-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-900">Login screen logo</div>
                                    <p class="text-xs text-gray-500 mt-1">Shown on the login/register pages. If empty, it falls back to the main logo.</p>
                                </div>
                            </div>

                            <?php
                                $authPreviewUrl = !empty($authLogoMediaUrl) ? $authLogoMediaUrl : null;
                            ?>

                            <div class="mt-4">
                                <?php if (isset($component)) { $__componentOriginal8704d840cb70e3dce5479facb8ad9f63 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8704d840cb70e3dce5479facb8ad9f63 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.admin.media-icon-picker','data' => ['label' => 'Choose from Media library','mediaName' => 'auth_logo_media_id','iconName' => 'auth_logo_icon_json','clearName' => 'auth_logo_clear','mediaId' => $authLogoMediaId,'mediaUrl' => $authPreviewUrl,'iconJson' => $authLogoIconJson,'allow' => 'images,icons','help' => 'If empty, login falls back to the main logo.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin.media-icon-picker'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Choose from Media library','media-name' => 'auth_logo_media_id','icon-name' => 'auth_logo_icon_json','clear-name' => 'auth_logo_clear','media-id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($authLogoMediaId),'media-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($authPreviewUrl),'icon-json' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($authLogoIconJson),'allow' => 'images,icons','help' => 'If empty, login falls back to the main logo.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8704d840cb70e3dce5479facb8ad9f63)): ?>
<?php $attributes = $__attributesOriginal8704d840cb70e3dce5479facb8ad9f63; ?>
<?php unset($__attributesOriginal8704d840cb70e3dce5479facb8ad9f63); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8704d840cb70e3dce5479facb8ad9f63)): ?>
<?php $component = $__componentOriginal8704d840cb70e3dce5479facb8ad9f63; ?>
<?php unset($__componentOriginal8704d840cb70e3dce5479facb8ad9f63); ?>
<?php endif; ?>
                            </div>

                            <div class="mt-6 max-w-xs">
                                <label class="block text-sm font-medium text-gray-700">Login logo size (px)</label>
                                <input type="number"
                                       name="auth_logo_size"
                                       min="24"
                                       max="256"
                                       value="<?php echo e((int) old('auth_logo_size', $authLogoSize ?? 80)); ?>"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                <p class="mt-1 text-xs text-gray-500">Applies to the login/register logo (image or icon).</p>
                                <?php $__errorArgs = ['auth_logo_size'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        
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
                                        <?php echo e($p->title); ?>

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

                    
                    <section class="border-t pt-10" id="notice_settings">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Site notification bar</h3>
                            <p class="text-xs text-gray-500">Optional banner pinned at the very top of every public page</p>
                        </div>

                        <div class="mt-5 max-w-xl">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="notice_enabled" value="1"
                                       <?php echo e(old('notice_enabled', $noticeEnabled ?? false) ? 'checked' : ''); ?>

                                       class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                Enable notification bar
                            </label>
                        </div>

                        <div class="mt-4 max-w-xl grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bar colour</label>
                                <div class="mt-1 flex items-center gap-3">
                                    <input id="notice_bg_colour_picker" type="color"
                                           value="<?php echo e(old('notice_bg_colour', $noticeBgColour ?? '#111827')); ?>"
                                           class="h-10 w-14 rounded-md border border-gray-300 bg-white p-1" />
                                    <input id="notice_bg_colour" type="text" name="notice_bg_colour"
                                           value="<?php echo e(old('notice_bg_colour', $noticeBgColour ?? '#111827')); ?>"
                                           class="block w-full rounded-md border-gray-300 font-mono text-sm"
                                           placeholder="#111827" />
                                </div>
                                <?php $__errorArgs = ['notice_bg_colour'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bar height (px)</label>
                                <input type="number" name="notice_height"
                                       value="<?php echo e((int) old('notice_height', $noticeHeight ?? 44)); ?>"
                                       min="24" max="200"
                                       class="mt-1 block w-full rounded-md border-gray-300"
                                       placeholder="44" />
                                <p class="mt-1 text-xs text-gray-500">Minimum height. The bar can grow if your message wraps onto multiple lines.</p>
                                <?php $__errorArgs = ['notice_height'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mode</label>
                                <select name="notice_mode" id="notice_mode"
                                        class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    <option value="text" <?php echo e(old('notice_mode', $noticeMode ?? 'text') === 'text' ? 'selected' : ''); ?>>Plain text</option>
                                    <option value="html" <?php echo e(old('notice_mode', $noticeMode ?? 'text') === 'html' ? 'selected' : ''); ?>>HTML</option>
                                </select>
                                <?php $__errorArgs = ['notice_mode'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div id="notice_link_fields">
                                <label class="block text-sm font-medium text-gray-700">Optional link (for text mode)</label>
                                <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <input type="text" name="notice_link_text" value="<?php echo e(old('notice_link_text', $noticeLinkText ?? '')); ?>"
                                               class="block w-full rounded-md border-gray-300" placeholder="Link text" />
                                    </div>
                                    <div>
                                        <input type="text" name="notice_link_url" value="<?php echo e(old('notice_link_url', $noticeLinkUrl ?? '')); ?>"
                                               class="block w-full rounded-md border-gray-300" placeholder="https://..." />
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">If you choose HTML mode, you can embed links directly in the HTML.</p>
                                <?php $__errorArgs = ['notice_link_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="mt-4" id="notice_text_wrap">
                            <label class="block text-sm font-medium text-gray-700">Notification text</label>
                            <textarea name="notice_text" rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300"
                                      placeholder="e.g. Scheduled maintenance: 1–2 March 2026"><?php echo e(old('notice_text', $noticeText ?? '')); ?></textarea>
                            <?php $__errorArgs = ['notice_text'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div class="mt-4" id="notice_html_wrap">
                            <label class="block text-sm font-medium text-gray-700">Notification HTML</label>
                            <textarea name="notice_html" rows="6"
                                      class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm"
                                      placeholder="Paste banner HTML here (links, spans, etc)…"><?php echo e(old('notice_html', $noticeHtml ?? '')); ?></textarea>
                            <?php $__errorArgs = ['notice_html'];
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
                            <h3 class="text-sm font-semibold text-gray-900">Maintenance mode</h3>
                            <p class="text-xs text-gray-500">When enabled, all public pages redirect to a selected maintenance page</p>
                        </div>

	                        <div class="mt-5 max-w-xl">
	                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
	                                <input
	                                    type="checkbox"
	                                    name="maintenance_enabled"
	                                    value="1"
	                                    <?php echo e(old('maintenance_enabled', $maintenanceEnabled ?? false) ? 'checked' : ''); ?>

	                                    class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
	                                >
	                                Enable maintenance mode
	                            </label>
	                        </div>

	                        <div class="mt-4 max-w-xl">
	                            <label class="block text-sm font-medium text-gray-700">Maintenance page</label>
	                            <select name="maintenance_page_id"
	                                    class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
	                                <option value="">— Select a published page —</option>
	                                <?php $__currentLoopData = $maintenancePages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
	                                    <option value="<?php echo e($p->id); ?>" <?php echo e((int) old('maintenance_page_id', $maintenancePageId) === (int) $p->id ? 'selected' : ''); ?>>
	                                        <?php echo e($p->title); ?>

	                                    </option>
	                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
	                            </select>
	                            <p class="mt-1 text-xs text-gray-500">Tip: create a page like “Maintenance” and keep it simple (logo, message, contact details).</p>
	                            <?php $__errorArgs = ['maintenance_page_id'];
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

    <script>
        (function () {
            const modeEl = document.getElementById('notice_mode');
            const textWrap = document.getElementById('notice_text_wrap');
            const htmlWrap = document.getElementById('notice_html_wrap');
            const linkFields = document.getElementById('notice_link_fields');

            function refresh() {
                const mode = (modeEl?.value || 'text');
                if (textWrap) textWrap.style.display = (mode === 'text') ? '' : 'none';
                if (htmlWrap) htmlWrap.style.display = (mode === 'html') ? '' : 'none';
                if (linkFields) linkFields.style.opacity = (mode === 'text') ? '1' : '0.5';
            }

            // Notice bar colour picker sync (picker <-> hex input)
            const colourInput = document.getElementById('notice_bg_colour');
            const pickerInput = document.getElementById('notice_bg_colour_picker');

            function normaliseHex(v){
                if(!v) return '';
                v = String(v).trim();
                if(!v.startsWith('#')) v = '#' + v;
                return v;
            }

            function isHex(v){
                return /^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(v);
            }

            function syncFromText(){
                if(!colourInput || !pickerInput) return;
                const v = normaliseHex(colourInput.value);
                if(isHex(v)) pickerInput.value = v;
            }

            function syncFromPicker(){
                if(!colourInput || !pickerInput) return;
                const v = normaliseHex(pickerInput.value);
                if(isHex(v)) colourInput.value = v;
            }

            colourInput?.addEventListener('input', syncFromText);
            pickerInput?.addEventListener('input', syncFromPicker);
            syncFromText();

            modeEl?.addEventListener('change', refresh);
            refresh();
        })();
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/settings/edit.blade.php ENDPATH**/ ?>