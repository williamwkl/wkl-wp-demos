// Builds playground/<demo>.json: a self-contained Playground blueprint that pulls the theme, mu-plugins and data
// from the public GitHub repo (raw URLs) instead of local mounts, then logs in so the WordPress admin opens.
// usage: node scripts/public-blueprints.mjs [rawBase]
//   rawBase default: https://raw.githubusercontent.com/williamwkl/wkl-wp-demos/v1.0.0
//   IMPORTANT: replace v1.0.0 with the actual release tag or commit SHA at publish time and re-run, so a public link
//   always loads exactly the reviewed files (a branch name like main would change under the link).
// Share link once the repo is public: https://playground.wordpress.net/#<url-encoded raw URL of playground/<demo>.json>
//   or https://playground.wordpress.net/?blueprint-url=<raw URL of playground/<demo>.json>
import { readFileSync, writeFileSync, readdirSync, statSync, mkdirSync } from 'node:fs';
import path from 'node:path';
const RAW = (process.argv[2] || 'https://raw.githubusercontent.com/williamwkl/wkl-wp-demos/v1.0.0').replace(/\/$/, '');
const walk = d => readdirSync(d).flatMap(f => { const p = path.join(d, f); return statSync(p).isDirectory() ? walk(p) : [p]; });
mkdirSync('playground', { recursive: true });
const variants = [['saltline', 'blueprint.json', 'saltline'], ['harbor-pine', 'blueprint.json', 'harbor-pine'], ['harbor-pine', 'blueprint-naive.json', 'harbor-pine-naive'], ['makers', 'blueprint.json', 'makers']];
for (const [demo, file, out] of variants) {
  const bp = JSON.parse(readFileSync(`demos/${demo}/${file}`, 'utf8'));
  const writes = [];
  const map = [['theme', `/wordpress/wp-content/themes/${demo}`], ['mu-plugins', '/wordpress/wp-content/mu-plugins'], ['data', '/wordpress/wp-content/demo-data']];
  for (const [dir, target] of map) {
    const root = `demos/${demo}/${dir}`;
    try { statSync(root); } catch { continue; }
    if (dir === 'theme') writes.push({ step: 'mkdir', path: target });
    for (const f of walk(root)) {
      if (/img-naive/.test(f) && !/naive/.test(out)) continue;
      const rel = path.relative(root, f);
      const dest = `${target}/${rel}`;
      writes.push({ step: 'mkdir', path: path.posix.dirname(dest) });
      writes.push({ step: 'writeFile', path: dest, data: { resource: 'url', url: `${RAW}/${f}` } });
    }
  }
  const seen = new Set();
  const dedup = writes.filter(s => s.step !== 'mkdir' || (!seen.has(s.path) && seen.add(s.path)));
  const steps = bp.steps.filter(s => s.step !== 'login');
  const firstPlugin = steps.findIndex(s => s.step === 'installPlugin' || s.step === 'installTheme');
  steps.splice(firstPlugin < 0 ? 1 : firstPlugin, 0, ...dedup);
  steps.push({ step: 'login', username: 'admin', password: 'password' });
  writeFileSync(`playground/${out}.json`, JSON.stringify({ ...bp, landingPage: '/wp-admin/', steps }, null, 2) + '\n');
  console.log(out, dedup.filter(s => s.step === 'writeFile').length, 'files');
}
