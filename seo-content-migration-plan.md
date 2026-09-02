# SEO, content and migration plan — synergi.ae → new theme

Written 1 September 2026. Planning and analysis only — nothing on staging or
production was changed in its preparation. Every number below comes from one of
four verified sources: the GSC export in this folder (12 months to 1 Sep 2026),
the company profile PDF (20 Aug 2026), the live site inspected read-only over
HTTP, and the staging database read through Novamira. Where a claim could not
be verified, it is marked as needing confirmation, not asserted.

This plan extends — and never contradicts — `migration-plan.md` (the transfer
mechanics) and `stage-7-decisions.md` (the structural record). Where new
evidence supersedes an earlier recommendation, that is said explicitly with the
evidence.

---

## 0. What the data actually says (read this first)

Five findings shape everything below:

1. **Visibility grew 10×; clicks did not.** Monthly impressions went from 1,204
   (Sep 2025) to 12,093 (Aug 2026). Monthly clicks went from 54 to 85–111.
   Sitewide CTR is 1.30% at an average position of ~19. The site is being
   *seen* and not *chosen* — mostly because it ranks 8–20, and partly because
   titles don't match how people phrase these searches.

2. **79% of all clicked queries are brand.** Of 256 query-level clicks, 201 are
   "synergi"-family searches. Non-brand organic search currently delivers
   roughly **one click per week**. There is very little traffic to lose — and a
   great deal of accumulated *visibility* to convert. This reframes the
   migration: the risk is not losing traffic that exists, it is failing to cash
   in impressions that already exist.

3. **Procurement is 76% of all impressions — and it is generic, not
   geographic.** 369 procurement/sourcing queries produced 41,627 impressions.
   The biggest are international phrasings with no country at all: "sourcing
   and procurement providers" (1,705 imps, pos 7), "procurement operations
   services" (1,348, **pos 4.5**), "procurement services" (1,193, pos 7.2) —
   nearly all at 0% CTR. The site already ranks internationally; the
   "international" ambition is not a bet, it is the current reality of the
   site's largest cluster. The US alone generated 7,038 impressions.

4. **The pages assumed to hold market rankings mostly don't.**
   `/bpo-services-in-saudi-arabia-ksa-riyadh/` — the legacy KSA landing page at
   the centre of the collision question — does not appear in the GSC pages
   report at all: effectively **zero impressions in 12 months**. The KSA
   queries that do appear ("bpo companies in saudi arabia", 246 imps, pos 15.6)
   are carried by the Riyadh **blog post** (1,909 imps) and the homepage.
   Likewise `/synergi-uae-2-2/` records nothing — and the live site already
   301-redirects it to the homepage. The UAE rankings live on the **homepage**
   (644 clicks, 21,870 imps, title and H1 = "BPO Services in UAE & the Gulf").

