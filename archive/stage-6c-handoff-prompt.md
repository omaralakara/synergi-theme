# Synergi theme — Stage 6c handoff prompt

Paste everything below the horizontal rule into a new, clean Claude chat.
Written 27 August 2026, mid-Stage-6c, after the service template was built and
reviewed.

---

Read `CLAUDE.md` in this folder first — it overrides anything I say in chat.
Then read this whole brief before writing any code.

## 1. Do this first

**One change, then stop and show me.**

> On `templates/service.php`, make the hero photograph the **background** of the
> band rather than a column beside the copy. Full-bleed image, the copy sitting
> on top of it, with whatever navy scrim is needed to keep the text at 4.5:1.
> Everything else about the hero stays exactly as it is.

Nothing else on the page needs changing. Once that looks right, the service
template is signed off and we move to §7.

Constraints that apply to this specific change:

- The image must stay a real attachment through `wp_get_attachment_image()` or
  an equivalent that keeps `srcset`/`sizes` — do not drop to a CSS
  `background-image` with a hard-coded URL. It is the LCP element, so it keeps
  `fetchpriority="high"`.
- Body text over the photograph must measure **4.5:1 or better** (CLAUDE.md §9).
  Say what you measured, not that you checked.
- It must still work with no image set — the band falls back to the flat navy.
- `parts/page-header.php` is shared with `page.php`, `single.php` and
  `archive.php`. Those must render exactly as they do now.

## 2. What this project is

A hybrid WordPress theme called `synergi` replacing an Elementor build on
synergi.ae. PHP templates in Git render the site, `theme.json` holds every
design token, and designed pages get their text and pictures from hand-built
custom fields — no ACF, no page builder, no build step.

**The one rule:** anything describing how the site looks lives in a file;
anything a person wrote lives in the database.

## 3. Where the build is

Stages 0–5 complete and tagged. **Stage 6a complete and tagged
`stage-6a-done`.** Stage 6c is in progress and not yet tagged.

The theme is **91 files** on branch `main`. Last two commits:

```
cb5d50e  Put the service page back on Synergi's own colours and photograph
216271c  Build the service template: one file, six service lines
```

### Stage 6a — done

- `inc/fields.php` — the field engine. Four field types (text/textarea, a JSON
  repeater with a vanilla-JS add/remove/reorder UI, an image field through the
  core media modal, a link field) and nine leaf types. Nonces, capability
  checks, per-leaf sanitising, and an admin notice naming anything rejected.
- `inc/records.php` — site records in one `syn_records` option, edited at
  **Settings → Site records**. Three records built: `services`, `figures`,
  `locations`. `partners`, `events` and `social` are deferred by decision.
- `sections/numbers.php` reads the `figures` record, with its old hard-coded
  values as the empty-value fallback.

### Stage 6c — built, under review

- `templates/service.php` — one template, six service lines. Composes ten
  sections, five of them new.
- New partials: `capabilities` (interactive explorer), `process`, `case-study`,
  `faq` (native `<details>`, no JS), `related-services`.
- `inc/service-fields.php` — the five field groups the template reads.
- `parts/page-header.php` extended: given a photograph it becomes the service
  hero; given none it is the same title band it always was.
- **Human Resources is built and published on staging** at
  `/human-resources-rebuild/` (page ID 10568), with real copy.

## 4. Rules that must not break

All of CLAUDE.md, but these are the ones this stage keeps bumping into:

- **Never touch the homepage.** It is frozen by the business. Section 6 below is
  how you prove you did not.
- **No URL changes.** Adding URLs is fine; moving or redirecting an existing one
  is not.
- **No hard-coded design values.** Every colour, size, radius and duration
  resolves to a `theme.json` custom property through the alias layer in
  `base.css`. A hex in a stylesheet is a bug.
- **Logical CSS properties only.** No `left`/`right`.
- **The grep rule.** Every class in a template is findable verbatim in exactly
  one CSS file. Never assemble a class name from a PHP variable — write the six
  service accents out in full, the way `sections/services.php` already does.
- **One `<h1>` per page**, emitted by the template. `header.php` already opens
  `<main id="main-content">` — never open a second one.
- **Escape on output, sanitise on input**, always, including stored meta.
- **Renders correctly with JavaScript disabled.** Enhancements are additive: the
  capability rail ships `hidden` and the script unhides it.
- **Do not invent facts.** Copy comes from the company profile PDF, the live
  site, or the planning docs. Anything without a source stays marked `[TO WRITE]`
  and is raised, not filled in.

## 5. How to work on staging

**WP-CLI does not run** — `proc_open` and `exec` are disabled. The ability is
registered but errors. Use the Novamira MCP connection to staging:

- `novamira/execute-php` for anything read-only or scripted.
- To deploy: build the zip with `tools/build-zip.ps1`, then
  `novamira/create-upload-link` → `curl -X PUT` the zip → `execute-php` with
  `ZipArchive::extractTo(WP_CONTENT_DIR . '/themes')`.
- `novamira/write-file` refuses PHP outside its sandbox, so the zip route is the
  only way to ship a template.

**Rollback:** the pre-6c theme is backed up on the server at
`wp-content/uploads/syn-deploy/synergi-before-6c` (78 files). Copy it back over
`wp-content/themes/synergi`.

**LiteSpeed minifies and caches the delivered HTML**, which strips the theme's
`<!-- syn-section: … -->` comments. Purge with
`do_action('litespeed_purge_all')` before measuring anything.

