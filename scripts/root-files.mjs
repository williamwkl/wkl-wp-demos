// Writes the files that live at the root of the published site (dist/): CNAME for GitHub Pages,
// robots.txt (only honoured at a domain root) and a sitemap index pointing at each demo's sitemap.xml.
// usage: node scripts/root-files.mjs [dist]
import { readFileSync, writeFileSync, existsSync, readdirSync, statSync } from 'node:fs';
import path from 'node:path';
const dist = process.argv[2] || 'dist';
const CONFIG = JSON.parse(readFileSync('site.config.json', 'utf8'));
const base = CONFIG.publicBase.replace(/\/$/, '');
const host = new URL(base).host;
const demos = readdirSync(dist).filter(d => statSync(path.join(dist, d)).isDirectory() && existsSync(path.join(dist, d, 'sitemap.xml')) && !d.endsWith('-naive'));
if (!host.endsWith('github.io')) writeFileSync(path.join(dist, 'CNAME'), host + '\n');
writeFileSync(path.join(dist, 'sitemap.xml'), '<?xml version="1.0" encoding="UTF-8"?>\n<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' +
  demos.map(d => `  <sitemap><loc>${base}/${d}/sitemap.xml</loc></sitemap>`).join('\n') + '\n</sitemapindex>\n');
writeFileSync(path.join(dist, 'robots.txt'), 'User-agent: *\nAllow: /\n\nSitemap: ' + base + '/sitemap.xml\n');
console.log('CNAME', host, '| sitemap index:', demos.join(', '));
