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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">AI Agent</h2>
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
                <form method="POST" action="<?php echo e(route('admin.ai-agent.update')); ?>" class="p-6 space-y-10">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>

                    <section>
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Provider</h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Select which AI provider to use for page generation. API keys are stored encrypted in the database.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 max-w-xl">
                            <label class="block text-sm font-medium text-gray-700">Active provider</label>
                            <select id="ai_provider" name="provider" class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                <option value="openai" <?php echo e(old('provider', $provider) === 'openai' ? 'selected' : ''); ?>>OpenAI</option>
                                <option value="gemini" <?php echo e(old('provider', $provider) === 'gemini' ? 'selected' : ''); ?>>Google Gemini</option>
                                <option value="anthropic" <?php echo e(old('provider', $provider) === 'anthropic' ? 'selected' : ''); ?>>Anthropic (Claude) — coming soon</option>
                                <option value="disabled" <?php echo e(old('provider', $provider) === 'disabled' ? 'selected' : ''); ?>>Disabled</option>
                            </select>
                            <?php $__errorArgs = ['provider'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </section>

                    
                    <section id="provider_openai" class="border-t pt-10">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">OpenAI</h3>
                                <p class="text-xs text-gray-500 mt-1">Uses the OpenAI Responses API.</p>
                            </div>
                            <div class="text-xs <?php echo e($openAiHasKey ? 'text-green-700' : 'text-red-700'); ?>">
                                <?php echo e($openAiHasKey ? 'Key detected ✅' : 'No key set ❌'); ?>

                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">API key</label>
                                <input type="password"
                                       name="openai_api_key"
                                       value=""
                                       autocomplete="off"
                                       placeholder="Paste your OpenAI API key (leave blank to keep current)"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                <p class="mt-1 text-xs text-gray-500">
                                    Leave blank to keep the existing key. Stored encrypted in <code>settings</code>.
                                    (You can also use <code>OPENAI_API_KEY</code> in <code>.env</code> as a fallback.)
                                </p>
                                <?php $__errorArgs = ['openai_api_key'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-3">
                                    <input type="checkbox" name="openai_api_key_clear" value="1" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                    Clear stored key
                                </label>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Model</label>

                                
                                <input type="hidden" name="openai_model" id="openai_model" value="<?php echo e(old('openai_model', $openAiModel)); ?>">

                                <select id="openai_model_select" class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    <?php $__currentLoopData = $openAiModelOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($opt['id']); ?>" <?php echo e(old('openAiModelSelect', $openAiModelSelect) === $opt['id'] ? 'selected' : ''); ?>>
                                            <?php echo e($opt['label']); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <option value="custom" <?php echo e(old('openAiModelSelect', $openAiModelSelect) === 'custom' ? 'selected' : ''); ?>>Custom…</option>
                                </select>

                                <div id="openai_model_custom_wrap" class="mt-3" style="display:none;">
                                    <label class="block text-xs font-medium text-gray-600">Custom model</label>
                                    <input type="text"
                                           id="openai_model_custom"
                                           value="<?php echo e(old('openAiModelCustom', $openAiModelCustom)); ?>"
                                           placeholder="e.g. gpt-5.2"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                    <p class="mt-1 text-xs text-gray-500">Only use this if your model isn’t in the list.</p>
                                </div>

                                <?php $__errorArgs = ['openai_model'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700">Timeout (seconds)</label>
                                    <input type="number"
                                           name="openai_timeout"
                                           min="5" max="120"
                                           value="<?php echo e((int) old('openai_timeout', $openAiTimeout)); ?>"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                    <?php $__errorArgs = ['openai_timeout'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    
                    <section id="provider_gemini" class="border-t pt-10" style="display:none;">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Google Gemini</h3>
                                <p class="text-xs text-gray-500 mt-1">Uses the Gemini Developer API (generateContent).</p>
                            </div>
                            <div class="text-xs <?php echo e($geminiHasKey ? 'text-green-700' : 'text-red-700'); ?>">
                                <?php echo e($geminiHasKey ? 'Key stored ✅' : 'No key stored ❌'); ?>

                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">API key</label>
                                <input type="password"
                                       name="gemini_api_key"
                                       value=""
                                       autocomplete="off"
                                       placeholder="Paste your Gemini API key (leave blank to keep current)"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                <?php $__errorArgs = ['gemini_api_key'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-3">
                                    <input type="checkbox" name="gemini_api_key_clear" value="1" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                    Clear stored key
                                </label>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Model</label>

                                <input type="hidden" name="gemini_model" id="gemini_model" value="<?php echo e(old('gemini_model', $geminiModel)); ?>">

                                <select id="gemini_model_select" class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    <?php $__currentLoopData = $geminiModelOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($opt['id']); ?>" <?php echo e(old('geminiModelSelect', $geminiModelSelect) === $opt['id'] ? 'selected' : ''); ?>>
                                            <?php echo e($opt['label']); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <option value="custom" <?php echo e(old('geminiModelSelect', $geminiModelSelect) === 'custom' ? 'selected' : ''); ?>>Custom…</option>
                                </select>

                                <div id="gemini_model_custom_wrap" class="mt-3" style="display:none;">
                                    <label class="block text-xs font-medium text-gray-600">Custom model</label>
                                    <input type="text"
                                           id="gemini_model_custom"
                                           value="<?php echo e(old('geminiModelCustom', $geminiModelCustom)); ?>"
                                           placeholder="e.g. gemini-2.5-flash"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                </div>

                                <?php $__errorArgs = ['gemini_model'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700">Timeout (seconds)</label>
                                    <input type="number"
                                           name="gemini_timeout"
                                           min="5" max="120"
                                           value="<?php echo e((int) old('gemini_timeout', $geminiTimeout)); ?>"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                    <?php $__errorArgs = ['gemini_timeout'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    
                    <section id="provider_anthropic" class="border-t pt-10" style="display:none;">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-semibold text-gray-900">Anthropic (Claude)</h3>
                                <p class="text-xs text-gray-500 mt-1">Saved here for convenience — API wiring is not enabled yet.</p>
                            </div>
                            <div class="text-xs <?php echo e($anthropicHasKey ? 'text-green-700' : 'text-gray-500'); ?>">
                                <?php echo e($anthropicHasKey ? 'Key stored ✅' : 'No key stored'); ?>

                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">API key</label>
                                <input type="password"
                                       name="anthropic_api_key"
                                       value=""
                                       autocomplete="off"
                                       placeholder="Paste your Anthropic API key (optional)"
                                       class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 mt-3">
                                    <input type="checkbox" name="anthropic_api_key_clear" value="1" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500">
                                    Clear stored key
                                </label>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Model</label>
                                <input type="hidden" name="anthropic_model" id="anthropic_model" value="<?php echo e(old('anthropic_model', $anthropicModel)); ?>">

                                <select id="anthropic_model_select" class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500">
                                    <?php $__currentLoopData = $anthropicModelOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($opt['id']); ?>" <?php echo e(old('anthropicModelSelect', $anthropicModelSelect) === $opt['id'] ? 'selected' : ''); ?>>
                                            <?php echo e($opt['label']); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <option value="custom" <?php echo e(old('anthropicModelSelect', $anthropicModelSelect) === 'custom' ? 'selected' : ''); ?>>Custom…</option>
                                </select>

                                <div id="anthropic_model_custom_wrap" class="mt-3" style="display:none;">
                                    <label class="block text-xs font-medium text-gray-600">Custom model</label>
                                    <input type="text"
                                           id="anthropic_model_custom"
                                           value="<?php echo e(old('anthropicModelCustom', $anthropicModelCustom)); ?>"
                                           placeholder="e.g. claude-3-5-sonnet-latest"
                                           class="mt-1 block w-full rounded-md border-gray-300 focus:border-gray-500 focus:ring-gray-500" />
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="pt-6 border-t flex items-center justify-end gap-3">
                        <a href="<?php echo e(route('admin.pages.ai.create')); ?>" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">
                            Go to AI Page
                        </a>
                        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-md text-sm font-medium text-white bg-gray-900 hover:bg-gray-800">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const providerSelect = document.getElementById('ai_provider');
            const sections = {
                openai: document.getElementById('provider_openai'),
                gemini: document.getElementById('provider_gemini'),
                anthropic: document.getElementById('provider_anthropic'),
            };

            function syncProvider() {
                const v = (providerSelect?.value || 'openai').toLowerCase();
                Object.entries(sections).forEach(([key, el]) => {
                    if (!el) return;
                    if (v === 'disabled') {
                        el.style.display = 'none';
                        return;
                    }
                    el.style.display = (key === v) ? 'block' : 'none';
                });
            }

            function wireModel(selectId, hiddenId, customWrapId, customInputId) {
                const sel = document.getElementById(selectId);
                const hidden = document.getElementById(hiddenId);
                const wrap = document.getElementById(customWrapId);
                const custom = document.getElementById(customInputId);

                function sync() {
                    if (!sel || !hidden) return;
                    const val = sel.value;
                    const isCustom = val === 'custom';
                    if (wrap) wrap.style.display = isCustom ? 'block' : 'none';

                    if (isCustom) {
                        const v = (custom?.value || '').trim();
                        if (v !== '') hidden.value = v;
                    } else {
                        hidden.value = val;
                    }
                }

                sel?.addEventListener('change', sync);
                custom?.addEventListener('input', sync);
                sync();
            }

            providerSelect?.addEventListener('change', syncProvider);
            syncProvider();

            wireModel('openai_model_select', 'openai_model', 'openai_model_custom_wrap', 'openai_model_custom');
            wireModel('gemini_model_select', 'gemini_model', 'gemini_model_custom_wrap', 'gemini_model_custom');
            wireModel('anthropic_model_select', 'anthropic_model', 'anthropic_model_custom_wrap', 'anthropic_model_custom');
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/settings/ai-agent.blade.php ENDPATH**/ ?>