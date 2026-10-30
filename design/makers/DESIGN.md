# Fernwood Makers: design spec (local makers directory)

Home mockup: `design/home-mockups/makers.html` (renders: `makers-desktop.png`, `makers-mobile.png`).

## 1. Brand concept

A county-wide directory of independent makers (potters, woodworkers, weavers, bakers, printers) that reads like a well-run civic catalogue: chunky grotesque type, bone-white ground, ochre as the single accent, and the search box as the hero. Utility first; the maker photos carry the warmth.
Name: **Fernwood Makers**. Tagline: **Find the people who make things in Fernwood County.** Register: modern catalogue / field guide (think a good public-library site, not a marketplace).

Copy rules: no em dashes, never the word "AI", no real people, addresses or phones. `hello@fernwoodmakers.example.com`, `(555) 010-0100`. Fictional county **Fernwood** with nine fictional towns: Alder Creek, Bramblewood, Coldwater, Dunmore, Elk Ridge, Fallowfield, Greystone, Harlow, Ivywick. All makers and shops are fictional.

## 2. Design tokens

### Colour

| Token | Hex | Role |
|---|---|---|
| `--c-ink` | `#161512` | text, dark band, primary dark buttons |
| `--c-ochre` | `#B4700F` | THE accent. Large text only (24px+), chips when active, underline marks, category dot. Never small text |
| `--c-ochre-deep` | `#8F5607` | accent for small text and the primary CTA fill |
| `--c-bone` | `#F6F4EE` | page background |
| `--c-bone-2` | `#ECE8DE` | tinted sections, filter rail background, input background |
| `--c-clay` | `#C8B89A` | image placeholder, decorative rules on dark |
| `--c-moss` | `#3F5A3C` | one secondary used for the "Open studio" availability tag only |
| `--c-mute` | `#5B5850` | secondary text |
| `--c-white` | `#FFFFFF` | card surfaces only (cards on bone; the page itself is never pure white) |

WCAG ratios:

- ink on bone 16.60 · ink on white 21 · mute on bone 6.46 · mute on bone-2 5.81 · mute on white 7.10
- ochre-deep on bone 5.45 (AA, small text) · white on ochre-deep 6.00 (AA, button label)
- ochre on bone 3.63: large text (24px+ or 19px bold) and non-text only
- ink on ochre 4.58 (AA, active chip label) · white on moss 7.67 · bone on ink 16.60

### Type

- Display and UI: **Bricolage Grotesque** (variable; use weights 500 and 700, width 100 only). Fallback: `"Bricolage Grotesque", "Helvetica Neue", Arial, sans-serif`. Display tracking -0.025em; the H1 uses 700.
- Body: **system sans** stack (same as the other demos). The directory is text-heavy; system fonts keep it fast.

| Token | Mobile | Desktop | LH | Use |
|---|---|---|---|---|
| `--fs-display` | 40px | 72px (`clamp(2.5rem, 1rem + 5vw, 4.5rem)`) | 1.0 | H1 |
| `--fs-h2` | 28px | 40px (`clamp(1.75rem, 1.2rem + 2vw, 2.5rem)`) | 1.1 | section titles |
| `--fs-h3` | 18px | 20px | 1.3 | card titles (Bricolage 700) |
| `--fs-lead` | 17px | 19px | 1.5 | hero sub |
| `--fs-body` | 16px | 16px | 1.6 | body |
| `--fs-small` | 13px | 13px | 1.4 | meta, chips, filter labels |
| `--fs-eyebrow` | 12px | 12px | 1.3 | all caps +0.08em, used once in the featured band |

Inputs 16px minimum, 48px high.

### Spacing, radius, depth

