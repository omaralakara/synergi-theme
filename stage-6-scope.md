# Stage 6 — scope, after the stakeholder content structure

Written 26 Aug, when the content structure arrived from the business. It changes
what Stage 6 has to produce, so the scope is set down here before any code is
written rather than discovered halfway through.

---

## 1. Are we on the right approach?

Yes — and the structure document is the strongest evidence so far, for one line
in it:

> **Key figures — same numbers as the homepage. One set, used everywhere.**

That sentence is only cheap to satisfy in a field-driven theme. In Elementor
every page holds its own copy of those numbers, so "one set, used everywhere" is
a promise somebody has to keep by hand, every time, for ever. Here it is a
single record that several templates read. The same is true of the locations
(homepage, Contact Us, Global Locations) and the partners (homepage band,
partners page).

Two more lines confirm the direction:

- **"Case studies — for each service, to add a Case study underneath."** A thing
  that appears under a service *and* in Media is content with two homes. A
  template can do that; a page built by hand cannot without duplication.
- **"Adding a new service line: fill in the fields, it comes out matching the
  others."** The plan already promised this. Six service lines through one
  template is exactly the case it was designed for.

So: the architecture holds. What changes is the **size** of Stage 6, and one
gap in how fields were specified. Both are addressed below.

---

## 2. What the structure changes

### 2a. Answered: there are six service lines, not five

CLAUDE.md §12a carried an open question — seven icons, five service pages, and
an instruction not to invent the sixth. The structure lists **HR, Technology &
AI, Marketing, Procurement, Accounting and Project Management**. Answered, and
recorded in CLAUDE.md. Homepage section 02 already renders all six. Every "the 5
service lines" in the build plan is out by one.

### 2b. A new page group that is in no plan: OUR SOLUTIONS

Five pages that do not exist today and appear in neither the build plan's §6
inventory nor the 26-page count:

- Shared services design & set-up
- Build–Operate–Transfer
- Systems implementation
- Carve-out & post-acquisition integration
- Fractional leadership

These are **new URLs**, which §2.8 permits — that restriction forbids *moving*
existing URLs, not adding new ones. They need a template of their own or a
shared one with Services (see decision D1).

### 2c. Three kinds of content that have no home yet

| Wanted | Appears on | Needs its own URL? |
|---|---|---|
| Case studies | under each service, and in Media | Yes — they are read on their own |
| Podcast episodes | Media, with guest bios and transcripts | Yes |
| Upcoming events | homepage section, and Media | Probably not |

### 2d. Four changes to homepage sections already built and approved

None is large; all are listed so none is forgotten.

| Section | Change asked for | Size |
|---|---|---|
| 01 hero | "one sentence for a business reader. **Delete the legacy keyword paragraph beneath it**" | copy only |
| 06 numbers | figures should be **dated** ("three to five dated figures") | one field + a line of markup |
| 07 partners | logos should **link to the partners page**, not off-site | small — the markup deliberately has no links today, see below |
| 08 locations | show **entity and function delivered**, not just city and country | two fields + markup |

The partners one is worth a note: section 07's logos were built as list items
rather than links *because the design source made them anchors with no href*,
which is not a link at all. Giving them a real destination is the fix that was
always wanted; the work is small and the accessibility reasoning still holds.

### 2e. One homepage section that does not exist: Upcoming events

The structure puts it between locations and social. It is not in the design
source, so it has no approved look — it needs designing, not porting.

### 2f. Sections in the built homepage the structure does not mention

Shared services (03), why choose Synergi (05), blog (09), podcast (11) and the
closing CTA (12) do not appear in the structure's homepage list. **This is
almost certainly because the list is a set of changes and priorities rather
than a full inventory** — but it must not be assumed. See decision D3.

---

## 3. The gap the structure exposed in how fields were specified

CLAUDE.md §7 described only **postmeta** — fields attached to one page. That is
right for a page's own eyebrow and lede, and wrong for anything the business
considers a single fact:

- key figures — homepage **and** About Us
- locations — homepage, Contact Us **and** Global Locations
- partners — homepage band **and** the partners page
- service lines — homepage cards, the navigation **and** six service pages

Stored as postmeta these become four or five copies that drift apart. That is
the same failure this project exists to escape, in a new place.

§7 has been rewritten with the rule: **if this changes, how many pages should
change with it?** More than one means it is a *site record*, stored once in a
single `syn_records` option and read wherever needed — not a custom post type,
because these need no URL, template or SEO of their own.

§7 also now says images are chosen with the media picker, never by slug — see
§5 below.

---

## 4. The four decisions — all answered 26 Aug

Two were answered by the business; two were delegated to me and decided in
`sitemap-and-navigation.md`, which is now the authority on structure. Kept here
as the record of what was asked and what was settled.

**D1 — one template.** `templates/service.php` carries both the six service
lines and the five solutions. Splitting later is cheap; maintaining two
near-identical templates is not.

