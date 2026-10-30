// Form-field audit (resting + focused) on the static exports, in WebKit (iPhone Safari emulation) and Chromium.
// usage: node scripts/field-audit.mjs <origin> <demo> [--shots <out.png>] [--label before|after]
// Checks per field at 360, 390 and 1440:
//   - font-size >= 16px on phones (iOS zooms on focus below that)
//   - text fields 48px tall (44px minimum) and consistent within a form
//   - native appearance removed on text-like fields and selects (no iOS inner shadow / gray chrome)
//   - focus indicator present when focused (outline, or box-shadow/border change on the field or its pill wrapper),
//     not clipped by an overflow:hidden ancestor
//   - placeholder / selected option text not truncated
//   - checkbox/radio: label hit area >= 44px tall, focus indicator visible
import { chromium, webkit, devices } from 'playwright';
import { writeFileSync, mkdirSync } from 'node:fs';
import path from 'node:path';

const [origin, demo, ...rest] = process.argv.slice(2);
const shotOut = rest.includes('--shots') ? rest[rest.indexOf('--shots') + 1] : null;
const PAGES = {
  saltline: ['/contact/', '/book/'],
  'harbor-pine': ['/', '/contact/', '/family-law/'],
  makers: ['/', '/makers/', '/for-makers/', '/makers/coldwater-clay/'],
  store: ['/shop/', '/product/morning-fog/', '/bundle/', '/demo-cart/', '/demo-checkout/'],
}[demo];
const WIDTHS = [360, 390, 1440];
const SEL = 'input:not([type=hidden]):not([type=submit]), select, textarea';

