# Harbor & Pine Law: design spec

Home mockup: `design/home-mockups/harbor-pine.html` (renders: `harbor-pine-desktop.png`, `harbor-pine-mobile.png`).

## 1. Brand concept

A two-partner personal injury and family law firm that reads as calm, local and plain-spoken: warm paper, deep ink, a restrained serif, one brass accent used only for the action that matters (the free case evaluation). Nothing shouts; the form is the hero.
Name: **Harbor & Pine Law**. Tagline: **Steady counsel when life changes course.** Register: quiet professional (a good letterhead, not a billboard).

Copy rules: no em dashes, never the word "AI", no real people, addresses or phones. Use `intake@harborpinelaw.example.com`, `(555) 010-0100`, fictional town **Harborview**. Partners are fictional: **Elena Marsh** (personal injury) and **Daniel Okafor** (family law). Every result or review is labelled sample. Footer carries "Attorney advertising. Prior results do not guarantee a similar outcome."

## 2. Design tokens

### Colour

| Token | Hex | Role |
|---|---|---|
| `--c-ink` | `#1B2733` | body text, headlines |
| `--c-navy` | `#24364A` | dark band and footer background, form card header |
| `--c-pine` | `#2E4A3E` | secondary brand tint: eyebrow text, icon colour, practice-area labels |
| `--c-brass` | `#8A5A24` | THE accent: primary CTA fill, active states, form submit |
| `--c-paper` | `#F8F5EF` | page background |
| `--c-paper-2` | `#EEE8DE` | tinted section background |
| `--c-line` | `#D9D2C5` | hairline borders (decorative only) |
| `--c-mute` | `#5A6673` | secondary text |
| `--c-white` | `#FFFDF9` | form card surface, text on dark |

WCAG ratios:

- ink on paper 13.94 · ink on paper-2 12.45 · mute on paper 5.39 (AA) · mute on paper-2 4.81 (AA)
- brass on paper 5.41 (AA, brass text allowed) · white on brass 5.79 (AA, button label)
- white on navy 12.15 · paper-2 on navy 10.13 · white on pine 9.55
- line on paper 1.38: decorative only, never a control edge (control edges use ink at 40 percent, 3.1:1 or better)

### Type

- Display: **Newsreader** (serif, weights 400 and 500, optical size variable). Fallback: `"Newsreader", "Iowan Old Style", "Times New Roman", serif`. Headlines at 400 weight, -0.015em tracking. Italic used only for the pull quote.
- Body: **system sans** stack: `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif`.

| Token | Mobile | Desktop | LH | Use |
|---|---|---|---|---|
| `--fs-display` | 38px | 64px (`clamp(2.375rem, 1.3rem + 4.2vw, 4rem)`) | 1.05 | H1 |
| `--fs-h2` | 30px | 42px (`clamp(1.875rem, 1.3rem + 2vw, 2.625rem)`) | 1.1 | sections |
| `--fs-h3` | 21px | 24px | 1.25 | practice area, attorney names |
| `--fs-lead` | 18px | 19px | 1.55 | hero sub |
| `--fs-body` | 16px | 17px | 1.65 | body |
| `--fs-small` | 14px | 14px | 1.5 | meta, disclaimers |
| `--fs-eyebrow` | 12px | 13px | 1.3 | all caps, +0.1em, pine colour, at most once per section |

Form inputs: 16px minimum, 48px min height (prevents iOS zoom, thumb friendly).

### Spacing, radius, depth

- Scale `4 8 12 16 24 32 48 64 96 128`. Sections 96px desktop / 64px mobile. Gutter 20 / 48. Content max 1140px, prose 64ch.
- Radius: `--r-sm 4px` (inputs, chips), `--r-md 8px` (buttons, form card). Deliberately tighter than the other demos: a law firm reads straighter.
- Depth technique: **hairline borders** (`--c-line`) only. No shadows anywhere except the sticky mobile CTA bar (`0 -1px 0 var(--c-line)`), which is a border, not a shadow. The form card: white surface + 1px line border.

### Buttons and focus

