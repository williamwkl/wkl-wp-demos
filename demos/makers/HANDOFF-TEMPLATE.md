# Site handoff: Fernwood Makers directory (sample deliverable)

Prepared for: [client name] · Launch date: [date] · Prepared by: [your name]

## What was built
- Block theme site (child of Twenty Twenty-Five) with a "Maker" content type, Category and Town taxonomies, and listing fields (maker name, price band, open studio, materials, lead time, where to buy, website).
- Directory pages: /makers/ (search, filters, sort, pagination), /craft/<name>/, /town/<name>/, and a page per maker.
- Search and filters run in the browser from a generated index file, so they work on a static copy too. Without JavaScript all listings still show and the filter form still submits.

## Plugins and why
| Plugin | Why | Cost / license |
|---|---|---|
| Yoast SEO | Titles, descriptions, sitemap for every listing | Free (GPL) |
| Must-use: Fernwood directory | Data model, CSV importer, directory rendering, JSON index | Custom, included |
No fields plugin and no filter plugin: fewer updates, faster pages.

## Logins (fill in, store in a password manager, never email)
| System | URL | Owner account | Notes |
|---|---|---|---|
| WordPress admin | [site]/wp-admin | [client email] | Editors can add and edit makers |
| Google Sheet (master list) | [sheet URL] | [client] | Published as CSV for imports |
| Hosting / Domain | [host] / [registrar] | [client] | |

## How to edit content
- One maker: Makers > edit > "Listing details" box on the right; set Category and Town in the sidebar; set a featured image (landscape, 1200px+).
- Many makers: update the Google Sheet and run the import (IMPORT.md). Rows are matched by business name.
- The search index refreshes automatically after any save or import.

## Updates and backups
Monthly plugin/core updates after a staging check; daily off-site backups, 30-day retention; export the Makers list (Tools > Export) before large imports.

## Licenses
Free GPL software only. Bricolage Grotesque font: SIL Open Font License. Photos: CC0 (CREDITS.md).

## Support
[Support terms.]
