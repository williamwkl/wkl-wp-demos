// Writes the files that live at the root of the published site (dist/): CNAME for GitHub Pages,
// robots.txt (only honoured at a domain root) and a sitemap index pointing at each demo's sitemap.xml.
// usage: node scripts/root-files.mjs [dist]
import { readFileSync, writeFileSync, existsSync, readdirSync, statSync } from 'node:fs';
import path from 'node:path';
const dist = process.argv[2] || 'dist';
import { CONFIG, BASE } from './site-base.mjs';
import { rmSync } from 'node:fs';
const base = BASE;
const host = new URL(base).host;
const demos = readdirSync(dist).filter(d => statSync(path.join(dist, d)).isDirectory() && existsSync(path.join(dist, d, 'sitemap.xml')) && !d.endsWith('-naive'));
// CNAME only once the custom domain is live; publishing it earlier would redirect github.io to a domain that does not resolve.
if (CONFIG.domainLive) writeFileSync(path.join(dist, 'CNAME'), host + '\n'); else rmSync(path.join(dist, 'CNAME'), { force: true });
// Landing page canonical follows the same base.
const idx = path.join(dist, 'index.html');
if (existsSync(idx)) writeFileSync(idx, readFileSync(idx, 'utf8').replace(/<link rel="canonical" href="[^"]*">/, `<link rel="canonical" href="${base}/">`));
// Root favicon (browsers request /favicon.ico at the domain root).
if (!existsSync(path.join(dist, 'favicon.ico')) && existsSync('scripts/assets/favicon.ico')) writeFileSync(path.join(dist, 'favicon.ico'), readFileSync('scripts/assets/favicon.ico'));
writeFileSync(path.join(dist, 'sitemap.xml'), '<?xml version="1.0" encoding="UTF-8"?>\n<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' +
  demos.map(d => `  <sitemap><loc>${base}/${d}/sitemap.xml</loc></sitemap>`).join('\n') + '\n</sitemapindex>\n');
writeFileSync(path.join(dist, 'robots.txt'), 'User-agent: *\nAllow: /\n\nSitemap: ' + base + '/sitemap.xml\n');
console.log(CONFIG.domainLive ? 'CNAME ' + host : 'no CNAME (domainLive false), base ' + base, '| sitemap index:', demos.join(', '));