Staging has `WP_DEBUG` and `SYN_DEBUG` on. The debug log carries a constant
stream of Yoast and Novamira deprecations that are **not** ours — filter for
`synergi` or the theme path before concluding anything.

## 6. How to prove the homepage is untouched

Do this after any deploy. It is not optional, and "I didn't change it" is not
the evidence.

1. `do_action('litespeed_purge_all');`
2. Fetch `get_permalink(10547)` — the "Homepage (rebuild)" page.
3. Normalise LiteSpeed's own metadata before comparing: one `?ver=[0-9a-f]+`
   query string and two `YYYY-MM-DD HH:MM:SS` timestamps. These change on every
   purge and are not theme output.
4. Normalised, it must be **byte-identical**. The baseline is 91,088 bytes
   normalised.
5. Cross-check: LiteSpeed names its combined CSS and JS files after a hash of
   their contents. Those filenames must be unchanged —
   `1d267eceece24d166fc9ca26d407b8d8.css` and
   `58bb460aa3a4b8a8755aafc88bf8e1e1.js`.

The homepage never renders `parts/page-header.php` and links none of the service
stylesheets, so structurally it should not move. Measure anyway.

## 7. What comes after the hero change

In order. Stop and show me between each.

1. **Project Management.** The sixth service line, which has no page today. It
   must be built by **entering content only, with zero code changes** — that is
   the test of "one template, six lines". **It is blocked:** the company profile
   gives eight capability titles and no descriptions, and no opening sentence.
   That copy has to be written and approved first. Ask me for it.
2. **The other four service pages** — Technology & AI, Marketing, Procurement,
   Accounting. Content entry, rebuilt on the template at their existing URLs.
   Procurement is the awkward one: 381 words, a different structure ("Why our
   procurement services stand out", a process block), and an **89-character
   page title** that is over the 60-character limit. Flag it, do not silently
   rewrite it.
3. **Tag `stage-6c-done`.**

Held until the business answers whether the five Solutions pages have copy
written: 6d (the solutions template), `market.php`, `guide.php`.

Deferred by decision, not cancelled: **6b**, retrofitting the twelve homepage
partials onto fields. It must land before handover — a site where the homepage
is the only page needing a developer is not a finished handover.

## 8. Open questions, and what is already answered

**Still open, ask before building the affected page:**

1. Project Management's eight capability descriptions and opening sentence.
2. The fifth HR FAQ — mobilisation time. Four are answered from the profile; the
   fifth has no source anywhere and was left out rather than invented.
3. Which client names may be published. The profile identifies real clients,
   including one Abu Dhabi government entity by name. The HR case study on the
   built page uses the client **type** only.
4. Whether the "10–15% direct savings" figure is cleared for the public site.
5. Which office emails and phone numbers are public, and on which domain — the
   deck uses `synergibpo.com`, the live site is `synergi.ae`, and the domain
   move is a separate later event.
6. The legal entity and delivered function per office, for the `locations`
   record.

**Answered since the last handoff, do not re-ask:**

- **Partner names are not missing.** Five partner logos sit in the media library
  with the names in their alt text: Menaitech, Odoo, Lexzur, Innovawave, ICXI.
  The older brief lists this as blocked; it is not.
- **`theme.json` already carries a distinct accent gradient for all six service
  lines**, Project Management included.
- **Project Management has eight capabilities in the profile, not seven.**
- **`max_input_vars` is 5000** on this host, not the 1000 default, so long
  repeaters are safe.
- **Yoast is not emitting `FAQPage`** on the service pages, so the theme emits
  it. Re-check with `has_block('yoast/faq-block')` if that ever changes.

## 9. Decisions taken during 6c, so you do not undo them

- **The hero carries no per-service colour.** `theme.json`'s six `serviceAccent`
  gradients are card-sized accents — teal, bronze, violet — and at full-bleed
  scale they read as a different brand. The six pages are told apart by their
  photograph. The accent survives only as a 40×3px rule above each capability.
- **Each service page uses the photograph already on its live page.** They are
  navy duotones and already on-brand. HR uses attachment 8125; its alt text was
  blank and has been backfilled.
- **Headings are `--syn-navy`, body is `--syn-text-soft`** — matching the
  homepage exactly. `navy-deep` was wrong and has been corrected.
- **The process band mirrors the numbers band's blue**, rather than inventing a
  second dark surface. Ink read as near-black.
- **No figures in the hero** — the numbers band already says them.
- **No blog band on service pages** — an untargeted list of recent posts is
  filler. It comes back when posts are tagged to a service.
- **The case study is postmeta, not a post type.** One featured study per page
  is page-specific. Custom post types are out of scope for this stage; the
  case-study archive is a later one.
- **`related-services` is its own partial** rather than reusing
  `sections/services.php`. That deck is a fanned carousel with controls and
  swipe — about nine times the payload to render five links — and its cards
  expect a per-card capability list the services record does not carry.
- **The admin shows what is stored, not the registered defaults.** Pre-filling
  a repeater would write the defaults into the database on first save and stop
  later changes in code ever reaching the page.

## 10. When you finish a piece of work

Tell me, briefly:

1. What changed, file by file.
2. What you measured, with the numbers — not that you checked.
3. The homepage proof from §6.
4. Anything still open or assumed.
5. Whether it is safe to move on.

Keep it short. If something cannot be finished, finish everything else and say
plainly what is left and why.
