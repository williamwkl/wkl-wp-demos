// Collects the numbers for the Health Check report from reports/ (Lighthouse JSON) only, so every figure
// in the PDF traces back to a saved report. Writes health-check/data.json.
// usage: node scripts/health-data.mjs
import { readFileSync, readdirSync, writeFileSync, existsSync } from 'node:fs';
import path from 'node:path';
const dirs = { naive: 'reports/harbor-pine/naive', optimized: 'reports/harbor-pine/optimized', saltline: 'reports/saltline', makers: 'reports/makers' };
const pick = f => {
  const j = JSON.parse(readFileSync(f, 'utf8')); const a = j.audits, c = j.categories;
  const req = a['network-requests']?.details?.items || [];
  const byType = {}; for (const r of req) { const t = r.resourceType || 'Other'; byType[t] = (byType[t] || 0) + (r.transferSize || 0); }
  return {
    url: j.finalDisplayedUrl || j.finalUrl, fetchTime: j.fetchTime, lighthouse: j.lighthouseVersion,
    scores: Object.fromEntries(['performance', 'accessibility', 'best-practices', 'seo'].map(k => [k, Math.round(c[k].score * 100)])),
    lcp_s: +(a['largest-contentful-paint'].numericValue / 1000).toFixed(1), fcp_s: +(a['first-contentful-paint'].numericValue / 1000).toFixed(1),
    cls: +a['cumulative-layout-shift'].numericValue.toFixed(3), tbt_ms: Math.round(a['total-blocking-time'].numericValue),
    weight_kb: Math.round(a['total-byte-weight'].numericValue / 1000), requests: req.length,
    bytes_by_type_kb: Object.fromEntries(Object.entries(byType).map(([k, v]) => [k, Math.round(v / 1024)])),
    render_blocking: (a['render-blocking-insight']?.details?.items || a['render-blocking-resources']?.details?.items || []).length,
    savings_kb: Object.fromEntries(['uses-responsive-images', 'modern-image-formats', 'image-delivery-insight', 'unused-css-rules', 'unused-javascript'].filter(k => a[k]?.details?.overallSavingsBytes !== undefined || a[k]?.details?.items).map(k => [k, Math.round((a[k].details.overallSavingsBytes ?? (a[k].details.items || []).reduce((t, i) => t + (i.wastedBytes || 0), 0)) / 1000)])),
    third_party_hosts: [...new Set(req.map(r => { try { return new URL(r.url).host; } catch { return ''; } }).filter(h => h && !/^127\.0\.0\.1|localhost/.test(h)))],
  };
};
const out = { source: 'reports/*.report.json (Lighthouse 12, mobile preset, static exports served locally with gzip)', generated: new Date().toISOString(), pages: {} };
for (const [k, d] of Object.entries(dirs)) {
  if (!existsSync(d)) continue;
  out.pages[k] = {};
  for (const f of readdirSync(d).filter(f => f.endsWith('.report.json'))) out.pages[k][f.replace('.report.json', '')] = pick(path.join(d, f));
}
writeFileSync('health-check/data.json', JSON.stringify(out, null, 1));
console.log(Object.entries(out.pages).map(([k, v]) => k + ':' + Object.keys(v).length).join(' '));
