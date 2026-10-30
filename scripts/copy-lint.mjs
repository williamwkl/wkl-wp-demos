// Visible-copy lint on exported HTML: no em dashes, no standalone word "AI", no 127.0.0.1 leaks.
import { readdirSync, readFileSync, statSync } from 'node:fs';
import path from 'node:path';
const root = process.argv[2]; let bad = 0;
const walk = d => readdirSync(d).flatMap(f => { const p = path.join(d, f); return statSync(p).isDirectory() ? (f === 'wp-includes' || f === 'wp-content' ? [] : walk(p)) : p.endsWith('.html') ? [p] : []; });
for (const f of walk(root)) {
  const html = readFileSync(f, 'utf8');
  const text = html.replace(/<script[\s\S]*?<\/script>|<style[\s\S]*?<\/style>/g, ' ').replace(/<[^>]+>/g, ' ').replace(/&#8212;|&mdash;/g, '—');
  const alts = [...html.matchAll(/(?:alt|aria-label|content)="([^"]*)"/g)].map(m => m[1]).join(' ');
  for (const [name, re] of [['em dash', /—/], ['word AI', /\bAI\b/], ['localhost leak', /127\.0\.0\.1/]]) {
    const hay = name === 'localhost leak' ? html : text + ' ' + alts;
    if (re.test(hay)) { bad++; console.log(`FAIL ${name}: ${f}`); }
  }
}
console.log(bad ? `${bad} copy issues` : 'copy lint clean');
