<div class="space-y-4">
    <div>
        <label class="block font-medium">Title</label>
        <input name="title" class="w-full border rounded p-2" value="{{ old('title', $page->title ?? '') }}">
        @error('title')<div class="text-red-600">{{ $message }}</div>@enderror
    </div>

    <div>
        <label class="block font-medium">Slug (e.g. about-us or services/web)</label>
        <input name="slug" class="w-full border rounded p-2" value="{{ old('slug', $page->slug ?? '') }}">
        @error('slug')<div class="text-red-600">{{ $message }}</div>@enderror
    </div>

    <div>
        <label class="block font-medium">Body (supports shortcode: [form slug=&quot;contact&quot;])</label>
        <textarea name="body" rows="10" class="w-full border rounded p-2">{{ old('body', $page->body ?? '') }}</textarea>
        @error('body')<div class="text-red-600">{{ $message }}</div>@enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block font-medium">Status</label>
            <select name="status" class="w-full border rounded p-2">
                @php($val = old('status', $page->status ?? 'draft'))
                <option value="draft" @selected($val==='draft')>Draft</option>
                <option value="published" @selected($val==='published')>Published</option>
            </select>
        </div>
        <div>
            <label class="block font-medium">Template</label>
            <input name="template" class="w-full border rounded p-2" value="{{ old('template', $page->template ?? 'default') }}">
        </div>
    </div>

    <div>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_homepage" value="1" @checked(old('is_homepage', $page->is_homepage ?? false))>
            <span>Set as homepage</span>
        </label>
    </div>

    <hr>

    <h3 class="font-semibold text-lg">SEO</h3>

    <div>
        <label class="block font-medium">Meta Title</label>
        <input name="meta_title" class="w-full border rounded p-2" value="{{ old('meta_title', $page->seo->meta_title ?? '') }}">
    </div>

    <div>
        <label class="block font-medium">Meta Description</label>
        <textarea name="meta_description" rows="3" class="w-full border rounded p-2">{{ old('meta_description', $page->seo->meta_description ?? '') }}</textarea>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block font-medium">Canonical URL</label>
            <input name="canonical_url" class="w-full border rounded p-2" value="{{ old('canonical_url', $page->seo->canonical_url ?? '') }}">
        </div>
        <div>
            <label class="block font-medium">Robots (e.g. index,follow)</label>
            <input name="robots" class="w-full border rounded p-2" value="{{ old('robots', $page->seo->robots ?? '') }}">
        </div>
    </div>
</div>
