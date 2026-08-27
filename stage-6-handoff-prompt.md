# Synergi theme — session handoff prompt

Paste everything below the horizontal rule into a new, clean Claude chat. It is
written to be self-contained: it says what the project is, what is already
built, what was decided, what to build next, and the rules that must not be
broken. It does not assume the new chat has seen any earlier conversation.

Written 26 August 2026, at the end of Stage 5. **Revised 27 August** after the
business narrowed the scope — see the block headed "Scope as of 27 August",
which overrides anything later in this brief that disagrees with it.

---

You are continuing an in-progress WordPress theme build. Read this whole brief
before doing anything, then confirm the rules and the stage goal back to me
before you write a single line of code.

## Scope as of 27 August — this overrides the rest of this brief

Five decisions were taken on 27 Aug, after this brief was first written. They
are recorded in full in `stage-6-scope.md` §8, which is the authority. Where
anything below contradicts them, they win.

1. **The homepage is frozen.** 6b (retrofitting the twelve homepage partials)
   and 6e (the four content changes and the new events section) **do not run in
   this stage**. Do not propose them. They are deferred, not cancelled — 6b must
   still land before handover, because a site where the homepage is the only
   page a developer must edit is not a finished handover.
2. **6a ships three records, not six** — `services`, `figures`, `locations`.
   `partners`, `events` and `social` are deferred; nothing built in this stage
   reads them.
3. **One section is retrofitted inside 6a**, as the deliberate exception to the
   freeze: `sections/numbers.php` reads the `figures` record, with its current
   `$args` defaults as the empty-value fallback. Nothing about the rendered
   homepage may change, and that is proved by diffing the HTML — not by looking
   at it. This exists because 6b was originally the fields engine's safety test
   and freezing it removed that test.
4. **The service page gets a design pass, using the `design` skill, at the start
   of 6c.** There is no service page design anywhere in this repo — the approved
   design covers the homepage and nothing else. It must be structurally
   consistent with the homepage rather than a new visual direction, reuse the
   existing section partials wherever one fits, and produce **one template that
   carries all six service lines**, differing in content and photographs only.
5. **Every service and solution page carries an FAQ.** A `_syn_faqs` repeater
   (question + answer), a new `sections/faq.php`, keyboard-operable, every answer
   visible with JavaScript off, and `FAQPage` JSON-LD **only** after confirming
   Yoast is not already emitting it on that page.

**Active Stage 6 is therefore 6a → 6c.** 6d (solutions), `market.php` and
`guide.php` are held until the business confirms whether the five Solutions
pages have copy written.

## 0. Read these files first, in this order

