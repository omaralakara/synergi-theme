# Stage 4 — post migration checklist

Audited on staging, 25 August 2026, against `_elementor_data` and `post_content`.

**This file has two halves.** Everything down to the horizontal rule is the
worklist as it was found. Everything after "What was actually done" is the
record of the changes made, with the postmeta key holding each backup.

**25 posts, not the 22 the build plan expected** — 23 published plus 2 drafts.
The extra posts were published after the plan was written. Group A matches the
plan exactly (7 posts with no Elementor data) and so does Group C (10 posts
split across multiple text widgets), so the plan's analysis still holds.

`_elementor_data` is **not** touched by any of this. It is the rollback
(CLAUDE.md §2.9) and stays until at least a week after launch.

---

## What the columns mean

- **content** — bytes of `post_content`. This is what `single.php` renders.
- **el_text** — bytes of text found inside Elementor `text-editor` widgets.
  Roughly what the old theme rendered.
- **first h** — the first heading level in `post_content`. Anything above `h2`
  skips a level under the template's `h1` and breaks CLAUDE.md §8.

`el_text` is never an exact match for `content` — Elementor stores wrapper
markup inside the widget — so a small difference is normal. A large one, or
`el_text` exceeding `content`, is the signal worth chasing.

---

## Group A — no Elementor data (7 posts)

Nothing to migrate. `post_content` was always the source of truth for these.
Verify they render and move on.

| ID | Post | content | first h | Action |
|---|---|---|---|---|
| 8026 | Scaling Smart: Where AI Meets Human-Centered Growth | 3,420 | – | render check |
| 8319 | How Smart Procurement Transforms Business Performance | 1,265 | – | render check |
| 8322 | Beyond Payroll: How Modern HR Services Drive Growth | 1,223 | – | render check |
| 8785 | Why Marketing Belongs in Shared Services | 7,438 | **h4** | **fix headings** |
| 8870 | Procurement Operations Excellence | 2,838 | h2 | render check |
| 8918 | Why Shared Services in UAE & GCC are Transforming | 7,033 | **h3** | **fix headings** |
| 9015 | Why Businesses are Turning to BPO Companies in Riyadh | 6,338 | h2 | render check |

Post 8870 is the one already verified end to end on staging: one `h1`, heading
order `1,2,2,2,2,2,2,2,2,2`, no skips.

---

## Group B — a single text widget (8 posts, 2 of them drafts)

Low risk: one widget means one block of content, so ordering cannot be wrong.
Check completeness only.

| ID | Post | content | el_text | first h | Note |
|---|---|---|---|---|---|
| 99 *(draft)* | Announcing Innovawave LLC | 667 | 667 | – | exact match |
| 102 *(draft)* | EIH acquires majority stake in BHM Capital | 3,749 | 3,801 | **h4** | **fix headings** |
| 8617 | Building Effective Shared Services | 4,156 | 4,061 | **h4** | **fix headings** |
| 9927 | The power of automating your HR operations | 10,557 | 10,877 | h2 | **el_text exceeds content — check** |
| 9975 | Fractional C-Level Executives | 9,646 | 10,377 | h2 | **el_text exceeds content by 731 — check** |
| 10124 | The future of BPO in Syria | 29,346 | 0 | h2 | no text widget; content is complete |
| 10362 | The Hidden Cost of Poor Inventory Data | 11,311 | 0 | h2 | no text widget; content is complete |
| 10398 | Reducing the Hidden Cost of Workflow Chaos | 9,972 | 0 | h2 | no text widget; content is complete |

**9927 and 9975 are the two to open first.** In both, Elementor holds more text
than `post_content` does, which is the shape a truncated migration takes. It may
be nothing more than widget wrapper markup — but it is the only place in the
whole archive where content could actually be missing.

---

## Group C — split across several text widgets (10 posts)

The plan's "10 split posts", confirmed. These are the ones where ordering can be
wrong, because the old theme rendered N widgets in Elementor's order and
`post_content` has to reproduce that order in one stream. Check each one by one:
content complete, sections in the same sequence, no duplicated or dropped block.

