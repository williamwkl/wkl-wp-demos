// Renderer for the WordPress Health Check report (pure: no file writes). Used by scripts/health-check.mjs and
// scripts/test-health-check.mjs.
//
// Figures can only enter the report through placeholders, and every figure renders its OWN label and page:
//   {{metric now.home.cls}}                    -> "Home CLS 0.031"              (label map below, page from the path)
//   {{metric now.*.requests|range}}            -> "30 to 38 requests per page"  (|min/|max/|failing say which pages)
//   {{metric after.*.scores.performance|min}}  -> "Performance 99 or better on every page after the fixes"
//   {{pair family-law.lcp_s}}                  -> "Family law LCP: 4.8 s before, 2.1 s after"
//   {{pair *.requests}}                        -> "30 to 38 requests per page, down to 4 to 6 after the fixes"
//   {{group now scores.accessibility,scores.seo}} -> "Accessibility and SEO score 100 on every page" (fails if values differ)
//   {{page home requests,cls}}                 -> "Home: 38 requests, CLS 0.031" (page named once)
//   {{metric audit.facts.webp_px}}             -> "1200 px wide"                (the fact's own "render" in audit.json)
//   {{value audit.reviewed}}                   bare value: ONLY for label-free fields (dates, offer, report meta, check
//                                              counts) and |count / |word counts
// Numbering is produced here, never typed: {{#page}} footers ("Page 3 of 9"), {{#finding}}, {{#fix id}}, {{#step}},
// {{#tier id}}, and references to them: {{fixes a,b}} / {{Fixes a,b}}, {{tier id}}, {{pageref section-label}}.
// Fails closed: unknown field, pipe, label-map entry, fact render, reference or placeholder kind throws; and any digit in
// the visible text that did not come from a placeholder is returned as a problem (the build fails on it).
const O = '', C = '';
export const M = v => O + v + C;
export const unmark = s => s.replace(/[]/g, '');
const s1 = n => (+n).toFixed(1);
export const ORDER = [['home', 'Home', 'Home'], ['personal-injury', 'Personal injury', 'Pers. injury'], ['family-law', 'Family law', 'Family law'], ['our-attorneys', 'Our attorneys', 'Attorneys'], ['results', 'Results', 'Results'], ['contact', 'Contact', 'Contact'], ['legal', 'Legal', 'Legal']];
const PAGE = Object.fromEntries(ORDER.map(([k, n]) => [k, n]));
// Lighthouse field -> label (the ONLY place a metric's label is written). {v} is the value.
// Lighthouse field -> how its value reads (the ONLY place a metric's label is written). {v} is the value.
// noun: the label reads after the value ("38 requests"), so a range reads "30 to 38 requests per page".
export const METRIC_LABELS = {
  'scores.performance': { name: 'Performance', text: 'Performance {v}' }, 'scores.accessibility': { name: 'Accessibility', text: 'Accessibility {v}' },
  'scores.best-practices': { name: 'Best Practices', text: 'Best Practices {v}' }, 'scores.seo': { name: 'SEO', text: 'SEO {v}' },
  lcp_s: { name: 'LCP', text: 'LCP {v} s', unit: ' s' }, fcp_s: { name: 'FCP', text: 'FCP {v} s', unit: ' s' }, cls: { name: 'CLS', text: 'CLS {v}' },
  tbt_ms: { name: 'Total Blocking Time', text: 'Total Blocking Time {v} ms', unit: ' ms' },
  requests: { name: 'requests', text: '{v} requests', noun: true }, render_blocking: { name: 'render-blocking files', text: '{v} render-blocking files', noun: true },
  weight_kb: { name: 'page weight', text: 'page weight {v} KB', unit: ' KB' },
  'savings_kb.uses-responsive-images': { name: 'responsive-image savings', text: 'responsive-image savings {v} KB', unit: ' KB' },
  'savings_kb.modern-image-formats': { name: 'modern-format savings', text: 'modern-format savings {v} KB', unit: ' KB' },
};
const labelFor = (fieldPath, where) => { const l = METRIC_LABELS[fieldPath]; if (!l) throw new Error(`${where}: no label for field "${fieldPath}" in METRIC_LABELS`); return l; };
const fmtVal = (fieldPath, v) => /_s$/.test(fieldPath) ? (typeof v === 'string' && / to /.test(v) ? v.split(' to ').map(s1).join(' to ') : s1(v)) : v;

