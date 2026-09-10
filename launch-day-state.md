# Launch day — state as of 10 September 2026, 09:30

Written during the pre-launch audit, because three documents in this folder now
disagree with each other and with both live sites. **Where this file and any
other file disagree, this one is newer.** It is not a replacement for
`migration-plan.md` — that still holds the reasoning. This holds what is true
today and what is left.

The step-by-step runbook lives at the artifact published today ("Synergi
Cutover Runbook"); this file is the written record behind it.

---

## Decisions taken today

| | Decision |
|---|---|
| **`/connect/`** | Stays **published**, gets a Yoast **noindex**. Not drafted — a printed QR code points at it and a draft would 404 the people holding it. It renders because Elementor stays active (below). Rebuild on a template in week 1. |
| **Elementor** | **Stays active through launch.** It renders from `_elementor_data` regardless of theme, and `page.php` calls `the_content()`, so `/connect/` and the ICXI page keep working. This was already the rollback plan (CLAUDE.md §2.9); it is now a deliberate dependency. **Do not deactivate it until `/connect/` is rebuilt.** |
| **Homepage `<title>`** | **Keep production's**: `BPO Services in UAE & the Gulf \| Synergi Business Solutions`. The new title leads with the brand; that page is 66% of clicks and ranks 2.56 for "bpo services in uae". Must be set on staging **before** the export or it is not in the payload. |
| **Launch path** | Option C, formally. Theme from Git + scripted content transfer. |

---

## What was fixed in code today

Three commits, working tree clean, `synergi-theme.zip` rebuilt (477 KB, 150
entries, includes `parts/post-categories.php`).

- **`4b628bd`** — the transfer no longer carries the staging noindex; carries the
  seven repaired blog posts; `sslverify` on; ends with three assertions; adds
  `syn_transfer_cleanup()`.
- **`6992c15`** — mojibake repaired in `functions.php` and `footer.css`.
- **`7d05a13`** — the blog category row (`parts/post-categories.php`).

---

## Corrections to the existing documents

Verified over HTTP against both sites on 10 Sep. Each of these is recorded as a
problem somewhere in this folder and is **not** one.

| Recorded as | Actually |
|---|---|
| `migration-plan.md`: "Production Novamira is down (404). Blocks everything." | **Connected.** Both `novamira-synergi-ae` and `novamira-staging-synergi` respond. |
| `migration-plan.md`: "22 pages have no SEO title or description." | **Zero missing.** All 37 payload objects carry both. |
| P12 — old theme demo content live and crawlable | **Gone.** `/portfolio/*`, `/demo/`, `/pricing/` all 404. |
| P13 — an Arabic URL is publicly reachable | `/ar/our-services/` → 301 → `/our-services/`. |
| §1.2 — three competing procurement URLs | **Already consolidated on production.** `/procurement-services-uae/` and `/procurement/` both 301 to `/our-services/procurement/`. |
| §2.3 — `synergi-uae-2-2` points at the wrong page | Already 301 → `/`. The payload's rule agrees. |
| `seo-action-list.md` PHASE 0 checkboxes | **Stale.** P1 and P3 are closed — `problems-to-solve.md` is the source of truth, not the action list. |
| P5/P6 — "the new site emits no analytics at all" | Site Kit loads `GT-TXBFKV55` → `G-EX4ZJYVVPG` on production. It is theme-independent and **survives the theme switch untouched**. GTM is a post-launch improvement, not a launch blocker. |

---

## Still open at the time of writing

1. **Delete `wp-content/uploads/syn-transfer/payload.json` on staging.** Verified
   anonymously downloadable on 10 Sep — 162 KB holding every page's copy, all
   Yoast metadata, the 61-rule redirect table, nine site records and the menu.
   It has been public since the 9 Sep dry run. Regenerate it in the window,
   delete it again after. `syn_transfer_cleanup()` does it.
2. **Read the dry run.** The importer matches by slug with
   `ORDER BY ID ASC LIMIT 1`, and production has held pages at both
   `/procurement/` and `/our-services/procurement/` — the same slug at two
   depths. If both rows still exist it targets the older one, reparents it, and
   WordPress renames the loser `procurement-2`. Check every
   `WOULD UPDATE (id …)` resolves to the page you expect.
3. **The CRM does not travel.** `btcbi_flow` is the plugin's own table. The
   flow repoint onto WPForms **and the three transients** must be repeated on
   production by hand. Skip the transients and the flow looks perfect and
   silently never fires — that fault cost a day on staging.
4. **WPForms form 7560 must exist on production.** The contact page carries a
   hard-coded `[wpforms id="7560"]` and the form itself is not in the payload.

---

## The ordering trap

The import sets `page_on_front`. That happens **while the old theme is still
active** — so between the import finishing and the theme switch, the live site
serves `/homepage-rebuild/` through Theratio and Elementor.

Import and theme activation are **one action**. Have Appearance → Themes open
on the Synergi theme before running the live import.

---

## The security findings

Full detail in the audit; the ones that outlive launch day:

- **Novamira is active on production**, exposing 33 REST routes including
  `/novamira/v1/admin-access`, which mints a password-free admin login and
  therefore routes around WPS Hide Login and Wordfence 2FA. Every endpoint
  probed unauthenticated returned 401/403, so it is a **credentialed** surface,
  not an open one. `synergi-build-plan.md` §11 has said to move it to staging
  since August. Still to do.
- `readme.html` and `license.txt` are served (leak the WP version). Delete.
- No HSTS, no `X-Content-Type-Options`, no `X-Frame-Options`, no
  `Referrer-Policy`. Four lines in Cloudflare.
- Slider Revolution **7.1.2** running; 7.1.8 available.
- **The theme itself audited clean** against CLAUDE.md §5: no `eval`, no
  unescaped output, nonce + capability on every save, one prepared `$wpdb`
  query, ABSPATH guard in all 80 files, zero hard-coded colours in CSS, zero
  physical directions, every `console.log` gated behind `synDebug`.

---

## The numbers this launch is judged against

From the Search Console exports in `datagsc/`, three months to 7 Sep 2026:

- **314 clicks from 35,645 impressions** — 0.88% CTR.
- Homepage: **207 clicks**, 66% of the total.
- **79%** of named-query clicks are people typing "synergi".
- Non-brand named queries: **20 clicks from 25,667 impressions — 0.078%**.
- Zero-click page-one queries worth attacking first, all procurement:
  *sourcing and procurement providers* (1,156 impressions, pos 8.3),
  *procurement operations services* (868, pos 5.0),
  *sourcing services providers* (754, pos 8.6).

There is very little organic traffic to lose, which makes the migration far
less risky than it feels. The entire real risk sits on the homepage's brand
traffic — which is what the title decision and the noindex guards protect.

Judge the rebuild on **non-brand clicks per month**, not impressions.
