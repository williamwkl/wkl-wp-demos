// Live WooCommerce flow in the Playground site: add to cart, guest checkout with the offline demo payment, order received.
// usage: node scripts/smoke-store-live.mjs http://127.0.0.1:9404 [product-path]
import { chromium } from 'playwright';
const [base, product = '/product/test-product/'] = process.argv.slice(2);
const b = await chromium.launch(); const p = await b.newPage({ viewport: { width: 1280, height: 900 } });
const errors = []; p.on('pageerror', e => errors.push(e.message));
await p.goto(base + product, { waitUntil: 'networkidle' });
await p.click('button[name="add-to-cart"], .single_add_to_cart_button');
await p.waitForLoadState('networkidle');
await p.goto(base + '/checkout/', { waitUntil: 'networkidle' });
const fill = async (label, v) => { const f = p.getByLabel(label, { exact: true }).first(); if (await f.count()) await f.fill(v); };
await fill('Email address', 'buyer@example.com');
await fill('First name', 'Test'); await fill('Last name', 'Buyer');
await fill('Address', '1 Example Way'); await fill('City', 'Sampletown'); await fill('ZIP Code', '94000');
const pay = p.getByLabel('Demo payment', { exact: false }); if (await pay.count()) await pay.first().check();
await p.click('button.wc-block-components-checkout-place-order-button, #place_order');
await p.waitForURL(/order-received/, { timeout: 30000 }).catch(() => {});
const ok = /order-received/.test(p.url());
console.log(JSON.stringify({ orderReceived: ok, url: p.url().replace(base, ''), text: ok ? (await p.textContent('main')).replace(/\s+/g, ' ').slice(0, 140) : '', errors }, null, 1));
await p.screenshot({ path: process.env.SHOT || 'store-checkout.png', fullPage: true }).catch(() => {});
await b.close(); process.exitCode = ok ? 0 : 1;
