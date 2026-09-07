# Stage 6 — what is left, re-planned

> **SUPERSEDED, 1 September 2026.** Stage 6 and Stage 7 are both closed. Read
> `stage-7-decisions.md` for what the structure now is, and `migration-plan.md`
> for what Stage 8 has to do. This file is kept as the record of how the build
> was sequenced, not as a description of the site.
>
> **What changed since this was written on 28 Aug.** Everything in "the remaining
> build order" below shipped except where noted:
>
> - **6d, the three missing records** — `social` shipped. `partners` and `events`
>   were dropped on 31 Aug: partners stay on the homepage band with no shared
>   record, and Upcoming Events is deferred with nothing depending on it.
> - **6e, the homepage** — shipped, minus the Upcoming Events section above.
> - **6f, About Us and its children** — About Us shipped. `/our-approach/` was
>   never rebuilt; its content was old and not rendering, so the page is a draft
>   and the URL now 301s to `/about-us/`. `/our-leadership/` was built, then
>   trashed on 1 Sep by decision, and 301s to `/engagement-team/`.
> - **6g, Contact Us** — shipped 28 Aug.
> - **6h, the Solutions template, listing and five pages** — all shipped.
> - **6i, the listing pages** — all four shipped.
> - **"Not Stage 6 — a stage of its own"** — half done. **Case studies became the
>   `syn_case_study` post type on 1 Sep**, with a term archive per service line
>   and all twelve URLs unchanged, reversing decision D4. Podcast episodes are
>   still not a post type and still have no URLs of their own.
>
> One piece of D4's promise is outstanding: `templates/service.php` still shows
> one hand-typed case study rather than querying its own. The query function and
> the terms exist; the template is not wired to them.

Written 28 August 2026, after 6a, 6b and 6c shipped. Supersedes the sub-stage
list in `stage-6-scope.md` §6, which stopped at 6e and did not account for the
pages the content architecture asks for.

Source of truth for structure: **`sitemap-and-navigation.md`** (26 Aug) — the
decision record mapping every row of the architecture sheet to a page, a
section, a record or a post type. This file is the build order that follows
from it.

---

## The question this answers: Stage 6 or Stage 7?

**Stage 6.** Stage 7 is *"navigation and structure decisions"* — building the
menus and acting on the nine decisions in `synergi-build-plan.md` §6. It does
not build pages.

Stage 6 is templates and fields. Every remaining item below is a template, a
section or a record, so it belongs here. Stage 8 then does what it says —
migrating the *content* of pages whose templates already exist, then launch.

The original Stage 6 scope missed this because it listed the three templates it
knew about (service, solutions, market/guide) and left About Us, Contact Us,
Media and the listing pages to "migration". That was wrong: those pages need
sections built before there is anything to migrate into.

---

## Where the build actually is

**Rebuilt on the new theme — 8 pages:**
the homepage, the six service pages, and `/human-resources-rebuild/` (a review
copy, delete before launch).

**Still Elementor — everything else:** About Us · Our Approach · Our Leadership ·
Engagement Team · Global Locations · Contact Us · Our Services listing · Media ·
Executive Podcast · Blog · Connect · Shared Services UAE · BPO Services KSA.

**Records built:** `services`, `figures`, `locations`, `why`, `why_cards`,
`final_cta`.
**Records still missing:** `partners`, `events`, `social` — deferred in 6a, and
now blocking three separate pieces of work.

**Templates built:** `homepage.php`, `service.php`, `about.php`, `people.php`
(the last two on 27–28 Aug; their pages still need creating and filling).
**Post types:** none. Case studies and podcast episodes are both post types in
the architecture and neither exists.

---

## Every row of the architecture sheet, against reality

### 1. Homepage — built, with a change list outstanding

| Row | State |
|---|---|
| A brief about what we do | Built. **Outstanding:** delete the legacy keyword paragraph under the hero |
| The services | Built and editable, but the six cards are **hard-coded** — the one part of the homepage still needing a developer |
| Industries we serve | Built, field-editable ✓ |
| Synergi in numbers | Built, reads `figures` ✓. **Outstanding:** show the as-at date |
| Our partners | Built, field-editable. **Outstanding:** needs the `partners` record and must link to `/our-partners/`, which does not exist |
| The locations | Built, reads `locations` ✓. **Outstanding:** show entity and function |
| Upcoming events | **Does not exist.** New section, not in the design source, so it needs designing. Needs the `events` record |
| Social media latest posts | Built as the Instagram band. **Outstanding:** needs the `social` record and must link to `/contact-us/#social` |

### 2. About Us — templates built, pages not yet filled

| Row | Becomes | State |
|---|---|---|
| The team | Page `/our-leadership/` | `templates/people.php` ✓ with its fields. Page still Elementor, 215w |
| Vision, mission, values | Section of About Us | Built ✓ — `sections/story.php` (mission and vision as flip cards) and `sections/values.php` (the wheel), redesigned 28 Aug |
| Our approach | Page `/our-approach/` | **Not started.** No template, no sections. 1,118w, the biggest page on the site |
| Key figures | `figures` record → section | Record ✓, `sections/numbers.php` ✓, and `templates/about.php` now places it ✓ |

