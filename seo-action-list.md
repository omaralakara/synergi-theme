# SEO action list — what to do, in order, to launch without losing ground and then gain

Written 7 September 2026, from the `datagsc/` exports pulled the same day, the
GA4 landing-page export, and live reads of both staging and production through
Novamira.

**Nothing was changed to produce this document.** It is a reading of the data
and a list of actions.

## How this relates to the other documents

`seo-content-migration-plan.md` is still the master plan and most of what
follows is already in it. This file exists because the 7 September pulls
contained three things that plan has never seen — the **Coverage report**, the
**per-page query data for the four competing procurement URLs**, and the
**16-month homepage query set** — and because one item on staging now
**contradicts** the plan. Where the two disagree, the disagreement is flagged
here and the plan should be corrected.

This file also absorbs `archive/launch-seo-safety.md` (written earlier today, before
the plan was re-read). Where that file presented findings as new which the plan
already covered, this one says so.

---

# Decisions taken — 7 September 2026

Answered by the business, recorded here so the contradictions in the other
documents can be corrected.

| # | Decision | Status |
|---|---|---|
| 1 | `/shared-services-uae/` → **redirect to the Shared Services page**. The old URL is not kept. | **Applied on staging.** Target changed from `markets/united-arab-emirates` to `our-solutions/shared-services`. |
| 2 | `/synergi-uae-2-2/` → **redirect to the homepage**. | **Applied on staging.** Target changed from `markets/united-arab-emirates` to `/`. |
| 3 | `/our-leadership/` is an old page; redirect to `/engagement-team/` stands. | Kept as configured — **but see the content gap below.** |
| 4 | `/our-approach/` is not wanted as a page at all; its content was already hidden from users. | Kept as configured: draft + 301 to `/about-us/`. |
| 5 | The 12 case studies and 5 case-service term archives are **noindexed**. | **Applied on staging.** Set at post-type and taxonomy level (`noindex-syn_case_study`, `noindex-tax-syn_case_service`), so future case studies inherit it. `/case-studies/` itself stays indexed. |
| 6 | `/our-leadership/` board and advisors are **out of date — people have left**. | Confirmed 7 Sep. Do not migrate the content; the redirect to `/engagement-team/` stands. See §9.5 — the stale list is live on production today and should be fixed there independently. |

| 7 | The "Thank You" page is **not recreated**; its URL redirects to the homepage for now. | **Applied on staging.** |
| 8 | `/careers/` is **redirected**, not built. Target: `/contact-us/`, whose lede already invites "career conversations". | **Applied on staging.** |
| 9 | `/hr-digital-transformation-guide/` and `/procurement-readiness/` are **set to draft** rather than rebuilt. | **Applied on staging**, each with a 301 so the live URL does not 404 — HR guide → `/our-services/human-resources/`, readiness → `/our-services/procurement/`. Reversible: republish and the redirect can be removed. |
| 10 | `/our-approach/` is dumped. | Confirmed. Already draft + 301 to `/about-us/`. |

| 11 | **No board or governance section on the new site.** The Board of Directors and Strategic Advisors lists are not wanted. | Nothing to build — staging already has no such content, and `/our-leadership/` redirects to `/engagement-team/`. Decision closed. |

**Still live on production, though:** `/our-leadership/` continues to publish the
board and advisor names the business has now said are wrong. That page disappears
at launch, so the only question is how long it stays up until then. Removing it
sooner is a production content change and needs its own explicit go-ahead
(CLAUDE.md §2.1 keeps production untouched until Stage 8).

Reversing decision 5 later is one setting per type, once client names are
cleared (`open-questions.md` §1) and the case studies can be thickened.

**Also applied 7 September:** the `why` and `why_cards` site records were
populated with the section's own approved copy and its four card images
(IDs 10482, 10481, 10783, 10483), so the band that renders on seven pages is now
editable in one place. The wording is byte-identical to what rendered before —
nothing moves on screen. The leftover `_syn_heading_backup_2026_08_25` meta on
post 8918 was deleted.

Staging's redirect table is now **39 rules** (was 35).

### Still unresolved after these decisions

`/procurement-bpo-readiness-checklist/` (a published post, 519 words) also
carried an Elementor form. The post keeps its content and stays published, but
**its form is gone and has no replacement.** Decide whether it needs one, or
whether the checklist offer is retired like the other two.

Staging's redirect table remains 35 rules; two targets changed, none added or
removed.

### Consequence of decision 1: a URL change needing a written exception

CLAUDE.md §2.8 forbids URL changes, and `seo-content-migration-plan.md` §2's
target map says shared services stays at `/shared-services-uae/`. Decision 1
overrides both, deliberately.

- The URL earns 8 clicks / 295 impressions at **position 9.0** (last 3 months).
- A 301 passes most ranking signal, and now points at the page that actually
  answers the query, which it did not before.
- **Both documents must be corrected** so they stop describing the old plan:
  `seo-content-migration-plan.md` §2 target map and §5.1 URL disposition, and
  `open-questions.md` §6's closing note.

### Consequence of decision 3: content that exists nowhere on the new site

Verified on production today. `/our-leadership/` (215 words) carries material
`/engagement-team/` does not:

- **Board of Directors** — Mansoor Almheiri (Chairman), Ahmed Ayyoub (Vice
  Chairman), Mohamad Saker (CEO)
- **Strategic Advisors** — Sevag Alexandrian, with a 20-year biography

Staging's `/engagement-team/` holds 17 operational team members and **no board
and no advisors**. It is a team page, not a governance page.

This matters beyond completeness: **"sevag alexandrian" earns 4 clicks at
position 4.28** — the best-performing person query on the site — and the page
that ranks for it is being redirected to a page that does not mention him.

The redirect itself is fine. The content should not simply vanish.

- [ ] Add a **Board of Directors** and **Strategic Advisors** group to the
      `people.php` template's data (the `_syn_people` repeater already exists;
      this is a second group, not new theme code) so the governance content
      survives on `/engagement-team/`. Then the redirect carries both the URL
      and the meaning.

---

# Part 1 — What the new data changed

### 1.1 Half the site is not indexed *(new — no Coverage data existed before)*

As of 4 September: **34 pages indexed, 35 not indexed.**

| Reason | Pages | What it means |
|---|---|---|
| Discovered – currently not indexed | 9 | Google found them and **chose not to crawl** |
| Crawled – currently not indexed | 9 | Google read them and **chose not to index** |
| Page with redirect | 8 | Expected — the 301s |
| Not found (404) | 3 | Expected |
| Excluded by 'noindex' | 3 | Expected — privacy/terms/readiness |
| Blocked due to other 4xx | 1 | Investigate |
| Blocked by robots.txt | 1 | Investigate |
| Alternate page with proper canonical | 1 | Fine |

