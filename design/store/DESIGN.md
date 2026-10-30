# Lantern Hill Roasters: WooCommerce demo store design spec

Mockup: `design/home-mockups/store.html` (seven screens in one file, switched by URL hash: `#home` default, `#shop`, `#product`, `#bundle`, `#cart`, `#checkout`, `#confirm`; the bundle builder, category filter and size pricing work in the mockup). Renders: `store-{mobile,tablet,desktop}.png` (home at 390 / 768 / 1440) and `store-bundle-desktop.png`.

## 1. Brand concept

A two-person small-batch coffee roaster in a former bakery. The fourth brand must sit apart from the other three: Saltline is cool coastal editorial serif, Harbor & Pine is quiet legal letterhead, Fernwood is a chunky civic catalogue. Lantern Hill is **warm, paper-and-ink, with a little print-shop wit**: a high-contrast condensed serif for headlines (italics used for the one word that matters), a cream page the colour of a paper bag, and a coffee-cherry red that is used only where money changes hands (buy, add, bundle discount). The depth technique is the distinctive move: **hard offset shadows** (a solid 3 to 5 px block in ink, no blur) on the primary button, hovered product cards and the order summary, like a rubber stamp or a letterpress plate. Nothing else in the portfolio uses it.

Name: **Lantern Hill Roasters**. Tagline: **Coffee roasted this week, not this quarter.** Fictional town **Ashgrove**, 14 Mill Street. Owners **Mara and Tomas** (first names only). `hello@lanternhillroasters.example.com`, `(555) 010-0122`. Every review is "(sample review)"; the footer and the checkout both say the store is a demo in test mode. No em dashes, never the word "AI".

## 2. Design tokens

### Colour

| Token | Hex | Role |
|---|---|---|
| `--c-ink` | `#1F1813` | text, dark bands, offset shadows, active chips |
| `--c-roast` | `#3A2A20` | tag text on cream-2, placeholder photo base |
| `--c-cherry` | `#8E2F3F` | THE accent: primary CTA fill, cart badge, selected bundle card edge, links |
| `--c-cherry-deep` | `#75242F` | primary hover |
| `--c-leaf` | `#3B5A3E` | eyebrows, savings text, confirmation stamp, "Bestseller" tag |
| `--c-cream` | `#F7F1E6` | page background |
| `--c-cream-2` | `#EDE4D4` | roast-notes strip, tags, progress track |
| `--c-white` | `#FFFCF7` | product cards, summaries, inputs |
| `--c-mute` | `#6A5F55` | secondary text |
| `--c-line` | `rgba(31,24,19,.14)` | hairlines on cards (decorative) |
| peach `#F2C9A0` | | eyebrow and highlight on dark bands only |

WCAG (computed, sRGB): ink on cream 15.59 · ink on cream-2 13.89 · ink on white 17.12 · mute on cream 5.53 (AA) · mute on cream-2 4.92 (AA) · mute on white 6.07 · cherry on cream 7.11 (links) · white on cherry 7.81 (button label) · white on cherry-deep 10.01 · leaf on cream 6.87 · leaf on `#E1EADF` 6.27 (Bestseller tag) · roast on cream-2 10.87 (tags) · cream on ink 15.59 · `#CFC4B4` on ink 10.19 (footer secondary) · peach on ink 11.38 · demo notice `#5A3E00` on `#FFF2CF` 8.88. Never ink on cherry (2.19). Control edges use ink at 45 percent (3.4:1 on cream, above the 3:1 non-text minimum).

### Type

- Display: **Instrument Serif** (regular + italic only, two woff2 files of about 30 KB each, self-hosted, `font-display: swap`). Fallback `"Instrument Serif", "Iowan Old Style", Georgia, serif`. Headlines at weight 400, -0.01em tracking, line-height 1.0 to 1.05. Italic is allowed once per headline for emphasis ("not this quarter") and for pull quotes; never for body.
- Body and UI: system sans stack (no file).
- Scale: display `clamp(2.75rem, 1.4rem + 5.4vw, 5rem)` (44 to 80 px), H2 `clamp(2rem, 1.3rem + 2.4vw, 2.875rem)`, H3 22 to 26, card title 20, lead 18 to 20, body 16 to 17, small 14, eyebrow 13 uppercase +0.1em in leaf. Prices use `font-variant-numeric: tabular-nums`; the big price on the product page and totals are Instrument Serif at 24 px.