| ID | Post | widgets | content | el_text | first h | Action |
|---|---|---|---|---|---|---|
| 8946 | Nejmeh Club Appoints Synergi | 4 | 13,315 | 1,356 | – | order check |
| 9290 | Free 7-Step Checklist for Procurement BPO Readiness | 9 | 9,803 | 8,880 | **h4** | order + **headings** |
| 9525 | 2025 in Review, 2026 in Focus | 7 | 9,875 | 7,752 | **h4** | order + **headings** |
| 9631 | Why Business Process Engineering Matters | 6 | 6,495 | 5,174 | **h4** | order + **headings** |
| 9650 | The Blueprint of a Successful Digital Presence | 7 | 6,378 | 5,885 | **h4** | order + **headings** |
| 9849 | Women Leading the Future of BPO | 5 | 3,568 | 3,372 | h2 | order check |
| 9870 | Supply Chain Disruption in the UAE | 9 | 9,138 | 8,763 | h2 | order check |
| 9899 | Why Odoo is the right technology | 4 | 4,076 | 3,303 | h2 | order check |
| 9914 | The New PIF Strategy 2026-2030 | 5 | 2,871 | 2,662 | h2 | order check |
| 10511 | Is Your B2B Website Ready for AI Search? | 25 | 12,500 | 7,539 | h2 | order check |

In every one of these `content` is larger than `el_text`, which is the
reassuring direction: `post_content` holds at least as much as the widgets did.

---

## Heading fixes (7 published + 1 draft)

`single.php` emits the post title as the one `h1`. It deliberately does **not**
rewrite the headings underneath — a template that silently promoted an author's
`h4` would be lying about what the database holds, and the next editor would
have no idea why their heading changed. These are fixed in the editor.

Demote to a proper sequence starting at `h2`:

- 8785 — opens at h4 (8 × h4)
- 8918 — opens at h3, then h3/h4 mixed
- 8617 — opens at h4 (4 × h4)
- 9290 — opens at h4 (8 × h4)
- 9525 — opens at h4 (6 × h4)
- 9631 — opens at h4 (5 × h4)
- 9650 — opens at h4 (6 × h4)
- 102 *(draft)* — opens at h4

The rest already open at `h2` and descend correctly.

---

## The H3→H1 snippet must be retired

ASE code snippet **#10379, "SEO: single post title H3 to H1"**, is published and
running. It hooks `template_redirect`, opens an output buffer over every single
post, and rewrites `<h3 class="entry-title">` into `<h1>`.

It is already inert: it returns the HTML untouched when a real `h1` exists, and
`single.php` now emits one. But it still buffers every single-post response for
no reason, and it is a trap for whoever next wonders why post markup differs
from the template.

Retire it once the posts above are verified — not before, so there is a way back
if anything unexpected turns up.

---

## Blocked: `/blog/` is not a post archive

`page_for_posts` is **not set**. `/blog/` is page #136 "Our Blog", an ordinary
Elementor page rendered by `page.php`, so `archive.php` is never reached there.

`archive.php` itself is verified — the category archive renders it correctly,
one `h1`, cards at `h2`, no heading skips. What is unresolved is whether `/blog/`
should become the real posts page. Setting `page_for_posts = 136` keeps the URL
identical (§2.8 holds) but replaces the Elementor content with the post listing.

That is one of the nine structural decisions the plan assigns to Stage 7, so it
is recorded here rather than decided.

---

# What was actually done — 25 August 2026

Everything below was executed on **staging only**. `_elementor_data` was not
touched on any post; every content change kept a backup in postmeta.

## 1. `/blog/` is now a real post archive

`page_for_posts` set to page #136 "Our Blog".

- The URL is unchanged: `https://staging.synergi.ae/blog/` (§2.8 holds).
- It now renders `archive.php`: one `h1` ("Our Blog"), 10 post cards at `h2`,
  pagination, no heading skips, no Elementor markup.
- Page #136's old Elementor content is preserved twice over — in
  `_elementor_data`, and copied to postmeta `_syn_content_before_posts_page`.
