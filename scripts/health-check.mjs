// Builds the sample WordPress Health Check report.
//   1. node scripts/health-data.mjs            -> health-check/data.json (Lighthouse numbers from reports/)
//   2. node scripts/health-check.mjs           -> health-check/report.html, dist/health-check/, deliverables/*.pdf
// Sources: data.json (Lighthouse), audit.json (checks + every other figure as a fact with a source and its own render
// label), offer.json (prices). Placeholder rules and the fail-closed trace live in scripts/health-render.mjs;
// negative tests in scripts/test-health-check.mjs. Generated here (labels fixed in code, values from fields): the score
// dashboard, the Core Web Vitals table, the LCP chart, the before/after table, the security and maintenance tables.
import { readFileSync, writeFileSync, mkdirSync, cpSync } from 'node:fs';
import { chromium } from 'playwright';
import { BASE } from './site-base.mjs';
import { M, unmark, makeContext, render, ORDER } from './health-render.mjs';

const read = f => readFileSync(f, 'utf8');
export function buildBlocks(ctx) {
  const { now, after, AUDIT, LCP_GOOD } = ctx, s1 = n => (+n).toFixed(1);
  const pages = Object.values(now), perf = pages.map(p => p.scores.performance);
  const failing = pages.filter(p => p.lcp_s > LCP_GOOD).length;
  const sec = ctx.root.audit.security, mai = ctx.root.audit.maintenance;
  const ringColor = pct => pct < 50 ? '#B23A3A' : pct < 90 ? '#8A5E0E' : '#2E6B46';
  const ring = (label, pct, size, title, small) => `    <div class="score" role="listitem"><svg class="ring" viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="27" fill="none" stroke="#F0F0F4" stroke-width="7"/><circle cx="32" cy="32" r="27" fill="none" stroke="${ringColor(pct)}" stroke-width="7" stroke-dasharray="${(Math.max(0, Math.min(100, pct)) * 1.696).toFixed(1)} 169.6" stroke-linecap="round" transform="rotate(-90 32 32)"/><text x="32" y="37" text-anchor="middle" font-size="${size}" font-weight="600" font-family="Georgia,serif" fill="#141417">${label}</text></svg><b>${title}</b><small>${small}</small></div>`;
  const minOf = k => Math.min(...pages.map(p => p.scores[k]));
  const DASHBOARD = [
    ring(M(Math.min(...perf)), Math.min(...perf), 16, 'Speed', `Lighthouse Performance, worst page (range ${M(Math.min(...perf))} to ${M(Math.max(...perf))})`),
    ring(M(s1(Math.max(...pages.map(p => p.lcp_s)))) + 's', ((pages.length - failing) / pages.length) * 100, 13, 'Core Web Vitals', `worst LCP; LCP fails on ${M(failing)} of ${M(pages.length)} pages`),
    ring(`${M(sec.pass)}/${M(sec.total)}`, (sec.pass / sec.total) * 100, 14, 'Security', `checks passed; ${M(sec.fail)} fail, ${M(sec.partial)} partial, ${M(sec.not_checked)} not checked`),
    ring(M(minOf('seo')), minOf('seo'), 16, 'SEO', `Lighthouse SEO, lowest of all ${M(pages.length)} pages`),
    ring(M(minOf('accessibility')), minOf('accessibility'), 16, 'Accessibility', `Lighthouse Accessibility, lowest of all ${M(pages.length)} pages`),
    ring(`${M(mai.pass)}/${M(mai.total)}`, (mai.pass / mai.total) * 100, 14, 'Maintenance', 'checks passed'),
  ].join('\n');

  const CWV_TABLE = `
      <thead><tr><th>Page</th><th class="num">Performance</th><th class="num">LCP</th><th class="num">FCP</th><th class="num">CLS</th><th class="num">Requests</th><th class="num">Weight</th><th>Status</th></tr></thead>
      <tbody>
${ORDER.map(([k, name]) => { const p = now[k]; const fail = p.lcp_s > LCP_GOOD || p.cls > AUDIT.facts.cls_good.value; return `        <tr><td>${name}</td><td class="num">${M(p.scores.performance)}</td><td class="num">${M(s1(p.lcp_s))} s</td><td class="num">${M(s1(p.fcp_s))} s</td><td class="num">${M(p.cls)}</td><td class="num">${M(p.requests)}</td><td class="num">${M(p.weight_kb)} KB</td><td><span class="tag ${fail ? 'fail' : 'pass'}">${fail ? 'LCP fails' : 'Passes'}</span></td></tr>`; }).join('\n')}
      </tbody>`;

  const Y0 = 190, PX = 34; // chart baseline and 34 px per second (design spec)
  const bars = ORDER.map(([k, , short], i) => {
    const a = now[k].lcp_s, b = after[k].lcp_s, x = 78 + i * 84;
    const ha = +(a * PX).toFixed(1), hb = +(b * PX).toFixed(1);
    return `        <g transform="translate(${x},0)"><rect x="0" y="${+(Y0 - ha).toFixed(1)}" width="24" height="${ha}" fill="#C9C9D1"/><rect x="28" y="${+(Y0 - hb).toFixed(1)}" width="24" height="${hb}" fill="#1F4E8C"/><text x="26" y="212" text-anchor="middle" fill="#141417">${short}</text><text x="12" y="${Math.round(Y0 - ha - 4)}" text-anchor="middle" fill="#5B5F6B">${M(s1(a))}</text><text x="40" y="${Math.round(Y0 - hb - 4)}" text-anchor="middle" fill="#1F4E8C" font-weight="600">${M(s1(b))}</text></g>`;
  }).join('\n');
  const LCP_CHART = `<svg viewBox="0 0 680 230" width="100%" role="img" aria-labelledby="lcpT" style="max-width:680px">
      <title id="lcpT">LCP per page before and after: ${ORDER.map(([k, name]) => `${name} ${M(s1(now[k].lcp_s))} to ${M(s1(after[k].lcp_s))}`).join(', ')}. Threshold ${M(LCP_GOOD)}.</title>
      <g font-family="-apple-system,Segoe UI,Roboto,Arial,sans-serif" font-size="11" fill="#5B5F6B">
        <line x1="60" y1="20" x2="60" y2="190" stroke="#E4E4EA"/>
        <line x1="60" y1="190" x2="660" y2="190" stroke="#141417"/>
        <g stroke="#E4E4EA">${[1, 2, 3, 4, 5].map(i => `<line x1="60" y1="${Y0 - i * PX}" x2="660" y2="${Y0 - i * PX}"/>`).join('')}</g>
        ${[0, 1, 2, 3, 4, 5].map(i => `<text x="52" y="${Y0 - i * PX + 4}" text-anchor="end">${M(i)}${i === 5 ? ' s' : ''}</text>`).join('')}
        <line x1="60" y1="${Y0 - LCP_GOOD * PX}" x2="660" y2="${Y0 - LCP_GOOD * PX}" stroke="#B23A3A" stroke-dasharray="4 4"/><text x="656" y="${Y0 - LCP_GOOD * PX - 4}" text-anchor="end" fill="#B23A3A" font-weight="600">${M(LCP_GOOD)} s good threshold</text>
      </g>
      <g font-family="-apple-system,Segoe UI,Roboto,Arial,sans-serif" font-size="11">
${bars}
      </g>
    </svg>`;

  const h = now.home, ha = after.home, f = now['family-law'], fa = after['family-law'];
  const row = (label, fn, unit = '') => `      <tr><td>${label}</td><td class="num">${M(fn(h))}${unit}</td><td class="num"><b>${M(fn(ha))}${unit}</b></td><td class="num">${M(fn(f))}${unit}</td><td class="num"><b>${M(fn(fa))}${unit}</b></td></tr>`;
  const BEFORE_AFTER = `<div class="tablewrap"><table>
    <thead><tr><th>Metric (mobile)</th><th class="num">Home, now</th><th class="num">Home, after</th><th class="num">Family law, now</th><th class="num">Family law, after</th></tr></thead>
    <tbody>
${[row('Lighthouse Performance', p => p.scores.performance), row('Largest Contentful Paint', p => s1(p.lcp_s), ' s'), row('First Contentful Paint', p => s1(p.fcp_s), ' s'), row('Cumulative Layout Shift', p => p.cls), row('Page weight', p => p.weight_kb, ' KB'), row('Requests', p => p.requests), row('Render-blocking resources', p => p.render_blocking)].join('\n')}
    </tbody>
  </table></div>`;

  const TAG = { pass: ['pass', 'Pass'], fail: ['fail', 'Fail'], partial: ['warn', 'Needs work'], not_checked: ['na', 'Not checked'] };
  const tagOf = st => `<span class="tag ${TAG[st][0]}">${TAG[st][1]}</span>`;
  return {
    DASHBOARD, CWV_TABLE, LCP_CHART, BEFORE_AFTER,
    SECURITY_ROWS: AUDIT.security.map(c => `      <tr><td>${c.check}</td><td>${tagOf(c.status)}</td><td>${c.evidence}</td><td>${c.fix}</td></tr>`).join('\n'),
    MAINT_ROWS: AUDIT.maintenance.map(c => `        <tr><td>${c.check}</td><td>${tagOf(c.status)}</td><td>${c.evidence}</td></tr>`).join('\n'),
  };
}
export function loadContext() {
  return makeContext(JSON.parse(read('health-check/data.json')), JSON.parse(read('health-check/audit.json')), JSON.parse(read('health-check/offer.json')), read);
}

