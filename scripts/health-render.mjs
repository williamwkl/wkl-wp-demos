// Renderer for the WordPress Health Check report (pure: no file writes). Used by scripts/health-check.mjs and
// scripts/test-health-check.mjs.
//
// Figures can only enter the report through placeholders, and a metric always renders its OWN label and page:
//   {{metric now.home.scores.accessibility}}   -> "Home Accessibility 100"     (label map below, page from the path)
//   {{metric after.family-law.lcp_s}}          -> "Family law, after fixes: LCP 2.1 s"
//   {{metric now.*.requests|range}}            -> "30 to 38 requests"           (all pages; |min/|max say lowest/highest)
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
export const METRIC_LABELS = {
  'scores.performance': 'Performance {v}', 'scores.accessibility': 'Accessibility {v}', 'scores.best-practices': 'Best Practices {v}', 'scores.seo': 'SEO {v}',
  lcp_s: 'LCP {v} s', fcp_s: 'FCP {v} s', cls: 'CLS {v}', tbt_ms: 'Total Blocking Time {v} ms',
  requests: '{v} requests', weight_kb: 'page weight {v} KB', render_blocking: '{v} render-blocking resources',
  'savings_kb.uses-responsive-images': 'responsive-images savings {v} KB', 'savings_kb.modern-image-formats': 'modern-formats savings {v} KB',
};
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
  const { path, pipes, v, wild, all } = evaluate(ctx, expr);
  const fact = path.match(/^audit\.facts\.(\w+)$/);
  if (fact) return ctx.AUDIT.facts[fact[1]].render.replace('{v}', M(v));
  const m = path.match(/^(now|after)\.([\w*-]+)\.(.+)$/);
  if (!m) throw new Error(`{{metric ${expr}}}: not a metric field`);
  const [, set, page, fieldPath] = m;
  const label = METRIC_LABELS[fieldPath];
  if (!label) throw new Error(`{{metric ${expr}}}: no label for field "${fieldPath}" in METRIC_LABELS`);
  if (pipes.includes('count') || pipes.includes('word')) throw new Error(`{{metric ${expr}}}: a count is a {{value}}`);
  const shown = /_s$/.test(fieldPath) && !pipes.includes('s1') ? (typeof v === 'string' && / to /.test(v) ? v.split(' to ').map(s1).join(' to ') : s1(v)) : v;
  let text = label.replace('{v}', M(shown));
  const afterTag = set === 'after' ? ', after fixes' : '';
  if (page !== '*') { if (!PAGE[page]) throw new Error(`{{metric ${expr}}}: unknown page "${page}"`); return `${PAGE[page]}${afterTag}: ${text}`; }
  const failingOnly = pipes.includes('failing');
  const scope = failingOnly ? 'failing pages' : pipes.includes('min') || pipes.includes('max') ? (all && Math.min(...all) === Math.max(...all) ? 'every page' : (pipes.includes('min') ? 'lowest page' : 'highest page')) : 'all pages';
  return `${text} (${scope}${afterTag})`;
}

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
  const list = xs => xs.length === 1 ? xs[0] : xs.slice(0, -1).join(', ') + ' and ' + xs.at(-1);
  html = html.replace(/\{\{(fixes|Fixes|fix|tier|pageref) ([\w,-]+)\}\}/g, (m, kind, ids) => {
    const lk = kind.toLowerCase();
    if (lk === 'pageref') { const i = pages.findIndex(p => p.toLowerCase().replace(/[^a-z0-9]+/g, '-') === ids); if (i < 0) throw new Error('unknown page ' + m); return 'page ' + M(i + 1); }
    const table = lk === 'tier' ? defs.tier : defs.fix;
    const nums = ids.split(',').map(id => { if (!table[id]) throw new Error(`unknown reference ${m} ("${id}")`); return M(table[id]); });
    return (lk === 'tier' ? 'Tier' : kind) + ' ' + list(nums);
  });
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
