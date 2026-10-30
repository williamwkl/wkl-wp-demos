// Static export: crawl a running local WordPress and write a relative-path mirror.
// usage: node scripts/export.mjs <origin> <outDir> [extraPath ...]
import { mkdir, writeFile, rm } from 'node:fs/promises';
import path from 'node:path';
const [origin, outDir, ...extra] = process.argv.slice(2);
const O = origin.replace(/\/$/, '');
const host = new URL(O).host;
const queue = ['/', ...extra];
const seen = new Set();
const SKIP = /^\/(wp-admin|wp-login\.php|xmlrpc\.php|wp-json|feed|comments\/feed)|\/feed\/?$|\/embed\/?$|\?/;
function toFile(p) {
  if (p.endsWith('/')) return p + 'index.html';
  if (!path.posix.extname(p)) return p + '/index.html';
  return p;
}
function relPrefix(p) {
  const depth = toFile(p).split('/').length - 2;
  return depth <= 0 ? './' : '../'.repeat(depth);
}
function enqueue(u) {
  try {
    const url = new URL(u, O + '/');
    if (url.host !== host) return;
    let p = decodeURIComponent(url.pathname);
    if (url.search && !/\.(css|js)$/.test(p)) return; // drop query pages; keep versioned assets
    if (SKIP.test(p)) return;
    if (!seen.has(p)) { seen.add(p); queue.push(p); }
  } catch {}
}
await rm(outDir, { recursive: true, force: true });
seen.add('/');
while (queue.length) {
  const p = queue.shift();
  const res = await fetch(O + p, { redirect: 'follow' });
  if (!res.ok) { console.warn('skip', res.status, p); continue; }
  const finalPath = new URL(res.url).pathname;
  if (finalPath !== p && !seen.has(finalPath)) { seen.add(finalPath); queue.push(finalPath); }
  const type = res.headers.get('content-type') || '';
  const file = path.join(outDir, toFile(p));
  await mkdir(path.dirname(file), { recursive: true });
  if (/text|javascript|json|xml|svg/.test(type)) {
    let body = await res.text();
    const esc = O.replace(/\//g, '\\/');
    for (const m of body.matchAll(/(?:href|src|content)=["']((?:https?:)?\/[^"'\s]*)["']|url\(\s*["']?([^"')]+)["']?\s*\)|srcset=["']([^"']+)["']|<loc>([^<]+)<\/loc>/g)) {
      const v = m[1] || m[2] || m[4];
      if (v) enqueue(new URL(v.replace(/&amp;/g, '&'), O + p).href);
      if (m[3]) m[3].split(',').forEach(s => enqueue(new URL(s.trim().split(/\s+/)[0], O + p).href));
    }
    // URLs inside inline JSON (escaped slashes), e.g. WordPress emoji settings.
    for (const m of body.matchAll(new RegExp(esc.replace(/[.\\/]/g, c => '\\' + c) + '(\\\\\/[^"\\s]+?\\.(?:js|css|svg|png|webp|woff2))', 'g'))) enqueue(O + m[1].replace(/\\\//g, '/'));
    // Elementor lazy-loads widget handler chunks by name at runtime; fetch them so the static copy has them.
    if (/javascript/.test(type)) for (const m of body.matchAll(/__elementorLoadChunk\(`([\w-]+)`\)/g)) enqueue(O + '/wp-content/plugins/elementor/assets/js/chunks/' + m[1] + '.min.js');
    if (/css/.test(type)) {
      // css url() relative to the css file: keep relative
    } else if (/html/.test(type)) {
      const pre = relPrefix(p);
      const PUB = (process.env.PUBLIC_BASE || '').replace(/\/$/, '');
      // Canonical, og:url and JSON-LD keep absolute public URLs; everything else becomes relative.
      if (PUB) body = body.replace(new RegExp('(rel="canonical" href="|property="og:url" content=")' + O.replace(/[.\/]/g, m => '\\' + m), 'g'), '$1' + PUB)
        .replace(/(<script[^>]*application\/ld\+json[^>]*>)([\s\S]*?)(<\/script>)/g, (m, a, j, c) => a + j.split(O).join(PUB).split(esc).join(PUB.replace(/\//g, '\\/')) + c);
      body = body.split(encodeURIComponent(O)).join(encodeURIComponent(PUB || O))
        .replace(new RegExp(`<link rel=.dns-prefetch. href=.//${new URL(O).hostname.replace(/\./g, '\\.')}.[^>]*>\\n?`, 'g'), '');
      body = body.split(O + '/').join(pre).split(esc + '\\/').join(pre.replace(/\//g, '\\/')).split(O + '"').join(pre + '"')
        .replace(/(href|src|action)=(["'])\/(?!\/)/g, `$1=$2${pre}`)
        .replace(/(srcset=["'])([^"']+)/g, (m, a, v) => a + v.replace(/(^|,\s*)\/(?!\/)/g, `$1${pre}`))
        .replace(/\?ver=[^"'&\s]+/g, '');
    } else if (/xml/.test(type)) {
      body = body.split(O).join(process.env.PUBLIC_BASE?.replace(/\/$/, '') || O);
    }
    await writeFile(file, body);
  } else {
    await writeFile(file, Buffer.from(await res.arrayBuffer()));
  }
}
// sitemap.xml + robots.txt for the static copy (WordPress serves its sitemap dynamically)
const PUBB = (process.env.PUBLIC_BASE || O).replace(/\/$/, '');
const pages = [...seen].filter(p => /\/$/.test(p) && !/\/wp-content\//.test(p)).sort();
await writeFile(path.join(outDir, 'sitemap.xml'), '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' + pages.map(p => `  <url><loc>${PUBB}${p}</loc></url>`).join('\n') + '\n</urlset>\n');
await writeFile(path.join(outDir, 'robots.txt'), `User-agent: *\nAllow: /\n\nSitemap: ${PUBB}/sitemap.xml\n`);
console.log('exported', seen.size, 'paths to', outDir);
