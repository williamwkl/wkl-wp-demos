# WordPress portfolio demos

Three WordPress demo sites for fictional businesses, each rebuilt from a WordPress Playground blueprint (WordPress 7.1.4 and every plugin/theme pinned to a tested version from versioned wordpress.org zips; the boot still needs network access to download them) and exported to a static copy for free hosting (GitHub Pages project site: `https://williamwkl.github.io/wkl-wp-demos/<demo>/`, not published yet).

| Demo | What it shows | Stack |
|---|---|---|
| **Saltline Kayak Tours** (`demos/saltline`) | Local service business: hero, tours, testimonials, FAQ accordion, booking request form, LocalBusiness + TouristTrip schema | Twenty Twenty-Five child block theme, `theme.json` tokens, Yoast SEO, Contact Form 7 |
| **Harbor & Pine Law** (`demos/harbor-pine`) | Law firm with the intake form as the hero, mobile sticky call bar, documented naive vs optimized performance pass | Hello Elementor child, Elementor (free) containers/widgets, Fluent Forms (free), Yoast SEO |
| **Fernwood Makers** (`demos/makers`) | Searchable directory: `maker` CPT, craft/town taxonomies, listing meta, search + filters + sort + pagination that work on a static host, Google Sheets CSV import | Twenty Twenty-Five child block theme, custom mu-plugin (no fields/filter plugin), Yoast SEO |

Designs: `design/<demo>/DESIGN.md` and `design/home-mockups/` (design specs, implemented as written; deviations listed below).
Every demo also has `HANDOFF-TEMPLATE.md` (sample client deliverable) and `CREDITS.md` (photo/font licenses). Demo 2 has `OPTIMIZATION.md`; demo 3 has `IMPORT.md`.

## Lighthouse (mobile preset, static export served locally with gzip)

Lighthouse 12, default mobile throttling, re-measured after QA round 1. Full HTML/JSON reports in `reports/`.

| Demo | Page | Perf | A11y | Best Pr. | SEO | LCP | CLS |
|---|---|---|---|---|---|---|---|
| Saltline | Home | 98 | 100 | 100 | 100 | 2.3 s | 0 |
| Saltline | Tours / About / FAQ / Contact | 99 / 99 / 100 / 99 | 100 | 100 | 100 | 1.7 to 2.0 s | 0 to 0.001 |
| Harbor & Pine (optimized) | Home | 99 | 100 | 100 | 100 | 2.1 s | 0 |
| Harbor & Pine (optimized) | 6 inner pages | 99 to 100 | 100 | 100 | 100 | 1.7 to 2.1 s | 0 |
| Harbor & Pine (naive, before) | Home / Family law (worst) | 96 / 81 | 100 | 100 | 100 | 2.3 / 4.8 s | 0.031 / 0.001 |
| Fernwood Makers | Home | 100 | 100 | 100 | 100 | 1.6 s | 0 |
| Fernwood Makers | Directory /makers/ | 99 | 100 | 100 | 100 | 2.1 s | 0 |
| Fernwood Makers | Listing, category, town, categories, towns, for-makers, about | 99 to 100 | 100 | 100 | 100 | 1.2 to 2.1 s | 0 |

Utility pages `/demo-form/` and `/credits/` are deliberately `noindex, follow` and left out of `sitemap.xml`, so Lighthouse SEO reports 60 on them by design.

Targets (Perf >= 90, A11y/BP/SEO >= 95, LCP <= 2.5 s, CLS <= 0.1) are met on every page of the three shipped builds. The naive Harbor & Pine build is the "before" and misses Performance/LCP on its photo pages.

Responsive QA (`scripts/check.mjs`, tightened after QA round 1): every page at 360, 390, 768, 1024, 1280 and 1440 px. It fails on any horizontal scroll; body, footer and form text under 16px on phones (only meta text the specs define as small is exempt: eyebrows, card meta, disclaimers); any control smaller than 44 x 44 px, including nav and footer links, filter labels and checkbox labels (only inline links inside running prose are exempt, per WCAG 2.5.8); and images rendered at a distorted aspect ratio. All shipped pages pass. Screenshots at 390, 768 and 1440 for every page are in `screens/<demo>/`.

Form fields: `scripts/field-audit.mjs <origin> <demo>` checks every input, select, textarea and checkbox at 360, 390 and 1440 in WebKit (iPhone emulation) and Chromium, resting and focused: 16px+ text, 48px fields, native iOS chrome removed (custom select arrow), one visible focus ring per demo (not inside wrappers, not clipped), no truncated placeholders or select text, 44px checkbox hit areas. 0 issues on all three demos; before/after sheets in `screens/<demo>/fields-focus-390.png` (built with `scripts/field-sheet.mjs`).

Other checks: `scripts/linkcheck.mjs dist/<demo>` (0 broken internal references in all four exports), `scripts/copy-lint.mjs` (no em dashes, no "AI", no localhost URLs), and smoke tests on the static exports: `scripts/smoke-saltline.mjs`, `scripts/smoke-harbor-pine.mjs`, `scripts/smoke-makers.mjs` (menus, Escape to close, demo form validation + success state, sticky bar made inert while hidden, directory search/filters/URL sync/pagination/taxonomy pre-check, the filter sheet as a modal dialog with focus trap and focus return, and the no-JS behaviour below).

### Without JavaScript