5. **The live site already did part of the consolidation — and one redirect
   isn't what it looks like.** Live today: `/procurement-services-uae/`
   (22,091 imps — the site's most-seen URL) and `/procurement/` both 301 to
   `/our-services/procurement/`; `/engagement-team-2/` 301s to
   `/engagement-team/`. And `/synergi-uae-2-2/` 301s to `/` — **because it IS
   the live front page** (`page_on_front` = 320, "Synergi UAE"; verified 2 Sep
   once production Novamira came back). That 301 is WordPress's canonical
   redirect, not a rule — it evaporates the moment launch points the front
   page at the new Home page, which is why the explicit Yoast rule and the
   staging trashing in §5.2 are mandatory, not optional.

---

## 1. Existing SEO performance

**Twelve-month totals** (pages report): 993 clicks · 76,384 impressions ·
CTR 1.30% · average position ≈ 19.4. Desktop 70% of clicks. By country: UAE 67%
of impressions (51,274) but only 0.93% CTR; Lebanon 189 clicks at 28% CTR
(brand); India 88 clicks; US 7,038 impressions at pos 14 (the generic
procurement cluster ranking abroad); KSA 1,624 impressions at pos 35.

### 1a. Clusters — what ranks, where it lives, what it earns

| Cluster | Queries | Clicks | Impressions | Held today by |
|---|---|---|---|---|
| Brand ("synergi…") | 93 | 201 | 2,085 | Homepage, `/our-leadership/`, `/contact-us/` |
| Procurement & sourcing | 369 | 23 | 41,627 | `/our-services/procurement/` (after live consolidation of 3 URLs) |
| BPO / outsourcing / shared services | 140 | 23 | 5,383 | Homepage (UAE/Gulf), blog posts |
| HR / payroll | 40 | 0 | 2,686 | Nothing dedicated — scattered |
| Marketing | 20 | 0 | 637 | One blog post |
| Other (informational, people, misc) | 270 | 9 | 2,668 | Blog, `/our-leadership/` |

By market: **UAE** 221 queries / 25 clicks / 7,533 imps. **KSA** 19 queries /
0 clicks / 707 imps. **GCC/Gulf/MENA** 27 queries / 0 clicks / 278 imps.

### 1b. Keywords to protect (currently earning clicks or page-1 positions)

| Query | Clicks | Imps | Pos | Held by |
|---|---|---|---|---|
| synergi / synergi ae / synergi bpo | 186 | 1,315 | 1–8.8 | Homepage |
| bpo companies in uae | 7 | 371 | 6.6 | Homepage |
| bpo in uae | 3 | 248 | 6.6 | Homepage |
| bpo services in uae | 2 | 53 | 2.6 | Homepage |
| procurement outsourcing companies | 5 | 504 | 13.8 | Procurement page |
| procurement companies in dubai | 4 | 271 | 10.9 | Procurement page |
| procurement operations services | 0 | 1,348 | **4.5** | Procurement page |
| procurement services | 0 | 1,193 | 7.2 | Procurement page |
| sourcing and procurement providers | 0 | 1,705 | 7.0 | Procurement page |
| bpo | 2 | 691 | 5.6 | Homepage |
| sevag alexandrian / mohamad saker (people) | 6 | ~90 | 4–6 | `/our-leadership/` |

Note "synergi" itself sits at **position 8.79** — page one but not top for the
exact brand name (ambiguity with "synergy"-named companies). Protecting and
improving the brand SERP is itself a launch objective (Organization schema,
consistent site name, unchanged NAP).

### 1c. High impressions, low CTR (the conversion problem)

The whole procurement cluster is this problem: ~41,600 impressions → 23 clicks
(0.06% CTR), much of it at positions 4–12 where CTR should be 3–12%. The page's
title ("Procurement Services Across the GCC | Synergi") doesn't speak the
searcher's language — the query phrasings are "providers", "companies",
"outsourcing", "operations services". A title/meta rewrite on **one page** is
the single highest-leverage SEO action available (§7, quick wins). The
homepage's 21,870 impressions at 2.94% CTR are the second.

### 1d. Ranking close to page one (pos 8–20, ≥100 impressions — push candidates)

hr bpo companies in uae (967 @ 9.8) · procurement services companies (1,150 @
11.1) · procurement services providers (1,038 @ 11.6) · sourcing services (918
@ 10.7) · bpo companies in dubai (664 @ 11.5) · procurement logistics services
(782 @ 11.7) · sourcing providers (609 @ 13.1) · bpo companies (233 @ 13.9) ·
procurement outsourcing companies (504 @ 13.8) · bpo services uae (116 @ 14.4)
· procurement bpo firms (538 @ 14.9) · procurement providers (967 @ 15.6) ·
bpo companies in saudi arabia (246 @ 15.6) · sourcing services uae (215 @
16.3) · procurement bpo services (701 @ 16.3) · procurement services firms
(934 @ 16.4) · gulf bpo (127 @ 16.8) · hr bpo companies in dubai (662 @ 17.7)
· shared marketing services (220 @ 20.8).

### 1e. Queries where a dedicated (or strengthened) page could win

| Opportunity | Evidence | Answer |
|---|---|---|
| **HR BPO — UAE** | hr bpo companies in uae 967 @ 9.8 · in dubai 662 @ 17.7 · hr bpo uae 244 @ 9.8 — all 0 clicks; no page targets them | UAE market page with an HR-weighted section (§3), plus HR service page retitle (§4). A dedicated HR-BPO-UAE page only if these stall at pos ~8 after 90 days |
| **KSA outsourcing** | bpo companies in saudi arabia 246 @ 15.6 · outsourcing companies in riyadh 93 @ 29 · hr bpo saudi/ksa 212 imps @ 40+ | `/markets/saudi-arabia/` (built, on staging) — §3 |
| **Gulf/Middle East BPO** | gulf bpo 127 @ 16.8 · procurement bpo middle east · back office outsourcing middle east | `/markets/` GCC hub (built, on staging) — §3 |
| **Marketing shared services** | marketing shared services 269 @ 9.2 · shared marketing services 220 @ 20.8 — carried by the best-performing blog post (26 clicks) | Strengthen `/our-services/marketing/` + interlink from the post; no new page |
| **Supply-chain BPO** | supply chain bpo companies 214 @ 35 · vendors 104 @ 51 | Page 3–5 today; longer-term content play off the two existing supply-chain posts |
| **Fractional CMO / CXO** | fractional cmo uae 15 @ 62 · uae fractional cmo services 28 @ 13 · strategic cmo solutions uae 30 @ 12 | CXO-as-a-service is in the profile; covered by `/our-solutions/fractional-leadership/` (built) — needs metadata + these exact phrasings |
| **Payroll — Syria** | payroll outsourcing in syria 136 @ 10.7 · outsource payroll small business syria 147 @ 22 | Keep the Syria blog post exactly as is. **No Syria page**: the company profile lists no Syria office (see §4d discrepancy) |

### 1f. Cannibalisation — current and future

| Collision | State | Action |
|---|---|---|
| 3 procurement URLs | **Resolved on live** (both 301 → `/our-services/procurement/`); rules present in staging table | Keep; do not regress (§5.3) |
| Homepage vs `/synergi-uae-2-2/` | **Resolved on live** (301 → `/`); but the page is still **published on staging** and would come back at launch | Trash on staging + carry the redirect (§5.2 — critical) |
| Homepage vs `/markets/` (GCC) | New — both could target "BPO in the GCC" | Homepage goes brand-led (§2); `/markets/` owns the explicit GCC query |
| KSA: legacy landing page vs `/markets/saudi-arabia/` vs Riyadh post | Open question §6 | Evidence-based answer in §3c: 301 the legacy URL to the market page — it holds no measured equity |
| HR service page vs 2 HR blog posts | Mild | Internal-link hierarchy (§5.8) |
| Shared services: `/shared-services-uae/` vs 2 posts vs homepage band | Mild | Solution page is canonical; posts link into it |

---

## 2. Recommended website targeting

### The principle

The homepage's search equity is **brand + UAE-BPO**. The procurement equity —
the largest on the site — already lives on a service page and is already
generic/international. So the homepage can be generalised **safely** provided
the UAE cluster is re-housed first. That is exactly the sequencing the business
asked for, and the data confirms it is the only cluster the homepage transition
puts at risk.

### The two-phase homepage transition

**Phase 1 — at launch.** The homepage keeps its UAE & Gulf keyword signals
while everything else changes around it (theme, content, structure). One change
in a migration at a time.

- Title: `Synergi | BPO & Shared Services in the UAE, Gulf & Beyond` (55
  chars). Brand moves first (helps the weak brand SERP), "UAE" and "Gulf"
  retained, "Beyond" begins the story.
- H1: keep the current default — "BPO Services in UAE & the Gulf to Power Your
  Business". It is already the staging field default, so this is zero work.
- Body: the rebuilt homepage already mentions the Gulf naturally in its section
  headings; no extra geo-stuffing. Add one "Where we operate" reference linking
  the three market pages and Global Locations (§5.8).

**Phase 2 — post-launch, gated on evidence, not on a date.** When GSC shows
`/markets/united-arab-emirates/` is the URL Google returns for "bpo companies
in uae" and its siblings (check the Pages filter monthly; expect 60–90 days):

- Title: `Synergi | Business Process Outsourcing & Shared Services` — fully
  international, brand-led.
- H1: `BPO & Shared Services to Power Your Business` — the minimal edit of the
  existing line: geography out, everything else intact.
- Body keeps exactly one geographic sentence, in the profile's own language:
  home-grown in the GCC, delivery centres onshore and offshore — plus the
  market-page links. If after 90 more days the UAE page has **not** taken over
  the UAE queries, Phase 2 waits; the homepage does not give up rankings to a
  page that hasn't earned them. This implements the business's own instruction:
  nothing leaves the homepage until its replacement demonstrably works.

### Where each keyword family lives (target map)

| Family | Lives on | Not on |
|---|---|---|
| Brand, "bpo company", "shared services", category-generic | Homepage | — |
| bpo/outsourcing + UAE/Dubai/Abu Dhabi | `/markets/united-arab-emirates/` (new) | Homepage (after Phase 2) |
| bpo/outsourcing + Saudi/KSA/Riyadh | `/markets/saudi-arabia/` | Homepage, legacy landing page |
| GCC / Gulf / Middle East explicit | `/markets/` hub | Homepage title |
| procurement (all phrasings, geo and generic) | `/our-services/procurement/` | Homepage, any market page's title |
| hr bpo / hr outsourcing / payroll | `/our-services/human-resources/` (generic) + UAE market page (geo) | — |
| marketing shared services / fractional CMO | `/our-services/marketing/` + `/our-solutions/fractional-leadership/` | — |
| shared services + geo | `/shared-services-uae/` (kept at its URL, per stage-7) | — |
| informational (how/why/what) | Blog posts, each linked up to its pillar page | Service pages |

**Industry pages: do not build them.** The 12 months of query data contain
almost no industry-phrased demand ("bpo for hospitality" and kin are absent).
Industries stay as bands on the homepage and market pages, fed by content that
already exists. Revisit only if the quarterly review shows industry queries
appearing. This also honours the architecture rule against inventing page
types.

---

## 3. Market-page strategy

The evidence supports **three** market pages plus the hub — no more. Qatar
(≈0 query demand), Romania, Lebanon (brand-only demand) and Kuwait (2 imps) are
rows on Global Locations, not pages. Build pages when demand appears in GSC,
not before. All three use `templates/market.php` and existing bands — zero new
theme code.

### 3a. `/markets/` — the GCC hub *(built on staging; needs metadata)*

- **Primary:** business process outsourcing GCC. **Supporting:** gulf bpo (127
  imps), bpo middle east, back office outsourcing middle east, outsourcing
  companies gulf, shared services GCC.
- **Intent:** commercial-investigation, regional buyer comparing providers.
- **Title:** `Business Process Outsourcing in the GCC | Synergi` (49) — keep
  the existing page title; it is right.
- **Meta:** `BPO, shared services and managed functions across the Gulf — UAE,
  Saudi Arabia and Qatar — from a GCC-born provider with onshore and offshore
  delivery centres.` (158)
- **H1** (= page title): `Business Process Outsourcing in the GCC`.
- **Outline** (existing bands): hero (lede as stored — it is good) → story "One
  back office across several Gulf entities" (stored) → services band (six
  lines, from the `services` record) → industries band → why band → FAQ
  ("outsourcing in the GCC" set, stored) → insights band (assign: Gulf/GCC
  posts) → final CTA.
