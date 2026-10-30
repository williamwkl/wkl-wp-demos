// Consistency check for the public base URL across the whole published site (dist/).
// Every page's canonical, og:url and JSON-LD URLs, and every sitemap <loc>, must use the active BASE
// (scripts/site-base.mjs), and no local origin may leak.
// usage: node scripts/check-base.mjs [dist]    exit 1 on any mismatch
import { readdirSync, readFileSync, statSync } from 'node:fs';
import path from 'node:path';
import { BASE } from './site-base.mjs';
export function checkBase(dist = 'dist', { skipRoot = false } = {}) {
  const walk = d => readdirSync(d).flatMap(f => { const p = path.join(d, f); return statSync(p).isDirectory() ? (/^(wp-content|wp-includes)$/.test(f) ? [] : walk(p)) : [p]; });
  const files = walk(dist).filter(f => /\.(html|xml|txt)$/.test(f) && !(skipRoot && path.dirname(f) === path.resolve(dist) || skipRoot && path.dirname(f) === dist));
  const bad = [];
  for (const f of files) {
    const t = readFileSync(f, 'utf8'), rel = path.relative(dist, f);
    if (/127\.0\.0\.1|localhost:/.test(t)) bad.push(`${rel}: local origin leaked`);
    if (f.endsWith('.html')) {
      const noindexRedirect = /http-equiv="refresh"/.test(t);
      const canon = (t.match(/<link rel="canonical" href="([^"]+)"/) || [])[1];
      if (!canon && !noindexRedirect && !/name=['"]robots['"][^>]*noindex/.test(t)) bad.push(`${rel}: no canonical`);
      if (canon && !canon.startsWith(BASE + '/')) bad.push(`${rel}: canonical ${canon}`);
      const og = (t.match(/property="og:url" content="([^"]+)"/) || [])[1];
      if (og && !og.startsWith(BASE + '/')) bad.push(`${rel}: og:url ${og}`);
      for (const block of t.matchAll(/<script[^>]*application\/ld\+json[^>]*>([\s\S]*?)<\/script>/g)) {
        for (const u of block[1].replace(/\\\//g, '/').matchAll(/"(?:@id|url|item)":"(https?:\/\/[^"]+)"/g)) {
          const host = new URL(u[1]).host;
          if (host === new URL(BASE).host) { if (!u[1].startsWith(BASE + '/')) bad.push(`${rel}: JSON-LD ${u[1]}`); }
        }
      }
    }
    if (f.endsWith('sitemap.xml')) for (const m of t.matchAll(/<loc>([^<]+)<\/loc>/g)) if (!m[1].startsWith(BASE + '/')) bad.push(`${rel}: sitemap loc ${m[1]}`);
  }
  return { files: files.length, bad };
}
if (import.meta.url === `file://${process.argv[1]}`) {
  const { files, bad } = checkBase(process.argv[2] || 'dist');
  console.log(`${files} files checked against ${BASE}: ${bad.length ? bad.length + ' problems' : 'consistent'}`);
  bad.slice(0, 20).forEach(b => console.log('  ' + b));
  process.exitCode = bad.length ? 1 : 0;
}
