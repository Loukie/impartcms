// Font Awesome icon list for the icon library.
// Source file (generated): resources/js/admin/fa-icons.json
// Each entry already includes style + className + svg.

import base from './fa-icons.json';

const list = (Array.isArray(base) ? base : []).map(it => {
  const name = String(it.name || '').trim();
  const style = String(it.style || '').trim();
  const className = String(it.className || '').trim();
  const svg = String(it.svg || '').trim();

  return {
    name,
    label: String(it.label || name),
    style,
    className,
    svg,
  };
}).filter(it => it.name && it.style && it.className);

export default list;
