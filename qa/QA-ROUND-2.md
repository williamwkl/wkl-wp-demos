# QA round 2: re-verification at 183e4b2 (2026-10-30)

Method: the same as round 1. Exports served with gzip at the site root (port 5055) and at `/wkl-wp-demos/<demo>/` (port 5056), with JS on and off, at 6 widths across all 75 pages. I re-ran my own scripts rather than the builder's `linkcheck.mjs` and `check.mjs`.

## Round-1 defects

| ID | Status | Evidence |
|---|---|---|
| D1 og:image relative | FIXED | 40 of 40 og:image tags are absolute `https://williamwkl.github.io/wkl-wp-demos/...` |
| D2 naive stale export | FIXED | The crawl finds 0 broken links. Naive Lighthouse: home Perf 94, BP 100, LCP 2.6 s. Family law: Perf 82, LCP 4.7 s. It is still the "before" build. |
| D3 no-JS fallback | FIXED | With JS off, `?category=ceramics` shows 36 makers with a "36 makers" counter. The filters are hidden and a note says "Search and filters need JavaScript…". Taxonomy pages show 6 and 5 makers. |
| D4 sheet modal | FIXED | `role=dialog`, `aria-modal=true`, background `inert`, body `overflow:hidden`. 45 Tab/Shift+Tab presses: 0 leave the sheet. Escape closes it and returns focus to `.filters-open`. |
| D5 /page/N duplicates | FIXED | 0 `/page/` entries in any sitemap or export (makers sitemap has 60 entries). |
| D6 overflow | FIXED | 0 overflow across 75 pages × 6 widths |
| D7 targets under 44 | FIXED | Strict scan (44×44 including nav, footer and labels): 0 offenders |
| D8 sticky bar focusable | FIXED | When hidden: `inert=true`, `aria-hidden=true`. When visible: `inert=false`. |
| D9 counter / skip links | FIXED | Empty state reads "No makers match these filters (0 of 36)". One skip link per page. |
| D10 no-JS POST | FIXED, with a new defect | Forms now submit with GET to `/demo-form/`. See R1. |
| D11 LCP margin | FIXED | `/makers/` LCP 2102 ms on all 3 runs, Perf 99 |

Lighthouse, mobile: Saltline (5 pages), Harbor & Pine (7 pages) and Makers (11 pages, including /credits/ and /demo-form/) all score Perf ≥99, A11y/BP/SEO 100, LCP ≤2.2 s, CLS ≤0.001.

Other checks:
- **Link crawl:** 310 URLs at the subpath, 0 broken.
- **Copy lint:** clean on all 4 exports, plus my own sweep for em dashes, localhost, and phone numbers or emails that are not 555 or example.com: 0 hits.
- **Makers images:** 39 image stems, all credited (CREDITS.md and image-credits.csv). No image repeats within any page.
- **Blueprints:** valid JSON, every referenced path is tracked.
- **Smoke scripts:** all 3 pass.

## New defects

1. **P2 R1: with JS off, form contents go into the URL.** Repro: JS off, `/harbor-pine/contact/`, click Submit. You land on `demo-form/?…&full_name=&phone=&email=&matter=&details=` together with `_fluentform_4_fluentformnonce` and `_wp_http_referer`. All forms (Saltline, Harbor, Makers) now use `method="get"`, so a visitor's name, phone, email and case details end up in browser history, host logs and the Referer header (checklist 15.1). It is worse on Harbor: the fields use `aria-required` but not `required`, so with JS off an empty form submits too. Fix: keep the form's own action `#` with JS off and show the no-JS note instead, or make `/demo-form/` link-only and strip names from the form fields when JS is off.
2. **P3 R2: utility pages are indexable.** `/demo-form/` (3 demos) and `/credits/` are `index,follow` and listed in the sitemaps. `demo-form` should be `noindex` and dropped from the sitemap.
3. **P3 R3: Makers listing slugs changed.** `/makers/alder-creek-cider/` is now a 404. That's harmless because nothing is published yet, but the round-1 report and any saved links no longer resolve.
4. **P3 R4: pre-checked taxonomy checkbox has no name or value.** On `/craft/ceramics/` it serialises as `=on` and is disabled. Its label keeps full opacity, so it isn't visibly disabled. Adding a town and then "Clear" correctly keeps the locked category.

## Verdict

**NOT GREEN.** One P2 remains: R1, personal data in the URL on the no-JS form path. Everything else is P3.
