export function projectDatabaseEnvironment(profile, source) {
  if (profile !== 'cms' && profile !== 'khung') throw new Error('Unknown project.');
  return source;
}