- **Internal links in:** homepage "Where we operate", footer markets column,
  both child market pages' "other markets", `/global-locations/`.
  **Out:** both child market pages, all six service pages, `/case-studies/`.
- **Replaces/supports:** takes the explicit-GCC queries the homepage half-holds
  today; supports both child pages.

### 3b. `/markets/united-arab-emirates/` — NEW; the launch-critical page

The largest re-housing job: ~7,500 impressions across 221 UAE queries,
including the entire HR-BPO-UAE cluster (≈1,900 imps) that today has no home.
The profile provides more UAE delivery evidence than any other market: five of
the twelve case studies are UAE engagements (sovereign-investor payroll for
750+ staff, government-entity on-site HR, hospitality-group HR transformation,
franchise HR BPO, Abu Dhabi HR function build), the Abu Dhabi leadership
office, and the 2023 UAE incorporation.

- **Primary:** bpo companies in uae (371 @ 6.6 — page 1 already, via the
  homepage). **Supporting:** bpo services uae/in uae/dubai · bpo company in
  dubai · outsourcing companies in abu dhabi/dubai · **hr bpo companies in uae
  / dubai / hr bpo uae** · business solutions uae · professional outsourcing
  services uae.
- **Intent:** commercial, local-buyer; "companies/providers in …" phrasing —
  a compare-and-shortlist search.
- **Title:** `BPO Companies in UAE: BPO Services in Dubai & Abu Dhabi | Synergi`
  (should render ≤60 in most SERPs; alternative: `BPO Services in the UAE –
  Dubai & Abu Dhabi | Synergi` at 48).
- **Meta:** `Synergi delivers BPO, HR outsourcing, payroll, procurement and
  shared services for organizations in the UAE — from our Abu Dhabi base,
  serving Dubai and every emirate.` (159)
