# Design critique 4 (2026-10-30, HEAD 8455126)
Inspected:
- **Lantern Hill store:** screens/store at 390/768/1440, compared with the store mockups.
- **Live flow, my own captures at 390:** add to cart, cart, checkout and order, with dist/ served on :9488.
- **Saltline /book/:** all 6 steps plus the error and done states, at 390 and 1440, compared with the saltline-booking mockups. Also Saltline home, tours and contact.
- **Health Check:** PDF pages 1-9 rendered to PNG, and dist/health-check at 390, 768 and 1440.
Note: full-page captures show the sticky header mid-page. That is a capture artifact; checked in the viewport, the header sits correctly under the announcement bar.

## Lantern Hill store: NEEDS FIXES
1. **Demo cart and checkout, all sizes:** the product thumbnail is a broken image (browser broken-image glyph) in the cart line and in the checkout order summary. This is the money path, and a broken image is the clearest "unfinished" signal there is. Fix: write the product image URL into the cart item, the same path as the product card image. Verify: add Morning Fog, then the cart and checkout both show its photo.
2. **Cart, checkout and confirmation, empty or finished states:** with an empty cart, checkout still offers "Place test order, $0.00" and the Check out button is active. After the confirmation page the cart badge still shows 1. Fix:
   - Empty cart: hide the summary and Check out, and keep only "Your cart is empty. Browse the coffee".
   - Checkout with an empty cart: redirect to the cart.
   - Confirmation: clear the cart.
3. **Coffee imagery, home, shop, bundle and product, all sizes:** all 6 coffees are close-up bean piles, so the cards look the same and none shows the bag that the mockup (and the copy, "roast date stamped on the back") centres on. On the product page the gallery thumbnails are other coffees' photos (Summit's and Ashgrove's beans under Morning Fog). Fix: one bag or packshot image per coffee (even one bag shot recoloured per label). Each product gallery should show only its own images.
P3:
- The cart page lacks the Cart / Checkout / Confirmation step row that checkout has.
- The coupon placeholder "FIRSTBAG" reads as a real value; use "Code".

## Saltline booking (+ rest of site): GREEN
- The flow matches the mockup step for step: calendar seat counts, full and sold-out days, the low-seat badges, the live "Your booking" summary, required-field error, review, and the done state with the email preview.
- Home, tours and contact still match round 3.
P3:
- At 390 the step labels truncate ("5. Det...", "6. Co..."); show only the current step's label.
- On the done screen at 390 the "Your booking" summary repeats beneath the email card; hide it on done.
- Contact form: the field labelled "Tour" has the placeholder "Choose a topic"; rename the label to "Topic".

## Health Check PDF + web: GREEN (1 fix advised)
- The PDF reads as a premium consultant deliverable: cover, one-page summary with a score row, ranked fixes table, and evidence blocks. Typography and rhythm are consistent across 9 pages.
1. **PDF page 2 and web, score rings:** Speed 81 is drawn in red, while Security 1/8 (worse) and Maintenance 2/6 are amber. Colour severity is inverted against the numbers, and an Upwork buyer will spot it. Fix: map the ring colour to one scale (Lighthouse-style 0-49 red, 50-89 amber, 90-100 green; ratios by percentage). Verify: Speed amber, Security red.
P3:
- Web at 390: the 5-column "Top five fixes" table wraps word by word into a very tall table. Stack each row as a card below 600px.
