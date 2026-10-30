# Saltline Kayak Tours: online booking design spec

Mockup: `design/saltline-booking/mockup.html` (one page, seven states switched by URL hash: `#tour` default, `#date`, `#time`, `#party`, `#details`, `#confirm`, `#done`; calendar, slot pick, party counter with capacity and the running summary work in the mockup). Renders: `mockup-{mobile,tablet,desktop}.png` (date step at 390 / 768 / 1440) and `mockup-done-desktop.png` (confirmation with the email preview).

## 1. What changes for Saltline

Today the Contact page has a booking *request* form (name, email, tour select, preferred date, group size). This adds real booking: a `/book/` page with a six-step flow that shows seats left per departure and holds seats, plus a confirmation email. Every "Book a tour" and "Book this tour" button on the site points at `/book/` (tour pages pre-select their tour with `?tour=sea-cave-explorer`). The Contact form stays for questions and private groups.

Tokens, type, buttons, focus ring and copy rules are the existing Saltline ones (`design/saltline/DESIGN.md`, `demos/saltline/theme/theme.json`): sand page, warm-white panel, sea for selected and progress states, coral only on the primary action, Fraunces for headings, system sans for everything else. Nothing new is introduced except two small availability colours borrowed from the Makers ochre: `#B4700F` dot (non-text, 3.45 on sand, above the 3:1 non-text minimum) and `#8F5607` text for "2 left" (5.19 on sand, 4.92 on the `#F6E7CC` warning pill, both AA).

Other ratios used here: sea on mist 7.22 (pill text), ink on mist 11.99 (note text), mute on warm white 6.03, ink on warm white 14.83, sea on warm white 8.93, white on coral 5.07 (primary label), white on sea 8.93 (booked stamp). Sold-out and past days are mute at 55 percent opacity on no background; they are not interactive (rendered as `<span>`, not links), so contrast rules for controls do not apply, and each carries "sold out" / "past" in its accessible name.

## 2. Flow

Layout: page head (eyebrow, H1 "Book your morning on the water.", lead), then a 7/12 panel (stepper + current step) and a 5/12 sticky summary card "Your booking" that fills in as steps complete (check icons, total, policy, contact). On phones the summary sits below the panel.

Stepper: six segments with a 4 px bar each (sand-2, sea when done or current), `aria-current="step"` on the current one; under 600 px only the current label is shown, the bars still show progress.

| Step | Content | Rules and states |
|---|---|---|
| 1 Tour | Three selectable rows: 4:3 thumbnail, name, duration, level, season, price per person. Footer link for groups of 9+ and private tours | Pre-selected when arriving from a tour page. Rows are links to the date step (`aria-pressed` not needed because selection advances) |
| 2 Date | Month calendar (table, `role="grid"`, Mon to Sun, `<abbr>` weekday headers), each open day a 44 px+ cell with the day number, a sea or ochre dot and "n left"; sold out days struck through with "Full"; past days dimmed; selected day coral outline with `aria-current="date"`. Month arrows 44 px with full accessible names ("Previous month, June 2027"). Legend below. "Continue with July 17" stays `aria-disabled` until a day is picked | Sample availability is a fixed table (July 2027, "today" is July 5). Season rules: Sunrise daily May to Oct, Sea Cave June to Sept on low-tide days, Night Glow July to Sept on dark-moon nights; days outside a tour's season render as "no departure" |
| 3 Time | Departure cards for the chosen day: time, one-line note, pill with seats left (sea pill 4+, ochre pill 3 or fewer, grey "Sold out" and inert). Tide note | Capacity 8 per departure. Selected card sea outline |
| 4 Party | Two counters (Adults 13+, Kids 8 to 12) with 44 px minus / plus buttons and live `output`s; capacity bar "n of 5 remaining seats on this departure"; plus buttons disable at the remaining count; kids cannot exceed adults (one adult per kid in a double); when the group fills the departure a mist note offers the private-group route | Price per person is the tour price; total updates in the summary |
| 5 Details | Name, mobile, email (confirmation and weather updates), experience select, optional notes textarea, required consent checkbox (swim, 8+, policy link), optional season-opening email checkbox | 48 px fields, 16 px text, native validation, one coral focus ring |
| 6 Confirm | Review table (tour, departure, meet time and place, paddlers, lead paddler, total), payment choice as radio cards: "Pay at the boathouse" (default, seats held now) and "Pay by card now" (labelled as live-only), policy line, "Hold my seats" | No deposit in the demo |
| Done | Sea stamp, "See you at first light, Priya.", booking number SL-2027-0318, "(demo: no email is actually sent)", Add to calendar + Book another tour, then the **confirmation email preview**: From / To / Subject strip, Saltline wordmark, headline, details card (tour, when, arrive by, where, paddlers, due at check-in, booking), what to bring, weather line, coral "Change or cancel this booking" button, footer with phone and policy | The summary card flips "Details" to done with the lead paddler's name |