- Scale `4 8 12 16 24 32 48 64 96 128`. Sections 80px desktop / 56px mobile (tighter than the other two: a directory is denser). Gutter 16 mobile / 40 desktop. Content max 1240px (wider to fit rail + grid).
- Radius: `--r-sm 4px` (chips, inputs), `--r-md 10px` (cards, buttons), `--r-pill 999px` (search field, filter chips).
- Depth: **flat with hairline borders on cards only** (`1px solid rgba(22,21,18,0.1)`). No shadows. Hover on a card: border darkens to 0.3 and the image zooms 1.02 (transform only, 200ms).

### Buttons, chips, focus

- Primary: ochre-deep fill, white label, 600, 14px 22px, 48px tall, radius 10px.
- Dark: ink fill, bone label (used for "Search" inside the hero bar).
- Secondary: 1.5px ink border at 50 percent, ink label.
- Chip (filter): pill, bone-2 fill, ink label 13px, 36px tall (44px tap area via padding on mobile); active = ochre fill, ink label; a check icon appears when active (not colour only).
- Focus: `outline: 3px solid var(--c-ochre-deep); outline-offset: 2px`.

## 3. Page specs

### Home (see mockup)

1. **Header**: wordmark "Fernwood Makers" with a small inline SVG fern-leaf mark, nav Browse / Categories / Towns / Events / For makers, primary "List your work". Mobile: wordmark + search icon button + hamburger.
2. **Hero (the distinctive move: an oversized typographic hero whose only object is the search bar)**: H1 "Find the people who make things in Fernwood County." (the word "make" underlined in ochre with an SVG stroke), lead "214 independent makers across 9 towns. Search by craft, town or price, then visit the studio or order direct." Pill search bar: text input "Try: stoneware mugs, Coldwater, under $50" + dark "Search" button. Below: six category chips with counts: Ceramics 41, Woodwork 36, Textiles 29, Jewelry 27, Food and drink 44, Print and paper 22, plus "All categories".
3. **Browse block (rail + grid; the only card grid on the page)**: H2 "Browse the directory." Desktop: left rail 260px (bone-2) with three filter groups: Category (checkbox list), Town (checkbox list of 9), Price range (four options: $ under 25, $$ 25 to 75, $$$ 75 to 200, $$$$ 200 and up), "Open studio visits" toggle, "Clear filters" link. Results header: "Showing 6 of 214" + Sort select (Recommended, Newest, A to Z). Grid 3 columns of listing cards: image 4:3, category + town meta line, name (H3), maker name, price band, "Open studio" moss tag where applicable. Mobile: horizontal scrolling chip row (Category, Town, Price, Open studio) + "Filters" button (opens a bottom sheet with the same three groups), grid becomes 1 column at 390 and 2 columns at 600+. Six sample listings:
   - **Hollow Oak Woodworks**, Ada Lindqvist, Alder Creek, Woodwork, $$$, Open studio
   - **Coldwater Clay**, Theo Marchetti, Coldwater, Ceramics, $$
   - **Bramble Loom**, Sunita Rao, Bramblewood, Textiles, $$$
   - **Greystone Press**, Milo Fenwick, Greystone, Print and paper, $
   - **Elk Ridge Honey Co.**, June Castellano, Elk Ridge, Food and drink, $, Open studio
   - **Ivywick Silver**, Hana Okabe, Ivywick, Jewelry, $$$$
4. **Featured maker band (dark ink, full bleed)**: eyebrow "Maker of the month", large italic-free Bricolage quote "I throw about forty mugs a week. Maybe six make it to the shelf." Ada Lindqvist is woodwork, so the quote belongs to Theo Marchetti, Coldwater Clay. Portrait 4:5 right (hands at the wheel, no face), primary "Visit Coldwater Clay", secondary "Read the interview".
5. **Towns row (bone-2)**: H2 "Nine towns, one directory." Nine town links in a wrapping list, each with its count ("Coldwater 31"), the count in mute. No cards.
6. **For makers (split)**: H2 "Make things in Fernwood? Get listed for free." Short paragraph (free listing, 10-minute form, reviewed within a week, no commission ever) + primary "List your work" + text link "Listing guidelines". Right: a mini listing card preview with "Your studio here" to show what they get.
7. **Footer (ink)**: wordmark, four link columns (Browse, Towns, For makers, About), "A community project of the Fernwood County Library (fictional)", email, "Sample site built for portfolio purposes."

