// Finds image files of the source that nothing refers to, and moves them to a quarantine folder (so they can be put back, or deleted
// once the system has been checked). Read-only unless --apply / --restore is given.
//
//   node scripts/unused-images.mjs                 report only  (build-exports/unused-images/report.json + report.md)
//   node scripts/unused-images.mjs --apply         move the files of the last report to _unused-images/ (same relative paths)
//   node scripts/unused-images.mjs --restore       move them back
//   node scripts/unused-images.mjs --check [label] list image references that point at no file that exists NOW (run before and after --apply:
//                                                  a reference that appears only afterwards was broken by the move) -> build-exports/unused-images/dangling-<label>.txt
//   options: --db-refs <file.tsv>   extra references (image paths mentioned by database content, one per line, "<table.column>\t<path>")
//
// Rule: an image is KEPT when anything could use it. A file is unused only when none of these finds it:
//   path     a reference ends with its path (assets/img/a/b.png, ../img/a/b.png, a/b.png)
//   name     its file name appears in any text of the source, CSS, JS, Blade, config, language files or in the database references (kept even
//            when only the name matches: a different folder may be meant, we do not guess)
//   dynamic  the code builds its path (asset('assets/img/flags/'.$code.'.svg')): every file under that folder is kept
//   manifest a Vite manifest lists it (images bundled from CSS)
//   fixed    favicons and touch icons, which browsers ask for by themselves
// Third-party folders (public/plugins, public/vendor, public/assets/vendor, the vendor libs of the admin themes), the curated source folder SBUT-Hinh-Anh-Video, the uploads (public/media, public/userfiles, storage) and build output are never touched.
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const OUT = path.join(root, 'build-exports/unused-images');
const QUARANTINE = path.join(root, '_unused-images');
const args = process.argv.slice(2);
const flag = name => args.includes(name);
const option = name => (args.includes(name) ? args[args.indexOf(name) + 1] : undefined);