const WORDS = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen', 'twenty'];
const BARE_OK = [/^audit\.reviewed$/, /^offer\.\w+$/, /^reports\.\w+$/, /^audit\.(security|maintenance)\.\w+$/];

export function makeContext(D, AUDIT, OFFER, readFile) {
  for (const [k, f] of Object.entries(AUDIT.facts)) {
    if (!f.source) throw new Error('audit.json fact needs a "source": ' + k);
    if (!f.render || !f.render.includes('{v}')) throw new Error('audit.json fact needs a "render" with {v}: ' + k);
    if (f.file && !readFile(f.file).includes(f.match)) throw new Error(`audit.json fact ${k}: "${f.match}" not found in ${f.file}`);
  }
  const now = D.pages.naive, after = D.pages.optimized;
  const count = (list, st) => list.filter(c => c.status === st).length;
  const sec = AUDIT.security, mai = AUDIT.maintenance;
  return {
    now, after, AUDIT, OFFER, LCP_GOOD: AUDIT.facts.lcp_good_s.value,
    root: {
      now, after, offer: OFFER,
      audit: {
        security: { total: sec.length, pass: count(sec, 'pass'), fail: count(sec, 'fail'), partial: count(sec, 'partial'), not_checked: count(sec, 'not_checked'), other_open: sec.filter(c => c.status !== 'pass' && !c.top_fix).length },
        maintenance: { total: mai.length, pass: count(mai, 'pass') },
        facts: Object.fromEntries(Object.entries(AUDIT.facts).map(([k, f]) => [k, f.value])),
        reviewed: new Date(AUDIT.reviewed + 'T12:00:00Z').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric', timeZone: 'UTC' }),
      },
      reports: { measured: Object.keys(now).length + Object.keys(after).length, lighthouse: [...new Set(Object.values(now).map(p => p.lighthouse.split('.').slice(0, 2).join('.')))].join(', ') },
    },
  };
}

function evaluate(ctx, expr) {
  const [path, ...pipes] = expr.split('|');
  let cur = [ctx.root], wild = false;
  for (const part of path.split('.')) {
    if (part === '*') { wild = true; cur = cur.flatMap(o => Object.values(o)); continue; }
    cur = cur.map(o => { if (o == null || typeof o !== 'object' || !(part in o)) throw new Error(`unknown field {{${expr}}} (at "${part}")`); return o[part]; });
  }
  let v = wild ? cur : cur[0];
  const PIPES = {
    min: x => Math.min(...x.map(Number)), max: x => Math.max(...x.map(Number)),
    range: x => { const n = x.map(Number), lo = Math.min(...n), hi = Math.max(...n), f = y => x.find(z => +z === y); return lo === hi ? String(f(lo)) : f(lo) + ' to ' + f(hi); },
    count: x => x.length, failing: x => x.filter(y => +y > ctx.LCP_GOOD), s1: x => Array.isArray(x) ? x.map(s1) : s1(x),
    word: x => WORDS[x] ?? String(x), Word: x => { const w = WORDS[x] ?? String(x); return w[0].toUpperCase() + w.slice(1); },
  };
  const all = wild ? v.map(Number) : null;
  for (const p of pipes) { if (!PIPES[p]) throw new Error(`unknown pipe |${p} in {{${expr}}}`); v = PIPES[p](v); }
  if (Array.isArray(v)) throw new Error(`{{${expr}}} is a list: reduce it with a pipe`);
  return { path, pipes, v, wild, all };
}

