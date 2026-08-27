# Design source — snapshot

Snapshot of the **`synergi-homepage-assets` plugin v2.1.0**, taken from **staging.synergi.ae** on **24 August 2026** via the Novamira connection. Byte-for-byte identical to the plugin as installed; sizes verified on copy.

## Why this folder exists

This is the approved homepage design, written as plain HTML, CSS and vanilla JS by Synergi's own developer. It is the **input** to the theme build, not part of the theme. Stage 5 converts it into `synergi/sections/*.php` plus `synergi/assets/css/sections/*.css`.

Nothing in this folder is ever shipped or enqueued. Do not edit these files to change the site — change the theme.

## What's here

| File | Bytes | Notes |
|---|---|---|
| `templates/homepage-content.html` | 46,502 | The homepage markup — split into sections at Stage 5 |
| `assets/css/main.min.css` | 104,702 | The design's stylesheet. Declares `:root` **8 times**; see CLAUDE.md §3 for the values that actually win |
| `assets/js/main.js` | 48,741 | Vanilla, no jQuery, no external libraries |
| `assets/js/why-section.js` | 5,505 | Second script — easy to miss, do not drop it |
| `assets/icons/*.svg` | 7 files | accounting, human-resources, marketing, procurement, **project-management**, technology-ai, favicon |
| `assets/svg/connection-field.svg` | 563 | Decorative |
| `synergi-homepage-assets.php` | 12,499 | The plugin wrapper. Kept as documentation: it shows the Elementor workarounds the design needed, which the new theme removes |

The Montserrat font is **not** duplicated here — `reference/montserrat-latin.woff2` is the same file.

## Two things to know

**This snapshot can go stale.** The live source is edited directly on production, versioned by copying filenames (39 `.bak*` files existed alongside these on 18–19 Aug 2026). Before Stage 5, re-pull from staging and diff against this folder. If it has changed, the newer version wins — and say so in the commit message.

**There are 7 icons and six service lines.** ~~There are 7 icons but only 5 service pages... do not invent a sixth service line~~ — **superseded 26 Aug.** The stakeholder content structure names six service lines: HR, Technology & AI, Marketing, Procurement, Accounting and **Project Management**. The seventh icon is the favicon. See CLAUDE.md §12a note 2 and `sitemap-and-navigation.md` §2.3, which are the authority. Homepage section 02 already renders all six.

## Excluded deliberately

All 39 `.bak*` files. They are the previous dev's manual version history, superseded by this repository.