### 3. Our Services — done, except the case studies

All six pages built on `templates/service.php` at their real URLs. The listing
page `/our-services/` (307w) is still Elementor. The yellow *Case studies* row is
a **post type**, deliberately out of Stage 6 (decision D4).

### 4. Our Solutions — nothing built

The listing `/our-solutions/` does not exist. Of the five solutions, one
(`/shared-services-uae/`, 459w) exists and gets rebuilt at its current URL —
`sitemap-and-navigation.md` §4 explains why it must not be split in two — and
four are new pages.

### 5. Media — nothing built

`/media/` is a 30-word stub. The podcast archive exists as a page. Case studies
have no archive. Both podcasts and case studies are post types that do not exist.

### 6. Contact Us — nothing rebuilt

87 words, Elementor. Three sections wanted: locations with email (record ✓,
section ✓), an enquiry form, and social accounts (needs the `social` record).
**Blocked** on synergi.ae vs synergibpo.com — the sheet says so itself.

---

## The remaining build order

Numbered to continue the existing scheme. Dependencies are real: each unlocks
the next.

### 6d — the three missing records
`partners`, `events`, `social`. Small, and nothing below moves without them.
Unblocks the partners band, the events section and Contact Us.
**Blocked by:** nothing.

### 6e — finish the homepage
The four changes in `stage-6-scope.md` §2d, plus the new Upcoming events
section, plus wiring the services deck to the `services` record and the social
band to the `social` record. Ends with the homepage needing no developer at all.
**Blocked by:** 6d. Partly by the locations question in `open-questions.md` (entity and
function per office), and Upcoming events needs a design pass — it is not in the
design source.

### 6f — About Us, and its two children
About Us on a template composing vision/mission/values and the figures band.
`/our-leadership/` for the team. `/our-approach/` rebuilt — 1,118 words, the
single heaviest content migration in the project.
**Blocked by:** nothing structural.

**Where it got to, 28 Aug.** `templates/about.php` and `templates/people.php`
both exist, with their field groups. The About bands then had a design pass on
28 Aug, because as first built they were three stacks of words rather than the
page the company's own deck draws:

- **Mission and vision** are photographic flip cards — the name on the picture,
  the statement on the back, turned by pointer or keyboard. Below 62rem, and on
  any touch screen, the picture simply sits above the words.
- **The values** are discs on a ring around the photograph above 66rem, placed
  from an index over a count so a fifth or a fourth value needs no developer.
  Four to six get the wheel; any other number keeps the card grid.
- **The journey** is the deck's own drawing: a pale rail, the year inside each
  disc, names alternating above and below on dotted leads. Off the navy, so the
  page alternates white / paper / white / paper / navy down its length. It
  scrolls with its rail past eight stops and stacks below 75rem.
- **The why band** is rendered here rather than written again — the same record
  the homepage and the six service pages read.
- The milestone defaults were corrected against the slide: the UAE belongs to
  2023 and Lebanon to 2024. **Wants a check from someone who was there.**

**Still open in 6f:** `/our-approach/` has no template and no sections — it is
the 1,118 words and it has not started. The two photographs About Us needs
(mission, vision) and the values photograph are not uploaded.

### 6g — Contact Us
Three sections. Reuses `sections/locations.php`. The enquiry form goes through
the consolidated form plugin, not theme code (CLAUDE.md §11).
**Blocked by:** the domain decision, and which emails and phone numbers are
public — questions 3 and 4 in `open-questions.md`.

### 6h — the Solutions template, listing and five pages
Was 6d in the old numbering. One template, five pages, one of them a rebuild at
an existing URL.
**Blocked by:** whether the five Solutions pages have copy — question 5 in
`open-questions.md`.

### 6i — the listing pages
`/our-services/`, `/our-solutions/`, `/our-partners/` (new), `/media/`. All four
are the same shape: a short intro over a grid that reads a record or a query,
so they are one template with four sets of content, not four templates.
**Blocked by:** 6d for partners, 6h for solutions.

### Not Stage 6 — a stage of its own
Case studies and podcast episodes as post types with their archives, per
decision D4: new content types come after the templates are proven, and
probably after the domain move. The service pages already carry a one-per-page
case study field, so nothing is blocked by waiting.

---

## What to do first

**6d, today.** Three records, no blockers, and it unblocks both 6e and 6g.

Then **6f**, which is the largest pile of real content and is blocked by nothing
— getting `/our-approach/`'s 1,118 words moved early takes the biggest single
risk out of the schedule.

6e, 6g and 6h all wait on answers in `open-questions.md`. That file is now the
critical path for three of the six remaining pieces, which is worth saying out
loud: **the build is no longer waiting on code.**
