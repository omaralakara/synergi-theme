# Synergi website: the build plan

Version 2. 20 August 2026. Supersedes the earlier draft.

> **Amendments — 23 August 2026.** Decisions taken since v2, which override the matching lines below:
>
> 1. **Architecture confirmed: hybrid theme.** Decision 1 in section 13 is closed.
> 2. **Custom fields are hand-built, not Meta Box.** The project runs with zero paid dependencies. Repeatable groups are a small vanilla-JS repeater over core `add_meta_box`, storing JSON in postmeta. Meta Box is uninstalled with the retired plugins. Wherever this document says "Meta Box fields", read "hand-built fields" — spec in `CLAUDE.md` §7. (The Phase 4 Meta Box display-condition risk disappears with it.)
> 3. **Arabic/English is a committed future phase**, after the domain move. The theme ships RTL-ready now: CSS logical properties only, translatable strings. Zero cost today.
> 4. **Integrations are first-class:** GTM/GA4 deferred after first paint, Search Console via Site Kit (finish its setup at launch), CRM via the consolidated form plugin + Bit Integrations. New features/plugins stay possible through the four-question gate. Rules in `CLAUDE.md` §11.
> 5. **jQuery is not deregistered while WPForms depends on it** — audit first, drop when the dependency list is empty (verified 20 Aug; corrects the earlier cleanup spec).
> 6. **Elementor Pro licence during transition:** leaning to $0 — removing the nulled activator keeps pages rendering; the cost is a ~3-week freeze on editing Pro widgets, pending marketing's OK. Test unlicensed rendering on staging first.
> 7. **Governance docs now exist:** `CLAUDE.md` (build rules) and `synergi-build-stages.md` (Stages 0–9 with prompts and verification gates). The build follows the stages file, which maps onto the phases below.

This document starts from zero. It states the problem, the one architectural decision that everything else hangs on, what you give up in each direction, and how the thing actually gets built.

---

## 1. Where we are

Today every page and every post on synergi.ae is built in Elementor. Adding a page means opening the builder and assembling it from widgets. Adding a post means the same thing.

Three consequences, and the third is the one that matters most.

**The site is slow.** The homepage ships 6.45 MB across 111 requests. The server is not the problem: time to first byte is 270 ms and the cache is working. The problem is 4.8 MB of CSS and JavaScript sent by Elementor, Element Pack, Slider Revolution and Instagram Feed on every page, including pages where those plugins render nothing at all.

**Editing takes too long.** The homepage alone holds 88 widgets. Changing a colour across the site means finding it in every widget that uses it. There is no diff, no review, no undo beyond WordPress revisions.

**Custom work has to fight the builder.** This is the important one, and the evidence is already in your own codebase. The `synergi-homepage-assets` plugin exists because the new design could not be built as Elementor widgets. Its own header says so: it renders the design's HTML directly "instead of rebuilding it as native Elementor widgets." Inside it are workarounds for Elementor rather than features of the design. It declares a dependency on Elementor's stylesheet purely so the design wins the cascade. It disables its own reveal animations inside the Elementor editor, because Elementor rebuilds a widget's DOM on every edit and the animation never fires. It manually enqueues the Instagram scripts because Elementor's shortcode scan misses them.

That plugin is the argument for this whole project, written by the person who hit the wall.

---

## 2. What we are replacing it with

The instinct is to swap Elementor for a different page builder. Gutenberg is the obvious candidate, and the earlier specification proposed a full block theme built around it.

**We now think that is the wrong call for Synergi, and we want to correct it before any more work is done.**

A full block theme moves you from one page builder to another. It is free, it is maintained by WordPress itself, and it is where WordPress is heading. But it brings three specific problems that land squarely on what you have told us you want.

**Layout goes back into the database.** The moment anyone edits a template in the Site Editor, WordPress writes that template into the database as a `wp_template` record, and the database copy overrides the file in your theme. Your Git repository no longer reflects what the site renders. This is the same disease as Elementor, in a newer form. Your site currently has zero of these records, so nothing is lost by avoiding them.

