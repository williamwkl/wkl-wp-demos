# Saltline Kayak Tours: design spec

Home mockup: `design/home-mockups/saltline.html` (renders: `saltline-desktop.png`, `saltline-mobile.png`).

## 1. Brand concept

A small, guide-owned sea kayak outfit on a cold northern coast. Quiet confidence, salt-worn, editorial: big serif headlines on warm sand, deep sea teal as the brand colour, one coral accent for the booking action.
Name: **Saltline Kayak Tours**. Tagline: **Paddle the coast at its own pace.** Register: outdoor editorial (think a well-made guidebook, not an adventure-sports site).

Copy rules for the build: no em dashes, never the word "AI", no real people, addresses or phones. Use `hello@saltlinekayak.example.com` and `(555) 010-0100`. Town is fictional: **Marrow Cove, on the North Shore**.

## 2. Design tokens

### Colour (roles + verified contrast)

| Token | Hex | Role |
|---|---|---|
| `--c-ink` | `#15262C` | body text, dark band background |
| `--c-sea` | `#0E4D5C` | brand teal: headings on dark band, links, secondary buttons |
| `--c-sea-deep` | `#083440` | dark band gradient end, footer |
| `--c-coral` | `#B8461F` | THE accent. Primary CTA fill only. Keep under 10 percent of any screen |
| `--c-sand` | `#F4EEE3` | page background |
| `--c-sand-2` | `#E9E0D0` | tinted section background, card surfaces on sand |
| `--c-mist` | `#D7E4E6` | cool tint, chips and eyebrow text on dark |
| `--c-mute` | `#4E6368` | secondary text |
| `--c-white` | `#FBF9F4` | card surface, text on dark |

WCAG ratios (computed, sRGB):

- ink on sand 13.52 (AAA) · ink on white 14.83 · mute on sand 5.50 (AA) · mute on sand-2 4.85 (AA)
- sea on sand 8.14 (AA, use for links and teal headings)
- white on sea 8.93 · white on sea-deep 12.67 · mist on sea 7.22 · sand on ink 13.52
- white on coral 5.07 (AA normal text, button label) · coral on sand 4.62 (AA; coral text on sand allowed at 16px+)
- Never: ink on coral (3.47), coral text on sand-2.

No pure black or white anywhere. Sand and ink are both tinted warm/cool toward the sea hue.

### Type

Two families maximum, both self-hosted woff2 with `font-display: swap`:

- Display: **Fraunces** (variable, opsz 9..144, weights 500 and 600 only). Fallback: `"Fraunces", "Iowan Old Style", "Palatino Linotype", Georgia, serif`. Letter-spacing on display sizes: -0.02em.
- Body: **system sans** (no file to load): `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif`.

Scale (mobile 390 / desktop 1440, line-height):

| Token | Mobile | Desktop | LH | Use |
|---|---|---|---|---|
| `--fs-display` | 42px | 76px (`clamp(2.625rem, 1.2rem + 5.2vw, 4.75rem)`) | 1.02 | H1 hero |
| `--fs-h2` | 32px | 48px (`clamp(2rem, 1.2rem + 2.6vw, 3rem)`) | 1.08 | section titles |
| `--fs-h3` | 22px | 26px | 1.2 | card titles |
| `--fs-lead` | 18px | 20px | 1.5 | hero sub, intro paragraphs |
| `--fs-body` | 16px | 17px | 1.6 | body |
| `--fs-small` | 14px | 14px | 1.5 | meta, captions |
| `--fs-eyebrow` | 12px | 13px | 1.3 | all caps, +0.08em tracking, used at most once per section |

Prose max width 62ch.

### Spacing, radius, depth

- Spacing scale (rem): `4 8 12 16 24 32 48 64 96 128` as `--sp-1..--sp-10`. Section padding: 96px desktop, 64px mobile. Grid gap 24px mobile, 32px desktop. Outer gutter 20px mobile, 48px desktop. Content max width 1180px.
- Radius: `--r-sm 6px` (chips, inputs), `--r-md 12px` (cards, buttons), `--r-lg 20px` (hero image). Nested radius = outer minus gap.
- Depth technique: **flat background shifts only** (sand to sand-2 to dark band). No shadows, no card borders. The only hairline is the nav bottom rule at `rgba(21,38,44,0.12)`.

### Buttons and focus

- Primary: coral fill, white label, 600 weight, 14px 24px padding (min height 48px), radius 12px, hover darken 6 percent, active `scale(0.97)`, transition 160ms ease-out.
- Secondary: transparent, 1.5px ink border at 60 percent opacity, ink label; on dark bands: mist border, white label.
- Text link: sea colour, underline offset 3px, hover ink.
- Focus ring (all interactive): `outline: 3px solid var(--c-coral); outline-offset: 3px;` on dark bands use mist.
- Reduced motion: all transitions removed under `prefers-reduced-motion: reduce`.

