# AI Visual Audit

This CMS includes a screenshot-based redesign tool (Gemini vision) and a global Admin AI popup.

## Admin AI popup (page assist)

Available on every admin page via the floating **AI Assistant** button.

- Select a target page (auto-selects when you are editing a page)
- Enter an instruction
- Choose **Tweak** or **Rewrite**
- Default save mode is **Draft** (safe)

Endpoints:

- `GET /{admin}/ai/pages/search?q=`
- `POST /{admin}/ai/page-assist`

## Visual Audit (screenshots + redesign)

Admin → **AI Visual Audit**

This feature:

1) Captures a screenshot of a selected page (via a temporary signed URL so drafts work)
2) Captures a screenshot of a reference site URL
3) Sends both screenshots to Gemini (vision) to produce redesigned HTML
4) Saves the result as **Draft** and clears homepage if needed

### One-time setup

No Node required ✅  
This uses a locally installed **Chrome/Chromium/Edge** in headless mode.

- Make sure **Google Chrome** or **Microsoft Edge** is installed on the machine running the CMS.
- Optional: if your browser is in a non-standard location, set:

```env
AI_SCREENSHOT_BIN=C:\\Path\\To\\chrome.exe
```

### Files

- Screenshot runner: Chrome/Edge headless CLI (invoked from PHP)
- Signed preview route: `/_ai_preview/pages/{pagePreview}` (signed)
