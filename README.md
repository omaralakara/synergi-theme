# Synergi website rebuild — project folder

Everything needed to build the new synergi.ae theme, in the order you'd read it. Updated 1 September 2026. All earlier drafts have been removed; what's here is current.

## The documents

| File | What it is | Read it when |
|---|---|---|
| `Synergi-Website-Rebuild-Brief.docx` | The executive brief — the whole project explained clearly for the COO | Presenting or approving the project |
| `synergi-architecture-explained.md` | The approach in plain language: why hybrid, who edits what, blog, Arabic, fields | You want to understand the decisions |
| `synergi-build-plan.md` | The full plan (v2 + 23 Aug amendments): problem, trade-offs, phases, SEO, security, rollback | You want the complete reasoning and evidence |
| `CLAUDE.md` | The build rules: hard restrictions, theme structure, tokens, security / performance / SEO / accessibility standards | **Every build session starts by reading this** |
| `synergi-build-stages.md` | Stages 0–9 with copy-paste prompts, verification gates and rollbacks | Doing the actual build, one stage at a time |
| `design-source/` | The approved homepage design as built by Synergi's own developer — HTML, CSS, JS, icons. **Input to the build, never shipped.** Snapshot from staging, 24 Aug; see its `SOURCE.md` | Stage 5 |
| `sitemap-and-navigation.md` | The sitemap, the main menu, and what every row of the stakeholder structure becomes — page, section, archive, post type or record | **The authority on structure** |
| `stage-6-scope.md` | What the stakeholder structure changed about Stage 6, decisions D1–D4, and (§8) what the business narrowed on 27 Aug | Before Stage 6 |
| `stage-6-remaining-plan.md` | What is left of Stage 6, re-planned on 28 Aug, in build order with its blockers | **Deciding what to do next** |
| `stage-6-handoff-prompt.md` | A self-contained brief for starting Stage 6 in a clean session — what is built, what was decided, what to do next | Starting a new build session |
| `stage-7-decisions.md` | The nine structural decisions with their outcomes, the URL disposition table, the accessibility audit, and what Stage 7 knowingly left | **The record of what the site's structure now is** |
| `migration-plan.md` | How staging actually becomes production: the three options, what must not travel, and the runbook | **Before Stage 8** |
| `stage-4-post-migration.md` · `stage-5-measurement.md` | What the blog migration found; the measured homepage payload and whose bytes they are | Checking budgets or the blog |
| `open-questions.md` | The content and structure questions the build is waiting on | Anything is blocked |
| `tools/` | Scripts used to split the design source: extract, diff, cascade, and `build-zip.ps1` for the staging upload | Packaging a build |
| `reference/theme.json` | The corrected design tokens as working code (from the 20 Aug scaffold — values verified against the live design) | Stage 2 |
| `reference/montserrat-latin.woff2` | The design's only font, copied from the server, SHA-1 verified | Stage 2 |
| `reference/token-notes.md` | Verified notes on the token values: cascade quirks, decisions needing eyeballing on staging | Stage 2 and the CSS split |

## The decision, in three lines

Keep WordPress. Remove Elementor and its add-on stack. Build a **hybrid theme**: PHP templates in Git render the site, `theme.json` holds every design token, the block editor is used only for writing content, and designed pages get editable text through hand-built custom fields. Zero paid dependencies. English first, RTL-ready for the Arabic phase. GTM/GA4, Search Console and CRM integrations supported by design.

---

# Editing the site

There are exactly **three** places the words and pictures on this site can live, and which one a piece of content belongs in is decided by a single question: **if this changes, how many pages should change with it?**

| Where | Answer to that question | Edited at |
|---|---|---|
| **Page fields** | One page | The boxes under the editor on that page |
| **Site records** | More than one page | Settings → Site records |
| **The block editor** | It *is* the page | The normal WordPress editor |

Get this wrong and the site drifts: the same figure typed on four pages becomes four different figures within a month. That is the failure the whole architecture exists to prevent, so when in doubt, ask the question again.

## Page fields

Every designed page uses a **template**, chosen in the editor sidebar under *Page Attributes → Template*. Choosing the template is what makes its field boxes appear; without it you get an ordinary page.

