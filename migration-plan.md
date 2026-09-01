# The migration — how staging becomes production

Written 1 September 2026, because Stage 8 in `synergi-build-stages.md` says
"deploy theme to production, switch theme" and that is only half of what has to
happen. This file is the missing half.

---

## The problem, in one paragraph

**The theme is in Git. The content is not.** The templates, sections, fields
engine and record engine all live in `synergi/` and deploy as a zip. But the 22
new pages, the twelve case studies, the nine site records in the `syn_records`
option, every `_syn_*` field value on every page, the rebuilt menu and all the
Yoast metadata live only in the **staging database**. Upload the theme to
production on its own and you get a site of templates with nothing to render.

Production is also not frozen. It has been live and serving traffic since the
staging clone was taken in Stage 0, so it is not a stale copy that can simply be
overwritten without looking.

---

## Three ways to do it

| | What happens | Verdict |
|---|---|---|
| **A. Full database migration** | All-in-One WP Migration pushes staging over production. Everything arrives at once, exactly as approved. | **Only if production has not changed.** It overwrites every post, option, user and form entry created on production since the clone. |
| **B. Theme only** | Deploy the zip, rebuild content by hand on production. | **No.** 22 pages, nine records and several hundred field values retyped, with no way to prove the result matches what was signed off. |
| **C. Theme from Git + a scripted content transfer** | Deploy the zip. Then a script exports exactly the content objects from staging and writes them to production. | **This one.** Surgical, repeatable, and — the point — *rehearsable*. |

### Why C

A migration you can run twice is a migration you can test. The script can be run
against a throwaway clone of production first, the result diffed against
staging, and the diff read before anything touches the live site. Options A and B
are both single-shot: you find out whether they worked by looking at the live
site afterwards.

C also survives the thing that will actually happen — that somebody publishes a
blog post on production during the week before launch. A full database push
destroys it silently. A scripted transfer never touches it.

---

## What the script has to carry

Everything below lives in the staging database and nowhere else:

- **The `syn_records` option** — nine records: `services`, `solutions`,
  `markets`, `figures`, `locations`, `why`, `why_cards`, `final_cta`, `social`.
- **All `_syn_*` postmeta** on every page and case study.
- **The 22 pages built in August**, with their slugs, parents, templates and
  menu order.
- **The twelve case studies**, now `syn_case_study` posts, with their
  `syn_case_service` terms.
- **The Main Menu**, its 29 items and the `primary` location assignment.
- **Yoast metadata** — titles, descriptions, focus keywords, and the noindex
  flags that are deliberate.
- **The redirect table** (`wpseo-premium-redirects-base`), which now carries
  `/our-approach/ → /about-us/` and `/our-leadership/ → /engagement-team/`.
- **The media library additions** from August, and the alt text written 1 Sep.

## What must NOT travel

These are staging settings and every one of them would do damage on production:

| Setting | Why it must not travel |
|---|---|
| `blog_public = 0` | De-indexes the entire site. |
| Homepage per-page `noindex` | Deliberate on staging; catastrophic on production. |
| `/media/` per-page `noindex` | Inherited from 2024, should be cleared, not copied. |
| Site Kit's Search Console property | Points at `staging.synergi.ae`. |
| The two ASE tag snippets | `G-F8BHKGB935` and the LinkedIn pixel, still firing on staging. |
| `WP_ENVIRONMENT_TYPE = production` | Wrong on staging; irrelevant but confusing on production. |
| Any `staging.synergi.ae` string | The records were cleaned on 1 Sep; re-check before every run. |

This list is the single strongest argument against option A.

---

## The runbook

### Before the window

1. **Fix the production Novamira connection.** It currently returns 404. Every
   step below depends on it, and it is the one item with no workaround.
2. **Answer the collision** (`open-questions.md` §6) using Search Console data.
   The `/markets/` pages must not go live on production until this is decided —
   publishing them alongside the two legacy landing pages is what creates the
   cannibalisation the question exists to prevent.
3. **Sign off content and images**, page by page, on staging. A written list,
   not a walkthrough. This is what staging is for and it is the step most likely
   to be skipped.
4. **Write the transfer script and rehearse it** against a fresh clone of
   production. Diff the result against staging. Read the diff.
5. **Take a fresh production backup and prove it restores.** An untested backup
   is not a backup (Stage 0's rule, and it still holds).

### The window

6. Deploy the theme from Git (`powershell tools/build-zip.ps1`, upload, do not
   activate yet).
7. Run the content transfer.
8. **Switch the theme.**
9. Clear the homepage `noindex` and give it a real Yoast title and description.
   Clear `/media/`'s noindex.
10. Confirm `blog_public = 1` and the redirect table is in place.
11. Flush rewrite rules — the case-study post type and its taxonomy need it, and
    `inc/case-study-post-type.php` does it once when
    `SYN_CASE_STUDY_REWRITE_VERSION` moves.
12. **Verify the golden pages:** home · one service · one solution · one market ·
    one case study · a term archive (`/case-studies/service/human-resources/`) ·
    one blog post · the blog · contact.

### After

13. **Deactivate — do not delete — the retired plugins.** Elementor,
    `_elementor_data` and the old theme stay for at least a week (CLAUDE.md
    §2.9). They are the rollback.
14. Watch for a week. Compare Search Console coverage and Core Web Vitals
    against the pre-launch baseline.

### Rollback

Switch the theme back to Theratio. That is why it is still installed, and why
nothing above deletes anything.

---

## Known blockers, as of 1 September

- **Production Novamira is down (404).** Blocks everything.
- **The collision is undecided.** Blocks publishing the two `/markets/` pages.
- **The launch path is not formally chosen.** This file recommends C; it is not
  yet a decision.
- **Two ASE tag snippets are still firing on staging** and cannot be switched
  off through the API — see the note in the project memory. Two clicks in
  wp-admin → Code Snippets.
- **22 pages have no SEO title or description.** Not a blocker for the
  mechanics, but they should not go live generated.
