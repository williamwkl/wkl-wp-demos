// Interaction smoke test on the static export: mobile menu opens/closes with keyboard, demo form success state.
import { chromium } from 'playwright';
const base = process.argv[2];
const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 390, height: 844 } });
await p.goto(base + '/');
await p.click('button.wp-block-navigation__responsive-container-open');
const open = await p.isVisible('.wp-block-navigation__responsive-container.is-menu-open a:has-text("FAQ")');
await p.keyboard.press('Escape');
const closed = !(await p.isVisible('.wp-block-navigation__responsive-container.is-menu-open'));
await p.goto(base + '/contact/');
await p.click('input[type=submit]');
const blocked = await p.isVisible('.wpcf7-form');
await p.fill('#bk-name', 'Test Paddler'); await p.fill('#bk-email', 'test@example.com');
await p.selectOption('#bk-tour', { index: 1 }); await p.fill('#bk-date', '2027-06-01');
await p.check('.wpcf7-acceptance input'); await p.click('input[type=submit]');
const ok = await p.textContent('.form-success').catch(() => null);
console.log({ menuOpens: open, escCloses: closed, emptySubmitBlocked: blocked, success: ok && ok.slice(0, 40) });
await p.screenshot({ path: 'screens/saltline/contact-success-390.png' });
await b.close();