- Forms: required fields use native browser validation. On the static copies the exporter strips every field `name` and hidden input (nonce, referer) from demo forms, so a submit only opens the `/demo-form/` notice page with no query string: nothing a visitor types reaches the URL, history or server logs (`scripts/smoke-nojs-forms.mjs` checks all 6 forms). A static host answers POST with 405, which is why the forms use GET.
- Makers directory: every listing is shown and the counter states the true number; the search box and filter rail are hidden, and a note links to the real static category and town pages (which are pre-filtered server-side). Query-string filters such as `?category=ceramics` only work with JavaScript on the static copy; on live WordPress the same query string is also applied server-side.

## Run locally

Requirements: Node 20+ (tested on 24), internet access on first boot (Playground downloads WordPress and plugins). No Docker, no PHP install needed.

```bash
npm install                      # playwright, lighthouse (dev tools only)
scripts/serve.sh saltline 9401   # http://127.0.0.1:9401  (wp-admin: admin / password)
scripts/serve.sh harbor-pine 9402
scripts/serve.sh harbor-pine 9402 demos/harbor-pine/blueprint-naive.json   # the "before" build
scripts/serve.sh makers 9403
```

`serve.sh` boots WordPress Playground CLI with the demo's `blueprint.json` and mounts `theme/`, `mu-plugins/` and `data/` from the repo, so edits to those files show on reload. Each boot is a fresh site (the blueprint seeds it in about 90 seconds).

## Rebuild a static export

```bash
PUBLIC_BASE=https://williamwkl.github.io/wkl-wp-demos/saltline node scripts/export.mjs http://127.0.0.1:9401 dist/saltline
node scripts/static-server.mjs dist 5055            # GitHub Pages-like server with gzip
node scripts/lh.mjs http://127.0.0.1:5055/saltline reports/saltline / /tours/
node scripts/check.mjs http://127.0.0.1:5055/saltline saltline / /tours/
node scripts/copy-lint.mjs dist/saltline            # no em dashes, no "AI", no localhost leaks
node scripts/linkcheck.mjs dist/saltline            # every internal href/src/url() exists
node scripts/redirect-stubs.mjs dist/makers demos/makers/data/redirects.csv https://williamwkl.github.io/wkl-wp-demos/makers   # makers only: stubs for renamed listing slugs
```

`export.mjs` crawls the running site, rewrites every internal URL to a relative path (so it works under any sub-folder), keeps canonical, og:url, og:image and JSON-LD absolute to `PUBLIC_BASE`, fetches Elementor's runtime chunks and assets referenced from inline JSON, drops server-only head links (feeds, REST, oEmbed), turns POST forms into GET forms aimed at `/demo-form/`, skips paginated `/page/N/` duplicates, and writes `sitemap.xml` + `robots.txt` (robots.txt only takes effect at a domain root, not in a project sub-folder).

Forms on the static copies are demo forms: with JavaScript they validate in the browser and show a success state; without it they land on a `/demo-form/` notice page. No third-party form service is used.

## Live WordPress admin links (Playground)

`playground/<demo>.json` are self-contained blueprints (generated by `node scripts/public-blueprints.mjs [rawBase]`) that fetch the theme and mu-plugins from this repo's raw GitHub URLs and log in as admin. They currently point at the placeholder tag `v1.0.0`: at publish time, create the release tag (or use the commit SHA), re-run the script with that ref, and commit, so a public link always loads exactly the reviewed files. Link format:

`https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/williamwkl/wkl-wp-demos/v1.0.1/playground/makers.json`

Verified locally by serving the repo as a stand-in for raw GitHub: the makers blueprint booted and rendered all 36 listings. The real raw-GitHub URLs cannot be verified until the repo is published. Playground sites reset on every open (nothing is saved).

## Deviations from the design specs

- Saltline: tours are plain pages, not a `tour` CPT (orchestrator decision 3). The About hero uses a shoreline photo (no free boathouse photo found). Hero photo is 560px wide WebP to keep mobile LCP under 2.5 s; the font preload was dropped for the same reason. The contact map placeholder was removed (address text kept). Photo credits live on a /credits/ page linked from the footer.
- Harbor & Pine: Fluent Forms instead of WPForms/Elementor Form (orchestrator decision 1); header and footer are child-theme PHP templates (lighter than the Header Footer & Blocks plugin). The stock desk photo and the contact map placeholder were removed after design review. Icons come from Font Awesome via Elementor's Icon List (rendered as inline SVG in the optimized build) rather than Lucide. "call centre" changed to "call center" (US firm). The FAQ accordions are an HTML widget with `<details>`.
- Fernwood Makers: the sample dataset has 36 makers, so counts are live (36 makers, 9 towns, town counts 2 to 7) instead of the mockup's "214". Every listing has its own photo; listing galleries show three different photos and never repeat one shown in the "More in" row. Filtering is client-side over server-rendered cards plus a generated JSON index (orchestrator decision 2). The listing map tile was removed rather than faked. "Read the interview" links to the maker page (no interview content in the demo). The display font is subset to Latin at a fixed optical size (32 KB) to keep directory LCP under 2.2 s.

## Repo map

```
demos/<demo>/blueprint.json    Playground blueprint (plugins, theme, settings, seed)
demos/<demo>/theme/            child theme (theme.json or style tokens, templates, assets)
demos/<demo>/mu-plugins/       seed content, structured data, head cleanup, directory plugin (makers)
demos/makers/data/             sample CSV for the Google Sheets import path
dist/<demo>/                   static exports (dist/index.html is a landing page)
reports/<demo>/                Lighthouse HTML + JSON
screens/<demo>/                screenshots (390 / 768 / 1440 per page)
playground/                    public Playground blueprints
scripts/                       serve, export, image pipeline, QA, Lighthouse
```
