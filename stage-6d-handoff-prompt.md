# Synergi theme — handoff prompt, Stage 6d onwards

Paste everything below the horizontal rule into a new, clean Claude chat.
Written 28 August 2026, after 6a, 6b and 6c shipped and Stage 6 was re-planned.

---

Read `CLAUDE.md` in this folder first — it overrides anything I say in chat.
Then read these three, in order, before writing any code:

- **`stage-6-remaining-plan.md`** — what is left of Stage 6 and in what order.
  This is the plan you are working to.
- **`sitemap-and-navigation.md`** — the structure decisions behind it: every row
  of the content architecture mapped to a page, a section, a record or a post
  type.
- **`open-questions.md`** — six questions waiting on me. Three of the six
  remaining pieces of Stage 6 are blocked on these, not on code.

## 1. What this project is

A hybrid WordPress theme called `synergi` replacing an Elementor build on
synergi.ae. PHP templates in Git render the site, `theme.json` holds every
design token, and designed pages get their text and pictures from hand-built
custom fields — no ACF, no page builder, no build step.

**The one rule:** anything describing how the site looks lives in a file;
anything a person wrote lives in the database.

## 2. Where the build is

Stages 0–5 complete and tagged. **6a, 6b and 6c complete and tagged**
(`stage-6a-done`, `stage-6b-done`, `stage-6c-done`). Branch `main`, working tree
clean, **92 files**.

**Rebuilt on the new theme — 8 pages:** the homepage and the six service pages,
plus `/human-resources-rebuild/`, a review copy to delete before launch.

**Still Elementor — 13 pages:** About Us · Our Approach (1,118 words, the
heaviest on the site) · Our Leadership · Engagement Team · Global Locations ·
Contact Us · Our Services listing · Media · Executive Podcast · Blog · Connect ·
Shared Services UAE · BPO Services KSA.

**Templates:** `homepage.php`, `service.php`. **Post types:** none.

**Records built:** `services`, `figures`, `locations`, `why`, `why_cards`,
`final_cta`, at Settings → Site records.
**Records missing:** `partners`, `events`, `social`.

## 3. Do this first — 6d, then 6f

**6d — the three missing records.** `partners`, `events`, `social`. Deferred
back in 6a and now blocking the homepage partners band, the new Upcoming events
section and Contact Us. Nothing blocks this. Follow the shape of the six that
exist in `inc/records.php`; note the `single` flag added in 6b for records that
hold one thing rather than a list.

**Then 6f — About Us and its two children.** `/our-leadership/` for the team,
`/our-approach/` rebuilt. Blocked by nothing, and it is the largest pile of real
content in the project, so moving it early takes the biggest scheduling risk out.

6e, 6g and 6h all wait on answers. Ask me for them rather than guessing.

## 4. One decision I still owe you

**The six service cards on the homepage are hard-coded** — the only part of the
homepage still needing a developer. The `services` record holds those six, but
its `summary` is short — *"Record to report, and everything under it."* —
because that is what the related-services deck on service pages needs. The
homepage deck uses a longer one: *"Support for day-to-day finance processes,
transaction cycles, analysis, and reporting."*

Same six services, two deliberately different summaries. Ask me whether to add a
second column (`card_summary`, plus `label` and `capabilities`) or make both
decks say the same thing. Either way the homepage must stay byte-identical —
seed the record from the strings the partial prints today.

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

### Three traps that cost real time. Do not rediscover them.

1. **`opcache_reset()` does not reload the request that calls it.** After
   extracting the zip, the request that did the extracting is still running the
   OLD PHP. Verify in a *separate* `execute-php` call, or you will chase a
   phantom — last session it looked exactly like "my new records are not
   registering".
2. **LiteSpeed serves cached HTML straight through a purge.** Add
   `?nc=<random>` to any URL you check after a content change.