- **H1** (= page title): `BPO Services in the United Arab Emirates` — with the
  eyebrow "United Arab Emirates" (mirroring the Saudi page's pattern).
- **Outline** (existing bands, mirroring the built Saudi page):
  1. Hero — lede: incepted in the UAE in 2023, leadership in Abu Dhabi,
     functions run for organizations across the emirates (all profile facts).
  2. Story — "What UAE organizations outsource first": multi-entity back
     offices, growth outpacing support functions; pillars: delivered-in-UAE
     track record, onshore + offshore delivery, one engagement lead.
  3. Services band from the `services` record — HR first (the demand), then
     accounting, procurement, technology & AI, marketing, project management.
  4. **HR-weighted evidence section** (the case-study band): the
     sovereign-portfolio payroll engagement (750+ staff, anonymised per open
     question 1) — directly answering "hr bpo companies in uae".
  5. Industries band — investment holdings, government & semi-government,
     hospitality & F&B, luxury retail (all evidenced in the profile's UAE
     engagements).
  6. Why band (from the shared record) → FAQ ("Questions about outsourcing in
     the UAE": mainland vs free-zone delivery, UAE labour-law compliance in
     payroll — profile-supported topics only) → insights band (UAE posts:
     smart-procurement-UAE, supply-chain-disruption-UAE, shared-services-UAE-GCC)
     → final CTA.
- **Internal links in:** homepage (prominent, Phase 1 onward), `/markets/`,
  Saudi page, HR + procurement service pages, the three UAE blog posts,
  footer. **Out:** HR service page (first link in body), other services,
  `/markets/`, UAE case studies, `/contact-us/`.
- **Replaces/supports:** inherits the homepage's UAE targeting in Phase 2;
  `/synergi-uae-2-2/`'s redirect repoints here (§5.2) — currently → `/`, so
  this is an improvement, not a URL change.

### 3c. `/markets/saudi-arabia/` — built on staging; needs metadata + the collision decision

- **Primary:** bpo companies in saudi arabia (246 @ 15.6). **Supporting:**
  bpo services ksa/riyadh · outsourcing companies in riyadh · best outsourcing
  company in riyadh · hr bpo companies in saudi / ksa · (context) Vision 2030,
  PIF — evidenced by the ELM-group engagement in the profile and the PIF-2030
  blog post.
- **Intent:** commercial, market-entry and localisation buyers.
- **Title:** `BPO Services in Saudi Arabia (KSA & Riyadh) | Synergi` (52) —
  deliberately inherits the legacy landing page's proven phrasing.
- **Meta:** `Consulting, manpower augmentation and full outsourcing for
  organizations in Saudi Arabia — HR, payroll, procurement and technology,
  delivered in the Kingdom.` (155)
- **H1** stays `BPO Services in Saudi Arabia` (the current page title).
- **Outline:** already built and filled on staging — hero, story ("What
  organizations in Saudi Arabia are building"), services, industries
  (semi-government, PE/family offices), why-in-the-Kingdom band, the ELM
  functional-outsourcing case study (anonymised), KSA FAQ, insights (Riyadh
  post, PIF post), CTA. Content review only; no rebuild.
- **The collision decision (open-questions §6), answered with data:** the
  recommendation on file was "keep the old URLs — they have the history." The
  GSC export now shows `/bpo-services-in-saudi-arabia-ksa-riyadh/` has **no
  measurable history**: absent from the pages report (the report includes pages
  down to 1 impression), no internal links since the footer rebuild, raw
  Elementor. The KSA equity actually sits in the Riyadh blog post and the
  homepage. **Recommendation, superseding the earlier one: 301 the legacy URL
  → `/markets/saudi-arabia/`** and let the market page + the interlinked
  Riyadh/PIF posts carry the cluster. CLAUDE.md §2.8 requires a written record
  for any URL change — this section and its evidence are that record, pending
  the business sign-off the open question asked for.
- **Internal links in:** homepage, `/markets/`, UAE page, Riyadh post + PIF
  post (add a link from each), HR service page, footer. **Out:** services,
  KSA case studies (`/case-studies/outsourcing-service-line-design-ksa/`,
  `/case-studies/shared-services-assessment-family-office/`), `/contact-us/`.

**Not the same text with the flag changed:** the two country pages already
diverge structurally — Saudi leads with build-and-commercialise consulting
evidence; UAE leads with run-it-for-you HR/payroll BPO evidence. That is what
the delivery history actually looks like in the profile, and it is the
differentiation Google needs.

---

## 4. Content recommendations

### 4a. Remains as is (protect — do not rewrite during migration)

- **All 24 blog posts**, at unchanged URLs. Three are ranking assets:
  `why-marketing-belongs-in-shared-services…` (26 clicks — best post),
  `why-businesses-in-riyadh…` (1,909 imps), `bpo-in-syria…` (525 imps + the
  two Syria payroll queries). Touch only to add pillar links (§5.8).
- `/shared-services-uae/` — kept at its URL on the solution template
  (stage-7); its geographic title is deliberate.
- `/our-services/procurement/` **content** — but see 4b: its metadata/H1 need
  care, and its TEST case-study placeholder must go.
- The six service pages' bodies, the About family, Contact, Global Locations —
  all newly built and approved.

### 4b. Revise (with the reason)

| Page | Revision | Why |
|---|---|---|
| Homepage | Two-phase generalisation per §2 | The ask; sequenced to protect the UAE cluster |
| `/our-services/procurement/` | Title/meta rewrite toward searcher phrasing: title `Procurement Outsourcing & Procurement Services Company | Synergi`; meta leading with "provider of end-to-end procurement and sourcing services". **Page title (→ H1) becomes "Procurement Services"** (menu label stays "Procurement" — WP menu labels are independent) | 41,627 imps @ 0.06% CTR; the live H1 is keyword-rich, the rebuilt H1 is one word — the cluster is too valuable to hand a weaker heading |
| `/our-services/human-resources/` | Page title → `HR Outsourcing`; Yoast title `HR Outsourcing & HR BPO Services Company | Synergi` | The 2,686-imp HR cluster phrases as "hr bpo companies"; the current page targets none of it |
| `/engagement-team/` | Absorb what made `/our-leadership/` earn 84 clicks @ pos 5.3: the named people users search for | The 301 transfers the URL's equity only if the destination answers the query. **Confirm**: "Sevag Alexandrian" is searched (74 imps) but is not in the profile's team list — who is this and do they belong on the page? |
| `/about-us/` | Verify it answers what `/our-approach/` ranked for (1,510 imps @ pos 5) before the 301 lands | Same principle as above |
| `/our-services/marketing/` | Add a shared-services-marketing angle and a link from the star post; consider the "fractional CMO" phrasing (profile: "Fractional Chief Marketing Officer") | 489 imps across marketing-shared-services queries land on a post, not the service |
| `/our-solutions/fractional-leadership/` | Metadata targeting "fractional c-level / fractional CMO UAE" | Matching queries exist; page has no Yoast meta |

### 4c. Moves

- UAE-specific claims/copy on the homepage → `/markets/united-arab-emirates/`
  (Phase 2).
- Anything worth keeping from the legacy KSA landing page → fold into
  `/markets/saudi-arabia/` before its 301 (review its 714 words once; most is
  superseded).

### 4d. New content to create (all sourced from the profile)

1. `/markets/united-arab-emirates/` — full page per §3b.
2. Yoast titles + descriptions for the **21 objects currently missing them on
   staging** (verified): homepage, `/markets/`, `/markets/saudi-arabia/`,
   `/our-solutions/` + 4 solution pages (all but Shared Services UAE),
   `/case-studies/`, `/our-services/project-management/`, and all 12 case
   studies; plus the missing Yoast title on the Riyadh post.
3. A real case study (or an emptied band) to replace the **TEST placeholder
   still live in the Procurement page's case-study fields on staging** — it
   says "TEST — must be replaced before launch" and it is still there.
4. FAQ sets for the UAE market page (profile-supported topics only).

### 4e. Unsupported or contradicted claims — need confirmation before use

| Item | The problem |
|---|---|
| **Office list: Damascus vs Romania** | The theme's `locations` record and open-questions §4 list Abu Dhabi, Riyadh, Doha, Beirut, **Damascus**. The company profile (p.6 map and p.20 addresses) lists Abu Dhabi, Riyadh, Doha, Beirut, **Bucharest (Romania)** — and no Syria office. Global Locations and the LocalBusiness schema cannot ship until one list is confirmed. |
| Client names | Open question 1 — everything stays anonymised until answered (staging already complies). |
| "Sevag Alexandrian" | Searched 74× ; not in the profile's engagement team. Confirm role before featuring. |
| Contact details / domain | Open question 3 — profile shows KSA and Lebanon phone numbers and `@synergibpo.com` emails; which are public, and which domain to show pre-move, is unanswered. |
| Figures | 50+ clients · 5 delivery locations · 100+ years combined · 10–15% savings — all profile-verified and already in the `figures` record. No new figures may be invented beyond these. |

---

## 5. SEO migration plan

Runs inside `migration-plan.md`'s option C runbook (theme from Git + scripted
content transfer). Steps below are the SEO layer, in order.

### 5.1 Complete URL disposition — every URL Google saw this year

Every URL in the 12-month GSC pages report, verified against live (HTTP) and
staging (database). "Same" = renders at the identical URL on the new theme.

**Unchanged (36 URLs):** `/` · `/contact-us/` · `/global-locations/` ·
`/about-us/` · `/engagement-team/` · `/blog/` · `/media/` (noindex cleared at
launch) · `/executive-podcast/` · `/our-services/` + all six service pages ·
`/shared-services-uae/` · `/hr-digital-transformation-guide/` ·
`/procurement-readiness/` · `/privacy-policy/` · `/terms/` · `/connect/` · all
ranked blog posts and category archives.

**Redirects already live in production (keep; verify in staging table):**

| Origin | Target | In staging table? |
|---|---|---|
| `/procurement-services-uae/` | `/our-services/procurement/` | ✓ |
| `/procurement/` (+ 5 legacy variants) | `/our-services/procurement/` | ✓ |
| `/engagement-team-2/` | `/engagement-team/` | ✓ |
| `/synergi-uae-2-2/` | `/` | **✗ — not a rule at all**: it is the live front page, so WP canonically redirects it; the redirect dies at launch and the page is still published on staging. Add an explicit rule + trash (§5.2) |
| `/our-approach/technology/` | `/our-services/technology-ai/` | **✗ — WP 404-permalink guessing**, not a managed rule; make it an explicit rule |
| `/our-approach/healthcare/` | a JPG file (WP guessing matching an attachment) | ✗ — add an explicit rule to `/about-us/` |

**New redirects at launch (staging table, already present):**
`/our-approach/` → `/about-us/` · `/our-leadership/` → `/engagement-team/`.

**Redirects to add before launch:**

| Origin | Target | Why |
|---|---|---|
| `/synergi-uae-2-2/` | `/markets/united-arab-emirates/` (or keep `/`) | Preserve the live redirect; repointing to the UAE page is topically better — decide once, in writing |
| `/bpo-services-in-saudi-arabia-ksa-riyadh/` | `/markets/saudi-arabia/` | §3c decision (pending sign-off) |
| `/category/humen-resource/` | `/category/human-resources/` **directly** | Currently a 2-hop chain (humen-resource → human-resource → human-resources); flatten |
| `/our-approach/*` children (real-estate, financial-services, hospitality, travel-tourism, energy, alternative-investments) | `/about-us/` | Already 404 on live but still earning impressions; cheap recovery |
| `/our-services/customer-experience-solutions/` | `/our-services/` | 404 on live, 30 imps |
| `/our-ecosystem/` | `/about-us/` | 404 on live, 3 clicks historically |
| `/synergi-partners-…-icxi-2/`, `/privacy-policy-2/` | their canonical twins | "-2" duplicates with impressions |
| `/corporate-social-responsibility/` | `/about-us/` (verify status first) | Unreachable during checks; resolve in the pre-launch crawl |

**Leave as 404:** `/headers/`, `/ot_mega_menu/` (builder artifacts), the
thank-you machine-slug (stage-7 decision 2), `/eih-ethmar…/` + `/category/eihnews/`
(content removed during the year), uploads .jpg/.png SERP strays.

**New URLs at launch (index fresh):** `/markets/` · `/markets/saudi-arabia/` ·
`/markets/united-arab-emirates/` · `/our-solutions/` + 5 solution pages ·
`/case-studies/` + 12 studies + 5 term archives.

### 5.2 Pages that must change on staging before launch

1. **Trash `/synergi-uae-2-2/`** and add its redirect — otherwise launch
   *resurrects* a page live already redirects, complete with its double `<h1>`
   and a Slider Revolution shortcode for a deactivated plugin.
2. Trash `/bpo-services-in-saudi-arabia-ksa-riyadh/` once §3c is signed off,
   adding its redirect.
3. Replace or empty the Procurement page's TEST case-study fields.
4. Delete `/human-resources-rebuild/` if still present (review copy; it is not
   in the published list — verify it is gone).

### 5.3 The redirect-table merge — RESOLVED 2 Sep

Production Novamira came back on 2 Sep and live's
`wpseo-premium-redirects-base` was read and diffed. Result: **staging's table
is live's table plus the two new rules** (`our-approach → about-us`,
`our-leadership → engagement-team`), minus one junk rule live carries
(`page-not-found → 404-2`) that should not be re-added. The redirects observed
on live that are in neither table are WordPress-native behaviour (canonical
front-page redirect, 404 permalink guessing), not managed rules — which is
exactly why §5.1's additions must be made *explicit* Yoast rules before
launch: native behaviour does not survive the front-page switch. Ship
staging's table + the §5.1 additions; re-verify every rule is a single-hop
301 to a 200 in the rehearsal. Re-run this diff once more in the launch
window in case production gains rules between now and then.

