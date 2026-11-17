// Tạo hai tệp CSS dẫn xuất của theme `bandean-v2` từ shared/campus-base.css và các tệp riêng của theme (CSS dùng chung không bị sửa nên các theme khác không đổi):
//   palette.css     màu xanh dương -> xanh lá (xoay màu -58 độ, giữ độ bão hòa và độ sáng), chỉ các khai báo có màu xanh dương
//   typography.css  cỡ chữ tối thiểu: mọi khai báo font-size bọc max(giá-trị-gốc, var(--bd2-fs-min)); --bd2-fs-min khai ở banner.css
// Chạy lại mỗi khi campus-base.css, theme.css, sections.css, banner.css hoặc footer.css đổi cỡ chữ/màu:  node scripts/bandean-v2-derived-css.mjs
// (--check: không ghi, thoát mã 1 nếu hai tệp đang lệch so với nguồn). Hai tệp đầu ra có chú thích đầu tệp, không sửa tay.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const assets = path.join(root, 'Modules/CmsFrontend/resources/assets');
const themeDir = path.join(assets, 'css/screens/bandean-v2');
const PREFIX = '.proposal-public.sbut-public';
const read = file => fs.readFileSync(file, 'utf8');

// ---------- phân tích CSS tối thiểu ----------
function splitTop(text, sep) {
  const parts = []; let depth = 0; let cur = '';
  for (const ch of text) {
    if ('(['.includes(ch)) depth++; else if (')]'.includes(ch)) depth--;
    if (ch === sep && depth === 0) { parts.push(cur); cur = ''; } else cur += ch;
  }
  if (cur.trim()) parts.push(cur);
  return parts;
}
function stripComments(css) { return css.replace(/\/\*[\s\S]*?\*\//g, ''); }
function* blocks(css) {
  let i = 0; const n = css.length;
  while (i < n) {
    const j = css.indexOf('{', i); if (j < 0) break;
    const prelude = css.slice(i, j).trim();
    let depth = 1; let k = j + 1;
    while (k < n && depth) { if (css[k] === '{') depth++; else if (css[k] === '}') depth--; k++; }
    yield [prelude, css.slice(j + 1, k - 1)];
    i = k;
  }
}
function prefixSelector(sel) {
  sel = sel.trim();
  if (sel.startsWith(PREFIX)) return sel;
  if (sel.startsWith('.sbut-public')) return PREFIX + sel.slice('.sbut-public'.length);
  if (/^(:root|html|body)/.test(sel)) return null;
  return `${PREFIX} ${sel}`;
}
// transform(declaration) -> chuỗi khai báo mới hoặc null (bỏ qua)
function rulesFrom(css, transform) {
  const out = [];
  for (const [prelude, body] of blocks(css)) {
    if (/^@(media|supports)/.test(prelude)) {
      const inner = rulesFrom(body, transform);
      if (inner.length) out.push(`${prelude}{${inner.join('')}}`);
      continue;
    }
    if (prelude.startsWith('@')) continue; // @keyframes, @font-face, @import...
    const hasId = /#[A-Za-z_-]/.test(prelude);
    const kept = splitTop(body, ';').filter(d => d.includes(':')).map(d => transform(d.trim(), hasId)).filter(Boolean);
    if (!kept.length) continue;
    const sels = prelude.split(',').map(prefixSelector).filter(Boolean);
    if (sels.length) out.push(`${sels.join(',')}{${kept.join(';')}}`);
  }
  return out;
}

// ---------- palette: xanh dương -> xanh lá ----------
const SHIFT = -58 / 360;
function rgbToHls(r, g, b) {
  r /= 255; g /= 255; b /= 255;
  const max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2;
  if (max === min) return [0, l, 0];
  const d = max - min, s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
  const h = max === r ? ((g - b) / d + (g < b ? 6 : 0)) : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
  return [h / 6, l, s];
}
function hlsToRgb(h, l, s) {
  if (s === 0) return [l, l, l].map(v => Math.round(v * 255));
  const q = l < 0.5 ? l * (1 + s) : l + s - l * s, p = 2 * l - q;
  const f = t => { t = (t + 1) % 1; if (t < 1 / 6) return p + (q - p) * 6 * t; if (t < 1 / 2) return q; if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6; return p; };
  return [f(h + 1 / 3), f(h), f(h - 1 / 3)].map(v => Math.round(v * 255));
}
const isBlue = (r, g, b) => { const [h, l, s] = rgbToHls(r, g, b); const deg = h * 360; return deg >= 170 && deg <= 262 && s >= 0.06 && l >= 0.04 && l <= 0.985; };
const shift = (r, g, b) => { const [h, l, s] = rgbToHls(r, g, b); return hlsToRgb((h + SHIFT + 1) % 1, l, s); };
function recolor(text) {
  let changed = false;
  let out = text.replace(/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b/g, (all, raw) => {
    if (raw.length === 3) raw = [...raw].map(c => c + c).join('');
    const r = parseInt(raw.slice(0, 2), 16), g = parseInt(raw.slice(2, 4), 16), b = parseInt(raw.slice(4, 6), 16);
    if (!isBlue(r, g, b)) return all;
    changed = true;
    return '#' + shift(r, g, b).map(v => v.toString(16).padStart(2, '0')).join('').toUpperCase();
  });
  out = out.replace(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(?:,\s*([\d.]+)\s*)?\)/g, (all, r, g, b, a) => {
    [r, g, b] = [+r, +g, +b];
    if (!isBlue(r, g, b)) return all;
    changed = true;
    const [nr, ng, nb] = shift(r, g, b);
    return a !== undefined ? `rgba(${nr},${ng},${nb},${a})` : `rgb(${nr},${ng},${nb})`;
  });
  return [out, changed];
}

// ---------- typography: cỡ chữ tối thiểu ----------
// hasId: luật gốc dùng #id (đặc hiệu hơn tiền tố .proposal-public.sbut-public) nên bản ép phải kèm !important mới thắng.
function minFontSize(decl, hasId) {
  let m = decl.match(/^font-size\s*:\s*([\s\S]+?)(\s*!important)?$/i);
  if (!m) {
    // Viết tắt `font: [kiểu] [độ đậm] <cỡ>[/dòng] <họ chữ>`: lấy cỡ có đơn vị (số trần như 400, 700 là độ đậm, không phải cỡ).
    const shorthand = decl.match(/^font\s*:\s*(?:[a-z0-9.\-]+\s+)*?(\d+(?:\.\d+)?(?:px|rem|em|pt|%))(?:\/[\w.%-]+)?\s+[^\s].*?(\s*!important)?$/i);
    if (!shorthand) return null;
    m = [shorthand[0], shorthand[1], shorthand[2]];
  }
  const value = m[1].trim();
  if (/^(inherit|initial|unset|revert|smaller|larger|xx-small|x-small|small|medium|large|x-large|xx-large)$/i.test(value) || value.includes('--bd2-fs-min')) return null;
  return `font-size:max(${value},var(--bd2-fs-min))${m[2] || hasId ? ' !important' : ''}`;
}

// ---------- đầu ra ----------
const sources = {
  palette: [path.join(assets, 'shared/campus-base.css')],
  typography: [path.join(assets, 'shared/campus-base.css'), ...['theme.css', 'sections.css', 'banner.css', 'footer.css'].map(f => path.join(themeDir, f))],
};
const paletteRules = rulesFrom(stripComments(read(sources.palette[0])), decl => {
  if (!decl.includes(':') || decl.includes('var(--')) return null;
  const [out, changed] = recolor(decl);
  return changed ? out : null;
});
// theme.css của bandean-v2 là tệp riêng: màu xanh dương của nó đổi tại chỗ (một lần, không sinh tệp) nên không đi qua đây.
const typographyRules = sources.typography.flatMap(file => rulesFrom(stripComments(read(file)), minFontSize));
// Luật cũ của khung (layouts/sections/stylesFront): `@media (max-width: 480px) { #mobileSidebar a, button { font-size: 13px !important } }` ép MỌI nút xuống 13px.
// Trả nút về cỡ chữ kế thừa từ khối cha (như bản máy tính, nhờ `font: inherit`), không nhỏ hơn mức tối thiểu.
typographyRules.push(`@media (max-width: 480px){${PREFIX} button{font-size:max(1em,var(--bd2-fs-min)) !important}}`);

const outputs = {
  [path.join(themeDir, 'palette.css')]: '/* bandean-v2: màu xanh dương còn sót trong shared/campus-base.css được đổi sang tông xanh lá (cùng độ bão hòa và độ sáng, xoay màu -58 độ).\n   TẠO TỰ ĐỘNG bởi scripts/bandean-v2-derived-css.mjs (chỉ các khai báo có màu xanh dương); campus-base.css giữ nguyên cho các theme khác.\n   Nạp sau theme.css và trước sections.css. Bộ màu gốc: docs/design-standards/BO-MAU-THEME.md. */\n' + paletteRules.join('\n') + '\n',
  [path.join(themeDir, 'typography.css')]: '/* bandean-v2: cỡ chữ tối thiểu. Mọi khai báo font-size của campus-base.css, theme.css, sections.css và banner.css được bọc max(giá-trị-gốc, var(--bd2-fs-min)).\n   --bd2-fs-min khai ở banner.css (bằng cỡ chữ dòng thông tin tuyển sinh: 16,8px máy tính, 15,6px điện thoại). Đổi một giá trị đó là đổi cả trang.\n   TẠO TỰ ĐỘNG bởi scripts/bandean-v2-derived-css.mjs; nạp SAU cùng (sau banner.css). Quy tắc font: docs/design-standards/BO-MAU-THEME.md. */\n' + typographyRules.join('\n') + '\n',
};

const check = process.argv.includes('--check');
let stale = 0;
for (const [file, content] of Object.entries(outputs)) {
  const rel = path.relative(root, file).replace(/\\/g, '/');
  const current = fs.existsSync(file) ? read(file) : '';
  if (check) { if (current !== content) { stale++; console.log(`LỆCH: ${rel}`); } else console.log(`khớp: ${rel}`); continue; }
  fs.writeFileSync(file, content);
  console.log(`đã ghi ${rel} (${content.split('\n').length - 4} dòng quy tắc)`);
}
if (check && stale) process.exit(1);