if (import.meta.url === `file://${process.argv[1]}`) {
  const ctx = loadContext();
  const { html: marked, problems } = render(read('health-check/report.template.html'), ctx, buildBlocks(ctx));
  if (problems.length) { console.error('Digits not from a placeholder:\n  ' + [...new Set(problems)].join('\n  ')); process.exit(1); }
  const html = unmark(marked);
  writeFileSync('health-check/report.html', html);
  mkdirSync('dist/health-check/assets', { recursive: true });
  writeFileSync('dist/health-check/index.html', html.replace('<head>', '<head>\n<link rel="canonical" href="' + BASE + '/health-check/">'));
  cpSync('health-check/assets', 'dist/health-check/assets', { recursive: true });
  mkdirSync('deliverables', { recursive: true });
  const b = await chromium.launch(); const pg = await b.newPage();
  await pg.goto('file://' + process.cwd() + '/health-check/report.html', { waitUntil: 'networkidle' });
  await pg.emulateMedia({ media: 'print' });
  await pg.pdf({ path: 'deliverables/WordPress-Health-Check-Sample.pdf', format: 'Letter', printBackground: true, preferCSSPageSize: true });
  const heights = await pg.evaluate(() => [...document.querySelectorAll('.page')].map(p => [p.offsetHeight, p.scrollHeight]));
  await b.close();
  console.log('pages', heights.length, 'overflowing pages', heights.filter(([o, s]) => s > o + 1).length, 'trace ok (every figure from a placeholder)');
}
