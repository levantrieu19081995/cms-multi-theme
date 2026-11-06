import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { glob } from 'glob';
import path from 'path';
import url from 'url';

/**
 * Get Files from a directory
 * @param {string} query
 * @returns array
 */
function GetFilesArray(query) {
  return glob.sync(query);
}

const __filename = url.fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const nestedJsFiles = GetFilesArray(__dirname + '/resources/assets/js/**/*.js');
const nestedStyleFiles = GetFilesArray(__dirname + '/resources/assets/css/**/*.css');
const nestedStyle2Files = GetFilesArray(__dirname + '/resources/assets/sass/**/*.scss');

const buildFiles = [...nestedJsFiles, ...nestedStyleFiles, ...nestedStyle2Files];

console.log('Module CmsBackend');
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
    outDir: '../../public/build-cmsbackend',
    emptyOutDir: true,
    manifest: true
  },
  plugins: [
    laravel({
      publicDirectory: '../../public',
      buildDirectory: 'build-cmsbackend',
      input: buildFiles,
      refresh: buildFiles
    })
  ]
});

// Scen all resources for assets file. Return array
//function getFilePaths(dir) {
//    const filePaths = [];
//
//    function walkDirectory(currentPath) {
//        const files = readdirSync(currentPath);
//        for (const file of files) {
//            const filePath = join(currentPath, file);
//            const stats = statSync(filePath);
//            if (stats.isFile() && !file.startsWith('.')) {
//                const relativePath = 'Modules/CmsBackend/'+relative(__dirname, filePath);
//                filePaths.push(relativePath);
//            } else if (stats.isDirectory()) {
//                walkDirectory(filePath);
//            }
//        }
//    }
//
//    walkDirectory(dir);
//    return filePaths;
//}

//const __filename = fileURLToPath(import.meta.url);
//const __dirname = dirname(__filename);

//const assetsDir = join(__dirname, 'resources/assets');
//export const paths = getFilePaths(assetsDir);

//export const paths = [
//    'Modules/CmsBackend/resources/assets/sass/app.scss',
//    'Modules/CmsBackend/resources/assets/js/app.js',
//];
