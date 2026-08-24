# CLAUDE.md — Synergi theme build rules

This file governs every Claude session that works on the Synergi theme. Read it fully before writing any code. If an instruction in a chat conflicts with this file, stop and ask — do not silently break a rule.

Companion documents in this folder:
- `synergi-build-plan.md` — the full plan (v2, 20 Aug 2026). The "why".
- `synergi-architecture-explained.md` — the approach in plain language.
- `synergi-build-stages.md` — the stage-by-stage build guide with prompts. The "when".
- This file — the "how" and the "never".

---

## 1. What we are building

A **hybrid WordPress theme** called `synergi` for synergi.ae. PHP templates in Git render the site. `theme.json` defines every design token. The block editor is used only for writing content (posts and simple pages). Designed pages get their editable text from hand-built custom fields.

**The one rule:** anything that describes how the site looks lives in a file; anything a person wrote lives in the database. Every decision below serves this rule.

## 2. Hard restrictions — never do these

1. **Never work against production.** All development happens on staging or locally. Production is touched only at launch (Stage 8), deliberately, with a rollback ready.
2. **No page builders.** No Elementor widgets, no builder plugins, no FSE/Site Editor templates (`templates/*.html`), no `wp_template` records. If the Site Editor would write it to the database, we don't use it.
3. **No build pipeline.** No npm, no webpack/vite, no React, no TypeScript, no Sass. Plain PHP, plain CSS, plain vanilla JS. If a feature seems to need a build step, redesign the feature.
4. **No jQuery in theme code.** Never write theme code that depends on it. But do NOT blindly deregister it: WPForms declares jQuery as a front-end dependency and deregistering silently breaks form validation (verified 20 Aug). `inc/cleanup.php` ships a diagnostic instead (`SYNERGI_AUDIT_JQUERY` prints every handle still depending on it); jQuery is dropped only once that list is empty — realistically when the form stack is consolidated.
5. **No paid dependencies.** No ACF Pro, no Meta Box extensions, no premium plugins. Custom fields are hand-built (see §7). The theme must run on a clean WordPress install with zero licenses.
6. **No external requests from the front end** added by the theme itself. No Google Fonts CDN, no CDN scripts, no third-party embeds baked into templates. Fonts are self-hosted. The measurement stack (GTM/GA4 — see §11) is the deliberate exception and always loads deferred, after first paint.
7. **No hard-coded design values.** Every color, font size, spacing step, radius, and container width comes from `theme.json` via CSS custom properties. A hex value in a stylesheet is a bug. The only exception: `theme.json` itself.
8. **No URL changes.** Every existing URL renders at exactly the same path. No redirects, no slug edits, no CPT conversions before the domain move.
9. **Never delete `_elementor_data`** or the old theme/plugins until at least one week after launch. They are the rollback.
10. **No new plugins** unless they pass all four tests in the build plan (takes >2 days to build, payload measured first, loads only where used, actively maintained with an owner). Default answer is no.
11. **No physical CSS directions.** Use logical properties only: `margin-inline-start`, `padding-block`, `inset-inline-end`, `text-align: start`. This keeps the theme RTL-ready for the Arabic phase at zero cost. `left`/`right` in CSS is a bug outside of transforms and absolutely-positioned decorative elements — and even there, prefer logical.
12. **`!important` requires a comment** explaining what forced it. Target: zero.
13. **One stage at a time.** Never start a stage before the previous stage's verification checklist passes (see `synergi-build-stages.md`). Never combine stages "to save time".

## 3. Design tokens — the canonical values

These are verified from the approved design's `main.min.css` and the Elementor global kit. Do not "improve" them. Do not reintroduce values from older specs (Josefin Sans, Inter, IBM Plex Mono, cyan `#32aae1`, bronze `#a66b37` are all WRONG and must not appear).

**Font:** Montserrat only. Variable weight, self-hosted `montserrat-latin.woff2`, latin subset, `font-display: swap`. One family, one file, one pipeline.

**Editor palette** (exposed to editors in `theme.json`):

| Slug | Value | Name |
|---|---|---|
| navy | `#1d4e89` | Navy |
| navy-deep | `#0b2341` | Navy deep |
| ink | `#071a31` | Ink |
| cyan | `#28abe5` | Cyan |
| cyan-soft | `#dff4fc` | Cyan soft |
| mint | `#8fd7f3` | Mint |
| text | `#232324` | Text |
| text-soft | `#5c6673` | Text soft |
| white | `#ffffff` | White |
| paper | `#f3f5f8` | Paper |
| paper-blue | `#edf5fa` | Paper blue |

