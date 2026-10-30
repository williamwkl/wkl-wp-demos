# QA round 4: HEAD 8455126 (2026-10-30)

Method: dist/ served with gzip at the site root and at the `/wkl-wp-demos/` subpath. Pages tested with JS on and off, in Chromium on 111 pages × 6 widths and in WebKit on 29 pages × 4 widths. Lighthouse (mobile) on 16 pages. Link crawl of 425 URLs.

## Claims

| Claim | Verdict | Evidence |
|---|---|---|
| (1) Health Check PDF traceable, no contact info | PASS, 1 nit | I checked 12 figures against `reports/harbor-pine/{naive,optimized}/*.report.json` and all match. Naive table per page (Perf / LCP / FCP / CLS / requests / KB): Home 96 / 2.3 / 2.3 / 0.031 / 38 / 325. Personal injury 88 / 3.6 / 479 KB. Family law 81 / 4.8 / 700 KB. Contact CLS 0.037, 33 requests. Render-blocking 16 to 28. pines.jpg 458 KB, with responsive-images savings 405 KB and modern-formats savings 188 KB. Personal injury savings 212 / 121 KB. Optimized: 4 to 6 requests, Perf 99-100, family-law LCP 2.1 s. pines-1200.webp is 82 KB. The PDF contains no email, phone or URL; it is signed only "William L., WKL Labs". Nit: "FCP 2.0 s to under 1 s" is not true for optimized Home (FCP 1.4 s). |
| (2) Store: no request leaves the host, no Stripe keys | PARTIAL FAIL | Typed checkout values (marker string) appear 0 times in any URL, Referer or request, in both Chromium and WebKit. No POSTs and no off-host requests. `git grep` for sk_/pk_/rk_/whsec_/stripe secret: 0 hits. Typing in the store is safe; the store fails only on the broken bundle images (D1 below). |
| (3) Saltline /book/ | PASS | Capacity is capped (8 of 8; on a departure with 1 seat left, only 1 adult is possible). Kids can never exceed adults: lowering adults from 2 to 1 drops kids from 2 to 1. With JS off there are 2 departure tables (24 rows) and no form. The booking marker never reaches a URL, Referer or request. Smoke test passes. |
| (4) Custom-domain prep | FAIL (P2, see D2) | `dist/CNAME`, a root robots.txt and a sitemap index are present. |
| Radio `name`s | PASS | Only radio, checkbox and `sort` keep a `name`. Every text, email and tel field and every textarea has its name stripped. With JS off, the product form sends `?lh_size=17&lh_grind=Whole+bean`, which holds fixed values only. |
| Store a11y | PASS with notes | Variation radios sit in fieldsets with legends. Bundle tiles are native checkboxes inside labels, not aria-pressed buttons; that is fine and Space toggles them. Cart Remove buttons have accessible names and are 59×44. There is no quantity control at all, only Remove. A polite live region is present. |
| Harbor + Makers unchanged | Changed but still pass | 75 files differ: new form-field CSS and timestamps. All checks still pass. |

Sweep:
- **Lighthouse (mobile):** store pages (home, shop, product, bundle), Saltline home and /book/, health-check, Harbor home and family law, Makers `/makers/` ×3: Perf 98-100, A11y/BP/SEO 100, LCP ≤2.4 s, CLS ≤0.002.
- **Store utility pages:** demo-cart and demo-checkout score SEO 63 because they are intentionally noindex.
- **Widths and targets:** 0 overflow and 0 targets under 44 px, in both engines.
- **Links and copy:** the crawl finds 0 broken links. Copy lint is clean on all exports.
- **Contact details:** no phone or email in the repo beyond 555 numbers and example.com addresses.

## Defects

1. **P2 D1: bundle thumbnails 404 on every deployment.** The bundle `data-img` attributes are root-absolute, e.g. `/wp-content/uploads/2026/10/house-blend-800-150x150.webp`. The source is `demos/store/mu-plugins/lantern-store.php:267`, and the exporter does not rewrite `data-*` attributes. Repro: open `/store/bundle/` and pick 3 items, then open the cart. You get 404s for house-blend, dripper and camp-mug `-150x150.webp` in Chromium and WebKit. The path resolves to the host root, so the images break at github.io/wkl-wp-demos/store/, at demos.wkllabs.com/store/ and locally.
2. **P2 D2: the domain setup contradicts itself.** `dist/CNAME` = demos.wkllabs.com, but Saltline, Harbor, Makers and the store keep github.io canonicals, while the root index and health-check use demos.wkllabs.com.
   - Once Pages serves the CNAME, github.io URLs 301 to the custom domain, so every demo's canonical points at a redirect.
   - The root `sitemap.xml` at demos.wkllabs.com lists demo sitemaps whose `<loc>`s are on github.io. Search engines drop cross-host sitemap entries.
   - If dist/ is published before DNS resolves, github.io redirects to a domain that doesn't resolve, and the whole site goes down.
   - Fix: hold CNAME out of dist/ until DNS is confirmed, or switch every canonical to demos.wkllabs.com at the same time.
3. **P3:** checkout PII (name and address) is stored in sessionStorage `lh-last-order` after the demo order. It lasts only for the tab, but it is unnecessary.
4. **P3:** with JS off, the bundle form accepts more than 3 picks and submits them all to demo-form.
5. **P3:** health-check page has no skip link. The root `/favicon.ico` returns 404, which drops Best Practices to 96 on the root index and becomes real on the custom domain.
6. **P3:** the cart has no quantity control. Quantity can only change by re-adding or removing.
7. **P3:** PDF FCP claim "under 1 s" contradicts optimized Home at 1.4 s.

## Verdict

**NOT GREEN.** Two P2 defects remain: D1 (broken bundle images) and D2 (domain, canonical and sitemap mismatch, plus the deploy hazard).