function renderMetric(ctx, expr) {
  const { path, pipes, v, all } = evaluate(ctx, expr);
  const fact = path.match(/^audit\.facts\.(\w+)$/);
  if (fact) return ctx.AUDIT.facts[fact[1]].render.replace('{v}', M(v));
  const m = path.match(/^(now|after)\.([\w*-]+)\.(.+)$/);
  if (!m) throw new Error(`{{metric ${expr}}}: not a metric field`);
  const [, set, page, fieldPath] = m;
  const l = labelFor(fieldPath, `{{metric ${expr}}}`);
  if (pipes.includes('count') || pipes.includes('word')) throw new Error(`{{metric ${expr}}}: a count is a {{value}}`);
  const text = l.text.replace('{v}', M(fmtVal(fieldPath, v)));
  const after = set === 'after' ? ' after the fixes' : '';
  if (page !== '*') { if (!PAGE[page]) throw new Error(`{{metric ${expr}}}: unknown page "${page}"`); return `${PAGE[page]} ${text}${after}`; }
  const same = all && Math.min(...all) === Math.max(...all);
  if (pipes.includes('failing')) return `${text}${after}`; // the sentence names the failing pages via {{value ...|failing|count}}
  if (pipes.includes('range')) return same ? `${text} on every page${after}` : `${text}${l.noun ? ' per page' : ' across pages'}${after}`;
  if (pipes.includes('min') || pipes.includes('max')) {
    if (same) return `${text} on every page${after}`;
    const score = /^scores\./.test(fieldPath);
    if (pipes.includes('max')) return l.noun ? `at most ${text} per page${after}` : score ? `${text} on the best page${after}` : `${text} on the slowest page${after}`;
    return score ? `${text} or better on every page${after}` : `${text} on the fastest page${after}`;
  }
  throw new Error(`{{metric ${expr}}}: a page-wide figure needs |range, |min, |max or |failing`);
}
// {{group now scores.accessibility,scores.best-practices,scores.seo}} -> "Accessibility, Best Practices and SEO score 100 on
// every page". Fails unless every listed field has the same value on every page of that set.
function renderGroup(ctx, set, fields) {
  if (!['now', 'after'].includes(set)) throw new Error(`{{group ${set} ...}}: set must be now or after`);
  for (const f of fields) if (!/^scores\./.test(f)) throw new Error(`{{group ${set} ${fields}}}: only scores.* fields can be grouped ("${f}" is not a score)`);
  const vals = fields.map(f => { labelFor(f, `{{group ${set} ${fields}}}`); return [evaluate(ctx, `${set}.*.${f}|min`).v, evaluate(ctx, `${set}.*.${f}|max`).v]; }).flat().map(Number);
  if (Math.min(...vals) !== Math.max(...vals)) throw new Error(`{{group ${set} ${fields}}}: values differ (${[...new Set(vals)].join(', ')}); write them separately`);
  const names = fields.map(f => METRIC_LABELS[f].name);
  return `${list(names)} score${names.length === 1 ? 's' : ''} ${M(vals[0])} on every page${set === 'after' ? ' after the fixes' : ''}`;
}
// {{pair family-law.lcp_s}} -> "Family law LCP: 4.8 s before, 2.1 s after";  {{pair *.requests}} -> "30 to 38 requests per
// page before, 4 to 6 after";  {{pair *.scores.performance}} -> "Performance: 81 to 97 before, 99 to 100 after".
function renderPair(ctx, expr) {
  const m = expr.match(/^([\w*-]+)\.(.+)$/);
  if (!m) throw new Error(`{{pair ${expr}}}: expected page.field`);
  const [, page, fieldPath] = m, l = labelFor(fieldPath, `{{pair ${expr}}}`);
  const get = set => { const r = evaluate(ctx, `${set}.${page}.${fieldPath}` + (page === '*' ? '|range' : '')); return M(fmtVal(fieldPath, r.v)); };
  if (page !== '*' && !PAGE[page]) throw new Error(`{{pair ${expr}}}: unknown page "${page}"`);
  const unit = l.unit || '';
  if (l.noun) return `${page === '*' ? '' : PAGE[page] + ': '}${get('now')} ${l.name}${page === '*' ? ' per page' : ''}, down to ${get('after')} after the fixes`;
  return `${page === '*' ? '' : PAGE[page] + ' '}${l.name}: ${get('now')}${unit} before, ${get('after')}${unit} after`;
}
// {{page home requests,weight_kb,render_blocking,cls}} -> "Home: 38 requests, page weight 325 KB, 28 render-blocking files, CLS 0.031"
function renderPage(ctx, page, fields, set = 'now') {
  if (!PAGE[page]) throw new Error(`{{page ${page} ...}}: unknown page`);
  const parts = fields.map(f => labelFor(f, `{{page ${page} ${fields}}}`).text.replace('{v}', M(fmtVal(f, evaluate(ctx, `${set}.${page}.${f}`).v))));
  return `${PAGE[page]}: ${parts.join(', ')}`;
}
const list = xs => xs.length === 1 ? xs[0] : xs.slice(0, -1).join(', ') + ' and ' + xs.at(-1);