- Primary: brass fill, white label, 600, 14px 24px, min height 48px, radius 8px, hover brass darkened 8 percent, active `scale(0.98)`, 150ms ease-out.
- Secondary: 1.5px ink border at 55 percent, ink label. Phone link in header is a secondary.
- Text link: ink, underline 1px, underline colour brass, hover brass.
- Focus ring: `outline: 3px solid var(--c-brass); outline-offset: 2px`. On navy, outline colour paper.
- Reduced motion honoured.

## 3. Page specs

### Home (see mockup)

1. **Top bar (navy, 36px)**: left "Free case evaluations, no fee unless we win on injury cases" (small, paper-2), right phone link and "Evening and weekend calls". Hidden on mobile below 720px (the sticky bar covers it).
2. **Header**: wordmark "Harbor & Pine" with a thin pine-tree + wave inline SVG mark, nav Personal injury / Family law / Our attorneys / Results / Contact, secondary phone button, primary "Free case evaluation". Mobile: wordmark, phone icon button (44px), hamburger.
3. **Hero (the distinctive move: a two-column hero where the right column IS the intake form)**. Left 6/12: eyebrow "Personal injury and family law in Harborview"; H1 "Steady counsel when life changes course."; lead "When you are hurt, or your family is coming apart, you need someone who will tell you plainly where you stand. We have done that for Harborview families for 14 years."; primary "Start your free case evaluation" (anchors to the form), secondary "Call (555) 010-0100". Three facts in a row under the buttons: "No fee unless we win (injury)", "Same-week appointments", "Replies within one business day". Right 5/12 (offset 1/12): the **intake form card**: header "Free case evaluation" + "Takes about two minutes. Confidential.", fields: Full name, Phone, Email, "What do you need help with?" (select: Car or truck accident, Slip and fall, Workplace injury, Divorce or separation, Child custody and support, Something else), "Tell us briefly what happened" (textarea, optional), consent checkbox "I understand that submitting this form does not create an attorney-client relationship.", submit "Request my free evaluation". Mobile: hero text, then the form card full width (no sidebar), then the facts.
4. **Trust strip (paper-2)**: four items with 20px inline SVG icons (Lucide: shield-check, clock, phone, file-text): "Free, confidential first conversation", "No fee unless we win, injury cases", "Same-week appointments", "You talk to a lawyer, not a call centre".
5. **Practice areas (two columns divided by a hairline)**: H2 "Two kinds of hard weeks. We handle both." Left: H3 "Personal injury", list: Car and truck accidents, Slip and fall, Workplace injuries, Wrongful death. Right: H3 "Family law", list: Divorce and separation, Child custody and parenting time, Child and spousal support, Modifications and enforcement. Each column ends with a text link "How we handle injury cases" / "How we handle family cases".
6. **What to expect (split: text left 7/12, pull quote right)**: H2 "What the first 30 days look like." Three short paragraphs with run-in bold leads (not numbered circles): "The first call." "The plan in writing." "No surprises on fees." Right: italic Newsreader pull quote from Elena Marsh: "Most people call us on the worst week of their year. Our job is to make the next one calmer."
7. **Attorneys**: H2 "The people you will actually talk to." Two profiles side by side: 4:5 portrait placeholder (builder may use a free-licence portrait of a non-identifiable person or a monogram tile), name, title, 2-line bio, bar admission year (fictional), text link "More about Elena". Mobile: stacked.
8. **Results and reviews (paper-2)**: H2 "Outcomes, in their words." Two client quotes labelled "(sample review)", plus three result tiles labelled "(sample result)": "$410,000 settlement, rear-end collision", "Full custody retained, relocation case", "$125,000 settlement, retail slip and fall". Small print beneath: "Prior results do not guarantee a similar outcome."
9. **CTA band (navy)**: H2 "Tell us what happened. We will tell you where you stand." Paragraph, primary "Start your free case evaluation", phone link. 
10. **Footer (navy, hairline rule)**: wordmark, office hours, email, phone, "Harborview, by appointment", nav links, "Attorney advertising. Sample site built for portfolio purposes."
11. **Mobile sticky bar** (below 720px): two buttons full width: "Call" (secondary, paper) and "Free evaluation" (brass). Hidden when the form is in view (IntersectionObserver, 20 lines of JS, optional).

