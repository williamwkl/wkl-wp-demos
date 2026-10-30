# QA round 5: 9cfc4c1 (2026-10-30)

## Round-4 items

| Item | Status | Evidence |
|---|---|---|
| D1 bundle/cart thumbnails | FIXED | I ran the flow empty cart → bundle (3 picks) → product → cart → checkout → confirmation in Chromium and WebKit, at the site root and at `/wkl-wp-demos/store/`. 0 broken images, 0 responses ≥400, 0 off-host requests. No `data-*="/wp-content` left in dist. |
| D2 domain consistency (current build) | FIXED | No `dist/CNAME`. All 109 canonicals, 108 og:url, 52 og:image, 759 JSON-LD @id, 101 sitemap locs and 6 robots `Sitemap:` lines are on williamwkl.github.io. |
| domainLive=true path | **FAIL (P2, R5-1)** | See below. |
| No PII in storage | FIXED | After the order, sessionStorage and localStorage are empty (no keys); the marker string appears nowhere in either. The confirmation says "Thanks. Your coffee roasts Thursday." with no name. The cart badge goes to 0, and the cart is empty afterwards. |
| Qty stepper / empty cart | PASS | The empty cart renders an explanation. Each cart line has Decrease and Increase quantity buttons plus a Remove button, all labelled and 44 px tall. Increase stops at quantity 9 and the total updates. |
| Health-check rings + FCP | PASS | Ring dasharrays over a 169.6 circumference give 81, 5/7, 1/8, 100, 100 and 2/6, matching the data. The PDF and the page now say "FCP 2.0 to 2.3 s down to 0.8 to 1.4". That matches the optimized reports (0.8 to 1.4 s). |
| Favicon, skip link | FIXED | Root Best Practices is 100. The health-check skip link targets `#summary`, which exists. |

Sweep:
- **Link crawl:** 461 URLs at the subpath, 0 broken.
- **Copy lint:** clean on all 5 demos.
- **Stripe keys:** 0 hits for sk_/pk_ keys.
- **Widths:** 111 pages × 6 widths in Chromium and the store/health/book pages in WebKit: 0 overflow.
- **Lighthouse (mobile):**
  - Store home, bundle and product: 100 across the board.
  - Health-check: 100.
  - Saltline home: 98. /book/: 99, CLS 0.008.
  - Makers /makers/: 99.
  - Root: 100.
  - A11y/BP/SEO are 100 on every page.

## Defects

1. **P2 R5-1: setting `domainLive: true` does not switch everything together.** `scripts/root-files.mjs` writes the CNAME, the root sitemap index and robots.txt on the custom domain whenever the flag is true. Every demo's canonical, og:url, JSON-LD and sitemap are only switched when that demo is re-exported from a live Playground site. Nothing checks that the two agree, and `build.sh` runs root-files after every single-demo export.
   - Repro (scratch copy): set `domainLive: true` and run `node scripts/root-files.mjs dist`.
   - Result: `CNAME demos.wkllabs.com`, root sitemap on demos.wkllabs.com, root index canonical on demos.wkllabs.com.
   - But `dist/saltline/index.html` keeps canonical `https://williamwkl.github.io/wkl-wp-demos/saltline/`.
   - That reproduces the round-4 D2 mixed-host state as soon as the flag is flipped and anything less than every demo, plus health-check, is rebuilt.
   - Fix: root-files should refuse to write CNAME unless every `dist/*/index.html` canonical and sitemap `<loc>` uses BASE. Alternatively, make flipping the flag a single all-demos rebuild that ends with that check.
2. **P3:** the health-check skip link is 41 px tall when focused.
3. **P3 (carried over):** a robots.txt inside a demo subfolder is ignored by crawlers until the custom domain is live.

## Verdict

**NOT GREEN.** One P2 remains: R5-1, the domain flip is not enforced as all-or-nothing. The shipped dist/ itself is consistent.
