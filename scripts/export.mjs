// Static export: crawl a running local WordPress and write a relative-path mirror.
// usage: node scripts/export.mjs <origin> <outDir> [extraPath ...]
import { mkdir, writeFile, rm } from 'node:fs/promises';
import path from 'node:path';
const [origin, outDir, ...extra] = process.argv.slice(2);
// Public base URL comes from site.config.json (single source of truth); PUBLIC_BASE env still overrides.
import { readFileSync as _rf } from 'node:fs';
const CONFIG = JSON.parse(_rf(new URL('../site.config.json', import.meta.url), 'utf8'));
const DEMO_CFG = CONFIG.demos[path.basename(outDir)] || {};
// Per-demo static adaptations: server-only pages to leave out, and links to point at their static stand-ins.
const EXCLUDE = new Set(DEMO_CFG.exclude || []);
const REWRITE = DEMO_CFG.rewrite || {};
if (!process.env.PUBLIC_BASE) process.env.PUBLIC_BASE = CONFIG.publicBase.replace(/\/$/, '') + '/' + path.basename(outDir) + '/';
const O = origin.replace(/\/$/, '');
const host = new URL(O).host;
const queue = ['/', '/demo-form/', ...extra];
for (const p of DEMO_CFG.include || []) if (!queue.includes(p)) queue.push(p); // pages only reachable through rewritten links // /demo-form/ is only reachable through form actions
const seen = new Set();
const noindex = new Set();
const SKIP = /\/page\/\d+\/?$|^\/(wp-admin|wp-login\.php|xmlrpc\.php|wp-json|feed|comments\/feed)|\/feed\/?$|\/embed\/?$|\?/;
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
    if (url.search && !path.posix.extname(p)) return; // drop query pages; keep versioned assets (query is ignored)
    if (SKIP.test(p) || EXCLUDE.has(p)) return;
    if (!seen.has(p)) { seen.add(p); queue.push(p); }
  } catch {}
}
await rm(outDir, { recursive: true, force: true });
seen.add('/'); seen.add('/demo-form/');
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
    for (const m of body.matchAll(/(?:href|src|content|data-index)=["']((?:https?:)?\/[^"'\s]*)["']|url\(\s*["']?([^"')]+)["']?\s*\)|srcset=["']([^"']+)["']|<loc>([^<]+)<\/loc>/g)) {
      const v = m[1] || m[2] || m[4];
      if (v) enqueue(new URL(v.replace(/&amp;/g, '&'), O + p).href);
      if (m[3]) m[3].split(',').forEach(s => enqueue(new URL(s.trim().split(/\s+/)[0], O + p).href));
    }
    // URLs inside inline JSON (escaped slashes), e.g. WordPress emoji settings.
    for (const m of body.matchAll(new RegExp(esc.replace(/[.\\/]/g, c => '\\' + c) + '(\\\\\/[^"\\s]+?\\.(?:js|css|svg|png|webp|woff2))', 'g'))) enqueue(O + m[1].replace(/\\\//g, '/'));
    // Plain absolute asset URLs inside inline JSON/JS (e.g. "concatemoji":"http://host/...js").
    for (const m of body.matchAll(new RegExp(O.replace(/[.\/]/g, c => '\\' + c) + '(\\/[^"\'\\s]+?\\.(?:js|css|svg|png|webp|woff2))', 'g'))) enqueue(O + m[1]);
    // Elementor lazy-loads widget handler chunks by name at runtime; fetch them so the static copy has them.
    if (/javascript/.test(type)) for (const m of body.matchAll(/__elementorLoadChunk\(`([\w-]+)`\)/g)) enqueue(O + '/wp-content/plugins/elementor/assets/js/chunks/' + m[1] + '.min.js');
    if (/css/.test(type)) {
      // css url() relative to the css file: keep relative
    } else if (/html/.test(type)) {
      const pre = relPrefix(p);
      const PUB = (process.env.PUBLIC_BASE || '').replace(/\/$/, '');
      // Canonical, og:url and JSON-LD keep absolute public URLs; everything else becomes relative.
      if (PUB) body = body.replace(new RegExp('(rel="canonical" href="|property="og:url" content="|property="og:image" content="|name="twitter:image" content=")' + O.replace(/[.\/]/g, m => '\\' + m), 'g'), '$1' + PUB)
        .replace(/(<script[^>]*application\/ld\+json[^>]*>)([\s\S]*?)(<\/script>)/g, (m, a, j, c) => a + j.split(O).join(PUB).split(esc).join(PUB.replace(/\//g, '\\/')) + c);
      // Mark the static copy so page scripts can switch to their browser-only behaviour.
      body = body.replace(/<html\b/i, '<html data-static-export');
      for (const [from, to] of Object.entries(REWRITE)) body = body.split(`href="${O}${from}"`).join(`href="${O}${to}"`).split(`href="${from}"`).join(`href="${to}"`);
      body = body.split(encodeURIComponent(O)).join(encodeURIComponent(PUB || O))
        .replace(new RegExp(`<link rel=.dns-prefetch. href=.//${new URL(O).hostname.replace(/\./g, '\\.')}.[^>]*>\\n?`, 'g'), '');
      body = body.split(O + '/').join(pre).split(esc + '\\/').join(pre.replace(/\//g, '\\/')).split(O + '"').join(pre + '"')
        .replace(/(href|src|action)=(["'])\/(?!\/)/g, `$1=$2${pre}`)
        .replace(/(srcset=["'])([^"']+)/g, (m, a, v) => a + v.replace(/(^|,\s*)\/(?!\/)/g, `$1${pre}`))
        .replace(/\?ver=[^"'&\s]+/g, '')
        // Server-only discovery links (feeds, REST, oEmbed, RSD, shortlink) point at endpoints a static host does not have.
        .replace(/<link\b[^>]*rel=["'](?:alternate|EditURI|https:\/\/api\.w\.org\/|shortlink)["'][^>]*(?:rss\+xml|oembed|application\/json|xmlrpc|wp-json|\?p=)[^>]*>\s*/gi, '')
        // A static host answers POST with 405: send any POST form to the site's /demo-form/ notice page with GET instead.
        .replace(/<form\b[^>]*>/gi, tag => (tag = tag.replace(/\snovalidate(=["'][^"']*["'])?/i, ''), !/method=["']?post/i.test(tag)) ? tag
          : tag.replace(/\smethod=["']?post["']?/i, ' method="get"').replace(/\saction=["'][^"']*["']/i, '').replace(/^<form/i, `<form action="${pre}demo-form/"`))
        // Demo forms must never serialize what a visitor typed: drop field names and hidden inputs (nonce, referer, ids),
        // so a no-JS submit just opens /demo-form/ with no query string. Native required replaces aria-only required.
        .replace(/(<form\b[^>]*action="[^"]*demo-form\/"[^>]*>)([\s\S]*?)(<\/form>)/gi, (m, open, inner, close) => open + inner
          .replace(/<input\b[^>]*type=["']hidden["'][^>]*>/gi, '')
          .replace(/<(input|select|textarea|button)\b[^>]*>/gi, t => (/type=["']?radio/i.test(t) || /\sdata-role=/i.test(t)) ? t : t.replace(/\sname=(["'])[^"']*\1/i, '')) // radios keep names (they group options), as do data-role product pickers; neither carries typed text
          .replace(/(<(?:input|select|textarea)\b[^>]*aria-required=["']true["'])(?![^>]*\srequired)/gi, '$1 required')
          // Fluent Forms marks required fields on the group label only; carry that to the control.
          .split(/(?=<div class=["']ff-el-group)/).map(g => /^<div class=["']ff-el-group/.test(g) && /ff-el-is-required|ff-el-tc/.test(g)
            ? g.replace(/<(input|select|textarea)\b(?![^>]*\srequired)([^>]*?)(\/?)>/i, (m, t, rest, sl) => /type=["']?(hidden|submit)/i.test(rest) ? m : `<${t}${rest} required${sl}>`) : g).join('') + close);
      if (/<meta[^>]+name=["']robots["'][^>]+noindex/i.test(body)) noindex.add(p);
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
const pages = [...seen].filter(p => /\/$/.test(p) && !/\/wp-content\//.test(p) && !noindex.has(p)).sort();
await writeFile(path.join(outDir, 'sitemap.xml'), '<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n' + pages.map(p => `  <url><loc>${PUBB}${p}</loc></url>`).join('\n') + '\n</urlset>\n');
await writeFile(path.join(outDir, 'robots.txt'), `User-agent: *\nAllow: /\n\nSitemap: ${PUBB}/sitemap.xml\n`);
console.log('exported', seen.size, 'paths to', outDir);