### 5.4 Metadata and H1s

- Write the 21 missing Yoast titles/descriptions (§4d.2) — on staging, before
  the content transfer, so they travel with it.
- The homepage must go live with a real Yoast title + description (§2 Phase 1);
  today it has none (the staging noindex hid the gap).
- H1 policy is already architectural (template-emitted, exactly one). Fix the
  known double-H1 pages by the §5.2 trashing (`/synergi-uae-2-2/`) and by
  checking `/connect/` after its Elementor content is reviewed.
- Retitle Procurement and Human Resources pages per §4b (page title = H1).
- Titles under 60 characters; flag, don't silently truncate (CLAUDE.md §8).

### 5.5 Canonicals, sitemap, robots

- Yoast owns canonicals; the theme emits none (verified architecture). Spot-
  check canonical on: homepage, one service, one market, one case study, one
  term archive, `/blog/` page 2 (pagination).
- At launch, on production: `blog_public` stays 1; clear the homepage and
  `/media/` page-level noindex (already in the stage-8 runbook); **verify the
  case-study post type and `syn_case_service` term archives appear in Yoast's
  sitemap** — new types default on, but confirm, and confirm `/connect/`
  remains indexable-but-unlinked as decided.
- `/privacy-policy/`, `/terms/`, `/procurement-readiness/` are deliberately
  noindexed on staging — confirm that is wanted on production (they earned 3
  clicks total; noindex is defensible, just make it a decision).
