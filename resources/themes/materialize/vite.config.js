import { loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import html from '@rollup/plugin-html';
import { glob } from 'glob';
import dependencies from './dependencies.js';

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

function filterUnique(value, index, array) {
  return array.indexOf(value) === index;
}

/**
 * Js Files
 */
// Page JS Files
const pageJsFiles = GetFilesArray(themePath + '/assets/js/*.js');

const nestedPageJsFiles = GetFilesArray(themePath + '/assets/js/**/*.js');

// Processing Vendor JS Files
const vendorJsFiles = GetFilesArray(themePath + '/assets/vendor/js/*.js');

// Processing Libs JS Files
const LibsJsFiles = [];
/**
 * Scss Files
 */
// Processing Core, Themes & Pages Scss Files
const CoreScssFiles = GetFilesArray(themePath + '/assets/vendor/scss/**/!(_)*.scss');
const LibsScssFiles = [];
const LibsCssFiles = [];

for (let lib of dependencies.vendorLibs) {
  let jsFiles = GetFilesArray(themePath + '/assets/vendor/libs/' + lib + '/*.js');
  let scssFiles = GetFilesArray(themePath + '/assets/vendor/libs/' + lib + '/!(_)*.scss');
  let cssFiles = GetFilesArray(themePath + '/assets/vendor/libs/' + lib + '/*.css');
  for (let f of jsFiles) {
    LibsJsFiles.push(f);
  }
  for (let f of scssFiles) {
    LibsScssFiles.push(f);
  }
  for (let f of cssFiles) {
    LibsCssFiles.push(f);
  }
}

// Processing Fonts Scss Files
const FontsScssFiles = GetFilesArray(themePath + '/assets/vendor/fonts/**/!(_)*.scss');

/**
 * @ttdat Custom file
 */
const CustomCssFiles = [themePath + '/assets/css/demo.css', themePath + '/assets/css/main.css'];

const CustomJsFiles = [themePath + '/js/app.js'];

// Processing Window Assignment for Libs like jKanban, pdfMake
// function libsWindowAssignment() {
//   return {
//     name: 'libsWindowAssignment',

//     transform(src, id) {
//       if (id.includes('jkanban.js')) {
//         return src.replace('this.jKanban', 'window.jKanban');
//       } else if (id.includes('vfs_fonts')) {
//         return src.replaceAll('this.pdfMake', 'window.pdfMake');
//       }
//     }
//   };
// }

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
  ...FontsScssFiles
].filter(filterUnique);

console.log('Module theme');
for (let lib of dependencies.vendorLibs) {
  // console.log('  #' + lib);
}
buildFiles.forEach(function (item, i) {
  // console.log(' -', item);
});

export default {
  laravel: {
    input: buildFiles,
    refresh: []
  },
  plugins: [
    // laravel({
    //   input: buildFiles,
    //   refresh: false
    // }),
    // html(),
    // libsWindowAssignment()
  ]
};