## 3. Page specs

### Home (see mockup)

1. **Header**: wordmark "Saltline" with a 3-wave inline SVG mark, nav Tours / About / FAQ / Contact, primary button "Book a tour". Mobile: wordmark + Book button + hamburger (44px target). Sticky on desktop only.
2. **Hero (the one distinctive move: asymmetric split with a photo that overhangs the proof strip)**. Left 7/12: eyebrow "Guided sea kayak tours, Marrow Cove"; H1 "Paddle the coast at its own pace."; lead "Small-group tours along the North Shore, led by guides who grew up on this water. No experience needed, every bit of gear included."; primary "Book a tour", secondary "See the three tours". Right 5/12: portrait photo 4:5, radius 20px, offset 48px down on desktop. Mobile: text then photo 4:5 at full width.
3. **Conditions strip (sand-2)**: four facts, each label + value: Tide "High 7:42 am", Water "58 F", Wind "6 kt NW", Sunrise "6:51 am". Marked "Today, sample data" in small text. Builder: static text, no live feed.
4. **Proof row**: "4.9 average from 312 reviews (sample)", "Small groups, 8 paddlers max", "Certified sea kayak guides", "Dry bags, spray skirts and wetsuits included". Inline SVG icons (Lucide: star, users, award, package), 20px, 0.75 opacity.
5. **Tours grid (the only card grid on the page)**: H2 "Three tours, one coast." Three cards, image 4:3, name, duration + level, price, text link "Check dates".
   - **Sunrise Harbor Paddle**: 2 hours, easy, $65 per person. "Glassy water, seals on the breakwater, coffee on the beach after."
   - **Sea Cave Explorer**: 3.5 hours, moderate, $110. "Paddle into the Hollow Cliffs caves at low tide with a guide who knows every swell."
   - **Night Glow Paddle**: 2 hours, easy, $95. "Summer nights when the bay lights up with bioluminescence. Blankets and cocoa provided."
6. **Objection section (split, photo left 5/12, text right)**: H2 "Never kayaked? Good, most of our guests haven't." Paragraph on the 20-minute beach lesson, stable touring kayaks, and the guide ratio. Three short facts as a plain list (not cards): "1 guide for every 4 paddlers", "Wetsuits for every size, kids 8 and up", "Free reschedule if the forecast turns".
7. **Testimonials (sand-2)**: two quotes marked "(sample review)". "My 9-year-old asked to go again before we even got the boats out of the water." Priya R. · "Jonah read the swell like a weather app and got us into the far cave. Unreal." Marcus T.
8. **Plan your trip band (dark, sea to sea-deep)**: H2 "Plan your morning on the water." Three mini FAQs (What to wear, Cancellations, Where to meet) each two lines, then primary CTA "Check availability" and secondary "Read all FAQs".
9. **Footer (sea-deep)**: wordmark, three link columns (Tours, Company, Help), meeting point "The boathouse at Marrow Cove public launch", email, phone, "Sample site built for portfolio purposes."

### Tours

Hero: short text hero (H1 "Tours", lead). Then one full-width row per tour alternating image side (Media & Text), each with: 4:3 photo, name, 60-word description, facts list (duration, level, group size, price, what is included), season dates, "Book this tour" primary. End with the FAQ teaser band.

### About

H1 "Two guides, one boathouse, fifteen seasons." Split hero with a photo of the boathouse (not faces). Story paragraph (fictional founders Jonah and Lise, surnames omitted). Certifications list. Safety section with a photo of gear laid out. Ends with the dark band CTA.

### FAQ

Single column, 10 items in an accordion (core Details block): experience needed, age minimum (8), what to wear, what is included, weather and cancellations (free reschedule, full refund if we cancel), parking, restrooms, photos (guide shoots, shared free within 48 hours), group bookings, gift cards. Sidebar on desktop with "Still have a question?" email box.

### Contact / Booking

Two columns desktop: left form (name, email, tour select, preferred date, group size, message, consent checkbox), right details (meeting point, hours, map placeholder as a tinted block with a pin icon, email, phone). Mobile: form first. Success state inline. Form plugin: WPForms Lite or the core Contact pattern via a lightweight plugin; no external booking widget (keeps Lighthouse clean).

## 4. Imagery direction

All photos: one grade (slightly warm, lifted blacks), same radius 12/20px. Prefer scenes without faces or with people seen from behind. Free sources: Unsplash and Pexels (both free licence, no attribution required; record the photographer in the media library caption anyway).