**Utility values** (CSS variables in base.css, NOT in the editor palette): border `#d7e1e9`, focus ring `#ffd15c`, success `#8dd8b4`.

**Layout:** container `82rem`, narrow container `68rem`, gutter `clamp(1.25rem, 4vw, 3.5rem)`, header height `5.25rem`, section spacing `7.5rem`, radius `4px / 6px / 8px / 8px` (sm/md/lg/xl).

**Type scale — verified from the design's own CSS, 24 Aug. Use these exact clamps; do not invent a scale.**

| Token | Value |
|---|---|
| step -1 | `clamp(0.82rem, 0.8rem + 0.1vw, 0.9rem)` |
| step 0 (body) | `clamp(1rem, 0.95rem + 0.2vw, 1.12rem)` |
| step 1 | `clamp(1.2rem, 1.05rem + 0.55vw, 1.55rem)` |
| step 2 | `clamp(1.55rem, 1.2rem + 1.1vw, 2.25rem)` |
| step 3 | `clamp(2.1rem, 1.45rem + 2.2vw, 3.5rem)` |
| step 4 | `clamp(2.8rem, 1.7rem + 3.8vw, 5.8rem)` |

**Other verified tokens** (base.css, not the editor palette): shadow-soft `0 1.5rem 4rem rgba(7,26,49,0.12)`, shadow-card `0 0.8rem 2.2rem rgba(7,26,49,0.08)`, border-dark `rgba(255,255,255,0.16)`, gradient-brand `linear-gradient(45deg, #1d4e89 0%, #28abe5 100%)`, ease-out `cubic-bezier(0.2,0.75,0.25,1)`, duration `320ms`, duration-fast `160ms`.

Five tokens in the source are aliases and must NOT become separate values: `--color-signal` → secondary, `--color-mint-soft` → surface-blue, `--color-charcoal` → primary-ink, `--color-paper` → surface-soft, `--color-line` → border. Collapse them.

**Editor lockdown:** `defaultPalette: false`, `defaultGradients: false`, `custom: false`, `customFontSize: false`. Editors pick from the brand palette only — brand drift is prevented structurally, not by policy.

## 4. Theme structure — every file and what belongs in it

```
wp-content/themes/synergi/
├── style.css             Theme header comment ONLY. Zero style rules.
├── theme.json            All tokens: palette, type scale, spacing, layout. §3 values.
├── functions.php         Thin. Only require_once calls into inc/. No logic.
├── inc/
│   ├── setup.php         add_theme_support, nav menus, image sizes, editor styles.
│   ├── assets.php        Enqueues. Base CSS/JS + conditional per-section loading.
│   ├── cleanup.php       Remove emoji script, oEmbed cruft, wp-block-library-theme,
│   │                     classic-theme-styles. jQuery: audit tool only, not deregistered
│   │                     while WPForms depends on it (§2.4). Disable file editor
│   │                     is a wp-config constant, noted here as a comment.
│   ├── integrations.php  GTM/GA4 deferred loading (§11). Nothing else.
│   ├── fields.php        Hand-built meta boxes: registration, render, save. See §7.
│   └── sections.php      Section loader: registers sections, tracks which a page uses,
│                         tells assets.php what CSS/JS to enqueue.
├── templates/            Page templates (classic, chosen in the editor sidebar).
│   ├── service.php       The 5 service lines. Fields-driven.
│   ├── market.php        UAE / KSA / Global Locations pages.
│   └── guide.php         The 2 guides. (First candidate to cut if time slips.)
├── sections/             One PHP partial per reusable design section.
│   ├── hero.php · service-grid.php · stat-row.php · insights.php · cta.php · …
├── parts/                header.php-included partials: nav.php, topbar.php, etc.
├── front-page.php        Homepage. Composition of sections; no unique markup inline.
├── header.php · footer.php
├── single.php            Blog post. Renders post_content. Owns the ONE <h1>.
├── archive.php           Blog listing.  page.php  Default page.
├── search.php · 404.php · index.php     (index.php = required fallback, minimal)
└── assets/
    ├── css/
    │   ├── base.css      Reset, typography, utility vars, layout primitives. <20 KB.
    │   └── sections/     One file per section, same name as its PHP partial.
    ├── js/
    │   ├── main.js       Nav toggle + shared behavior. Vanilla, defer, <10 KB.
    │   └── sections/     One file per section that needs behavior.
    └── fonts/montserrat-latin.woff2
```

