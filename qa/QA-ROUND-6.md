# QA round 6: beba1a5 (2026-10-31)

## Checks

| Item | Status | Evidence |
|---|---|---|
| R5-1 domain flip | CLOSED (moot) | No CNAME anywhere in dist/, and no `domainLive` or `publicBase` left in site.config.json or the scripts. Every canonical (111), og:url (110), og:image (41), JSON-LD @id (772), sitemap loc (101) and robots Sitemap line (6) is on williamwkl.github.io. `node scripts/check-base.mjs dist` exits 0 (133 files). `root-files.mjs` runs it before and after writing. |
| check-base fails on planted mismatch | PARTIAL | In a scratch copy it fails (exit 1) on a planted canonical or sitemap `<loc>`. It **passes (exit 0)** on a planted og:image, a JSON-LD @id on a foreign host, a robots.txt Sitemap line, or a dist/CNAME. See P3 #1. |
| /book/ focus box on load | FIXED | At 390 px in Chromium and WebKit, with and without `?tour=`: on load `activeElement` is BODY with no ring. After a step change, focus moves to the step H2 without an outline. In Chromium every Tab stop shows a ring. WebKit's headless Tab did not move focus off BODY (a harness limit), so I could not confirm rings there by keyboard. |
| Lighthouse (mobile) | PASS | Store home, product and bundle: 100/100/100/100. Saltline home: 98. /book/: 99, CLS 0.008. Health-check: 100. Makers /makers/: 99, LCP 2.1 s. Harbor home: 99. A11y/BP/SEO are 100 on all of them. |
| Crawl / widths / copy | PASS | 461 URLs, 0 broken. 111 pages × 6 widths: 0 overflow, 0 targets under 44 px, 0 page errors. Copy lint is clean on all 5 demos. |

## Defects (none P0-P2)

1. **P3:** `check-base.mjs` does not check og:image, robots.txt `Sitemap:`, or CNAME. It also ignores JSON-LD URLs on a different host, so a whole-host swap in JSON-LD passes. That is harmless while github.io is the only base, but the gate is weaker than its header says. Add og:image, robots and CNAME checks, and flag any absolute URL on a non-BASE host.
2. **P3:** my scanner flags the health-check skip link (`a.skip`, off-screen until focused) as not a standard skip link. It works and its target `#summary` exists. Informational only.

## Verdict

**GREEN.** No P0-P2 defects remain.
