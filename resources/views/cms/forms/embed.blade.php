@php
    /**
     * Form embed renderer
     * Supports:
     * - text, email, textarea, phone
     * - select
     * - cards (single) + cards_multi
     * Layout:
     * - settings.layout blocks: row + page_break
     */

    $rawFields = $form->fields ?? [];
    $settings = is_array($form->settings ?? null) ? $form->settings : [];
    $layout = $settings['layout'] ?? null;
    $pricing = is_array($settings['pricing'] ?? null) ? $settings['pricing'] : [];

    // Normalise fields to map [id => field]
    $fields = [];
    if (is_array($rawFields)) {
        // detect list vs map
        $isAssoc = count($rawFields) === 0 ? true : (array_keys($rawFields) !== range(0, count($rawFields) - 1));
        if ($isAssoc) {
            $fields = $rawFields;
        } else {
            foreach ($rawFields as $idx => $f) {
                if (!is_array($f)) continue;
                $id = $f['id'] ?? ('f_' . ($idx + 1));
                $fields[$id] = array_merge(['id' => $id], $f);
            }
        }
    }

    // Normalise layout
    if (!is_array($layout) || count($layout) === 0) {
        $layout = [[
            'type' => 'row',
            'id' => 'r_default',
            'columns' => 1,
            'cols' => [array_keys($fields)],
        ]];
    }

    // Split into steps by page_break
    $steps = [[]];
    foreach ($layout as $block) {
        if (($block['type'] ?? '') === 'page_break') {
            $steps[] = [];
            continue;
        }
        $steps[count($steps) - 1][] = $block;
    }
    $hasSteps = count($steps) > 1;

    // Helper: extract scalar field id from mixed shapes (string/int/array with id)
    $fbFieldId = function ($v) {
        if (is_string($v) || is_int($v)) return (string) $v;
        if (is_array($v)) {
            if (isset($v['id'])) return (string) $v['id'];
            if (isset($v['field_id'])) return (string) $v['field_id'];
            if (isset($v['fieldId'])) return (string) $v['fieldId'];
            if (array_key_exists(0, $v) && (is_string($v[0]) || is_int($v[0]))) return (string) $v[0];
        }
        return null;
    };

    // Helper: normalise column payload to a simple list of field ids
    $fbColFields = function ($col) {
        if (is_array($col) && isset($col['fields']) && is_array($col['fields'])) return $col['fields'];
        return is_array($col) ? $col : [];
    };

    $formId = 'impart_form_' . $form->id . '_' . substr(md5($form->slug), 0, 6);

    // NOTE: must be a closure (not a named function) to avoid "Cannot redeclare" when multiple forms render on one page.
    $renderIconOrImage = function ($opt) {
        $html = '';
        if (!empty($opt['media_url'])) {
            $url = e($opt['media_url']);
            $html .= '<img src="' . $url . '" alt="" style="width:100%;height:100%;object-fit:cover" />';
            return $html;
        }
        if (!empty($opt['icon']) && is_array($opt['icon'])) {
            $kind = strtolower((string)($opt['icon']['kind'] ?? ''));
            $size = (int)($opt['icon']['size'] ?? 24);
            $colour = e((string)($opt['icon']['colour'] ?? '#111827'));
            if ($kind === 'fa') {
                // Prefer inline SVG when available (portable + no FA font/CSS dependency)
                $svg = isset($opt['icon']['svg']) && is_string($opt['icon']['svg']) ? trim($opt['icon']['svg']) : '';
                if ($svg !== '' && str_starts_with($svg, '<svg')) {
                    // Strip comments + any script tags defensively
                    $svg = preg_replace('/<!--([\s\S]*?)-->/', '', $svg);
                    $svg = preg_replace('/<script[^>]*>[\s\S]*?<\/script>/i', '', $svg);

                    // Remove width/height so our sizing wins
                    $svg = preg_replace('/\s(width|height)="[^"]*"/i', '', $svg);

                    $html .= '<span style="display:inline-block;width:' . $size . 'px;height:' . $size . 'px;color:' . $colour . ';line-height:1;vertical-align:-0.125em">' . $svg . '</span>';
                    return $html;
                }

                $cls = e((string)($opt['icon']['value'] ?? ''));
                $html .= '<i class="' . $cls . '" style="font-size:' . $size . 'px;color:' . $colour . ';line-height:1"></i>';
                return $html;
            }
            if ($kind === 'lucide') {
                $name = e((string)($opt['icon']['value'] ?? ''));
                $html .= '<i data-lucide="' . $name . '" style="width:' . $size . 'px;height:' . $size . 'px;color:' . $colour . ';display:inline-block"></i>';
                return $html;
            }
        }
        return $html;
    };
