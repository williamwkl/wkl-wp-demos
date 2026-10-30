// Builds the sample WordPress Health Check report.
//   1. node scripts/health-data.mjs            -> health-check/data.json (Lighthouse numbers from reports/)
//   2. node scripts/health-check.mjs           -> health-check/report.html, dist/health-check/, deliverables/*.pdf
//
// Every figure in the report is an explicit FIELD placeholder, never typed text:
//   {{now.home.scores.accessibility}}          one field of one page (now = current site, after = optimized copy)
//   {{now.*.scores.performance|min}}           the same field across all pages, reduced (min, max, range, count, ...)
//   {{audit.security.pass}} / {{audit.security.total}}, {{audit.facts.webp_px}}, {{offer.tier1_price}}
// Placeholders also work inside audit.json check text. Pipes: s1 (1 decimal), min, max, range ("a to b"), count, failing
// (pages over the LCP threshold), word / Word (spelled out). Unknown paths fail the build.
// TRACE: after rendering, any digit in the report's visible text that did not come from a placeholder fails the build.
// Allowed without a placeholder: page footers ("Page 3 of 9") and page references, the names H1 and woff2, section numbering
// (finding numbers, fix ranks, order-of-work steps, "Tier 2", "fixes 1, 2 and 5") and the LCP chart's axis scale.
import { readFileSync, writeFileSync, mkdirSync, cpSync } from 'node:fs';
import { chromium } from 'playwright';
import { BASE } from './site-base.mjs';

const D = JSON.parse(readFileSync('health-check/data.json', 'utf8'));
const OFFER = JSON.parse(readFileSync('health-check/offer.json', 'utf8')); // prices and durations: one place, set by William
const AUDIT = JSON.parse(readFileSync('health-check/audit.json', 'utf8')); // review findings + every non-Lighthouse figure
const now = D.pages.naive, after = D.pages.optimized;
const ORDER = [['home', 'Home', 'Home'], ['personal-injury', 'Personal injury', 'Pers. injury'], ['family-law', 'Family law', 'Family law'], ['our-attorneys', 'Our attorneys', 'Attorneys'], ['results', 'Results', 'Results'], ['contact', 'Contact', 'Contact'], ['legal', 'Legal', 'Legal']];
const s1 = n => (+n).toFixed(1);

// Values that came from a field are wrapped in private-use markers until the trace check has run.
const O = '', C = '';
const M = v => O + v + C;
const unmark = s => s.replace(/[]/g, '');

