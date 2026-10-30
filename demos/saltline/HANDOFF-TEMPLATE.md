# Site handoff: Saltline Kayak Tours (sample deliverable)

Prepared for: [client name] · Launch date: [date] · Prepared by: [your name]

## What was built
- WordPress block theme site: child theme "Saltline" on Twenty Twenty-Five. Pages: Home, Tours, About, FAQ, Contact.
- Colors, fonts and spacing live in `theme.json` (Appearance > Editor > Styles), so changes apply site-wide.

## Plugins and why
| Plugin | Why | Cost / license |
|---|---|---|
| Yoast SEO | Titles, meta descriptions, XML sitemap, social previews | Free (GPL) |
| Contact Form 7 | Booking request form (Contact page) | Free (GPL) |
| Must-use: Saltline structured data | LocalBusiness and TouristTrip schema for search results | Custom, included |
Kept deliberately small: no page builder, no slider, no extra cache plugin if the host provides page caching.

## Logins (fill in, store in a password manager, never email)
| System | URL | Owner account | Notes |
|---|---|---|---|
| WordPress admin | [site]/wp-admin | [client email] | Admin for the owner, Editor role for staff |
| Hosting | [host] | [client] | Client owns the account |
| Domain registrar | [registrar] | [client] | Auto-renew on |
| Google Search Console | | [client] | Sitemap submitted: /sitemap_index.xml |
Temporary developer accounts are removed at handoff.

## How to edit content
- Tours, prices, FAQ answers: Pages > edit the page, click the text and type. Prices appear on Home and Tours (update both).
- Photos: click an image > Replace. Use landscape photos at least 1200px wide; WordPress creates the smaller sizes.
- Booking form fields and the email recipient: Contact > Contact Forms > Booking request > Mail tab.
- Note: the demo includes `assets/js/demo-form.js`, which stops real submissions, and turns off Contact Form 7's own script (`wpcf7_load_js`) because the static copy has no server. Before launch, delete both lines in functions.php and the `wpcf7_form_action_url` filter in `mu-plugins/demo-static.php` so CF7 submits and emails normally.

## Updates and backups
- Updates: WordPress core minor updates automatic; plugin and theme updates monthly after a staging check.
- Backups: daily off-site backups by the host (or UpdraftPlus to cloud storage), 30-day retention. Test a restore once a quarter.
- Security: strong passwords plus 2FA for admins, least-privilege roles, keep plugin count low.

## Licenses
All plugins are free GPL versions; no renewals. Font Fraunces: SIL Open Font License. Photos: CC0 / public domain (see CREDITS.md).

## Support
[Support terms: response time, included hours, hourly rate after that.]
