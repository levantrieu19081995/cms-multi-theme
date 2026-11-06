// npm run dev:theme -- <theme>   |   npm run dev:all-themes
// Starts Laravel and Vite together (scripts/dev-all.ps1) with the asset profile in the PROCESS environment of that session
// only: .env and the database are never touched, so the theme selected in the administration is left as it is.
import { spawn } from 'node:child_process';
import path from 'node:path';
import { resolveAssetProfile, root, themeKeys } from './theme-profiles.mjs';

const [target = 'all'] = process.argv.slice(2);

try {
  if (target !== 'all' && !themeKeys.includes(target)) {
    throw new Error(`Choose "all" or one of: ${themeKeys.join(', ')}. Example: npm run dev:theme -- nam-binh-duong`);
  }
  const profile = resolveAssetProfile({ CMS_ASSET_MODE: target === 'all' ? 'all' : 'single', CMS_ASSET_THEME: target === 'all' ? '' : target });
  console.log(profile.mode === 'all'
    ? 'Dev: all themes (the administrator can switch theme).'
    : `Dev: theme ${profile.theme}${profile.dependencies.length ? ` (+ ${profile.dependencies.join(', ')})` : ''} - the theme is fixed for this session.`);

  const args = ['-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', path.join(root, 'scripts', 'dev-all.ps1'), '-Mode', profile.mode];
  if (profile.theme) args.push('-Theme', profile.theme);
  const child = spawn('powershell', args, { stdio: 'inherit', cwd: root });
  // Ctrl+C reaches the whole console group: let dev-all.ps1 stop PHP and Vite itself, then leave with its exit code.
  process.on('SIGINT', () => {});
  child.on('exit', code => process.exit(code ?? 0));
} catch (error) {
  console.error(error.message);
  process.exitCode = 1;
}