### Browse (the directory page)

Same rail + grid as the home browse block but paged (12 per page), with the search bar sticky under the header, active filters shown as removable chips above the grid, the results count announced via `aria-live="polite"`, empty state "No makers match those filters yet. Try removing a town or widening the price range." with a Clear filters button, and URL-synced filters (`?category=ceramics&town=coldwater&price=2`) so results are shareable and crawlable.

### Listing (single maker)

Gallery (one large 4:3 + three 1:1 thumbs, no slider), name, category chips, town, price band, "Open studio" tag, description (prose 64ch), details list (materials, lead time, where to buy, hours), map placeholder tile, contact form (name, email, message, no phone field), "More in Ceramics" row of 3 cards.

### Categories, Towns

Simple index pages: a heading, a sentence, and a wrapping list of links with counts, then the grid filtered by that term.

### For makers (submit a listing)

Explain the criteria (made in Fernwood County, by the person listed, sold to the public), then the form: business name, maker name, category, town, price band, description, website, up to 4 photos, consent. Success: "Thanks. We review every listing by hand and will email you within a week."

## 4. Imagery direction

Hands and work surfaces, never faces: the directory is about the objects. One grade: neutral, slight warmth, consistent 4:3 crop with the object centred. Free sources: Unsplash and Pexels.

| Section | Shot | Ratio | Search terms |
|---|---|---|---|
| Listing cards | Mugs on a shelf, carved spoons, loom close-up, letterpress blocks, honey jars, silver rings on linen | 4:3 | "stoneware mugs shelf", "wooden spoons carved", "weaving loom close up", "letterpress type", "honey jars", "silver rings linen" |
| Featured band | Hands at a pottery wheel | 4:5 | "pottery wheel hands clay" |
| For makers preview card | Reuse a card photo | 4:3 | |
| Hero | No image, typographic | | |

WebP, cards 800px wide, lazy loaded; featured portrait 1000px.

## 5. WordPress build mapping (block theme, Twenty Twenty-Five child)

Data model: CPT `maker` (supports title, editor, thumbnail), taxonomies `craft` (category) and `town`, post meta `price_band` (1 to 4) and `open_studio` (bool), plus `maker_name`, `website`. Register with a 60-line mu-plugin or the child theme's functions.php.

Filtering (decision for William): simplest Lighthouse-safe path is **server-rendered filtering with a URL query**: a custom `filter-form` block (or a plain HTML form inside a Group block) that submits GET params, plus a `pre_get_posts` hook that applies `tax_query` and `meta_query` to the `maker` archive, which the Query Loop block renders. No JS plugin, no client-side rendering, fully crawlable. Alternative if the builder wants instant filtering: the free **FacetWP** is not free; "Filter Everything" (free tier) works with Query Loop but adds about 60 KB JS. Recommend the server-rendered form.

| Section | Blocks / pattern |
|---|---|
| Header | Template part: Group flex > Site Title + Navigation + Buttons |
| Hero | Group (wide) > Heading h1 (display style) + Paragraph lead + Search block (styled pill, button inside) + Group flex-wrap of Button blocks styled as chips (`is-style-chip`) linking to `/craft/ceramics/` |
| Browse block | Columns (260px fixed + fluid) > left: the filter form (Custom HTML or custom block) / right: Query Loop (post type maker, 6 per page, 3 columns grid) > Post Featured Image (4:3) + Post Terms (craft, town) + Post Title h3 + custom block for maker name, price band, open-studio tag |
| Featured band | Cover (solid ink) > Columns 7/5 > Paragraph eyebrow + Heading h2 (quote) + Buttons / Image |
| Towns row | Group (bone-2) > Heading + Terms List via Custom HTML or a Query Loop over the `town` taxonomy (needs a small block or shortcode) |
| For makers | Media & Text reversed > Heading + Paragraph + Buttons / Group card |
| Footer | Template part: Columns x4 |
| Browse page | `archive-maker.html` template: same columns, Query Pagination, aria-live count via a tiny inline script or server text |
| Single maker | `single-maker.html`: Columns > Gallery (no slider) / Post Title + Post Terms + meta block + Post Content + form shortcode |

