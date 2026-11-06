// Single registry of the public themes and of how their assets are built (Vite). Nothing else declares a theme's assets.
//
// Two asset modes, chosen with process environment variables (never written to .env):
//   CMS_ASSET_MODE=all                              every theme is built / watched; the administrator can switch theme.
//   CMS_ASSET_MODE=single CMS_ASSET_THEME=<key>     shared assets + that theme (+ its dependencies) only; the theme is fixed.
// No variable = all (the behaviour before this registry existed).
//
// Source layout is unchanged: Modules/CmsFrontend/resources/assets/{css,js}/screens/<theme>/ . Everything else under
// css/, js/ and sass/ of CmsFrontend is "shared" and is always built. Core resources and CmsBackend are always built too.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { glob } from 'glob';

export const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export const frontendAssets = 'Modules/CmsFrontend/resources/assets';

// Effects are part of the shared framework (see sharedEffectEntries below): every profile builds them, so any theme can use any effect and a
// single-theme build never lacks one. Per-theme entries here are only what really belongs to one theme.
const defaultSharedEntries = [`${frontendAssets}/shared/search-dialog.css`];

// Layout base used by the campus themes (Nam Bình Dương, Ban Đề án) but not by default (Khuyến nông).
const campusSharedEntries = [`${frontendAssets}/shared/campus-base.css`];

// Shared effect entries, always built (Homepage Layout Manager: EffectCatalog / effects/auto). The CSS files are loaded by the layouts
// (reveal, stagger) or conditionally by home.blade.php of each theme; effects/auto/*.js are self-starting entries loaded only when an
// enabled block declares the preset.
const sharedEffectEntries = [...['reveal', 'stagger', 'gallery-orbit', 'crossfade-slider'].map(name => `${frontendAssets}/effects/${name}/style.css`), `${frontendAssets}/effects/auto/*.js`, `${frontendAssets}/effects/dashboard-reveal/dashboard-reveal.css`, `${frontendAssets}/effects/dashboard-reveal/dashboard-reveal.js`];

/**
 * label         shown by the tools only (the administrator-facing label stays in Modules/CmsBackend/config/screens.php)
 * dependencies  themes whose entries must be built with this one (declared, never picked up by accident)
 * entries       CSS / JS entries owned by the theme (glob patterns relative to the project root)
 * sharedEntries CSS entries outside screens/ that ONLY this theme loads (shared/ layout base, effects/): built for the theme (and for mode `all`), never for the others
 * fallbackEntries  entries of ANOTHER theme that this theme still loads because ScreenTheme falls back to the default
 *                  view (see the comments below). They are verified at build time against the Blade views.
 */
export const themeProfiles = {
  default: {
    label: 'Mặc định (Khuyến nông)',
    dependencies: [],
    entries: {
      css: [`${frontendAssets}/css/screens/default/**/*.css`],
      js: [`${frontendAssets}/js/screens/default/**/*.js`]
    },
    sharedEntries: defaultSharedEntries,
    fallbackEntries: []
  },

  'nam-binh-duong': {
    label: 'Nam Bình Dương',
    dependencies: [],
    entries: {
      css: [`${frontendAssets}/css/screens/nam-binh-duong/**/*.css`],
      js: [`${frontendAssets}/js/screens/nam-binh-duong/**/*.js`]
    },
    sharedEntries: campusSharedEntries,
    // The theme has no layouts/sections/{styles,scripts}Front view, so the default ones are used and they load the shell.
    fallbackEntries: [`${frontendAssets}/css/screens/default/shell.css`, `${frontendAssets}/js/screens/default/shell.js`]
  },

  'ban-de-an': {
    label: 'Ban Đề án',
    // Layout loads shared/campus-base.css + effects (shared assets, always built) and its own theme.css/theme.js: no longer needs Nam Bình Dương's files.
    dependencies: [],
    entries: {
      css: [`${frontendAssets}/css/screens/ban-de-an/**/*.css`],
      js: [`${frontendAssets}/js/screens/ban-de-an/**/*.js`]
    },
    sharedEntries: campusSharedEntries,
    fallbackEntries: [`${frontendAssets}/css/screens/default/shell.css`, `${frontendAssets}/js/screens/default/shell.js`]
  }
};

export const themeKeys = Object.keys(themeProfiles);

/**
 * Blade views that no layout includes and that the build check must not read (paths relative to
 * Modules/CmsFrontend/resources/views/screens/<theme>/). scriptsIncludesFront still points at assets/js/front-config.js,
 * a file that no longer exists; nothing includes it.
 */
export const unusedViews = ['components/layouts/sections/scriptsIncludesFront.blade.php'];