- **To reverse:** Settings → Reading → set "Posts page" back to "— Select —".
  The old page content returns immediately.

## 2. Heading hierarchy fixed on 8 posts

Each post's headings were shifted as a block so the shallowest becomes `h2`,
which preserves the relative structure instead of flattening it. Original
content saved to postmeta `_syn_heading_backup_2026_08_25` on every post touched.

| ID | Shift | Before | After |
|---|---|---|---|
| 102 *(draft)* | −2 | 4 | 2 |
| 8617 | −2 | 4,4,4,4 | 2,2,2,2 |
| 8785 | −2 | 4×8 | 2×8 |
| 8918 | −1 | 3,3,3,3,4,4,4,4,3,3 | 2,2,2,2,3,3,3,3,2,2 |
| 9290 | −2 | 4×8 | 2×8 |
| 9525 | −2 | 4×6 | 2×6 |
| 9631 | −2 | 4×5 | 2×5 |
| 9650 | −2 | 4×6 | 2×6 |

Byte counts are identical before and after — only tag names changed. The script
aborted on any post whose non-heading content would have shifted, or whose
heading count changed. Note 8918: it had two real levels and kept both.

## 3. The two suspected-truncation posts

- **9927 — clean.** Zero sentences present in Elementor but missing from
  `post_content` (3,799 vs 3,800 characters).
- **9975 — clean, but it exposed a different defect.** The two sentences that
  looked missing were present all along; the comparison failed because the
  content contained a literal `&lt;/a &gt;` — a broken closing tag that rendered
  as visible `</a >` text on the page.

## 4. Broken markup artifacts removed

| ID | Removed | Bytes |
|---|---|---|
| 9975 | 4 × `&lt;/a &gt;` | 9,646 → 9,602 |
| 10124 | 1 × `&lt;/a &gt;` | 29,346 → 29,335 |

Backups in postmeta `_syn_artifact_backup_2026_08_25`.

Post **8946** carries `data-elementor-lightbox-*` attributes on a working image
link. With Elementor deactivated nothing reads them, so the link behaves as an
ordinary link — cosmetic cruft for Stage 9, not a defect.

## 5. The missing `syn-card` image size

`add_image_size( 'syn-card', 720, 405 )` was registered in Stage 1, but every
image had been uploaded before that, so the derivative never existed. Card
`src` attributes were therefore falling back to the full-size original — up to
1,672px wide for a 22rem box.

24 derivatives generated with `wp_get_image_editor()` and registered in each
attachment's metadata. One image is smaller than the crop and falls back
gracefully. Nothing was deleted or overwritten.

`/blog/` card images: **~775 KB → 346 KB** of `src`, and every card is
lazy-loaded with a working `srcset`, so real first-load is a fraction of that.

## 6. Verification

All **23 published posts** fetched and checked: HTTP 200, exactly one `h1`, no
heading skips, no Elementor markup, no stray anchors. Zero exceptions.

| Route | Status | CSS | Requests | Blocking JS | Excl. third party |
|---|---|---|---|---|---|
| `/blog/` | 200 | 19.6 KB | 25 | 2 | 585 KB |
| single post | 200 | 19.6 KB | 16 | 2 | 284 KB |
| category | 200 | 19.6 KB | 15 | 2 | 222 KB |
| search | 200 | 19.6 KB | 24 | 2 | 649 KB |
| 404 | **404** | 19.6 KB | 15 | 2 | 222 KB |

The two blocking scripts are jQuery and a plugin bundle; no theme code
contributes to either. Total page weight and JavaScript still exceed §6 for the
unchanged third-party reason recorded in the `stage-3-done` tag.

## Still open

- **ASE snippet #10379** ("SEO: single post title H3 to H1") is still published
  and still buffering every single-post response. It is inert now — every post
  has a real `h1` — and it is safe to retire.
- **Duplicate GA4**: `G-F8BHKGB935` in ASE snippet #8607 alongside Site Kit's
  `GT-TXBFKV55`. Left untouched by request.