**D2 — combine, at the existing URL.** Decided after reading the live page.
`/shared-services-uae/` is 459 words, holds the Yoast focus keyword *"Shared
Services UAE"*, and its headings ("Our Capabilities", "Get Started with Shared
Services UAE") are already offer content. **It is the solution page, thin, not a
different page.** Building a second one would put two pages on the site
competing for the same query — cannibalising what the structure itself calls
*"our strongest offer and the one buyers search for"*. It is rebuilt on the
solution template at its existing URL, and folds into `/our-solutions/` with a
301 at the domain move, which is a redirect event anyway. Full reasoning:
`sitemap-and-navigation.md` §4.

**D3 — a change list.** Nothing is removed from the built homepage. Shared
services (03), why Synergi (05), blog (09), podcast (11) and the closing CTA
(12) all stay.

**D4 — as recommended.** Case studies and podcast episodes become custom post
types; events become a site record. The requirement that a case study have *one
central source* and appear under both a service and Media without duplication is
met by a `syn_case_service` taxonomy: the service page queries its own term, the
Media hub queries all of them. One record, three surfaces, nothing copied. See
`sitemap-and-navigation.md` §3.

**Still open, and not blocking:** whether all five Solutions pages have copy
written. It decides whether 6d builds five pages or one template and one page —
the template is identical either way, so 6a, 6b and 6c proceed regardless.

## 5. Can the homepage be edited from WordPress after Stage 6?

Yes. That is precisely what step **6b** below delivers, and it is worth being
exact about what does and does not become editable.

**Becomes editable, in the page editor, by anyone with access:**
- every heading, paragraph, eyebrow, label and button text on the homepage
- every photograph and logo, chosen through the normal WordPress media picker
- every link destination
- the number of rows in a repeatable group — add a seventh industry, remove a
  partner, reorder the figures

**Deliberately does not become editable:**
- layout, colour, spacing, type size, the order of sections

That second list is not an omission. It is the reason the site stops drifting:
an editor changing words cannot change the design by accident. Changing the
design is a developer task, one file, in Git, reviewable.

### On images specifically

Today six sections resolve their images by **slug**, through
`syn_attachment_id_by_slug()`. That was right for building against a fixed set
of uploads and is wrong for handing over — it means changing a photograph
requires knowing what a slug is and uploading a file that produces the right
one.

Stage 6 replaces every one of those with an **image field**: a thumbnail, a
"Choose image" button, a "Remove" button, using the media modal already in
WordPress. The slug lookup stays only as the fallback when a field is empty, so
nothing breaks on the day the fields ship.

---

## 6. The order to build in

**6a — the fields engine.** `inc/fields.php`: simple fields, the JSON repeater
with its vanilla-JS admin UI, image fields, link fields, and the `syn_records`
site-record store with its Settings screen. Nonces, capability checks, per-leaf
sanitise on save, escape on render. Nothing visible changes yet.

**6b — retrofit the homepage onto fields.** Every `$args` default in the twelve
partials becomes a field; every slug lookup becomes an image field. **This comes
before any new template on purpose:** the homepage is already built and visually
approved, so if the engine has a flaw it shows up against a known-good page
instead of while a new template is also being debugged. It is also the step that
answers "can we edit the homepage from WordPress" — after 6b, yes.

**6c — the service template.** Build Human Resources fully. Then build **Project
Management** — the newly confirmed sixth line — entering content only. If it
needs one code change, the template is not done. Project Management is the
better second test than Accounting, because it is the line with no existing
page, so it proves the template can create as well as replace.

**6d — the solutions template**, per decision D1.

**6e — the four homepage changes** in §2d, once fields exist to carry them.

New content types (D4) are **not** Stage 6. They are a stage of their own after
the templates are proven, and probably after the domain move.

---

## 7. What this does to the schedule

Stage 6 as originally written was three templates plus fields. It is now:

| | Original | Now |
|---|---|---|
| Templates | 3 | 3–4 (D1) |
| Pages served | 11 | 16 |
| Fields | postmeta only | postmeta **and** a site-record store |
| Homepage | untouched | retrofitted onto fields (6b) |

Roughly half as much again. The retrofit (6b) is the addition that pays for
itself: it is the only way to find out whether the fields engine is good before
committing four templates to it.

If a week slips, the cut order from the build plan still applies — the guide
template merges into `page.php` first. Add to the front of that list: **6d
(solutions) can ship after launch** if the five solution pages do not exist as
content yet. Nothing else here is optional.

---

## 8. Decisions taken 27 Aug, before 6a began

Four scope questions were put to the business on 27 Aug with estimates attached.
All four were answered the same day. This section is the record; where it
conflicts with anything above, this section is later and wins.

### 8a. 6a ships a reduced record set — three, not six

`syn_records` is built in full as a mechanism, but only three records are
defined in v1:

| Built now | Why | Deferred | Why it can wait |
|---|---|---|---|
| `services` | Read by the six service pages and the Our Services listing — 6c cannot run without it | `partners` | Only `/our-partners/`, which does not exist yet |
| `figures` | Read by the numbers section and About Us; also the retrofit's test subject | `events` | Only the Media hub and a homepage section that is frozen |
| `locations` | Read by Contact Us and Global Locations | `social` | Only Contact Us and the footer |