### Spacing, radius, depth

- Scale `8 12 16 24 32 48 64 96`. Sections `clamp(56px, 7vw, 88px)`. Gutter 20 / 48. Content max 1200 px.
- Radius: `--r-sm 4px` (inputs, tags), `--r-md 10px` (cards, buttons), `--r-lg 18px` (hero photo), pill 999 px (chips, size and grind options).
- Depth: **hard offset shadow**. Primary button `3px 3px 0 ink` (on dark bands the offset is cream), pressed state collapses to `1px 1px 0` with a 2 px translate. Product card hover `4px 4px 0 ink` with a 1 px lift. Bundle summary and cart summary `5px 5px 0 ink` with a 1.5 px ink border (the "receipt"). Selected bundle item `3px 3px 0 cherry`. No blurred shadows anywhere.

### Buttons, chips, fields, focus

- Primary: cherry fill, white label, 600, 48 px min height, radius 10. Secondary: 1.5 px ink border, ink label. "Add" on cards: 44 px outline button that fills ink on hover.
- Chips (category filter, size, grind): pill, 1.5 px ink border, 44 px tall; active = ink fill, cream label. Implemented as `button[aria-pressed]` (filters) and visually-hidden radios (options), so keyboard and screen readers get native semantics.
- Inputs 48 px, 16 px text, custom select arrow (inline SVG data URI), native iOS chrome removed.
- Focus ring `3px solid cherry, offset 3px`; cream on dark bands. Reduced motion removes all transitions.

## 3. Screens

### Home (`#home`, rendered)
1. Announcement bar (ink): "Free shipping on orders over $40. Roasted Mondays and Thursdays, shipped the same day."
2. Header: lantern mark + wordmark, nav Shop / Build a bundle / Subscriptions / Our roastery, search icon, cart icon with count badge, hamburger under 1024. Sticky, blurred cream.
3. Hero 7/12 + 5/12: eyebrow, H1 with the italic turn, lead, primary "Shop coffee", secondary "Build a 3-bag bundle", three check facts (roast date, free shipping, 4.9 from 212 reviews (sample)). Photo 4:5 radius 18.
4. Roast-notes strip (cream-2, hairline top and bottom): Last roast day / coffees in rotation / batch size / roast days. 2 x 2 on phones, 4 across from 768.
5. This week's coffees: 4 product cards (2 / 3 / 4 columns), tag, title link, tasting notes, price per 12 oz, Add.
6. Bundle band (ink): "Pick any three, save 12 percent." + primary CTA; three stacked placeholder products on the right.
7. Our roastery split (photo 4:3 left 5/12): story paragraph + three facts with leaf check icons.
8. Two sample reviews as cherry-ruled blockquotes in Instrument Serif.
9. Footer (ink): brand + address, Shop / Company / Help columns, legal line with the demo disclaimers.

### Shop (`#shop`)
H1 + sort select; category chips All (12) / Coffee (6) / Brewing gear (3) / Mugs and merch (2) / Subscriptions (1); live count with `aria-live`; 12 cards. Chips filter client-side in the static copy (same JS pattern as the Makers directory); on live WordPress the chips link to the real category archives so the filter also works without JS.

### Product (`#product`, Morning Fog)
Breadcrumb; gallery (1:1 main + 4 thumbs) 7/12; right column: eyebrow, H1, origin line, tasting-note tags with the roast date, description, price that updates with the size radio (12 oz $19 / 2 lb $52 / 5 lb $120), grind radio (whole bean, filter, espresso, French press), quantity stepper (44 px buttons) + "Add to cart", shipping line, three `<details>` accordions (brewing notes, where it comes from, shipping and returns). "Goes well with" row of two cards.

### Build your own bundle (`#bundle`, rendered at 1440 with two items picked)
Rules: exactly 3 items from 10 eligible products (bags, gear, mugs), any mix, 12 percent off the sum when 3 are picked. Left 7/12: filter chips + a grid of pressable item tiles (`button[aria-pressed]`, cherry check badge when selected, remaining tiles disabled at 3). Right 5/12 sticky receipt card: "n of 3 picked" (`aria-live`), progress bar, three slots that fill with the picks (each with a 44 px remove), Items / Bundle discount / Bundle price, and a CTA that stays `aria-disabled` and reads "Pick 3 items to continue" until the third pick, then "Add bundle to cart, $xx.xx". Mobile: tiles 2 across, receipt below the grid (not sticky).

