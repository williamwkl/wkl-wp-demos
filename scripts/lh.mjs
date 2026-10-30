// Lighthouse (mobile, default throttling) against a static export served locally.
// usage: node scripts/lh.mjs <baseUrl> <reportDir> <path> [path ...]
import { execFileSync } from 'node:child_process';
import { mkdirSync, readFileSync } from 'node:fs';
const [base, dir, ...paths] = process.argv.slice(2);
mkdirSync(dir, { recursive: true });
const rows = [];
for (const p of paths) {
  const name = p.replace(/^\/|\/$/g, '').split('/').pop() || 'home';
  const out = `${dir}/${name}`;
  execFileSync('npx', ['lighthouse', base + p, '--quiet', '--chrome-flags=--headless=new', '--output=json', '--output=html', `--output-path=${out}`, '--only-categories=performance,accessibility,best-practices,seo'], { stdio: 'inherit' });
  const j = JSON.parse(readFileSync(`${out}.report.json`, 'utf8'));
  const c = j.categories, a = j.audits;
  rows.push({ page: p, perf: Math.round(c.performance.score * 100), a11y: Math.round(c.accessibility.score * 100), bp: Math.round(c['best-practices'].score * 100), seo: Math.round(c.seo.score * 100), lcp: a['largest-contentful-paint'].displayValue, cls: a['cumulative-layout-shift'].displayValue, tbt: a['total-blocking-time'].displayValue,
    fails: Object.values(a).filter(x => x.score !== null && x.score < 0.9 && ['binary', 'numeric'].includes(x.scoreDisplayMode) && x.details).map(x => x.id).slice(0, 8).join(',') });
}
console.table(rows);