// ---- field context ----
for (const [k, f] of Object.entries(AUDIT.facts)) {
  if (!f.source) throw new Error('audit.json fact needs a "source": ' + k);
  if (f.file && !readFileSync(f.file, 'utf8').includes(f.match)) throw new Error(`audit.json fact ${k}: "${f.match}" not found in ${f.file}`);
}
const LCP_GOOD = AUDIT.facts.lcp_good_s.value;
const count = (list, st) => list.filter(c => c.status === st).length;
const sec = AUDIT.security, mai = AUDIT.maintenance;
const CTX = {
  now, after,
  audit: {
    security: { total: sec.length, pass: count(sec, 'pass'), fail: count(sec, 'fail'), partial: count(sec, 'partial'), not_checked: count(sec, 'not_checked'), other_open: sec.filter(c => c.status !== 'pass' && !c.top_fix).length },
    maintenance: { total: mai.length, pass: count(mai, 'pass') },
    facts: Object.fromEntries(Object.entries(AUDIT.facts).map(([k, f]) => [k, f.value])),
    reviewed: new Date(AUDIT.reviewed + 'T12:00:00Z').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', timeZone: 'UTC' }),
  },
  offer: OFFER,
  reports: { measured: Object.keys(now).length + Object.keys(after).length, lighthouse: [...new Set(Object.values(now).map(p => p.lighthouse.split('.').slice(0, 2).join('.')))].join(', ') },
};
const WORDS = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen', 'twenty'];
const lookup = (path) => {
  let cur = [CTX], wild = false;
  for (const part of path.split('.')) {
    if (part === '*') { wild = true; cur = cur.flatMap(o => Object.values(o)); continue; }
    cur = cur.map(o => { if (o == null || !(part in Object(o))) throw new Error(`unknown field {{${path}}} (at "${part}")`); return o[part]; });
  }
  return wild ? cur : cur[0];
};
const PIPES = {
  s1: v => Array.isArray(v) ? v.map(s1) : s1(v),
  min: v => Math.min(...v.map(Number)), max: v => Math.max(...v.map(Number)),
  range: v => { const n = v.map(Number), lo = Math.min(...n), hi = Math.max(...n), f = x => v.find(y => +y === x); return lo === hi ? String(f(lo)) : f(lo) + ' to ' + f(hi); },
  count: v => v.length,
  failing: v => v.filter(x => +x > LCP_GOOD),
  word: v => WORDS[v] ?? String(v), Word: v => { const w = WORDS[v] ?? String(v); return w[0].toUpperCase() + w.slice(1); },
};
const field = expr => { const [path, ...pipes] = expr.split('|'); let v = lookup(path); for (const p of pipes) { if (!PIPES[p]) throw new Error(`unknown pipe |${p} in {{${expr}}}`); v = PIPES[p](v); } if (Array.isArray(v)) throw new Error(`{{${expr}}} is a list: reduce it with a pipe`); return v; };
// ---- LABEL lint: a placeholder must belong to the label written in front of it ----
// Convention in the template: the label precedes the value ("LCP {{...}}", "Home: requests {{...}}", "SEO {{...}}").
// The nearest label in the 40 characters before a placeholder (not crossing another placeholder or an element boundary)
// must match the placeholder's field, and the nearest page name must match its page. So a figure moved under the wrong
// label, category or page fails the build even though it is a real field.
const FIELD_LABELS = [
  [/\bLCP\b|Largest Contentful Paint/, /lcp/], [/\bFCP\b|First Contentful Paint|First text/, /fcp/], [/\bCLS\b|Cumulative Layout Shift/, /cls/],
  [/\bTBT\b|Total Blocking Time/, /tbt/], [/\brequests\b/i, /requests/], [/render-blocking/i, /render_blocking/],
  [/page weight|\bweight $/, /weight_kb/], [/\bsavings\b/, /savings_kb/],
  [/\baccessibility\b/i, /scores\.accessibility/], [/best practices/i, /scores\.best-practices/], [/(?<!Yoast )\bSEO\b/, /scores\.seo/], [/\bPerf(?:ormance)?\b/, /scores\.performance/],
];
const PAGE_NAMES = ORDER.map(([k, name]) => [k, name]);
export function labelProblems(template) {
  const out = [];
  const flat = template.replace(/<style[\s\S]*?<\/style>/g, '').replace(/<[^>]+>/g, '¦');
  for (const m of flat.matchAll(/\{\{([a-z][\w.*|-]*)\}\}/g)) {
    const before = flat.slice(Math.max(0, m.index - 40), m.index).split(/¦|\}\}|[.;:!?] (?=[A-Z])/).pop(); // same element, same clause
    const path = m[1].split('|')[0];
    let best = null;
    for (const [re, want] of FIELD_LABELS) { const all = [...before.matchAll(new RegExp(re.source, 'g' + re.flags.replace('g', '')))]; const last = all.pop(); if (last && (!best || last.index > best.at)) best = { at: last.index, want, label: last[0] }; }
    if (best && !best.want.test(path)) out.push(`label "${best.label}" in front of {{${m[1]}}}`);
    const pm = path.match(/^(?:now|after)\.([\w-]+)\./);
    if (pm && pm[1] !== '*') {
      let page = null;
      for (const [k, name] of PAGE_NAMES) { const i = before.lastIndexOf(name); if (i >= 0 && (!page || i > page.at)) page = { at: i, k, name }; }
      if (page && page.k !== pm[1]) out.push(`page "${page.name}" in front of {{${m[1]}}}`);
    }
  }
  return out;
}
const fill = s => s.replace(/\{\{([a-z][\w.*|-]*)\}\}/g, (m, e) => M(field(e)));

