# Stage 7 — the decision record

Closed 1 September 2026. This is the written record `synergi-build-stages.md`
requires before Stage 7 can be tagged: each of the nine structural decisions in
`synergi-build-plan.md` §6 either implemented or explicitly deferred, plus the
decisions Stage 6 made in passing and never wrote down.

Everything below was applied on **staging only**. Production is untouched.

---

## The nine decisions

| # | Decision | Outcome | State |
|---|---|---|---|
| 1 | `/synergi-homepage-2026-draft-build/` is published and indexable | Trashed. It was a prototype and nothing links to it. | **Done** |
| 2 | The machine-slug "Thank You" page | **Left to 404, deliberately.** Verified first that nothing depends on it: the WPForms confirmation is an inline message, not a redirect, and no published page links to it. | **Done** |
| 3 | Two contact pages — `/contact-us/` and `/connect/` | **Both kept.** `/connect/` was set to draft on 31 Aug; that was reversed on 1 Sep because a draft returns 404 and the URL is indexed. It is now **published and out of every menu** — a utility URL, exactly as `sitemap-and-navigation.md` §2 specified. The QR codes in circulation keep working and it is ready for the booth. | **Done** |
| 4 | Three content hubs overlap | **Media is the hub.** Blog and Executive Podcast keep their own archives and appear as children of Media in the navigation. `/media/`'s inherited 2024 `noindex` must be cleared at launch — it is on the Stage 8 runbook. | **Done** |
| 5 | The ICXI partnership page is news built as a page | **Converted to a blog post at the identical URL**, and filed under Synergi News. Permalinks are `/%postname%/`, so the URL did not move. | **Done** |
| 6 | Ten drafts: publish, delete or leave | Seven trashed. `/our-approach/` and `/our-leadership/` remain but are now redirected (see below). `press-releases` stays a draft — it is unfinished content, not structure. | **Done** |
| 7 | Four menus, one assigned | Only **Main Menu** survives, assigned to `primary`. 29 items, every one verified as returning 200. | **Done** |
| 8 | The Element Pack mega-menu | **No mega-menu.** Element Pack is deactivated and the navigation is theme code (`inc/nav.php`, `parts/nav.php`). True in practice since Stage 6; recorded here because it was never written down. | **Done** |
| 9 | 806 of 906 images have no alt text | **No bulk backfill** — the pictures are being replaced and 459 of the 914 images are attached to nothing. Alt text is written per page as each page is migrated. Done on 1 Sep for all 11 photographs the site actually renders; the five country flags correctly carry `alt=""` because they are decorative and the city and country sit beside them in text. | **Done** |

---

## URL disposition

Every URL touched in August, and what happens to it. §2.8 forbids *moving* URLs;
these are removals, and each one gets a deliberate answer rather than a 404 by
accident.

| URL | Was | Now | Why |
|---|---|---|---|
| `/our-approach/` | published, 1,118 words, indexed | **301 → `/about-us/`** | Content was old and not rendering. The redirect keeps whatever the URL earned until the page is rebuilt. |
| `/our-leadership/` | published, indexed | **301 → `/engagement-team/`** | Reverses `sitemap-and-navigation.md` §2, which named this the survivor. The business decided on 1 Sep that Engagement Team is the page that stays. §2 is superseded by this row. |
| `/connect/` | drafted 31 Aug | **published, out of the menus** | See decision 3. |
| `/full-episodek6vl…/` | published under a generated slug | **404** | See decision 2. |
| `/synergi-homepage-2026-draft-build/` | published | **404** | A prototype. Nothing links to it. |
| `/our-solutions/shared-services-design/` | built 28 Aug | **404** | A duplicate of `/shared-services-uae/`, trashed 31 Aug. Every field that referenced it was repointed on 1 Sep. |
| `/our-solutions/carve-out-integration/` | — | **as shipped** | `sitemap-and-navigation.md` §7 predicted `carve-out-and-integration`. The shorter slug shipped. The record is amended to match the URL; the URL does not move. |

