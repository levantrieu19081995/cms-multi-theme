import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import html from '@rollup/plugin-html';
import { viteStaticCopy } from 'vite-plugin-static-copy'
import { glob } from 'glob';
import path from 'node:path'

const env = loadEnv('', '');
const themePath = env.VITE_THEME_PATH + '/' + env.VITE_THEME_NAME;

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */
function GetFilesArray(query) {
  return glob.sync(query);
}

/**
 * Js Files
 */
// Page JS Files
const pageJsFiles = GetFilesArray(themePath + '/assets/js/*.js', { nodir: true });

const nestedPageJsFiles = GetFilesArray(themePath + '/assets/js/**/*.js', { nodir: true });

// Processing Vendor JS Files
const vendorJsFiles = GetFilesArray(themePath + '/assets/vendor/js/*.js', { nodir: true });

// Processing Libs JS Files
const LibsJsFiles = GetFilesArray(themePath + '/assets/vendor/libs/**/*.js', { nodir: true });

/**
 * Scss Files
 */
// Processing Core, Themes & Pages Scss Files
const CoreScssFiles = GetFilesArray(themePath + '/assets/vendor/scss/**/!(_)*.scss', { nodir: true });

// Processing Libs Scss & Css Files
const LibsScssFiles = GetFilesArray(themePath + '/assets/vendor/libs/**/!(_)*.scss', { nodir: true });
const LibsCssFiles = GetFilesArray(themePath + '/assets/vendor/libs/**/*.css', { nodir: true });

// Processing Fonts Scss Files
const FontsScssFiles = GetFilesArray(themePath + '/assets/vendor/fonts/**/!(_)*.scss', { nodir: true });

/**
 * @ttdat Custom file
 */
const CustomCssFiles = [
  themePath + '/css/app.css',
];

const CustomJsFiles = [
  themePath + '/js/app.js'
];

const buildFiles = [
  ...CustomCssFiles,
  ...CustomJsFiles,
  ...pageJsFiles,
  ...nestedPageJsFiles,
  ...vendorJsFiles,
  ...LibsJsFiles,
  ...CoreScssFiles,
  ...LibsScssFiles,
  ...LibsCssFiles,
  ...FontsScssFiles,
];

console.log('Build:');
buildFiles.forEach(function (item, i) {
  console.log(' - ',item);
});

const copyTargets = [
  {
    src: 'resources/themes/' + env.VITE_THEME_NAME + '/assets',
    dest: path.resolve('public'),
  },
];
console.log('Build:');
copyTargets.forEach((item) => {
  console.log(' - From ' + item.src + ' to ' + item.dest);
})

export default defineConfig({
  plugins: [
    viteStaticCopy({
      targets: copyTargets,
    }),
    laravel({
      input: buildFiles,
      refresh: true
    }),
    html(),
  ]
});
