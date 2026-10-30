// With JavaScript off: empty submits are blocked by native validation, and a filled submit lands on /demo-form/
// with NO query string (nothing a visitor typed may end up in the URL). usage: node scripts/smoke-nojs-forms.mjs <origin>
import { chromium } from 'playwright';
const base = process.argv[2];
const cases = [
  ['saltline/contact/', '.wpcf7-form'],
  ['harbor-pine/', '#intake form'], ['harbor-pine/contact/', '#intake form'], ['harbor-pine/family-law/', 'aside form'],
  ['makers/for-makers/', '.demo-form'], ['makers/makers/coldwater-clay/', '.demo-form'],
];
const b = await chromium.launch(); const ctx = await b.newContext({ javaScriptEnabled: false }); const p = await ctx.newPage();
let fail = 0;
for (const [u, sel] of cases) {
  await p.goto(base + '/' + u);
  const form = p.locator(sel).first();
  await form.locator('[type=submit]').click(); await p.waitForTimeout(300);
  const blocked = p.url().endsWith('/' + u);
  for (const el of await form.locator('input:not([type=checkbox]):not([type=file]):not([type=submit]), textarea').all()) {
    const t = await el.getAttribute('type');
    await el.fill(t === 'email' ? 'a@example.com' : t === 'date' ? '2027-06-01' : t === 'number' ? '2' : t === 'url' ? 'https://example.com' : 'Test');
  }
  for (const s of await form.locator('select').all()) await s.selectOption({ index: 1 });
  for (const c of await form.locator('input[type=checkbox]').all()) await c.check();
  await form.locator('[type=submit]').click(); await p.waitForLoadState();
  const url = new URL(p.url());
  const ok = blocked && url.pathname.endsWith('/demo-form/') && url.search === '';
  if (!ok) fail++;
  console.log(ok ? 'ok  ' : 'FAIL', u, 'emptyBlocked=' + blocked, 'landed=' + url.pathname + url.search);
}
await b.close(); process.exitCode = fail ? 1 : 0;