| Template | For | Field boxes it adds |
|---|---|---|
| **Homepage (rebuild)** | The homepage | Hero, Services, Shared services, Industries, Partners, Blog, Instagram, Podcast |
| **Service page** | The six service lines | Intro, What it covers, How we work, Case study, Questions |
| **Solution page** | The five solutions | Intro, What it covers, How it runs, Proof, Questions |
| **Market page** | Markets overview, Saudi Arabia, UAE… | Intro, Introduction, Services, Industries, Why here, Proof, Questions, Related insights |
| **About Us** | About Us | Intro, Who we are, Our approach, Our values, Our journey |
| **People page** | Our Leadership, Engagement Team | Intro, The list |

**Case studies are not on this list**, because they are not pages. They are their
own type — *Case studies* in the admin menu, like Posts. Add one, write the story
in the ordinary editor, fill the facts and outcome boxes, and pick its service
line in the *Service* box. It then appears on `/case-studies/`, on that service's
own archive at `/case-studies/service/{reference}/`, and in every grid that asks
for it. There is no list to keep in step.

Three things are true of every one of them:

- **The page title is the heading.** Templates emit the page's single `<h1>` from the title, so it can never disagree with the browser tab or the menu. There is no "heading" field, and there should not be one.
- **The SEO title is Yoast's, and may differ.** The theme emits no `<title>`, meta description, canonical, Open Graph tag or focus keyword anywhere — those are Yoast's boxes further down the same screen. Two of each in one page is a Search Console error, not better SEO.
- **Every box has a default.** Clearing a box restores the approved wording rather than emptying the page. A section with nothing to show hides itself completely — no empty heading, no gap.

Fields carry **copy and pictures, never layout**. There is no colour field, no width field, no "background" field, and there will not be one: an editor changing words must not be able to change the design.

## Site records

Settings → **Site records**. Nine of them, each a list of rows edited once and read everywhere:

| Record | What it holds | Read by |
|---|---|---|
| `services` | The six service lines: name, reference, icon, summary, address | Homepage, every service page, every market page, the services listing |
| `solutions` | The five solutions | Every solution page, the solutions listing |
| `markets` | The markets with a page of their own | Every market page |
| `figures` | The key numbers, each with the date it was verified | Homepage, About Us, service, solution and market pages |
| `locations` | The delivery offices | Homepage, Contact Us, Global Locations |
| `why` · `why_cards` | The "Why companies choose Synergi" band | Eight pages |
| `final_cta` | The closing call to action | Every designed page |
| `social` | The social accounts | Contact Us, the homepage band |

Change a figure here and it changes on every page that shows it. That is the point.

## The block editor

Blog posts, and any simple page that isn't on a template. The colour palette is locked to the eleven brand colours, custom colours and custom font sizes are switched off, and the default WordPress palette is gone — so brand drift is prevented structurally rather than by policy.

## Adding a new page of an existing type

Say a sixth solution:

1. Settings → Site records → **Solutions** → *Add solution*. Name, reference (`lowercase-with-dashes`), one-line summary, address.
2. Pages → **Add New**. Title it, set the parent page (that produces the URL), pick the **Solution page** template.
3. Type the reference from step 1 into the *Solution* box, so the page leaves itself out of its own "other solutions" list.
4. Fill the boxes down the screen — they're in the same order as the page. Leave a section's fields empty and that section does not render.
5. Fill Yoast's SEO title, description and social image. Publish.

It now appears in the "other solutions" list on all five existing solution pages automatically. Markets work identically, with the Markets record and the Market page template.

---

# Editing the theme

Read `CLAUDE.md` in full first — it is the contract, and it overrides anything here. What follows is the map.

## Where everything lives

```
synergi/
├── theme.json          Every design token: palette, type scale, spacing, layout
├── style.css           Theme header comment only. Zero style rules.
├── functions.php       Thin. require_once calls into inc/ and nothing else.
├── inc/
│   ├── setup.php · assets.php · cleanup.php · integrations.php · nav.php
│   ├── sections.php    The section registry and loader
│   ├── fields.php      The field ENGINE — how a field works
│   ├── records.php     The site-record engine
│   └── *-fields.php    WHICH fields exist, one file per page family
├── templates/          One file per page type, chosen in the editor
├── sections/           21 reusable bands, one PHP partial each
├── parts/              header/footer partials: nav, page-header, post-card…
└── assets/css/sections/  ·  assets/js/sections/   one file per section, same name
```