The 18 in the first two rows are the finding. "Crawled – currently not indexed"
is Google's way of saying *this page is not worth an index slot*. Nine pages in
that state, on a 48-URL site, is a quality signal — not a technical fault.
Launching a better-built site is the correct response to it, which is
encouraging, but it also means **indexation must be watched after launch**, not
assumed.

**Action:** in GSC → Indexing → Pages, click into "Crawled – currently not
indexed" and "Discovered – currently not indexed" and export the URL lists. The
summary CSV only carries counts. Those 18 URLs decide whether the new versions
are worth keeping, merging, or dropping.

### 1.2 The procurement problem is four pages, not three *(refines plan §0.3)*

The plan identified generic procurement queries as 76% of impressions. The
per-page pulls show how badly they collide:

| URL | 16-month clicks | Impressions | CTR |
|---|---|---|---|
| `/procurement-services-uae/` | 16 | 18,887 | 0.085% |
| `/our-services/procurement/` | 2 | 11,025 | 0.018% |
| `/procurement/` | 6 | 9,164 | 0.065% |
| **Combined** | **24** | **39,076** | **0.061%** |

**139 queries have all three pages competing against each other**, burning
36,609 impressions. And no page is winning: on those shared queries
`/procurement-services-uae/` ranks best only **41%** of the time, and the
average position across all three is **37–38** — not the 17 the page-level
report suggests.

The fourth page is the **homepage**, which independently ranks for
"procurement bpo companies" (837 impressions), "procurement bpo firms" (521),
"procurement bpo services" (494), "procurement bpo consultants" (416) and
"procurement bpo providers" (319).

**This changes the recommendation in `archive/launch-seo-safety.md` §4.1.** That section
suggested keeping `/procurement-services-uae/` because it looked strongest. On
the query-level data it is not meaningfully stronger, and it is already 301'd on
production anyway (verified live: 18 redirect rules, both `procurement` and
`procurement-services-uae` → `our-services/procurement`). **The consolidation
onto `/our-services/procurement/` is already done and is correct. Keep it.**

The remaining work is not the redirect — it is that the surviving page ranks at
position 37 for generic global phrasings that will never click. See §4.2.

### 1.3 The site is found by people who already know it *(confirms plan §0.2, sharper)*

Homepage, 16 months, 423 queries:

| | Clicks | Impressions | CTR |
|---|---|---|---|
| Brand ("synergi…") | 191 | 1,759 | 10.9% |
| Non-brand | 24 | 12,435 | 0.19% |

**89% of homepage clicks are people typing the company name.** Non-brand search
delivers roughly one click a week.

By country, the same story: Lebanon converts 88 clicks from 257 impressions
(34% CTR, average position 2 — people looking for the company). The UAE
generates 23,859 impressions for 132 clicks (0.55%, average position 28 —
people looking for a supplier and not finding us near the top).

**The strategic consequence, and it is the most important line in this
document:** there is very little traffic to lose at launch. The risk is not
losing clicks — it is *continuing to fail to convert 12,000 monthly impressions
into clicks*. Migration safety matters, but it is the smaller half of the job.

### 1.4 Impressions grew 10×, clicks did not *(already plan §0.1, now with 16 months)*

| Month | Clicks | Impressions |
|---|---|---|
| Sep 2025 | 54 | 1,204 |
| Dec 2025 | 66 | 4,051 |
| Mar 2026 | 65 | 8,735 |
| Jun 2026 | 111 | 7,634 |
| Aug 2026 | 85 | 12,093 |

Twelve-month total: **993 clicks, 76,384 impressions.** Last three months: 309
clicks, 32,569 impressions, average position 26.7.

Visibility is compounding; conversion of it is flat. Every action in Part 4
targets this gap.

### 1.5 A contradiction between the plan and staging — must be resolved

`seo-content-migration-plan.md` §5.1 lists **`/shared-services-uae/` as
"Unchanged"** — rendering at the identical URL on the new theme.

Staging does not do that. Verified today:

- Post **#8877** is the same post on both sites.
- On production its slug is `shared-services-uae`.
- On staging its slug is **`our-solutions/shared-services`**.
- Staging's redirect table sends `shared-services-uae` → **`markets/united-arab-emirates`**.

So the URL *does* change, and the old URL is redirected to a **different page
than the content that used to live there**. Someone searching "shared services
UAE" would land on the UAE market page rather than the Shared Services page.

`open-questions.md` §6 also states this URL became the Shared Services solution
page and kept its slug. That is not what staging does either.

The page earns 8 clicks / 295 impressions at **position 9.0** in the last three
months — one of the better-performing non-brand positions on the site.

**Decide one of:**

- **(a) Keep the production URL.** Change the staging slug back to
  `shared-services-uae`, drop the redirect. Matches the plan, protects a
  position-9 ranking, costs a slightly odd URL under a tidy new structure.
  *Recommended — this is what the plan already promised, and CLAUDE.md §2.8
  says URLs don't change.*
- **(b) Keep the new URL** and retarget the redirect to
  `our-solutions/shared-services` — never to the market page. A URL change,
  needing a written §2.8 exception.

Either way, **the current configuration is wrong** and should not ship.

---

# Part 2 — Before launch (blocking)

Ordered. Items 1–3 are the ones that would cause real loss.

### 2.1 The two noindex flags *(already plan §5.5 and §6 — restated because it is fatal)*

Staging has `blog_public = 0` and a page-level Yoast noindex on the homepage
(`homepage-rebuild`, ID 10547) and on `/media/`. All three are correct for
staging. If any reaches production, the damage is total: the homepage alone is
55% of clicks and 60% of sessions.

- [ ] After the database lands on production, **Settings → Reading → uncheck
      "Discourage search engines"**. Confirm `blog_public = 1`.
- [ ] **Clear the homepage page-level noindex.** It is independent of the
      setting above; fixing one does not fix the other.
- [ ] **Clear `/media/`'s noindex.**
- [ ] Confirm `/privacy-policy/`, `/terms/`, `/procurement-readiness/` should
      *stay* noindex (they are deliberate, and defensible at 3 clicks total).
- [ ] View source on the live homepage and one service page and grep for
      `noindex` before announcing launch.

### 2.2 Resolve the `/shared-services-uae/` contradiction