export function render(template, ctx, blocks = {}) {
  let html = template.replace(/\{\{([A-Z][A-Z0-9_]*)\}\}/g, (m, k) => { if (!(k in blocks)) throw new Error('unknown block ' + m); return blocks[k]; });
  // numbering defined by the renderer, in document order
  const defs = { fix: {}, tier: {} }; let n = { page: 0, finding: 0, fix: 0, step: 0, tier: 0 };
  // pages = every <section class="page"> in order (the cover counts); a footer shows the number of the section it sits in
  const starts = [...html.matchAll(/<section class="page\b[^"]*"[^>]*>/g)].map(x => ({ at: x.index, label: (x[0].match(/aria-label="([^"]+)"/) || [])[1] || '' }));
  const pages = starts.map(x => x.label);
  const sectionAt = i => starts.filter(x => x.at < i).length;
  html = html.replace(/\{\{#(page|finding|fix|step|tier)(?: ([\w-]+))?\}\}/g, (m, kind, id, at) => {
    n[kind]++;
    if (kind === 'fix' || kind === 'tier') { if (!id) throw new Error(m + ' needs an id'); if (defs[kind][id]) throw new Error('duplicate ' + m); defs[kind][id] = n[kind]; }
    if (kind === 'page') { if (id) throw new Error(m + ' takes no id'); return `Page ${M(sectionAt(at))} of ${M(starts.length)}`; }
    if (kind === 'finding') return M(String(n.finding).padStart(2, '0'));
    if (kind === 'tier') return 'Tier ' + M(n.tier);
    return M(n[kind]);
  });
  html = html.replace(/\{\{(fixes|Fixes|fix|tier|pageref) ([\w,-]+)\}\}/g, (m, kind, ids) => {
    const lk = kind.toLowerCase();
    if (lk === 'pageref') { const i = pages.findIndex(p => p.toLowerCase().replace(/[^a-z0-9]+/g, '-') === ids); if (i < 0) throw new Error('unknown page ' + m); return 'page ' + M(i + 1); }
    const table = lk === 'tier' ? defs.tier : defs.fix;
    const nums = ids.split(',').map(id => { if (!table[id]) throw new Error(`unknown reference ${m} ("${id}")`); return M(table[id]); });
    return (lk === 'tier' ? 'Tier' : kind) + ' ' + list(nums);
  });
  html = html.replace(/\{\{group (\w+) ([\w.,-]+)\}\}/g, (m, set, f) => renderGroup(ctx, set, f.split(',')));
  html = html.replace(/\{\{pair ([\w.*-]+)\}\}/g, (m, e) => renderPair(ctx, e));
  html = html.replace(/\{\{page ([\w-]+) ([\w.,-]+)\}\}/g, (m, pg, f) => renderPage(ctx, pg, f.split(',')));
  html = html.replace(/\{\{(metric|value) ([a-z][\w.*|-]*)\}\}/g, (m, kind, expr) => {
    if (kind === 'metric') return renderMetric(ctx, expr);
    const { path, pipes, v } = evaluate(ctx, expr);
    if (!(BARE_OK.some(re => re.test(path)) || pipes.includes('count'))) throw new Error(`{{value ${expr}}}: "${path}" carries a label; use {{metric ${expr}}}`);
    return M(v);
  });
  const left = html.match(/\{\{[^}]*\}\}/);
  if (left) throw new Error('unknown placeholder ' + left[0]);
  return { html, problems: untraced(html) };
}

// Every digit in the visible text must sit inside a renderer marker.
export function untraced(markedHtml) {
  const text = markedHtml.replace(/<style[\s\S]*?<\/style>/g, ' ').replace(/<head>[\s\S]*?<\/head>/, ' ')
    .replace(/<svg[\s\S]*?<\/svg>/g, m => ' ¦ ' + [...m.matchAll(/<(?:text|title)[^>]*>([^<]*)</g)].map(x => x[1]).join(' ¦ ') + ' ¦ ')
    .replace(/<[^>]+>/g, ' ').replace(/&[a-z]+;/g, ' ')
    .replace(/[^]*/g, ' ⟨field⟩ ');
  return [...text.matchAll(/\d/g)].map(m => text.slice(Math.max(0, m.index - 45), m.index + 25).replace(/\s+/g, ' ').trim());
}
