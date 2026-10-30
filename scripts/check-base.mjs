// Consistency check for the public base URL across the whole published site (dist/).
// Must use the active BASE (scripts/site-base.mjs): every page's canonical, og:url, og:image/twitter:image, every JSON-LD
// URL on our host, every sitemap <loc>, every robots.txt Sitemap: line. Must not exist: a CNAME file anywhere, a local
// origin, or an absolute URL to any host outside THIRD_PARTY (and each third party only where it is expected).
// usage: node scripts/check-base.mjs [dist]    exit 1 on any mismatch
import { readdirSync, readFileSync, statSync } from 'node:fs';
import path from 'node:path';
import { BASE } from './site-base.mjs';
// Known third parties: host -> files where they may appear (regex on the dist-relative path).
export const THIRD_PARTY = {
  'www.w3.org': /./, 'www.sitemaps.org': /sitemap\.xml$|\.xml$/, 'schema.org': /./, // namespaces and JSON-LD @context
  'yoast.com': /./, // Yoast SEO's HTML comment
  's.w.org': /^harbor-pine-naive\//, 'fonts.googleapis.com': /^harbor-pine-naive\//, // the deliberately unoptimized "before" build
  'openfontlicense.org': /OFL\.txt$/, 'github.com': /OFL\.txt$/, // font license texts
};
export function checkBase(dist = 'dist', { skipRoot = false } = {}) {
  const walk = d => readdirSync(d).flatMap(f => { const p = path.join(d, f); return statSync(p).isDirectory() ? (/^(wp-content|wp-includes)$/.test(f) ? [] : walk(p)) : [p]; });
  const all = walk(dist);
  const files = all.filter(f => /\.(html|xml|txt)$/.test(f) && !(skipRoot && path.dirname(f) === path.resolve(dist) || skipRoot && path.dirname(f) === dist));
  const bad = [];
  const ourHost = new URL(BASE).host;
  for (const f of all) if (path.basename(f) === 'CNAME') bad.push(`${path.relative(dist, f)}: CNAME present (there is no custom domain)`);
  for (const f of files) {
    const t = readFileSync(f, 'utf8'), rel = path.relative(dist, f);
    if (/127\.0\.0\.1|localhost:/.test(t)) bad.push(`${rel}: local origin leaked`);
    for (const m of t.replace(/\\\//g, '/').matchAll(/(?:https?:)?\/\/([a-z0-9][a-z0-9.-]*\.[a-z]{2,})(?=[\/"'\s)<>:?#,]|$)/gi)) {
      const host = m[1].toLowerCase();
      if (host === ourHost || host === '127.0.0.1') continue;
      if (/(^|\.)example\.(com|org|net)$/.test(host)) continue; // fictional contact/site addresses
      if (!THIRD_PARTY[host]) bad.push(`${rel}: absolute URL to unlisted host ${host}`);
      else if (!THIRD_PARTY[host].test(rel)) bad.push(`${rel}: ${host} is not expected in this file`);
    }
    if (path.basename(f) === 'robots.txt') {
      const maps = [...t.matchAll(/^Sitemap:\s*(\S+)/gim)].map(m => m[1]);
      if (!maps.length) bad.push(`${rel}: no Sitemap line`);
      for (const u of maps) if (!u.startsWith(BASE + '/')) bad.push(`${rel}: Sitemap ${u}`);
    }
    if (f.endsWith('.html')) {
      const noindexRedirect = /http-equiv="refresh"/.test(t);
      const canon = (t.match(/<link rel="canonical" href="([^"]+)"/) || [])[1];
      if (!canon && !noindexRedirect && !/name=['"]robots['"][^>]*noindex/.test(t)) bad.push(`${rel}: no canonical`);
      if (canon && !canon.startsWith(BASE + '/')) bad.push(`${rel}: canonical ${canon}`);
      const og = (t.match(/property="og:url" content="([^"]+)"/) || [])[1];
      if (og && !og.startsWith(BASE + '/')) bad.push(`${rel}: og:url ${og}`);
      for (const m of t.matchAll(/(?:property|name)="(og:image(?::secure_url)?|twitter:image)" content="([^"]+)"/g)) if (!m[2].startsWith(BASE + '/')) bad.push(`${rel}: ${m[1]} ${m[2]}`);
      for (const block of t.matchAll(/<script[^>]*application\/ld\+json[^>]*>([\s\S]*?)<\/script>/g)) {
        for (const u of block[1].replace(/\\\//g, '/').matchAll(/"([@\w]+)":\s*"(https?:\/\/[^"]+)"/g)) { // every URL-valued key
          const host = new URL(u[2]).host;
          if (host === ourHost) { if (!u[2].startsWith(BASE + '/')) bad.push(`${rel}: JSON-LD ${u[1]} ${u[2]}`); }
          else if (u[1] !== '@context' && host !== 'schema.org' && !/(^|\.)example\.(com|org|net)$/.test(host)) bad.push(`${rel}: JSON-LD ${u[1]} points off-site: ${u[2]}`);
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
