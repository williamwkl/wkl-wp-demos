// Live WooCommerce flow in the Playground site: add to cart, guest checkout with the offline demo payment, order received.
// usage: node scripts/smoke-store-live.mjs http://127.0.0.1:9404 [product-path]
import { chromium } from 'playwright';
const [base, product = '/product/morning-fog/'] = process.argv.slice(2);
const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1280, height: 900 } });
const errors = []; p.on('pageerror', e => errors.push(e.message));
await p.goto(base + product, { waitUntil: 'networkidle' });
await p.click('form.pdp-form button[type=submit]');
await p.waitForLoadState('networkidle');
// bundle: three picks, real cart items plus the 12 percent fee
await p.goto(base + '/bundle/', { waitUntil: 'networkidle' });
const picks = p.locator('.pick-item'); for (const i of [0, 6, 8]) await picks.nth(i).click();
await p.click('[data-b-cta]');
await p.waitForLoadState('networkidle');
await p.waitForURL(/\/cart\//);
const cartText = (await p.textContent('main')).replace(/\s+/g, ' ');
const bundleFee = /Bundle discount/.test(cartText);
// qty abuse: raise one bundle line to 9 through the Store API (what the cart's stepper calls); the fee must stay exactly one set.
const qty = await p.evaluate(async () => {
  const r = await fetch('/wp-json/wc/store/v1/cart', { credentials: 'same-origin' }); const nonce = r.headers.get('Nonce'); const cart = await r.json();
  const lines = cart.items.filter(i => (i.item_data || []).some(d => /Bundle/.test(d.name || d.key)));
  const minor = 10 ** cart.totals.currency_minor_unit;
  const setPrice = lines.reduce((s, l) => s + Number(l.prices.price) * l.quantity, 0) / minor;
  const feeBefore = -Number(cart.totals.total_fees) / minor;
  const u = await fetch('/wp-json/wc/store/v1/cart/update-item', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Nonce: nonce }, body: JSON.stringify({ key: lines[0].key, quantity: 9 }) });
  const after = await u.json();
  return { bundleLines: lines.length, setPrice, feeBefore, feeAfter: -Number(after.totals.total_fees) / minor, expected: Math.round(setPrice * 0.12 * 100) / 100 };
});
qty.oneSetOnly = qty.bundleLines === 3 && Math.abs(qty.feeBefore - qty.expected) < 0.005 && Math.abs(qty.feeAfter - qty.expected) < 0.005;
await p.goto(base + '/checkout/', { waitUntil: 'networkidle' });
const fill = async (label, v) => { const f = p.getByLabel(label, { exact: true }).first(); if (await f.count()) await f.fill(v); };
await fill('Email address', 'buyer@example.com');
await fill('First name', 'Test'); await fill('Last name', 'Buyer');
await fill('Address', '1 Example Way'); await fill('City', 'Sampletown'); await fill('ZIP Code', '94000');
const pay = p.getByLabel('Test order', { exact: false }); if (await pay.count()) await pay.first().check();
await p.click('button.wc-block-components-checkout-place-order-button, #place_order');
await p.waitForURL(/order-received/, { timeout: 30000 }).catch(() => {});
const ok = /order-received/.test(p.url());
console.log(JSON.stringify({ bundleFee, qty, orderReceived: ok, url: p.url().replace(base, ''), text: ok ? (await p.textContent('main')).replace(/\s+/g, ' ').slice(0, 140) : '', errors }, null, 1));
await p.screenshot({ path: process.env.SHOT || (process.env.TMPDIR || '/tmp') + '/store-checkout.png', fullPage: true }).catch(() => {});
await b.close(); process.exitCode = ok && bundleFee && qty.oneSetOnly ? 0 : 1;
