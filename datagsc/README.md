# Search and analytics data

Raw exports. Nothing here is edited — these are the evidence the SEO decisions
were made from. Folder names are Google's own download names, which say nothing
useful, so this table is the key.

## Google Search Console

| Folder | What it is | Date range |
|---|---|---|
| `https___synergi.ae_-Performance-on-Search-2026-09-01/` | Whole site — the first pull | Last 12 months, to 29 Aug 2026 |
| `https___synergi.ae_-Performance-on-Search-2026-09-07/` | **Homepage only** (`https://synergi.ae/`) | Last 16 months |
| `https___synergi.ae_-Performance-on-Search-2026-09-07 (1)/` | `/procurement-services-uae/` | Last 16 months |
| `https___synergi.ae_-Performance-on-Search-2026-09-07 (2)/` | `/procurement/` | Last 16 months |
| `https___synergi.ae_-Performance-on-Search-2026-09-07 (3)/` | `/our-services/procurement/` | Last 16 months |
| `https___synergi.ae_-Performance-on-Search-2026-09-07 (4)/` | Whole site | Last 3 months |
| `https___synergi.ae_-Coverage-2026-09-07/` | Indexing → Pages report | As at 4 Sep 2026 |

Each performance folder holds the same seven files: `Queries.csv`, `Pages.csv`,
`Countries.csv`, `Devices.csv`, `Chart.csv` (daily), `Search appearance.csv` and
`Filters.csv`. **`Filters.csv` is the one to open first** — it states which page
and date range that folder covers.

All CSVs are CRLF-encoded. Strip `\r` before comparing values in a script, or
trailing-slash matching will fail silently.

## Google Analytics 4

| File | What it is | Date range |
|---|---|---|
| `Landing_page_Landing_page.csv` | Sessions by landing page | 1 Jan – 7 Sep 2026 |

Note: this is GA4's "this year" default, not the 12 months requested, so it does
not line up with the GSC windows. Fine for comparing *which pages* appear, not
for comparing absolute numbers.

Every row shows `Key events = 0`. Form submissions are not tracked as
conversions — that is a finding, not a missing export.

## Screenshots

`screenshots/` holds the confirmation that `/synergi-uae-2-2/` and
`/bpo-services-in-saudi-arabia-ksa-riyadh/` returned **no data** over 16 months.
That negative result is why the "the old pages hold the history" assumption was
dropped.

## Still to pull

- Query list for `/our-approach/` (16 months, page-filtered) — decides whether
  `/about-us/` is the right redirect target for a page averaging position 5.01
- URL lists behind "Crawled – currently not indexed" (9) and "Discovered –
  currently not indexed" (9) in the Coverage report. The summary export carries
  counts only, and those 18 URLs are the clearest quality signal Google gives.

## What was concluded from all this

See `../seo-action-list.md`.