// ---- generated blocks (every value is a field) ----
const cwv = `
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
const chart = `<svg viewBox="0 0 680 230" width="100%" role="img" aria-labelledby="lcpT" style="max-width:680px">
      <title id="lcpT">LCP per page before and after: ${ORDER.map(([k, name]) => `${name} ${M(s1(now[k].lcp_s))} to ${M(s1(after[k].lcp_s))}`).join(', ')}. Threshold ${M(LCP_GOOD)}.</title>
      <g font-family="-apple-system,Segoe UI,Roboto,Arial,sans-serif" font-size="11" fill="#5B5F6B">
        <line x1="60" y1="20" x2="60" y2="190" stroke="#E4E4EA"/>
        <line x1="60" y1="190" x2="660" y2="190" stroke="#141417"/>
        <g stroke="#E4E4EA">${[1, 2, 3, 4, 5].map(i => `<line x1="60" y1="${Y0 - i * PX}" x2="660" y2="${Y0 - i * PX}"/>`).join('')}</g>
        ${[0, 1, 2, 3, 4, 5].map(i => `<text x="52" y="${Y0 - i * PX + 4}" text-anchor="end" data-axis>${i}${i === 5 ? ' s' : ''}</text>`).join('')}
        <line x1="60" y1="${Y0 - LCP_GOOD * PX}" x2="660" y2="${Y0 - LCP_GOOD * PX}" stroke="#B23A3A" stroke-dasharray="4 4"/><text x="656" y="${Y0 - LCP_GOOD * PX - 4}" text-anchor="end" fill="#B23A3A" font-weight="600">${M(LCP_GOOD)} s good threshold</text>
      </g>
      <g font-family="-apple-system,Segoe UI,Roboto,Arial,sans-serif" font-size="11">
${bars}
      </g>
    </svg>`;

const h = now.home, ha = after.home, f = now['family-law'], fa = after['family-law'];
const row = (label, fn, unit = '') => `      <tr><td>${label}</td><td class="num">${M(fn(h))}${unit}</td><td class="num"><b>${M(fn(ha))}${unit}</b></td><td class="num">${M(fn(f))}${unit}</td><td class="num"><b>${M(fn(fa))}${unit}</b></td></tr>`;
const beforeAfter = `<div class="tablewrap"><table>
    <thead><tr><th>Metric (mobile)</th><th class="num">Home, now</th><th class="num">Home, after</th><th class="num">Family law, now</th><th class="num">Family law, after</th></tr></thead>
    <tbody>