The three deferred records cost roughly half an hour each to add once the
repeater exists, because they are the same mechanism with different field
definitions. Defining them now would mean building three Settings groups that
no template reads for several weeks.

**Honest note on `locations`:** it is the one of the three that nothing in the
MVP actually consumes — Contact Us and `market.php` are both outside this
scope. It is kept because it is the record most likely to be needed next and
the marginal cost is small. If 6a runs long, it is the first thing to drop.

### 8b. The `numbers.php` safety retrofit is approved

One section — `sections/numbers.php` — is wired to read the `figures` record
during 6a, with its current `$args` defaults kept as the empty-value fallback.
Estimated 1.5 hours.

**This is the only part of the frozen homepage that 6a touches, and it changes
nothing visible.** The retrofit contract from 6b applies in miniature: same
markup, same classes, same payload, proved by diffing the rendered HTML before
and after.

**Why it is worth 4% of the stage:** 6b was originally sequenced first because
it tested the fields engine against a page that already looked right. With the
homepage frozen (§8e), that test disappears and the engine's first consumer
becomes a brand-new template — two unknowns at once. One section restores the
test at one-twelfth the cost. If the record shape is wrong, this is where it
surfaces, before `templates/service.php` is built on top of it.

### 8c. The service page gets a real design, consistent with the homepage

The gap found on 27 Aug: **there is no service page design anywhere in this
repo.** `design-source/templates/` holds one file, `homepage-content.html`. Yet
`synergi-build-stages.md` and the handoff brief both carry a verification item
reading "the HR page matches the service prototype". There was no prototype to
match.

Decided: **design it, using the `design` skill, structurally consistent with the
approved homepage.** Not a fresh visual direction — the homepage is approved and
the service pages are its relatives. Concretely:

- **One template, six pages.** All six service lines render through
  `templates/service.php`. They differ in content and photographs only. A
  seventh line added later inherits the design with no code change — that is
  the promise in `synergi-architecture-explained.md` and this is where it gets
  tested.
- **Composed from existing sections wherever one fits.** `why.php`,
  `numbers.php`, `blog.php` and `final-cta.php` already exist, are approved and
  are on-brand. Reusing them keeps the service page inside the design system by
  construction rather than by discipline, and it is CLAUDE.md §4's rule anyway:
  *templates compose sections, they do not contain section markup.*
- **New partials only where nothing fits.** Expected: a capabilities grid and
  the FAQ section in §8d. Anything beyond those two needs justifying.

The design pass happens at the start of 6c, before template code, and produces
something to approve rather than a page to react to.

### 8d. Every service page carries an FAQ

New requirement, and it is not in any earlier document. Every service page — and
by extension every solution page, since D1 gives them one template — ends with a
frequently-asked-questions block.

This is well-founded rather than novel: `/shared-services-uae/` already carries
an "FAQ" heading in its live content (`sitemap-and-navigation.md` §4), so the
pattern exists on the site today and is being made consistent rather than
introduced.

What it costs:

| Piece | Note |
|---|---|
| `_syn_faqs` field group | A repeater: question + answer. The answer sanitizes with `wp_kses_post()` — see CLAUDE.md §7b |
| `sections/faq.php` + its CSS | A new partial. Disclosure pattern, one question per row |
| `assets/js/sections/faq.js` | Accordion behaviour. Must be keyboard-operable, and must render every answer visible with JavaScript off (CLAUDE.md §10 definition of done) |
| `FAQPage` JSON-LD | **Only after confirming Yoast is not already emitting it** on that page. CLAUDE.md §8 now records this |

Estimated 2–3 hours, one time. Every page after the first is content entry.

### 8e. What stays frozen, and what is still open

**Frozen by the business, 27 Aug:** the homepage. 6b (the twelve-partial
retrofit) and 6e (the four content changes and the new events section) do not
run in this stage. The single-section retrofit in §8b is the deliberate
exception.

**6b must still land before handover.** A site where eleven page types are
editable and the homepage is the only one requiring a developer is not a
finished handover. It is deferred, not cancelled.

**Still open, and unchanged from §4:** whether the five Solutions pages have
copy written. It decides whether 6d builds five pages or one template and one
page. 6d is held until marketing answers. 6a and 6c proceed regardless.

### 8f. What this does to the estimate

Revised on 27 Aug, replacing the numbers in §7. Hours are elapsed working-loop
time — building, deploying to staging, testing, fixing — not one person's
labour.

| | Realistic hours |
|---|---|
| 6a, three records | 8 |
| `numbers.php` retrofit (§8b) | 1.5 |
| 6c, including the design pass (§8c) and the FAQ section (§8d) | 14 |
| Content entry, the four remaining service pages | 5 |
| **Active Stage 6** | **~28.5** |

At four hours a day with same-day review, that completes around **Monday 7
September 2026**. 6d, `market.php` and `guide.php` sit outside this and are
scheduled once the solutions copy question is answered.

The design pass and the FAQ section are what moved this from the 23 hours quoted
for MVP earlier the same day. Both were the business's call, both were made with
the cost stated, and neither is padding.