const IMAGE = /\.(png|jpe?g|gif|webp|svg|ico|avif|bmp)$/i;
const TEXT = /\.(php|js|mjs|cjs|ts|css|scss|sass|json|html?|xml|vue|svg|txt)$/i;
const NEVER = [/^node_modules\//, /^vendor\//, /^public\/build\//, /^public\/plugins\//, /^public\/vendor\//, /^public\/assets\/vendor\//, /^resources\/themes\/[^/]+\/assets\/vendor\//, /^SBUT-Hinh-Anh-Video\//, /^public\/media\//, /^public\/userfiles\//, /^storage\//, /^build-exports\//, /^_unused-images\//, /^\.codex-backup\//];
const NOT_CORPUS = [/^node_modules\//, /^vendor\//, /^public\/build\//, /^public\/media\//, /^public\/userfiles\//, /^storage\//, /^build-exports\//, /^_unused-images\//, /^\.codex-backup\//, /^docs\//, /^tests\//, /^\.git\//, /package-lock\.json$/, /composer\.lock$/, /\.map$/, /^SBUT-Hinh-Anh-Video\//]; // third-party code is READ (it may point at our images) but never moved

const git = (...a) => execFileSync('git', a, { cwd: root, maxBuffer: 1 << 28 }).toString('utf8');
const tracked = git('ls-files', '-z').split('\0').filter(Boolean);
const lower = s => s.toLowerCase();
const base = p => lower(path.posix.basename(p));

function normalise(token) {
  let t = token;
  try { t = decodeURIComponent(t); } catch { /* keep as is */ }
  t = lower(t).replace(/\\/g, '/').replace(/^(\.\.?\/)+/, '').replace(/^\/+/, '');
  return t;
}

if (flag('--restore') || flag('--apply')) {
  const reportFile = path.join(OUT, 'report.json');
  if (!fs.existsSync(reportFile)) throw new Error('No report: run the scan first.');
  const report = JSON.parse(fs.readFileSync(reportFile, 'utf8'));
  let moved = 0;
  for (const rel of report.unused.map(item => item.path)) {
    const from = flag('--apply') ? path.join(root, rel) : path.join(QUARANTINE, rel);
    const to = flag('--apply') ? path.join(QUARANTINE, rel) : path.join(root, rel);
    if (!fs.existsSync(from) || fs.existsSync(to)) continue;
    fs.mkdirSync(path.dirname(to), { recursive: true });
    fs.renameSync(from, to);
    moved++;
  }
  console.log(`${flag('--apply') ? 'Moved to _unused-images/' : 'Restored from _unused-images/'}: ${moved} file(s).`);
  process.exit(0);
}

// ---- 1. candidates: tracked images outside the folders that are never touched
const candidates = tracked.filter(f => IMAGE.test(f) && !NEVER.some(re => re.test(f)));

// ---- 2. the texts that may refer to an image
const corpus = tracked.filter(f => TEXT.test(f) && !NOT_CORPUS.some(re => re.test(f)));
const extRe = /\.(?:png|jpe?g|gif|webp|svg|ico|avif|bmp)(?![A-Za-z0-9])/gi;
const STOP = new Set([...' \t\r\n"\'`()<>,;=\{}|[]*^']);
/** Linear scan: every image-looking path in a text (walks back from each extension to the previous delimiter, at most 300 characters). */
function* imageTokens(text) {
  for (const m of text.matchAll(extRe)) {
    let start = m.index;
    const floor = Math.max(0, m.index - 300);
    while (start > floor && !STOP.has(text[start - 1])) start--;
    if (start < m.index) yield text.slice(start, m.index + m[0].length);
  }
}
const dynamicRes = [
  /['"`]([A-Za-z0-9_\-./]{6,120}\/)['"`]\s*(?:\.|\+)(?!\.)/g, // 'assets/img/x/' . $name   |   'assets/img/x/' + name
  /([A-Za-z0-9_\-.]{1,40}(?:\/[A-Za-z0-9_\-.]{1,40}){1,8}\/)(?:\{\$|\{\{|\$\{|\$[a-zA-Z_])/g, // assets/img/x/{$name}  |  assets/img/x/$name
  /(?:glob|scandir|allFiles|files|File::files|File::glob)\(\s*(?:public_path|base_path|resource_path)\(\s*['"]([A-Za-z0-9_\-./]{3,120})['"]/g,
];
// Code that serves pages may build an image path; build configs and scripts only mention folders. And a folder only counts when it is one for images.
const DYNAMIC_SOURCE = /^(app|routes|lang|bootstrap|config)\/|^Modules\/[^/]+\/(app|routes|config)\/|\/resources\/views\/|^resources\/(views|js|css|sass)\/|^resources\/themes\/[^/]+\/(views|assets\/js|assets\/scss|assets\/css)\//;
const IMAGE_DIR = /(^|\/)(assets|img|images|icons?|flags|avatars?|logos?|backgrounds?|illustrations?|photos?|thumbs?|banners?)\//i;
const tokens = new Map(); // basename -> Set(normalised tokens)
const spaced = []; // candidates whose name contains a space are searched as plain text
let corpusBytes = 0;
const dynamicPrefixes = new Map(); // prefix -> files that declare it
for (const rel of corpus) {
  let text;
  try { text = fs.readFileSync(path.join(root, rel), 'utf8'); } catch { continue; }
  corpusBytes += text.length;
  text = text.replace(/data:[a-z/+.-]+;base64,[A-Za-z0-9+/=]{40,}/gi, 'data:'); // inline images carry no path
  for (const raw of imageTokens(text)) {
    const t = normalise(raw);
    const b = path.posix.basename(t);
    if (!tokens.has(b)) tokens.set(b, new Set());
    tokens.get(b).add(t);
  }
  // Minified bundles are not code that builds an image path; scanning them for patterns would only cost time.
  for (const re of text.length > 400_000 || !DYNAMIC_SOURCE.test(rel) ? [] : dynamicRes) {
    for (const m of text.matchAll(re)) {
      const prefix = normalise(m[1]).replace(/^public\//, '').replace(/\/?$/, '/');
      if (prefix.length < 6 || !prefix.includes('/') || !IMAGE_DIR.test(prefix)) continue; // too general, or not an image folder
      if (!dynamicPrefixes.has(prefix)) dynamicPrefixes.set(prefix, new Set());
      dynamicPrefixes.get(prefix).add(rel);
    }
  }
}
// database content (paths only)
const dbRefs = [];
const dbFile = option('--db-refs');
if (dbFile) {
  for (const line of fs.readFileSync(path.resolve(dbFile), 'utf8').split('\n')) {
    const p = line.includes('\t') ? line.split('\t')[1] : line;
    if (!p || !IMAGE.test(p.trim())) continue;
    const t = normalise(p.trim());
    const b = path.posix.basename(t);
    if (!tokens.has(b)) tokens.set(b, new Set());
    tokens.get(b).add(t);
    dbRefs.push(t);
  }
}
// Vite manifests (images that CSS pulled into a build)
const manifestSrc = new Set();
const manifests = ['public/build/manifest.json', ...fs.readdirSync(path.join(root, 'build-exports'), { recursive: true }).filter(f => String(f).endsWith('manifest.json')).map(f => `build-exports/${f}`)];
for (const m of manifests) {
  try {
    for (const entry of Object.values(JSON.parse(fs.readFileSync(path.join(root, m), 'utf8')))) {
      if (entry?.src && IMAGE.test(entry.src)) manifestSrc.add(lower(entry.src));
    }
  } catch { /* no build */ }
}

// ---- 2b. --check: references that point at nothing
if (flag('--check')) {
  const label = args[args.indexOf('--check') + 1]?.startsWith('--') ? 'now' : (args[args.indexOf('--check') + 1] ?? 'now');
  const present = new Map();
  for (const f of tracked.filter(f => IMAGE.test(f) && fs.existsSync(path.join(root, f)))) {
    const b = base(f);
    if (!present.has(b)) present.set(b, []);
    present.get(b).push(lower(f));
  }
  const EXTERNAL = /^(media|storage|userfiles|http|https|data|blob|cdn|www)[:/.]|^\/\//;
  const dangling = [];
  for (const [b, set] of tokens) {
    for (const t of set) {
      if (EXTERNAL.test(t) || t.includes('${') || t.includes('{{') || t.includes('$')) continue;
      const here = present.get(b) ?? [];
      const ok = t.includes('/') ? here.some(p => p === t || p.endsWith(`/${t}`)) : here.length > 0;
      if (!ok) dangling.push(t);
    }
  }
  fs.mkdirSync(OUT, { recursive: true });
  const file = path.join(OUT, `dangling-${label}.txt`);
  fs.writeFileSync(file, `${[...new Set(dangling)].sort().join('\n')}\n`);
  console.log(`${new Set(dangling).size} reference(s) point at no existing image -> ${path.relative(root, file)}`);
  process.exit(0);
}

// ---- 3. decide
// A reference that has a folder part and points at a real file belongs to THAT file: it does not keep other files of the same name.
// (Several folders hold copies with the same names; only a bare name, or a path that matches no file at all, keeps every file of that name.)
const trackedImages = new Map(); // basename -> lower paths of every tracked image, including the folders that are never touched
for (const f of tracked.filter(f => IMAGE.test(f))) {
  const b = base(f);
  if (!trackedImages.has(b)) trackedImages.set(b, []);
  trackedImages.get(b).push(lower(f));
}
const resolvedCache = new Map();
const resolves = t => {
  if (!resolvedCache.has(t)) resolvedCache.set(t, (trackedImages.get(path.posix.basename(t)) ?? []).some(p => p === t || p.endsWith(`/${t}`)));
  return resolvedCache.get(t);
};
const FIXED = /(^|\/)(favicon[^/]*|apple-touch-icon[^/]*|android-chrome[^/]*|mstile[^/]*|safari-pinned-tab\.svg|og-image[^/]*)$/i;
const dynamicDirs = [...dynamicPrefixes.keys()];
const used = { path: 0, name: 0, dynamic: 0, manifest: 0, fixed: 0 };
const unused = [];
const dynamicHits = new Map();
for (const file of candidates) {
  const lp = lower(file);
  const b = base(file);
  let why = null;
  const names = tokens.get(b);
  if (names) {
    for (const t of names) {
      if (t.includes('/') && (lp === t || lp.endsWith(`/${t}`))) { why = 'path'; break; }
    }
    // A bare name, or a folder path that matches no file, could mean this file: keep it.
    if (!why && [...names].some(t => !t.includes('/') || !resolves(t))) why = 'name';
  }
  if (!why && manifestSrc.has(lp)) why = 'manifest';
  if (!why) {
    const dir = dynamicDirs.find(prefix => lp.includes(`/${prefix}`) || lp.startsWith(prefix));
    if (dir) { why = 'dynamic'; dynamicHits.set(dir, (dynamicHits.get(dir) ?? 0) + 1); }
  }
  if (!why && FIXED.test(file)) why = 'fixed';
  if (!why && /\s/.test(b)) {
    // names with spaces cannot be found by the token scan: search the plain text
    spaced.push(file);
  }
  if (why) { used[why]++; continue; }
  unused.push({ path: file, bytes: fs.statSync(path.join(root, file)).size });
}
// spaced names: plain-text search over the corpus
if (spaced.length) {
  const body = corpus.map(rel => { try { return lower(fs.readFileSync(path.join(root, rel), 'utf8')); } catch { return ''; } }).join('\n');
  for (const file of spaced) {
    if (body.includes(base(file)) || body.includes(encodeURI(base(file)))) {
      const i = unused.findIndex(item => item.path === file);
      if (i >= 0) { unused.splice(i, 1); used.name++; }
    }
  }
}

// ---- 4. report
const mb = bytes => (bytes / 1048576).toFixed(2);
const group = key => {
  const rows = new Map();
  for (const item of unused) {
    const k = key(item.path);
    const row = rows.get(k) ?? { count: 0, bytes: 0 };
    row.count++; row.bytes += item.bytes; rows.set(k, row);
  }
  return [...rows].sort((a, b) => b[1].bytes - a[1].bytes);
};
const top = p => p.split('/').slice(0, p.startsWith('public/assets/img/') || p.startsWith('resources/themes/') ? 4 : 2).join('/');
const totalBytes = unused.reduce((sum, item) => sum + item.bytes, 0);
const summary = {
  generated_at: new Date().toISOString(),
  candidates: candidates.length, corpus_files: corpus.length, corpus_mb: +(corpusBytes / 1048576).toFixed(1),
  database_references: dbRefs.length, manifest_images: manifestSrc.size, dynamic_folders: dynamicDirs.length,
  kept: used, unused_count: unused.length, unused_mb: +mb(totalBytes),
};
fs.mkdirSync(OUT, { recursive: true });
fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify({ summary, dynamic_folders: Object.fromEntries([...dynamicPrefixes].map(([k, v]) => [k, { declared_in: [...v].slice(0, 5), files_kept: dynamicHits.get(k) ?? 0 }])), unused: unused.sort((a, b) => a.path.localeCompare(b.path)) }, null, 1));
const lines = [
  '# Ảnh không được tham chiếu', '',
  `Tạo lúc ${summary.generated_at}. Ứng viên: ${summary.candidates} ảnh; đọc ${summary.corpus_files} tệp văn bản (${summary.corpus_mb} MB); ${summary.database_references} tham chiếu từ CSDL; ${summary.manifest_images} ảnh trong manifest build.`, '',
  `Giữ lại: theo đường dẫn ${used.path}, theo tên tệp ${used.name}, thư mục động ${used.dynamic}, manifest ${used.manifest}, cố định ${used.fixed}.`,
  `**Không dùng: ${unused.length} ảnh, ${mb(totalBytes)} MB.**`, '',
  '## Theo thư mục', '', '| Thư mục | Số ảnh | MB |', '|---|---|---|', ...group(top).map(([k, v]) => `| ${k} | ${v.count} | ${mb(v.bytes)} |`), '',
  '## Thư mục động (mã tự ghép đường dẫn, giữ nguyên cả thư mục)', '', ...[...dynamicPrefixes].map(([k, v]) => `- \`${k}\` (${dynamicHits.get(k) ?? 0} tệp được giữ) khai báo ở ${[...v].slice(0, 2).join(', ')}`), '',
];
fs.writeFileSync(path.join(OUT, 'report.md'), lines.join('\n'));
console.log(JSON.stringify(summary, null, 1));
console.log(`Report: ${path.relative(root, path.join(OUT, 'report.md'))}`);
