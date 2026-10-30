# WordPress portfolio demos

Three WordPress demo sites for fictional businesses, each reproducible from a WordPress Playground blueprint and exported to a static copy for free hosting (GitHub Pages project site: `https://williamwkl.github.io/wkl-wp-demos/<demo>/`, not published yet).

| Demo | What it shows | Stack |
|---|---|---|
| **Saltline Kayak Tours** (`demos/saltline`) | Local service business: hero, tours, testimonials, FAQ accordion, booking request form, LocalBusiness + TouristTrip schema | Twenty Twenty-Five child block theme, `theme.json` tokens, Yoast SEO, Contact Form 7 |
| **Harbor & Pine Law** (`demos/harbor-pine`) | Law firm with the intake form as the hero, mobile sticky call bar, documented naive vs optimized performance pass | Hello Elementor child, Elementor (free) containers/widgets, Fluent Forms (free), Yoast SEO |
| **Fernwood Makers** (`demos/makers`) | Searchable directory: `maker` CPT, craft/town taxonomies, listing meta, search + filters + sort + pagination that work on a static host, Google Sheets CSV import | Twenty Twenty-Five child block theme, custom mu-plugin (no fields/filter plugin), Yoast SEO |

Designs: `design/<demo>/DESIGN.md` and `design/home-mockups/` (design specs, implemented as written; deviations listed below).
Every demo also has `HANDOFF-TEMPLATE.md` (sample client deliverable) and `CREDITS.md` (photo/font licenses). Demo 2 has `OPTIMIZATION.md`; demo 3 has `IMPORT.md`.

## Lighthouse (mobile preset, static export served locally with gzip)

Lighthouse 12, default mobile throttling. Full HTML/JSON reports in `reports/`.

| Demo | Page | Perf | A11y | Best Pr. | SEO | LCP | CLS |
|---|---|---|---|---|---|---|---|
| Saltline | Home | 98 | 100 | 100 | 100 | 2.3 s | 0 |
| Saltline | Tours | 99 | 100 | 100 | 100 | 2.1 s | 0 |
| Saltline | About / FAQ / Contact | 99 / 100 / 100 | 100 | 100 | 100 | 1.7 to 1.8 s | 0 to 0.001 |
| Harbor & Pine (optimized) | Home | 98 | 100 | 100 | 100 | 2.3 s | 0 |
| Harbor & Pine (optimized) | 6 inner pages | 99 to 100 | 100 | 100 | 100 | 1.7 to 2.1 s | 0 |
| Harbor & Pine (naive, before) | Home | 87 | 100 | 96 | 100 | 3.8 s | 0.031 |
| Harbor & Pine (naive, before) | Family law (worst) | 81 | 100 | 96 | 100 | 4.8 s | 0.001 |
| Fernwood Makers | Home | 98 | 100 | 100 | 100 | 2.4 s | 0 |
| Fernwood Makers | Directory /makers/ | 97 | 100 | 100 | 100 | 2.5 s | 0 |
| Fernwood Makers | Listing, category, town, for-makers, about | 99 to 100 | 100 | 100 | 100 | 1.4 to 2.1 s | 0 |

Targets (Perf >= 90, A11y/BP/SEO >= 95, LCP <= 2.5 s, CLS <= 0.1) are met on every page of the three shipped builds. The naive Harbor & Pine build intentionally misses them (it is the "before").

Responsive QA (`scripts/check.mjs`): every page at 360, 390, 768, 1024, 1280 and 1440 px: no horizontal scroll, body text >= 16px on phones (14px only for meta/disclaimer text the specs define as small), interactive targets >= 44px (inline text links exempt), images keep their aspect ratio. All pass. Screenshots at 390, 768 and 1440 for every page are in `screens/<demo>/`.

Interaction smoke tests on the static exports: `scripts/smoke-saltline.mjs`, `scripts/smoke-harbor-pine.mjs`, `scripts/smoke-makers.mjs` (menus, Escape to close, demo form validation + success state, sticky bar, directory filters/search/URL sync/pagination/taxonomy lock, bottom sheet, no-JS fallback).

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
```

`export.mjs` crawls the running site, rewrites every internal URL to a relative path (so it works under any sub-folder), keeps canonical/og/JSON-LD absolute to `PUBLIC_BASE`, fetches Elementor's runtime chunks, and writes `sitemap.xml` + `robots.txt`.

Forms on the static copies are demo forms: they validate in the browser and show a visible "Demo form: nothing is sent" note and success state. No third-party form service is used.

## Live WordPress admin links (Playground)

`playground/<demo>.json` are self-contained blueprints (generated by `node scripts/public-blueprints.mjs`) that fetch the theme and mu-plugins from this repo's raw GitHub URLs and log in as admin. Once the repo is public:

`https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/williamwkl/wkl-wp-demos/main/playground/makers.json`

Verified locally by serving the repo as a stand-in for raw GitHub: the makers blueprint booted and rendered all 36 listings. The real raw-GitHub URLs cannot be verified until the repo is published. Playground sites reset on every open (nothing is saved).

## Deviations from the design specs

- Saltline: tours are plain pages, not a `tour` CPT (orchestrator decision 3). The About hero uses a shoreline photo (no free boathouse photo found). Hero photo is 560px wide WebP to keep mobile LCP under 2.5 s; the font preload was dropped for the same reason. Proof icons are inline SVG.
- Harbor & Pine: Fluent Forms instead of WPForms/Elementor Form (orchestrator decision 1); header and footer are child-theme PHP templates (lighter than the Header Footer & Blocks plugin). Icons come from Font Awesome via Elementor's Icon List (rendered as inline SVG in the optimized build) rather than Lucide. "call centre" changed to "call center" (US firm). The FAQ accordions are an HTML widget with `<details>`.
- Fernwood Makers: the sample dataset has 36 makers, so counts are live (36 makers, 9 towns) instead of the mockup's "214". Filtering is client-side over server-rendered cards plus a generated JSON index (orchestrator decision 2); the server also filters from the same query string when JavaScript is off. Six photos are reused across listings per category. "Read the interview" links to the maker page (no interview content in the demo). The search button label is visually hidden on phones but keeps its accessible name.

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