**Custom animated sections become expensive.** Your homepage uses `IntersectionObserver` reveals and canvas rendering. In a block theme, each of those has to become a registered block, which needs a JavaScript editing component, which needs npm and a build pipeline that somebody has to maintain. Meta Box could avoid that, but you have Meta Box core only, without the Blocks extension. And the animation problem does not go away: the Gutenberg editor rebuilds a block's DOM on edit exactly as Elementor does, so the same workaround you already wrote would have to be written again.

**It is the slowest path to week three.** Block markup has a strict serialisation format. When it does not match what the editor expects you get validation errors, and hunting those is precisely the kind of week-two debugging you said you want to avoid.

### What we recommend instead

A **hybrid theme**. WordPress renders your own PHP templates. Layout lives in files in Git. Content lives in the database where content belongs. The block editor is used for what it is genuinely good at, which is writing articles.

Concretely, three kinds of page, handled three different ways:

| Kind | Examples | How it is built | Who can change it |
|---|---|---|---|
| Designed | Homepage, the 5 service lines, the 2 market pages | PHP template plus a section partial, with editable copy in Meta Box fields | Developer builds it; anyone edits the copy |
| Simple | About, Contact, Privacy, Terms | `page.php` renders block content | Anyone, in the normal editor |
| Posts | All 22 blog posts | `single.php` renders block content | Marketing writes, developer publishes |

A new custom section is a PHP partial for the markup, with its own stylesheet and, where it needs behaviour, a small vanilla script. No build step, no React, no registration ceremony. It is exactly the model your homepage plugin already uses, except that it stops fighting a builder because there is no longer a builder in the way.

---

## 3. The trade-offs, stated plainly

Four options were considered. This is what each costs.

### Stay on Elementor and prune plugins

Removing Element Pack, Slider Revolution, the phone field and Instagram Feed's site-wide loading takes roughly 2.3 MB off every page, with no redesign at all.

It is worth doing regardless. But it leaves layout in the database, leaves editing slow, and leaves custom sections fighting the builder. It fixes one of your three problems.

### Full block theme

**You get:** editors can build page layouts without a developer. Header and footer editable in the Site Editor. Aligned with where WordPress is going, so less rework in five years.

**You give up:** files as the source of truth, the moment anyone touches the Site Editor. A build pipeline for any custom block. Time spent on block validation errors. And the animation problem returns unchanged.

**Right for you if:** marketing needs to create new page layouts unaided, frequently.

### Hybrid theme (recommended)

**You get:** total control. Every layout decision is a file in Git with a reviewable diff. Custom sections are plain HTML and CSS with a little vanilla JavaScript, which is what your design already is. No build tooling. Nothing to learn beyond PHP and CSS. The fastest path to a working site, because the homepage template is close to a copy-paste of the HTML that already exists.

**You give up:** creating a *new page layout* is a developer task. Marketing can write posts and edit copy on existing pages, but they cannot invent a new page shape. You also accept that you are slightly against the direction WordPress is travelling, which may mean revisiting this in several years.

**Right for you if:** there is always a developer at the company. You told us there is.

### Headless (Astro or Next.js)

**You get:** the best possible performance ceiling and a modern developer experience.

**You give up:** one system becomes two, with a second hosting bill and a deployment pipeline. For 48 pages this is cost without a matching return, and it puts the blog behind a build step.

**Right for you if:** the site were heading past several hundred pages, or the team's strength were JavaScript rather than PHP.

### Why the recommendation is not close

Your stated requirements are full control, easy maintenance, custom sections and animations, and no page-builder constraints. The hybrid theme satisfies all four directly. The block theme satisfies two of them and fights you on the other two. That is the whole argument.

One thing worth being clear about: this decision is reversible in one direction. A hybrid theme can adopt block templates later, page by page, because the two coexist in the same theme. Going the other way, from a full block theme back to PHP templates, means unpicking whatever the Site Editor has written into the database. So the recommendation is also the safer bet.

---

## 4. How the site works after launch

### Publishing a blog post

Marketing opens Posts, Add New, writes or pastes the article, adds a featured image and a category, saves as draft. A developer sets the Yoast fields and publishes.

