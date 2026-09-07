# Launch SEO safety — staging checked against production evidence

Written 7 September 2026. Sources: the 12-month GSC export in
`https___synergi.ae_-Performance-on-Search-2026-09-01/`, the GA4 landing-page
export `Landing_page_Landing_page.csv` (1 Jan – 7 Sep 2026), and a live read of
staging.synergi.ae through Novamira.

The question this answers: **if staging replaced production today, what would
we lose?** Production is the only site with traffic; staging has none by
design. So every check below is staging content measured against production
evidence, never against staging analytics.

Per-page GSC query exports were still being pulled when this was written. They
affect §4.4 and nothing else.

---

## 1. Three things that would actively hurt — fix before launch

### 1.1 The whole staging site is set to "discourage search engines"

`blog_public = 0`. Correct for staging. But All-in-One WP Migration is the
installed migration route, and a whole-database migration carries this option
with it. If it ships, **every page on synergi.ae goes noindex.**

That is the single largest risk in the entire launch, and it is one checkbox.
Settings → Reading → uncheck "Discourage search engines", immediately after the
database lands on production, verified by viewing source for
`<meta name="robots" content="noindex">`.

### 1.2 The homepage carries its own Yoast noindex

The front page (`page_on_front` = 10547, slug `homepage-rebuild`) has
`_yoast_wpseo_meta-robots-noindex = 1`. This is **independent of 1.1** — fixing
the site-wide setting does not clear it.

What is at stake, from the production data:

| | |
|---|---|
| Clicks, 12 months | 644 (55% of all site clicks) |
| Impressions | 21,870 |
| GA4 sessions | 3,042 (60% of all sessions) |

Clear the noindex on that page before launch.

### 1.3 `/media/` is published and noindex

74 impressions, 13 GA sessions. Small, but it is a hub page linked from the main
menu, and noindexing a hub also devalues what it links to. Confirm this was
deliberate; if not, clear it.

`privacy-policy`, `terms` and `procurement-readiness` are also noindex. Those
three look deliberate and are fine.

---

## 2. Redirect coverage — this part is in good shape

35 Yoast 301s are configured on staging. Cross-checked against every production
URL with at least one search impression, **only two are uncovered:**

| URL | Evidence | Verdict |
|---|---|---|
| `/headers/` | 1 click, 36 impressions | Junk page from the old theme. Let it 404. |
| `/careers/` | 75 GA sessions, all new users, 0 search impressions | **Needs a decision.** See §5. |

Everything else — including all eight `/our-approach/*` industry pages, both
duplicate ICXI posts, the truncated Nejmeh slug, `/our-ecosystem/`,
`/corporate-social-responsibility/` and `/privacy-policy-2/` — resolves.

## 3. Content depth on the new pages — not thin

The concern with a fields-driven rebuild is that `post_content` reads as empty
and the pages look thin to a crawler. Measured from the `_syn_` meta actually
rendered:

| Page type | Approx. words |
|---|---|
| Market pages (Saudi, UAE) | 1,030 – 1,140 |
| Service pages (all six) | 700 – 850 |
| Solution pages (all five) | 570 – 810 |

Comparable to or better than the pages they replace. No action.

---

## 4. Four judgement calls that should be decided, not defaulted

These are all *already configured* on staging. None is broken. Each is a
decision that was made implicitly and is worth making explicitly.

### 4.1 The procurement consolidation — highest stakes on the site

Three production URLs currently split the procurement search signal:

| URL | Clicks | Impressions | Position |
|---|---|---|---|
| `/procurement-services-uae/` | 76 | 22,091 | 17.0 |
| `/procurement/` | 20 | 10,009 | 31.8 |
| `/our-services/procurement/` | 5 | 9,794 | 30.0 |

Staging redirects the first two into the third. Consolidating is the right call
— 42,000 impressions competing with themselves is the biggest structural SEO
problem the site has. But note what it does: it merges the **strongest** URL
(position 17, keyword and geography in the slug) into the **weakest** (position
30). Combined signals should net positive, and `/our-services/procurement/` is
the better long-term structure — but this is the one redirect where a
regression would be visible, so it is the one to watch in GSC for six weeks
after launch.

