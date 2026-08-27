# Synergi theme — handoff prompt after Stage 6

Paste everything below the horizontal rule into a new, clean Claude chat.
Written 28 August 2026, at the close of Stages 6b and 6c.

---

Read `CLAUDE.md` in this folder first — it overrides anything I say in chat.
Then read this whole brief, and `open-questions.md`, before writing any code.

## 1. What this project is

A hybrid WordPress theme called `synergi` replacing an Elementor build on
synergi.ae. PHP templates in Git render the site, `theme.json` holds every
design token, and designed pages get their text and pictures from hand-built
custom fields — no ACF, no page builder, no build step.

**The one rule:** anything describing how the site looks lives in a file;
anything a person wrote lives in the database.

## 2. Where the build is

Stages 0–5 complete and tagged. **Stage 6a, 6b and 6c are complete and
tagged** (`stage-6a-done`, `stage-6b-done`, `stage-6c-done`). The theme is
**92 files** on branch `main`, working tree clean.

### What exists and works

- **Six service pages**, all on `templates/service.php`, all at their real
  URLs: `/our-services/{human-resources, technology-ai, marketing,
  procurement, accounting, project-management}/`. Project Management was
  created by entering content only, zero code changes.
- **Five field groups** on every service page: intro, capabilities, how we
  work, case study, frequently asked.
- **Six site records** at Settings → Site records, each read by more than one
  page: service lines, key figures, locations, the why band's heading, the why
  band's cards, and the closing call to action.
- **Eight field groups on the homepage** — hero, services, shared services,
  industries, partners, blog, instagram, podcast.
- The homepage has been byte-identical throughout, verified after every
  deploy at **90,983 bytes normalised**.

### What is deliberately NOT editable, and why

Do not "fix" these — each is a decision with a reason:

- The hero photograph is the page's **Featured Image**. Already editable,
  already in the sidebar; a second control would give one picture two owners.
- The shared-services **hub diagram** — its bubbles, the middle one, the
  numbered steps — is layout, not copy (CLAUDE.md §7c). Its heading,
  paragraphs, markets, pills and button *are* fields.
- The blog band's **post count and excerpt length** are tuning, not words.

## 3. Do this first — the one decision blocking a small job

**The six service cards on the homepage are still hard-coded.** They are the
only part of the homepage a developer is still needed for.

The `services` record already holds those six, but its `summary` is short —
*"Record to report, and everything under it."* — because that is what the
related-services deck on service pages needs. The homepage deck uses a longer
one: *"Support for day-to-day finance processes, transaction cycles, analysis,
and reporting."*

Same six services, two deliberately different summaries. Ask me which:

- **(a)** add a second column to the record (`card_summary`, plus `label` and
  `capabilities`) so each deck keeps its own wording, or
- **(b)** both decks say the same thing, and one summary serves both.

Then wire `sections/services.php` to the record. Either way the homepage must
stay byte-identical — seed the record from the strings the partial prints today.

## 4. Then: Stage 7 — navigation and structure

**Stage 7 is blocked on me, not on code.** It cannot start until I have
answered the nine structural decisions from `synergi-build-plan.md` §6. Ask me
for them; do not decide them yourself:

1. `/synergi-homepage-2026-draft-build/` is published and indexable — unpublish.
2. `/full-episodek6vl3n8...` is live with a machine-generated slug — delete or
   redirect.
3. Two contact pages, `/contact-us/` and `/connect/` — consolidate?
4. Three content hubs — Blog, Media, Executive Podcast overlap.
5. The ICXI partnership page is news built as a page — move to the blog?
6. Ten drafts — publish, delete or leave.
7. Four menus exist, one is assigned — which survive?
8. The Element Pack mega-menu — does the new design keep one?
9. 806 of 906 images have no alt text — fix as we migrate, schedule the rest.

Also outstanding, and also mine to answer: **`open-questions.md`** in this
folder. Six questions, each with what it blocks. Nothing there blocks Stage 7.

**Stage 6d is held**, not cancelled: the solutions template, `market.php` and
`guide.php`, waiting on whether the five Solutions pages have copy written.
That is question 5 in `open-questions.md`.

## 5. How to work on staging

**WP-CLI does not run** — `proc_open` and `exec` are disabled. Use the Novamira
MCP connection to staging:

- `novamira/execute-php` for anything read-only or scripted.
- To deploy: build the zip with `tools/build-zip.ps1`, then
  `novamira/create-upload-link` → `curl -X PUT` the zip → `execute-php` with
  `ZipArchive::extractTo(WP_CONTENT_DIR . '/themes')`.