Same two people as today, minus the layout step, because `single.php` already knows what a post looks like. Every post also gets a proper `<h1>`, which fixes a heading-order problem that currently affects all 22 posts and could not be corrected through the API while the layout sat inside Elementor.

### Editing copy on a designed page

The service pages, the market pages and the homepage expose their editable text as Meta Box fields. Eyebrow, lede, capability titles and descriptions, quick facts. Anyone with access can change the words. Nobody can change the layout by accident, which is the point.

### Adding a simple page

Pages, Add New, write it in the block editor, publish. It renders through `page.php` with the site's typography and spacing already applied.

### Adding a new service line

Pages, Add New, set the parent to Our Services, choose the Service line template, fill in the fields. It comes out matching the other five because the same template renders all six.

### Adding a brand new custom section

A developer creates three files: `sections/name.php` for the markup, `assets/css/sections/name.css` for the styling, and `assets/js/sections/name.js` if it needs behaviour. The section registers itself for conditional loading, so its CSS only downloads on pages that use it.

Then it is available to any template. Roughly half a day for a section with animation, less without.

This is the mechanism that stops the site accumulating 520 one-off widgets again. A section is built once and reused, rather than assembled by hand every time.

---

## 5. Where everything lives

The rule is simple: **anything that describes how the site looks lives in a file. Anything a person wrote lives in the database.** Elementor broke that rule and that is why the site is hard to change.

```
wp-content/themes/synergi/
├── theme.json            colours, type scale, spacing. The only place they are defined
├── style.css             theme header
├── functions.php         thin; requires from inc/
├── inc/
│   ├── setup.php         theme supports, image sizes, menus
│   ├── assets.php        conditional CSS and JS loading
│   ├── cleanup.php       removes core cruft
│   ├── fields.php        Meta Box definitions
│   └── sections.php      the section loader
├── templates/            page templates chosen in the editor sidebar
│   ├── service.php
│   ├── market.php
│   └── guide.php
├── sections/             reusable design sections
│   ├── hero.php
│   ├── service-grid.php
│   ├── stat-row.php
│   ├── insights.php
│   └── cta.php
├── parts/                header.php, footer.php, nav.php
├── front-page.php        homepage
├── single.php            blog post
├── archive.php           blog listing
├── page.php              default page
├── search.php · 404.php
└── assets/
    ├── css/              one file per section, plus base
    ├── js/               vanilla only
    └── fonts/            Montserrat, self-hosted, latin subset
```

`theme.json` works in a hybrid theme exactly as it does in a block theme. It defines the palette and type scale once, feeds them to the block editor so post content matches the site, and emits CSS variables the section stylesheets use. That part of the earlier specification was right and we are keeping it.

### The design tokens, corrected

The earlier specification had the wrong values. These are read from your own `main.min.css` and cross-checked against the Elementor global kit.

**Font: Montserrat only.** Variable weight, self-hosted, latin subset, one file. The specification proposed Josefin Sans and Inter. Josefin Sans belongs to the Theratio theme you are removing, and Inter appears nowhere in the design.

**Palette:** navy `#1d4e89`, navy deep `#0b2341`, ink `#071a31`, cyan `#28abe5`, cyan soft `#dff4fc`, mint `#8fd7f3`, text `#232324`, muted text `#5c6673`, white, paper `#f3f5f8`, paper blue `#edf5fa`. Utility values for borders, focus rings and success states sit outside the editor palette.

The specification's cyan was `#32aae1`, which is a value nothing uses, and it included a bronze that exists nowhere in the design.

**One thing to fix while we are here.** `main.min.css` declares `:root` eight times, and later blocks silently override earlier ones. The corner radius, for example, is declared as `0.5rem` and then redeclared as `4px`. We take the values that actually win, and we consolidate to one layer, because a second override layer is how this design starts becoming the thing we are replacing.

---

## 6. Site structure

48 published URLs today: 26 pages and 22 posts, plus 10 drafts. Every URL stays exactly where it is, so no redirects are needed and nothing is put at risk before the domain move.