async function audit(browserType, name) {
  const b = await browserType.launch();
  const issues = [];
  const shots = [];
  for (const w of WIDTHS) {
    const ctx = await b.newContext(w < 800 ? { ...devices['iPhone 13'], viewport: { width: w, height: 844 } } : { viewport: { width: w, height: 900 } });
    for (const pg of PAGES) {
      const p = await ctx.newPage();
      await p.goto(origin + '/' + demo + pg, { waitUntil: 'networkidle' });
      // On phones the directory filters live in a sheet: open it so its fields are measured too.
      const passes = [false];
      if (w < 960 && await p.locator('.filters-open').count()) passes.push(true);
      for (const sheet of passes) {
      if (sheet) { await p.click('.filters-open'); await p.waitForTimeout(150); }
      const scope = sheet ? '.filters.is-open ' : '';
      const n = await p.locator(SEL).count();
      for (let i = 0; i < n; i++) {
        const el = p.locator(SEL).nth(i);
        if (sheet && !(await el.evaluate(e => !!e.closest('.filters.is-open')))) continue;
        if (!sheet && await el.evaluate(e => !!e.closest('.filters') && getComputedStyle(e.closest('.filters')).position === 'fixed')) continue;
        if (!(await el.isVisible())) continue;
        await el.scrollIntoViewIfNeeded();
        const rest = await el.evaluate(e => {
          const s = getComputedStyle(e);
          const r = e.getBoundingClientRect();
          const tag = e.tagName.toLowerCase(), type = (e.getAttribute('type') || tag).toLowerCase();
          const out = { tag, type, id: e.id || e.name || e.className.split(' ')[0], fs: parseFloat(s.fontSize), h: Math.round(r.height), w: Math.round(r.width), app: s.webkitAppearance || s.appearance };
          // text truncation: placeholder (or selected option) text width vs the content box
          const c = document.createElement('canvas').getContext('2d'); c.font = `${s.fontWeight} ${s.fontSize} ${s.fontFamily}`;
          const txt = tag === 'select' ? (e.options[e.selectedIndex] || {}).text || '' : (e.value || e.placeholder || '');
          const avail = r.width - parseFloat(s.paddingLeft) - parseFloat(s.paddingRight) - parseFloat(s.borderLeftWidth) - parseFloat(s.borderRightWidth);
          out.trunc = ['checkbox', 'radio', 'file', 'date', 'textarea'].includes(type) ? false : c.measureText(txt).width > avail + 1;
          out.txt = txt.slice(0, 30);
          if (type === 'checkbox' || type === 'radio') { const l = e.closest('label') || document.querySelector(`label[for="${e.id}"]`); out.hit = l ? Math.round(l.getBoundingClientRect().height) : Math.round(r.height); }
          return out;
        });
        await el.focus();
        await p.keyboard.press('Shift'); // keyboard modality, so :focus-visible applies
        const focus = await el.evaluate(e => {
          const s = getComputedStyle(e);
          const wrap = e.closest('.search');
          const ws = wrap ? getComputedStyle(wrap) : null;
          const ring = (s.outlineStyle !== 'none' && parseFloat(s.outlineWidth) >= 2) || (s.boxShadow && s.boxShadow !== 'none') || (ws && ws.boxShadow !== 'none');
          // clipped: an ancestor with overflow hidden whose box cuts into the field plus 3px ring
          let clipped = false; const r = e.getBoundingClientRect();
          for (let a = e.parentElement; a && a !== document.body; a = a.parentElement) {
            const as = getComputedStyle(a); if (as.overflow === 'visible' && as.overflowX === 'visible') continue;
            const ar = a.getBoundingClientRect();
            if (r.left - 4 < ar.left || r.right + 4 > ar.right) { if (!wrap || a !== wrap) clipped = true; }
          }
          return { ring: !!ring, outline: s.outlineStyle + ' ' + s.outlineWidth + ' ' + s.outlineColor, shadow: (wrap ? ws.boxShadow : s.boxShadow).slice(0, 40), clipped, inside: !!wrap && s.outlineStyle !== 'none' };
        });
        const f = { ...rest, ...focus };
        const where = `${name}@${w} ${pg} ${f.tag}[${f.type}]#${f.id}`;
        const phone = w < 800;
        if (phone && f.fs < 16 && !['checkbox', 'radio', 'file'].includes(f.type)) issues.push(`${where}: font ${f.fs}px (<16, iOS zoom)`);
        if (!['checkbox', 'radio', 'file', 'textarea'].includes(f.type) && f.h < 44) issues.push(`${where}: height ${f.h}px`);
        if (!['checkbox', 'radio', 'file', 'range'].includes(f.type) && f.app && !/none/.test(f.app) && name === 'webkit' && phone) issues.push(`${where}: native appearance "${f.app}"`);
        if (!f.ring) issues.push(`${where}: no visible focus indicator`);
        if (f.inside) issues.push(`${where}: outline drawn inside the pill wrapper`);
        if (f.clipped) issues.push(`${where}: focus ring clipped by an overflow ancestor`);
        if (f.trunc) issues.push(`${where}: text truncated "${f.txt}"`);
        if ((f.type === 'checkbox' || f.type === 'radio') && f.hit < 44) issues.push(`${where}: hit area ${f.hit}px`);
        if (shotOut && w === 390 && name === 'webkit') {
          const box = await el.evaluate(e => { const x = e.closest('label') && ['checkbox', 'radio'].includes(e.type) ? e.closest('label') : (e.closest('.search') || e); const r = x.getBoundingClientRect(); return { x: r.left, y: r.top, w: r.width, h: r.height }; });
          const pad = 10;
          const clip = { x: Math.max(0, box.x - pad), y: Math.max(0, box.y - pad), width: Math.min(390 - Math.max(0, box.x - pad), box.w + pad * 2), height: box.h + pad * 2 };
          const buf = await p.screenshot({ clip });
          shots.push({ label: `${pg} ${f.tag}${f.type !== f.tag ? '[' + f.type + ']' : ''} ${f.id}`.slice(0, 60), data: buf.toString('base64') });
        }
        await el.evaluate(e => e.blur());
      }
      }
      await p.close();
    }
    await ctx.close();
  }
  await b.close();
  return { issues, shots };
}

const wk = await audit(webkit, 'webkit');
const cr = await audit(chromium, 'chromium');
const all = [...new Set([...wk.issues, ...cr.issues])];
console.log(`${demo}: ${all.length} issues`);
all.forEach(i => console.log('  ' + i));
if (shotOut) {
  mkdirSync(path.dirname(shotOut), { recursive: true });
  writeFileSync(shotOut + '.json', JSON.stringify(wk.shots));
}
process.exitCode = all.length ? 1 : 0;