- [ ] Pick (a) or (b) from §1.5. Correct staging, `seo-content-migration-plan.md`
      §5.1 and `open-questions.md` §6 so all three agree.

### 2.3 Fix two redirect targets that point at the wrong page

Staging carries 35 rules; production carries 18. The 17 additions are mostly
right. Two are not:

- [ ] `shared-services-uae` → currently `markets/united-arab-emirates`. Wrong
      (see §1.5). Fix per the decision made.
- [ ] `synergi-uae-2-2` → currently `markets/united-arab-emirates`. That URL is
      the **live front page** (production `page_on_front` = 320, verified
      today). It resolves to `/` for every visitor today. **Retarget to `/`.**
      Low stakes — it holds no separate equity — but free to get right, and
      sending the old homepage URL to a sub-page is simply incorrect.

### 2.4 Decide `/our-leadership/` — the site's second-best page is being deleted

Production has `/our-leadership/` as a published page (ID 2302). Staging has no
such page and 301s it to `/engagement-team/`.

| | Clicks | Impressions | Position |
|---|---|---|---|
| 12 months | 84 | 3,310 | 5.27 |
| Last 3 months | 19 | 735 | 9.46 |

**84 clicks makes it the second-highest-click page on the entire site**, behind
only the homepage, and it converts at 2.5% — four times the site average.

Redirecting it into `/engagement-team/` (6 clicks, 404 impressions) may well be
right if the two pages say the same thing. But this is a page that works being
merged into one that does not, and it is not called out anywhere in the plan as
a decision.

- [ ] Confirm deliberately: does `/engagement-team/` fully replace
      `/our-leadership/`? If not, keep the URL and put the leadership content
      back on it.

### 2.5 Decide `/our-approach/` — position 5.01, being retired

Draft on staging, 301 to `/about-us/`. 1,510 impressions at **position 5.01**,
the best average position on the site, but 0.6% CTR — it ranks well for queries
it does not answer.

- [ ] Pull its query list (GSC → Performance → filter Page →
      `https://synergi.ae/our-approach/`, 16 months). This is the one export
      from the earlier list that was not included in `datagsc/`.
- [ ] If those queries have a natural home in the new site, redirect there
      instead of `/about-us/`.

### 2.6 The remaining pre-launch items already in the plan

Not repeated in detail — see `seo-content-migration-plan.md` §7A. In summary:
trash `/synergi-uae-2-2/` and the KSA legacy page with explicit rules; write the
21 missing Yoast titles/descriptions plus the homepage's (it has none — the
staging noindex hid the gap); replace the Procurement TEST case-study fields;
retitle Procurement and HR; re-run the live redirect diff in the launch window;
pre-launch crawl and GSC-URL replay.

---

# Part 3 — Launch day

