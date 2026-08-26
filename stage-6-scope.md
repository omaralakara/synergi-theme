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

## 4. Decisions I need before building

These are cheap to answer now and expensive to reverse later, because they
determine URLs and template count.

**D1 — Are Solutions and Services the same shape?**
A solution page and a service page may want the same template with different
content, or genuinely different layouts. If the same: one `templates/service.php`
serves eleven pages. If different: two templates, and Stage 6 grows by about a
day. *My recommendation: start them on one template. Splitting later is cheap;
maintaining two near-identical templates is not.*

**D2 — Does "Shared services design & set-up" replace the Shared Services UAE
market page, or sit beside it?**
There is already a "Shared Services UAE" market page in the inventory, and
homepage section 03 is about shared services. Three things with the same name is
a navigation problem, not a template problem, but it changes what Stage 6
builds. *This one I cannot decide — it is a content decision.*

**D3 — Is the homepage structure a change list or a replacement?**
If the sections it does not mention (shared services, why Synergi, blog,
podcast, final CTA) are meant to be removed, the homepage shrinks and some
Stage 5 work is dropped. *My reading is that it is a change list and everything
stays, but confirm before I act on it.*

**D4 — Case studies, episodes and events: real pages or records?**
My recommendation, on the "does it need its own URL?" test:
- **Case studies → custom post type.** They are read on their own, linked from
  two places, and will grow in number.
- **Podcast episodes → custom post type.** Guest bios and transcripts are a
  page's worth of content each.
- **Events → site record**, not a post type. An event is a date, a title, a
  place and a link; the structure says it "leads to Media" rather than to an
  event page.

Note that CPTs add URLs. §2.8 forbids *changing* existing URLs; adding new ones
for genuinely new content is allowed, but they should be created **after** the
domain move if there is any doubt, so nothing is published twice at two domains.

---

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