Patterns to register: `fernwood/hero-search`, `fernwood/browse`, `fernwood/featured`, `fernwood/towns`, `fernwood/for-makers`.

### theme.json snippet

```json
{
  "version": 3,
  "settings": {
    "color": {
      "defaultPalette": false,
      "palette": [
        { "slug": "ink", "name": "Ink", "color": "#161512" },
        { "slug": "ochre", "name": "Ochre", "color": "#B4700F" },
        { "slug": "ochre-deep", "name": "Ochre deep", "color": "#8F5607" },
        { "slug": "bone", "name": "Bone", "color": "#F6F4EE" },
        { "slug": "bone-2", "name": "Bone 2", "color": "#ECE8DE" },
        { "slug": "clay", "name": "Clay", "color": "#C8B89A" },
        { "slug": "moss", "name": "Moss", "color": "#3F5A3C" },
        { "slug": "mute", "name": "Mute", "color": "#5B5850" },
        { "slug": "white", "name": "White", "color": "#FFFFFF" }
      ]
    },
    "typography": {
      "fluid": true,
      "fontFamilies": [
        { "slug": "display", "name": "Bricolage Grotesque", "fontFamily": "\"Bricolage Grotesque\", \"Helvetica Neue\", Arial, sans-serif",
          "fontFace": [ { "fontFamily": "Bricolage Grotesque", "fontWeight": "500 700", "fontStyle": "normal", "fontDisplay": "swap", "src": [ "file:./assets/fonts/bricolage-var.woff2" ] } ] },
        { "slug": "body", "name": "System sans", "fontFamily": "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif" }
      ],
      "fontSizes": [
        { "slug": "small", "size": "0.8125rem", "fluid": false },
        { "slug": "body", "size": "1rem", "fluid": false },
        { "slug": "lead", "size": "1.1875rem", "fluid": { "min": "1.0625rem", "max": "1.1875rem" } },
        { "slug": "h3", "size": "1.25rem", "fluid": { "min": "1.125rem", "max": "1.25rem" } },
        { "slug": "h2", "size": "2.5rem", "fluid": { "min": "1.75rem", "max": "2.5rem" } },
        { "slug": "display", "size": "4.5rem", "fluid": { "min": "2.5rem", "max": "4.5rem" } }
      ]
    },
    "spacing": {
      "spacingSizes": [
        { "slug": "20", "size": "0.5rem", "name": "2" }, { "slug": "30", "size": "1rem", "name": "4" },
        { "slug": "40", "size": "1.5rem", "name": "6" }, { "slug": "50", "size": "2rem", "name": "8" },
        { "slug": "60", "size": "3rem", "name": "12" }, { "slug": "70", "size": "3.5rem", "name": "14" },
        { "slug": "80", "size": "5rem", "name": "20" }
      ]
    },
    "layout": { "contentSize": "760px", "wideSize": "1240px" }
  },
  "styles": {
    "color": { "background": "var(--wp--preset--color--bone)", "text": "var(--wp--preset--color--ink)" },
    "typography": { "fontFamily": "var(--wp--preset--font-family--body)", "fontSize": "var(--wp--preset--font-size--body)", "lineHeight": "1.6" },
    "elements": {
      "heading": { "typography": { "fontFamily": "var(--wp--preset--font-family--display)", "fontWeight": "700", "lineHeight": "1.05", "letterSpacing": "-0.025em" } },
      "link": { "color": { "text": "var(--wp--preset--color--ink)" } },
      "button": { "color": { "background": "var(--wp--preset--color--ochre-deep)", "text": "var(--wp--preset--color--white)" }, "border": { "radius": "10px" }, "spacing": { "padding": { "top": "14px", "bottom": "14px", "left": "22px", "right": "22px" } }, "typography": { "fontWeight": "600" } }
    },
    "blocks": {
      "core/search": { "border": { "radius": "999px" } }
    }
  }
}
```