They are all in the project root, `c:\Users\USER\Desktop\synergi-blocks-theme\`.

1. **`CLAUDE.md`** — the build rules. Hard restrictions, design tokens, theme
   structure, security, performance budgets, SEO, accessibility, field rules,
   debugging standards. **This file overrides anything I say in chat.** If a
   chat instruction conflicts with it, stop and ask.
2. **`sitemap-and-navigation.md`** — the sitemap, the main menu, and what every
   row of the stakeholder content structure becomes (page, section, archive,
   post type, record). Decided 26 Aug. This is the authority on structure.
3. **`stage-6-scope.md`** — what the stakeholder content structure changed about
   Stage 6, and decisions D1–D4.
4. **`synergi-build-stages.md`** — Stages 0–9, each with its goal, prompt,
   verification checklist and rollback. Stage 6 is the section headed "Stage 6".
5. **`stage-5-measurement.md`** — the measured homepage payload, and which
   budgets currently fail and why.
6. **`synergi-build-plan.md`** — the full reasoning behind the project. Read for
   context, not for instructions; where it conflicts with CLAUDE.md or with
   `sitemap-and-navigation.md`, those two win.
7. **`synergi-architecture-explained.md`** — the approach in plain language.
8. **`reference/token-notes.md`** — how the design tokens were verified, and the
   `:root` cascade trap that produced two wrong values.
9. **`design-source/SOURCE.md`** — what the frozen design snapshot is, and what
   must never be shipped out of it.
10. **`Synergi - Company Overview - 2026.pdf`** — and the PowerPoint version of
    the same deck, if it is present in the folder. The official company profile.
    See §7 below for exactly how it may and may not be used.

The theme itself is in `synergi/`. Read `synergi/functions.php`,
`synergi/inc/sections.php` and two or three of `synergi/sections/*.php` before
touching anything — the section partials' docblocks are the specification that
Stage 6a's fields have to satisfy.

## 1. What this project is

A **hybrid WordPress theme** called `synergi`, replacing an Elementor build on
synergi.ae. PHP templates in Git render the site. `theme.json` holds every
design token. The block editor is used only for writing posts and simple pages.
Designed pages get their editable text and pictures from **hand-built custom
fields** — no ACF, no Meta Box, no paid plugin, no build step, no page builder.

**The one rule the whole architecture serves:** anything that describes how the
site looks lives in a file; anything a person wrote lives in the database.

The client is Synergi, a boutique Business Process Outsourcing provider
headquartered in Abu Dhabi with delivery locations across the GCC, Lebanon and
Romania.

## 2. Where the build is right now

Stages 0–5 are **complete, verified and tagged** in Git (`stage-1-done` through
`stage-5-done`, on branch `main`). Specifically:

| Stage | What exists |
|---|---|
| 0 | Staging site, Git repo, fallback theme installed, verified backup |
| 1 | Theme skeleton — `style.css`, thin `functions.php`, `inc/setup.php`, `inc/assets.php`, `inc/cleanup.php` |
| 2 | `theme.json` with the full token set, `assets/css/base.css`, self-hosted Montserrat, editor lockdown |
| 3 | `header.php`, `parts/nav.php`, `inc/nav.php`, `footer.php`, `page.php`, `parts/page-header.php` |
| 4 | `single.php`, `archive.php`, `search.php`, `404.php`, and the 22 posts migrated on staging |
| 5 | All twelve homepage sections split out of the design source, the `inc/sections.php` conditional loader, animations ported |

The theme is currently 75 files: 12 section partials, 12 section stylesheets,
7 section scripts, 6 `inc/` modules, 5 parts, 4 part stylesheets, `base.css`,
`main.js`, `theme.json`, the font, and 12 SVG icons.

**Stage 6 has not started.** `inc/fields.php` does not exist yet. That is the
next thing to build.

Two things about the current state that matter:

- The homepage lives at **`synergi/templates/homepage.php`** — a page template
  called "Homepage (rebuild)" — not at `front-page.php`. That is deliberate: it
  is verified on a draft page so a half-built homepage never replaces the live
  Elementor one. It becomes `front-page.php` at Stage 8.
- **Every section's copy is currently a PHP default** inside its partial, e.g.
  `$syn_title = $args['title'] ?? __( 'Synergi in Numbers', 'synergi' );`.
  Stage 6b turns each of those into a real field. Nothing on the homepage is
  editable from WordPress yet, apart from the hero photograph, which comes from
  the page's Featured Image.

For scale, the `$args` keys already documented across the twelve partials —
these are what 6b has to turn into fields:

```
hero 7 · services 4 · shared-services 10 · industries 3 · why 4 · numbers 4
partners 4 · locations 6 · blog 6 · instagram 5 · podcast 9 · final-cta 6
```

### The payload situation, stated honestly

Measured on staging 26 Aug (`stage-5-measurement.md`): 68 requests, 2,165 KB.
CSS passes at 76.7 KB. JavaScript fails at 1,152 KB against a 200 KB budget,
and total weight fails at 2.2× budget.

**The theme is 36.8 KB of that — about 1.7%, and inside every budget it
controls.** The overage is plugin payload: Site Kit, Instagram Feed Pro,
Wordfence, Kirki, a Google Maps API key, WPForms. Do not try to solve this in
theme code, and do not let Stage 6 make the theme's own share worse. Re-measure
at the end of each sub-stage and report the theme's own bytes separately from
the page total.

## 3. The sitemap and main menu — decided, do not redesign

```
Home
About Us      ▾  Our Approach · Our Leadership · Global Locations
Our Services  ▾  Human Resources · Technology & AI · Marketing
                 Procurement · Accounting · Project Management
Our Solutions ▾  Shared Services · Build–Operate–Transfer
                 Systems Implementation · Carve-out & Integration
                 Fractional Leadership
Media         ▾  Executive Podcast · Case Studies · Blog · Events
Contact Us
```

Six top-level items; four have dropdowns. The dropdowns list pages a visitor
would want directly — they are deliberately **not** a mirror of the sitemap.
Menus are Stage 7 work, not Stage 6. This is here so you know what the templates
are feeding.

## 4. What every item is — page, section, archive, post type or record

This classification is decided. Do not reclassify anything without asking.

### Pages — own URL, own template

**Existing, being rebuilt on the new templates:**
`/` · `/about-us/` · `/our-approach/` (1,118 words, the most substantial page on
the site) · `/our-leadership/` · `/global-locations/` · `/our-services/` ·
`/our-services/human-resources/` · `/our-services/technology-ai/` ·
`/our-services/marketing/` · `/our-services/procurement/` ·
`/our-services/accounting/` · `/shared-services-uae/` ·
`/bpo-services-in-saudi-arabia-ksa-riyadh/` · `/media/` ·
`/executive-podcast/` · `/contact-us/` · `/blog/`

**New URLs this project creates** — eight. Adding URLs is permitted; moving them
is not:

```
/our-services/project-management/
/our-solutions/
/our-solutions/build-operate-transfer/
/our-solutions/systems-implementation/
/our-solutions/carve-out-and-integration/
/our-solutions/fractional-leadership/
/our-partners/
/case-studies/
```

### Sections — part of a parent page, no URL of their own

The twelve built homepage sections: `hero` · `services` · `shared-services` ·
`industries` · `why` · `numbers` · `partners` · `locations` · `blog` ·
`instagram` · `podcast` · `final-cta`.

Plus: *Vision, mission and values* is a section of About Us. *Upcoming events*
is a new homepage section with no design in the source — it has to be designed,
not ported (Stage 6e). Contact Us has three sections: locations, the enquiry
form, and social accounts at the anchor `#social`.

### Archives

`/blog/` — already built at Stage 4, with topic clusters as categories.
`/executive-podcast/` becomes the archive for the podcast post type.
`/case-studies/` becomes the archive for the case study post type.

### Post types — new content types, and **NOT Stage 6**

`syn_case_study` with a `syn_case_service` taxonomy linking each case study to
one or more of the six service lines and five solutions, plus podcast episodes.
These are a **separate stage after Stage 6**, probably after the domain move.
Do not build them during Stage 6 even if it seems convenient.

### Site records — the `syn_records` option

Six records are designed; **three are built in 6a.** Stored **once** in a single `syn_records` option, edited on one
Settings screen, read by whichever templates need them. This exists because the
business asked for *"key figures — same numbers as the homepage, one set, used
everywhere"*, and postmeta cannot express that without copies that drift apart.

| Record | Fields | Read by | In 6a? |
|---|---|---|---|
| `services` | name, slug, icon, one-liner, URL | Homepage 02, Our Services listing, service pages, menu | **Yes** |
| `figures` | value, label, **as-at date** | Homepage 06, About Us | **Yes** |
| `locations` | city, country, entity, function, email, image | Homepage 08, Contact Us, Global Locations | **Yes** |
| `partners` | name, logo, link | Homepage 07, `/our-partners/` | Deferred |
| `events` | title, date, place, link | Homepage (new section), Media | Deferred |
| `social` | network, URL | Contact Us, footer, homepage 10 | Deferred |

**Only the first three are built in 6a** (27 Aug decision, `stage-6-scope.md`
§8a). The deferred three are read only by pages that do not exist yet, and each
costs roughly half an hour to add later because it is the same mechanism with
different field definitions. Build the store so adding one is a data change, not
a code change.

**The test for whether something is a page field or a site record is one
question: if this changes, how many pages should change with it?** More than one
means it is a site record, and putting it in postmeta is a bug.

Records are an **option, not a custom post type**, because they need no URL, no
template and no SEO of their own — a CPT would invent URLs nobody asked for.

## 5. Decisions already made

### The four Stage 6 decisions

- **D1 — one template.** `templates/service.php` carries both the six service
  lines and the five solutions. Splitting later is cheap; maintaining two
  near-identical templates is not.
- **D2 — Shared Services: combine, at the existing URL.**
  `/shared-services-uae/` is 459 words, holds the Yoast focus keyword *"Shared
  Services UAE"*, and its headings — "Our Capabilities", "Get Started with
  Shared Services UAE" — are already offer content. **It is the solution page,
  thin, not a different page.** Building a second "Shared services design &
  set-up" page would put two pages on the site competing for the same query and
  cannibalise the company's strongest offer. It is rebuilt on the solution
  template **at its existing URL**, and folds into
  `/our-solutions/shared-services/` with a 301 only at the domain move, which is
  a redirect event anyway.
- **D3 — the structure is a change list, not a replacement.** Nothing is removed
  from the built homepage. Shared services (03), why Synergi (05), blog (09),
  podcast (11) and the closing CTA (12) all stay.
- **D4 — case studies and podcast episodes become custom post types; events
  become a site record.** A case study is tagged to its services with the
  `syn_case_service` taxonomy: the service page queries its own term, the Media
  hub queries all of them. One record, three surfaces, nothing duplicated.
  Events are a record because nobody is meant to land on an event page.

### Other decisions already taken

- **Six service lines, not five.** HR, Technology & AI, Marketing, Procurement,
  Accounting and **Project Management**. The seventh SVG icon in the design
  source was the favicon. Every "the 5 service lines" in `synergi-build-plan.md`
  is out by one.
- **Images move from slug lookups to media-picker fields.** Six sections
  currently call `syn_attachment_id_by_slug()` — `industries`, `locations`,
  `partners`, `podcast`, `why`, and the hero via Featured Image. Each becomes an
  image field, with the slug lookup kept as the fallback when the field is empty,
  so nothing breaks on the day the fields ship.
- **The blog clusters are categories.** Keep Outsourcing Insights, Synergi News,
  Procurement, Marketing; rename "Human Resource" to "Human Resources"; delete
  EIH News and Uncategorized (both empty); create empty Accounting,
  Technology & AI and Project Management. Delete the nine zero-post demo tags.
  *(Stage 7 work.)*
- **`/connect/` stays live but out of the navigation.** `/engagement-team/`
  stays live and folds into `/our-leadership/` with a 301 at the domain move.
- Three of the four menus — Impact Menu, Menu One Page, Menu Service — are
  unused and get deleted at Stage 7. Only Main Menu is assigned, to `primary`.

## 6. The order to build Stage 6 in — 6a to 6e

> **Superseded in part, 27 Aug.** This section is kept because it explains *why*
> each sub-stage exists and why the order is what it is — reasoning that is still
> correct and worth reading. But the scope block at the top of this brief is
> later: **6b and 6e do not run**, 6a ships three records not six, one section is
> retrofitted inside 6a, and 6c begins with a design pass and adds an FAQ. Read
> the descriptions below for the reasoning; take the scope from the top.

Build in this order. **Do not reorder, do not merge two sub-stages, and do not
start the next one until I have confirmed the previous one.**

**6a — the fields engine.** `inc/fields.php` and nothing else. No templates.
Four field types: simple text, a JSON repeater with a vanilla-JS
add/remove/move-up/move-down UI, an image field using the core `wp.media` modal,
and a link field storing url plus label. Then the `syn_records` site-record store
with its one Settings screen, the same repeater UI, holding the six records in
§4. Nonces, `current_user_can( 'edit_post', $post_id )`, bail on autosave and
revisions, per-leaf sanitise on save, escape on render, and an admin notice
naming anything rejected. Admin assets under `assets/admin/`, enqueued only on
the screens that use them. **Nothing visible changes on the front end.** Show me
the admin screens before anything is built on top.

**6b — retrofit the homepage onto fields.** Every `$args` default in the twelve
partials becomes a real field; every `syn_attachment_id_by_slug()` call becomes
an image field with the slug lookup as the empty-value fallback. Figures,
locations and partners read from `syn_records`, not postmeta.
**This comes before any new template on purpose:** the homepage is already built
and visually approved, so a flaw in the engine shows up against a known-good page
instead of while a new template is also being debugged. It is also the step that
answers "can we edit the homepage from WordPress" — after 6b, yes.
**Nothing about the rendered homepage may change:** same markup, same classes,
same measured payload. Prove it by diffing the rendered HTML before and after.
One commit per section, so a bad retrofit reverts alone.

**6c — the service template.** `templates/service.php`, composing section
partials plus fields. Build **Human Resources** completely on staging and verify.
Then build **Project Management** entering content only — it is the sixth line
and has no existing page, so it proves the template can create a page as well as
re-skin one. **If Project Management needs a single code change, the template is
not done.**

**6d — the solutions template.** Per D1, extend `templates/service.php` to carry
the five Solutions pages. Build the `/shared-services-uae/` rebuild first. Then
`templates/market.php` for UAE / KSA / Global Locations, and — only if it
survives the cut list — `templates/guide.php`.

**6e — the four homepage changes the structure asked for**, now that fields
exist to carry them:

1. Hero: one sentence for a business reader, and **delete the legacy keyword
   paragraph beneath it**.
2. Numbers (06): figures gain an **as-at date** — one field and a line of markup.
3. Partners (07): logos **link to `/our-partners/`**. They are list items today
   because the design source made them anchors with no `href`, which is not a
   link at all; giving them a real destination is the fix that was always wanted.
4. Locations (08): show **entity and function delivered**, not just city and
   country — two fields and markup.

Then the new **Upcoming events** section, which sits between locations and
social and has no design in the source, so it needs designing rather than
porting.

### What "done" means for each sub-stage

From `synergi-build-stages.md` Stage 6 and CLAUDE.md §10:

- Every field: nonce, capability check, autosave/revision bail, per-leaf sanitise
  on save, escape on render
- Repeater: add/remove/reorder works, saves survive reload, and hostile input —
  quotes, emoji, raw HTML, a 5,000-word paste — is stored and displayed safely
- Site records: changing a key figure once changes it on the homepage **and** on
  About Us
- Images: a photograph can be replaced by someone who has never heard of a slug
- The homepage after 6b renders byte-identical markup to before it, and the same
  measured payload
- The HR page matches the service design; its fields are editable by a
  non-developer
- Project Management built with **zero** code changes
- No field can change a colour, a width, a spacing value or a section order
- Field boxes are invisible on ordinary pages
- Every service, solution and market page under budget
- Exactly one `<h1>` per page, emitted by the template
- Keyboard focus visible on every control; 4.5:1 body-text contrast
- `prefers-reduced-motion` respected; the page renders correctly with JavaScript
  disabled
- No hard-coded design value in any stylesheet
- Zero PHP notices, warnings or deprecations with `WP_DEBUG` on

## 7. The company profile — how to use it, and how not to

`Synergi - Company Overview - 2026.pdf` (20 August 2026, 20 pages) is in the
project root; a PowerPoint version of the same deck may also be present.
**Read it before drafting any page copy.** If PDF page rendering is unavailable
in your environment, `pdftotext -layout` produces usable text. Inspect the
PowerPoint too if it is there, because the deck's diagrams, the locations map
and the partner logos carry information the text layer does not.

**It is the primary source of fact for the new pages.** Verified contents:

- The positioning: a boutique BPO provider with a stated ambition to evolve into
  a tech-driven *"Shared Services As-a-Service (SSaaS)"* provider, home-grown in
  the GCC, with delivery centres onshore and offshore
- The journey: Ideation 2022 → UAE 2023 → Romania 2023 → Lebanon 2024 →
  Qatar 2025 → KSA 2025
- The four key figures — **50+ clients served · 5 global delivery locations ·
  100+ years combined experience · 10–15% direct savings** — all as at
  20 August 2026, which is the `as-at date` the `figures` record needs
- The industries served: Public Sector, Financial Services, Real Estate,
  Hospitality, Automotive, Manufacturing, Diversified Investments
- All six service lines with their full capability bullets, including the seven
  Project Management capabilities the new page needs
- The Build–Operate–Transfer model with its three phases and their durations
- The maturity-lifecycle solution table — Startup & Incubation · Relocation or
  New Market Entry · Mid-stage/SME · Mature/Consolidation & M&A · Divestment —
  with challenge, solutions and value for each stage
- Ten worked case studies with client type, scope of project and deliverables
- Office addresses for UAE, KSA, Qatar, Lebanon and Romania
- The full engagement team with names, titles and email addresses

**Hard rules for using it:**

1. **Do not invent.** Never create a service, a statistic, a client name, a
   capability, a location, a certification, a partner or any business claim that
   is not in the profile, on the existing website, or in the approved planning
   documents. If you need a fact you do not have, **stop and list exactly what is
   missing** so I can confirm it with the company. A named gap is a good outcome;
   a plausible invention is the worst possible one.
2. **Do not silently promote internal deck content to public web copy.** The deck
   is a sales document. Named clients, savings percentages and headcount figures
   may or may not be cleared for the public site. Flag every one you want to
   publish and let me confirm it.
3. **Watch the domain.** Every email address and the website in the deck use
   **synergibpo.com**. The live site is **synergi.ae**. The domain move is a
   separate, later event. Do not publish `@synergibpo.com` addresses without
   asking, and never hard-code either domain — CLAUDE.md §12 requires all URLs
   relative or via `home_url()`.
4. **Existing page copy wins on existing pages.** `/our-approach/`,
   `/shared-services-uae/` and `/bpo-services-in-saudi-arabia-ksa-riyadh/` carry
   earned Yoast focus keywords and real rankings. Rebuilding them on a template
   must preserve their substance and their keywords — you are re-housing copy,
   not rewriting it.
5. **Flag any page title over 60 characters** during migration. Do not silently
   rewrite one.

## 8. Content that needs to be created

New copy is genuinely needed for the pages below. Draft it from the company
profile, the existing site, and the planning documents — and from nowhere else.

| Page | Source available | Status |
|---|---|---|
| `/our-services/project-management/` | Profile has all seven PM capabilities | **Draftable now** |
| `/our-solutions/` (listing) | Profile's lifecycle table plus the BOT model | **Draftable now** |
| `/our-solutions/build-operate-transfer/` | Profile's BOT page, with phases and durations | **Draftable now** |
| `/our-solutions/systems-implementation/` | Partial — ERP, SAP, Oracle HCM, Menaitech and Zoho all appear across services and case studies, but there is no single systems-implementation offer statement | **Draft, then confirm scope** |
| `/our-solutions/carve-out-and-integration/` | Partial — the lifecycle table's "Mature/Consolidation & M&A" and "Divestment" columns | **Draft, then confirm scope** |
| `/our-solutions/fractional-leadership/` | Partial — "CXO as a service", "Fractional CMO" and "Fractional HR Manager" appear as capabilities, not as a described offer | **Draft, then confirm scope** |
| `/our-partners/` | The profile states alliances exist, but the partner names are **logo images only** | **Blocked — see below** |
| `/media/` hub | 30 words live today; no source for the rest | **Needs a brief** |
| About Us — vision, mission, values | The profile has the vision; there is **no formal mission statement and no values list** | **Partly blocked** |
| Upcoming events section | **No source anywhere** | **Blocked** |

### What is genuinely missing and must be confirmed with the company

Ask me these before the affected page is built. **None of them blocks 6a, 6b
or 6c.**

1. **Partner names and logo files.** The deck shows the logos as images; the
   names are in no text source. `/our-partners/` and the homepage partners band
   cannot be filled honestly without them.
2. **Which function each location delivers.** The deck's map legend has BPO, CX,
   Technology Delivery Center, Business Development and Future entry, but which
   pin carries which icon is graphical, not textual. Stage 6e's locations change
   needs this stated explicitly per location, along with the legal entity name
   for each.
3. **Which office emails and phone numbers are public**, and on which domain.
4. **Whether the five Solutions pages have copy written**, or whether Stage 6d
   builds one template and one page and the rest follow later. *(This is the one
   open question already recorded in `sitemap-and-navigation.md` §9.)*
5. **Which client names may be published**, if any. Several case studies name or
   clearly identify clients — a DIFC commodities trader, a Mubadala portfolio
   company, an F&B franchisee, a crypto trading firm, an ELM/PIF entity. Public
   use needs permission.
6. **Whether the 10–15% direct savings figure is cleared for the public site**,
   and whether it needs a qualifier.
7. **A formal mission statement and a values list**, if About Us is to have that
   section.
8. **Any upcoming events at all** — title, date, place and link.
9. **The social account URLs** for the `social` record.
10. **Leadership photographs and approved bios** for `/our-leadership/`. The deck
    has names, titles and emails only.

## 9. The exact first task

**Stage 6a, and only Stage 6a.**

6a runs in **three steps, with a stop after each.** Do not run them together.

**Step 1 — the four field types.**

> Build the page-field engine and nothing else — no templates, no site records
> yet, no change to any existing section partial, no front-end change of any
> kind. `synergi/inc/fields.php`, registered from `functions.php`. Four field
> types: simple text, a JSON repeater with a vanilla-JS add / remove / move-up /
> move-down UI, an image field using the core `wp.media` modal, and a link field
> storing url plus label. Nonces, `current_user_can( 'edit_post', $post_id )`,
> bail on autosave and revisions, per-leaf sanitise on save, escape on render,
> and an admin notice naming anything rejected. Field boxes appear only on pages
> using a matching template. Admin assets under `synergi/assets/admin/`,
> enqueued only on the screens that use them. Then stop and hand me a zip — I am
> going to spend half an hour trying to break the repeater before you go on.

**Step 2 — the site records.** Only after I have tested step 1.

> Build `synergi/inc/records.php` — a separate file from `fields.php`, because
> CLAUDE.md §7a says page fields and site records are two different things and a
> single thousand-line module is not readable. One `syn_records` option, one
> Settings screen, the same repeater UI, holding **three records only**:
> `services` (name, slug, icon, one-liner, URL), `figures` (value, label, as-at
> date) and `locations` (city, country, entity, function, email, image). Same
> security rules as step 1, plus shape validation on read — never trust stored
> option data at render time.

**Step 3 — the one-section safety retrofit.**

> Wire `synergi/sections/numbers.php` to read the `figures` record, keeping its
> current `$args` defaults as the empty-value fallback so nothing breaks when
> the record is empty. **Nothing about the rendered homepage may change.** Prove
> it: capture the rendered HTML before and after and diff them. If the diff is
> not empty, the retrofit is wrong — fix it, do not explain it away. This is the
> only part of the frozen homepage this stage touches, and it exists to find a
> bad record shape now rather than after four templates sit on it.

After each step, stop and give me the ten-item stage report in §11 below.

Before you start step 1, confirm back to me: the rules you have read, what 6a
includes, what it deliberately excludes, the three records and why the other
three are deferred, and anything in this brief you think is wrong.

## 10. Safety, testing and regression rules — non-negotiable

**Environment**

- **Never work against production.** All work is local or on staging. Production
  is touched only at Stage 8, deliberately, with a rollback ready.
- WP-CLI is unavailable on this host — `proc_open` and `exec` are disabled.
  Anything scripted against the site goes through the Novamira MCP connection to
  staging, or through wp-admin.
- Develop with `WP_DEBUG` on and `SYN_DEBUG` on. Code is done only when it
  produces zero notices, warnings and deprecations.

**Backup and rollback, before every sub-stage**

- Confirm a current staging backup exists before starting, and say so in the
  report.
- Every sub-stage is its own set of small commits, one concern each, on `main`,
  tagged at the end — `stage-6a-done`, and so on.
- **Never commit on top of a broken state.** Fix or revert first.
- Field data lives in postmeta and options and survives a code revert. Templates
  revert with Git. 6b is the risky step: one commit per section, so a bad
  retrofit reverts alone.
- **Never delete `_elementor_data`**, the old theme, or the old plugins. They are
  the live rollback until at least a week after launch.

**Regression prevention**

- 6b must produce **byte-identical rendered HTML** for the homepage. Capture the
  rendered page before and after and diff it. Any difference is a defect unless I
  have explicitly approved it.
- Re-measure after every sub-stage: total weight, requests, CSS, JS, blocking
  scripts. Report the theme's own bytes separately from the page total. A
  regression in the theme's share is a defect.
- Verify conditional loading still holds: a plain page must load `base.css` and
  nothing from `assets/css/sections/`.
- Check the existing pages still render: `/blog/`, a single post, a category, a
  search result, a 404, and `/about-us/`.

**SEO**

- **No URL changes.** Ever, in Stage 6. Adding new URLs is fine; moving or
  redirecting an existing one is not.
- Exactly one `<h1>` per page, emitted by the template, never typed by an editor.
  Heading levels never skip.
- Yoast owns `<title>`, meta description, canonical and OG tags. The theme
  outputs none of them.
- Existing focus keywords and metadata must survive a rebuild untouched.
- Meaningful `alt` text on content images, empty `alt=""` on decorative ones.

**Responsive and accessibility**

- Check 360px, 768px and 1440px on everything you change.
- Visible keyboard focus on every interactive element, including the repeater's
  admin UI. Never `outline: none` without a visible replacement.
- 4.5:1 contrast minimum for body text, verified on the navy and ink sections.
- `prefers-reduced-motion` respected — reduced means content visible with no
  motion, never hidden content.
- Every page must render correctly with JavaScript disabled.

**Code**

- Logical CSS properties only. No `left` or `right`. No hard-coded design values —
  every colour, size, spacing step and radius comes from `theme.json`.
- `defined( 'ABSPATH' ) || exit;` at the top of every PHP file. A `syn_` prefix on
  every function, `_syn_` on every meta key.
- Escape all output, sanitise all input, nonce and capability-check every save.
- No jQuery in theme code. No npm, no build step, no framework, no page builder.
- `!important` requires a comment explaining what forced it. Target: zero.

## 11. Stop after each sub-stage and report these ten things

**Do not continue automatically from one sub-stage to the next.** After 6a, stop.
After 6b, stop. And so on. Wait for me to confirm I have worked through the
checklist before you begin the next one.

Each report must contain, in this order:

1. **A simple explanation** of what was completed, in plain language.
2. **Every file, template, field, page and setting changed** — a complete list,
   with nothing omitted.
3. **The technical checks you performed**, and their results.
4. **A step-by-step checklist of what I should personally check**, in order.
5. **The exact WordPress screens, URLs and front-end areas to inspect** — real
   paths, not descriptions.
6. **What I should expect to see** if everything is working correctly.
7. **Explicit confirmation** that desktop, tablet and mobile rendering, SEO,
   existing content, links and current functionality are not broken — with the
   evidence for each, not an assurance.
8. **Every remaining issue, risk, placeholder and piece of missing content.**
9. **Backup and rollback information** — what the backup is, what the tag is, and
   the exact steps to undo this sub-stage.
10. **A clear recommendation** on whether it is safe to approve the next
    sub-stage, with the reasoning.

**Nothing may be silently skipped, left unfinished, duplicated, or published with
placeholder or unsupported content.** If something cannot be finished, finish
everything else and say plainly what was left and why. If you find a problem with
an instruction, say so in a sentence or two and then keep building under a stated
assumption — do not stop the work over it unless proceeding would be unsafe.
