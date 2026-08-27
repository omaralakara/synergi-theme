# Synergi website rebuild — project folder

Everything needed to build the new synergi.ae theme, in the order you'd read it. Updated 23 August 2026. All earlier drafts have been removed; what's here is current.

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
| `stage-6-handoff-prompt.md` | A self-contained brief for starting Stage 6 in a clean session — what is built, what was decided, what to do next | Starting a new build session |
| `stage-4-post-migration.md` · `stage-5-measurement.md` | What the blog migration found; the measured homepage payload and whose bytes they are | Checking budgets or the blog |
| `tools/` | Scripts used to split the design source: extract, diff, cascade, and `build-zip.ps1` for the staging upload | Packaging a build |
| `reference/theme.json` | The corrected design tokens as working code (from the 20 Aug scaffold — values verified against the live design) | Stage 2 |
| `reference/montserrat-latin.woff2` | The design's only font, copied from the server, SHA-1 verified | Stage 2 |
| `reference/token-notes.md` | Verified notes on the token values: cascade quirks, decisions needing eyeballing on staging | Stage 2 and the CSS split |

## The decision, in three lines

Keep WordPress. Remove Elementor and its add-on stack. Build a **hybrid theme**: PHP templates in Git render the site, `theme.json` holds every design token, the block editor is used only for writing content, and designed pages get editable text through hand-built custom fields. Zero paid dependencies. English first, RTL-ready for the Arabic phase. GTM/GA4, Search Console and CRM integrations supported by design.

## Status — 27 August 2026

- Architecture: **agreed** (hybrid, 23 Aug)
- **Stages 0–5: complete, verified and tagged** (`stage-1-done` … `stage-5-done`). The theme is 74 files: twelve homepage sections with their own CSS and JS, the conditional section loader, header, footer, nav, and the blog templates with all 22 posts migrated on staging.
- **Stage 6 is next**, and its scope was narrowed twice — first by the stakeholder content structure (26 Aug), then by the business (27 Aug). Read `stage-6-scope.md` §8 for what is actually being built.
- Measured, honestly: the homepage is over budget at 2,165 KB — but **the theme is 36.8 KB of that, about 1.7%, and inside every budget it controls.** The overage is plugin payload. See `stage-5-measurement.md`.
- Open: whether the five Solutions pages have copy written (holds 6d); the nine structural decisions (block Stage 7); the ten content questions in `stage-6-handoff-prompt.md` §8.