## The one rule that shapes everything

Anything describing **how the site looks** lives in a file. Anything **a person wrote** lives in the database. Every other rule follows from that.

## A section is three files with one name

`hero` is `sections/hero.php`, `assets/css/sections/hero.css` and (optionally) `assets/js/sections/hero.js`, and every CSS class it owns starts `syn-hero`. Seeing a class in the browser inspector tells you which files to open — always. Sections never enqueue their own assets; a template declares what it will render with `syn_use_sections()` *before* `get_header()`, and `inc/assets.php` loads only those files.

Under `SYN_DEBUG`, view-source lists which sections were declared and which rendered, and names any mismatch.

## Building a new page type

The last two templates — Solution and Market — were built without one line of new CSS or JavaScript, by composing bands that already existed. **Do that first.** Reach for a new section only when the page genuinely needs a shape none of the 21 can make, and then build all three files with the same name rather than a stylesheet that restyles an existing band. A second file that overrides a component is the failure this project exists to escape.

The pattern for a new page type is four steps and two new files:

1. `inc/thing-fields.php` — its field groups, scoped to the template, and its site record if it needs one. Register the record on the `syn_register_records` action rather than editing `inc/records.php`; register the fields on `syn_register_fields`.
2. `templates/thing.php` — `Template Name:` in the docblock, `syn_use_sections()` above `get_header()`, then one `syn_section()` call per band.
3. One `require_once` in `functions.php`.
4. Nothing else. No CSS, no JS, no edits to shared files.

Field types at the top level are `text`, `textarea`, `image`, `link` and `repeater`. Inside a repeater a subfield may also be `url`, `html`, `int`, `select`, `date` or `email` — a `url` at the top level is silently dropped, so use `text` there.

## Working on it

- **Never against production.** Local or staging only; production is touched at launch, deliberately, with a rollback ready.
- **Build the upload:** `powershell tools/build-zip.ps1` → `synergi-theme.zip` at the repo root. It writes forward-slash entry paths, which Windows' own `Compress-Archive` does not — that is the "theme is missing the style.css stylesheet" error.
- **`WP_DEBUG` on** while developing. Code is done when it produces zero notices, warnings and deprecations.
- **`SYN_DEBUG`** (set in `wp-config.php` on staging) prints the section and asset diagnostics into view-source.
- **Commits:** small, one concern each, and the message says *why*. `git log` on a file should read as that file's decision history.

## Definition of done, per template

Under budget when measured · exactly one `<h1>` · keyboard focus visible on every control · 4.5:1 body-text contrast · `prefers-reduced-motion` respected · renders correctly with JavaScript disabled · no hard-coded design value in any stylesheet · no PHP notices with `WP_DEBUG` on.

---

## Status — 1 September 2026

- Architecture: **agreed** (hybrid, 23 Aug)
- **Stages 0–7: complete, verified and tagged** (`stage-1-done` … `stage-7-done`)
- **Built:** the homepage, the six service pages, About Us, the people template,
  the Solution and Market templates and their pages, Contact Us, Global
  Locations, the Media hub, the podcast page, the four listing pages, and twelve
  case studies on a post type of their own with a term archive per service line.
- **Stage 7 closed 1 Sep.** The nine structural decisions are recorded in
  `stage-7-decisions.md`, with the URL disposition table and the accessibility
  audit. Menus are built and every one of the 29 items resolves.
- **Not built, knowingly:** podcast episodes as a post type, the `partners` and
  `events` records, the Upcoming Events section, and the wiring that would let a
  service page query its own case studies rather than show one typed by hand.
  All four are named in `stage-7-decisions.md` so Stage 8 does not discover them.
- Measured, honestly: the homepage is over budget at 2,165 KB — but **the theme is 36.8 KB of that, about 1.7%, and inside every budget it controls.** The overage is plugin payload. See `stage-5-measurement.md`.
- **Next: Stage 8 — migration and launch.** Read `migration-plan.md` first: the
  theme is in Git but the content is only in the staging database, and Stage 8's
  one-line description does not cover that. Three things block it — the
  production Novamira connection returns 404, the landing-page collision in
  `open-questions.md` §6 is undecided, and the launch path is not formally
  chosen.
