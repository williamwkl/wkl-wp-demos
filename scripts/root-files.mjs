// Writes the files that live at the root of the published site (dist/):
// robots.txt (only honoured at a domain root) and a sitemap index pointing at each demo's sitemap.xml.
// usage: node scripts/root-files.mjs [dist]
import { readFileSync, writeFileSync, existsSync, readdirSync, statSync } from 'node:fs';
import path from 'node:path';
const dist = process.argv[2] || 'dist';
import { CONFIG, BASE } from './site-base.mjs';
import { rmSync } from 'node:fs';
import { checkBase } from './check-base.mjs';
const base = BASE;
// Refuse to publish root files unless every demo export already uses this base.
const pre = checkBase(dist, { skipRoot: true });
if (pre.bad.length) {
  console.error(`Refusing to write root files: ${pre.bad.length} pages or sitemaps do not use ${BASE}.`);
  pre.bad.slice(0, 15).forEach(b => console.error('  ' + b));
  console.error('Rebuild the listed demos (scripts/build.sh <demo>) or the Health Check first');
  process.exit(1);
}
const demos = readdirSync(dist).filter(d => statSync(path.join(dist, d)).isDirectory() && existsSync(path.join(dist, d, 'sitemap.xml')) && !d.endsWith('-naive'));
rmSync(path.join(dist, 'CNAME'), { force: true }); // no custom domain: never publish a CNAME
// Landing page canonical follows the same base.
const idx = path.join(dist, 'index.html');
if (existsSync(idx)) writeFileSync(idx, readFileSync(idx, 'utf8').replace(/<link rel="canonical" href="[^"]*">/, `<link rel="canonical" href="${base}/">`));
// Root favicon (browsers request /favicon.ico at the domain root).
if (!existsSync(path.join(dist, 'favicon.ico')) && existsSync('scripts/assets/favicon.ico')) writeFileSync(path.join(dist, 'favicon.ico'), readFileSync('scripts/assets/favicon.ico'));
writeFileSync(path.join(dist, 'sitemap.xml'), '<?xml version="1.0" encoding="UTF-8"?>\n<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' +
  demos.map(d => `  <sitemap><loc>${base}/${d}/sitemap.xml</loc></sitemap>`).join('\n') + '\n</sitemapindex>\n');
writeFileSync(path.join(dist, 'robots.txt'), 'User-agent: *\nAllow: /\n\nSitemap: ' + base + '/sitemap.xml\n');
const post = checkBase(dist);
if (post.bad.length) { console.error('Root files inconsistent:', post.bad.slice(0, 5)); process.exit(1); }
console.log('base ' + base, '| sitemap index:', demos.join(', '));
