// Minimal Playwright screenshot helper
// Usage:
//   node scripts/ai-screenshot.mjs --url=https://example.com --out=/abs/path.png

import fs from 'node:fs';
import path from 'node:path';

function parseArgs() {
  const out = {};
  for (const a of process.argv.slice(2)) {
    if (!a.startsWith('--')) continue;
    const [k, ...rest] = a.slice(2).split('=');
    out[k] = rest.join('=');
  }
  return out;
}

const args = parseArgs();
const url = args.url;
const outPath = args.out;

if (!url || !outPath) {
  console.error('Missing --url or --out');
  process.exit(1);
}

// Lazy import so the script can show a clearer error if Playwright isn't installed.
let chromium;
try {
  ({ chromium } = await import('playwright'));
} catch (e) {
  console.error('Playwright is not installed. Run: npm i -D playwright && npx playwright install chromium');
  process.exit(1);
}

// Ensure output directory exists
fs.mkdirSync(path.dirname(outPath), { recursive: true });

const browser = await chromium.launch({ headless: true });
try {
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  page.setDefaultNavigationTimeout(90000);
  page.setDefaultTimeout(90000);

  await page.goto(url, { waitUntil: 'networkidle' });
  await page.waitForTimeout(500);
  await page.screenshot({ path: outPath, fullPage: true });
  console.log(outPath);
} finally {
  await browser.close();
}