### Personal injury / Family law (service pages, same template)

Short hero (H1, lead, primary CTA). Two-column body: left prose with H2s (What we handle, How fees work, What to bring to the first call), right sticky mini intake form (name, phone, submit) on desktop; on mobile the full form sits at the end. FAQ accordion of 6 items per page.

### Our attorneys

H1 "Two partners, one office, one phone number." Two long profiles (portrait 4:5 left, text right, alternate), each with education, admissions, "Outside the office" paragraph.

### Results

Grid of 8 result tiles labelled sample, grouped by practice, with the disclaimer at top and bottom.

### Contact

Full intake form (same fields plus preferred contact time), office details, map placeholder tile, parking note, hours. Success state: "Thank you. Someone from our office will call you within one business day." No phone number in the success message.

## 4. Imagery direction

Register: calm, architectural, local. No gavels, no scales of justice, no handshake stock, no smiling models.

| Section | Shot | Ratio | Search terms (Unsplash / Pexels) |
|---|---|---|---|
| Hero (optional, behind text at 8 percent tint or omitted) | None by default; typographic hero. If used: soft harbour morning fog | 16:9 | "harbor fog morning", "coastal town overcast" |
| Attorneys | Portrait tiles: monogram on pine-tinted paper (CSS) or free-licence portraits of non-identifiable people (from behind, in profile) | 4:5 | "person office window profile" |
| What to expect | Desk with paper and pen, natural light | 3:2 | "desk notebook window light" |
| Service pages | Harbour, quiet street, pine trees in fog | 3:2 | "pine trees fog", "harbor town street" |

All photos one grade: desaturated 20 percent, warm. Hero has no image, so LCP is the H1 text.

## 5. Elementor (free) build mapping

Global settings live in Site Settings so the builder sets them once.

**Global Colors**: Primary = Ink `#1B2733`, Secondary = Pine `#2E4A3E`, Text = Ink `#1B2733`, Accent = Brass `#8A5A24`, plus custom: Navy `#24364A`, Paper `#F8F5EF`, Paper 2 `#EEE8DE`, Line `#D9D2C5`, Mute `#5A6673`, White `#FFFDF9`.

**Global Fonts**: Primary headline = Newsreader 400 (self-host via Elementor > Custom Fonts is Pro only; on free, add `@font-face` in a 10-line child-theme stylesheet and set the family name to "Newsreader" in Site Settings > Typography), Secondary = system sans, Text = system sans 17px / 1.65, Accent = system sans 600.

Layout: Container width 1140, gap 24, mobile breakpoint 767, tablet 1024.

| Section | Elementor widgets (free) |
|---|---|
| Top bar | Container (navy) > Text Editor + Button (link style) |
| Header | Elementor free has no header builder: use the theme's header (Hello Elementor + a block-based or classic menu) or the free "Header Footer & Blocks" plugin. Wordmark = Image (SVG) + Nav Menu via theme |
| Hero | Container 2 columns (58 / 42) > Heading (eyebrow, h2 tag styled small), Heading h1, Text Editor, Button x2, Icon List (3 facts, horizontal) / right: Container (white, line border, radius 8) > Heading h2 + Text Editor + Shortcode (form) |
| Form | Elementor Form is Pro. Use **WPForms Lite** (or Fluent Forms free) embedded via the Shortcode widget, styled with the global tokens through the child stylesheet. Fields: name, phone, email, select, textarea, checkbox; confirmation message inline; email notification to intake address |
| Trust strip | Container (paper-2) > 4 Icon Box widgets (icon left, 20px, pine) |
| Practice areas | Container 2 columns with the column divider styled as a 1px line background > Heading h3 + Icon List (no icons, bullet off) + Text link |
| What to expect | Container 2 columns (60 / 40) > Text Editor x3 (bold run-in) / Testimonial widget styled as pull quote (or Text Editor with italic) |
| Attorneys | Container 2 columns > Image Box widgets (image top, 4:5) |
| Results | Container (paper-2) > 2 Testimonial widgets + 3 Icon Box widgets (no icon, headline = amount) + Text Editor disclaimer |
| CTA band | Container (navy, full width) > Heading + Text + Button + Button (link) |
| Footer | Theme footer or HFB plugin: Container 4 columns |
| Sticky mobile bar | Container with Custom CSS is Pro; use a 12-line child-theme CSS block (`position: fixed; bottom: 0`) on a Container with a custom class, shown only on mobile via Responsive visibility |