- Production robots.txt is sane (verified); it needs no change at launch.
  Keep staging's `Disallow: /` (§6).

### 5.6 Images and alt text

Media-library additions travel with the transfer (migration-plan). Alt text for
every rendered image was written 1 Sep (stage-7 §9); decorative flags carry
empty alt correctly. At the pre-launch crawl, re-run an images-without-alt
check on the golden pages only — the library-wide backfill remains deliberately
out of scope (the images are being replaced).

### 5.7 Structured data

- **Organization/LocalBusiness:** planned for the contact/locations context and
  not yet built (stage-7 leftover) — build after the office-list discrepancy
  (§4e) is resolved, checking Yoast isn't already emitting an equivalent
  (CLAUDE.md §8). Yoast's site-representation Organization schema should carry
  the logo and the `social` record's profiles as `sameAs` — this is also the
  brand-SERP play (§1b).
- **FAQPage:** live on 15 pages via the field group; the Yoast-duplication
  check is already architectural. Verify none of the migrated pages carries a
  Yoast FAQ block too.
- **Article:** Yoast emits it on posts; no theme work.
- Validate the golden pages in the Rich Results test at launch (+ the two
  market pages).

### 5.8 Internal-link migration (concrete list)

- Homepage → the three market pages + Global Locations ("Where we operate").
- Riyadh post + PIF post → `/markets/saudi-arabia/` (one contextual link each).
- UAE posts (smart-procurement-UAE, supply-chain-UAE, shared-services-UAE-GCC)
  → `/markets/united-arab-emirates/` and/or the matching service page.
- Marketing-shared-services post → `/our-services/marketing/` +
  `/shared-services-uae/`.
- Syria post: leave targeting as is; add one link to `/our-services/human-resources/`
  (its payroll queries are HR demand).
- Odoo post → `/our-services/technology-ai/` (verify; likely exists).
- Service pages ↔ market pages: smallest implementation is a link inside an
  existing FAQ answer or the related-insights band — no new theme sections.
- Footer already carries the pages people want (stage-7); add the markets
  column when the UAE page ships.

### 5.9 404 and redirect-chain prevention

Pre-launch, on the rehearsal clone (migration-plan step 4): crawl staging with
a site crawler; then **replay this GSC export's 69-URL page list plus the live
sitemap's 43 URLs** against the clone and assert every one returns 200 or a
single-hop 301 to a 200. The two known chain risks: the humen-resource
category chain (§5.1) and any rule whose target itself becomes a redirect
(our-approach/technology → technology-ai is fine; verify after the merge).

### 5.10 Analytics and GSC verification

- GSC property (`https://synergi.ae/`) is URL-unchanged — **no change of
  address, no re-verification needed**. Do not touch the property.
- This export (dated 1 Sep 2026, in the repo) **is the pre-launch benchmark**;
  keep it immutable. Record the launch date as a GSC annotation externally
  (GSC has no annotations — keep a dated log in the repo).
- Site Kit: finish setup on production at launch (stage-8 item); its Search
  Console connection must point at the production property, not staging's.
- GTM/GA4 continue through `inc/integrations.php`, deferred; container IDs are
  options, not code — verify IDs on production after switch.
- The two ASE tag snippets firing on staging (`G-F8BHKGB935`, LinkedIn pixel)
  must not travel — they need the two wp-admin clicks (known limitation:
  post_status via API is ignored for ASE snippets).
- Submit the (regenerated) sitemap in GSC within the launch hour; then watch
  Coverage daily for week one.

### 5.11 Benchmarks to compare before/after

Recorded now, from this export (12 months to 1 Sep 2026; last-30-days proxy =
Aug 2026):

| Metric | 12-month | Aug 2026 |
|---|---|---|
| Clicks / impressions / CTR / position (site) | 993 / 76,384 / 1.30% / ~19.4 | 85 / 12,093 |
| Homepage | 644 clicks / 21,870 imps / 2.94% / 15.0 | — |
| Procurement (3 URLs combined) | 101 / 41,894 | — |
| `/our-leadership/` (→ engagement-team) | 84 / 3,310 / pos 5.27 | — |
| Brand: "synergi" | 153 clicks / pos 8.79 | — |
| UAE cluster (221 queries) | 25 / 7,533 | — |
| KSA cluster (19 queries) | 0 / 707 | — |
| HR cluster (40 queries) | 0 / 2,686 | — |

Also request before launch (§8): indexed-page count from GSC Coverage, and
monthly WPForms/Bit Integrations submission counts as the conversion baseline
(GA4 data was not provided).

---

## 6. Staging safeguards — verified 1 September, nothing changed

| Check | State | Verdict |
|---|---|---|
| `blog_public` | `0` (Discourage indexing) | ✓ |
| Homepage page-level noindex | set | ✓ deliberate; **clear at launch** |
| `/media/` page-level noindex | set | inherited 2024 flag; **clear at launch** |
| `robots.txt` | `User-agent: * / Disallow: /` | ✓ blocks crawling |
| Staging in Google today | not directly checkable from here | see below |

Two honest caveats, reported not fixed:

1. `Disallow: /` and noindex work against each other in one edge case: a
   staging URL discovered through an external link can be indexed *URL-only*,
   because the crawl block prevents Google reading the noindex. Low risk in
   practice; the check is one `site:staging.synergi.ae` search, and the
   staging Search Console property (Site Kit created one) can confirm zero
   indexed pages. Worth doing once now and once the week before launch.