- `novamira/write-file` refuses PHP outside its sandbox, so the zip route is the
  only way to ship a template.

**Rollback:** `wp-content/uploads/syn-deploy/synergi-before-6c` is the pre-6c
theme. Git tags cover everything since.

### Three traps that cost time in the last session

1. **`opcache_reset()` does not reload the current request.** After extracting
   the zip, the request that did the extracting is still running the OLD PHP.
   Verify in a *separate* `execute-php` call or you will chase a phantom.
2. **LiteSpeed serves cached HTML straight through a purge.** Add
   `?nc=<random>` to any URL you are checking after a content change.
3. **Never write `\uXXXX` escapes in an `execute-php` payload.** PHP
   single-quoted strings do not interpret them and the transport eats the
   backslash, so `—` reaches the database as the literal text `u2014`. It
   shipped to the live HR page that way. Type the real character, and sweep
   `_syn_%` postmeta for `u[0-9a-f]{4}` after any content entry.

## 6. How to prove the homepage is untouched

Do this after any deploy. "I didn't change it" is not evidence.

1. `do_action('litespeed_purge_all');`
2. Fetch `get_permalink(10547)`.
3. Normalise **three** things, not two: one `?ver=[0-9a-f]+`, the
   `YYYY-MM-DD HH:MM:SS` timestamps, and `data-locatornonce="[0-9a-f]+"` —
   that last one is a rotating nonce from the Instagram plugin and is not
   theme output.
4. Compare against `wp-content/uploads/syn-deploy/base-homepage-v2.html` on
   the server. It must be **byte-identical at 90,983 bytes**.

The older brief named two LiteSpeed bundle filenames to cross-check. Those are
stale — bundle names change whenever any plugin's CSS or JS changes. Normalise
`/wp-content/litespeed/(css|js)/[0-9a-f]{32}\.(css|js)` instead of matching it.

## 7. How to verify contrast properly

CLAUDE.md §9 wants 4.5:1 and the last session measured it rather than eyeballing
it. The method, worth reusing: drive headless Chrome over CDP, hide the copy,
screenshot what the browser actually painted, feed the PNG back into the page as
a `data:` URL, draw it to a canvas and sample every pixel behind each piece of
text. Use `document.createRange()` for the tight glyph box — an element's block
box is as wide as its column and measuring it reports a worse ratio than any
reader experiences.

Current numbers across the six service pages: eyebrow 9.61–10.64, h1
13.81–16.22, lede 6.83–10.09, buttons 16.05–17.29, nav 5.81–13.82. Lowest
anywhere **5.81**. Exclude closed dropdown items — `.syn-submenu` paints its own
opaque navy, so they never sit on the photograph when actually visible.

## 8. Budget, measured this way

Measure **gzipped**, not raw. `wp_remote_get()` decompresses, so `strlen($body)`
is uncompressed and will look like a failure that is not there.

Homepage as delivered: CSS **32.7 KB** / 120 · JS **113.9 KB** / 200 · HTML
18.8 KB. `stage-5-measurement.md` records JS failing at 1,152 KB; LiteSpeed's
combine — fix #1 in that document — is now doing its job.

## 9. Decisions taken, so you do not undo them

- **The service hero uses the homepage hero's shade**, not a navy wash:
  `--syn-ink`, two stacked gradients, `sections/hero.css` §2 is the original.
  Only the stop positions differ, and the block-start is darker at 58%/50%
  because the nav sits on this band and an editor picks the photograph.
- **`90deg` is a physical direction** and CLAUDE.md §2.11 rules it out. It is
  there because the homepage hero already does exactly this; both need the same
  one-line flip for Arabic, and keeping them identical is the point.
- **Case studies use client TYPE, never a name**, until names are cleared —
  question 1 in `open-questions.md`.
- **FAQ JSON-LD uses `JSON_HEX_TAG`, never `esc_html()`.** esc_html turns `"`
  into `&quot;`, and an entity inside `<script>` is never decoded, so every FAQ
  block on the site was invalid JSON while looking perfect in view-source.
- **Marketing has no photograph.** Its Photograph field is deliberately empty so
  it reads as outstanding; it falls back to the homepage hero image. Question 6
  in `open-questions.md`.
- **`/human-resources-rebuild/` (page 10568) is still published** as the review
  copy. Its content now lives at the real URL. Delete it before launch.

## 10. When you finish a piece of work

Tell me, briefly:

1. What changed, file by file.
2. What you measured, with the numbers — not that you checked.
3. The homepage proof from §6.
4. Anything still open or assumed.
5. Whether it is safe to move on.

Keep it short. If something cannot be finished, finish everything else and say
plainly what is left and why.