Copy: no em dashes, never "AI", fictional lead paddler **Priya Raman**, `priya@example.com`, `(555) 010-0142`; Saltline contact `hello@saltlinekayak.example.com`, `(555) 010-0100`.

## 3. Plugin recommendation

Checked on wordpress.org on 2026-10-30 (listing pages only, nothing installed yet):

| Plugin | Free tier fits? | Notes |
|---|---|---|
| **Events Manager** (free) | **Yes, recommended** | 60,000+ installs, 7.4.12 updated 2026-10-26, tested up to WP 7.1.4, 4.2 stars (551). Free: recurring events, bookings with spaces per event, guest booking, approve or reject, attendee list and CSV, confirmation emails, four blocks (calendar, list, locations, details) plus shortcodes and template tags. Pro (paid) adds payments, coupons, waitlists, reminders. Tours map onto it directly: each tour is a recurring event series, each departure an event with 8 spaces, party size is the booking's spaces count, pay-at-boathouse is the free offline flow. Two reviews from mid-2026 report block-editor regressions after 7.3.x; pin the tested version in the blueprint. Its default calendar and booking form CSS is heavy and dated, so the child theme must override it (see build notes) |
| MotoPress Appointment Booking Lite | Functionally the best (capacity per slot, number of people with price scaling, confirmation and reminder emails, pay on site, all free) | But 2,000+ installs, tested up to WP 6.9.11 only, 21 reviews: too small a maintenance signal to recommend to a client. Runner-up if Events Manager's block regressions persist |
| Simply Schedule Appointments (free) | No | Accessible, 50,000+ installs, 5 stars, updated 2026-10-13, but group or capacity bookings are a paid feature; the free tier is one person per slot |
| Amelia Lite | No | 90,000+ installs, WCAG work in the changelog, but group booking is premium and the free events module is limited; capacity rules sit behind the paid tier |
| BookingPress Lite | No | Closed on wordpress.org since 2025-02-01 (guideline violation); not installable from the directory |
| WooCommerce Bookings | No | Paid, excluded by the brief |

Requirements the pick must keep meeting: free, maintained in the last 90 days, tested on the current WordPress, bookings with capacity and party size, accessible booking form (labels, keyboard, visible focus), and light enough to keep the page at Perf 90+. Re-check the listing before the build; if Events Manager has gone more than 90 days without an update, switch to MotoPress Lite.

## 4. WordPress build mapping (Twenty Twenty-Five child, Events Manager)