The alternative, if you would rather not take the risk: keep
`/procurement-services-uae/` as the canonical procurement page and redirect the
other two into it. Uglier structure, safer traffic.

### 4.2 `/shared-services-uae/` redirects to the wrong page

Currently: `shared-services-uae` → `markets/united-arab-emirates`.

The searcher's intent is *shared services*, not *the UAE market*. The site now
has a dedicated Shared Services solution page at `our-solutions/shared-services`
(811 words), which is the matching answer. **Recommend changing the target to
`our-solutions/shared-services`.**

Note also that `open-questions.md` §6 states this URL *became* the Shared
Services solution page and kept its slug. Staging does not match that — the
solution lives at `our-solutions/shared-services` and the old URL redirects
away. The document is out of date and should be corrected either way.

### 4.3 `/synergi-uae-2-2/` redirects to the UAE market page

That URL is the current front page on production; WordPress canonicalises it to
`/`, which is why it has no separate GSC row. Anyone holding that link today
lands on the homepage. After launch they would land on the UAE market page
instead.

**Recommend retargeting to `/`** so the URL keeps doing what it does today. Low
stakes — no accumulated equity either way — but free to get right.

### 4.4 `/our-approach/` is being retired, and it ranks at position 5

| Clicks | Impressions | Position | CTR |
|---|---|---|---|
| 9 | 1,510 | **5.01** | 0.6% |

The best average position of any page on the site. Staging has it as a draft and
redirects it to `/about-us/`.

The 0.6% CTR says it ranks at position 5 for queries it does not answer, so
retiring it is defensible. But a page at position 5 should be retired
deliberately. Before confirming, pull its query list from GSC (Performance →
filter Page → `/our-approach/`) and check what it actually ranks for. If those
queries have a natural home in the new site, redirect there instead of
`/about-us/`.

---

## 5. Not an SEO issue, but found on the way

- **`/careers/`** — 75 sessions in eight months, every one a new user, 8s
  engagement. The word "careers" appears nowhere in the theme, the sitemap
  document, or any plan. Either a real page missing from the whole rebuild, or a
  URL people keep guessing at that already 404s. Worth two minutes.
- **`/404` shows 58 sessions from 4 active users.** Four people generating 58
  error sessions is a loop, not browsing.
- **Old theme demo content is live and crawlable** on production —
  `/portfolio/modern-villa-in-belgium`, `/portfolio-category/architecture`,
  `/demo`, `/pricing`, `/help`, `/support`, `/sales`. ~35 sessions between them.
  None exist on staging, none are redirected. Fine to 404 at launch, but they
  should be gone rather than left to rot.
- **Cloudflare is in front of the site** (`/cdn-cgi/l/email-protection` appears
  in GA). Cache purge belongs in the launch runbook, alongside LiteSpeed.
- **An Arabic URL is publicly reachable on production** —
  `/ar/our-services/` plus an Arabic slug recorded a session. Arabic is a later
  phase; this should not be live yet.

---

## 6. The measurement gap, and why it has a deadline

**GA4 records zero key events.** Not a single conversion, on any page, in eight
months. Form submissions are not tracked.

This means we cannot answer "does the new site generate more enquiries than the
old one" — not today, and not after launch either, because there will be no
before to compare against. Setting form submissions up as a GA4 key event
**before** launch buys a baseline for whatever weeks remain. After launch that
baseline is unobtainable.

It is roughly a 20-minute job in GTM and it is the only item in this document
that expires.

---

## Launch-day checklist, condensed

1. [ ] Settings → Reading → **uncheck** "Discourage search engines" (§1.1)
2. [ ] Clear Yoast noindex on the homepage (§1.2)
3. [ ] Decide `/media/` noindex (§1.3)
4. [ ] Retarget `shared-services-uae` → `our-solutions/shared-services` (§4.2)
5. [ ] Retarget `synergi-uae-2-2` → `/` (§4.3)
6. [ ] Decide `/our-approach/` after reading its query list (§4.4)
7. [ ] Decide `/careers/` (§5)
8. [ ] Purge Cloudflare **and** LiteSpeed
9. [ ] Submit the new sitemap in GSC, request indexing on the homepage
10. [ ] Watch `/our-services/procurement/` in GSC weekly for six weeks (§4.1)

**Before launch, separately:** GA4 key event for form submissions (§6).
