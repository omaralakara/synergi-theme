# Synergi theme — build stages and prompts

The controlled, step-by-step path from nothing to launch. Ten stages, numbered 0 to 9. Each stage has: a goal, what must be true before it starts, the exact prompt to give Claude, the files it touches, a verification checklist, and a rollback. **A stage does not start until the previous stage's checklist passes — no exceptions.** This gate is what guarantees we never break anything we can't undo.

Every prompt below assumes Claude has read `CLAUDE.md` in this folder first. Start every session with:

> Read CLAUDE.md and synergi-build-stages.md in this folder. We are on Stage N. Confirm the rules and the stage goal before doing anything.

---

## Stage 0 — Safety net

**Goal:** make it impossible to lose anything, before a single line of theme code exists.

**Before starting:** nothing. This is the first thing that happens.

**Steps (mostly done by you in Hostinger, Claude assists):**
1. Clone production to a staging site (Hostinger → Website → Staging).
2. Create a Git repository for the theme folder (`synergi-blocks-theme` repo already exists on your desktop — the theme will live inside it, content/database never committed).
3. On staging AND production, install a stock WordPress theme (e.g. Twenty Twenty-Five), left inactive. Theratio is currently the only theme installed, so a failed theme activation would have nothing to fall back to. Two minutes, cheapest insurance in the plan.
4. Take a fresh backup and **prove it restores** (restore it onto staging or a throwaway). An untested backup is not a backup.
5. Confirm who can access staging and note its URL and credentials somewhere safe (not in the repo).

**Prompt for Claude:**
> Stage 0. Help me verify the safety net: I've created staging at [URL]. Walk me through checking that (1) staging is a true copy, (2) the fallback theme is installed on both sites, (3) the latest backup restores correctly. Don't touch production. Produce a short written record of what was verified.

**Verify before moving on:**
- [ ] Staging loads and mirrors production content
- [ ] Fallback theme visible in Appearance → Themes on both sites
- [ ] A backup restore has actually been performed and checked
- [ ] Git repo initialized, first commit made
- [ ] Nothing on production changed

**Rollback:** nothing to roll back — that's the point of doing this first.

---

## Stage 1 — Theme skeleton

**Goal:** an empty but valid theme that activates on staging without errors.

**Before starting:** Stage 0 checklist passed.

**Prompt for Claude:**
> Stage 1. Create the synergi theme skeleton exactly per CLAUDE.md §4: style.css (header only), functions.php (thin), inc/setup.php, inc/assets.php, inc/cleanup.php, empty placeholder templates (index.php, header.php, footer.php, page.php), and the assets folder structure. No design yet — a plain readable page is success. Every PHP file gets the ABSPATH guard and syn_ prefixes. Then give me the zip to upload to staging.

**Files:** the whole skeleton per CLAUDE.md §4.

**Verify:**
- [ ] Theme activates on staging with `WP_DEBUG` on — zero notices/warnings
- [ ] A page and a post render (unstyled is fine)
- [ ] View source: no jQuery, no emoji script, no block-library-theme CSS
- [ ] Committed and tagged `stage-1-done`

**Rollback:** switch staging back to Theratio. (This is why the old theme stays installed.)

---

## Stage 2 — Tokens and fonts

**Goal:** every design token live and reachable, front end and editor, before anything is built on them.

**Before starting:** Stage 1 verified.

**Prompt for Claude:**
> Stage 2. Write theme.json with the exact canonical tokens from CLAUDE.md §3 — palette, Montserrat with the self-hosted woff2, fluid type scale, spacing steps, 82rem container, and the editor lockdown settings (defaultPalette/custom/customFontSize all false). Add assets/css/base.css with the utility variables (border, focus, success), the reset, and base typography using only the theme.json custom properties. Logical properties only. Then tell me exactly how to verify tokens reach both the front end and the block editor.

**Verify:**
- [ ] Front end: computed styles show `--wp--preset--color--navy` etc. resolving to the §3 values
- [ ] Editor: color picker shows ONLY the 11 brand colors; font-size picker shows only the scale; no custom color/size option
- [ ] Montserrat renders in the editor AND the front end (this was the named risk in the plan)
- [ ] Not a single hex value in base.css other than via variables
- [ ] Tagged `stage-2-done`

**Rollback:** git revert; theme still activates (skeleton unaffected).

---

## Stage 3 — Header, footer, nav and one real page, measured

**Goal:** header, footer, navigation and `page.php` — one complete real page, end to end, under budget. If one page can't hit budget, 48 won't.

**Before starting:** Stage 2 verified. The design is frozen (`design-source/`), so nothing else gates this.

### The header and footer are built HERE — and this is why

The design source contains **no header or footer markup**: `homepage-content.html` starts at `<main>`. But the design CSS fully styles both. Verified class inventory:

