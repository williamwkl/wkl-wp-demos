// Writes static redirect stubs for renamed listing slugs (meta refresh + canonical + noindex), e.g. into dist/makers.
// usage: node scripts/redirect-stubs.mjs <distDir> <redirects.csv> [publicBase]   (default: site.config.json publicBase + /<demo>)
//   redirects.csv rows: old_path,new_path   (site-relative, trailing slash)
import { readFileSync, mkdirSync, writeFileSync } from 'node:fs';
import path from 'node:path';
const [dist, csv, pubArg] = process.argv.slice(2);
const CONFIG = JSON.parse(readFileSync(new URL('../site.config.json', import.meta.url), 'utf8'));
const pub = pubArg || CONFIG.publicBase.replace(/\/$/, '') + '/' + path.basename(dist);
const rows = readFileSync(csv, 'utf8').trim().split('\n').slice(1).map(l => l.split(','));
for (const [from, to] of rows) {
  const depth = from.split('/').filter(Boolean).length;
  const rel = '../'.repeat(depth) + to.replace(/^\//, '');
  const abs = pub.replace(/\/$/, '') + to;
  mkdirSync(path.join(dist, from), { recursive: true });
  writeFileSync(path.join(dist, from, 'index.html'), `<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Moved</title><meta name="robots" content="noindex, follow"><link rel="canonical" href="${abs}"><meta http-equiv="refresh" content="0; url=${rel}"></head><body><p>This listing moved to <a href="${rel}">${to}</a>.</p></body></html>\n`);
}
console.log(rows.length, 'redirect stubs');