### Cart (`#cart`)
Line items (72 px thumbnail, title, variant, price, Remove), including a bundle line that lists its three contents and shows "Bundle discount applied". Receipt-style summary with subtotal, free shipping, estimated tax, total, coupon field, "Check out", and a lock line stating the demo runs in test mode.

### Checkout (`#checkout`)
Steps Cart / Checkout / Confirmation. A yellow demo notice first: "Demo checkout, test mode. Nothing is charged and no order is placed. Use the test card 4242 4242 4242 4242, any future date, any CVC." Then Contact (email), Shipping address (two-column names, street, city + ZIP, shipping method select with local pickup), Payment as a Stripe-style card box (brand row, number, expiry, CVC) with the lock line explaining it is the Stripe test-mode element in the live demo. CTA "Place test order, $81.94". Order summary on the right (sticky at 1024+).

### Confirmation (`#confirm`)
Centered: leaf stamp with a check, eyebrow "Test order placed", H1 "Thanks, Dana. Your coffee roasts Thursday.", order number LH-10427, "(demo: no email is actually sent)", receipt with ship-to and "Test card ending 4242", two buttons.

## 4. WooCommerce mapping (Twenty Twenty-Five child, block theme)

| Screen / section | Blocks and settings |
|---|---|
| Header | Template part `header`: Group (flex) > Site Logo (inline SVG) + Site Title, Navigation, `woocommerce/customer-account` (search can be core Search in a modal), `woocommerce/mini-cart` (badge count comes from it) |
| Announcement bar | Group (ink background) > Paragraph, inside the header template part |
| Home hero | Columns 7/12 + 5/12 (Paragraph eyebrow, Heading, Paragraph lead, Buttons, List) + Image |
| Roast-notes strip | Group (cream-2, full width) > Columns x4; static text in the demo |
| Coffees grid | `woocommerce/product-collection` (Featured collection, 4 items, columns 2/3/4) with inner Product Image, Product Title, Product Price, Add to Cart Button; tags come from product tags shown via Product Badge or a Paragraph bound to meta |
| Bundle band | Cover (ink) > Heading, Paragraph, Buttons; three Images |
| Roastery split | Media & Text + List |
| Reviews | Columns x2 > Quote |
| Footer | Template part `footer`: Columns 2fr 1fr 1fr 1fr |
| Shop | Template `archive-product`: `woocommerce/product-filters` with the Attribute / Taxonomy filter rendered as chips, `woocommerce/catalog-sorting`, `woocommerce/product-results-count`, Product Collection (12 per page) |
| Product | Template `single-product`: `woocommerce/product-image-gallery`, Product Title, Product Price, Product Short Description, `woocommerce/add-to-cart-form` (variable product: size as a variation, grind as a free attribute), Product Details (tabs) replaced by three Details blocks, `woocommerce/related-products` (2) |
| Bundle builder | A custom block `lanternhill/bundle-builder` in the child theme's mu-plugin: server-renders the 10 eligible products from a product tag `bundle`, ships one 4 KB script for the picking and pricing, and on live WordPress adds the three products to the cart with a `bundle` cart-item meta and applies a 12 percent cart discount via `woocommerce_cart_calculate_fees` (negative fee). No bundle plugin needed |
| Cart | Template `page-cart`: `woocommerce/cart` block (Filled Cart, Cart Items, Cart Totals, coupon form) |
| Checkout | Template `page-checkout`: `woocommerce/checkout` block (Contact, Shipping address, Shipping options with Local Pickup, Payment) with WooPayments or Stripe in **test mode**, and a Paragraph notice above it |
| Confirmation | Template `order-confirmation`: `woocommerce/order-confirmation-*` blocks (status, summary, totals, shipping address) |

Plugins: WooCommerce (free) only. Payment: the Stripe gateway plugin in test mode with test keys, or WooCommerce's built-in "Cash on delivery" renamed "Test order" if no keys are set in Playground. Subscriptions are a simple product named "Roaster's choice, monthly" with a note (the real subscription extension is paid; say so in the demo copy, do not fake recurring billing).