Lighthouse notes: Elementor free loads about 200 KB of CSS/JS; keep it under control by disabling Elementor's Google Fonts (self-host Newsreader, two files), Font Awesome icons off (use inline SVG icons via the Icon widget "upload SVG"), no slider widget, no video, no Lottie, lazy load all images below the fold, and keep the hero text-only so LCP is text.

## 6. Responsive behaviour matrix

Breakpoints (mobile first): `sm 600`, `md 720` (sticky bar and top bar switch here), `lg 1024`, `xl 1200` (phone button appears in header). Mockup renders at 390, 768 and 1440 are in `design/home-mockups/harbor-pine-{mobile,tablet,desktop}.png`.

| | Mobile 360 to 390 | Tablet 768 | Small desktop 1024 | Desktop 1280 to 1440 |
|---|---|---|---|---|
| Top bar | Hidden (sticky bottom bar replaces it) | Visible, two items | Visible | Visible |
| Header / nav | Wordmark + phone icon button (44px, tel link) + hamburger. Menu sheet lists 5 links at 56px tall plus the two buttons at the bottom | Wordmark + primary "Free case evaluation" + phone icon + hamburger | Full inline nav (5 links, nowrap) + primary button; phone button hidden under 1200 | Nav + secondary phone button + primary button, 76px tall |
| Sticky mobile bar | Visible: Call (secondary) + Free evaluation (brass), 48px tall, safe-area padding; hides while the intake form is in view | Hidden from 720 up | Hidden | Hidden |
| Gutter / content | 20px, max 1140 | 32px | 40px | 48px, max 1140 |
| Type scale | Display 38, H2 30, H3 21, lead 18, body 16 | Display 46, H2 34 | Display 56, H2 38 | Display 64, H2 42, H3 24, lead 19, body 17 |
| Hero | Stacked: eyebrow, H1, lead, two buttons wrapping to full width, three facts as a vertical list, THEN the intake card full width | Same stack; facts in one horizontal row; form card full width under the copy | Two columns 6/5 with 48px gap; form card beside copy | Two columns 6/5 with 96px gap |
| Intake form | Single column, every input 48px tall and 16px font, submit full width; name and phone stack | Name and phone side by side; rest single | Same as tablet inside the card | Same |
| Trust strip | 2 x 2, 14px | 2 x 2 | 4 across | 4 across |
| Practice areas | Stacked, divider becomes a horizontal rule between the two groups | Two columns with vertical hairline from 720 | Two columns | Two columns, 64px gap |
| What to expect | Stacked, pull quote under the text | Stacked (quote under text) | Two columns 7/5 | 7/5 |
| Attorneys | Stacked profiles, 120px portrait left of text | Two profiles across, 120px portraits | Two across, 120px portraits | Two across, 160px portraits |
| Portrait crop | 4:5 at all sizes | | | |
| Results | Quotes then tiles, stacked | Stacked | Two columns | Two columns, 64px gap |
| CTA band | Stacked, buttons wrap | Stacked | Text left, buttons right | Same |
| Footer | 2 x 2 | 4 across | 4 across | 2fr 1fr 1fr 1fr |
| Service pages | Prose then full form at the end | Prose then form | Prose 7 cols + sticky mini form 5 cols from 1024 | Same |
| Contact | Single column: form, then office details | Form then details | Two columns 7/5 | 7/5 |
