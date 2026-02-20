/**
 * Generate a Font Awesome icon index for the ImpartCMS icon library.
 *
 * Source of truth: SVG files shipped with @fortawesome/fontawesome-free.
 * This avoids the metadata format churn between Font Awesome major versions.
 *
 * Output: resources/js/admin/fa-icons.json
 * Shape:
 *  [{
 *    name: "house",
 *    label: "house",
 *    style: "solid"|"regular"|"brands",
 *    className: "fa-solid fa-house",
 *    svg: "<svg ...>...</svg>"  // uses currentColor
 *  }, ...]
 */

import fs from 'fs';
import path from 'path';

const projectRoot = process.cwd();

const SRC_BASE = path.join(projectRoot, 'node_modules', '@fortawesome', 'fontawesome-free', 'svgs');
const OUT_PATH = path.join(projectRoot, 'resources', 'js', 'admin', 'fa-icons.json');

const STYLE_MAP = {
  solid: 'fa-solid',
  regular: 'fa-regular',
  brands: 'fa-brands',
};

function exists(p) {
  try { fs.accessSync(p); return true; } catch { return false; }
}

function listSvgFiles(dir) {
  if (!exists(dir)) return [];
  return fs.readdirSync(dir)
    .filter(f => f.toLowerCase().endsWith('.svg'))
    .map(f => path.join(dir, f));
}

function cleanSvg(svg) {
  let s = String(svg || '').trim();
  if (!s) return '';

  // Strip HTML comments (FA includes a license comment)
  s = s.replace(/<!--([\s\S]*?)-->/g, '');

  // Collapse whitespace between tags to keep the JSON smaller
  s = s.replace(/>\s+</g, '><');

  // Ensure it's a single line
  s = s.replace(/\r?\n/g, '');

  return s.trim();
}

function titleFromName(name) {
  return name.replace(/-/g, ' ');
}

function buildIndex() {
  if (!exists(SRC_BASE)) {
    console.error('Font Awesome SVG source folder not found:', SRC_BASE);
    console.error('Run: npm install');
    process.exit(1);
  }

  const out = [];

  for (const style of Object.keys(STYLE_MAP)) {
    const dir = path.join(SRC_BASE, style);
    const clsPrefix = STYLE_MAP[style];

    const files = listSvgFiles(dir);
    for (const file of files) {
      const filename = path.basename(file);
      const name = filename.replace(/\.svg$/i, '');
      const iconClass = `fa-${name}`;

      let svg = '';
      try {
        svg = cleanSvg(fs.readFileSync(file, 'utf8'));
      } catch {
        svg = '';
      }

      out.push({
        name,
        label: titleFromName(name),
        style,
        className: `${clsPrefix} ${iconClass}`,
        svg,
      });
    }
  }

  // Stable sort by label then style
  out.sort((a, b) => {
    const la = (a.label || a.name || '').toLowerCase();
    const lb = (b.label || b.name || '').toLowerCase();
    if (la < lb) return -1;
    if (la > lb) return 1;
    const sa = (a.style || '').toLowerCase();
    const sb = (b.style || '').toLowerCase();
    if (sa < sb) return -1;
    if (sa > sb) return 1;
    return 0;
  });

  return out;
}

const index = buildIndex();

fs.mkdirSync(path.dirname(OUT_PATH), { recursive: true });
fs.writeFileSync(OUT_PATH, JSON.stringify(index, null, 0), 'utf8');

console.log(`✅ Wrote ${index.length} icons to ${OUT_PATH}`);
