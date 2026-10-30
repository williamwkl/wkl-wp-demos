// Builds the sample WordPress Health Check report from saved Lighthouse JSON only.
//   1. node scripts/health-data.mjs            -> health-check/data.json (numbers from reports/)
//   2. node scripts/health-check.mjs           -> health-check/report.html, dist/health-check/, deliverables/*.pdf
// Generated from data.json (Lighthouse): the per-page Core Web Vitals table, the LCP chart, the before/after table, the FCP range.
// Generated from audit.json (review findings): the security and maintenance tables, their ratios, counts and headlines.
// Then every number left in the rendered text must equal a field of data.json, audit.json or offer.json (see TRACE below):
// the build fails on any untraced number, range or ratio.
import { readFileSync, writeFileSync, mkdirSync, cpSync } from 'node:fs';
import { chromium } from 'playwright';
import { BASE } from './site-base.mjs';

const D = JSON.parse(readFileSync('health-check/data.json', 'utf8'));
const OFFER = JSON.parse(readFileSync('health-check/offer.json', 'utf8')); // prices and durations: one place, set by William
const AUDIT = JSON.parse(readFileSync('health-check/audit.json', 'utf8')); // security + maintenance checks and every other non-Lighthouse figure
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

// One severity scale for every ring (Lighthouse style): 0-49 red, 50-89 amber, 90-100 green; ratios become percents.
const ringPct = (label) => {
  if (/\//.test(label)) { const [a, b] = label.split('/').map(Number); return (a / b) * 100; }
  if (/s$/.test(label)) { const pages = Object.values(now); return (pages.filter(p => p.lcp_s <= 2.5).length / pages.length) * 100; }
  return +label;
};
const ringColor = pct => pct < 50 ? '#B23A3A' : pct < 90 ? '#8A5E0E' : '#2E6B46';
const fcpRange = (set) => { const v = Object.values(set).map(p => p.fcp_s); return s1(Math.min(...v)) + (Math.min(...v) === Math.max(...v) ? '' : ' to ' + s1(Math.max(...v))) + ' s'; };

// ---- Security and maintenance: rows, ratios, counts and headlines all come from audit.json ----
const WORDS = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen', 'twenty'];
const cap = w => w[0].toUpperCase() + w.slice(1);
const TAG = { pass: ['pass', 'Pass'], fail: ['fail', 'Fail'], partial: ['warn', 'Needs work'], not_checked: ['na', 'Not checked'] };
const count = (list, st) => list.filter(c => c.status === st).length;
const sec = AUDIT.security, mai = AUDIT.maintenance;
const SC = { total: sec.length, pass: count(sec, 'pass'), fail: count(sec, 'fail'), partial: count(sec, 'partial'), na: count(sec, 'not_checked') };
const MC = { total: mai.length, pass: count(mai, 'pass') };
const tagOf = st => `<span class="tag ${TAG[st][0]}">${TAG[st][1]}</span>`;
const GEN = {
  SECURITY_ROWS: sec.map(c => `      <tr><td>${c.check}</td><td>${tagOf(c.status)}</td><td>${c.evidence}</td><td>${c.fix}</td></tr>`).join('\n'),
  MAINT_ROWS: mai.map(c => `        <tr><td>${c.check}</td><td>${tagOf(c.status)}</td><td>${c.evidence}</td></tr>`).join('\n'),
  SEC_TOTAL_WORD: cap(WORDS[SC.total]),
  SEC_HEADLINE: `Security: ${WORDS[SC.pass]} check${SC.pass === 1 ? '' : 's'} passed, ${WORDS[SC.fail]} failed, and all ${WORDS[SC.fail]} are cheap to fix.`,
  SEC_FAIL_WORD_LC: WORDS[SC.fail],
  SEC_RATIO: `${SC.pass}/${SC.total}`,
  SEC_SUMMARY: `${SC.fail} fail, ${SC.partial} partial, ${SC.na} not checked`,
  MAINT_HEADLINE: `${cap(WORDS[MC.total])} checks, ${WORDS[MC.pass]} passed`,
  MAINT_RATIO: `${MC.pass}/${MC.total}`,
  TIER2_SEC_WORD_LC: WORDS[sec.filter(c => c.status !== 'pass' && !c.top_fix).length],
};
let html = readFileSync('health-check/report.template.html', 'utf8')
  .replace(/\{\{([A-Z0-9_]+)\}\}/g, (m, k) => k in GEN ? GEN[k] : m)
  .replace('{{FCP_RANGE}}', 'FCP ' + fcpRange(now) + ' down to ' + fcpRange(after))
  .replace(/<circle cx="32" cy="32" r="27" fill="none" stroke="#[0-9A-F]{6}" stroke-width="7" stroke-dasharray="[\d.]+ 169\.6"([^>]*)\/><text([^>]*)>([^<]+)<\/text>/g, (m, rest, tattr, label) => {
    const pct = Math.max(0, Math.min(100, ringPct(label)));
    return `<circle cx="32" cy="32" r="27" fill="none" stroke="${ringColor(pct)}" stroke-width="7" stroke-dasharray="${(pct * 1.696).toFixed(1)} 169.6"${rest}/><text${tattr}>${label}</text>`;
  })
  .replace(/\{\{(tier\d_(?:price|days)|care_price)\}\}/g, (m, k) => OFFER[k])
  .replace('{{CWV_TABLE}}', cwv).replace('{{LCP_CHART}}', chart).replace('{{BEFORE_AFTER}}', beforeAfter);

// ---- TRACE: every number in the rendered text must come from data.json, audit.json or offer.json ----
// Values are typed by UNIT: a number followed by a unit (s, ms, KB, px, minutes, hours, days, requests, ...) must equal a
// value of that unit: data.json fields by suffix (_s, _ms, _kb, requests, render_blocking), audit.json facts by their "unit".
// A bare number must equal some known value. Ranges "a to b" must be a min/max pair of one data.json field in one set, a
// before/after pair of one field on one page, or a declared fact range. Ratios "a/b" and "a of b" must be a declared pair.
// audit.json facts carrying "file" + "match" are checked against that file. Structural numbers are removed first: page
// footers, finding and fix numerals, tier names, step numbers, the review date, "fixes 1, 2 and 5".
const known = new Map(), ranges = new Set(), ratios = new Set();
const num = v => String(+(+v).toFixed(3));
const UNIT = { s: 's', second: 's', seconds: 's', ms: 'ms', kb: 'kb', px: 'px', minute: 'min', minutes: 'min', hour: 'h', hours: 'h', day: 'd', days: 'd', requests: 'requests', request: 'requests', 'render-blocking': 'render_blocking', users: 'users', pages: 'pages', page: 'pages', checks: 'checks', weights: 'weights' };
const add = (unit, v) => { for (const u of [unit, '*']) { if (!known.has(u)) known.set(u, new Set()); known.get(u).add(num(v)); } };
const unitOfField = k => /_s$/.test(k) ? 's' : /_ms$/.test(k) ? 'ms' : /_kb(\.|$)/.test(k) ? 'kb' : k === 'requests' ? 'requests' : k === 'render_blocking' ? 'render_blocking' : 'score';
const leaves = (o, out = {}, pre = '') => { for (const [k, v] of Object.entries(o)) { if (v && typeof v === 'object' && !Array.isArray(v)) leaves(v, out, pre + k + '.'); else if (typeof v === 'number') (out[pre + k] ??= []).push(v); } return out; };
const fmt = (k, v) => /_s$/.test(k) ? [v, s1(v)] : [v];
for (const set of [now, after]) {
  const pages = Object.values(set), byField = {};
  for (const p of pages) for (const [k, vs] of Object.entries(leaves(p))) (byField[k] ??= []).push(...vs);
  for (const [k, vs] of Object.entries(byField)) {
    for (const v of vs) for (const f of fmt(k, v)) add(unitOfField(k), f);
    const lo = Math.min(...vs), hi = Math.max(...vs);
    ranges.add(num(lo) + '-' + num(hi)); if (/_s$/.test(k)) ranges.add(num(s1(lo)) + '-' + num(s1(hi)));
  }
  const failing = pages.filter(p => p.lcp_s > 2.5), fails = failing.length;
  if (fails) ranges.add(num(s1(Math.min(...failing.map(p => p.lcp_s)))) + '-' + num(s1(Math.max(...failing.map(p => p.lcp_s))))); // "the failing pages take a to b s"
  add('pages', pages.length); add('pages', fails); ratios.add(fails + '/' + pages.length);
}
for (const k of Object.keys(now)) for (const [f, vs] of Object.entries(leaves(now[k]))) { // before -> after on one page and one field
  const b = leaves(after[k] || {})[f]; if (!b) continue;
  for (const a of fmt(f, vs[0])) for (const c of fmt(f, b[0])) ranges.add(num(a) + '-' + num(c));
}
add('reports', Object.keys(now).length + Object.keys(after).length); // appendix: one Lighthouse report per measured page
for (const p of [...Object.values(now), ...Object.values(after)]) add('version', p.lighthouse.split('.').slice(0, 2).join('.'));
for (const v of Object.values(SC)) add('checks', v); for (const v of Object.values(MC)) add('checks', v);
ratios.add(SC.pass + '/' + SC.total); ratios.add(MC.pass + '/' + MC.total);
const versions = new Set();
for (const [k, f] of Object.entries(AUDIT.facts)) {
  if (!f.source || !f.unit) throw new Error('audit.json fact needs "source" and "unit": ' + k);
  if (f.file && !readFileSync(f.file, 'utf8').includes(f.match)) throw new Error(`audit.json fact ${k}: "${f.match}" not found in ${f.file}`);
  for (const v of [].concat(f.value)) { if (f.unit === 'version') versions.add(String(v)); add(f.unit, v); }
  if (f.range) ranges.add(f.range.map(num).join('-'));
  if (f.ratio) ratios.add(f.ratio.join('/'));
}
for (const v of Object.values(OFFER)) if (/^\$?\d+$/.test(v)) add('*', String(v).replace('$', ''));
for (const [k, v] of Object.entries(OFFER)) if (/_days$/.test(k)) add('d', v);
const WORDNUM = Object.fromEntries(WORDS.map((w, i) => [w, i]).filter(([w]) => w !== 'one' && w !== 'zero')); // "one" is mostly the article
const text = html.replace(/<style[\s\S]*?<\/style>/g, '').replace(/<head>[\s\S]*?<\/head>/, '')
  .replace(/<svg[\s\S]*?<\/svg>/g, m => ' ¦ ' + [...m.matchAll(/<(text|title)([^>]*)>([^<]*)</g)].filter(x => !/x="52"/.test(x[2])).map(x => x[3]).join(' ¦ ') + ' ¦ ') // chart axis ticks (x=52) are scale, not findings
  .replace(/<div class="n">\d+<\/div>|<td data-label="#">\d+<\/td>|<tr><td>\d+<\/td>/g, ' ') // finding numbers, fix ranks, order-of-work steps
  .replace(/<\/?(?:td|th|tr|li|p|div|dd|dt|h[1-6]|small)\b[^>]*>/g, ' ¦ ') // cell/block boundary: a unit never binds across it
  .replace(/<[^>]+>/g, ' ').replace(/&[a-z]+;/g, ' ')
  .replace(/Page \d+ of \d+|\bTier \d\b|\bfixes? [\d, and]+\d|October 30, 2026|\bH1\b|\bwoff2\b/g, ' ')
  .replace(/\b(\d+)x\b/g, '$1') // "4x CPU"
  .replace(new RegExp('\\b(' + Object.keys(WORDNUM).join('|') + ')\\b', 'gi'), w => String(WORDNUM[w.toLowerCase()]))
  .replace(/\s+/g, ' ');
const problems = [];
const where = (str, i) => '"' + str.slice(Math.max(0, i - 40), i + 25).trim() + '"';
let rest = text;
rest = rest.replace(/(\d+(?:\.\d+)?)\s*(?:s|KB|ms)?\s+(?:to|down to|up to)\s+(?:about\s+)?(\d+(?:\.\d+)?)/g, (m, a, b, i) => {
  if (!ranges.has(num(a) + '-' + num(b))) problems.push('range ' + m + ' in ' + where(text, i)); return ' ';
});
rest = rest.replace(/\b(\d+) ?(?:\/|of) ?(\d+)\b/g, (m, a, b) => { if (!ratios.has(a + '/' + b)) problems.push('ratio "' + m + '"'); return ' '; });
rest = rest.replace(/\b\d+\.\d+\.\d+\b/g, (v, i) => { if (!versions.has(v)) problems.push('version ' + v + ' in ' + where(rest, i)); return ' '; });
// Page-bound evidence ("Home: 38 requests, 325 KB ..." / "Family law LCP 4.8 s ..."): each unit value must be THAT page's.
const names = ORDER.map(([, n]) => n).join('|');
for (const m of text.matchAll(new RegExp('\\b(' + names + ')(?::| (?=LCP))([^¦]*?)(?=\\b(?:' + names + ')(?::| LCP)|¦|$)', 'g'))) {
  const key = ORDER.find(([, n]) => n === m[1])[0], pv = {};
  for (const [f, vs] of Object.entries(leaves(now[key]))) for (const v of vs) for (const x of fmt(f, v)) (pv[unitOfField(f)] ??= new Set()).add(num(x));
  pv.cls = new Set([num(now[key].cls)]);
  for (const u of m[2].matchAll(/(?:CLS (\d+(?:\.\d+)?))|(\d+(?:\.\d+)?) ?(s|KB|requests|render-blocking)\b/g)) {
    const [unit, v] = u[1] ? ['cls', u[1]] : [UNIT[u[3].toLowerCase()], u[2]];
    if (!pv[unit]?.has(num(v))) problems.push(`page-bound ${m[1]}: ${u[0]} is not this page's value`);
  }
}
for (const m of rest.matchAll(/(\d+(?:\.\d+)?)(?:[ -]([A-Za-z-]+))?/g)) {
  const [, v, word] = m, unit = word && UNIT[word.toLowerCase()];
  const pool = known.get(unit || '*');
  if (!pool || !pool.has(num(v))) problems.push('number ' + v + (unit ? ' ' + word : '') + ' in ' + where(rest, m.index));
}
if (problems.length) { console.error('Untraced figures (add them to health-check/audit.json facts with a source, or fix the text):\n  ' + [...new Set(problems)].join('\n  ')); process.exit(1); }

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
console.log('pages', heights.length, 'overflowing pages', heights.filter(([o, s]) => s > o + 1).length, problems.length ? 'TRACE FAIL' : 'trace ok');
