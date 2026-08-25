# Stage 4 — post migration checklist

Audited on staging, 25 August 2026, against `_elementor_data` and `post_content`
as they stand. Nothing in this file has been changed on staging; it is the
worklist, not a record of work done.

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
