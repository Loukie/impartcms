@can('access-admin')
{{--
    Frontend Admin Bar — only rendered for logged-in admin users.
    Uses html { margin-top } (WordPress pattern) to shift page content
    down without affecting fixed-positioned elements.
--}}
<style>
#cms-admin-bar{position:fixed;top:0;left:0;right:0;z-index:9999999;background:#1d2327;color:#c3c4c7;height:32px;display:flex;align-items:center;padding:0;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;font-size:13px;line-height:1;box-shadow:0 1px 3px rgba(0,0,0,.5);}
#cms-admin-bar a{color:#c3c4c7;text-decoration:none;display:inline-flex;align-items:center;gap:5px;height:32px;padding:0 10px;transition:color .1s,background .1s;white-space:nowrap;}
#cms-admin-bar a:hover,#cms-admin-bar a:focus{color:#fff;background:rgba(255,255,255,.08);outline:none;}
#cms-admin-bar .ab-brand{font-weight:600;color:#fff;border-right:1px solid rgba(255,255,255,.12);margin-right:2px;}
#cms-admin-bar .ab-sep{display:inline-block;width:1px;height:14px;background:rgba(255,255,255,.15);margin:0 2px;align-self:center;}
/* Shift page content down — same pattern WordPress uses */
html{margin-top:32px !important;}
/* Push notice bar below the admin bar when both are present */
#site-notice-bar{top:32px !important;}
</style>

<div id="cms-admin-bar" role="navigation" aria-label="CMS Admin Bar">
    <a href="{{ route('dashboard') }}" class="ab-brand" title="ImpartCMS Dashboard">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
        </svg>
        ImpartCMS
    </a>

    <a href="{{ route('dashboard') }}">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
        </svg>
        Dashboard
    </a>

    @if(isset($page) && $page->id)
        <span class="ab-sep" aria-hidden="true"></span>
        <a href="{{ route('admin.pages.edit', $page->id) }}">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit Page
        </a>
    @endif
</div>
@endcan