Lighthouse notes: one variable font file (subset Latin, about 70 KB) or two static weights; server-rendered filters (no filter plugin JS); 4:3 WebP cards with width/height; pagination not infinite scroll; the bottom-sheet filter on mobile is a `<details>` or a 30-line vanilla JS toggle, no library.

## 6. Responsive behaviour matrix

Breakpoints (mobile first): `sm 600` (grid 1 to 2 columns, search button label appears), `md 720` (for-makers split), `lg 960` (filter rail appears, grid 3 columns, inline nav), `xl 1280`. Mockup renders at 390, 768 and 1440 are in `design/home-mockups/makers-{mobile,tablet,desktop}.png`.

| | Mobile 360 to 390 | Tablet 768 | Small desktop 1024 | Desktop 1280 to 1440 |
|---|---|---|---|---|
| Header / nav | Wordmark + search icon button + hamburger (both 44px); "List your work" moves into the menu sheet | Wordmark + "List your work" + search icon + hamburger | Full inline nav (5 links) + "List your work" | Same, 68px tall |
| Gutter / content | 16px, max 1240 | 28px | 36px | 40px |
| Type scale | Display 40, H2 28, H3 18, lead 17, body 16 | Display 50, H2 32 | Display 60, H2 36 | Display 72, H2 40, H3 20, lead 19 |
| Hero search | Pill full width, icon-only Search button (arrow), input 44px; category chips in a single horizontal scrolling row (edge-to-edge, 44px tall) | Pill 720 max, "Search" label visible; chips wrap | Same | Same |
| Directory filters | No rail. A horizontal chip row: "Filters" (opens a bottom sheet with Category, Town, Price, Open studio, Clear and a full-width "Show N makers" button) plus active-filter chips; chips 44px tall | Same chip row + sheet (no rail until 960) | Left rail 260px (sticky, top 16px) + results | Rail 260px + results, 48px gap |
| Results header | "Showing 6 of 214" and Sort wrap to two lines | One line | One line | One line |
| Listing grid | 1 column, cards full width, image 4:3 | 2 columns, 24px gap | 3 columns | 3 columns, 24px gap |
| Card image crop | 4:3 at every size, object centred | | | |
| Featured band | Photo first (4:3 crop) then quote, buttons wrap | Photo first 4:3 then quote | Two columns 7/5, portrait 4:5 | 7/5, portrait 4:5 |
| Towns row | Wrapping list, 20px names | Wrapping, 24px | Wrapping, 26px | Wrapping, 28px, 32px gaps |
| For makers | Stacked: text, buttons, preview card (full width, 340 max) | Two columns from 720 (7/5), preview right | 7/5 | 7/5 |
| Footer | 2 x 2 (five groups: brand spans the first row) | 2 x 3 | 5 across | 2fr 1fr 1fr 1fr 1fr |
| Browse page | Sticky search under the header (56px), chip row, 1-col grid, pagination buttons 44px | 2-col grid | Rail + 3-col grid | Rail + 3-col grid |
| Single listing | Gallery stacked (1 large 4:3, 3 thumbs in a row), details below, form at end | Gallery 4:3 + thumbs row; details beside from 768 (7/5) | 7/5 | 7/5 |
| Submit form | Single column, inputs 48px, photo upload tiles 2 x 2, submit full width | Single column 640 max | Same | Same |
