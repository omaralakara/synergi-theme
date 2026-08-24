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
| `reference/theme.json` | The corrected design tokens as working code (from the 20 Aug scaffold — values verified against the live design) | Stage 2 |
| `reference/montserrat-latin.woff2` | The design's only font, copied from the server, SHA-1 verified | Stage 2 |
| `reference/token-notes.md` | Verified notes on the token values: cascade quirks, decisions needing eyeballing on staging | Stage 2 and the CSS split |

## The decision, in three lines

Keep WordPress. Remove Elementor and its add-on stack. Build a **hybrid theme**: PHP templates in Git render the site, `theme.json` holds every design token, the block editor is used only for writing content, and designed pages get editable text through hand-built custom fields. Zero paid dependencies. English first, RTL-ready for the Arabic phase. GTM/GA4, Search Console and CRM integrations supported by design.

## Status

- Architecture: **agreed** (hybrid, 23 Aug)
- Governance docs: **done**
- Code written: **none yet — deliberately.** Stage 0 (staging, Git, fallback theme, verified backup) is the first move.
- Open before code: the two security items get an owner and a date; the frozen prototype direction (blocks Stage 3); the nine structural decisions (block Stage 7).
