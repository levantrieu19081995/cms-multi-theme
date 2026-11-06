// Build of the CMS assets per asset profile (see scripts/theme-profiles.mjs).
//
//   node scripts/theme-build.mjs all                  every theme       -> build-exports/all-themes/build
//   node scripts/theme-build.mjs <theme>              one theme         -> build-exports/themes/<theme>/build
//   add --check-only to run the registry / Blade checks without running Vite.
//
// The output goes to its own folder under build-exports/; public/build is never written or cleared. Deploy = copy the
// content of that folder into public/build of the target project (theme-build.json comes along and tells Laravel which
// themes exist in that build).
import fs from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';
import { glob } from 'glob';
import { build } from 'vite';
import { projectProfiles } from './project-profiles.mjs';
import { frontendEntryFiles, resolveAssetProfile, root, themeKeys, themeProfiles, unusedViews } from './theme-profiles.mjs';

const posix = file => file.replace(/\\/g, '/');
const rel = file => posix(path.relative(root, file));

/** VITE_THEME_PATH/VITE_THEME_NAME of .env: Theme::asset() prefixes the theme assets with it. */
function themeAssetPrefix() {
  const env = fs.existsSync(path.join(root, '.env')) ? fs.readFileSync(path.join(root, '.env'), 'utf8') : '';
  const read = key => env.match(new RegExp(`^${key}=(.*)$`, 'm'))?.[1]?.trim().replace(/^["']|["']$/g, '');

  return `${read('VITE_THEME_PATH') || 'resources/themes'}/${read('VITE_THEME_NAME') || 'materialize'}`;
}

/** Entries Vite is asked to build for a profile (what the manifest must contain), relative to the project root. */
async function expectedEntries(profile) {
  const entries = new Set();
  // Core
  for (const pattern of ['resources/assets/js/**/*.js', 'resources/assets/css/**/*.css', 'resources/assets/sass/**/*.scss']) {
    glob.sync(pattern, { cwd: root, posix: true }).forEach(file => entries.add(file));
  }
  // Core theme package (resources/themes/<name>/vite.config.js)
  const themeConfig = await import(pathToFileURL(path.join(root, themeAssetPrefix(), 'vite.config.js')).href).then(module => module.default);
  for (const file of themeConfig.laravel.input) entries.add(posix(file).replace(/^\.\//, ''));
  // CmsBackend (always complete)
  for (const pattern of ['js/**/*.js', 'css/**/*.css', 'sass/**/*.scss']) {
    glob.sync(`Modules/CmsBackend/resources/assets/${pattern}`, { cwd: root, posix: true }).forEach(file => entries.add(file));
  }
  // CmsFrontend: shared + themes of the profile + verified fallback entries
  const { shared, byTheme, fallback } = frontendEntryFiles(profile);
  [...shared, ...Object.values(byTheme).flat(), ...fallback].forEach(file => entries.add(file));

  return entries;
}

function stripBladeComments(source) {
  return source.replace(/\{\{--[\s\S]*?--\}\}/g, '');
}

/** String literals of every @vite(...) call of a Blade file (the parentheses are balanced, strings are skipped). */
function viteLiterals(source) {
  const text = stripBladeComments(source);
  const found = [];
  for (let at = text.indexOf('@vite('); at !== -1; at = text.indexOf('@vite(', at + 6)) {
    let depth = 0;
    let quote = null;
    let end = at + 5;
    for (let i = at + 5; i < text.length; i++) {
      const char = text[i];
      if (quote) {
        if (char === '\\') i++;
        else if (char === quote) quote = null;
      } else if (char === "'" || char === '"') quote = char;
      else if (char === '(') depth++;
      else if (char === ')' && --depth === 0) { end = i; break; }
    }
    const call = text.slice(at, end + 1);
    for (const match of call.matchAll(/'((?:[^'\\]|\\.)*)'|"((?:[^"\\]|\\.)*)"/g)) {
      const literal = match[1] ?? match[2];
      // Only complete asset paths: pieces of a concatenation ('.scss', '/core') are not paths.
      if (/^[\w\-./]+\.(css|scss|js|mjs|ts)$/i.test(literal) && !literal.startsWith('.') && !literal.startsWith('/')) found.push(literal);
    }
  }

  return found;
}

function bladeFiles(dir) {
  return fs.existsSync(dir) ? glob.sync('**/*.blade.php', { cwd: dir, posix: true, ignore: unusedViews }) : [];
}

/**
 * Blade files a theme can render: its own views, the default views it does not override (ScreenTheme falls back to
 * `default`) and the frontend views outside screens/. Returns absolute paths.
 */
function effectiveViews(theme) {
  const screens = path.join(root, 'Modules/CmsFrontend/resources/views/screens');
  const own = new Set(bladeFiles(path.join(screens, theme)));
  const files = [...own].map(file => path.join(screens, theme, file));
  for (const file of bladeFiles(path.join(screens, 'default'))) {
    if (!own.has(file)) files.push(path.join(screens, 'default', file));
  }
  const viewsRoot = path.join(root, 'Modules/CmsFrontend/resources/views');
  for (const file of glob.sync('**/*.blade.php', { cwd: viewsRoot, posix: true, ignore: 'screens/**' })) files.push(path.join(viewsRoot, file));

  return files;
}

/** @returns {{ errors: string[], warnings: string[] }} */
async function checkViews(profile) {
  const expected = await expectedEntries(profile);
  const prefix = themeAssetPrefix();
  const references = new Map();
  for (const theme of profile.themes) {
    for (const file of effectiveViews(theme)) {
      for (const literal of viteLiterals(fs.readFileSync(file, 'utf8'))) {
        const asset = literal.startsWith('assets/') ? `${prefix}/${literal}` : literal;
        references.set(asset, [...(references.get(asset) ?? []), `${theme}: ${rel(file)}`]);
      }
    }
  }
  const errors = [];
  const warnings = [];
  for (const [asset, sources] of references) {
    if (expected.has(asset)) continue;
    const moduleName = asset.match(/^Modules\/([^/]+)\//)?.[1];
    const sample = [...new Set(sources)].slice(0, 3).join('; ');
    if (moduleName && !fs.existsSync(path.join(root, 'Modules', moduleName))) {
      warnings.push(`${asset} (module ${moduleName} is not in this project) used by ${sample}`);
    } else if (!fs.existsSync(path.join(root, asset))) {
      errors.push(`${asset} does not exist on disk, used by ${sample}`);
    } else {
      errors.push(`${asset} exists but is NOT built by this profile, used by ${sample}. Add it to scripts/theme-profiles.mjs (fallbackEntries) or fix the view.`);
    }
  }

  return { errors, warnings, count: references.size };
}

function checkManifest(profile, outDir, expected) {
  const manifestFile = path.join(outDir, 'manifest.json');
  if (!fs.existsSync(manifestFile)) throw new Error('manifest.json was not produced.');
  const manifest = JSON.parse(fs.readFileSync(manifestFile, 'utf8'));
  const keys = Object.keys(manifest);
  const errors = [];
  if (!keys.some(key => key.startsWith('resources/'))) errors.push('Manifest has no Core asset (resources/).');
  if (!keys.some(key => key.startsWith('Modules/CmsBackend/'))) errors.push('Manifest has no CmsBackend asset.');
  if (!keys.some(key => key.startsWith('Modules/CmsFrontend/'))) errors.push('Manifest has no CmsFrontend asset.');
  for (const entry of expected) {
    if (!Object.hasOwn(manifest, entry)) errors.push(`Manifest lacks the entry ${entry}.`);
  }
  // Single theme: no entry of a theme that is neither the theme, its dependencies nor a declared fallback.
  const allowed = new Set([...frontendEntryFiles(profile).fallback, ...Object.values(frontendEntryFiles(profile).byTheme).flat()]);
  for (const key of keys) {
    const theme = key.match(/^Modules\/CmsFrontend\/resources\/assets\/(?:css|js)\/screens\/([^/]+)\//)?.[1];
    if (theme && profile.mode === 'single' && !allowed.has(key)) errors.push(`Manifest has an entry of another theme: ${key}.`);
    if (theme && !themeKeys.includes(theme)) errors.push(`Manifest has an entry of an unregistered theme: ${key}.`);
  }
  for (const chunk of Object.values(manifest)) {
    for (const file of [chunk.file, ...(chunk.css ?? []), ...(chunk.assets ?? [])].filter(Boolean)) {
      if (!fs.existsSync(path.join(outDir, file))) errors.push(`Missing file ${file}.`);
    }
  }

  return { errors, entries: keys.length };
}

function writeMetadata(profile, outDir) {
  const metadata = {
    mode: profile.mode,
    themes: profile.themes,
    ...(profile.dependencies.length ? { dependencies: profile.dependencies } : {}),
    generatedAt: new Date().toISOString()
  };
  fs.writeFileSync(path.join(outDir, 'theme-build.json'), JSON.stringify(metadata, null, 2) + '\n');

  return metadata;
}

try {
  const argv = process.argv.slice(2);
  const checkOnly = argv.includes('--check-only');
  const [target] = argv.filter(arg => !arg.startsWith('--'));
  if (!target || (target !== 'all' && !Object.hasOwn(themeProfiles, target))) {
    throw new Error(`Choose "all" or a theme: ${themeKeys.join(', ')}.`);
  }
  // Process environment only: .env is never edited.
  process.env.CMS_ASSET_MODE = target === 'all' ? 'all' : 'single';
  process.env.CMS_ASSET_THEME = target === 'all' ? '' : target;
  process.env.CMS_BUILD_PROJECT = 'cms';
  if (!Object.hasOwn(projectProfiles, 'cms')) throw new Error('Project profile "cms" is missing.');
  const profile = resolveAssetProfile(process.env);

  console.log(`Profile: ${profile.mode === 'all' ? 'all themes' : `theme ${profile.theme}` + (profile.dependencies.length ? ` (+ ${profile.dependencies.join(', ')})` : '')}`);
  const views = await checkViews(profile);
  views.warnings.forEach(message => console.warn(`  warning: ${message}`));
  if (views.errors.length) throw new Error(`Blade views use assets this profile does not build:\n  - ${views.errors.join('\n  - ')}`);
  console.log(`Blade check: ${views.count} @vite assets used by the views of ${profile.themes.join(', ')} are all in the build.`);
  if (checkOnly) process.exit(0);

  const exportRoot = path.join(root, 'build-exports');
  const outDir = target === 'all' ? path.join(exportRoot, 'all-themes', 'build') : path.join(exportRoot, 'themes', target, 'build');
  const relativeTarget = path.relative(exportRoot, outDir);
  if (relativeTarget.startsWith('..') || path.isAbsolute(relativeTarget)) throw new Error('Build folder is outside build-exports.');

  await build({ root, configFile: path.join(root, 'vite.config.js'), build: { outDir, emptyOutDir: true } });

  const result = checkManifest(profile, outDir, await expectedEntries(profile));
  if (result.errors.length) throw new Error(`Manifest check failed:\n  - ${result.errors.join('\n  - ')}`);
  const metadata = writeMetadata(profile, outDir);
  console.log(`\nBuild finished: ${outDir}`);
  console.log(`Manifest checked (${result.entries} entries), theme-build.json: mode ${metadata.mode}, themes ${metadata.themes.join(', ')}.`);
  console.log('To deploy, copy the content of this folder into public/build of the right project. public/build here is untouched.');
} catch (error) {
  console.error(error.message);
  process.exitCode = 1;
}
