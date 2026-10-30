# Design critique 3 (2026-10-30, commit e93c2d6, fresh screens/)
Inspected: makers /towns at 1440 and 390, /credits at 1440, Bramblewood detail at 390, Coldwater town filter (zoomed), home at 1440 (Maker of the Month); saltline home at 768.

## Fernwood Makers: GREEN
Verified:
- /towns is now 9 photo tiles (3 columns at 1440, 2 at 390) with maker counts.
- /credits has the full table.
- The Bramblewood gallery shows jam jars and apples.
- Maker of the Month uses Theo's clay-wheel listing photo.
- The colour grade is more even.
- The Coldwater filter shows a brass check with a lock icon and "Set by this page" beside an "All towns" link. This is clear and legible.
P3:
- The 9th town tile (Ivywick) sits alone in the last row at 390. Acceptable.
- Town tiles reuse listing photos. Acceptable.
- The website line on the detail page breaks mid-word at 390 ("...example.c / om"). Add overflow-wrap:anywhere, or shorten the line.

## Saltline: NEEDS FIXES (1 item)
1. Home, 768, tours grid: the 2-up grid leaves Night Glow alone in the left half of the last row, with an empty right half. It reads as a missing card in the page's only card grid. Fix: show all 3 cards in one row at 768, or let the 3rd card span both columns as a horizontal card (image left, text right). Verify at 768: no empty cell.