- **Header:** `.site-header`, `.header-inner`, `.header-cta`, `.nav-list`, `.menu-toggle`, `.menu-open`, `.submenu`, `.submenu-toggle`, `.submenu-wide`
- **Footer:** `.site-footer`, `.footer-grid`, `.footer-brand`, `.footer-heading`, `.footer-conversation`, `.footer-links`, `.footer-email`, `.footer-bottom`

So this is **markup work against an existing CSS contract**, not a design exercise. Write HTML that matches those class names exactly and the design applies itself.

They belong in Stage 3 rather than earlier or later because: every page needs them to render at all, Stage 3's whole gate is "measure one real page" and you cannot measure a page with no header, and they are the most-reused code in the theme — everything built after Stage 3 inherits them. Building them later would make every earlier measurement invalid.

**Nav specifics:** the design has **no mega-menu**. `.submenu-wide` is a `21rem` two-column dropdown collapsing to one column — build a plain accessible dropdown with a two-column variant. `main.js` already handles `menu-toggle` and `submenuToggles`; port that behaviour rather than reinventing it.

**Footer content** comes from the current Elementor Pro footer template (#9031) on staging — lift the content, not the markup. The CSS class names tell you the structure: brand block, a "conversation"/CTA block, link columns, an email, and a bottom bar.

**Prompt for Claude:**
> Stage 3. Build header.php, parts/nav.php, footer.php and page.php per CLAUDE.md. There is no header/footer HTML in design-source — the CSS is the contract, so write markup matching exactly these classes: [paste the two lists above]. Mobile-first, logical properties only, skip link to #main-content, one h1 from the template, all JS deferred and vanilla, nav behaviour ported from design-source/assets/js/main.js. Nav is a simple dropdown with a two-column `.submenu-wide` variant — no mega-menu. Pull the footer's content from the current site's footer. Then apply it to the About Us page on staging and measure total weight, requests, CSS, JS and blocking scripts against the §6 budget.

**Verify:**
- [ ] About-style page measured: < 1 MB, < 40 requests, 0 blocking scripts, CSS/JS inside budget
- [ ] Header and footer render with the design's styling applied — no unstyled elements, meaning every class name matched the CSS contract
- [ ] Dropdown opens, closes, and the two-column `.submenu-wide` variant displays correctly
- [ ] Keyboard walk-through: skip link, nav fully operable (open/close/escape), focus visible everywhere
- [ ] Renders correctly with JS disabled — nav must still be usable
- [ ] Page looks right on 360px, 768px, 1440px widths
- [ ] Tagged `stage-3-done`

**Rollback:** git revert to `stage-2-done`.

---

## Stage 4 — The blog

**Goal:** `single.php` (with the H1 fix), `archive.php`, `search.php`, `404.php` — and the 22 posts migrated on staging.

**Before starting:** Stage 3 verified.

**Prompt for Claude:**
> Stage 4. Build single.php (post title as the one h1, featured image, post_content, correct heading order), archive.php for /blog/, search.php and 404.php, per CLAUDE.md. Then the post migration on STAGING only: the 7 posts with no Elementor data need nothing; the 5 single-text-widget posts and the 10 split posts need checking that post_content is complete and ordered. Give me a per-post checklist and check the 10 split posts one by one. Do not touch _elementor_data.

**Verify:**
- [ ] Every post shows its title as an `<h1>`, headings descend in order
- [ ] All 22 posts spot-checked on staging — content complete, ordering right, no Elementor leftovers rendering
- [ ] /blog/, a category page, search, and a 404 all render and are under budget
- [ ] `_elementor_data` untouched (rollback intact)
- [ ] Tagged `stage-4-done`

**Rollback:** reactivating Elementor + Theratio on staging restores the old rendering, because the data is still there.

---

## Stage 5 — Section library and the homepage

**Goal:** the design's CSS split into per-section files, the section loader working, and `front-page.php` assembled from sections with the animations ported. The plan's largest single job.

**Before starting:** Stage 4 verified.

**Prompt for Claude (do this iteratively — one section per prompt is fine):**
> Stage 5. We split the design stylesheet one section at a time, comparing against the current render after each split — never all at once. Start with [hero]: create sections/hero.php, assets/css/sections/hero.css, and its JS if it animates, wired through inc/sections.php for conditional loading. Show me what to compare visually before we do the next section. Sections in order: hero, service-grid, stat-row, insights, cta, then the rest of the homepage.

**Verify (after ALL sections):**
- [ ] Homepage on staging visually matches the frozen prototype (side-by-side check)
- [ ] Each section's CSS loads ONLY on pages containing it (check network panel on a plain page)
- [ ] Animations fire on scroll, pause off-screen, and disable fully under `prefers-reduced-motion`
- [ ] Homepage measured: within every §6 budget
- [ ] No override files — each section styled in exactly one place
- [ ] Tagged `stage-5-done`

**Rollback:** each section is its own commit; revert the one that broke, keep the rest.

---

## Stage 6 — Templates and custom fields

**Goal:** `templates/service.php`, `templates/market.php`, `templates/guide.php`, plus the hand-built fields (CLAUDE.md §7). Proven by building HR fully, then Accounting with content only.

**Before starting:** Stage 5 verified.

**Prompt for Claude:**
> Stage 6. First inc/fields.php per CLAUDE.md §7: eyebrow and lede as simple fields; capabilities and quick facts as JSON repeaters with a vanilla-JS admin UI (add/remove/up/down). Nonces, capability checks, per-leaf sanitize on save and escape on render. Boxes appear only on the matching template. Then templates/service.php composing sections + fields. We fill in Human Resources completely on staging, verify, then do Accounting entering only content — if Accounting needs any code change, the template isn't done. Then market.php and guide.php.

**Verify:**
- [ ] HR page matches the service prototype; fields editable by a non-developer
- [ ] Accounting built with zero code changes
- [ ] Repeater: add/remove/reorder works, saves survive reload, weird input (quotes, emoji, HTML) is stored and displayed safely
- [ ] Fields invisible on ordinary pages
- [ ] All service/market pages under budget
- [ ] Tagged `stage-6-done`

**Rollback:** field data lives in postmeta and survives code reverts; templates revert via git.

---

## Stage 7 — Navigation and structure decisions

**Goal:** menus built, and the nine structural decisions from `synergi-build-plan.md` §6 implemented on staging.

**Before starting:** Stage 6 verified. **You must have answered the nine decisions** (draft homepage, garbage-slug page, two contact pages, three content hubs, ICXI page, ten drafts, four menus, mega-menu, alt-text plan).

**Prompt for Claude:**
> Stage 7. Implement navigation per my decisions: [list them]. Mega-menu decision: [yes/no]. Then apply the structural decisions on staging: [list]. Every URL that changes (if any were decided) gets a 301 and a written record. Nothing on production.

**Verify:**
- [ ] Nav correct on desktop and mobile, fully keyboard-operable
- [ ] Each of the nine decisions either implemented or explicitly deferred, in writing
- [ ] Menu assigned; unused menus removed
- [ ] Tagged `stage-7-done`

**Rollback:** menus and page edits on staging only; production untouched.

---

## Stage 8 — Migration, testing, launch

**Goal:** remaining pages migrated, full test pass, theme live on production.

**Before starting:** Stage 7 verified. A fresh verified backup of production exists TODAY.

**Steps:**
1. Migrate remaining pages on staging, heaviest first (Homepage content, Engagement Team, Our Approach = 41% of the work).
2. Full pass: accessibility checklist, budgets on every template, Yoast metadata intact, `WP_DEBUG` clean.
   Integrations pass: GTM/GA4 loading deferred through `inc/integrations.php`, Site Kit setup finished (Search Console + GA4 wired properly), forms submitting and reaching the CRM through Bit Integrations — test one real submission end to end.
3. Launch: deploy theme to production, switch theme, immediately verify the golden pages (home, one service, one post, blog, contact).
4. Deactivate — **do not delete** — the retired plugins. Watch for a week.
5. Compare Search Console coverage and Core Web Vitals against the pre-launch baseline over the following weeks.

**Prompt for Claude:**
> Stage 8. Pre-launch: run the full definition-of-done checklist from CLAUDE.md §10 against every template on staging and give me a written pass/fail report. Then a launch runbook: exact ordered steps for production, what to check within the first 10 minutes, and the exact rollback steps if anything is wrong.

**Verify (post-launch):**
- [ ] All golden pages render correctly on production
- [ ] Search Console: no coverage errors after recrawl
- [ ] Budgets hold on production measurements
- [ ] Old theme + Elementor still installed (deactivated) — rollback alive
- [ ] Tagged `launch`

**Rollback:** reactivate Theratio + Elementor. Content falls back automatically because `_elementor_data` still exists. This stays possible for at least a week.

---

## Stage 9 — Cleanup (one week after launch, minimum)

**Goal:** remove what the new theme made obsolete — only once launch has proven stable.

- Delete retired plugins (Element Pack, Slider Revolution, phone field, Elementor + Pro, Theratio, Kirki…) per the plan's disposition table.
- Delete `_elementor_data` and the 82.9 MB of Elementor revisions (single WP-CLI command).
- Confirm ~$240/yr of licenses are not set to renew.
- Re-measure everything; record the final before/after numbers.

**Prompt for Claude:**
> Stage 9. Launch has been stable since [date]. Give me the ordered cleanup: plugin deletions, the _elementor_data and revisions cleanup commands, and a final measurement report comparing against the 6.45 MB baseline.

---

## If a week slips

Cut in this order (from the plan): guide template merges into `page.php` → mega-menu becomes a plain dropdown → podcast page stays on Elementor until after launch → lightest pages migrate after go-live. **Never cut:** Stage 0, the verification gates, or the accessibility pass.

## The two things this file cannot do for you

1. **The two security items** (nulled activator, Novamira on production) are handled this week, outside these stages, per the build plan §11.
2. **Decisions only you can make:** frozen prototype direction (blocks Stage 3) and the nine structural decisions (block Stage 7). Deciding them early keeps every gate green.
