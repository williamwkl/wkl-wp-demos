# Harbor & Pine Law: before and after optimization

Same Elementor (free) pages, same copy, same Fluent Forms intake form. Only the performance setup changes.
Both builds come from this folder: `blueprint-naive.json` (sets `hp_mode=naive`) and `blueprint.json` (`hp_mode=optimized`).
Measured with Lighthouse 12, mobile preset (simulated slow 4G, 4x CPU), against the static exports served locally with gzip.
Full reports: `reports/harbor-pine/naive/*.report.html` and `reports/harbor-pine/optimized/*.report.html`.

## Home page

| Metric | Naive build | Optimized build |
|---|---|---|
| Performance | 87 | 98 |
| Accessibility | 100 | 100 |
| Best Practices | 96 | 100 |
| SEO | 100 | 100 |
| Largest Contentful Paint | 3.8 s | 2.3 s |
| First Contentful Paint | 2.2 s | 1.4 s |
| Cumulative Layout Shift | 0.031 | 0 |
| Page weight | 547 KB | 225 KB |
| Requests | 40 | 6 |

All seven pages: naive Performance 81 to 97, optimized 98 to 100 (see README).

## What the naive build does (a typical first pass)

- Elementor loads Newsreader from Google Fonts with every weight and italic (one external CSS request plus font files from a third-party origin).
- Font Awesome icon font CSS (3 stylesheets) for the trust-strip and fact icons.
- Photos uploaded as full-size 1920px JPEGs (240 to 460 KB each), no generated sizes, no lazy loading.
- Every default stylesheet left on: Hello Elementor theme CSS, block library CSS, Elementor icons, Fluent Forms default styles.
- jQuery, Elementor's frontend runtime and widget chunks, Fluent Forms submission JS and the emoji script all load on every page.
- External CSS files for each Elementor widget (render-blocking requests).
- Best Practices loses points for a console 404 (the emoji script is requested from a URL the static export does not contain).

## What the optimized build changes

1. **Fonts**: Elementor Google Fonts switched off (`elementor_google_font = 0`); Newsreader self-hosted as one variable woff2 (400 to 500) plus one italic file, `font-display: swap`, preloaded.
2. **Icons**: Elementor "Inline Font Icons" on, so icons render as inline SVG and the Font Awesome CSS never loads.
3. **Images**: WebP at 1200px with generated responsive sizes (`srcset`), re-encoded at quality 60, lazy loaded below the fold. 457 KB pines photo becomes 82 KB; the desk photo 240 KB becomes 14 KB.
4. **Unused assets removed**: Hello parent CSS, block library CSS, Elementor icon font, Fluent Forms default CSS (the form is styled by the child theme tokens), jQuery, Elementor frontend JS (every widget used here is static), emoji script, oEmbed and REST discovery links.
5. **CSS delivery**: Elementor print method set to internal and all small local stylesheets inlined, so there are no render-blocking CSS requests.
6. **Caching**: the static export is itself the page cache (pre-rendered HTML on a CDN). On a live WordPress host the equivalent is host-level page caching or one free cache plugin, plus long cache headers on `/wp-content/uploads`.

Trade-offs to explain to a client: removing Elementor's frontend JS is only safe because these pages use static widgets (no sliders, tabs, popups, motion effects). Add an interactive widget and that dequeue must be scoped to pages that do not need it. Background lazy-loading (an Elementor experiment) is turned off in both builds because it depends on that JS.