Per-file rules:
- **Every PHP file** starts with `defined( 'ABSPATH' ) || exit;`.
- **Every function** is prefixed `syn_`. Every meta key is prefixed `_syn_` (underscore = hidden from the default custom-fields box).
- **A section** = `sections/name.php` + `assets/css/sections/name.css` (+ optional `assets/js/sections/name.js`). All three named identically. A section renders through a helper (`syn_section( 'name', $args )`) which also registers it for conditional asset loading. A section never enqueues anything itself.
- **Templates compose sections; they do not contain section markup.** If a template grows unique markup beyond structure, extract a section.
- **CSS files never grow an "override layer".** If the design changes, change the component file. A second file that restyles an existing component is the failure mode this project exists to escape (it's how the prototype got a 520-rule override on a 359-rule base).

## 5. Security rules

- **Escape all output.** `esc_html()` for text, `esc_attr()` for attributes, `esc_url()` for URLs, `wp_kses_post()` for editor-authored HTML. Raw `echo` of anything from the database is a bug.
- **Sanitize all input.** Field save handlers use `sanitize_text_field()`, `sanitize_textarea_field()`, `absint()`, etc. — matched to the field type.
- **Nonces + capability checks** on every meta save: verify `wp_verify_nonce()` AND `current_user_can( 'edit_post', $post_id )`, and bail on autosave/revision.
- **No `eval`, no dynamic includes, no user-controlled file paths, no direct `$wpdb` queries** unless prepared with `$wpdb->prepare()` and justified in a comment.
- **JSON meta** (the repeater) is decoded with `json_decode(..., true)`, validated for shape, and every leaf sanitized on save and escaped on output. Never trust stored meta at render time either — escape at output always.
- **No secrets in the repo.** No API keys, no credentials, no `.env` committed. The database is never committed.
- Theme code never creates users, never touches roles, never writes outside `wp-content/uploads` (and ideally not even there).

## 6. Performance rules and budget

Budgets are per page, hard, and checked at the end of every stage:

| Metric | Budget |
|---|---|
| Total page weight | < 1,000 KB |
| Requests | < 40 |
| JavaScript | < 200 KB |
| CSS | < 120 KB |
| Render-blocking scripts in `<head>` | 0 |
| LCP (measured at launch) | < 2.5 s |

How the theme stays inside them:
- **Conditional loading.** A page downloads only the CSS/JS for sections it actually renders (via `sections.php`). Base CSS is the only always-on stylesheet.
- **All JS deferred.** `wp_enqueue_script` with the defer strategy. Nothing blocks render.
- **One font file**, self-hosted, preloaded, `font-display: swap`.
- **Images through core:** `srcset`/`sizes` come free; set `fetchpriority="high"` on the LCP image per template; correct upload dimensions; WebP.
- **Animations:** IntersectionObserver + CSS transforms/opacity only (compositor-friendly). Canvas work must pause when off-screen. Everything respects `prefers-reduced-motion` — reduced means content visible with no motion, never hidden content.
- A change that breaks a budget either shrinks until it fits or is documented in the commit message with the reason. Silence is not an option.

## 7. Custom fields — hand-built, no plugin

`inc/fields.php` implements the fields with core WordPress APIs (`add_meta_box`), because no-subscription is a project decision.

- **Simple fields** (eyebrow, lede): plain inputs in a meta box, one postmeta each.
- **Repeatable groups** (capabilities: title + description + tags; quick facts: label + value): a vanilla-JS repeater UI (add / remove / reorder rows) storing ONE JSON array per group in a single postmeta key (e.g. `_syn_capabilities`). Sanitize per-leaf on save, `wp_json_encode` for storage, escape per-leaf on render.
- Field boxes appear only on pages using the matching template (check `_wp_page_template` in the meta box registration; fall back to a page-ID list if needed).
- Admin-side JS/CSS for the repeater lives in `assets/` under an `admin/` subfolder and is enqueued only on the relevant edit screens.
- Keep it boring: no drag libraries, no frameworks — up/down buttons are acceptable reordering.

## 8. SEO rules

- **Exactly one `<h1>` per page, emitted by the template**, never typed by an editor. `single.php` renders the post title as the `<h1>` (this fixes all 22 posts). Heading levels never skip.
- **Yoast owns `<title>`, meta description, canonical, OG tags.** The theme outputs none of these — no duplicate meta, ever. The theme declares `add_theme_support( 'title-tag' )` and otherwise stays out of the `<head>`.
- **URLs never change** (restriction §2.8). If one ever must, it gets a 301 and a written record.
- **Schema:** `LocalBusiness` JSON-LD on the contact/locations context, added once in a template part — check Yoast isn't already emitting an equivalent piece before adding.
- **Semantic landmarks:** one `<main>`, `<header>`/`<footer>`/`<nav>` with accessible names. Real `<article>` for posts.
- **Images:** meaningful `alt` required at upload for content images; empty `alt=""` for decorative ones. Alt text is backfilled for every image on a page as that page is migrated.
- Page titles under 60 characters; flag any over during migration, don't silently rewrite.

## 9. Accessibility rules

- Visible keyboard focus on every interactive element (the `#ffd15c` focus token). Never `outline: none` without a visible replacement.
- 4.5:1 contrast minimum for body text — verify specifically on navy/ink dark sections.
- All interactive elements reachable and operable by keyboard, including the mobile nav and any carousel.
- `prefers-reduced-motion` respected globally (see §6).
- Skip link to `#main-content` as the first focusable element.
- Forms: labels tied to inputs, errors announced, no placeholder-as-label.

## 10. Workflow rules

- **Git:** the theme folder is the repo. Small commits, one concern each, imperative messages. Every stage ends with a tagged commit (`stage-1-done`, etc.). Never commit directly on top of a broken state — fix or revert first.
- **Staging first, always.** Code is written locally/in the repo, deployed to staging, verified there. Production deployment happens only in Stage 8.
- **Measure, don't assume.** "Under budget" means a measured number recorded in the stage log, from the same tooling each time (browser devtools network panel, uncached first visit).
- **One stage at a time** with the verification gate passed (restriction §2.13). If something unexpected appears mid-stage (a plugin conflict, a content surprise), stop, record it, decide — don't improvise around it.
- **Definition of done, per template:** under budget when measured · exactly one `<h1>` · keyboard focus visible on every control · 4.5:1 body-text contrast · `prefers-reduced-motion` respected · renders correctly with JavaScript disabled · no hard-coded design value in any stylesheet · no PHP notices/warnings with `WP_DEBUG` on.

## 11. Integrations and extensibility

The theme must never block the business tools. This is plain WordPress, so all of the below work — the rules just keep them fast and deliberate.

- **Google Tag Manager / GA4:** loaded by `inc/integrations.php`, deferred until after first paint (or first interaction) so the ~556 KB analytics payload never blocks rendering. Container/measurement IDs live in an option or constant, never hard-coded in templates. GTM is the preferred single injection point: future pixels and tags go through the GTM container, not into theme code.
- **Google Search Console:** via Site Kit, which is already installed but never set up — finishing its setup is part of launch, and it also puts Search Console data in wp-admin. Yoast's sitemap keeps feeding it.
- **CRM and form automation:** forms post through the consolidated form plugin, and Bit Integrations (already active — audit what it's wired to before touching it, it may carry live CRM automation) connects submissions to the CRM. New CRM connections go through Bit Integrations or the CRM's own official plugin — never custom API calls in theme code.
- **Future features/plugins:** always possible — that's the point of staying on WordPress. Every addition passes the §2.10 four-question gate, gets its front-end payload measured before install, and loads only where used. A feature that is pure display (a new section type) is theme code, not a plugin.

## 12. Context Claude should know

- Live site: synergi.ae — WordPress 7.x, PHP 8.2, LiteSpeed, Hostinger. 48 published URLs (26 pages, 22 posts) + 10 drafts. Currently Elementor-built; this theme replaces that.
- WP-CLI is not available on this host (`proc_open`/`exec` disabled). Anything scripted against the site goes through Novamira's execute-php on staging, or through wp-admin.
- Two security items are handled outside this build (nulled "Elementor Pro Activator", Novamira on production) — the theme work never depends on them, but never weaken them either (e.g. never re-enable the file editor).
- The domain move to synergibpo.com is a separate, later event. Nothing in the theme may assume a domain — all URLs relative or via `home_url()`.
- Arabic is a future phase. The theme prepares for it structurally (logical properties, no baked-in directionality, translatable strings via `__()`/`esc_html__()` with text domain `synergi`) but ships English-only.
- The WTC Saudi site is the in-house precedent for this architecture (custom PHP theme + fields + Polylang). Useful as a pattern reference; do not copy its code or its habit of keeping field definitions only in the database.

## 12a. The design source — where the homepage comes from

The approved homepage design already exists as real HTML/CSS/JS inside the **`synergi-homepage-assets` plugin** (v2.1.0), present on both production and staging at `wp-content/plugins/synergi-homepage-assets/`. Verified inventory, 24 Aug:

| File | Size | Role in the build |
|---|---|---|
| `templates/homepage-content.html` | 46.5 KB | The homepage markup — Stage 5 splits this into `sections/*.php` |
| `assets/css/main.min.css` | 104.7 KB | The design's stylesheet — Stage 5 splits into `assets/css/sections/*.css` |
| `assets/js/main.js` | 48.7 KB | Vanilla, no jQuery, no libraries — ports across nearly as-is |
| `assets/js/why-section.js` | 5.5 KB | A second script; do not miss it |
| `assets/fonts/montserrat-latin.woff2` | 37.8 KB | Identical to `reference/montserrat-latin.woff2` |
| `assets/icons/*.svg` | 7 files | accounting, human-resources, marketing, procurement, **project-management**, technology-ai, favicon |
| `assets/svg/connection-field.svg` | 563 B | Decorative |

Three things to know before touching it:

1. **39 `.bak*` files sit alongside the live ones** (the previous dev versioned by copying filenames). Copy only the files in the table. Never copy a `.bak`.
2. **There are 7 icons but only 5 service pages** — a `project-management` icon exists with no matching page. Ask before assuming a sixth service line; do not invent the page.
3. `main.min.css` declares `:root` **8 times**; the table in §3 already holds the values that actually win. Consolidate to one layer — never carry the eight blocks across.

## 13. Write code that is easy to debug

The person debugging this theme in a year may not be the person who wrote it, and may be doing it at midnight before a launch. Every rule here exists to make a problem findable in minutes, not hours.

**Traceability — anything on screen leads back to exactly one file:**
- One name everywhere. A section called `hero` is `sections/hero.php`, `assets/css/sections/hero.css`, `assets/js/sections/hero.js`, and every one of its CSS classes starts `syn-hero`. Seeing a class in the browser inspector tells you which files to open, always.
- The grep rule: every class name written in a template must be findable verbatim in exactly one CSS file. No classes assembled from PHP variables or string concatenation, no dynamic function names, no clever indirection. Boring, explicit code beats compact code — compact saves ten seconds writing and costs an hour debugging.
- Every section wraps its output in HTML comments: `<!-- syn-section: hero -->` … `<!-- /syn-section: hero -->`. View-source on any page then shows exactly which partials built it and in what order. The bytes are negligible.

**Comments — explain why, not what:**
- Every file opens with a 2–4 line header comment: what this file does, what loads it, and what depends on it.
- Every `syn_` function gets a short docblock: what it does, its parameters, what it returns, and any side effect (enqueues something, writes meta, registers a hook).
- Every section partial's header documents the `$args` it expects, with types and an example call.
- Inside code, comment the decisions a reader cannot deduce: why a workaround exists, why an order matters, why a value is what it is — with a pointer to the doc that decided it (e.g. `// per CLAUDE.md §6: deferred so GA never blocks paint`). Do not narrate the obvious (`// loop over posts` helps no one).
- If a line needs a comment to explain *what* it does, first try rewriting the line so it doesn't.

**Fail loudly in development, gracefully in production:**
- The theme defines a `SYN_DEBUG` constant (true on staging via wp-config, absent/false on production). With it on: unminified assets load, and the section loader prints an HTML comment per page listing which sections were registered and which stylesheets it enqueued — so "why is this CSS missing?" answers itself in view-source.
- All development happens with `WP_DEBUG` on, and code is only done when it produces zero notices, warnings, and deprecations.
- Never suppress errors with `@`. Never fail silently: a field save that rejects input surfaces an admin notice saying what was rejected and why; a section called with missing required `$args` renders an HTML comment naming the missing key when `SYN_DEBUG` is on, and renders nothing (not broken markup) when it is off.
- Logging goes through `error_log()` with a `[synergi]` prefix so the theme's messages are separable in the server log. No `console.log` reaches production JS; debug logging in JS is gated on a `synDebug` flag the loader sets only when `SYN_DEBUG` is true.

**Structure that keeps debugging local:**
- Small functions with one job. If a function needs scrolling, split it.
- No hidden coupling: a section never depends on another section having run first. Shared behaviour lives in a named helper both call.
- CSS files open with a one-line contents note, and selectors stay flat (max two levels): deep nesting is where "why doesn't my override apply" comes from.
- Every Git commit message says why, not just what — `git log` on a file should read as the file's decision history. One concern per commit, so `git bisect` can find any regression.