${[row('Lighthouse Performance', p => p.scores.performance), row('Largest Contentful Paint', p => s1(p.lcp_s), ' s'), row('First Contentful Paint', p => s1(p.fcp_s), ' s'), row('Cumulative Layout Shift', p => p.cls), row('Page weight', p => p.weight_kb, ' KB'), row('Requests', p => p.requests), row('Render-blocking resources', p => p.render_blocking)].join('\n')}
    </tbody>
  </table></div>`;

const TAG = { pass: ['pass', 'Pass'], fail: ['fail', 'Fail'], partial: ['warn', 'Needs work'], not_checked: ['na', 'Not checked'] };
const tagOf = st => `<span class="tag ${TAG[st][0]}">${TAG[st][1]}</span>`;
const BLOCKS = {
  SECURITY_ROWS: sec.map(c => `      <tr><td>${c.check}</td><td>${tagOf(c.status)}</td><td>${c.evidence}</td><td>${c.fix}</td></tr>`).join('\n'),
  MAINT_ROWS: mai.map(c => `        <tr><td>${c.check}</td><td>${tagOf(c.status)}</td><td>${c.evidence}</td></tr>`).join('\n'),
  CWV_TABLE: cwv, LCP_CHART: chart, BEFORE_AFTER: beforeAfter,
};

// One severity scale for every ring (Lighthouse style): 0-49 red, 50-89 amber, 90-100 green; ratios become percents.
const ringPct = (label) => {
  if (/\//.test(label)) { const [a, b] = label.split('/').map(Number); return (a / b) * 100; }
  if (/s$/.test(label)) { const pages = Object.values(now); return (pages.filter(p => p.lcp_s <= LCP_GOOD).length / pages.length) * 100; }
  return +label;
};
const ringColor = pct => pct < 50 ? '#B23A3A' : pct < 90 ? '#8A5E0E' : '#2E6B46';

const TEMPLATE = readFileSync('health-check/report.template.html', 'utf8');
const mislabelled = [...labelProblems(TEMPLATE), ...labelProblems(JSON.stringify(AUDIT.security) + JSON.stringify(AUDIT.maintenance))];
if (mislabelled.length) { console.error('Figures under the wrong label or page:\n  ' + mislabelled.join('\n  ')); process.exit(1); }
let html = TEMPLATE.replace(/\{\{([A-Z][A-Z0-9_]*)\}\}/g, (m, k) => { if (!(k in BLOCKS)) throw new Error('unknown block ' + m); return BLOCKS[k]; });
html = fill(html);
html = html.replace(/<circle cx="32" cy="32" r="27" fill="none" stroke="#[0-9A-F]{6}" stroke-width="7" stroke-dasharray="[\d.]+ 169\.6"([^>]*)\/><text([^>]*)>([^<]+)<\/text>/g, (m, rest, tattr, label) => {
  const pct = Math.max(0, Math.min(100, ringPct(unmark(label))));
  return `<circle cx="32" cy="32" r="27" fill="none" stroke="${ringColor(pct)}" stroke-width="7" stroke-dasharray="${(pct * 1.696).toFixed(1)} 169.6"${rest}/><text${tattr}>${label}</text>`;
});
if (/\{\{/.test(html)) throw new Error('unfilled placeholder: ' + html.match(/\{\{[^}]*\}\}/)[0]);

// ---- TRACE: no digit in the visible text unless it came from a field ----
export function untraced(markedHtml) {
  const text = markedHtml.replace(/<style[\s\S]*?<\/style>/g, ' ').replace(/<head>[\s\S]*?<\/head>/, ' ')
    .replace(/<svg[\s\S]*?<\/svg>/g, m => ' ¦ ' + [...m.matchAll(/<(text|title)([^>]*)>([^<]*)</g)].filter(x => !/data-axis/.test(x[2])).map(x => x[3]).join(' ¦ ') + ' ¦ ')
    .replace(/<div class="n">\d+<\/div>|<td data-label="#">\d+<\/td>|<tr><td>\d+<\/td>/g, ' ¦ ') // finding numbers, fix ranks, order-of-work steps
    .replace(/<[^>]+>/g, ' ').replace(/&[a-z]+;/g, ' ')
    .replace(/[^]*/g, ' ⟨field⟩ ')
    .replace(/\bH1\b|\bwoff2\b/g, ' ') // names, not figures
    .replace(/Page \d+ of \d+|\bpage \d+\b|\bTier \d\b|\b[Ff]ixes? \d+(?:(?:, | and )\d+)*/g, ' ');
  return [...text.matchAll(/\d/g)].map(m => text.slice(Math.max(0, m.index - 45), m.index + 25).replace(/\s+/g, ' ').trim());
}
const problems = untraced(html);
if (problems.length) { console.error('Typed numbers in the report (use a field placeholder such as {{now.home.lcp_s|s1}} or an audit.json fact):\n  ' + [...new Set(problems)].join('\n  ')); process.exit(1); }
html = unmark(html);

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
console.log('pages', heights.length, 'overflowing pages', heights.filter(([o, s]) => s > o + 1).length, 'trace ok (every figure from a field)');