| Group | Pages | Template |
|---|---|---|
| Home | 1 | `front-page.php` |
| Services | `/our-services/` and 5 lines | `page.php`, `templates/service.php` |
| Markets | Shared Services UAE, BPO in Saudi Arabia, Global Locations | `templates/market.php` |
| About | About Us, Our Approach, Our Leadership, Engagement Team | `page.php` |
| Insights | Blog, Executive Podcast, Media, 2 guides | `archive.php`, `single.php`, `templates/guide.php` |
| Contact | Contact Us, Connect | `page.php` |
| Legal | Privacy Policy, Terms | `page.php` |

### Decisions we need from you

Nine, with our recommendation against each.

1. **`/synergi-homepage-2026-draft-build/` is published and indexable.** A draft build is publicly visible to Google. Unpublish it today, separately from everything else here.
2. **`/full-episodek6vl3n8qz2kjf9ya7pt4br1mx0wn5ce2sj8hu9og6ry3qv5dp4ts7zc1ab8ll9nf/`** is live with a machine-generated slug. Delete it, or give it a real URL with a redirect.
3. **Two contact pages.** `/contact-us/` and `/connect/`. We suggest consolidating unless they serve different campaigns.
4. **Three content hubs.** Blog, Media and Executive Podcast overlap. We suggest one Insights destination with filters.
5. **The ICXI partnership page** is news built as a page. It belongs in the blog.
6. **Ten drafts.** Publish, delete or leave. We suggest deciding now so they do not get migrated by accident.
7. **Four menus** exist, one is assigned. Confirm which survive.
8. **The Element Pack mega-menu.** Confirm whether the new design keeps one, because it changes the effort materially.
9. **806 of 906 images have no alt text.** Fix them for migrated pages as we go, and schedule the rest.

---

## 7. How it gets built

Three weeks. Each phase names what could break and how we avoid it, because avoiding week-two archaeology is the point of planning this at all.

### Phase 0: before anything else. Half a day.

There is no staging site and no version control. Every change so far has been made against production with no way back.

- Clone production to staging. Hostinger supports this natively.
- Put the theme in Git. Content is migrated, not committed.
- **Install a stock WordPress theme.** Theratio is currently the only theme on the server, so a failed activation has nothing to fall back to. This takes two minutes and it is the single cheapest piece of insurance in this plan.
- Prove the backup restores. An untested backup is not a backup.

*What breaks without this:* everything, irrecoverably. This is why it is Phase 0 and not Phase 1.

### Phase 1: foundation. Days 1 to 3.

- `theme.json` with the corrected tokens. Confirm the variables reach both the front end and the editor before building anything on them.
- `functions.php`, `inc/setup.php`, `inc/assets.php`, `inc/cleanup.php`.
- `page.php`, `header.php`, `footer.php`. One real page, end to end.
- Measure it. If one page is not under budget, 48 will not be.

*What breaks here:* the font not loading in the editor, so post content looks different from the site. We test the editor, not just the front end.

### Phase 2: the blog. Days 4 and 5.

- `single.php` with the `<h1>` fix, `archive.php`, `search.php`, `404.php`.
- Migrate the posts. This is far smaller than it sounds, and the detail is in section 8.

*What breaks here:* posts whose content is split across several Elementor widgets can lose ordering. We check those 10 individually rather than trusting a bulk operation.

### Phase 3: the section library and the homepage. Week 2.

- Split the 102 KB stylesheet into one file per section. This is the largest single job in the plan and the earlier specification badly underestimated it.
- Build the section loader so a page downloads only the CSS for sections it contains.
- Convert `homepage-content.html` into `front-page.php` plus section partials.
- Port the animations. They already work; they stop needing the Elementor workarounds.

*What breaks here:* the CSS split changes specificity and something shifts visually. We split one section at a time and compare against the current render, rather than splitting all of it and then debugging.

### Phase 4: templates and structure. Week 3, first half.

- `templates/service.php` with Meta Box fields. Build it for Human Resources, then prove it travels by doing Accounting with content only.
- `templates/market.php` and `templates/guide.php`.
- Navigation. Four menus and possibly a mega-menu.
- Implement the nine structural decisions.

*What breaks here:* the Meta Box display condition for page templates. We use a page-ID fallback alongside it so the fields appear either way.

