# Design critique 1 (2026-10-30, design-critic)
Inspected: home mockup vs build side by side at 1440 + 390 for all 3; inner pages at 1440 (saltline tours/about/faq/contact; harbor-pine attorneys/results/family-law/contact; makers directory/detail/categories/town); makers mobile cards. From screens/ only (no live serve).

## Saltline: NEEDS FIXES (light)
1. Home, 1440 + 768: hero photo no longer overhangs the conditions strip (spec section 3 "the one distinctive move": photo offset 48px down into the strip). Build ends the photo at the strip edge, so the hero reads as a stock 2-column template. Fix: negative bottom margin / translateY 48px on the hero image column, strip gets z-index below. Verify: photo bottom sits ~48px inside the sand strip, like the mockup.
2. Home, all sizes: the same kayaker (green vest, white boat) is the hero AND the "Never kayaked" photo. Repetition in the first two photos kills believability. Fix: swap the objection image for a beach-lesson / paddles-on-sand shot (spec: "kayak paddles beach gear").
3. Contact, 1440: "Map placeholder" box in the sidebar reads unfinished. Fix: static map image (OSM tile screenshot) or remove the box and keep the address text.
P3: section gaps ~30% larger than mockup (tours grid and objection section float); footer "Photos: public domain and CC0, see credits in the repo" is developer copy (move to /credits or drop); trust-strip lost the bold lead word at 390; Night Glow card shows a sunset, not bioluminescence.

## Harbor & Pine: NEEDS FIXES
1. Home, 390 + 1440: attorney monogram tiles lost their tinted card background; "EM"/"DO" float on a blurry smudge. Looks broken on the most trust-critical section. The Our Attorneys page renders the tile correctly, so it is a home-only style miss (same component, missing tile class/background). Fix: apply the attorneys-page tile styles. Verify: 112px tinted tile with monogram bottom-left at both sizes.
2. Home, 1440 + 390: added stock desk photo (pencils, scissors, notebook) above the partner quote; not in the mockup, irrelevant to law, and it breaks the quote's typographic weight. Fix: remove it, restore the plain pull quote with the left rule.
3. Home, practice areas, 1440: "How we handle injury/family cases" lost link styling and sits inside the list rhythm, so it reads as a 5th practice item. Fix: underline + spacing above as in mockup.
P3: hero form now taller than hero copy at 1440 (demo-notice box + bigger fields), leaving dead space under the trust checks; contact sidebar "Map placeholder"; quotes use straight quotes ("), mockup uses curly.

## Fernwood Makers: NEEDS FIXES
1. Photo repetition, everywhere: the B&W pottery-hands photo is Coldwater Clay, Greystone Glassworks, Maker of the Month, the detail hero AND all 3 detail thumbnails; striped mug repeats twice in "More in Ceramics"; yarn, workshop and paper-bag photos repeat on /makers and the Coldwater town page (Coldwater Print Shed uses Greystone Press's bag). A client spots this in 5 seconds. Fix: one unique photo per listing (36) and 3 distinct gallery shots on the detail page; minimum, every image unique per viewport.
2. Categories page, 1440: just a single text row of 6 categories then the footer, ~60% of the viewport empty. Reads as unfinished. Fix: category tiles with a photo + count (2-col mobile, 3-col desktop), or merge into the directory.
3. Town page (Coldwater), 1440: Coldwater checkbox in the sidebar is not checked although results are filtered to Coldwater, so filter state lies. Fix: pre-check the town (and categories page equivalents). Verify: checkbox reflects URL context.
P3: home counts dropped to "36 makers" vs mockup 214 (fine, but "9 towns" with 4 each looks thin; consider not showing counts of 4 everywhere); detail map box is a beige placeholder; "coldwaterclay.example.com (placeholder)" text visible on detail; "Sample photos for the demo." caption under gallery.
