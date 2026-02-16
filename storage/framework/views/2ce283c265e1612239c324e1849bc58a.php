<?php
    $isCreate = ($mode ?? 'edit') === 'create';
    $initialFields = is_array($form->fields ?? null) ? $form->fields : [];
    $initialSettings = is_array($form->settings ?? null) ? $form->settings : [];

    $defaultsCsv = '';
    if (!empty($initialSettings['default_recipients']) && is_array($initialSettings['default_recipients'])) {
        $defaultsCsv = implode(', ', array_values($initialSettings['default_recipients']));
    }

    $uiTemplate = (string) data_get($initialSettings, 'ui.template', 'normal');
    $uiColumns = (int) data_get($initialSettings, 'ui.columns', 1);
    $description = (string) ($initialSettings['description'] ?? '');
?>

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
            <div class="flex items-center gap-3">
                <a href="<?php echo e(route('admin.forms.index')); ?>" class="text-sm font-semibold text-gray-700 hover:text-gray-900">← Forms</a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    <?php echo e($isCreate ? 'New Form' : 'Edit Form'); ?>

                </h2>
            </div>

            <?php if(!$isCreate): ?>
                <div class="flex items-center gap-2">
                    <a href="<?php echo e(route('admin.forms.submissions.index', $form)); ?>"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-900 uppercase tracking-widest hover:bg-gray-50">
                        Submissions
                    </a>
                    <form method="POST" action="<?php echo e(route('admin.forms.destroy', $form)); ?>" onsubmit="return confirm('Delete this form? This will also delete its submissions.');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-rose-600 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-rose-700">Delete</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8" x-data="formBuilder()" x-init="init()" x-effect="sync()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <?php if(session('status')): ?>
                <div class="mb-4 p-3 rounded bg-green-50 text-green-800 border border-green-200">
                    <?php echo e(session('status')); ?>

                </div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="mb-4 p-3 rounded bg-red-50 text-red-800 border border-red-200">
                    <ul class="list-disc list-inside text-sm">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($e); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e($isCreate ? route('admin.forms.store') : route('admin.forms.update', $form)); ?>" @submit="sync()">
                <?php echo csrf_field(); ?>
                <?php if(!$isCreate): ?>
                    <?php echo method_field('PUT'); ?>
                <?php endif; ?>

                <input type="hidden" name="fields_json" x-model="fieldsJson">
                <input type="hidden" name="settings_json" x-model="settingsJson">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">Fields</div>
                                        <div class="text-xs text-gray-500">Add fields, set required, and configure select/card options.</div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="px-3 py-2 rounded border text-xs font-semibold hover:bg-gray-50" @click="addField('text')">+ Text</button>
                                        <button type="button" class="px-3 py-2 rounded border text-xs font-semibold hover:bg-gray-50" @click="addField('email')">+ Email</button>
                                        <button type="button" class="px-3 py-2 rounded border text-xs font-semibold hover:bg-gray-50" @click="addField('phone')">+ Phone</button>
                                        <button type="button" class="px-3 py-2 rounded border text-xs font-semibold hover:bg-gray-50" @click="addField('textarea')">+ Textarea</button>
                                        <button type="button" class="px-3 py-2 rounded border text-xs font-semibold hover:bg-gray-50" @click="addField('select')">+ Select</button>
                                        <button type="button" class="px-3 py-2 rounded border text-xs font-semibold hover:bg-gray-50" @click="addField('cards')">+ Cards</button>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-4">
                                    <template x-for="(f, idx) in fields" :key="idx">
                                        <div class="rounded-lg border border-gray-200 p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="flex items-center gap-2">
                                                    <select class="rounded-md border-gray-300 text-sm" x-model="f.type">
                                                        <option value="text">Text</option>
                                                        <option value="email">Email</option>
                                                        <option value="phone">Phone</option>
                                                        <option value="textarea">Textarea</option>
                                                        <option value="select">Select</option>
                                                        <option value="cards">Cards (single)</option>
                                                        <option value="cards_multi">Cards (multi)</option>
                                                        <option value="heading">Heading</option>
                                                        <option value="html">HTML block</option>
                                                    </select>

                                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="checkbox" class="rounded border-gray-300" x-model="f.required" :disabled="f.type==='heading' || f.type==='html'">
                                                        Required
                                                    </label>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <button type="button" class="px-2 py-1 rounded border text-xs font-semibold hover:bg-gray-50" @click="move(idx, -1)" :disabled="idx===0">↑</button>
                                                    <button type="button" class="px-2 py-1 rounded border text-xs font-semibold hover:bg-gray-50" @click="move(idx, 1)" :disabled="idx===fields.length-1">↓</button>
                                                    <button type="button" class="px-2 py-1 rounded border border-rose-200 text-xs font-semibold text-rose-700 hover:bg-rose-50" @click="remove(idx)">Remove</button>
                                                </div>
                                            </div>

                                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-600">Label</label>
                                                    <input type="text" class="mt-1 w-full rounded-md border-gray-300" x-model="f.label" placeholder="e.g. Full name">
                                                </div>

                                                <div x-show="f.type !== 'heading' && f.type !== 'html'">
                                                    <label class="block text-xs font-semibold text-gray-600">Name (key)</label>
                                                    <input type="text" class="mt-1 w-full rounded-md border-gray-300" x-model="f.name" placeholder="e.g. full_name">
                                                    <div class="mt-1 text-xs text-gray-500">Used in payload + CSV headers. Lowercase with underscores recommended.</div>
                                                </div>

                                                <div x-show="f.type === 'text' || f.type === 'email' || f.type === 'phone' || f.type === 'textarea'">
                                                    <label class="block text-xs font-semibold text-gray-600">Placeholder</label>
                                                    <input type="text" class="mt-1 w-full rounded-md border-gray-300" x-model="f.placeholder" placeholder="Optional">
                                                </div>

                                                <div x-show="f.type !== 'html'">
                                                    <label class="block text-xs font-semibold text-gray-600">Help text</label>
                                                    <input type="text" class="mt-1 w-full rounded-md border-gray-300" x-model="f.help" placeholder="Optional">
                                                </div>

                                                <div x-show="f.type === 'html'" class="md:col-span-2">
                                                    <label class="block text-xs font-semibold text-gray-600">HTML</label>
                                                    <textarea rows="4" class="mt-1 w-full rounded-md border-gray-300 font-mono text-xs" x-model="f.html" placeholder="<p>Any HTML…</p>"></textarea>
                                                    <div class="mt-1 text-xs text-gray-500">Rendered as-is (trusted admin input).</div>
                                                </div>
                                            </div>

                                            
                                            <div class="mt-4" x-show="['select','cards','cards_multi'].includes(f.type)">
                                                <div class="flex items-center justify-between">
                                                    <div class="text-xs font-semibold text-gray-600">Options</div>
                                                    <button type="button" class="px-3 py-1.5 rounded border text-xs font-semibold hover:bg-gray-50" @click="addOption(idx)">+ Option</button>
                                                </div>

                                                <div class="mt-2 space-y-2">
                                                    <template x-for="(o, oIdx) in (f.options || [])" :key="oIdx">
                                                        <div class="grid grid-cols-1 md:grid-cols-5 gap-2 items-start rounded border border-gray-200 p-2">
                                                            <div class="md:col-span-2">
                                                                <label class="block text-[11px] font-semibold text-gray-600">Label</label>
                                                                <input type="text" class="mt-1 w-full rounded-md border-gray-300" x-model="o.label">
                                                            </div>
                                                            <div>
                                                                <label class="block text-[11px] font-semibold text-gray-600">Value</label>
                                                                <input type="text" class="mt-1 w-full rounded-md border-gray-300" x-model="o.value">
                                                            </div>
                                                            <div>
                                                                <label class="block text-[11px] font-semibold text-gray-600">Description</label>
                                                                <input type="text" class="mt-1 w-full rounded-md border-gray-300" x-model="o.description" placeholder="Optional">
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <button type="button" class="mt-6 px-2 py-1 rounded border text-xs font-semibold hover:bg-gray-50" @click="pickMedia(idx, oIdx)">Image</button>
                                                                <button type="button" class="mt-6 px-2 py-1 rounded border border-rose-200 text-xs font-semibold text-rose-700 hover:bg-rose-50" @click="removeOption(idx, oIdx)">Remove</button>
                                                            </div>
                                                            <div class="md:col-span-5" x-show="o.media_preview">
                                                                <img :src="o.media_preview" class="mt-2 h-14 w-14 rounded border object-cover" alt="">
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                        </div>
                                    </template>

                                    <div x-show="fields.length===0" class="text-sm text-gray-600">No fields yet — add your first field above.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    
                    <div class="lg:col-span-1">
                        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                            <div class="p-6 space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">Form name</label>
                                    <input type="text" name="name" value="<?php echo e(old('name', $form->name)); ?>" class="mt-1 w-full rounded-md border-gray-300" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">Slug</label>
                                    <input type="text" name="slug" value="<?php echo e(old('slug', $form->slug)); ?>" class="mt-1 w-full rounded-md border-gray-300" placeholder="contact-us" required>
                                    <div class="mt-1 text-xs text-gray-500">Used in shortcode: <code>[form slug="…"]</code></div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input id="is_active" type="checkbox" name="is_active" value="1" class="rounded border-gray-300" <?php if(old('is_active', $form->is_active)): echo 'checked'; endif; ?>>
                                    <label for="is_active" class="text-sm font-semibold text-gray-700">Active</label>
                                </div>

                                <hr>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">Description (optional)</label>
                                    <textarea class="mt-1 w-full rounded-md border-gray-300" rows="3" x-model="settings.description"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">Default recipient emails (CSV)</label>
                                    <input type="text" class="mt-1 w-full rounded-md border-gray-300" x-model="settings.default_recipients" placeholder="you@example.com, admin@example.com">
                                    <div class="mt-1 text-xs text-gray-500">Only used if shortcode “to” isn’t set and no rule matches. Global fallback lives in Forms → Settings.</div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">Template</label>
                                    <select class="mt-1 w-full rounded-md border-gray-300" x-model="settings.ui_template">
                                        <option value="normal">Normal</option>
                                        <option value="booking">Booking</option>
                                        <option value="wizard">Wizard</option>
                                        <option value="cards">Cards</option>
                                        <option value="mixed">Mixed</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-600">Columns</label>
                                    <select class="mt-1 w-full rounded-md border-gray-300" x-model.number="settings.ui_columns">
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                    </select>
                                    <div class="mt-1 text-xs text-gray-500">Used by frontend renderer (basic support now; enhanced layouts later).</div>
                                </div>

                                <hr>

                                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-gray-900 text-white rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-gray-800">
                                    <?php echo e($isCreate ? 'Create' : 'Save changes'); ?>

                                </button>

                                <?php if(!$isCreate): ?>
                                    <div class="text-xs text-gray-600">
                                        Shortcode:
                                        <div class="mt-1">
                                            <code class="px-2 py-1 rounded bg-gray-100">[form slug="<?php echo e($form->slug); ?>" to="you@example.com"]</code>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function formBuilder() {
            return {
                fields: <?php echo json_encode($initialFields, 15, 512) ?>,
                settings: {
                    description: <?php echo json_encode($description, 15, 512) ?>,
                    default_recipients: <?php echo json_encode($defaultsCsv, 15, 512) ?>,
                    ui_template: <?php echo json_encode($uiTemplate, 15, 512) ?>,
                    ui_columns: <?php echo json_encode($uiColumns, 15, 512) ?>,
                },
                fieldsJson: '',
                settingsJson: '',

                init() {
                    // Ensure basic shape
                    if (!Array.isArray(this.fields)) this.fields = [];
                    this.fields = this.fields.map(f => this.normaliseField(f));
                    this.sync();
                },

                normaliseField(f) {
                    const out = Object.assign({
                        type: 'text',
                        name: '',
                        label: '',
                        required: false,
                        placeholder: '',
                        help: '',
                        html: '',
                        options: [],
                    }, (f || {}));

                    if (!Array.isArray(out.options)) out.options = [];
                    out.options = out.options.map(o => Object.assign({ label: '', value: '', description: '', media_id: null, media_preview: null }, o || {}));
                    return out;
                },

                addField(type) {
                    const base = this.normaliseField({ type });
                    if (type === 'heading') {
                        base.label = 'Section heading';
                        base.name = '';
                        base.required = false;
                    }
                    if (type === 'html') {
                        base.label = 'HTML';
                        base.name = '';
                        base.html = '<p>...</p>';
                        base.required = false;
                    }
                    if (['select','cards','cards_multi'].includes(type)) {
                        base.options = [
                            { label: 'Option 1', value: 'option_1', description: '', media_id: null, media_preview: null },
                            { label: 'Option 2', value: 'option_2', description: '', media_id: null, media_preview: null },
                        ];
                    }
                    this.fields.push(base);
                    this.sync();
                },

                remove(idx) {
                    this.fields.splice(idx, 1);
                    this.sync();
                },

                move(idx, dir) {
                    const to = idx + dir;
                    if (to < 0 || to >= this.fields.length) return;
                    const item = this.fields[idx];
                    this.fields.splice(idx, 1);
                    this.fields.splice(to, 0, item);
                    this.sync();
                },

                addOption(fieldIdx) {
                    const f = this.fields[fieldIdx];
                    if (!f) return;
                    if (!Array.isArray(f.options)) f.options = [];
                    f.options.push({ label: 'Option', value: 'option', description: '', media_id: null, media_preview: null });
                    this.sync();
                },

                removeOption(fieldIdx, optIdx) {
                    const f = this.fields[fieldIdx];
                    if (!f || !Array.isArray(f.options)) return;
                    f.options.splice(optIdx, 1);
                    this.sync();
                },

                pickMedia(fieldIdx, optIdx) {
                    const f = this.fields[fieldIdx];
                    if (!f || !Array.isArray(f.options) || !f.options[optIdx]) return;

                    window.ImpartMediaPicker && window.ImpartMediaPicker.open({
                        url: "<?php echo e(route('admin.media.picker')); ?>?tab=images",
                        onSelect: (payload) => {
                            // payload: { kind: 'media', media: {...} }
                            const media = payload && payload.media ? payload.media : null;
                            if (!media) return;
                            f.options[optIdx].media_id = media.id;
                            f.options[optIdx].media_preview = media.url;
                            this.sync();
                        }
                    });
                },

				sync() {
					const toKey = (s) => {
						return String(s || '')
							.toLowerCase()
							.trim()
							.replace(/[^a-z0-9\s_]+/g, '')
							.replace(/\s+/g, '_')
							.replace(/_+/g, '_')
							.replace(/^_+|_+$/g, '');
					};

					const settings = {
						description: this.settings.description || '',
						default_recipients: this.settings.default_recipients || '',
						ui: {
							template: this.settings.ui_template || 'normal',
							columns: Number(this.settings.ui_columns || 1),
						}
					};

					const fields = (this.fields || []).map((f, idx) => {
						const out = {
							type: f.type || 'text',
							label: f.label || '',
						};

						if (f.type !== 'heading' && f.type !== 'html') {
							let key = (f.name || '').trim();

							// ✅ auto-generate key from label if missing
							if (!key) {
								key = toKey(out.label) || `field_${idx + 1}`;
							}

							out.name = key;
							if (f.required) out.required = true;
						}

						if (f.placeholder) out.placeholder = f.placeholder;
						if (f.help) out.help = f.help;
						if (f.type === 'html') out.html = f.html || '';

						if (['select','cards','cards_multi'].includes(f.type)) {
							out.options = (Array.isArray(f.options) ? f.options : []).map(o => {
								const oo = {
									label: o.label || o.value || '',
									value: o.value || o.label || '',
								};
								if (o.description) oo.description = o.description;
								if (o.media_id) oo.media_id = Number(o.media_id);
								return oo;
							}).filter(o => (o.label || o.value));
						}

						return out;
					});

					this.fieldsJson = JSON.stringify(fields, null, 2);
					this.settingsJson = JSON.stringify(settings, null, 2);
				}

            }
        }
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
<?php /**PATH C:\laragon\www\2kocms\resources\views/admin/forms/edit.blade.php ENDPATH**/ ?>