### Phase 5: migration, testing, launch. Week 3, second half.

- Move the remaining pages, heaviest first: Homepage, Engagement Team and Our Approach are 41 per cent of the total work between them.
- Accessibility pass. Heading order, keyboard focus, contrast on the dark navy sections, `prefers-reduced-motion`.
- Switch the theme. Deactivate the retired plugins but **do not delete them for at least a week.**

### If a week slips

Cut in this order: the guide template merges into `page.php`; the mega-menu becomes a plain dropdown; the podcast page stays on Elementor until after launch; the lightest pages migrate after go-live.

We would not cut Phase 0, and we would not cut the accessibility pass.

---

## 8. Content migration

### The blog is nearly free

All 22 posts keep their article text in `post_content`. Elementor is wrapped around the outside rather than holding the content.

- Seven posts have no Elementor data at all and move untouched.
- Five posts are a single text widget containing the whole article. Removing the Elementor meta leaves the article intact.
- Ten posts are split across a few heading and text widgets and need checking, roughly a quarter of an hour each.

The widget types across the entire blog are `text-editor` (59), `heading` (46), `image` (6), `html` (3), and one each of form, gallery and icon-box. No carousels, no columns, no sliders.

Do not delete `_elementor_data` until after launch. Deactivating Elementor makes posts fall back to `post_content` automatically, so the meta is your rollback.

### The pages are the work

520 widgets across 25 published pages, in 29 distinct types. Three pages hold 215 of them.

Four of the five service pages carry eight widgets each, which is a separate finding worth naming: your commercial pages are the thinnest content on the site. The field-driven template makes depth easy to add later. We suggest treating that as work with marketing after launch rather than folding it into the migration.

---

## 9. SEO

Nothing here changes a URL. Everything else in this section depends on that. The domain move to synergibpo.com is already scheduled, and a redesign plus a domain change in the same window makes any ranking movement impossible to attribute. Redesign first on the existing domain, let it settle six to eight weeks, then move the domain as its own clean event.

Carried through the rebuild:

- Every URL identical. No redirects needed at this stage.
- Yoast stays. It is doing real work and the domain move will need it.
- One `<h1>` per page, from the template, never typed by an editor. This fixes 22 posts at once.
- Page titles under 60 characters. Fifteen are currently over.
- `LocalBusiness` schema and `hreflang` for the UAE and KSA pages.
- Alt text backfilled for images on migrated pages.

Two things to resolve before launch: the duplicate Yoast installation, where Premium 27.2 sits alongside free 28.3 and shows an unactivated subscription, and Site Kit, which is installed but never set up while GA4 loads 556 KB on every page.

---

## 10. Performance

| Metric | Today | Budget |
|---|---|---|
| Page weight | 6,601 KB | under 1,000 KB |
| Requests | 111 | under 40 |
| JavaScript | 3,099 KB | under 200 KB |
| CSS | 1,695 KB | under 120 KB per page |
| Render-blocking scripts in head | 6 | 0 |

Four things get you there, and none of them is heroic.

**Removing the builder stack.** Element Pack, Slider Revolution and Instagram Feed's site-wide loading account for the large majority of the payload. Element Pack alone ships 1.17 MB for a carousel and a mega-menu.

**Conditional section loading.** A page downloads CSS only for sections it contains. This is what makes the CSS budget reachable, because the design's stylesheet is 102 KB on its own.

**One font pipeline.** Today there are four: Google's CDN for two families plus Elementor's local copies of two more. The theme self-hosts one family, one file, latin subset.

**Core image handling.** WordPress already emits `srcset`, lazy loading and `fetchpriority`. The remaining gap is upload dimensions and compression.

The starting position is better than it looks. The design's JavaScript is 47.6 KB, vanilla, with no jQuery and no external libraries. That budget is not aspirational; you are already well inside it.

One honest note on the CSS budget. The earlier specification set 120 KB for the whole site, and the design's stylesheet is already 102 KB. Read as a per-page budget with conditional loading it is achievable. Read as a whole-site total it is not, and pretending otherwise would guarantee a missed target in week three.