- **Data**: Event Categories = tours (3). One recurring event per tour and season (for example Sunrise, daily, May 1 to Oct 31, 6:30 am and 7:30 am as two recurrences; Sea Cave and Night Glow as hand-entered dates, since they are tide and moon dependent). Spaces = 8 on every event. Location = "Boathouse, Marrow Cove public launch". Price via the free ticket with a price field (shown, collected offline).
- **Page `/book/`**: Group (7/5 Columns) containing the Events Manager Calendar block (month view, filtered by category from `?tour=`), the Events List block for the chosen day (slot cards via a custom event list template), and the booking form (`[event_booking_form]` in the event template). The six-step feel comes from the child theme's `book.js` (about 3 KB): it reads Events Manager's rendered markup and shows one section at a time, keeps the summary card in sync, and adds the stepper; without JS the page degrades to calendar, list and form stacked, all still working.
- **Templates**: child theme overrides `templates/calendar-full.php` (day cells with "n left" from `$EM_Event->get_bookings()->get_available_spaces()`), `templates/forms/bookingform.php` (adults / kids as two ticket types, counters instead of selects), and the booking email template (plain HTML matching the preview).
- **Styling**: dequeue `events-manager.css` and `events-manager.min.js`'s jQuery UI datepicker (not used: the calendar block renders server-side); ship one `booking.css` (about 6 KB) using the theme.json tokens. Keep jQuery only on `/book/` if the booking form's AJAX submit needs it; everywhere else Saltline stays jQuery-free.
- **Emails**: Events Manager sends the confirmation on booking; subject "You are booked: {tour}, {date}, {time}". In Playground the mail goes to the mail log, which is fine for the demo.
- **Blueprint additions**: install `events-manager.7.4.12.zip` pinned, set `dbem_bookings_approval = 0` (instant confirmation), `dbem_bookings_double = 0`, seed the three event series and the sample bookings that produce the "n left" pattern in the mockup.

## 5. Static copy vs live Playground

| Piece | Static export (GitHub Pages) | Live Playground |
|---|---|---|
| Six-step flow, calendar, slots, counters, summary | Works as an interactive demo over a fixed sample availability table (`book-data.json`, July 2027), the same 3 KB script | Works over real Events Manager availability |
| Holding seats | Simulated: "Hold my seats" shows the Done state with a generated booking number; nothing is stored or sent (the exporter strips field names, as with the other demo forms) | Real booking row, spaces decrement, admin sees it under Events > Bookings |
| Confirmation email | Static preview on the Done screen (the design of the real template) | Real email rendered from the template, visible in the mail log |
| No JavaScript | Calendar and list are pre-rendered for the sample month; a note says availability is sample data and links to Contact | Calendar, list and form all work server-side |
| Season switching, other months | Previous and next month show the same sample month with a "sample data" note | Real months |

Mark the page "Availability shown is sample data" in the footer of the static copy (already in the mockup).

## 6. Responsive behaviour matrix

| | Mobile 360 to 390 | Tablet 768 | Small desktop 1024 | Desktop 1280 to 1440 |
|---|---|---|---|---|
| Layout | Single column: head, stepper, panel, summary card | Same | 7/12 panel + 5/12 sticky summary (`top: 24px`) | Same, max 1180 |
| Stepper | Six bars, only the current label visible | Six bars with labels | Same | Same |
| Panel padding | 16 px (calendar bleeds 8 px each side so cells reach 45 px) | 24 px | 32 px | 32 px |
| Calendar cells | 45 x 45 px minimum at 360 to 390, number + dot + "n left" at 11 px | about 90 px square | about 70 px | about 72 px |
| Tour rows | 88 px thumb + text, meta wraps to two lines | One line meta | One line | One line |
| Slots | Stacked | 2 across | 2 across | 2 across |
| Party counters | Label and counter wrap when needed; buttons stay 44 px | Side by side | Side by side | Side by side |
| Details form | Single column, 48 px fields | Name and phone side by side | Same | Same |
| Buttons in the step footer | Back and Continue wrap to two rows if needed, Continue full width when wrapped | One row | One row | One row |
| Email preview | Full width card, details card rows wrap | 600 px wide inside the panel | Same | Same |

Verified in the mockup at 390 / 768 / 1440 on all seven states: zero horizontal overflow; every control 44 px or taller (calendar cells 45 px at 390 after the bleed fix; counter buttons pinned to 44 px).

## 7. Decisions for William

1. **Plugin**: Events Manager (free) is the recommendation on maintenance and install base; MotoPress Appointment Lite has the nicer capacity model but a tiny user base. Say which, or approve Events Manager.
2. **Payment**: the demo holds seats with pay-at-the-boathouse (no deposit). Online card payment needs Events Manager Pro or a WooCommerce bridge, both paid; the mockup labels "Pay by card now" as live-only rather than faking it.
3. **Kids rule**: one adult per kid in a double kayak and kids 8 to 12 at the same price. Change the price or ratio if the fictional outfit should read differently.
4. The booking page replaces the Contact page's "preferred date / group size" fields; the Contact form keeps name, email, tour and message for questions and private groups.
