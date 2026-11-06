import { glob } from 'glob';
import laravel from 'laravel-vite-plugin';
import { loadEnv, defineConfig } from 'vite';
import fs from 'fs';
import { projectProfiles } from './scripts/project-profiles.mjs';
import { excludedThemeWatchGlobs, resolveAssetProfile } from './scripts/theme-profiles.mjs';

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */

// Load core assets
function GetFilesArray(query) {
  return glob.sync(query);
}

const nestedPageJsFiles = GetFilesArray('resources/assets/js/**/*.js');
const nestedPageStyleFiles = GetFilesArray('resources/assets/css/**/*.css');
const nestedPageStyle2Files = GetFilesArray('resources/assets/sass/**/*.scss');
const customBuildFiles = [...nestedPageJsFiles, ...nestedPageStyleFiles, ...nestedPageStyle2Files];

console.log('Module Core');
customBuildFiles.forEach(f => {
  // console.log(' -', f);
});

// Load theme assets
const env = loadEnv('', '');
const themePath = env.VITE_THEME_PATH + '/' + env.VITE_THEME_NAME;
const themeConfigPath = './' + themePath + '/vite.config.js';
console.info('Load config:', themeConfigPath);
const themeConfig = await import(themeConfigPath).then(c => c.default);
// Merge theme + core
var inputFiles = customBuildFiles.concat(themeConfig.laravel.input);
var refreshFiles = customBuildFiles.concat(themeConfig.laravel.refresh);
var plugins = themeConfig.plugins;

// Public themes: CMS_ASSET_MODE=all (default) builds / watches every theme, single + CMS_ASSET_THEME only that one.
const assetProfile = resolveAssetProfile();
console.log('Asset mode:', assetProfile.mode === 'all' ? 'all themes' : 'single theme ' + assetProfile.theme + (assetProfile.dependencies.length ? ' (+ ' + assetProfile.dependencies.join(', ') + ')' : ''));
const ignoredThemeSources = excludedThemeWatchGlobs(assetProfile);

// Load module assets
var modules = JSON.parse(fs.readFileSync('modules_statuses.json'));
const buildProject = process.env.CMS_BUILD_PROJECT;
if (buildProject !== undefined) {
  if (!Object.hasOwn(projectProfiles, buildProject)) {
    throw new Error(`Unknown build project: ${buildProject}`);
  }
  modules = Object.fromEntries(projectProfiles[buildProject].map(name => [name, true]));
  console.log('Build project:', buildProject);
}
for (var key in modules) {
  if (modules[key]) {
    try {
      var moduleConfigPath = './Modules/' + key + '/vite.config.js';
      console.log('Load config:', moduleConfigPath);
      var moduleConfig = await import(moduleConfigPath).then(c => c.buildConfig);
      if (moduleConfig) {
        // Merge module
        inputFiles = inputFiles.concat(moduleConfig.laravel.input);
        refreshFiles = inputFiles.concat(moduleConfig.laravel.refresh);
        plugins = plugins.concat(moduleConfig.plugins);
      } else {
        // Invalid config
        console.log(' - Not found config');
      }
    } catch (error) {
      if (buildProject !== undefined) throw error;
      console.log('Không thể build Module', key);
      console.error(error);
    }
  }
}

// Copyright banner: chunks of a public theme get that theme's unit; shared chunks use the unit of CMS_UNIT_KEY.
const copyrightUnits = {
  'ban-de-an': 'Ban Đề án · Trường Đại học Công nghệ Nam Bình Dương',
  'nam-binh-duong': 'Trường Đại học Công nghệ Nam Bình Dương',
  default: 'Trung tâm Khuyến nông và Dịch vụ Nông nghiệp thành phố Cần Thơ'
};
const unitEnv = loadEnv('', process.cwd(), 'CMS_UNIT_');
const fallbackUnit = copyrightUnits[unitEnv.CMS_UNIT_KEY] ?? copyrightUnits.default;
function copyrightBanner(chunk) {
  const moduleIds = [chunk.facadeModuleId, ...(chunk.moduleIds ?? [])].filter(Boolean).map(id => id.replace(/\\/g, '/'));
  const themeKey = Object.keys(copyrightUnits).find(key => moduleIds.some(id => id.includes('/screens/' + key + '/')));
  return '/*! © ' + (themeKey ? copyrightUnits[themeKey] : fallbackUnit) + '. Nghiêm cấm sao chép khi chưa được phép. */';
}

// output.banner only covers JS chunks and minification can move it below hoisted imports:
// put the comment first in every JS chunk and prepend it to every CSS file (unit taken from the chunk that imports the CSS).
const cssOwners = new Map();
const copyrightPlugin = {
  name: 'copyright-banner',
  enforce: 'post',
  buildStart() {
    cssOwners.clear();
  },
  // Recorded here because Vite drops the empty JS chunk of a pure CSS entry before generateBundle.
  renderChunk(code, chunk) {
    for (const cssFile of chunk.viteMetadata?.importedCss ?? []) {
      cssOwners.set(cssFile, [...(cssOwners.get(cssFile) ?? []), chunk.facadeModuleId, ...chunk.moduleIds]);
    }
    return null;
  },
  generateBundle(options, bundle) {
    for (const file of Object.values(bundle)) {
      if (file.type === 'chunk') {
        const banner = copyrightBanner(file);
        if (!file.code.startsWith(banner)) file.code = banner + '\n' + file.code.replace(banner, '');
      } else if (file.fileName.endsWith('.css')) {
        const source = typeof file.source === 'string' ? file.source : new TextDecoder().decode(file.source);
        if (!source.startsWith('/*! ©')) file.source = copyrightBanner({ moduleIds: cssOwners.get(file.fileName) ?? [] }) + '\n' + source;
      }
    }
  }
};

const globalConfig = {
  ...(ignoredThemeSources.length ? { server: { watch: { ignored: ignoredThemeSources } } } : {}),
  plugins: [
    laravel({
      input: inputFiles,
      refresh: refreshFiles
    })
  ].concat(plugins, copyrightPlugin),
  build: {
    sourcemap: false,
    rollupOptions: {
      output: {
        banner: copyrightBanner
      }
    }
  }
};

export default defineConfig(globalConfig);
