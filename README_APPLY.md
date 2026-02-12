# ImpartCMS Patch — WP-style Media Picker + Favicon + Font/Icon Upload Support

## What this patch does ✅
- Adds a WordPress-style Media Library picker modal (global, reusable).
- Updates Settings → Logo selection to use the picker (no more dropdown).
- Adds Settings → Favicon (pick from Media OR upload; removing never deletes Media items).
- Extends Media uploads to support: SVG, ICO, WOFF/WOFF2/TTF/OTF/EOT (and common images + PDF).
- Adds Media picker view with:
  - Bigger modal (95vw x 92vh)
  - Library / Upload / Cancel on the left
  - Folder + Search + Apply/Reset on the right
  - Tabs: All / Images / Icons / Fonts / Docs
  - Click to select + bottom “Select” button (like WP)

## How to apply (copy-paste override) 🧩
1) Extract this zip.
2) Copy the folders into your repo root:
   - app/
   - resources/
3) Overwrite when prompted.

## After applying 🚀
- Run:
  - npm run build
- If you run Vite dev server locally instead:
  - npm run dev

## Notes
- The picker is opened via `window.ImpartMediaPicker.open({ url, onSelect })`.
- For now, the picker loads at: `admin.media.index?picker=1`
  (so no extra routes required).

