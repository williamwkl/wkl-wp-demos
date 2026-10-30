# WordPress Health Check report: design spec

Mockup: `design/health-check/mockup.html` (9 letter-size pages; print to PDF with headless Chrome, `printBackground: true`, `preferCSSPageSize: true`). Subject site: the naive Harbor & Pine build (`dist/harbor-pine-naive`), with the optimized build as the "after".

## 1. What this is

A sample client deliverable William attaches to Upwork proposals for fix / speed / security jobs and sells as a fixed-price service ("WordPress Health Check", then Tier 1 to 3 fix packages, then a care plan). It must read as a consultant's report, not a tool export: plain-English findings, every number traceable to a Lighthouse JSON in `reports/harbor-pine/`, and honest "not checked" labels where the static copy cannot prove something.

Length: 9 pages (brief said 6 to 10). Page order:

1. Cover (dark)
2. Executive summary: one-paragraph verdict, six-score dashboard, top 5 fixes ranked by impact against effort
3. Findings 01 and 02 (images; fonts and icon fonts)
4. Finding 03 (unused scripts and styles) + Core Web Vitals per page table
5. Security: 8 checks as a checked / not-checked table + "why it matters to a law firm"
6. SEO, accessibility, maintenance (6 checks)
7. Before / after: LCP bar chart (inline SVG) + home and family-law metric table
8. Prioritized fix plan: three fixed-price tiers + day-by-day order of work
9. Care plan offer, how to say yes, method and scope

## 2. Visual system (neutral, consultant-grade)

Borrows the WKL Labs "Precision Dark" neutrals (`#141417`, `#26262C`, `#9A9AA4`, `#E8E8EC`, `#F5F5F7`) for the cover and one steady blue for charts and tags. No brand colour of the client's site appears in the report: the report is WKL's document about their site.

| Token | Hex | Role | Contrast |
|---|---|---|---|
| `--ink` | `#141417` | body text, cover background, table head rule | on paper 18.6 |
| `--ink-2` | `#26262C` | evidence blocks | |
| `--slate` | `#5B5F6B` | secondary text, running heads, axis labels | on paper 6.1 (AA), on paper-2 5.7 |
| `--line` | `#E4E4EA` | hairlines, bar baseline grid | decorative |
| `--paper` / `--paper-2` | `#FFFFFF` / `#F6F6F8` | page / tinted cards and evidence blocks | |
| `--blue` | `#1F4E8C` | eyebrows, "after" bars, Tier tags | on paper 8.1; white on blue 8.1 |
| `--red` | `#B23A3A` | Fail tag text, threshold line | on red-soft `#F8E8E8` 5.1 (AA) |
| `--amber` | `#8A5E0E` | Needs work / effort M | on amber-soft `#F8EFD9` 5.4 (AA) |
| `--green` | `#2E6B46` | Pass / effort S | on green-soft `#E3F1E8` 5.8 (AA) |

Cover text: `#F5F5F7` on ink 17.5; `#9A9AA4` meta labels on ink 6.5 (AA); `#C9C9D1` lead on ink 11.9.

Type: headings in **Source Serif 4** 600 (one variable woff2, self-hosted in the PDF build; Georgia fallback is acceptable since the PDF is rendered once), body in the system sans stack at 10.5pt / 1.5, evidence in the system mono at 8.5pt. Scale: cover H1 40pt, page H2 19pt, H3 12.5pt, table 9.5pt (`table.dense` 8.6pt on the two long tables), running heads 8.5pt uppercase +0.08em.

Layout: letter, 0.7in margins, each `.page` is a flex column with `gap: 16px` and the footer pushed to the bottom (`margin-top: auto`). Running head (site / section) and running foot (WKL Labs / page x of 9) on every page except the cover. Depth: hairlines only; the only shadow is the on-screen page stack (removed in print). Tags are small uppercase pills; score dashboard uses six SVG rings (stroke-dasharray on a 169.6 circumference) with the value in the centre.

Charts: inline SVG only (no chart library). The LCP chart is two bars per page, grey `#C9C9D1` current and blue after, with a dashed red 2.5 s threshold line and a `<title>` that reads the full data for screen readers. Bar scale 34px per second.

Print CSS: `@page { size: letter; margin: 0 }`, `.page { width: 8.5in; height: 11in; overflow: hidden; break-after: page }`, `print-color-adjust: exact`. Verified: all 9 pages measure exactly 1056px tall in print emulation (no clipping) and the PDF has 9 pages. On screen the page is `max-width: 100%` and the six-score grid drops to 2 columns under 640px, so the HTML also reads on a phone with zero horizontal overflow (checked at 390 / 768 / 1440).

## 3. Copy and data rules

- Every speed number comes from `reports/harbor-pine/naive/*.report.json` and `reports/harbor-pine/optimized/*.report.json` (Lighthouse 12.8.2, mobile preset, 2026-10-30). Where README.md and OPTIMIZATION.md round differently (home optimized LCP 2.1 vs 2.2 s), the report uses the JSON (2.2 s). If the reports are re-run, re-read them before regenerating the PDF.
- Scores on the dashboard: Speed = worst-page Lighthouse Performance (81); Core Web Vitals = worst LCP (4.8 s, 2 of 7 pages fail); SEO and Accessibility = Lighthouse 100 on all pages; Security and Maintenance are check counts, not invented scores.
- Security checks and how each was supported: admin user (README: Playground logs in as admin / password), version exposure (two `<meta name="generator">` tags in the naive export), backups (none in the blueprint), login protection (no plugin), plugins current (blueprint pins Elementor 4.3.5, Fluent Forms 6.2.16, Yoast 28.8), form token (exporter strips the Fluent Forms nonce, so it exists on the live form); XML-RPC / REST users and HTTPS / headers / file editing are "Not checked" because the static copy cannot show them.
- Fixed prices ($350 / $650 / $950, care plan $75 a month) and day counts are an offer, not data. They are placeholders for William to set; see decisions.
- No em dashes, never the word "AI", fictional people and businesses only (Elena Marsh, Daniel Okafor, Harbor & Pine Law), `example.com` emails, `(555) 010-0199` for WKL.

## 4. Build notes

- Source of truth is the HTML. Generate the PDF with Playwright: `page.emulateMedia({media:'print'}); page.pdf({format:'Letter', printBackground:true, preferCSSPageSize:true})`. Attach the PDF plus the 14 Lighthouse HTML reports as the appendix.
- To reuse for a real client: swap the cover meta, the per-page table (7 rows), the two evidence blocks, the security table results and the before / after table. The structure, tags and chart scale stay. Keep the "not checked" rows honest; add rows only when the check was actually run.
- A `scripts/health-check.mjs` that reads the Lighthouse JSON folder and fills the per-page table and chart bars would make this repeatable; not built here.

## 5. Decisions for William

1. Prices and durations of the three tiers and the care plan ($350 / $650 / $950 / $75 a month, 3 / 5 / 8 days) are placeholders at roughly the standard rate; confirm or change.
2. Whether to show the "after" page at all in a proposal attachment (it proves the method, but it also gives the fix list away). Suggested: keep it, the value is the hours, not the list.
3. WKL contact line uses `hello@wkllabs.example.com` and a 555 number per the repo rules; swap for the real ones only in the client-facing PDF, never in the repo.
