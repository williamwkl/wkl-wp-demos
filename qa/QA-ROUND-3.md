# QA round 3: re-verification at e93c2d6 (2026-10-30)

Method: the same as rounds 1 and 2. Exports served with gzip at the site root and at `/wkl-wp-demos/<demo>/`. I used my own scripts; the builder's scripts were re-run only as a cross-check.

## Round-2 defects

| ID | Status | Evidence |
|---|---|---|
| R1 typed data in the URL | FIXED | I filled every field with a marker string ("zqx…") and submitted all 6 forms: saltline/contact, harbor-pine home, contact and family-law, makers for-makers and a listing contact form. I did this with JS on and with JS off. The marker appeared 0 times in the final URL, any request URL, any Referer or any POST body. With JS off, the form lands on `demo-form/?` with an empty query. With JS on, the form stays on the page and shows the success state (the smoke scripts show the real success copy). Empty submit is blocked on all 6 forms in both modes. `scripts/smoke-nojs-forms.mjs` also passes. |
| R2 utility pages indexable | FIXED | `demo-form` and `credits` pages carry `noindex, follow` and are not in the sitemaps of the 3 shipped demos. |
| R3 renamed slugs | FIXED | All 10 entries in `redirects.csv` are noindex stubs with a canonical and a meta refresh, and every target returns 200 at the subpath. The old slugs are not in the sitemap. |
| R4 locked filter | FIXED per the builder's claim | The strict scan, the smoke tests and the sheet dialog checks pass. I did not re-measure the label styling. |

## Sweep

- **Lighthouse (mobile):**
  - Saltline `/`, `/tours/`, `/contact/`: Perf 99-100, LCP ≤2.2 s.
  - Harbor & Pine `/` and `/family-law/`: Perf 99, LCP 2.1 s.
  - Makers `/`, `/towns/`, `/makers/coldwater-clay/`: Perf 99-100.
  - Makers `/makers/` ×3: Perf 99, LCP 2.1 s every run.
  - A11y, BP and SEO are 100 on every page and CLS is 0.
- **Widths and targets:** 85 pages × 6 widths. 0 overflow, 0 targets under 44×44, one h1 per page, no skipped heading levels, 0 page errors or 4xx responses.
- **Links:** 316 URLs crawled at the subpath, 0 broken.
- **Copy:** lint is clean on all 4 exports.
- **Credits:** all image stems are credited (Saltline 7, Harbor 2, Makers 40). No image repeats within any Makers page.

## Defects

1. **P3:** `harbor-pine-naive/demo-form/` is still `index, follow` and listed in its sitemap. This is the "before" build, which is not one of the shipped demos.

Not exercised: contrast, beyond Lighthouse's audit.

## Verdict

**GREEN.** No P0-P2 defects remain.
