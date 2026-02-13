import fs from "node:fs";
import path from "node:path";

import * as solid from "@fortawesome/free-solid-svg-icons";
import * as regular from "@fortawesome/free-regular-svg-icons";
import * as brands from "@fortawesome/free-brands-svg-icons";

function extract(module, style, classPrefix) {
  const out = [];
  for (const val of Object.values(module)) {
    if (!val || typeof val !== "object") continue;
    if (!("iconName" in val) || !val.iconName) continue;

    const name = val.iconName;
    out.push({
      name,
      style,
      className: `${classPrefix} fa-${name}`,
      label: `${name} (${style})`,
    });
  }
  return out;
}

const list = [
  ...extract(solid, "solid", "fa-solid"),
  ...extract(regular, "regular", "fa-regular"),
  ...extract(brands, "brands", "fa-brands"),
]
  .filter((v, i, arr) => arr.findIndex(x => x.className === v.className) === i)
  .sort((a, b) => (a.name === b.name ? a.style.localeCompare(b.style) : a.name.localeCompare(b.name)));

const outPath = path.resolve("resources/js/admin/fa-icons.json");
fs.mkdirSync(path.dirname(outPath), { recursive: true });
fs.writeFileSync(outPath, JSON.stringify(list, null, 2), "utf8");

console.log(`✅ Wrote ${list.length} icons to ${outPath}`);
