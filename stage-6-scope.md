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
