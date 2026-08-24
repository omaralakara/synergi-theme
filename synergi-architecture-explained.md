# The Synergi rebuild, explained simply

Companion to `synergi-build-plan.md` (v2, 20 Aug 2026). This document explains the chosen architecture in plain language, answers the practical questions — who edits what, what happens to the blog, Arabic/English, and ACF vs Meta Box — and records the reasoning so it doesn't have to be re-argued later.

---

## 1. The approach in one paragraph

We are building a **hybrid WordPress theme**. That means: the *design and layout* of the site live in code files — PHP templates, CSS, a little vanilla JavaScript — stored in Git, exactly like a normal software project. The *words and images* live in the WordPress database, edited through the normal WordPress admin. No Elementor, no page builder of any kind, and no full "Site Editor" block theme either.

One rule holds the whole thing together:

> **Anything that describes how the site looks lives in a file. Anything a person wrote lives in the database.**

Elementor broke that rule — it stored layout *and* content together as serialized data in the database, which is why the site today is 6.45 MB, why a color change means editing 66 widgets, and why your own developer ended up writing a plugin to fight the builder. The hybrid theme restores the rule.

---

## 2. Why this works best for Synergi

You stated four requirements: full control, easy maintenance, custom sections and animations, and no builder constraints.

**Full control.** Every layout decision is a file with a reviewable diff in Git. If something breaks, `git revert`. Nothing about the design can silently change from inside the admin.

**Easy maintenance.** Every color, font size, and spacing step is defined once, in `theme.json`. Change the navy in one line and it changes everywhere — front end and editor both.

**Custom sections and animations.** A new section is three plain files: a PHP partial for the markup, a CSS file, and (if it moves) a small vanilla JS file. No npm, no React, no build pipeline. This is *exactly* the model your developer already invented in the `synergi-homepage-assets` plugin — we're just removing the builder they had to fight to make it work.

**No builder constraints.** There is no builder. WordPress renders your HTML.

**The proof it works: WTC.** The WTC Saudi site is already built this way — a custom classic theme with PHP page templates, ACF fields for editable content, and no page builder. It runs English and Arabic, it's maintained by the same team, and it works. Synergi's rebuild is not an experiment; it's applying a pattern the team has already shipped.

**And it's the safe bet.** A hybrid theme can adopt block templates later, page by page, if WordPress's direction ever makes that worthwhile. Going the other way — from a full block theme back to files — means untangling what the Site Editor wrote into the database. Reversible in one direction only, and we're starting on the reversible side.

---

## 3. Who edits what — the honest division of labor

Yes: **design changes and new page layouts are done by touching code.** That is deliberate, not a limitation. Here is the full picture:

| You want to… | How | Code? |
|---|---|---|
| Change a color, font size, spacing | Edit one line in `theme.json` | Yes — one line |
| Change the layout of any page | Edit the PHP template / section partial | Yes |
| Build a brand-new section or animation | New partial + CSS (+ JS) — about half a day | Yes |
| Edit the *words* on the homepage or a service page | Fill in fields in wp-admin (eyebrow, lede, capabilities…) | No |
| Write or edit a blog post | Normal WordPress editor | No |
| Add a simple page (About-style) | Normal WordPress editor; the theme styles it | No |
| Add a sixth service line | Add page, pick "Service line" template, fill fields | No |

So marketing never touches code, and code never depends on marketing. The trade-off, stated plainly: inventing a *new kind of page* is a developer task. That's acceptable because Synergi always has a developer in-house — and it's precisely what prevents the site from accumulating 520 one-off widgets again.

---

## 4. What happens to the blog posts

Almost nothing — and that's the good news. All 22 posts already keep their article text in `post_content`; Elementor was only wrapped around the outside.

- 7 posts have no Elementor data at all — they migrate untouched.
- 5 posts are one text widget holding the whole article — removing the Elementor wrapper leaves the article intact.
- 10 posts are split across a few heading/text widgets — about 15 minutes of checking each.

After migration, writing a post is: Posts → Add New → write in the normal editor → publish. The new `single.php` template handles the layout, and every post finally gets a proper `<h1>` — fixing a heading problem on all 22 posts at once that was impossible to fix while Elementor held the layout.

Safety net: we don't delete the old Elementor data until at least a week after launch. It's the rollback.

---

## 5. Arabic and English — yes, and WTC is the template

The hybrid approach handles bilingual well, and again you already have the working example: **WTC runs English and Arabic with Polylang on exactly this architecture.**

How it works: Polylang gives every page and post a language, paired with its translation. `/our-services/human-resources/` gets an Arabic twin (e.g. under `/ar/`). The PHP templates stay the same; the *content* — field values, post text, menus — is entered per language. Visitors switch with a language toggle; Google gets proper `hreflang` tags so each language ranks in the right market (which matters for the UAE/KSA split).

Three things Arabic genuinely requires, so we plan them rather than discover them:

1. **Right-to-left layout.** We write the CSS with logical properties (`margin-inline-start` instead of `margin-left`) from day one. Then RTL costs almost nothing. Retrofitting it later costs a full CSS pass.
2. **An Arabic font.** Montserrat has no Arabic glyphs. The Arabic side needs a paired Arabic typeface (self-hosted, same as Montserrat) — a design decision to make once, when Arabic content is commissioned.
3. **Translated content is real work.** The mechanism is cheap; writing good Arabic copy for ~26 pages is not. Recommendation: build the theme RTL-ready **now**, launch in English on schedule, and add Arabic as its own phase after the domain move — not squeezed into the 3-week window.

---

## 6. Custom fields: hand-built, zero subscriptions (final decision)

Three options existed for the editable fields on designed pages: Meta Box (installed, but the free core — repeatable groups need paid extensions), ACF Pro (what WTC uses — 9 field groups, 156 fields, proven, but a paid license), or building the fields ourselves with core WordPress APIs.

**The decision: build them ourselves.** The theme runs with zero paid dependencies, forever. What ACF was really selling us is the admin UI for repeatable rows — and that is about one day of work with core `add_meta_box` plus a small vanilla-JS repeater (add / remove / reorder), storing clean JSON in postmeta. Simple fields (eyebrow, lede) are plain inputs.

Why this fits: it matches the project's "fully controlled" principle better than any plugin; there is no renewal, no vendor, and no update that can break the admin; and it is not a trap — the stored data migrates into ACF easily if that is ever wanted. The honest cost: it is admin code we maintain ourselves, kept deliberately small and boring. The full spec is in `CLAUDE.md` §7.

WTC remains the useful precedent for the *architecture* (custom PHP theme + fields + two languages); Synergi just implements the fields layer without the license. Meta Box gets uninstalled with the rest of the retired plugins.

---

## 7. What this looks like when it's done

A visitor gets a page under 1 MB instead of 6.45 MB. Marketing writes posts and edits copy without being able to break the design. The developer changes the design by editing files, reviewing diffs, and deploying from Git — the same workflow as WTC. A color change is one line. A new section is half a day. Arabic is a content project, not a rebuild. And roughly $240/year of builder licenses stops renewing.

The site stops being something you fight, and becomes something you own.