The redirects are Yoast Premium rules, created through `WPSEO_Redirect_Manager`
and verified as single-hop 301s to a 200.

---

## Decisions Stage 6 made and never recorded

**Case studies are a post type, not pages.** Decision D4 in
`stage-6-remaining-plan.md` held `syn_case_study` back to a stage of its own and
twelve studies shipped as pages instead. The business reversed that on 1 Sep so
a service page could list its own studies automatically. Built the same day:
`inc/case-study-post-type.php`, `single-syn_case_study.php`,
`taxonomy-syn_case_service.php`. **All twelve URLs are unchanged** — verified
individually before and after. `/case-studies/` stays an ordinary page with its
editable intro (`has_archive` is false on purpose), and five new term archives
exist at `/case-studies/service/{reference}/`.

**Podcast episodes remain without URLs of their own.** Deferred, knowingly. The
Media dropdown points at `/executive-podcast/`, which lists episodes as content
rather than linking to a page each. This is the remaining half of D4 and is not
blocking anything.

**The footer links stay hard-coded.** The `footer` menu location is registered
and deliberately unassigned. Footer links change rarely and keeping them in
`footer.php` keeps them in Git and in review. `footer.php`'s own header called
this out as a Stage 7 handover; this is the answer.

**Two site records were never built.** `partners` and `events` do not exist in
`syn_records`. Partners stay on the homepage band with no shared record (31 Aug),
and Upcoming Events is deferred — it is one record plus one section and nothing
depends on it.

---

## Accessibility check

`synergi-build-stages.md` asks for the navigation to be "correct on desktop and
mobile, fully keyboard-operable". A **code audit** was done on 1 Sep and passes
on every point that can be checked without a browser:

- The skip link is the first focusable element (`header.php`).
- `:focus-visible { outline: 3px solid var(--syn-focus) }` is global and never
  removed anywhere in the stylesheets.
- Submenu toggles are real `<button>` elements, and `aria-expanded` is kept
  truthful on the hover path as well as the click path.
- `Escape` closes submenus and the mobile panel, and returns focus to the toggle.
- The open mobile panel traps Tab and Shift+Tab between its first and last
  focusable element.
- The closed mobile panel is `inert`, so its links are neither tabbable nor
  exposed to assistive technology while off-screen.
- `prefers-reduced-motion` removes the focus delay rather than hiding content.
- The nav carries `aria-label="Primary"`.

**Not verified, and outstanding:** that the focus ring is actually visible
against the navy header at the required contrast, real screen-reader
announcement, and behaviour on a physical touch device. These need a browser and
a person; they belong in the Stage 8 accessibility pass.

---

## What Stage 7 deliberately did not do

Stage 7 builds no pages. These are named so Stage 8 does not discover them:

- Service pages still show one hand-typed case study rather than querying their
  own. The query function and the terms exist; `templates/service.php` is not
  wired to them yet.
- Four pages still render raw Elementor content:
  `/bpo-services-in-saudi-arabia-ksa-riyadh/`, `/synergi-uae-2-2/`,
  `/procurement-readiness/`, `/hr-digital-transformation-guide/`. The two legal
  pages are fine as they are.
- 22 pages have no Yoast title or description — everything built in August.
- No `LocalBusiness`, `Service` or `Article` schema. `FAQPage` works on 15 pages.
- `/connect/` and `/synergi-uae-2-2/` each render two `<h1>` elements, both from
  stored Elementor content under a template that also emits one.
- The ICXI post has no featured image and no SEO title, because it arrived as a
  page.

## Still open, and not Stage 7's to close

- **The landing-page collision** (`open-questions.md` §6). Undecided, pending
  Search Console data. It is not live: the `/markets/` pages exist only on
  staging, so nothing is competing today. It must be settled before Stage 8
  publishes them.
- **The launch path.** `migration-plan.md` recommends a scripted content
  transfer over a full database push. Not yet a decision.
- **Production Novamira returns 404.** Blocks Stage 8 entirely.
