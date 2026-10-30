// Negative tests for the Health Check renderer: every way a figure could enter the report without its own label, page
// or source must fail. usage: node scripts/test-health-check.mjs   (exit 1 on any failure)
import { readFileSync } from 'node:fs';
import { render, makeContext } from './health-render.mjs';
import { loadContext, buildBlocks } from './health-check.mjs';

const ctx = loadContext();
let failed = 0;
const t = (name, fn) => { try { fn(); console.log('ok   ' + name); } catch (e) { failed++; console.log('FAIL ' + name + ': ' + e.message); } };
const throws = (tpl, re) => () => { let msg = null; try { render(tpl, ctx); } catch (e) { msg = e.message; } if (!msg) throw new Error('rendered without error'); if (re && !re.test(msg)) throw new Error('wrong error: ' + msg); };
const flags = tpl => () => { const { problems } = render(tpl, ctx); if (!problems.length) throw new Error('no problem reported for ' + tpl); };
const text = tpl => render(tpl, ctx).html.replace(/[]/g, '').replace(/<[^>]+>/g, '');

// missing / distant label: a metric field can never be printed bare
t('missing label: bare value of a metric field fails', throws('<p>{{value now.home.lcp_s}}</p>', /carries a label/));
t('distant label: hand-written label far from a bare value fails', throws('<p>LCP for the home page, measured on a phone with slow 4G: {{value now.home.lcp_s}}</p>', /carries a label/));
t('bare fact fails (facts render their own label)', throws('<p>{{value audit.facts.webp_px}}</p>', /carries a label/));
// the metric form carries its own label and page, so a hand-written wrong label/page cannot change what the figure says
t('metric renders its own label and page', () => { const s = text('<p>{{metric now.home.scores.accessibility}}</p>'); if (s !== 'Home Accessibility 100') throw new Error(s); });
t('wrong page next to a metric: the figure still names its own page', () => { const s = text('<p>Family law {{metric now.home.lcp_s}}</p>'); if (!/^Family law Home LCP \d/.test(s)) throw new Error(s); });
t('wrong label next to a metric: the figure still names its own field', () => { const s = text('<p>SEO {{metric now.home.scores.accessibility}}</p>'); if (!/^SEO Home Accessibility \d/.test(s)) throw new Error(s); });
t('after-fixes figures say so', () => { const s = text('<p>{{metric after.family-law.lcp_s}}</p>'); if (!/^Family law LCP [\d.]+ s after the fixes$/.test(s)) throw new Error(s); });
// group: one sentence for several fields, only when every value agrees
t('group renders one sentence when all values agree', () => { const s = text('<p>{{group now scores.accessibility,scores.best-practices,scores.seo}}</p>'); if (!/^Accessibility, Best Practices and SEO score \d+ on every page$/.test(s)) throw new Error(s); });
t('group fails when values differ', throws('<p>{{group now scores.performance,scores.seo}}</p>', /values differ/));
t('group fails on a single field that varies across pages', throws('<p>{{group now scores.performance}}</p>', /values differ/));
t('group fails on an unknown field', throws('<p>{{group now scores.speed}}</p>', /unknown field|no label/));
t('group fails on an unknown set', throws('<p>{{group later scores.seo}}</p>', /now or after/));
// pair: before/after from the same field and page
t('pair renders page, label, before and after', () => { const s = text('<p>{{pair family-law.lcp_s}}</p>'); const a = ctx.now['family-law'].lcp_s.toFixed(1), b = ctx.after['family-law'].lcp_s.toFixed(1); if (s !== `Family law LCP: ${a} s before, ${b} s after`) throw new Error(s); });
t('pair over all pages renders ranges per page', () => { const s = text('<p>{{pair *.requests}}</p>'); if (!/^\d+ to \d+ requests per page, down to \d+ to \d+ after the fixes$/.test(s)) throw new Error(s); });
t('pair fails on an unknown page', throws('<p>{{pair nowhere.lcp_s}}</p>', /unknown/));
t('pair fails on a field without a label', throws('<p>{{pair home.bytes_by_type_kb.Font}}</p>', /no label/));
t('page lists several fields with the page named once', () => { const s = text('<p>{{page home requests,cls}}</p>'); if (!/^Home: \d+ requests, CLS [\d.]+$/.test(s)) throw new Error(s); });
t('page fails on an unknown page', throws('<p>{{page nowhere requests}}</p>', /unknown page/));
// unknown field / page / map entry / pipe / placeholder kind
t('unknown field fails', throws('<p>{{metric now.home.lcpx}}</p>', /unknown field/));
t('unknown page fails', throws('<p>{{metric now.nowhere.lcp_s}}</p>', /unknown field/));
t('field without a label-map entry fails', throws('<p>{{metric now.home.bytes_by_type_kb.Font}}</p>', /no label/));
t('unknown pipe fails', throws('<p>{{metric now.*.lcp_s|median}}</p>', /unknown pipe/));
t('unreduced list fails', throws('<p>{{metric now.*.lcp_s}}</p>', /is a list/));
t('page-wide metric without a scope pipe fails', throws('<p>{{metric now.*.lcp_s|s1}}</p>', /is a list|needs/));
t('unknown placeholder kind fails', throws('<p>{{number now.home.lcp_s}}</p>', /unknown placeholder/));
t('fact without a render label fails', () => { const A = JSON.parse(readFileSync('health-check/audit.json', 'utf8')); delete A.facts.webp_px.render; let ok = false; try { makeContext(JSON.parse(readFileSync('health-check/data.json', 'utf8')), A, {}, f => readFileSync(f, 'utf8')); } catch { ok = true; } if (!ok) throw new Error('accepted'); });
t('fact whose file no longer matches fails', () => { const A = JSON.parse(readFileSync('health-check/audit.json', 'utf8')); A.facts.webp_quality.match = 'quality 61'; let ok = false; try { makeContext(JSON.parse(readFileSync('health-check/data.json', 'utf8')), A, {}, f => readFileSync(f, 'utf8')); } catch { ok = true; } if (!ok) throw new Error('accepted'); });
// typed digits, including exception-shaped ones: only the renderer may produce numbers
for (const typed of ['<p>LCP 4.8 s</p>', '<p>every page scores 100</p>', '<p>see page 123</p>', '<p>Fixes 123</p>', '<p>fixes 1, 2 and 5</p>', '<span>Page 2 of 9</span>', '<span class="tag">Tier 2</span>', '<div class="n">01</div>', '<td data-label="#">1</td>', '<svg><text>5 s</text></svg>', '<p>H1</p>'])
  t('typed digit fails: ' + typed, flags(typed));
// references must resolve to renderer-numbered items
t('reference to an unknown fix fails', throws('<td data-label="#">{{#fix a}}</td><p>{{fixes b}}</p>', /unknown reference/));
t('reference to an unknown tier fails', throws('<p>{{tier nope}}</p>', /unknown reference/));
t('reference to an unknown page fails', throws('<section class="page" aria-label="X"></section><p>{{pageref nope}}</p>', /unknown page/));
t('numbering comes from the renderer', () => { const s = text('<section class="page cover"></section><section class="page" aria-label="A"><td>{{#fix a}}</td><td>{{#fix b}}</td><span>{{#page}}</span></section><p>{{Fixes b,a}} on {{pageref a}}</p>'); if (!/^12Page 2 of 2Fixes 2 and 1 on page 2$/.test(s)) throw new Error(s); });
// the real report renders clean
t('the real report has no digit outside a placeholder', () => { const { problems } = render(readFileSync('health-check/report.template.html', 'utf8'), ctx, buildBlocks(ctx)); if (problems.length) throw new Error(problems.slice(0, 3).join(' | ')); });

console.log(failed ? `${failed} FAILED` : 'all health-check renderer tests passed');
process.exitCode = failed ? 1 : 0;