2. There is no access barrier (no HTTP auth/IP allowlist) — indexing
   protection is real but *privacy* protection is none: anyone with the URL
   can browse staging. Acceptable if accepted knowingly.

**Post-launch reminder (already implied by the plan, restated because it is
the single worst possible mistake):** the content transfer must never carry
`blog_public=0` or the noindex flags to production — migration-plan's
"must NOT travel" table already says so; §5.10's Coverage watch is the
backstop that would catch it within a day.

---

## 7. The prioritized plan

### A. Critical before launch (blocking, in order)

1. ~~Fix production Novamira~~ — **resolved 2 Sep**: the connection is back,
   the redirect diff is done (§5.3), and the runbook is unblocked.
2. **Sign off the collision decisions** (§3c + §5.2): KSA legacy URL → market
   page; `/synergi-uae-2-2/` trashed with redirect. Data attached; needs a yes.
3. **Resolve the office-list discrepancy** (Damascus vs Bucharest, §4e) —
   blocks Global Locations content and LocalBusiness schema.
4. Build `/markets/united-arab-emirates/` (§3b) — the homepage cannot
   generalise without it; on the existing template, no theme code.
5. Write the 21 missing Yoast titles/descriptions + homepage title/meta
   (§5.4); replace the Procurement TEST case study (§4d.3).
6. Apply the redirect additions and the chain flatten (§5.1) on staging's
   table; then, post-Novamira-fix, the live diff and merge (§5.3).
7. Retitle Procurement + HR pages (§4b) so H1s carry the clusters.
8. Pre-launch crawl + GSC-URL replay on the rehearsal clone (§5.9).

### B. Needed for launch (with the window)

Per migration-plan's runbook, plus: clear both noindex flags, verify sitemap
includes the new post type + term archives, golden-page schema validation,
Site Kit finished, sitemap resubmitted, ASE staging snippets confirmed off.

### C. Can follow launch (first 30 days)

Internal-link pass on the blog (§5.8) · rebuild
`/hr-digital-transformation-guide/` and `/procurement-readiness/` off raw
Elementor · `/connect/` H1 cleanup · author-archive noindex decision ·
LocalBusiness schema once offices confirmed · footer markets column.

### D. Quick SEO wins (highest leverage per hour, all before or at launch)

1. **Procurement title/meta rewrite** — 41.6k impressions sitting at pos 4–16
   behind a title that doesn't match the queries. This is the single biggest
   lever on the site.
2. Homepage title Phase 1 (§2) — 21.9k impressions at 2.94% CTR.
3. The humen-resource chain flatten + the eight cheap recovery redirects
   (§5.1) — an hour of work.
4. Engagement-team content check against the `/our-leadership/` queries — 84
   clicks/year riding on one 301.
5. Fractional-leadership metadata (§4b) — queries exist, page exists, zero
   connection today.

### E. Longer-term growth (quarter two and beyond)

- **Convert procurement visibility into page-1 positions**: one substantial
  supporting article per top sub-theme (procurement operations, sourcing
  services, procurement BPO — phrasings the data hands over), all linking to
  the service page. The cluster's 369 queries are the deepest well the site
  has.
- Push the pos-8–20 list (§1d) page by page via the monthly review.
- Supply-chain BPO cluster (pos 35–60 today) off the two existing posts.
- A dedicated HR-BPO-UAE page **only if** the UAE market page stalls at pos
  ~8–10 on the HR cluster after 90 days.
- Market pages for Qatar/Kuwait **only when** GSC shows demand.
- The Arabic phase multiplies all of this; the theme is structurally ready.

### F. Risks that could cost rankings — and the prevention already in the plan

| Risk | Prevention |
|---|---|
| Homepage generalises before the UAE page can catch the cluster | Two-phase transition, evidence-gated (§2) |
| Staging redirect table overwrites live's | Diff-and-merge rule (§5.3) |
| `/synergi-uae-2-2/` resurrected at launch | §5.2.1 |
| Procurement H1 weakens from keyword-rich to one word | Retitle (§4b) |
| `noindex`/`blog_public=0` reaching production | migration-plan's exclusion table + day-one Coverage watch |
| 84 leadership clicks lost through a hollow 301 | §4b engagement-team check |
| Redirect chains | §5.9 replay assertion |
| New URLs (markets, solutions, case studies) launching with no metadata | §5.4 |
| Yoast + theme double-FAQ schema | Architectural check, re-verified §5.7 |

### G. KPIs and the 30/60/90 monitoring plan

**KPIs:** non-brand clicks/month (baseline ≈ 5) · sitewide CTR (1.30%) ·
procurement-cluster clicks (23/year) · UAE-cluster position share held by
`/markets/united-arab-emirates/` · KSA-cluster average position (35+) · brand
"synergi" position (8.79) · indexed pages (baseline from Coverage, to be
pulled) · form submissions/month (baseline to be pulled).

- **Days 1–30 (stability):** GSC Coverage daily for week one, then twice
  weekly — watch for 404 spikes and any staging-flag accident. Verify the ~15
  redirects transfer in the Pages report (old URL impressions falling, new URL
  rising). Confirm the two market pages + case studies are indexed. Success =
  no cluster loses >20% of impressions month-over-month beyond seasonality.
- **Days 31–60 (transfer):** Check which URL Google serves for the UAE and KSA
  head terms (Pages filter per query). Procurement CTR after the retitle —
  expect movement here first. If `/markets/saudi-arabia/` isn't indexed or
  ranks below the trashed legacy URL's redirect, investigate before Phase 2.
- **Days 61–90 (Phase 2 gate):** If the UAE page holds the UAE cluster →
  execute the homepage Phase 2 title/H1 (§2) and re-benchmark. Quarterly
  review: the §1d push list re-scored, new-query discovery (industry demand?
  Qatar demand?), and the E-list priorities re-ordered on evidence.

---

## 8. What I still need (and exactly why)

1. ~~Production Novamira fixed~~ — **resolved 2 Sep**; the diff in §5.3 is
   done. Re-run it once in the launch window.
2. **Page-filtered GSC query exports** for `/`, `/our-services/procurement/`,
   and `/our-leadership/` (Performance → Pages → select page → Queries tab →
   export, same 12-month window). The aggregate export cannot prove which
   queries belong to which page; I inferred from titles and positions, and the
   homepage-transition gates in §2 should rest on the real mapping.