- [ ] Clear the noindex flags (§2.1) — **first action after the database lands**
- [ ] Purge **LiteSpeed and Cloudflare** (GA shows `/cdn-cgi/` paths, so
      Cloudflare is in front; the plan's runbook mentions LiteSpeed only)
- [ ] Verify every redirect is a **single hop, 301, to a 200**. Chains are the
      commonest launch-day loss and staging now has 35 rules
- [ ] Confirm Yoast's sitemap includes the case-study post type and
      `syn_case_service` term archives
- [ ] Resubmit the sitemap in GSC; request indexing on the homepage and the six
      service pages
- [ ] Spot-check canonicals: homepage, one service, one market, one case study,
      `/blog/` page 2
- [ ] Replay the full production URL list (68 from the Pages export) against the
      new site and confirm none 404 unintentionally
- [ ] Confirm ASE staging snippets are off

---

# Part 4 — First 30 days: convert the impressions that already exist

This is where the visibility gain comes from. All of it is content and metadata
work; none of it needs theme code.

### 4.1 Rewrite titles and descriptions against real query phrasing

The site ranks and is not clicked. These are page-1 or near-page-1 positions
earning **zero** clicks — the highest-leverage work available:

| Query | Impressions | Position | Clicks | Should be owned by |
|---|---|---|---|---|
| hr bpo companies in uae | 939 | 8.2 | **0** | `/our-services/human-resources/` |
| hr bpo companies in dubai | 680 | 17.4 | 0 | `/our-services/human-resources/` |
| bpo companies in dubai | 677 | 11.5 | 1 | `/markets/united-arab-emirates/` |
| procurement bpo companies | 837 | 19.0 | 0 | `/our-services/procurement/` |
| procurement operations services | 700 | 4.4 | 0 | `/our-services/procurement/` |
| sourcing and procurement providers | 888 | 8.1 | 0 | `/our-services/procurement/` |
| procurement advice support service | 465 | 8.5 | 0 | `/our-services/procurement/` |

Note the pattern: **the homepage is ranking for HR and procurement queries that
belong to the service pages.** That is the same cannibalisation as §1.2, one
level up. The fix is that each service page's title carries its cluster, and the
homepage's does not compete with them.

- [ ] Write titles that use the searcher's phrasing, not internal naming.
      "HR BPO Companies in UAE" beats "HR Outsourcing".
- [ ] Every description written as an *answer*, under 155 characters.
- [ ] Re-measure CTR per page at 30 days.

### 4.2 Decide the geography question for procurement

The surviving procurement page ranks position 37 on generic global phrasings
("procurement services", "sourcing services vendors") that produce thousands of
impressions and no clicks, because the searcher wants a local provider and the
SERP is full of them.

Meanwhile the UAE-qualified versions rank far better and do click.

Two coherent strategies. **Pick one, do not straddle:**

- **(a) Own the Gulf.** Make `/our-services/procurement/` explicitly UAE/GCC in
  title, H1 and body. Accept losing the generic global impressions — they are
  worth nothing at 0.02% CTR. Expect impressions to *fall* and clicks to rise.
  *Recommended: it matches where the clicks and the business actually are.*
- **(b) Chase the international cluster.** Keep generic phrasing and invest in
  the authority needed to move position 37 → 10 in a global market. Slower,
  more expensive, and competing with global BPOs.

Whichever is chosen, **write it down**, because "impressions fell" will
otherwise read as a launch regression when it is a deliberate trade.

### 4.3 Clean up what dilutes the site

- [ ] Remove the old theme's demo content, still live and crawlable:
      `/portfolio/*`, `/portfolio-category/*`, `/demo`, `/pricing`, `/help`,
      `/support`, `/sales`. ~35 GA sessions between them and no reason to exist.
      Likely contributors to the "crawled – not indexed" count in §1.1.
- [ ] Investigate the **Arabic URL live on production** —
      `/ar/our-services/` + Arabic slug recorded a GA session. Arabic is a later
      phase and should not be publicly reachable yet.
- [ ] Investigate `/404` showing **58 sessions from 4 active users** — that is a
      loop, not browsing.
- [ ] Decide `/careers/` — 75 GA sessions in eight months, every one a new user,
      and the URL **does not exist on production** (confirmed today), so all 75
      hit a 404. Either build the page or redirect it. The word "careers"
      appears nowhere in the theme, the sitemap document, or any plan.

### 4.4 Internal linking

- [ ] The blog carries the KSA and shared-services rankings (the Riyadh post
      alone has 1,909 impressions). Link those posts into the market and service
      pages they support — that is how the pages inherit the topical signal.
- [ ] `/bpo-services-in-saudi-arabia-ksa-riyadh/` lost its only internal link
      when the footer was rebuilt (recorded in `open-questions.md` §6). Whatever
      is decided about it, it should not remain orphaned.

---

# Part 5 — Measurement: the one item with a deadline

**GA4 has recorded zero key events, ever.** Every row of the landing-page export
shows `Key events = 0` across eight months and 5,072 sessions. Form submissions
are not tracked.

Consequence: we cannot answer "did the new site produce more enquiries" — not
now, and not after launch, because there will be no *before*.

- [ ] **Set up form submissions as a GA4 key event in GTM, before launch.**
      ~20 minutes. Every week it waits is a week of baseline permanently lost.
- [ ] Finish Site Kit setup (installed, never configured — CLAUDE.md §11)
- [ ] Record the pre-launch benchmark in the stage log: 993 clicks / 76,384
      impressions over 12 months; 309 clicks / 32,569 impressions over 3 months;
      average position 26.7; 34 indexed pages

---

# Part 6 — How we will know it worked

Compare at 30 / 60 / 90 days against the benchmark above. The honest success
criteria for this rebuild, given §1.3:

| Metric | Now | 90-day target | Why |
|---|---|---|---|
| Non-brand clicks / month | ~5 | 25+ | The actual growth metric |
| Sitewide CTR | 0.95% | 2%+ | Converting existing visibility |
| Indexed pages | 34 | 45+ | Content quality accepted by Google |
| "Crawled – not indexed" | 9 | 0–2 | The clearest quality signal Google gives |
| Brand share of clicks | 89% | <70% | Discovery, not just recall |
| Key events / month | untracked | tracked | Cannot manage what is not measured |

**Impressions are deliberately not on this list.** If §4.2(a) is chosen they
should fall, and treating that as a regression would push exactly the wrong
decisions.

Watch weekly for six weeks after launch: `/our-services/procurement/`,
`/our-leadership/` (or `/engagement-team/` if merged), `/shared-services-uae/`
(or its replacement), and the homepage.

---

# Part 7 — Decisions needed from the business

Everything above that cannot be actioned without an answer:

1. **`/shared-services-uae/`** — keep the URL, or move it and fix the redirect? (§1.5, §2.2)
2. **`/our-leadership/`** — does `/engagement-team/` genuinely replace it? (§2.4)
3. **`/our-approach/`** — retire to `/about-us/`, or somewhere better? (§2.5)
4. **Procurement geography** — own the Gulf, or chase the international cluster? (§4.2)
5. **`/careers/`** — build it, redirect it, or leave it 404ing? (§4.3)
6. **Arabic URL live on production** — intended or not? (§4.3)

Plus the still-open items in `open-questions.md`: client names, HR mobilisation
time, office contact details, legal entity per office, and a Marketing
photograph.

---

# Part 8 — Is the new site actually stronger? A direct comparison

Asked 7 September: *is production stronger than staging, content and structure
wise — and will the new site solve the pages fighting each other?*

**Short answer: staging is clearly stronger, and it fixes most of the fighting.
It does not fix all of it, and it introduces one new collision plus 17 new thin
URLs. Those are fixable before launch, and they are listed below.**

## 8.1 Where staging is unambiguously better

| | Production today | Staging |
|---|---|---|
| Procurement pages competing | 3 (+ homepage) | 1 |
| Industry pages under `/our-approach/` | 8, all near-zero traffic | 0, redirected |
| Duplicate URLs (`-2` suffixes, truncated slugs) | 5 | 0, redirected |
| `<h1>` per page | Some pages have 2 | Exactly 1, template-emitted |
| Page builder | Elementor + Slider Revolution + Element Pack | None |
| Redirect rules | 18 | 35 |
| Content on service pages | Elementor blocks, uneven | 700–850 words, structured |
| Content on market pages | none — pages don't exist | 1,030–1,140 words |
| Demo junk live (`/portfolio/*`, `/demo`, `/pricing`) | Yes, crawlable | Not present |

Structurally there is no contest. The old site is a decade of accumulated
duplicates on a builder; the new one is a designed information architecture. The
instinct that the current site is a mess is correct, and the rebuild is the
right response to it.

**And the strategy behind it is sound.** `seo-content-migration-plan.md` §2
contains a proper keyword target map (one query family → one URL) and a
two-phase homepage transition that deliberately does *not* hand the UAE cluster
to the new market page until GSC proves the market page has earned it. That is
exactly the right way to avoid trading a working ranking for a hopeful one.

## 8.2 The one collision the rebuild creates rather than solves

The plan's own target map says:

> shared services + geo → **`/shared-services-uae/` (kept at its URL)**

Staging does the opposite: post #8877 has been re-slugged to
`our-solutions/shared-services`, and `shared-services-uae` now 301s to
`markets/united-arab-emirates` — a different page from the content that used to
be there. The page ranks at **position 9.0** today.

Shared services would then be spread across three URLs
(`/our-solutions/shared-services/`, `/markets/united-arab-emirates/`, and the
blog post `why-shared-services-in-uae-gcc-are-transforming-businesses`) with the
strongest historical URL pointing at the wrong one of the three. That is the
old failure pattern in a new place.

**Fix before launch — see §1.5 and §2.2.** This is the single most important
item in this document for the question "will the new site solve the fighting".

## 8.3 The new thin-content risk

Staging roughly doubles the number of indexable URLs, on a site where Google
currently indexes only 34 of 48 and has explicitly declined 9 for quality.

New indexable URLs the rebuild adds:

| Type | Count | Depth | Yoast setting |
|---|---|---|---|
| Case studies (`syn_case_study`) | 12 | 139–228 words body + 38–68 meta | `noindex-syn_case_study: false` → **indexed** |
| Case-service term archives | 5 | No unique copy — listing only | `noindex-tax-syn_case_service: false` → **indexed** |

Twelve near-identically-structured 200-word pages plus five empty listing
archives is precisely the profile that produces "Crawled – currently not
indexed", and the site already has nine of those.

**This is not an argument against case studies** — they are good for sales and
for internal linking. It is an argument for controlling what enters the index:

- [ ] **Noindex the 5 `syn_case_service` term archives.** They carry no unique
      content and exist for navigation. Zero downside.
- [ ] **Decide on the 12 case studies.** Either thicken them (aim 400+ words —
      problem, approach, what changed, measurable outcome) or noindex them and
      let `/case-studies/` be the one indexed page in the cluster. Publishing 12
      thin pages into an index that is already rejecting content for quality
      works against the goal.
- [ ] Confirm the 5 category archives still earn their place (they exist on
      production too, so this is not new — but `/category/humen-resource/` is a
      typo'd slug that is live and indexed).

*Checked and fine:* author archives are disabled, attachment pages are disabled,
date archives exist but are noindexed, post-format archives are noindexed, ASE
code-snippet URLs are noindexed. No action needed on any of those.

## 8.4 The collision the plan defers on purpose (know that it is deferred)

The homepage currently ranks for queries that belong to the service pages —
"hr bpo companies in uae" (939 impressions, position 8.2, **0 clicks**),
"procurement bpo companies" (837), "procurement bpo firms" (521).

The plan's Phase 1 keeps the homepage on "BPO & Shared Services in the UAE, Gulf
& Beyond", which is right for migration safety — one change at a time. But it
means the homepage-versus-service-page overlap survives launch by design, and
Phase 2 only addresses the **geographic** cluster, not the HR and procurement
ones.

That is a defensible sequencing decision, not an error. It just should not be
mistaken for "the cannibalisation is solved at launch". It is solved for
procurement (three pages into one), for the industry pages, and for the
duplicates. It is **deferred** for homepage-versus-service-page.

- [ ] Add HR and procurement to the Phase 2 review, not just the UAE cluster.

## 8.5 The honest summary

| Question | Answer |
|---|---|
| Is production stronger content-wise? | **No.** Staging has more, better-structured, more complete content on every page type that matters. |
| Is production stronger structurally? | **No.** It is the mess. Staging is a designed hierarchy with 35 redirects consolidating it. |
| Will the new site stop pages fighting? | **Mostly.** Procurement, industries and duplicates: yes, solved. Shared services: currently made *worse* — fix §2.2. Homepage vs service pages: deliberately deferred to Phase 2. |
| Is there a risk of making things worse? | **Two, both fixable this week:** the shared-services redirect (§8.2) and 17 new thin indexable URLs (§8.3). |
| Should the launch proceed? | **Yes**, once Part 2 is closed. The current site cannot be improved incrementally into this; the rebuild is the right call and the evidence supports it. |

---

# Part 9 — Migration readiness: what is still missing

Audited 7 September against both sites through Novamira. Ordered by
consequence, not by effort. **The first item is not an SEO issue and is more
serious than everything else in this document combined.**

## 9.1 BLOCKING — every lead stops reaching the CRM, silently

Bit Integrations carries **two active flows** (`status = 1`) that send form
submissions to **Zoho Bigin**. Both are triggered by:

```
triggered_entity:    Elementor
triggered_entity_id: elementor_pro/forms/new_record
```

**Elementor is deactivated on the new site.** That hook will never fire again.
The flows will not error, will not warn, and will not appear broken in wp-admin
— they will simply never run.

Meanwhile staging's contact page renders `[wpforms id="7560"]` (the field
`_syn_contact_form_shortcode`), so submissions will land in WPForms and stop
there.

**Net effect on launch day: enquiries keep arriving, the form keeps saying
thank you, and nothing reaches sales.** This is the single largest risk in the
migration and it is not mentioned in any existing document.

- [ ] Rebuild both Bit Integrations flows to trigger on **WPForms** instead of
      Elementor, re-map the fields to the Zoho Bigin destination, and **submit a
      real test enquiry end-to-end** — confirming it appears in Bigin, not just
      that the flow saved.
- [ ] Do this on staging first, and re-verify on production immediately after
      launch. A flow that works on staging can still point at the wrong Bigin
      pipeline.
- [ ] Check with whoever owns Bigin whether other automations depend on the
      Elementor hook (CLAUDE.md §11 warns Bit Integrations may carry live CRM
      automation — this confirms it does).

## 9.2 BLOCKING — three of the four forms on the site have no replacement

Production runs Elementor forms on four pages:

| Page | Purpose | On the new site |
|---|---|---|
| `/contact-us/` | Main enquiry form | ✓ Replaced with WPForms 7560 |
| `/procurement-readiness/` | Lead magnet — checklist download | ✗ **No form** |
| `/hr-digital-transformation-guide/` | Lead magnet — video gate | ✗ **No form** |
| `/procurement-bpo-readiness-checklist/` | Lead magnet | ✗ **No form** |

`Simple Contact Form` (ID 7560) is the **only** form that exists on staging, and
no published staging content references any other.

- [ ] Decide per page: rebuild the form in WPForms, or retire the lead magnet.
- [ ] Whichever is chosen, the CRM flow in §9.1 must cover it too.

## 9.3 BLOCKING — one page will visibly break

`/hr-digital-transformation-guide/` (62 impressions) has **no page template**,
**40 words** of post content, and its layout lives entirely in `_elementor_data`
that will no longer render. What remains reads:

> "Who said Artificial Intelligence is far from HR? … **Fill out the form
> below** to wa…"

— followed by no form. A published page inviting an action it cannot perform.

`/procurement-readiness/` is in the same state but degrades better (906 words of
real text survive, and it is noindexed).

- [ ] Rebuild both on a theme template, or set them to draft before launch.
      Leaving them as-is ships a visibly broken page.

The existing plan lists this rebuild under "can follow launch (first 30 days)".
On this evidence it should move to before launch — post-launch is fine for
*improving* the page, not for *un-breaking* it.

## 9.4 BLOCKING — a form redirect target that does not exist

Production has a published page **"Thank You"** (ID 9146) at the slug
`full-episodek6vl3n8qz2kjf9ya7pt4br1mx0wn5ce2sj8hu9og6ry3qv5dp4ts7zc1ab8ll9nf`.
It is referenced from `/hr-digital-transformation-guide/`'s form configuration
as the post-submission redirect.

It does not exist on staging.

- [ ] Recreate it, or point the rebuilt form at a new confirmation page. Do not
      leave a form redirecting to a 404.

## 9.5 The leadership content — a live accuracy problem, not a migration one

Confirmed with the business on 7 September: the Board of Directors and Strategic
Advisors listed on `/our-leadership/` are **out of date — some of those people
have left.**

That resolves the §"Decisions taken" gap in the opposite direction to the one
proposed there: **do not carry this content across.** Redirecting
`/our-leadership/` → `/engagement-team/` and letting the stale governance list
disappear is now clearly correct, and the loss of the "sevag alexandrian"
ranking is a benefit rather than a cost — it is a query for someone no longer
with the company.

But note what this means about *today*:

- [ ] **`/our-leadership/` is live on production right now, publishing an
      incorrect board and an advisor who has left.** That is worth correcting on
      the live site independently of the migration, not left until launch.
- [ ] Decide whether the new site needs a governance section at all. If the
      board is a real trust signal for buyers, it should exist with correct
      names on `/about-us/` or `/engagement-team/`. If not, it stays gone.

## 9.6 Non-blocking gaps and cleanup

| Item | State | Action |
|---|---|---|
| `why` and `why_cards` site records | **Empty** | The `why.php` section falls back to approved defaults, so the band renders correctly on all seven pages. But the record was created so the copy is editable in one place, and empty records mean an editor cannot change it. Populate before handover. |
| Footer link columns | Hard-coded in `footer.php` | Deliberate and documented; the `footer` menu location is registered but unassigned. Stage 7 handover item, not a defect. |
| `_syn_heading_backup_2026_08_25` on post 8918 | Leftover backup meta | Delete. |
| Attachments | 984 on staging vs 917 on production | Staging has 67 more (build additions). They travel with the migration. Fine. |
| Yoast titles/descriptions | **0 missing** across all published pages, posts and case studies | The plan's "21 missing" is done. No action. |
| Menu | One "Main Menu", 30 items, no items pointing at unpublished content | ✓ |
| Dead shortcodes in published content | None found | ✓ |
| `_elementor_data` retained on published pages | Expected — CLAUDE.md §2.9 keeps it as the rollback | No action. |

## 9.7 Revised launch order

The SEO items in Part 2 remain correct. This is what now sits **above** them:

1. Rebuild the Bit Integrations flows on WPForms and test a real submission into
   Zoho Bigin (§9.1)
2. Decide and build the three missing forms, or retire the lead magnets (§9.2)
3. Fix or draft `/hr-digital-transformation-guide/` and `/procurement-readiness/`
   (§9.3)
4. Recreate the confirmation page or repoint the form (§9.4)
5. Then Part 2: the noindex flags, the redirect decisions, case-study indexing
6. Then Part 3: launch day

---

# Part 10 — Tracking: what exists, what breaks, what to do

Audited 7 September on both sites. **Answering the direct question first: no,
the new site does not carry the tracking. As configured today, staging outputs
no analytics at all, and the theme cannot load GA4 even if you asked it to.**

## 10.1 What production runs today — three separate mechanisms

Nobody has written this down before, and it matters because each one behaves
differently at launch.

| # | Mechanism | What it loads | Survives the new theme? |
|---|---|---|---|
| 1 | **Site Kit → Analytics** (`useSnippet: true`) | GA4 **G-EX4ZJYVVPG**, property 462310803 | **Yes** — Site Kit outputs this itself, independently of the theme |
| 2 | **ASE code snippet "Google"** (published) | A **second, different** GA4: **G-F8BHKGB935** | **No** — the ASE snippets feature is off on staging |
| 3 | **ASE code snippet "LinkedIn Tag"** (published) | LinkedIn Insight Tag, partner `9021449` | **No** — same reason |

There is also a fourth ASE snippet, **"SEO: single post title H3 to H1"**, which
rewrites post headings through an output buffer. It is not tracking, but note
it: **the new theme emits a correct single `<h1>` natively** (CLAUDE.md §8), so
this snippet must not travel — it would fight the template.

**Two GA4 properties are collecting simultaneously.** `G-EX4ZJYVVPG` is the one
Site Kit reports and the one the `datagsc/` landing-page export came from.
`G-F8BHKGB935` is a second property nobody has mentioned. Decide which is the
real one before launch — running both means every report is a guess about which
number is authoritative.

**There is no Google Tag Manager container.** Site Kit's Tag Manager module is
inactive with an empty container ID. Earlier notes in this file said "20 minutes
in GTM" — that was wrong: there is no GTM to configure. Everything is gtag,
loaded directly.

## 10.2 What staging runs today — nothing

- Site Kit → Analytics: `useSnippet: **false**`. No snippet output.
- Theme (`inc/integrations.php`): its GTM loader is present and correct, but
  `syn_gtm_id` is **not set** and `SYN_GTM_ID` is undefined, so it renders
  nothing. That is by design — the file's own header says Stage 1 ships it
  unconfigured and Stage 8 sets the ID.
- ASE snippets: all three exist but the ASE custom-code-snippets **feature is
  disabled**, so none execute.

Staging emitting nothing is correct for staging. The problem is only that
nothing has been set up to switch on at launch.

## 10.3 The gap nobody has hit yet

`inc/integrations.php` validates its ID with `/^GTM-[A-Z0-9]{4,}$/`. **It accepts
a GTM container ID and nothing else.** It has no gtag/GA4 path.

So the theme, as built, cannot load `G-EX4ZJYVVPG` directly. Two ways forward:

- **(a) Create a GTM container** and put GA4 and the LinkedIn tag inside it, then
  set `syn_gtm_id`. *Recommended.* CLAUDE.md §11 already names GTM as the
  intended single injection point; the theme was written for exactly this; it
  fixes LinkedIn at the same time; and it gives conversion tracking somewhere
  sensible to live. Roughly an hour, once.
- **(b) Leave Site Kit's snippet on** (`useSnippet: true` on production already).
  Zero work — it loads independently of the theme. But it covers GA4 only, so
  the LinkedIn tag stays dead, and it loads in `<head>` rather than deferred,
  which is the payload CLAUDE.md §6 wanted kept off the critical path.

Whichever is chosen, **(b) is the safety net**: Site Kit's snippet is already on
in production and will keep working through the launch whatever else happens. Do
not turn it off until (a) is verified.

## 10.4 Actions

- [ ] Decide which GA4 property is authoritative — `G-EX4ZJYVVPG` or
      `G-F8BHKGB935` — and retire the other.
- [ ] Choose (a) or (b) above. If (a): create the container, add GA4 + LinkedIn,
      set `syn_gtm_id` on staging, verify the tag fires, then set it on
      production at launch.
- [ ] **Set up form submissions as a GA4 key event** (§5). Currently zero key
      events in eight months. Do this *before* launch or the before/after
      comparison is impossible forever.
- [ ] Do **not** carry the "SEO: single post title H3 to H1" snippet to the new
      site.
- [ ] Site Kit's Search Console module points at `https://staging.synergi.ae/`
      on staging and `https://synergi.ae/` on production — correct on both, no
      action, but do not let the staging value travel.

---

# Part 11 — Zoho Bigin: how leads reach sales, and why they stop

Expanded because this is the one failure that costs money rather than rankings.

## 11.1 How a lead reaches the CRM today

1. Someone fills in the form on `/contact-us/`. That form is an **Elementor
   Pro** form.
2. On submission, Elementor Pro fires a WordPress event —
   `elementor_pro/forms/new_record` — meaning *"a form was just submitted, here
   is the data"*.
3. **Bit Integrations** is listening for that exact event. It holds **two active
   flows** (both named "Zoho Bigin", both `status = 1`).
4. Each flow takes the submitted fields and creates a record in **Zoho Bigin**,
   Zoho's small-business CRM.
5. Sales work the lead from Bigin.

Bit Integrations is the wiring between the form and the CRM. It is not
optional plumbing — it *is* the connection.

## 11.2 Exactly what breaks, and why it is silent

On the new site:

- Elementor is **deactivated**, so `elementor_pro/forms/new_record` will never
  fire again.
- The contact form is now **WPForms** (`[wpforms id="7560"]`, stored in the
  `_syn_contact_form_shortcode` field on the Contact page).
- WPForms fires a **different** event (`wpforms_process_complete`).
- Bit Integrations is still listening for the Elementor one.

**Result: the visitor fills in the form, sees "thank you", the entry is saved in
WPForms — and nothing reaches Bigin.** No error appears. No warning in wp-admin.
The flows still show as active, because they are: they are simply waiting for an
event that will never come.

This is why it is dangerous. A broken form gets reported within a day. A form
that silently stops feeding the CRM gets noticed weeks later, when someone asks
why the pipeline is empty.

## 11.3 What has to be done — in order

1. **Check the Zoho Bigin connection is still authorised.** Bit Integrations
   holds OAuth tokens; they expire. Fix this first or every later test fails for
   the wrong reason.
2. **Open each of the two flows and find out why there are two.** Both are named
   "Zoho Bigin" and both trigger on the same event. One may be for the contact
   form and one for a lead magnet, or one may be an abandoned duplicate. Do not
   rebuild a duplicate.
3. **Change the trigger** from Elementor Forms to **WPForms → Simple Contact
   Form (ID 7560)**.
4. **Re-map every field.** This is not a re-point — Elementor and WPForms name
   their fields differently, so the mapping from form field to Bigin field
   (name, email, phone, company, message) must be rebuilt by hand. A flow that
   triggers but maps nothing creates empty CRM records, which is worse than
   none, because it looks like it is working.
5. **Submit a real test enquiry on staging and confirm the record appears in
   Bigin.** Not "the flow saved". Not "the log shows a run". The record, visible
   in the CRM, with the right values in the right fields.
6. **Repeat step 5 on production immediately after launch.** A flow verified on
   staging can still point at the wrong Bigin pipeline once live.

## 11.4 The related loss nobody has counted

The three lead-magnet pages also used Elementor forms and fired the same event,
so whatever they were sending to Bigin stops too:

- `/procurement-readiness/` — now drafted
- `/hr-digital-transformation-guide/` — now drafted
- `/procurement-bpo-readiness-checklist/` — **still published, form gone**

Drafting the first two was a decision. The third is still live with no form.

- [ ] Confirm nobody in sales is expecting checklist or guide downloads to keep
      arriving. If they are, those forms need rebuilding in WPForms and adding
      to the flow work above.

---

# Part 12 — The step-by-step list

Do these in order. Phase 0 is genuinely urgent and is not about SEO at all.

---

## PHASE 0 — Stop the leads disappearing (do this first)

Three separate things each independently break lead capture, and together they
mean **an enquiry on the new site would vanish completely** — no CRM record, no
stored entry, no email.

**0.1 — The notification email goes to a dead address.**
WPForms form 7560 sends its notification to `info@y0r.256.myftpupload.com` — a
leftover GoDaddy temporary-hosting address from whenever the form was first
created. Nobody reads that mailbox.

- [ ] WPForms → Simple Contact Form → Settings → Notifications
- [ ] Change **Send To Email Address** to a real, monitored mailbox
- [ ] Change **From Email** to a synergi address so it does not land in spam
- [ ] Leave Reply-To as `{field_id="1"}` — that is correct, it replies to the
      enquirer
- [ ] Send a test and confirm it arrives

**0.2 — WPForms Lite stores no entries.**
There is no `wpforms_entries` table. On production, Elementor kept a copy of
every submission (193 of them), so a CRM failure still left the lead recoverable.
On the new site, **if the email fails and the CRM flow fails, the enquiry is
gone forever.**

- [ ] Treat 0.1 as the safety net it now is — that email is the only record
- [ ] Decide whether entry storage is wanted. WPForms entry storage is a paid
      feature; a free alternative or a simple database log would also do. Not
      blocking if 0.1 is done, but know that you are running without a net

**0.3 — The Zoho Bigin flows are listening for the wrong event.**
Full explanation in Part 11. In short: Bit Integrations waits for an Elementor
signal, Elementor is switched off, so nothing reaches the CRM.

- [ ] Check the Zoho Bigin authorisation in Bit Integrations is still valid
- [ ] Open both flows, work out why there are two, retire any duplicate
- [ ] Change the trigger to **WPForms → Simple Contact Form (7560)**
- [ ] Re-map the fields by hand — note the WPForms form has only **three**
      fields (Name, Email, Message) where the Elementor form may have had more.
      Anything Bigin expects that no longer exists must be dropped or the form
      extended
- [ ] Submit a real test enquiry on staging; confirm the record appears in Bigin
      with correct values
- [ ] Repeat on production immediately after launch

**0.4 — Investigate the 85% drop in enquiries.**
Elementor's stored submissions show this:

| Month | Submissions | | Month | Submissions |
|---|---|---|---|---|
| Aug 2025 | 36 | | Feb 2026 | 5 |
| Sep 2025 | 29 | | Mar 2026 | 1 |
| Oct 2025 | 45 | | Apr 2026 | 6 |
| Nov 2025 | 38 | | May 2026 | 3 |
| Dec 2025 | 8 | | Jun 2026 | 6 |
| Jan 2026 | 2 | | Jul 2026 | 8 |
| | | | Aug 2026 | 6 |

Something changed around **December 2025**: from roughly 37 enquiries a month to
roughly 5. Either the earlier volume was spam that later got filtered, or real
lead flow collapsed by 85% and nobody noticed.

- [ ] Open a handful of submissions from Oct 2025 and from Jul 2026 and compare.
      Spam or real? This single check decides whether the site has a lead
      problem or just a spam-filter history
- [ ] Note the HR guide form's last submission was **7 Dec 2025** — that offer
      has been dead for nine months, which supports drafting it

---

## PHASE 1 — Keep the tracking (before launch)

**1.1 — Decide which GA4 is real.**
Two are collecting: `G-EX4ZJYVVPG` (Site Kit) and `G-F8BHKGB935` (an ASE
snippet).

- [ ] analytics.google.com → check both properties for data and for which is
      linked to Google Ads (`adsLinked` is true on one)
- [ ] Pick the survivor. Everything below uses that ID
- [ ] Retire the other, or at minimum stop adding to the confusion

**1.2 — Create a GTM container.**

- [ ] tagmanager.google.com → Create Account → container type **Web**, for
      synergi.ae
- [ ] Copy the `GTM-XXXXXXX` ID
- [ ] Inside GTM add a **Google Tag** with the surviving GA4 measurement ID,
      trigger **All Pages**
- [ ] Add the **LinkedIn Insight Tag** (partner ID `9021449`) as a Custom HTML
      tag, trigger **All Pages** — this is currently loaded by an ASE snippet
      that will not run on the new site
- [ ] **Publish** the container (a saved-but-unpublished container does nothing)

**1.3 — Connect it to the theme.**

- [ ] On staging set the `syn_gtm_id` option to the container ID, or
      `define( 'SYN_GTM_ID', 'GTM-XXXXXXX' );` in `wp-config.php`
- [ ] Load a staging page and view source: the `syn-gtm-loader` script should
      appear in the footer
- [ ] Use GTM **Preview** mode against staging and confirm GA4 and LinkedIn both
      fire

**1.4 — Do not break the safety net.**

- [ ] Leave Site Kit's `useSnippet` **on** in production until 1.3 is verified
      live. It is theme-independent and will keep GA4 running through launch
      whatever else happens
- [ ] Only after the GTM tag is confirmed firing on production should Site Kit's
      snippet be turned off, to avoid double-counting

**1.5 — Conversion tracking, before launch.**

- [ ] In GTM add a trigger for the WPForms confirmation, and a GA4 Event tag
      named `generate_lead`
- [ ] GA4 → Admin → Events → mark `generate_lead` as a **Key event**
- [ ] Test a submission and confirm it shows in GA4 Realtime
- [ ] For the pre-launch baseline you do **not** need to wait: use the Elementor
      submissions table in 0.4. It already gives twelve months of real enquiry
      counts, which is a better baseline than anything GA could collect in the
      remaining weeks

**1.6 — Things that must NOT travel to production.**

- [ ] `blog_public = 0`
- [ ] The homepage and `/media/` page-level noindex flags
- [ ] The ASE snippet **"SEO: single post title H3 to H1"** — the new theme
      emits a correct single `<h1>` natively and the snippet would fight it
- [ ] Site Kit's Search Console property set to `staging.synergi.ae`

---

## PHASE 2 — Fix Search Console

**2.1 — At launch, in order.**

- [ ] Clear the three noindex flags (Settings → Reading, then the two pages)
- [ ] Purge **LiteSpeed** and **Cloudflare**
- [ ] View source on the homepage and one service page; grep for `noindex`
- [ ] Confirm Site Kit's Search Console property is `https://synergi.ae/`

**2.2 — Sitemap and indexing.**

- [ ] Yoast → check the sitemap includes pages, posts and the `/case-studies/`
      listing, and **excludes** the 12 case studies and the 5 case-service term
      archives (they were noindexed on 7 Sep — confirm Yoast dropped them)
- [ ] GSC → Sitemaps → resubmit `sitemap_index.xml`
- [ ] GSC → URL Inspection → request indexing on the homepage and the six
      service pages

**2.3 — Verify the redirects.**

- [ ] Replay all 68 production URLs from `datagsc/.../Pages.csv` against the new
      site
- [ ] Every one must be a **single hop** to a 200. Chains are the commonest
      launch-day loss and there are now 39 rules

**2.4 — Fix the indexing quality problem.**
34 pages indexed, 35 not — including 9 "Crawled – currently not indexed" and 9
"Discovered – currently not indexed".

- [ ] GSC → Indexing → Pages → click into each of those two rows and **export
      the URL list**. The summary CSV carries counts only
- [ ] For each of the 18: does it still exist on the new site, is it redirected,
      or should it go? These are Google telling you which pages it judged not
      worth an index slot
- [ ] Delete the old theme's demo content still live on production —
      `/portfolio/*`, `/portfolio-category/*`, `/demo`, `/pricing`, `/help`,
      `/support`, `/sales`. Likely contributors to that count
- [ ] Take the Arabic URL offline until the Arabic phase

**2.5 — Monitor, for six weeks.**

- [ ] Weekly: `/our-services/procurement/`, the homepage, `/engagement-team/`,
      `/our-solutions/shared-services/`
- [ ] Judge on **non-brand clicks**, not impressions. If the procurement page is
      rewritten for the Gulf, impressions should fall and that is the plan
      working, not failing

---

## PHASE 3 — Still needs a decision

- [ ] Does the site need a board / governance section? (see below)
- [ ] Which GA4 property survives (1.1)
- [ ] Procurement: Gulf-focused or generic — affects wording only, not launch

## Closed on 7 September

- `/procurement-bpo-readiness-checklist/` — **offer retired, no work needed.**
  The post carries 519 words of real content, has **no form and no download
  gate**, and ends on a plain "Talk to Synergi today". The gated landing page
  `/procurement-readiness/` was the offer, and it is already drafted with a 301.
  The post stays published exactly as it is.

---

# Appendix — one export still missing

Everything asked for arrived except the **`/our-approach/` query list**, which
decides §2.5. To pull it: GSC → Performance → Search results → Last 16 months →
**+ New → Page → URL is exactly** → `https://synergi.ae/our-approach/` → Export.

Also worth pulling when convenient, for §1.1: GSC → **Indexing → Pages** → click
into "Crawled – currently not indexed" and "Discovered – currently not indexed"
and export each URL list. The summary export carries only counts, and those 18
URLs are the clearest quality signal available.
