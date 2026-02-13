# ImpartCMS Shortcodes

ImpartCMS supports a small, WordPress-style shortcode system inside Page **Body** content.

## 1) Forms

Embed a form by its slug:

```text
[form slug="contact"]
```

Optional: override notification recipients (comma-separated):

```text
[form slug="contact" to="hello@example.com,ops@example.com"]
```

## 2) Icons

Icons are designed for your future theme builder, but you can already embed them manually in any Page **Body**.

### Option A: Simple attributes (recommended)

Font Awesome (Free):

```text
[icon kind="fa" value="fa-solid fa-house" size="24" colour="#111827"]
```

Lucide:

```text
[icon kind="lucide" value="home" size="24" colour="#111827"]
```

### Option B: JSON payload (compact + flexible)

Use **single quotes** around `data=...` so the JSON double-quotes don’t break the shortcode:

```text
[icon data='{"kind":"fa","value":"fa-solid fa-house","size":24,"colour":"#111827"}']
```

### Supported icon fields

- `kind`: `fa` or `lucide`
- `value`: FA class string (e.g. `fa-solid fa-house`) OR Lucide icon name (e.g. `home`)
- `size`: integer pixels (8–256)
- `colour`: hex colour (`#RGB`, `#RRGGBB`, `#RRGGBBAA`)

### Notes

- FA icons require the Font Awesome Free CSS to be present.
- Lucide icons render from `<i data-lucide="..."></i>` placeholders and need the Lucide JS initialiser to run.
