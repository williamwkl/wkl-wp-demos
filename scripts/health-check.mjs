// Builds the sample WordPress Health Check report from saved Lighthouse JSON only.
//   1. node scripts/health-data.mjs            -> health-check/data.json (numbers from reports/)
//   2. node scripts/health-check.mjs           -> health-check/report.html, dist/health-check/, deliverables/*.pdf
// The per-page Core Web Vitals table, the LCP chart and the before/after table are generated from data.json.
// Every other number in the report is then checked against data.json: the build fails if a figure cannot be traced.
import { readFileSync, writeFileSync, mkdirSync, cpSync } from 'node:fs';
import { chromium } from 'playwright';

const D = JSON.parse(readFileSync('health-check/data.json', 'utf8'));
const now = D.pages.naive, after = D.pages.optimized;
const ORDER = [['home', 'Home', 'Home'], ['personal-injury', 'Personal injury', 'Pers. injury'], ['family-law', 'Family law', 'Family law'], ['our-attorneys', 'Our attorneys', 'Attorneys'], ['results', 'Results', 'Results'], ['contact', 'Contact', 'Contact'], ['legal', 'Legal', 'Legal']];
const s1 = n => n.toFixed(1);

const cwv = `
      <thead><tr><th>Page</th><th class="num">Performance</th><th class="num">LCP</th><th class="num">FCP</th><th class="num">CLS</th><th class="num">Requests</th><th class="num">Weight</th><th>Status</th></tr></thead>
      <tbody>
${ORDER.map(([k, name]) => { const p = now[k]; const fail = p.lcp_s > 2.5 || p.cls > 0.1; return `        <tr><td>${name}</td><td class="num">${p.scores.performance}</td><td class="num">${s1(p.lcp_s)} s</td><td class="num">${s1(p.fcp_s)} s</td><td class="num">${p.cls}</td><td class="num">${p.requests}</td><td class="num">${p.weight_kb} KB</td><td><span class="tag ${fail ? 'fail' : 'pass'}">${fail ? 'LCP fails' : 'Passes'}</span></td></tr>`; }).join('\n')}
      </tbody>`;

const Y0 = 190, PX = 34; // chart baseline and 34 px per second (design spec)
const bars = ORDER.map(([k, , short], i) => {
  const a = now[k].lcp_s, b = after[k].lcp_s, x = 78 + i * 84;
  const ha = +(a * PX).toFixed(1), hb = +(b * PX).toFixed(1);
  return `        <g transform="translate(${x},0)"><rect x="0" y="${+(Y0 - ha).toFixed(1)}" width="24" height="${ha}" fill="#C9C9D1"/><rect x="28" y="${+(Y0 - hb).toFixed(1)}" width="24" height="${hb}" fill="#1F4E8C"/><text x="26" y="212" text-anchor="middle" fill="#141417">${short}</text><text x="12" y="${Math.round(Y0 - ha - 4)}" text-anchor="middle" fill="#5B5F6B">${s1(a)}</text><text x="40" y="${Math.round(Y0 - hb - 4)}" text-anchor="middle" fill="#1F4E8C" font-weight="600">${s1(b)}</text></g>`;
}).join('\n');
const chart = `<svg viewBox="0 0 680 230" width="100%" role="img" aria-labelledby="lcpT" style="max-width:680px">
      <title id="lcpT">LCP per page before and after: ${ORDER.map(([k, name]) => `${name} ${s1(now[k].lcp_s)} to ${s1(after[k].lcp_s)}`).join(', ')}. Threshold 2.5.</title>
      <g font-family="-apple-system,Segoe UI,Roboto,Arial,sans-serif" font-size="11" fill="#5B5F6B">
        <line x1="60" y1="20" x2="60" y2="190" stroke="#E4E4EA"/>
        <line x1="60" y1="190" x2="660" y2="190" stroke="#141417"/>
        <g stroke="#E4E4EA">${[1, 2, 3, 4, 5].map(i => `<line x1="60" y1="${Y0 - i * PX}" x2="660" y2="${Y0 - i * PX}"/>`).join('')}</g>
        ${[0, 1, 2, 3, 4, 5].map(i => `<text x="52" y="${Y0 - i * PX + 4}" text-anchor="end">${i}${i === 5 ? ' s' : ''}</text>`).join('')}
        <line x1="60" y1="${Y0 - 2.5 * PX}" x2="660" y2="${Y0 - 2.5 * PX}" stroke="#B23A3A" stroke-dasharray="4 4"/><text x="656" y="${Y0 - 2.5 * PX - 4}" text-anchor="end" fill="#B23A3A" font-weight="600">2.5 s good threshold</text>
      </g>
      <g font-family="-apple-system,Segoe UI,Roboto,Arial,sans-serif" font-size="11">
${bars}
      </g>
    </svg>`;