/** Shared CmsFrontend assets: everything outside screens/<theme>/, plus effects/auth. Always built. */
// Login is one flow for every theme: auth/ (shared form logic + base CSS) and effects/auth are shared entries. Each theme's own Login look is
// css/screens/<theme>/auth.css, picked up by that theme's `entries.css` glob (so a single build only carries its own). The three effects/auth .js
// files are plain scripts loaded with <script src> (pagereveal must be registered before the first render); the module files are imported by
// auth/login.js and are not entries. Other effect entries (sharedEffectEntries) are shared too; shared/ CSS of a layout base is per-theme: see sharedEntries.
const authEffectEntries = [
  `${frontendAssets}/auth/login-base.css`,
  `${frontendAssets}/auth/login.js`,
  ...['style.css', 'dashboard-arrival.css', 'dashboard-arrival.js', 'login-arrival.js', 'logout-login-transition.js', 'push-horizontal.css', 'push-horizontal-transition.js'].map(file => `${frontendAssets}/effects/auth/${file}`)
];
export const sharedFrontendPatterns = [`${frontendAssets}/js/**/*.js`, `${frontendAssets}/css/**/*.css`, `${frontendAssets}/sass/**/*.scss`, ...authEffectEntries, ...sharedEffectEntries];
const sharedFrontendIgnore = [`${frontendAssets}/{js,css,sass}/screens/**`];

/** Theme keys to build for `theme`: its dependencies first (recursively), then itself. */
export function themeClosure(theme, seen = []) {
  if (!Object.hasOwn(themeProfiles, theme)) throw new Error(`Theme "${theme}" is not registered. Registered: ${themeKeys.join(', ')}.`);
  if (seen.includes(theme)) throw new Error(`Circular theme dependency: ${[...seen, theme].join(' -> ')}.`);
  return [...new Set([...themeProfiles[theme].dependencies.flatMap(dep => themeClosure(dep, [...seen, theme])), theme])];
}

/**
 * @param {Record<string, string | undefined>} env
 * @returns {{ mode: 'all' | 'single', theme: string | null, themes: string[], buildThemes: string[], dependencies: string[] }}
 *   themes      themes the administrator may use
 *   buildThemes themes whose own entries are part of the build (themes + dependencies)
 */
export function resolveAssetProfile(env = process.env) {
  const mode = (env.CMS_ASSET_MODE || 'all').trim().toLowerCase();
  if (mode === 'all') {
    return { mode, theme: null, themes: [...themeKeys], buildThemes: [...themeKeys], dependencies: [] };
  }
  if (mode !== 'single') throw new Error(`CMS_ASSET_MODE must be "all" or "single" (got "${env.CMS_ASSET_MODE}").`);
  const theme = (env.CMS_ASSET_THEME || '').trim();
  if (!theme) throw new Error(`CMS_ASSET_MODE=single needs CMS_ASSET_THEME. Registered: ${themeKeys.join(', ')}.`);
  const buildThemes = themeClosure(theme);

  return { mode, theme, themes: [theme], buildThemes, dependencies: buildThemes.filter(key => key !== theme) };
}

/** Files (entries) of CmsFrontend for a profile, relative to the project root, forward slashes, sorted. */
export function frontendEntryFiles(profile) {
  const find = (patterns, ignore = []) => glob.sync(patterns, { cwd: root, ignore, posix: true, nodir: true });
  const shared = find(sharedFrontendPatterns, sharedFrontendIgnore);
  const byTheme = Object.fromEntries(profile.buildThemes.map(key => [key, find([...themeProfiles[key].entries.css, ...themeProfiles[key].entries.js, ...(themeProfiles[key].sharedEntries ?? [])])]));
  // A theme without its own Login page falls back to the default Login view, which needs default's auth.css.
  const loginFallback = profile.buildThemes
    .filter(key => key !== 'default' && !fs.existsSync(path.join(root, `Modules/CmsFrontend/resources/views/screens/${key}/pages/auth/login.blade.php`)))
    .map(() => `${frontendAssets}/css/screens/default/auth.css`);
  const fallback = [...new Set([...profile.buildThemes.flatMap(key => themeProfiles[key].fallbackEntries), ...loginFallback])];
  for (const file of fallback) {
    if (!glob.sync(file, { cwd: root, posix: true }).length) throw new Error(`Fallback entry is missing on disk: ${file}`);
  }

  return { shared: shared.sort(), byTheme, fallback: fallback.sort() };
}

/** Absolute, forward-slash entry list for the Vite configs. */
export function frontendInputs(profile = resolveAssetProfile()) {
  const { shared, byTheme, fallback } = frontendEntryFiles(profile);
  const all = [...shared, ...Object.values(byTheme).flat(), ...fallback];

  return [...new Set(all)].sort().map(file => `${root.replace(/\\/g, '/')}/${file}`);
}

/** Theme directories whose files must not be watched in single mode (css/js screens/<theme> of the themes that are not built). */
export function excludedThemeWatchGlobs(profile = resolveAssetProfile()) {
  const wanted = new Set(profile.buildThemes);

  return themeKeys
    .filter(key => !wanted.has(key))
    .flatMap(key => [`**/${frontendAssets}/css/screens/${key}/**`, `**/${frontendAssets}/js/screens/${key}/**`]);
}