3. **GSC Coverage/Indexing counts** for both properties (production and the
   staging property Site Kit created) — the indexing baseline, and the
   staging-leak check (§6).
4. **GA4 landing-page and conversion data** (12 months) — organic value per
   page is invisible in GSC; the benchmark table has a hole where conversions
   belong. Failing GA4, monthly WPForms/Bit Integrations submission counts.
5. **Answers to open questions 1, 3, 6** (client naming · contact details and
   domain · collision sign-off) and the **office-list confirmation** (§4e) —
   each blocks a named item above.

---

## 9. Execution record — 2 September 2026

The business answered on 2 Sep and the staging content + SEO list was executed
the same day. Decisions received, all applied:

- **KSA collision:** legacy `/bpo-services-in-saudi-arabia-ksa-riyadh/` trashed,
  301 → `/markets/saudi-arabia/`. (§3c's recommendation, signed off.)
- **`/synergi-uae-2-2/`:** trashed on staging, 301 → `/markets/united-arab-emirates/`.
- **`/shared-services-uae/`:** the business chose to 301 it to the UAE market
  page. The Shared Services solution page moved to
  `/our-solutions/shared-services/` (title "Shared Services Design & Set-Up");
  the solutions record, menu and footer follow. This supersedes
  `sitemap-and-navigation.md` §4 and is the written record CLAUDE.md §2.8 asks
  for. GSC context: the URL had 4 clicks / 235 impressions in 12 months.
- **Offices:** Bucharest stays; **Damascus added with the `badge` field set to
  "Coming soon"** (the record's designed mechanism). Riyadh and Beirut phones
  added from the profile. **Emails: none exist in any source** — the business
  wants them on `@synergibpo.com`, so the actual addresses are still needed
  before the email slots can be filled (§10.1).
- **Client names:** stay anonymised (confirmed).
- **Sevag Alexandrian:** unknown to the business; left off Engagement Team.
- **Homepage:** country keywords removed. The shared-services band's hard-coded
  note ("…Riyadh and across KSA", linking the trashed KSA page) was a Stage-5
  leftover with no field override — fixed in theme code
  (`sections/shared-services.php`, commit `52ca1f5`), deployed to staging via
  the build-zip flow. The note now points at `/markets/`. Saudi now appears on
  the homepage only in the Markets menu and the office list.

### Done on staging, verified

1. **All 21 missing Yoast titles/descriptions written** (homepage, 2 market
   pages, 4 solutions, solutions/case-studies listings, Project Management, 12
   case studies) plus the Riyadh post's title.
2. **Retitles:** Procurement page → "Procurement Services", HR page → "HR
   Outsourcing" (H1s), with the §4b Yoast titles; slugs unchanged; menu labels
   explicitly reset to "Procurement" / "Human Resources".
3. **`/markets/united-arab-emirates/` built and filled** per §3b (page 10681,
   market template, all bands, anonymised sovereign-portfolio payroll case
   study, 4 UAE FAQs). Added to the `markets` record and to the header menu
   under Markets, between GCC and Saudi Arabia.
4. **Saudi market page fixed:** its services list had Project Management twice
   and no Marketing.
5. **Redirects:** 16 new rules created via `WPSEO_Redirect_Manager` (§5.1's
   full list: the three above, our-approach/* children, technology→technology-ai,
   customer-experience-solutions, our-ecosystem, CSR, the two "-2" duplicates)
   and the `humen-resource` chain flattened to a direct rule.
   `shared-services-uae-2` retargeted off the trashed KSA page. All verified as
   single-hop 301s to 200s. Table now 35 rules.
6. **Procurement TEST case study emptied** — the band hides itself.
7. **Content sweep:** every published post plus the three content-rendering
   pages stripped of absolute `staging.synergi.ae` URLs (hundreds of
   occurrences — these would have shipped to production); dead internal links
   repointed in 8 posts; pillar links added to 7 posts (PIF and Riyadh → KSA
   market page; UAE posts → UAE market page; marketing post → marketing
   service + shared services; Syria post → HR; Odoo post → Technology & AI).
   Templated pages' dead Elementor post_content deliberately left untouched —
   it never renders and is part of the §2.9 rollback.

### Still open after this pass

1. **Office emails** — the addresses themselves (on `@synergibpo.com`) from
   the business; slots exist and are empty.
2. **Photographs** — the business is replacing all photos next; the UAE page
   reuses existing attachments meanwhile.
3. Page-filtered GSC exports, Coverage counts, GA4/conversion baselines (§8).
4. The launch-window items (§7B) — unchanged.

### Addendum — 2 Sep, second pass (case bands and remaining placeholders)

A full postmeta sweep found TEST case bands beyond Procurement: the Marketing,
Technology & AI and Accounting service pages, and the Build-Operate-Transfer,
Carve-Out and Fractional Leadership solution pages. Resolution, per the
business rule "real case study where one exists, empty where none does":

- **Filled from the built case studies** (title, client, brief, four scope
  lines, and a "Read the case study" link to the study's own URL):
  Technology & AI ← `hrms-sourcing-implementation-gcc` · Marketing ←
  `marketing-bpo-business-destination` · Accounting ←
  `accounting-bpo-digital-assets` · Project Management ←
  `shared-services-assessment-family-office`. HR already carried a real one;
  Systems Implementation and Shared Services already had real content.
- **Emptied** (no matching study; the band hides itself): Procurement,
  Build-Operate-Transfer, Carve-Out & Integration, Fractional Leadership.
- **Metadata upgraded with GSC phrasing** on the three service pages whose
  titles predated the data: Marketing → "Marketing Shared Services & Marketing
  BPO" (269 + 220 imps on those phrasings), Technology & AI → "ERP, Automation
  & IT", Accounting → "Accounting Outsourcing & Bookkeeping Services" (leaf
  services from the profile).
- Remaining page metas (About, Contact, Global Locations, Engagement Team,
  Blog, Media, Podcast, Our Services) were reviewed against GSC and left as
  written — no cluster contradicts them.

**Image upload sizes** (from the theme's own rendering): hero band `full` at
100vw → 1920×1080+ WebP under ~300 KB; case-study band `large` at ~40vw cover
→ 1600×1200 (min 1200 wide); location cards `large` at ≤31rem → 1200×900 (min
1000 wide); listing cards crop to 720×405 (16:9) → upload ≥1440×810.