| Section | Shot | Ratio | Search terms |
|---|---|---|---|
| Hero | Lone kayaker from behind on glassy teal water, cliffs | 4:5 | "sea kayak cliffs morning", "kayak fjord calm" |
| Tours cards | Harbor seal, cave entrance, bioluminescence night (or kayak at dusk) | 4:3 | "harbor seal rocks", "sea cave kayak", "bioluminescence beach" |
| Objection | Beach lesson, paddles lined up | 4:3 | "kayak paddles beach gear" |
| About | Wooden boathouse, gear flat lay | 3:2 | "boathouse coast", "kayak gear" |
| Conditions strip, footer | No photo, CSS only | | |

Export: WebP, hero 1600px wide max (about 120 KB), cards 900px, `loading="lazy"` on everything below the hero, hero `fetchpriority="high"` with width/height set.

## 5. WordPress build mapping (Twenty Twenty-Five child theme)

| Section | Blocks / pattern |
|---|---|
| Header | Template part `header`: Group (flex, space-between) with Site Title + Navigation + Buttons |
| Hero | Columns (7/12 + 5/12), Paragraph (eyebrow style), Heading h1, Paragraph (lead), Buttons, Image (rounded style) |
| Conditions strip | Group (background sand-2, full width) > Columns x4 > Paragraph (label) + Paragraph (value) |
| Proof row | Group > Columns x4 > inline SVG via Custom HTML + Paragraph |
| Tours grid | Group > Columns x3 (or Query Loop over a `tour` CPT if the builder adds one) > Image + Heading h3 + Paragraph + Paragraph (meta) + Paragraph link |
| Objection | Media & Text (media left, 42 percent) + List |
| Testimonials | Group (sand-2) > Columns x2 > Quote block |
| Plan band | Cover (no image, solid sea gradient via custom class) > Heading + Columns x3 + Buttons |
| Footer | Template part `footer`: Columns x4 |
| FAQ page | Details block per question |
| Contact | Columns 7/5 + form plugin shortcode block |

Register the six sections as block patterns (`saltline/hero`, `saltline/conditions`, `saltline/proof`, `saltline/tours`, `saltline/split`, `saltline/cta-band`).

### theme.json snippet

```json
{
  "version": 3,
  "settings": {
    "color": {
      "defaultPalette": false,
      "palette": [
        { "slug": "ink", "name": "Ink", "color": "#15262C" },
        { "slug": "sea", "name": "Sea", "color": "#0E4D5C" },
        { "slug": "sea-deep", "name": "Sea deep", "color": "#083440" },
        { "slug": "coral", "name": "Coral", "color": "#B8461F" },
        { "slug": "sand", "name": "Sand", "color": "#F4EEE3" },
        { "slug": "sand-2", "name": "Sand 2", "color": "#E9E0D0" },
        { "slug": "mist", "name": "Mist", "color": "#D7E4E6" },
        { "slug": "mute", "name": "Mute", "color": "#4E6368" },
        { "slug": "white", "name": "Warm white", "color": "#FBF9F4" }
      ]
    },
    "typography": {
      "fluid": true,
      "fontFamilies": [
        { "slug": "display", "name": "Fraunces", "fontFamily": "\"Fraunces\", \"Iowan Old Style\", Georgia, serif",
          "fontFace": [ { "fontFamily": "Fraunces", "fontWeight": "500 600", "fontStyle": "normal", "fontDisplay": "swap", "src": [ "file:./assets/fonts/fraunces-var.woff2" ] } ] },
        { "slug": "body", "name": "System sans", "fontFamily": "-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif" }
      ],
      "fontSizes": [
        { "slug": "small", "size": "0.875rem", "fluid": false },
        { "slug": "body", "size": "1.0625rem", "fluid": { "min": "1rem", "max": "1.0625rem" } },
        { "slug": "lead", "size": "1.25rem", "fluid": { "min": "1.125rem", "max": "1.25rem" } },
        { "slug": "h3", "size": "1.625rem", "fluid": { "min": "1.375rem", "max": "1.625rem" } },
        { "slug": "h2", "size": "3rem", "fluid": { "min": "2rem", "max": "3rem" } },
        { "slug": "display", "size": "4.75rem", "fluid": { "min": "2.625rem", "max": "4.75rem" } }
      ]
    },
    "spacing": {
      "spacingSizes": [
        { "slug": "20", "size": "0.5rem", "name": "2" }, { "slug": "30", "size": "1rem", "name": "4" },
        { "slug": "40", "size": "1.5rem", "name": "6" }, { "slug": "50", "size": "2rem", "name": "8" },
        { "slug": "60", "size": "3rem", "name": "12" }, { "slug": "70", "size": "4rem", "name": "16" },
        { "slug": "80", "size": "6rem", "name": "24" }
      ],
      "units": [ "px", "rem", "%", "vw" ]
    },
    "layout": { "contentSize": "760px", "wideSize": "1180px" }
  },
  "styles": {
    "color": { "background": "var(--wp--preset--color--sand)", "text": "var(--wp--preset--color--ink)" },
    "typography": { "fontFamily": "var(--wp--preset--font-family--body)", "fontSize": "var(--wp--preset--font-size--body)", "lineHeight": "1.6" },
    "elements": {
      "heading": { "typography": { "fontFamily": "var(--wp--preset--font-family--display)", "fontWeight": "500", "lineHeight": "1.08", "letterSpacing": "-0.02em" } },
      "link": { "color": { "text": "var(--wp--preset--color--sea)" } },
      "button": { "color": { "background": "var(--wp--preset--color--coral)", "text": "var(--wp--preset--color--white)" }, "border": { "radius": "12px" }, "spacing": { "padding": { "top": "14px", "bottom": "14px", "left": "24px", "right": "24px" } }, "typography": { "fontWeight": "600" }, ":focus": { "outline": { "color": "var(--wp--preset--color--coral)", "width": "3px", "style": "solid", "offset": "3px" } } }
    }
  }
}
```

