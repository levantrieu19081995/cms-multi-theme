import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { build } from 'vite';
import { projectProfiles } from './project-profiles.mjs';
import { themeKeys } from './theme-profiles.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const [profile, ...extra] = process.argv.slice(2);

try {
  if (!Object.hasOwn(projectProfiles, profile) || extra.length) {
    throw new Error('Chon cms hoac khung.');
  }
  const exportRoot = path.join(root, 'build-exports');
  const outDir = path.resolve(exportRoot, profile, 'build');
  const relativeTarget = path.relative(exportRoot, outDir);
  if (relativeTarget.startsWith('..') || path.isAbsolute(relativeTarget)) {
    throw new Error('Thu muc build nam ngoai vung xuat du an.');
  }
  // Vite can clear only this project's export directory, never public/build.
  process.env.CMS_BUILD_PROJECT = profile;
  await build({
    root,
    configFile: path.join(root, 'vite.config.js'),
    build: { outDir, emptyOutDir: true }
  });
  const manifest = JSON.parse(fs.readFileSync(path.join(outDir, 'manifest.json'), 'utf8'));
  const entries = Object.keys(manifest);
  const selected = projectProfiles[profile];
  for (const name of selected) {
    if (!entries.some(key => key.startsWith(`Modules/${name}/`))) {
      throw new Error(`Manifest thieu module ${name}.`);
    }
  }
  if (!entries.some(key => key.startsWith('resources/'))) throw new Error('Manifest thieu phan khung.');
  const unwanted = entries.filter(key => key.startsWith('Modules/') && !selected.some(name => key.startsWith(`Modules/${name}/`)));
  if (unwanted.length) throw new Error(`Manifest chua module ngoai du an: ${unwanted.join(', ')}`);
  for (const chunk of Object.values(manifest)) {
    for (const file of [chunk.file, ...(chunk.css ?? []), ...(chunk.assets ?? [])].filter(Boolean)) {
      if (!fs.existsSync(path.join(outDir, file))) throw new Error(`Thieu asset ${file}.`);
    }
  }
  if (profile === 'cms') {
    // Same metadata as build:all-themes, so Laravel knows this build holds every theme.
    fs.writeFileSync(path.join(outDir, 'theme-build.json'), JSON.stringify({ mode: 'all', themes: themeKeys, generatedAt: new Date().toISOString() }, null, 2) + '\n');
  }
  console.log(`\nBuild hoan tat: ${outDir}`);
  console.log('Da kiem tra manifest, phan khung va cac asset.');
  console.log('De trien khai, dua noi dung thu muc build nay vao public/build cua dung du an.');
  console.log('Trang thai module va public/build local khong thay doi.');
} catch (error) {
  console.error(error.message);
  process.exitCode = 1;
}