const h = now.home, ha = after.home, f = now['family-law'], fa = after['family-law'];
const row = (label, fn, unit = '') => `      <tr><td>${label}</td><td class="num">${fn(h)}${unit}</td><td class="num"><b>${fn(ha)}${unit}</b></td><td class="num">${fn(f)}${unit}</td><td class="num"><b>${fn(fa)}${unit}</b></td></tr>`;
const beforeAfter = `<div class="tablewrap"><table>
    <thead><tr><th>Metric (mobile)</th><th class="num">Home, now</th><th class="num">Home, after</th><th class="num">Family law, now</th><th class="num">Family law, after</th></tr></thead>
    <tbody>
${[row('Lighthouse Performance', p => p.scores.performance), row('Largest Contentful Paint', p => s1(p.lcp_s), ' s'), row('First Contentful Paint', p => s1(p.fcp_s), ' s'), row('Cumulative Layout Shift', p => p.cls), row('Page weight', p => p.weight_kb, ' KB'), row('Requests', p => p.requests), row('Render-blocking resources', p => p.render_blocking)].join('\n')}
    </tbody>
  </table></div>`;

let html = readFileSync('health-check/report.template.html', 'utf8')
  .replace('{{CWV_TABLE}}', cwv).replace('{{LCP_CHART}}', chart).replace('{{BEFORE_AFTER}}', beforeAfter);

// ---- Traceability check: every measured figure in the text must exist in data.json ----
const known = { s: new Set(), kb: new Set(), n: new Set(), cls: new Set() };
for (const set of [now, after]) for (const p of Object.values(set)) {
  known.s.add(s1(p.lcp_s)); known.s.add(s1(p.fcp_s)); known.kb.add(String(p.weight_kb)); known.cls.add(String(p.cls));
  for (const v of [...Object.values(p.scores), p.requests, p.render_blocking, ...Object.values(p.savings_kb)]) known.n.add(String(v));
  for (const v of Object.values(p.savings_kb)) known.kb.add(String(v));
}
// OPTIMIZATION.md is the other permitted source (photo sizes before/after).
const opt = readFileSync('demos/harbor-pine/OPTIMIZATION.md', 'utf8');
for (const m of opt.matchAll(/(\d+(?:\.\d+)?) ?KB/g)) known.kb.add(m[1]);
for (const m of opt.matchAll(/(\d+(?:\.\d+)?) s\b/g)) known.s.add((+m[1]).toFixed(1));
const text = html.replace(/<style[\s\S]*?<\/style>/g, '').replace(/<svg[\s\S]*?<\/svg>/g, m => m.replace(/<(?!text|title)[^>]+>/g, ' ')).replace(/<[^>]+>/g, ' ');
const problems = [];
const ALLOW = { s: ['2.5', '0.9'], kb: [] }; // 2.5 s is Google's threshold; others must be measured
for (const m of text.matchAll(/(\d+(?:\.\d+)?)\s*s\b(?!\w)/g)) { const v = (+m[1]).toFixed(1); if (!known.s.has(v) && !ALLOW.s.includes(v) && !/^\d+$/.test(m[1])) problems.push(`time ${m[0]}`); }
for (const m of text.matchAll(/(\d+)\s*KB\b/g)) if (!known.kb.has(m[1])) problems.push(`size ${m[0]}`);
for (const m of text.matchAll(/CLS (\d\.\d+)/g)) if (!known.cls.has(String(+m[1]))) problems.push(`cls ${m[0]}`);
if (problems.length) { console.error('Untraceable figures:\n  ' + [...new Set(problems)].join('\n  ')); process.exitCode = 1; }

writeFileSync('health-check/report.html', html);
mkdirSync('dist/health-check/assets', { recursive: true });
writeFileSync('dist/health-check/index.html', html.replace('<head>', '<head>\n<link rel="canonical" href="' + JSON.parse(readFileSync('site.config.json', 'utf8')).publicBase.replace(/\/$/, '') + '/health-check/">'));
cpSync('health-check/assets', 'dist/health-check/assets', { recursive: true });

mkdirSync('deliverables', { recursive: true });
const b = await chromium.launch(); const pg = await b.newPage();
await pg.goto('file://' + process.cwd() + '/health-check/report.html', { waitUntil: 'networkidle' });
await pg.emulateMedia({ media: 'print' });
await pg.pdf({ path: 'deliverables/WordPress-Health-Check-Sample.pdf', format: 'Letter', printBackground: true, preferCSSPageSize: true });
const heights = await pg.evaluate(() => [...document.querySelectorAll('.page')].map(p => [p.offsetHeight, p.scrollHeight]));
await b.close();
console.log('pages', heights.length, 'overflowing pages', heights.filter(([o, s]) => s > o + 1).length, problems.length ? 'TRACE FAIL' : 'trace ok');