Lighthouse notes: one font file (Fraunces variable subset, about 60 KB; or drop to two static woff2 at 500/600), no slider, no video, hero WebP with explicit dimensions, Details block for FAQ (no JS accordion plugin).

## 6. Responsive behaviour matrix

Breakpoints (min-width, mobile first): `sm 600`, `md 768`, `lg 1024`, `xl 1280`. Mockup renders at 390, 768 and 1440 are in `design/home-mockups/saltline-{mobile,tablet,desktop}.png`.

| | Mobile 360 to 390 | Tablet 768 | Small desktop 1024 | Desktop 1280 to 1440 |
|---|---|---|---|---|
| Header / nav | Wordmark + "Book a tour" (44px tall, 16px side padding) + hamburger. Menu opens as a full-height sheet from the right, links 56px tall, close button 44px. Not sticky | Same as mobile (hamburger); header not sticky | Full inline nav (4 links) + primary button; sticky with blur | Same, 72px tall, 48px gutters |
| Gutter / content | 20px gutters, content 350px | 32px gutters | 40px gutters | 48px gutters, max 1180px centred |
| Type scale | Display 42, H2 32, H3 22, lead 18, body 16 | Display 52, H2 38, H3 24, lead 19 | Display 66, H2 44 | Display 76, H2 48, H3 26, lead 20, body 17 |
| Hero | Stacked: eyebrow, H1, lead, two full-width-wrapping buttons, then photo 4:5 at full width overhanging the strip by 32px | Same stack, photo 4:5 at full width (reads large, fine at 768) | Two columns 7/12 + 5/12, photo 4:5 overhangs 64px | Same, hero copy padded 32px from top |
| Hero image crop | 4:5, focal point centred on the paddler | 4:5 | 4:5 | 4:5; never 16:9 (the overhang needs height) |
| Conditions strip | 2 x 2 grid, values 22px | 4 across, values 24px | 4 across | 4 across, values 28px |
| Proof row | 2 x 2, 14px text | 4 across | 4 across | 4 across |
| Tours grid | 1 column, 48px between tours, image 4:3 | 3 across (cards get narrow but hold; alternatively 1 + 2 is not allowed, keep equal) | 3 across | 3 across, 32px gap |
| Objection split | Stacked, photo first then text | Two columns 5/7 | Two columns | Two columns, centred vertically |
| Testimonials | Stacked, 32px gap | 2 across | 2 across | 2 across, 48px gap |
| Plan band | Mini FAQs stacked, buttons wrap | 3 across | 3 across | 3 across |
| Footer | 2 x 2 columns (brand block spans first row) | 4 across | 4 across | 2fr 1fr 1fr 1fr |
| Forms (Contact) | Single column, inputs 48px, 16px font, submit full width, sticky "Book a tour" not needed (CTA already in header) | Single column form, details beside it from 768 (7/5) | 7/5 | 7/5 |
| Tours page rows | Media & Text stacks, image on top | Side by side 50/50 | 50/50 | 42/58 alternating |
| FAQ page | Single column accordion, 56px tap rows | Single column, 680px max | Accordion + sidebar from 1024 | Accordion 7 cols + sidebar 4 cols |
| Touch targets | All links and buttons 44px minimum; chip and nav links padded to 44 | | | |
