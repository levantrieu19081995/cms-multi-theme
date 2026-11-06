export function projectUrl(path) {
  const prefix = (document.querySelector('meta[name="cms-route-prefix"]')?.content || '').replace(/\/$/, '');
  return `${prefix}/${path.replace(/^\//, '')}`;
}
