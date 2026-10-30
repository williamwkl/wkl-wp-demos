# Site handoff: Harbor & Pine Law (sample deliverable)

Prepared for: [client name] · Launch date: [date] · Prepared by: [your name]

## What was built
- Elementor (free) pages on a Hello Elementor child theme. Pages: Home, Personal injury, Family law, Our attorneys, Results, Contact, Legal.
- Global colors and fonts: Elementor > Site Settings (Global Colors, Global Fonts). Section styling: child theme stylesheet.
- Performance setup documented in OPTIMIZATION.md (Lighthouse mobile 87 to 98 on the home page).

## Plugins and why
| Plugin | Why | Cost / license |
|---|---|---|
| Elementor (free) | Visual page editing for staff | Free (GPL); Pro not required |
| Fluent Forms (free) | Case evaluation intake form, email notification to intake@ | Free (GPL) |
| Yoast SEO | Titles, meta descriptions, sitemap | Free (GPL) |
| Must-use: structured data | LegalService schema | Custom, included |

## Logins (fill in, store in a password manager, never email)
| System | URL | Owner account | Notes |
|---|---|---|---|
| WordPress admin | [site]/wp-admin | [client email] | Admin for a partner, Editor for staff |
| Hosting / Domain | [host] / [registrar] | [client] | Client owns both |
| Form notifications | Fluent Forms > Settings > Email Notifications | intake@ | Test after launch |

## How to edit content
- Text and buttons: Pages > Edit with Elementor > click any heading or paragraph.
- Practice area lists and FAQs: same editor; FAQs are an HTML widget using simple `<details>` blocks (no extra script).
- Results: every result and review must stay labelled and keep the "Prior results do not guarantee a similar outcome" line (attorney advertising rules vary by state; confirm with the firm).
- Note: the demo's `assets/js/site.js` replaces form submission with a demo message. Remove the submit handler before launch so Fluent Forms sends.
- Performance: the optimized build removes Elementor's frontend JS. If you add sliders, tabs, popups or motion effects, scope that removal (see OPTIMIZATION.md).

## Updates and backups
- Elementor and Fluent Forms update often: apply monthly on a staging copy first, then live.
- Daily off-site backups with 30-day retention; quarterly restore test. 2FA for all admins.

## Licenses
All free GPL plugins; no renewals. Newsreader font: SIL Open Font License. Photos: CC0 / public domain (CREDITS.md). If the firm later buys Elementor Pro, the license should be in the firm's name.

## Support
[Support terms.]