---

## 11. Security

Two items outrank everything else in this document, and neither is a performance issue. Both should be dealt with this week, independently of the rebuild.

**A nulled licence plugin is active on production.** "Elementor Pro Activator" v1.0.3 spoofs the Elementor licence API. We read its code: beyond faking the licence, it intercepts requests to Elementor's template library, forwards the full request payload to `gpltimes.com`, and injects whatever comes back into your site as template content. So a third party you have no relationship with receives your Elementor connect request data and controls content returned to your site.

It also means Elementor Pro cannot be updated. It is pinned at 4.1.2 while the free plugin has moved to 4.2.3, because updating would break the bypass.

Order of work: run a full Wordfence scan first, because removing the plugin does not remove anything it may already have installed. Audit the administrator accounts. Then buy a legitimate licence or bring the migration forward. Removing the activator does not take the site down: pages already built keep rendering, and what you lose is the ability to edit Pro widgets.

Worth knowing: **Wordfence has never completed a scan.** It is installed and it is version 9.0.0, but there is no record of a scan finishing. Nothing has actually been checked.

The reassuring part, from what we could verify: there are four users in total, three administrators who all appear to be staff, and one editor account used by an SEO tool. No rogue accounts. No PHP files in the uploads directory. The activator's own code carries no obfuscation.

**A remote-code-execution tool is running on production.** Novamira exposes arbitrary PHP execution, filesystem read and write, and the ability to mint a password-free admin login link. Its own description says it is for development and staging only. Note the interaction with your own hardening: you run WPS Hide Login to conceal the login page, and a tool that generates password-free admin links routes straight around it. Move it to staging.

Smaller items: the plugin and theme file editor is enabled in wp-admin and should be disabled; Bit Integrations Pro is installed but inactive, which is dormant attack surface; and there is orphaned scheduled work from plugins that are no longer installed, including ManageWP, whose public keys are still stored.

### Retired by this project

Roughly 240 US dollars a year in licences goes away: Elementor Pro, Element Pack Pro, Slider Revolution and Instagram Feed Pro. Plus the maintenance time, which is the larger number.

---

## 12. Rollback

At every point there is a way back.

- The old theme and Elementor stay installed, deactivated, for at least a week after launch.
- `_elementor_data` is not deleted until after that week. Posts and pages fall back to it automatically.
- The theme is in Git, so any change can be reverted to a known-good commit.
- Staging carries the same content, so anything can be rehearsed before it is done live.

The one thing with no rollback is the domain migration, which is exactly why it is scheduled as a separate event with its own quiet window.

---

## 13. What we need decided before Phase 1

1. **The architecture.** Hybrid theme, as recommended, or full block theme. Everything else follows from this.
2. **Phase 0 goes ahead this week.** Staging, Git, a fallback theme, a verified backup.
3. **The two security items** get an owner and a date.
4. **The nine structural decisions** in section 6.
5. **Which prototype direction is frozen.** No section work starts until it is.

---

## Annex: where the figures come from

Read from the live site on 20 August 2026 through the Novamira connection.

| Figure | Value |
|---|---|
| Published URLs | 48 (26 pages, 22 posts) |
| Drafts | 10 (7 pages, 3 posts) |
| Elementor widgets on published pages | 520, in 29 types |
| Widgets in the 3 heaviest pages | 215 (41 per cent) |
| Widgets on 4 of the 5 service pages | 8 each |
| Posts with no Elementor data | 7 of 22 |
| Posts that are a single text widget | 5 of 22 |
| Images | 906, of which 806 lack alt text |
| Design stylesheet | 102 KB minified, 51 custom properties, 25 `!important` |
| Design JavaScript | 47.6 KB, no jQuery, no external libraries |
| Elementor data in the database | 87.5 MB, of which 82.9 MB is old revisions |
| Registered custom blocks | none |
| Site Editor templates in the database | none |

Two figures in the 30 July review have since moved: it counted 40 URLs and 779 images needing alt text. The correct figures are 48 and 806.

The database finding is worth acting on separately. Deleting the 82.9 MB of Elementor revisions is a single command with no risk to live content.
