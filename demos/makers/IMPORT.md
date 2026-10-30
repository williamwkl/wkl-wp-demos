# Google Sheets to WordPress: importing makers

The directory's source of truth can be a shared Google Sheet. The importer lives in `mu-plugins/fernwood-directory.php` (`fw_import_csv()`), exposed as a WP-CLI command. The demo itself was seeded through this exact importer from `data/makers-sample.csv`.

## 1. Sheet layout

First row = column names (order does not matter, extra columns are ignored):

| Column | Required | Example | Notes |
|---|---|---|---|
| business_name | yes | Coldwater Clay | Match key: re-imports update the listing with this exact name |
| craft | yes | Ceramics | Category term, created if new |
| town | yes | Coldwater | Town term, created if new |
| maker_name | | Theo Marchetti | |
| price_band | | 2 | 1 = under $25, 2 = $25 to $75, 3 = $75 to $200, 4 = $200 and up |
| open_studio | | yes | yes / no |
| description | | Wheel-thrown stoneware... | Becomes the listing text and excerpt |
| materials, lead_time, where_to_buy | | | Shown on the listing page |
| website | | https://example.com | |
| image | | wheel | Optional key; the demo seed maps keys to media. A live site would use an image URL column |

## 2. Publish the sheet as CSV

Google Sheets: File > Share > Publish to web > choose the tab > Comma-separated values (.csv) > Publish. Copy the URL (it ends in `output=csv`). Only publish a sheet that contains public listing data.

## 3. Run the import

```bash
# from the WordPress root (WP-CLI)
wp fernwood import "https://docs.google.com/spreadsheets/d/e/<id>/pub?gid=0&single=true&output=csv"
# or a local file
wp fernwood import wp-content/demo-data/makers-sample.csv
```

Output: `Success: Created 0, updated 36, skipped 0.` plus a warning per bad row. Behaviour verified on the demo:

- First run on the sample: created 36. Second run: created 0, updated 36 (idempotent).
- Missing required column: nothing imported, error "Missing required column: town".
- Row with an empty business_name: skipped with "Row 2: empty business_name".
- Unreachable sheet URL: nothing imported, "Could not download the sheet: HTTP 404".

After every import (and every edit in wp-admin) the static search index `wp-content/uploads/fernwood/makers.json` is rewritten, so the browser search and filters stay in sync.

## 4. Scheduling (optional)

On a live host, run the same command nightly from cron, for example `0 3 * * * cd /var/www/site && wp fernwood import "<sheet url>"`. Rows deleted from the sheet are not deleted from WordPress (safer by default); unpublish those listings by hand or add a `status` column.
