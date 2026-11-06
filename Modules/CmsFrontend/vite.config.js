import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { frontendInputs, resolveAssetProfile } from '../../scripts/theme-profiles.mjs';

// Shared assets + the themes of the asset profile (CMS_ASSET_MODE / CMS_ASSET_THEME, see scripts/theme-profiles.mjs).
// Without those variables this is every file under assets/{js,css,sass}, exactly as before.
const assetProfile = resolveAssetProfile();
const buildFiles = frontendInputs(assetProfile);

console.log('Module CmsFrontend:', assetProfile.mode === 'all' ? 'all themes' : 'theme ' + assetProfile.buildThemes.join(' + '));
buildFiles.forEach(f => {
  // console.log(' -', f);
});

export const buildConfig = {
  laravel: {
    input: buildFiles,
    refresh: buildFiles
  },
  plugins: []
};

export default defineConfig({
  build: {
    outDir: '../../public/build-cmsfrontend',
    emptyOutDir: true,
    manifest: true
  },
  plugins: [
    laravel({
      publicDirectory: '../../public',
      buildDirectory: 'build-cmsfrontend',
      input: buildFiles,
      refresh: buildFiles
    })
  ]
});