### theme.json snippet

```json
{
  "version": 3,
  "settings": {
    "color": { "defaultPalette": false, "palette": [
      { "slug": "ink", "name": "Ink", "color": "#1F1813" }, { "slug": "roast", "name": "Roast", "color": "#3A2A20" },
      { "slug": "cherry", "name": "Cherry", "color": "#8E2F3F" }, { "slug": "cherry-deep", "name": "Cherry deep", "color": "#75242F" },
      { "slug": "leaf", "name": "Leaf", "color": "#3B5A3E" }, { "slug": "cream", "name": "Cream", "color": "#F7F1E6" },
      { "slug": "cream-2", "name": "Cream 2", "color": "#EDE4D4" }, { "slug": "white", "name": "Warm white", "color": "#FFFCF7" },
      { "slug": "mute", "name": "Mute", "color": "#6A5F55" } ] },
    "typography": { "fluid": true, "fontFamilies": [
      { "slug": "display", "name": "Instrument Serif", "fontFamily": "\"Instrument Serif\", \"Iowan Old Style\", Georgia, serif", "fontFace": [
        { "fontFamily": "Instrument Serif", "fontWeight": "400", "fontStyle": "normal", "fontDisplay": "swap", "src": [ "file:./assets/fonts/instrument-serif.woff2" ] },
        { "fontFamily": "Instrument Serif", "fontWeight": "400", "fontStyle": "italic", "fontDisplay": "swap", "src": [ "file:./assets/fonts/instrument-serif-italic.woff2" ] } ] },
      { "slug": "body", "name": "System sans", "fontFamily": "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif" } ],
      "fontSizes": [ { "slug": "small", "size": "0.875rem", "fluid": false }, { "slug": "body", "size": "1.0625rem", "fluid": { "min": "1rem", "max": "1.0625rem" } },
        { "slug": "lead", "size": "1.25rem", "fluid": { "min": "1.125rem", "max": "1.25rem" } }, { "slug": "h3", "size": "1.625rem", "fluid": { "min": "1.375rem", "max": "1.625rem" } },
        { "slug": "h2", "size": "2.875rem", "fluid": { "min": "2rem", "max": "2.875rem" } }, { "slug": "display", "size": "5rem", "fluid": { "min": "2.75rem", "max": "5rem" } } ] },
    "layout": { "contentSize": "760px", "wideSize": "1200px" }
  },
  "styles": {
    "color": { "background": "var(--wp--preset--color--cream)", "text": "var(--wp--preset--color--ink)" },
    "elements": {
      "heading": { "typography": { "fontFamily": "var(--wp--preset--font-family--display)", "fontWeight": "400", "lineHeight": "1.05", "letterSpacing": "-0.01em" } },
      "link": { "color": { "text": "var(--wp--preset--color--cherry)" } },
      "button": { "color": { "background": "var(--wp--preset--color--cherry)", "text": "var(--wp--preset--color--white)" }, "border": { "radius": "10px" }, "shadow": "3px 3px 0 var(--wp--preset--color--ink)", "spacing": { "padding": { "top": "12px", "bottom": "12px", "left": "22px", "right": "22px" } }, "typography": { "fontWeight": "600" } }
    },
    "blocks": {
      "woocommerce/product-price": { "typography": { "fontWeight": "600" } },
      "woocommerce/mini-cart": { "color": { "text": "var(--wp--preset--color--ink)" } }
    }
  }
}
```

WooCommerce block styles that must be overridden in the child `style.css` (they ship their own CSS): `.wc-block-components-button` (offset shadow, radius, cherry), `.wc-block-components-text-input input` (48 px, ink 45 percent edge), `.wc-block-components-radio-control` (pill chips for shipping options), `.wc-block-components-totals-*` (tabular numerals, Instrument Serif on the grand total), `.wc-block-components-notice-banner` (demo notice colours). Keep WooCommerce's own focus styles but recolour to cherry.

## 5. Static GitHub Pages copy vs live Playground

