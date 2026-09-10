# Launch day — state as of 10 September 2026, afternoon

Rewritten after the second session, which had Novamira access to both sites and
ran the export, two dry runs and a read-only audit of production. **Where this
file and any other file disagree, this one is newer.** It is not a replacement
for `migration-plan.md` — that still holds the reasoning. This holds what is
true today and what is left.

The step-by-step runbook is `launch-runbook.html` (published as "Synergi
Cutover Runbook"); this file is the written record behind it.

---

## Decisions taken today

| | Decision |
|---|---|
| **`/connect/`** | Stays **published** with a Yoast **noindex** — a printed QR code points at it. **Done on production 10 Sep**, verified live: `<meta name='robots' content='noindex, follow' />`, HTTP 200. |
| **Elementor** | **Reversed in the afternoon.** The whole Elementor stack is **deactivated at go-live** — the theme replaces it. Deactivated, never deleted (CLAUDE.md §2.9); `tools/launch-after-activation.php` saves the list for the rollback. What that costs is under "Pages that lose Elementor" below. |
| **Homepage `<title>`** | Keep production's: `BPO Services in UAE & the Gulf \| Synergi Business Solutions`. **Set on staging `homepage-rebuild` 10 Sep** (was "Synergi \| BPO & Shared Services in the UAE, Gulf & Beyond"); Yoast renders it, and it is in the payload. |
| **Repaired blog posts** | **Carried properly.** Their bodies now travel and render (Elementor is off). The ICXI announcement is a page on production and a post on staging: it is updated in place and **stays a page**, same URL. |
| **Email** | **`info@synergibpo.com` everywhere, no `.ae` address.** Form 7560 notifies it (carried from staging); WP Mail SMTP's sender label changes from `info@synergi.ae` — the Microsoft account behind it is already `info@synergibpo.com` on both sites, so delivery does not change. |
| **Case studies** | **Hidden from search for now**, as on staging: Yoast content-type noindex on `syn_case_study` and on the service archives. The `/case-studies/` listing page itself stays indexable, as on staging. |
| **Launch path** | Option C. Theme from Git + scripted content transfer. |

---

## What was fixed in code today

Morning (`4b628bd`, `6992c15`, `7d05a13`): the transfer stopped carrying the
staging noindex, carried the repaired posts, verified TLS, gained three
assertions and `syn_transfer_cleanup()`; mojibake fixed; blog category row.

Afternoon — transfer schema 3 and a post-activation script, each change because
of something the dry run or the audit measured:

| Found | Fixed by |
|---|---|
| The seven repaired posts' bodies **never travelled** — `"default"` counted as a template, so they went as `null`. | Export treats `default` as no template. `content_carried` must list exactly the seven posts. |
| Four templated pages carried an **empty body** that would have blanked production's rollback copy. | Export sends `null` for any templated or empty body. |
| ICXI would have been **created a second time**, empty, at the page's URL. | Importer matches a post against a top-level page at the same URL, updates it, never changes a type; any other URL clash stops the live run. |
| The case-study **taxonomy is not registered** while Theratio is active, so all 5 terms and 12 assignments would fail. | Importer registers both for the length of the run. |
| **59 images** to download against a **30-second** call limit. | `syn_transfer_import_media()` fetches them beforehand in time-boxed batches; the live import refuses to start while one is missing. |
| **`page_for_posts` = 0** on production — `/blog/` would never list. | Carried and set (→ 136). |
| The **logo** and **menu location** are theme mods, stored per theme. | Written into `theme_mods_synergi` before activation (logo 10506, menu 20). |
| Form 7560 on production has **no Phone field** and notifies **`info@y0r.256.myftpupload.com`**. | Staging's form carried whole; production's kept in `syn_wpforms_7560_backup_launch`. |
| Elementor Pro's site-wide **footer template 9031** would replace the theme footer. | Elementor stack retired by `tools/launch-after-activation.php`. |

Both dry runs on production wrote nothing. The second (schema 3) reported: 20
updates all resolving to the right pages at their current URLs, ICXI
"stays a page", 0 conflicts, content written only to the seven posts, 59
images missing, 43 mapped.

---

## Corrections to the existing documents

| Recorded as | Actually |
|---|---|
| `migration-plan.md`: "Production Novamira is down (404)." | **Connected.** Both servers respond. |
| Runbook: "Purge LiteSpeed **and** Cloudflare"; security headers are "four lines in Cloudflare". | **Cloudflare is not in front of synergi.ae** — responses carry `Server: LiteSpeed`, no `cf-ray`. Purge LiteSpeed only; headers go in LiteSpeed / `.htaccess`. |
| Runbook step 22: repoint "the flow" by changing its trigger. | **Two flows**, both on Elementor. Flow 1 also needs staging's **field map** (WPForms field IDs); flow 2 goes **off**, as on staging. |
| "Confirm form 7560 exists." | It exists, but is **not the same form** — see the table above. |
| Theme zip vs staging. | Identical apart from line endings and `inc/integrations.php`, where the **zip** is newer (a debug-comment change, `35c914d`). What was signed off is what ships. |
| `migration-plan.md`: "22 pages have no SEO title or description." | Zero missing. |
| P12, P13, §1.2, §2.3, P5/P6 | As recorded this morning: all already resolved on production. |

---

## Pages that lose Elementor

These are live, **not** in the transfer, and built with Elementor. With the
stack retired they render their stored text inside the new theme. Measured
10 Sep:

- **Readable, check after launch:** `/terms/`, `/privacy-policy/`, and ten blog
  posts outside the transfer (headings and paragraphs survive; Elementor
  layout, columns and some images do not).
- **Legacy landing pages:** `/shared-services-uae/`,
  `/bpo-services-in-saudi-arabia-ksa-riyadh/`, `/procurement-readiness/` —
  readable, unstyled. Still tied to the open cannibalisation question.
- **`/connect/`** — its four links survive (LinkedIn, Instagram, site,
  privacy), but it gains a second `<h1>` and loses its styling. **Open:**
  rebuild on a theme template, or accept for week 1.
- **Page 9146** (a hidden full-episode podcast page) renders **blank**.
- Redirected at launch, so unaffected: page 320, `/our-leadership/`,
  `/our-approach/`, `/hr-digital-transformation-guide/`.

---

## Done before the window (10 Sep, on "go")

- **Theme installed, not active.** Synergi 0.3.0, 150 files; the installed
  files' fingerprint matches the zip's exactly (`dbcdc157…`, line endings
  normalised). Theratio is still the active theme.
- **WP Mail SMTP sends as `info@synergibpo.com`.** Test email delivered with
  that sender (log row 194). The first attempt was overwritten by the plugin's
  own in-memory copy when the test mail refreshed the Microsoft token; the
  setting now lives in the database and survived a send.
- **Old-build plugins added to the retire list** (decided 10 Sep): Slider
  Revolution, Kirki, Meta Box, API KEY for Google Maps. They go after the
  switch, with the Elementor stack.
- **Redirects:** every staging redirect travels (61, merged with production's
  own `page-not-found`). The importer was writing them in the wrong shape for
  Yoast's served option, which would have disabled every redirect on the
  site; fixed in `ac2d861`.

- **Admin email is `omar.alakara@synergibpo.com`** on production and staging
  (was `mario.jreige@synergi.ae` / `@staging.synergi.ae`).
- **Mario's account deleted** (user 7, a former employee; decided 10 Sep). All
  375 items it owned — 6 posts, the executive-podcast page, a draft, 38
  images, 2 menu items, revisions — now belong to Omar (user 8). The theme
  shows no bylines, so visitors see no change. Administrators left: `omar`,
  `Soula` (still on `ahmed.patel@synergi.ae`).

## Site Kit needs reconnecting

Site Kit was connected through Mario's account, with the Google login
**`mario.jreige@synergi.ae`**; deleting the account removed that login, so its
wp-admin dashboard is disconnected. **The tags keep serving** — they come from
Site Kit's saved settings (`GT-TXBFKV55` → `G-EX4ZJYVVPG`), not from the login.

This also names the Google account behind GA property `462310803`, the one
recorded as unreachable. That access lives in Google, not WordPress. After
launch:

1. In Google Admin for `synergi.ae`, recover `mario.jreige@synergi.ae`.
2. Signed in as it, add `omar.alakara@synergibpo.com` as an administrator on
   GA property `462310803` and on the `https://synergi.ae/` Search Console
   property.
3. In wp-admin, Site Kit → reconnect as Omar with his own Google account.
   Keep the same property: choosing `G-F8BHKGB935` instead would double-count
   with ASE snippet 8607, which already sends to it.

## Still open

1. **`/connect/`** and **page 9146** — see above.
2. **Site Kit** — reconnect, above.
3. **Backup** — today's (05:55 UTC, 1.25 GB) is on the same server. Being
   downloaded by the launch lead.
4. **CRM** — flow 1 trigger + field map, flow 2 off, three transients, one real
   enquiry. By hand, after launch.

Every production write waits for an explicit "go".

---

## The ordering trap

The import sets `page_on_front`. That happens **while the old theme is still
active** — so between the import finishing and the theme switch, the live site
serves `/homepage-rebuild/` through Theratio. Import and activation are **one
action**; have Appearance → Themes open on Synergi before running it. The
import now touches only the database, so the gap is seconds.

---

## Rollback

1. Switch the theme back to Theratio.
2. Reactivate the plugins listed in `syn_launch_deactivated_plugins`.
3. `page_on_front` → 320, `page_for_posts` → 0.
4. Form 7560 ← `syn_wpforms_7560_backup_launch`.
5. Flows ← `syn_btcbi_flow_backup_launch`, then clear the three transients.

The redirect table merged, `_elementor_data` is untouched, and Theratio's own
theme mods were never written — nothing else needs restoring.

---

## The security findings

- **Novamira is active on production** (33 REST routes, including one that
  mints a password-free admin login). Credentialed, not open. Move it off after
  launch. The **Elementor Pro Activator** (nulled) goes with the Elementor
  stack at launch.
- `readme.html` and `license.txt` are served. Delete.
- No HSTS, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` —
  in LiteSpeed / `.htaccess`, not Cloudflare.
- Slider Revolution **7.1.2**; 7.1.8 available.
- The theme audited clean against CLAUDE.md §5.

---

## The numbers this launch is judged against

From `datagsc/`, three months to 7 Sep 2026: **314 clicks from 35,645
impressions** (0.88% CTR); homepage **207 clicks**, 66%; **79%** of named-query
clicks are "synergi"; non-brand **20 clicks from 25,667 impressions**. Judge the
rebuild on **non-brand clicks per month**.
