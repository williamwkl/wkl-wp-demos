// Broken-link check on a static export: every href/src/srcset/url() that points inside the export must exist.
// usage: node scripts/linkcheck.mjs dist/<demo>
import { readdirSync, readFileSync, statSync, existsSync } from 'node:fs';
import path from 'node:path';
const root = path.resolve(process.argv[2]);
const walk = d => readdirSync(d).flatMap(f => { const p = path.join(d, f); return statSync(p).isDirectory() ? walk(p) : [p]; });
const files = walk(root).filter(f => /\.(html|css)$/.test(f));
const broken = new Set();
for (const f of files) {
  const t = readFileSync(f, 'utf8');
  const refs = [...t.matchAll(/(?:href|src|action)=["']([^"'#]+)["']|url\(\s*["']?([^"')]+)["']?\s*\)/g)].map(m => m[1] || m[2]);
  for (const m of t.matchAll(/srcset=["']([^"']+)["']/g)) m[1].split(',').forEach(s => refs.push(s.trim().split(/\s+/)[0]));
  for (let r of refs) {
    if (!r || /^(https?:|mailto:|tel:|data:|\/\/|javascript:)/.test(r)) continue;
    r = r.split('?')[0].split('#')[0];
    let p = r.startsWith('/') ? path.join(root, '..', r) : path.resolve(path.dirname(f), r);
    if (existsSync(p) && statSync(p).isDirectory()) p = path.join(p, 'index.html');
    if (!existsSync(p)) broken.add(path.relative(root, f) + ' -> ' + r);
  }
}
console.log(files.length, 'files,', broken.size, 'broken refs');
[...broken].slice(0, 15).forEach(b => console.log('  ' + b));
process.exitCode = broken.size ? 1 : 0;
