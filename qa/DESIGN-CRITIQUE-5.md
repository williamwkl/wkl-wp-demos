# Design critique 5 (2026-10-30, HEAD 9cfc4c1)
Inspected at 390 by serving dist/ on :9489 and driving the flows:
- Store: empty cart, checkout with an empty cart, product page, add to cart, cart, checkout, test order and confirmation, home.
- Health-check web page and page 2 of the regenerated PDF.
- Saltline /book/ step 2 (the page as it loads with a tour preselected).
Not inspected: Saltline done screen (not captured this round), store at 1440.

## Lantern Hill store: GREEN
Verified fixed:
- Thumbnails show in the cart and in the checkout and confirmation summaries.
- An empty cart shows only "Your cart is empty. Browse the coffee". Checkout with an empty cart shows the same.
- The confirmation page clears the cart badge and shows order LH-24495.
- The Cart / Checkout / Confirmation step row now appears on the cart page too. The cart has a bordered quantity stepper. The coupon placeholder is now "Code".
- Packshots: flat illustrated kraft bags, one per coffee with its own colour and label, on tinted backgrounds. They hold together as a believable brand system and look better than the identical bean piles did. They will not pass for photos, which is acceptable for a demo.
- The product gallery shows only Morning Fog's own images (its bag plus its own beans photo).
P3:
- The label text on the bags is unreadable below about 120px.
- About 80px of dead space between the price and "Size" on the product page at 390.

## Health Check web + PDF: GREEN
- Ring colours now follow one scale: Speed 81 and LCP 4.8 s amber, Security 1/8 and Maintenance 2/6 red, SEO and Accessibility 100 green.
- The fixes table stacks into one card per fix at 390.
P3: the stacked cards print raw labels ("#: 1", "Fix:", "Effort:"). Make the number a heading, drop the colons, and set the field names in the small-caps label style used elsewhere.

## Saltline booking: NEEDS FIXES (1 item)
- The stepper at 390 now shows only the current step ("2. Date"). Fixed.
1. /book/?tour=..., 390, on load: the step heading has a 2px terracotta focus rectangle around it. JS moves focus to the heading, so the ring paints with no keyboard use and reads as a validation error on the very first screen. Fix: keep the focus move for screen readers, but style `[tabindex="-1"]:focus:not(:focus-visible){outline:none}`. Verify: no box on load; keyboard Tab still shows rings on the controls.