| Piece | Static export (GitHub Pages) | Live Playground (WordPress running) |
|---|---|---|
| Home, shop grid, category chips, sort | Works: cards are server-rendered; chips and sort filter client-side with the same 2 KB script pattern as the Makers directory. Category and tag archive pages are exported so the no-JS path still works | Works natively (Product Filters, Catalog Sorting blocks) |
| Product pages, size price switch | Works: variations are exported as data attributes and the price updates in JS; "Add to cart" opens the simulated cart | Works: real variable product, real Add to Cart |
| Bundle builder | Works fully: picking, pricing, discount, "Add bundle" writes to the simulated cart | Works fully: block adds three items plus a cart fee |
| Cart | Simulated: items held in `sessionStorage`, counts shown in the header badge, remove and coupon ("FIRSTBAG", 10 percent) work; a note says "Demo cart, held in your browser" | Real WooCommerce cart |
| Checkout | Form renders; "Place test order" validates required fields in the browser and goes to the confirmation page with a generated order number. Nothing is sent anywhere (the exporter strips field names, as with the other demos' forms) | Real checkout; Stripe test mode with 4242 card or "Test order" gateway |
| Confirmation | Static page with sample order LH-10427; the simulated cart is cleared | Real `order-received` page |
| Emails, My account, stock | Not available (note in the footer) | Order emails go to Playground's mail log; My account works |

The landing page `dist/index.html` should label the store card "Catalog, product pages and bundle builder work here; cart and checkout run for real in the Playground link".

## 6. Responsive behaviour matrix

Breakpoints (min-width): `sm 600`, `md 768`, `lg 1024`, `xl 1280`.

| | Mobile 360 to 390 | Tablet 768 | Small desktop 1024 | Desktop 1280 to 1440 |
|---|---|---|---|---|
| Header | Wordmark, search, cart badge, hamburger (all 44 px); menu is a full-height sheet | Same | Inline nav of 4 + icons | Same, 72 px tall |
| Hero | Stacked: text, buttons wrap full width, facts wrap, photo 4:5 | Same stack | 7/12 + 5/12 | Same, H1 max 12ch |
| Roast-notes strip | 2 x 2 | 4 across | 4 across | 4 across |
| Product cards | 2 across, 24 px gap; title links padded to 44 px tall | 3 across | 4 across (home and shop) | 4 across |
| Bundle | Tiles 2 across; receipt card below the grid, full width, not sticky | Tiles 3 across; receipt below | 7/12 tiles + 5/12 sticky receipt (`top: 88px`) | Same |
| Product page | Gallery then details; options wrap; Add button fills the row | 6/6 | 7/5 | 7/5 |
| Cart and checkout | Single column, summary after the form; "Place test order" full width | Single column | 7/12 form + 5/12 sticky summary | Same |
| Footer | Brand block spans, then 2 x 2 link columns (44 px rows) | 2fr 1fr 1fr 1fr | Same | Same |
| Type | Display 44, H2 32, body 16 | Display 58, H2 38 | Display 70 | Display 80, H2 46, body 17 |

Verified in the mockup at 390 / 768 / 1440 on all seven screens: zero horizontal overflow, every control 44 px or taller (card title links are padded to 44 px; the breadcrumb links are 44 x 44).

## 7. Lighthouse notes

Two small font files, no icon font (inline SVG), product photos as 800 px WebP 1:1 with width and height, lazy below the fold, hero `fetchpriority="high"`. WooCommerce's block CSS is the main weight: on the static copy, export only `wc-blocks-style.css` (about 60 KB gzipped to 12) and drop `wc-blocks-vendors` and the cart/checkout React bundles (they only run live). Keep jQuery off the catalog pages (WooCommerce core needs it only for the legacy add-to-cart AJAX; the block button does not). Target the same gates as the other demos: Perf 90+, A11y / BP / SEO 95+, LCP under 2.5 s, CLS under 0.1.

## 8. Decisions for William

1. Brand: coffee roaster chosen over a tea shop because bags, grind options and a roast date give the product page and bundle builder more to show. Easy to re-skin to tea if preferred.
2. Bundle rule: exactly 3 items, 12 percent off. Alternative "3 bags only" is simpler to explain but hides the mixed-cart logic that makes the demo interesting.
3. Payments in Playground: Stripe test keys need to be in the blueprint (public test keys are safe to commit; restricted test secret should still not be). If that is unwanted, the "Test order" (cash on delivery renamed) gateway shows the full checkout flow with no keys.
4. Subscriptions appear as one simple product with honest copy; no subscription extension is faked.