3. **Never write `\uXXXX` escapes in an `execute-php` payload.** PHP
   single-quoted strings do not interpret them and the transport eats the
   backslash, so `—` reaches the database as the literal text `u2014`. It
   shipped to the live HR page that way and rendered as "Synergi u2014 as
   consultants". Type the real character, and sweep `_syn_%` postmeta for
   `u[0-9a-fA-F]{4}` after any content entry.

## 6. How to prove the homepage is untouched

Do this after any deploy. "I didn't change it" is not evidence.

1. `do_action('litespeed_purge_all');`
2. Fetch `get_permalink(10547)`.
3. Normalise **three** things: one `?ver=[0-9a-f]+`, the
   `YYYY-MM-DD HH:MM:SS` timestamps, and `data-locatornonce="[0-9a-f]+"` — that
   last is a rotating nonce from the Instagram plugin, not theme output.
4. Compare against `wp-content/uploads/syn-deploy/base-homepage-v2.html` on the
   server. **Byte-identical at 90,983 bytes.**

Older briefs name two LiteSpeed bundle filenames to cross-check. Those are
stale — bundle names change whenever any plugin's CSS or JS changes. Normalise
`/wp-content/litespeed/(css|js)/[0-9a-f]{32}\.(css|js)` instead.

If the homepage legitimately must change — 6e changes it on purpose — re-baseline
deliberately and say so, rather than loosening the check.

## 7. How to verify contrast

CLAUDE.md §9 wants 4.5:1, measured rather than eyeballed. The method: drive
headless Chrome over CDP, hide the copy, screenshot what the browser actually
painted, feed the PNG back into the page as a `data:` URL, draw it to a canvas
and sample every pixel behind each piece of text. Use `document.createRange()`
for the tight glyph box — an element's block box is as wide as its column, and
measuring that reports a worse ratio than any reader experiences.

Current across the six service pages: eyebrow 9.61–10.64, h1 13.81–16.22, lede
6.83–10.09, buttons 16.05–17.29, nav 5.81–13.82. Lowest anywhere **5.81**.
Exclude closed dropdown items — `.syn-submenu` paints its own opaque navy, so
they never sit on a photograph when actually visible.

## 8. Budget — measure gzipped

`wp_remote_get()` decompresses, so `strlen($body)` is uncompressed and will look
like a failure that is not there. Homepage as delivered: CSS **32.7 KB** / 120 ·
JS **113.9 KB** / 200 · HTML 18.8 KB.

## 9. Decisions taken, so you do not undo them

- **The service hero uses the homepage hero's shade**, not a navy wash:
  `--syn-ink`, two stacked gradients, `sections/hero.css` §2 is the original.
  Only stop positions differ, and the block-start is darker at 58%/50% because
  the nav sits on that band and an editor chooses the photograph.
- **`90deg` is a physical direction** and CLAUDE.md §2.11 rules it out. It is
  there because the homepage hero already does exactly this; both need the same
  one-line flip for Arabic, and keeping them identical is the point.
- **The hero photograph is the page's Featured Image**, deliberately not a field.
- **The shared-services hub diagram is layout, not copy** (§7c). Its heading,
  paragraphs, markets, pills and button are fields; the diagram is not.
- **Case studies use client TYPE, never a name**, until names are cleared.
- **FAQ JSON-LD uses `JSON_HEX_TAG`, never `esc_html()`.** esc_html turns `"`
  into `&quot;`, and an entity inside `<script>` is never decoded — every FAQ
  block on the site was invalid JSON while looking perfect in view-source.
- **Marketing has no photograph.** Its field is deliberately empty so it reads
  as outstanding; it falls back to the homepage hero image.
- **Case studies and podcast episodes are post types, and are NOT Stage 6** —
  new content types come after the templates are proven.

## 10. When you finish a piece of work

Tell me, briefly:

1. What changed, file by file.
2. What you measured, with the numbers — not that you checked.
3. The homepage proof from §6.
4. Anything still open or assumed.
5. Whether it is safe to move on.

Keep it short. If something cannot be finished, finish everything else and say
plainly what is left and why.
