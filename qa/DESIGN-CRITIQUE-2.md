# Design critique 2 (2026-10-30, commit 183e4b2, fresh screens/)
Inspected: home at 390/768/1440 (all 3); saltline contact + credits; harbor home at all sizes; makers directory, detail (coldwater-clay, bramblewood-preserves), categories at 1440 + 390, town, towns, credits.

## Saltline: GREEN
Round 1 items verified: the hero photo now overhangs the conditions strip at 1440, 768 and 390. The "Never kayaked" photo is a new beach/kayaks shot. The map box is gone from contact. The repo-credits line is replaced by a "Photo credits" footer link. Night Glow is now a blue night shot.
P3: the objection photo is the same image as the About safety section (cross-page, acceptable). At 768 the tour cards stack full width and the page gets long; a 2-up grid would tighten it.

## Harbor & Pine: GREEN
Round 1 items verified: the monogram tiles are restored, the desk photo is removed, and the pull quote with its left rule is back. The practice-area links are underlined and separated from the list. The hero is balanced at 1440: the form is about the same height as the copy, and the trust strip now follows directly.
At 390 the sticky "Call / Free evaluation" bar matches spec item 11. It shows mid-page in the screenshot only because the capture is full-page.
P3: no other issues found.

## Fernwood Makers: NEEDS FIXES (1 item)
Round 1 items verified:
- Every listing photo is unique within its viewport.
- The detail gallery has 3 distinct shots.
- Categories is now a 6-tile photo grid (2 columns at 390, 3 at 1440) and works well.
- The town page pre-checks Coldwater.
- The "(placeholder)" text, the gallery caption and the map placeholder are gone.
1. Towns page (/towns), 1440: it has the same empty-page defect categories had, one text row of town names and then the footer. It is linked from the main nav, so a client will land on it. Fix: reuse the categories tile component (town photo + maker count, 2 or 3 columns). Verify: tiles fill the fold at 390 and 1440.
P3:
- The Credits page is a stub, about 3 lines then the footer. Acceptable, but add a top margin above the footer or the list of credits.
- The Bramblewood Preserves gallery shows cookies and cheese, which do not fit a jam maker.
- Maker of the Month (Theo, Coldwater Clay) uses the B&W hands photo, while his card uses the pink clay wheel shot. Use one image for both so the link reads as the same maker.
- Photo grades are mixed across the directory: dark, saturated textile and leather shots next to bright paper and jewelry. A light shared grade would unify it.
