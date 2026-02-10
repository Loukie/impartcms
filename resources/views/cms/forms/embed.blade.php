<form method="POST" action="{{ route('forms.submit', $form) }}" style="margin:16px 0; padding:16px; border:1px solid #ddd;">
    @csrf
    <input type="hidden" name="_page_id" value="{{ $page?->id }}">
    <input type="hidden" name="_override_to" value="{{ $overrideTo }}">

    <h3 style="margin:0 0 12px 0;">{{ $form->name }}</h3>

    @foreach(($form->fields ?? []) as $field)
        @php
            $name = $field['name'] ?? null;
            $label = $field['label'] ?? $name;
            $type = $field['type'] ?? 'text';
            $required = !empty($field['required']);
        @endphp

        @if($name)
            <div style="margin-bottom:12px;">
                <label style="display:block;font-weight:600;margin-bottom:6px;">
                    {{ $label }} @if($required) * @endif
                </label>

                @if($type === 'textarea')
                    <textarea name="{{ $name }}" rows="4" style="width:100%;padding:8px;">{{ old($name) }}</textarea>
                @else
                    <input type="{{ $type === 'email' ? 'email' : 'text' }}" name="{{ $name }}" value="{{ old($name) }}" style="width:100%;padding:8px;">
                @endif

                @error($name)<div style="color:#b00020;">{{ $message }}</div>@enderror
            </div>
        @endif
    @endforeach

    @if(session('status'))
        <div style="margin:10px 0; padding:10px; background:#e8fff1;">{{ session('status') }}</div>
    @endif

    <button type="submit" style="padding:10px 14px; background:#111; color:#fff; border:none;">Send</button>
</form>
