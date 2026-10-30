# Site handoff: Lantern Hill Roasters store (sample deliverable)

Prepared for: [client name] · Launch date: [date] · Prepared by: [your name]

## What was built
- WooCommerce store on a Twenty Twenty-Five child theme. 12 products in 4 categories; coffees are variable products (12 oz, 2 lb, 5 lb) with a grind choice saved on the order line.
- Build-your-own bundle: any 3 eligible items (product tag "Bundle") get 12 percent off, applied as a cart fee so it shows clearly on the order.
- Coupon FIRSTBAG (10 percent) as an example promotion.

## Plugins and why
| Plugin | Why | Cost |
|---|---|---|
| WooCommerce 11.2.1 | Catalog, cart, checkout, orders | Free |
| Yoast SEO 28.8 | Titles, descriptions, product sitemap | Free |
| Must-use: Lantern Hill storefront | Storefront templates, bundle builder, bundle discount | Custom, included |
No bundle or subscription plugin. Real recurring billing would need the paid WooCommerce Subscriptions extension.

## Payments (before launch)
The demo uses WooCommerce's Cash on delivery gateway renamed "Test order (no payment taken)". For a live store, install the official Stripe or WooPayments extension, connect the client's own account, test in test mode with test cards, then switch to live keys. Keys belong in the payment plugin settings, never in code or the repository.

## Logins (fill in, store in a password manager, never email)
| System | URL | Owner account | Notes |
|---|---|---|---|
| WordPress admin | [site]/wp-admin | [client] | Shop manager role for staff |
| Payment provider | [Stripe or WooPayments] | [client] | Payouts to the client's bank |
| Hosting / Domain | [host] / [registrar] | [client] | |

## How to edit content
- Products: Products > All products. Change prices per size under Variations. Tag an item "Bundle" to make it pickable in the bundle builder.
- Bundle rule: 3 items, 12 percent (constants in the storefront plugin; a settings field can be added).
- Roast date and announcement bar: theme template parts (Appearance > Editor).

## Updates, backups, orders
- Update WooCommerce monthly on a staging copy first; WooCommerce database updates run from the admin notice.
- Daily off-site backups including the database (orders live there), 30-day retention.
- Static copy note: the GitHub Pages version keeps a simulated cart in the visitor's browser only; real orders only exist in the live WordPress site.

## Licenses
WooCommerce and Yoast free (GPL). Instrument Serif: SIL Open Font License. Photos: CC0 / public domain (CREDITS.md).