@endphp

<style>
    .impart-form { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; }
    .impart-form .impart-field { margin-bottom: 12px; }
    .impart-form label { display:block; font-weight:600; margin-bottom:6px; }
    .impart-form input, .impart-form textarea, .impart-form select {
        width:100%; padding:10px 12px; border:1px solid #e5e7eb; border-radius:10px; background:#fff;
    }
    .impart-form input:focus, .impart-form textarea:focus, .impart-form select:focus { outline:none; border-color:#111827; box-shadow:0 0 0 3px rgba(17,24,39,0.08); }

    .impart-form .grid { display:grid; gap:12px; }
    .impart-form .grid-1 { grid-template-columns: 1fr; }
    .impart-form .grid-2 { grid-template-columns: repeat(2, 1fr); }
    .impart-form .grid-3 { grid-template-columns: repeat(3, 1fr); }
    .impart-form .grid-4 { grid-template-columns: repeat(4, 1fr); }

    .impart-form .cards { display:grid; gap:12px; grid-template-columns: repeat(2, minmax(0,1fr)); }
    @media (max-width: 640px) { .impart-form .cards { grid-template-columns: 1fr; } }

    .impart-form .card {
        border:1px solid #e5e7eb; border-radius:14px; background:#fff; padding:12px;
        display:flex; align-items:center; gap:12px; cursor:pointer;
        transition: border-color 120ms ease, box-shadow 120ms ease;
    }
    .impart-form .card:hover { border-color:#cbd5e1; }
    .impart-form .card.is-active { border-color:#111827; box-shadow:0 0 0 3px rgba(17,24,39,0.08); }

    .impart-form .card-media {
        width:52px; height:52px; border-radius:12px; border:1px solid #e5e7eb; background:#f8fafc;
        display:flex; align-items:center; justify-content:center; overflow:hidden;
        flex:none;
    }
    .impart-form .card-title { font-weight:700; }
    .impart-form .muted { color:#64748b; font-size:12px; }

    .impart-form .wizard-nav { display:flex; justify-content:space-between; gap:10px; margin-top:16px; }
    .impart-form .btn {
        padding:10px 14px; border-radius:12px; border:1px solid #e5e7eb; background:#fff; font-weight:700; cursor:pointer;
    }
    .impart-form .btn-primary { background:#111827; color:#fff; border-color:#111827; }
    .impart-form .btn[disabled] { opacity:.5; cursor:not-allowed; }

    .impart-form .honeypot { position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden; }

    .impart-form .pricebox {
        margin: 12px 0 14px;
        padding: 12px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        background: #f8fafc;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap: 10px;
    }
    .impart-form .pricebox .title { font-weight:800; }
    .impart-form .pricebox .amount { font-weight:900; font-size: 18px; }
</style>

<div class="impart-form" id="{{ $formId }}">
    {{--
        IMPORTANT:
        Public submit route uses {form:slug}. Passing the model directly would use its route key (id by default),
        which would generate /forms/{id}/submit and then fail binding (slug expected).
        Always pass the slug explicitly.
    --}}
    <form method="POST" action="{{ route('forms.submit', ['form' => $form->slug]) }}">
        @csrf

        @php
        $toValue = $overrideTo ?? ($to ?? null);
        $ccValue = $overrideCc ?? ($cc ?? null);
        $bccValue = $overrideBcc ?? ($bcc ?? null);
        @endphp
        @if(!empty($toValue))
            <input type="hidden" name="_impart_to" value="{{ $toValue }}">
        @endif

        @if(!empty($ccValue))
            <input type="hidden" name="_impart_cc" value="{{ $ccValue }}">
        @endif

        @if(!empty($bccValue))
            <input type="hidden" name="_impart_bcc" value="{{ $bccValue }}">
        @endif

        {{-- Honeypot (spam) --}}
        <div class="honeypot" aria-hidden="true">
            <label>Leave this field empty</label>
            <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        @if($hasSteps)
            <div class="muted" style="margin-bottom:10px" data-impart-step-indicator></div>
        @endif

        @if((bool)($pricing['enabled'] ?? false))
            <div class="pricebox" data-impart-pricebox>
                <div>
                    <div class="title">Total</div>
                    <div class="muted" data-impart-price-note></div>
                </div>
                <div class="amount" data-impart-price>R0</div>
            </div>
            <input type="hidden" name="_impart_price_zar" value="0" data-impart-price-hidden>
        @endif

        @foreach($steps as $si => $blocks)
            <div data-impart-step style="{{ $si === 0 ? '' : 'display:none' }}">
                @foreach($blocks as $block)
                    @php
                        $cols = max(1, min(4, (int)($block['columns'] ?? 1)));
                        $gridClass = 'grid-' . $cols;
                        $colsArr = $block['cols'] ?? [];
                    @endphp

                    <div class="grid {{ $gridClass }}" style="margin-bottom:14px">
                        @for($ci=0; $ci<$cols; $ci++)
                            <div>
                                @php
                                    $fieldIds = $fbColFields($colsArr[$ci] ?? []);
                                @endphp
                                @foreach($fieldIds as $fidRaw)
                                    @php
                                        $fid = $fbFieldId($fidRaw);
                                        if (!$fid) { $field = null; }
                                        else {
                                            $field = (isset($fields[$fid]) && is_array($fields[$fid])) ? $fields[$fid] : null;

                                            // Fallback: if layout stored numeric indexes but fields were saved as a list
                                            if (!$field && is_numeric($fid) && is_array($rawFields) && isset($rawFields[(int)$fid]) && is_array($rawFields[(int)$fid])) {
                                                $field = $rawFields[(int)$fid];
                                            }
                                        }
                                    @endphp
                                    @if(!$field) @continue @endif

                                    @php
                                        $type = strtolower((string)($field['type'] ?? 'text'));
                                        $name = (string)($field['name'] ?? $fid);
                                        $label = (string)($field['label'] ?? $name);
                                        $required = (bool)($field['required'] ?? false);
                                        $placeholder = (string)($field['placeholder'] ?? '');
                                        $options = is_array($field['options'] ?? null) ? $field['options'] : [];
                                    @endphp

                                    <div class="impart-field">
                                        <label>
                                            {{ $label }}
                                            @if($required)
                                                <span class="muted">*</span>
                                            @endif
                                        </label>

                                        @if(in_array($type, ['text','email']))
                                            <input type="{{ $type }}" name="{{ $name }}" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}>

                                        @elseif($type === 'textarea')
                                            <textarea name="{{ $name }}" rows="4" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}></textarea>

                                        @elseif($type === 'phone')
                                            <div style="display:flex; gap:10px; align-items:stretch">
                                                <select name="{{ $name }}_country" data-impart-phone-country style="width:72px; font-size:22px; padding:8px 10px;">
                                                    <option value="ZA" data-dial="+27">🇿🇦</option>
                                                    <option value="US" data-dial="+1">🇺🇸</option>
                                                    <option value="GB" data-dial="+44">🇬🇧</option>
                                                    <option value="AU" data-dial="+61">🇦🇺</option>
                                                    <option value="CA" data-dial="+1">🇨🇦</option>
                                                    <option value="NZ" data-dial="+64">🇳🇿</option>
                                                    <option value="DE" data-dial="+49">🇩🇪</option>
                                                    <option value="FR" data-dial="+33">🇫🇷</option>
                                                    <option value="NL" data-dial="+31">🇳🇱</option>
                                                    <option value="IE" data-dial="+353">🇮🇪</option>
                                                    <option value="ES" data-dial="+34">🇪🇸</option>
                                                    <option value="IT" data-dial="+39">🇮🇹</option>
                                                    <option value="PT" data-dial="+351">🇵🇹</option>
                                                    <option value="BE" data-dial="+32">🇧🇪</option>
                                                    <option value="CH" data-dial="+41">🇨🇭</option>
                                                    <option value="AT" data-dial="+43">🇦🇹</option>
                                                    <option value="SE" data-dial="+46">🇸🇪</option>
                                                    <option value="NO" data-dial="+47">🇳🇴</option>
                                                    <option value="DK" data-dial="+45">🇩🇰</option>
                                                    <option value="FI" data-dial="+358">🇫🇮</option>
                                                    <option value="PL" data-dial="+48">🇵🇱</option>
                                                    <option value="CZ" data-dial="+420">🇨🇿</option>
                                                    <option value="HU" data-dial="+36">🇭🇺</option>
                                                    <option value="GR" data-dial="+30">🇬🇷</option>
                                                    <option value="TR" data-dial="+90">🇹🇷</option>
                                                    <option value="AE" data-dial="+971">🇦🇪</option>
                                                    <option value="SA" data-dial="+966">🇸🇦</option>
                                                    <option value="IN" data-dial="+91">🇮🇳</option>
                                                    <option value="SG" data-dial="+65">🇸🇬</option>
                                                    <option value="MY" data-dial="+60">🇲🇾</option>
                                                    <option value="TH" data-dial="+66">🇹🇭</option>
                                                    <option value="VN" data-dial="+84">🇻🇳</option>
                                                    <option value="PH" data-dial="+63">🇵🇭</option>
                                                    <option value="ID" data-dial="+62">🇮🇩</option>
                                                    <option value="JP" data-dial="+81">🇯🇵</option>
                                                    <option value="KR" data-dial="+82">🇰🇷</option>
                                                    <option value="CN" data-dial="+86">🇨🇳</option>
                                                    <option value="HK" data-dial="+852">🇭🇰</option>
                                                    <option value="BR" data-dial="+55">🇧🇷</option>
                                                    <option value="MX" data-dial="+52">🇲🇽</option>
                                                    <option value="AR" data-dial="+54">🇦🇷</option>
                                                    <option value="CO" data-dial="+57">🇨🇴</option>
                                                    <option value="CL" data-dial="+56">🇨🇱</option>
                                                    <option value="PE" data-dial="+51">🇵🇪</option>
                                                    <option value="NG" data-dial="+234">🇳🇬</option>
                                                    <option value="KE" data-dial="+254">🇰🇪</option>
                                                    <option value="GH" data-dial="+233">🇬🇭</option>
                                                    <option value="EG" data-dial="+20">🇪🇬</option>
                                                </select>
                                                <input type="text" value="" readonly data-impart-phone-dial style="max-width:92px; text-align:center; font-weight:700; background:#f8fafc">
                                                <input type="tel" name="{{ $name }}" placeholder="{{ $placeholder ?: 'Phone number' }}" {{ $required ? 'required' : '' }}>
                                            </div>

                                        @elseif($type === 'select')
                                            <select name="{{ $name }}" {{ $required ? 'required' : '' }}>
                                                <option value="">-- Select --</option>
                                                @foreach($options as $opt)
                                                    @php
                                                        $ol = (string)($opt['label'] ?? '');
                                                        $ov = (string)($opt['value'] ?? '');
                                                    @endphp
                                                    <option value="{{ $ov }}">{{ $ol }}</option>
                                                @endforeach
                                            </select>

                                        @elseif(in_array($type, ['cards','cards_multi']))
                                            @php $isMulti = $type === 'cards_multi'; @endphp
                                            <div class="cards" data-impart-cards>
                                                @foreach($options as $oi => $opt)
                                                    @php
                                                        $ol = (string)($opt['label'] ?? ('Option ' . ($oi+1)));
                                                        $ov = (string)($opt['value'] ?? '');
                                                        $media = $renderIconOrImage($opt);
                                                        $id = $formId . '_' . $name . '_' . $oi;
                                                    @endphp

                                                    <label class="card" for="{{ $id }}" data-impart-card>
                                                        <span class="card-media">{!! $media !!}</span>
                                                        <span style="flex:1">
                                                            <span class="card-title">{{ $ol }}</span>
                                                        </span>
                                                        <input id="{{ $id }}" type="{{ $isMulti ? 'checkbox' : 'radio' }}" name="{{ $isMulti ? $name.'[]' : $name }}" value="{{ $ov }}" style="width:auto" {{ $required && !$isMulti ? 'required' : '' }}>
                                                    </label>
                                                @endforeach
                                            </div>

                                        @else
                                            <input type="text" name="{{ $name }}" placeholder="{{ $placeholder }}" {{ $required ? 'required' : '' }}>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endfor
                    </div>
                @endforeach

                @if($hasSteps)
                    <div class="wizard-nav">
                        <button type="button" class="btn" data-impart-prev {{ $si === 0 ? 'disabled' : '' }}>Back</button>
                        @if($si < count($steps) - 1)
                            <button type="button" class="btn btn-primary" data-impart-next>Next</button>
                        @else
                            <button type="submit" class="btn btn-primary">Submit</button>
                        @endif
                    </div>
                @else
                    <div style="margin-top:16px">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                @endif
            </div>
        @endforeach
    </form>
</div>

<script>
(function(){
    const root = document.getElementById(@json($formId));
    if (!root) return;

    const pricing = @json($pricing);

    // Card active styling
    root.querySelectorAll('[data-impart-cards]').forEach(group => {
        const sync = () => {
            group.querySelectorAll('[data-impart-card]').forEach(card => {
                const input = card.querySelector('input');
                const active = input && input.checked;
                card.classList.toggle('is-active', !!active);
            });
        };
        group.addEventListener('change', sync);
        sync();
    });

    // Phone country default (best-effort)
    // Priority:
    // 1) previously chosen (localStorage)
    // 2) timezone heuristic
    // 3) browser language region
    const saved = (() => {
        try { return localStorage.getItem('impart_phone_country') || ''; } catch (e) { return ''; }
    })();

    const tz = (() => {
        try { return (Intl.DateTimeFormat().resolvedOptions().timeZone || '').toString(); }
        catch (e) { return ''; }
    })();

    const lang = (navigator.language || '').toUpperCase();
    const langGuess = lang.includes('-') ? lang.split('-')[1] : '';

    const tzGuess = (() => {
        const z = (tz || '').toLowerCase();
        if (z.startsWith('africa/')) return 'ZA';
        if (z.startsWith('europe/london')) return 'GB';
        if (z.startsWith('europe/')) return 'DE';
        if (z.startsWith('america/')) return 'US';
        if (z.startsWith('australia/')) return 'AU';
        if (z.startsWith('pacific/auckland')) return 'NZ';
        return '';
    })();

    const guess = (saved || tzGuess || langGuess || '').toUpperCase();
    root.querySelectorAll('[data-impart-phone-country]').forEach(sel => {
        // Set initial selection (best-effort)
        if (guess) {
            const opt = Array.from(sel.options).find(o => (o.value || '').toUpperCase() === guess);
            if (opt) sel.value = opt.value;
        }

        const dialEl = sel.parentElement?.querySelector?.('[data-impart-phone-dial]') || null;
        const syncDial = () => {
            const opt = sel.options[sel.selectedIndex];
            const dial = (opt?.dataset?.dial || '').toString();
            if (dialEl) dialEl.value = dial;
        };

        syncDial();

        sel.addEventListener('change', () => {
            syncDial();
            try { localStorage.setItem('impart_phone_country', sel.value || ''); } catch (e) {}
        });
    });

    // Pricing (optional)
    const priceBox = root.querySelector('[data-impart-pricebox]');
    const priceEl = root.querySelector('[data-impart-price]');
    const priceNoteEl = root.querySelector('[data-impart-price-note]');
    const priceHidden = root.querySelector('[data-impart-price-hidden]');

    const fmtZar = (n) => {
        const num = Number(n || 0);
        try {
            return new Intl.NumberFormat('en-ZA', { style: 'currency', currency: 'ZAR', maximumFractionDigits: 0 }).format(num);
        } catch (e) {
            return 'R' + Math.round(num).toString();
        }
    };

    function getFieldValue(name) {
        const els = Array.from(root.querySelectorAll(`[name="${CSS.escape(name)}"],[name="${CSS.escape(name)}[]"]`));
        if (!els.length) return null;
        const anyArray = els.some(el => (el.getAttribute('name') || '').endsWith('[]'));
        if (anyArray) {
            return els.filter(el => el.checked).map(el => el.value);
        }
        const el = els[0];
        if (el.type === 'radio') {
            const checked = els.find(x => x.checked);
            return checked ? checked.value : '';
        }
        return el.value;
    }

    function ruleMatches(rule) {
        const conds = Array.isArray(rule?.conditions) ? rule.conditions : [];
        for (const c of conds) {
            const field = (c.field || '').toString();
            const op = (c.op || 'is').toString();
            const val = (c.value ?? '').toString();
            if (!field) return false;

            const current = getFieldValue(field);
            if (Array.isArray(current)) {
                const has = current.includes(val);
                if (op === 'is') { if (!has) return false; }
                if (op === 'is_not') { if (has) return false; }
            } else {
                const cur = (current ?? '').toString();
                if (op === 'is') { if (cur !== val) return false; }
                if (op === 'is_not') { if (cur === val) return false; }
            }
        }
        return true;
    }

    function computePriceZar() {
        if (!pricing || !pricing.enabled) return 0;
        const options = Array.isArray(pricing.options) ? pricing.options : [];
        const byId = new Map(options.map(o => [String(o.id), o]));
        const rules = Array.isArray(pricing.rules) ? pricing.rules : [];

        for (const r of rules) {
            if (!r) continue;
            if (!ruleMatches(r)) continue;
            const pid = String(r.price_option_id || '');
            const opt = byId.get(pid);
            if (!opt) continue;
            return Number(opt.amount_zar || 0);
        }

        // Default
        const defId = String(pricing.default_price_option_id || '');
        const defOpt = byId.get(defId);
        return defOpt ? Number(defOpt.amount_zar || 0) : 0;
    }

    function refreshPricing() {
        if (!priceBox) return;
        const amount = computePriceZar();
        if (priceEl) priceEl.textContent = fmtZar(amount);
        if (priceHidden) priceHidden.value = String(Math.round(amount));
        if (priceNoteEl) {
            priceNoteEl.textContent = (pricing.note || '').toString();
        }
    }

    if (priceBox) {
        root.addEventListener('change', refreshPricing);
        root.addEventListener('input', refreshPricing);
        refreshPricing();
    }

    // Wizard
    const steps = Array.from(root.querySelectorAll('[data-impart-step]'));
    if (steps.length <= 1) {
        // lucide pass if available
        if (window.ImpartLucide) window.ImpartLucide.render(root);
        return;
    }

    let i = 0;
    const indicator = root.querySelector('[data-impart-step-indicator]');

    const show = (idx) => {
        i = Math.max(0, Math.min(steps.length - 1, idx));
        steps.forEach((s, si) => s.style.display = (si === i ? '' : 'none'));
        if (indicator) indicator.textContent = `Step ${i + 1} of ${steps.length}`;
        if (window.ImpartLucide) window.ImpartLucide.render(steps[i]);
    };

    root.addEventListener('click', (e) => {
        const next = e.target.closest('[data-impart-next]');
        const prev = e.target.closest('[data-impart-prev]');
        if (next) {
            // basic validation: check required inputs in current step
            const current = steps[i];
            const required = Array.from(current.querySelectorAll('[required]'));
            for (const input of required) {
                if (!input.value) {
                    input.focus();
                    return;
                }
            }
            show(i + 1);
        }
        if (prev) show(i - 1);
    });

    show(0);
})();
</script>
