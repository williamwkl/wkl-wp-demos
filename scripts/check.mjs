// Responsive QA + screenshots.
// usage: node scripts/check.mjs <baseUrl> <demo> <path> [path ...]
// For each width: horizontal overflow, smallest body text on phones, tap targets under 44px
// (inline text links inside paragraphs/lists are exempt, per WCAG 2.5.8 inline exception), images
// rendering with a broken aspect ratio. Screenshots at 390/768/1440 go to screens/<demo>/.
import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';
const [base, demo, ...paths] = process.argv.slice(2);
const WIDTHS = [360, 390, 768, 1024, 1280, 1440];
const SHOTS = new Set([390, 768, 1440]);
await mkdir(`screens/${demo}`, { recursive: true });
const browser = await chromium.launch();
let fails = 0;
for (const p of paths) {
  for (const w of WIDTHS) {
    const page = await browser.newPage({ viewport: { width: w, height: w < 800 ? 844 : 900 }, deviceScaleFactor: 1 });
    await page.goto(base.replace(/\/$/, '') + p, { waitUntil: 'networkidle' });
    await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 600) { window.scrollTo(0, y); await new Promise(r => setTimeout(r, 40)); } window.scrollTo(0, 0); });
    const r = await page.evaluate((w) => {
      const out = { overflow: document.documentElement.scrollWidth - window.innerWidth, smallText: [], smallTargets: [], badImg: [] };
      const vis = el => { const s = getComputedStyle(el); const b = el.getBoundingClientRect(); return s.visibility !== 'hidden' && s.display !== 'none' && b.width > 0 && b.height > 0; };
      if (w <= 390) for (const el of document.querySelectorAll('main p, main li, main dd, main summary, main label')) {
        if (!vis(el) || el.closest('.screen-reader-text')) continue;
        const fs = parseFloat(getComputedStyle(el).fontSize);
        const isMeta = el.matches('.small,.meta,.strip-note,.lbl,.eyebrow,.demo-note,.hint,.proof li,.mini-faq p,cite,.tour-facts dd,.badge,.chip,.card-meta, .card-meta *,.result-count,.wpcf7-not-valid-tip') || el.closest('.meta,.proof,.mini-faq,.tour-facts,.card-meta,.footer');
        if (fs < 16 && !isMeta) out.smallText.push(el.tagName + ' ' + fs + 'px "' + el.textContent.trim().slice(0, 30) + '"');
      }
      for (const el of document.querySelectorAll('a[href], button, input:not([type=hidden]), select, textarea, summary')) {
        if (!vis(el) || el.closest('.screen-reader-text') || el.classList.contains('skip-link')) continue;
        if (el.tagName === 'A' && el.closest('p, li, dd, td, .foot-addr') && !el.closest('nav, .wp-block-navigation, .wp-block-button, .footer ul') && !el.className.match(/btn|button/)) continue;
        if (el.type === 'checkbox' || el.type === 'radio') { const lab = el.closest('label'); if (lab && lab.getBoundingClientRect().height >= 24) continue; }
        const b = el.getBoundingClientRect();
        if (b.height < 44 && b.width < 44 || b.height < 24) out.smallTargets.push(`${el.tagName}.${(el.className || '').toString().split(' ')[0]} "${(el.textContent || el.value || el.getAttribute('aria-label') || '').trim().slice(0, 24)}" ${Math.round(b.width)}x${Math.round(b.height)}`);
        else if (b.height < 44 && !el.closest('.footer')) out.smallTargets.push(`${el.tagName} "${(el.textContent || el.value || el.getAttribute('aria-label') || '').trim().slice(0, 24)}" h=${Math.round(b.height)}`);
      }
      for (const img of document.querySelectorAll('img')) {
        if (!vis(img) || !img.naturalWidth) continue;
        const s = getComputedStyle(img); const b = img.getBoundingClientRect();
        const nat = img.naturalWidth / img.naturalHeight, ren = b.width / b.height;
        if (s.objectFit !== 'cover' && Math.abs(nat - ren) / nat > 0.03) out.badImg.push(img.src.split('/').pop() + ` ${nat.toFixed(2)} vs ${ren.toFixed(2)}`);
      }
      return out;
    }, w);
    const bad = r.overflow > 0 || r.smallText.length || r.smallTargets.length || r.badImg.length;
    if (bad) fails++;
    console.log(`${bad ? 'FAIL' : 'ok  '} ${p} @${w}: overflow=${r.overflow}` + (r.smallText.length ? ` smallText=${JSON.stringify(r.smallText.slice(0, 4))}` : '') + (r.smallTargets.length ? ` targets=${JSON.stringify(r.smallTargets.slice(0, 6))}` : '') + (r.badImg.length ? ` img=${JSON.stringify(r.badImg)}` : ''));
    if (SHOTS.has(w)) {
      const name = (p.replace(/^\/|\/$/g, '').replace(/[/?=&]+/g, '-') || 'home');
      await page.screenshot({ path: `screens/${demo}/${name}-${w}.png`, fullPage: true });
    }
    await page.close();
  }
}
await browser.close();
console.log(fails ? `${fails} width/page combos FAILED` : 'all widths passed');
