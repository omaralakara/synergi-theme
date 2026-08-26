# Sitemap, navigation and content architecture

Decided 26 Aug 2026, from the stakeholder content structure, the live page
inventory on staging, the existing menus, and the Yoast focus keywords already
earned. This is the decision record; `stage-6-scope.md` holds the Stage 6 plan
that follows from it.

**The rule behind every decision below:** a thing gets its own URL when someone
would arrive at it from Google or send it to a colleague on its own. Everything
else is a section of the page it belongs to, or a record read by several pages.
Nothing is duplicated — where the same content is needed twice, it is stored
once and rendered twice.

---

## 1. The main menu

Six items, matching the six groups in the structure. Four have dropdowns.

```
Home
About Us      ▾  Our Approach · Our Leadership · Global Locations
Our Services  ▾  Human Resources · Technology & AI · Marketing
                 Procurement · Accounting · Project Management
Our Solutions ▾  Shared Services · Build–Operate–Transfer
                 Systems Implementation · Carve-out & Integration
                 Fractional Leadership
Media         ▾  Executive Podcast · Case Studies · Blog · Events
Contact Us
```

Dropdowns list **pages a visitor might want directly**. They are not a mirror of
the sitemap: About Us has three children in the menu but more sections on the
page, and that is deliberate — a dropdown with nine items is a wall, not a
navigation.

### Menu housekeeping this replaces

The live Main Menu has three faults worth naming, because they are visible to
visitors today:

- **Accounting appears twice** under Our Services.
- **Shared Services and BPO Services are nested under "Our Approach"**, which is
  not where anyone would look for them.
- **Project Management is missing**, because the page does not exist yet.

Three of the four menus are unused: **Impact Menu** (empty), **Menu One Page**
(seven anchor links from a demo), **Menu Service** (empty). Only *Main Menu* is
assigned, to `primary`. The other three should be deleted — that is build plan
decision 7, and the answer is now clear.

---

## 2. Every row in the structure, and what it becomes

Legend: **Section** = part of its parent page · **Page** = its own URL ·
**Archive** = a listing of many things · **Record** = stored once in
`syn_records`, rendered wherever needed · **Post type** = many items, each with
its own URL.

### 1. Homepage — everything is a section

| Row | Becomes | Notes |
|---|---|---|
| A brief about what we do | **Section** (01 hero) | Copy change: one sentence, legacy keyword paragraph deleted |
| The services | **Section** (02) | Six cards linking to `/our-services/{slug}/`. Reads the `services` record |
| Industries we serve | **Section** (04) | Already six, already matching |
| Synergi in numbers | **Section** (06) | Reads the `figures` record. Already sits above locations |
| Our partners | **Section** (07) | Reads the `partners` record; the band links to the new `/our-partners/` page |
| The locations | **Section** (08) | Reads the `locations` record; gains entity and function |
| Upcoming events | **Section — new** | Reads the `events` record; links to `/media/` |
| Social media latest posts | **Section** (10) | Reads the `social` record; links to `/contact-us/#social` |

Confirmed as a change list, not a replacement: shared services (03), why choose
Synergi (05), blog (09), podcast (11) and the closing CTA (12) all stay.

### 2. About Us — one page, one record, two children

| Row | Becomes | URL |
|---|---|---|
| The team | **Page** | `/our-leadership/` — exists, 215 words |
| Vision, mission, values | **Section** of About Us | — |
| Our approach | **Page** | `/our-approach/` — exists, **1,118 words**, the most substantial page on the site |
| Key figures | **Record** → section | Same `figures` record as the homepage |

*The team* is a page rather than a section because the structure's own reasoning
— *"for a boutique the people are the product"* — argues for depth, and depth
needs a URL. `/engagement-team/` (84 words) is the same subject, thinner; it
stays live because §2.8 forbids removing URLs, and folds into `/our-leadership/`
with a 301 at the domain move.

*Vision, mission, values* is a section: it is three short statements, nobody
searches for it, and nobody links to it alone.

### 3. Our Services — a listing page and six children

| Row | Becomes | URL |
|---|---|---|
| (the listing) | **Archive-style page** | `/our-services/` — exists, 307 words |
| HR | **Page** | `/our-services/human-resources/` — exists |
| Technology & AI | **Page** | `/our-services/technology-ai/` — exists |
| Marketing | **Page** | `/our-services/marketing/` — exists |
| Procurement | **Page** | `/our-services/procurement/` — exists |
| Accounting | **Page** | `/our-services/accounting/` — exists |
| Project Management | **Page — new** | `/our-services/project-management/` |
| Case studies | **Post type**, queried per service | see §3 |

The parent/child URL pattern is already established on the live site and is kept
exactly (§2.8). Project Management is the only new one, and it is also the
proof that the template can create a page rather than only re-skin one.

### 4. Our Solutions — a new listing page and five children

| Row | Becomes | URL |
|---|---|---|
| (the listing) | **Page — new** | `/our-solutions/` |
| Shared services design & set-up | **Page** | `/shared-services-uae/` — **existing page, see §4** |
| Build–Operate–Transfer | **Page — new** | `/our-solutions/build-operate-transfer/` |
| Systems implementation | **Page — new** | `/our-solutions/systems-implementation/` |
| Carve-out & post-acquisition integration | **Page — new** | `/our-solutions/carve-out-and-integration/` |
| Fractional leadership | **Page — new** | `/our-solutions/fractional-leadership/` |

### 5. Media — a hub over one archive, one post type and one record

| Row | Becomes | URL |
|---|---|---|
| (the hub) | **Page** | `/media/` — exists, 30 words, a stub today |
| Podcasts | **Post type** + its existing page as the archive | `/executive-podcast/` listing, `/executive-podcast/{slug}/` per episode |
| Case studies | **Post type** | `/case-studies/` listing, `/case-studies/{slug}/` each |
| Blogs | **Archive** — already exists | `/blog/`, clusters are categories (§5) |
| Upcoming events | **Record** → section | Same `events` record as the homepage |

Episodes and case studies are post types because each is a thing someone reads on
its own: an episode has a guest, a transcript and a share link; a case study is
what a buyer sends to their board. Events are a record because the structure
itself says they *"lead to Media"* — nobody is meant to land on an event page.

This also answers build plan decision 4 — *"three content hubs, Blog, Media and
Executive Podcast overlap; we suggest one Insights destination"*. Media becomes
that destination, with the podcast and the blog as sections of it that also keep
their own archives.

### 6. Contact Us — one page, three sections, all from records

| Row | Becomes | Notes |
|---|---|---|
| All locations with company email | **Section** | Same `locations` record as the homepage, plus an email field |
| Enquiry form | **Section** | Five fields maximum, routed by type/function/country |
| Social media accounts | **Section**, id `#social` | Same `social` record; the homepage links here |

`/connect/` (24 words, a link-in-bio page) stays live as a utility URL and out
of the navigation. That is build plan decision 3, answered: not consolidated
away, just not promoted.

---

## 3. Case studies — one source, two surfaces

The explicit requirement was *"one central source in WordPress; they can appear
under the relevant service and inside Media, but should not be duplicated."*

A **custom post type `syn_case_study`**, with a taxonomy `syn_case_service`
linking each case study to one or more of the six service lines and five
solutions.

- **A service page** renders `WP_Query` for case studies in its own term.
- **The Media hub and `/case-studies/`** render all of them.
- **A solution page** renders the ones tagged to it.

One record, three surfaces, nothing copied. Adding a case study is: Add New,
write it, tick which services it belongs to. It appears in every right place
immediately and in no wrong one.

A taxonomy rather than a field, because it gives `/case-studies/human-resources/`
for free — a URL a buyer can be sent directly.

---

## 4. D2 — Shared Services: combine, at the existing URL

**Recommendation: one page, not two, and it keeps `/shared-services-uae/`.**

I read the live page before deciding. `/shared-services-uae/` is 459 words,
carries the Yoast focus keyword **"Shared Services UAE"**, and its headings are:

> Why Choose Shared Services in UAE & Dubai? · Our Capabilities · Accounting
> Shared Services · Human Resources Shared Services · Procurement Shared
> Services · AI Shared Services · Marketing Shared Services · Shared Services in
> the GCC · Industries We Serve · FAQ · Get Started with Shared Services UAE

**That page already is the solution page.** "Our Capabilities" and "Get Started"
are offer content, not geography. It is thin, not wrong.

Building a second page called *"Shared services design & set-up"* would put two
pages on one site competing for the same query. That is keyword cannibalisation,
and the structure calls this *"our strongest offer and the one buyers search
for"* — the worst possible page to split in half.

So: **rebuild `/shared-services-uae/` on the solution template.** It gains the
design-and-set-up offer content and keeps the geography that earns the ranking.
The URL does not move, which §2.8 requires anyway.

**At the domain move** — already a redirect event, so the only sanctioned moment
to tidy URLs — it becomes `/our-solutions/shared-services/` with a 301, and the
five solutions become consistent. Until then the menu label reads "Shared
Services" and points at the existing URL; visitors never see the inconsistency.

The same reasoning keeps **`/bpo-services-in-saudi-arabia-ksa-riyadh/`** (714
words, focus keyword "BPO Services in Saudi Arabia") exactly where it is: it is
a geography landing page, not one of the five solutions. It is linked from Our
Solutions and from Contact, and stays out of the top navigation.

---

## 5. Blog topic clusters

The structure asks for the blog to be *"organised into topic clusters"*. The
categories already exist and nearly work:

| Category | Posts | Decision |
|---|---|---|
| Outsourcing Insights | 9 | Keep |
| Synergi News | 5 | Keep |
| Procurement | 4 | Keep — matches a service line |
| Human Resource | 3 | Keep, **rename to "Human Resources"** to match the service |
| Marketing | 2 | Keep — matches a service line |
| EIH News | 0 | Delete |
| Uncategorized | 0 | Delete |

Missing clusters for three service lines: **Accounting**, **Technology & AI**,
**Project Management**. Create them empty; they fill as marketing writes.

Nine tags exist — Architecture, Art, Building, Exterior, Furniture, House,
Interior, Livingroom, Trends — all with **zero posts**. They are leftovers from
a theme demo and should be deleted.

All 23 published posts have a featured image, so the homepage blog carousel and
the archive will render correctly with no content work.

---

## 6. The site records

Six records in one `syn_records` option, each edited on one Settings screen and
read wherever it is needed. This is what makes *"one set, used everywhere"* true
rather than a promise somebody keeps by hand.

| Record | Fields | Read by |
|---|---|---|
| `figures` | value, label, **as-at date** | Homepage 06, About Us |
| `locations` | city, country, entity, function, email, image | Homepage 08, Contact Us, Global Locations |
| `partners` | name, logo, link | Homepage 07, `/our-partners/` |
| `services` | name, slug, icon, one-liner, URL | Homepage 02, Our Services listing, service pages, menu |
| `events` | title, date, place, link | Homepage (new section), Media |
| `social` | network, URL | Contact Us, footer, homepage 10 |

The `as-at date` on figures is the structure's *"three to five **dated**
figures, matching the deck and proposals"* — the date is what lets a figure be
defended in a proposal, so it is a field, not a footnote.

---

## 7. New URLs this creates

Eight, all additions — §2.8 forbids *moving* URLs, not adding them:

```
/our-solutions/
/our-solutions/build-operate-transfer/
/our-solutions/systems-implementation/
/our-solutions/carve-out-and-integration/
/our-solutions/fractional-leadership/
/our-services/project-management/
/our-partners/
/case-studies/            (+ one per case study, and per service term)
                          (+ /executive-podcast/{slug}/ per episode)
```

**When to publish them** is a content decision, not an architecture one. The
templates and the post types are built in Stage 6; pages go live when there is
content to put on them. If any are still empty at the domain move, hold them
back rather than publishing thin pages that then rank badly.

---

## 8. Cleanup this makes obvious

From the live inventory, and answering four more build plan decisions:

- **`/full-episodek6vl3n8qz2kjf9ya7pt4br1mx0wn5ce2sj8hu9og6ry3qv5dp4ts7zc1ab8ll9nf/`**
  — a "Thank You" page live under a machine-generated slug. Give it
  `/thank-you/` with a 301, or delete it. (Decision 2.)
- **`/synergi-partners-with-the-international-customer-experience-institute-icxi/`**
  — 311 words of news built as a page. It belongs in the blog under Synergi
  News. (Decision 5.)
- **Draft duplicates**: `Engagement Team - [Duplicated]`, `Synergi UAE -
  [Duplicated]`, `Procurement` (old), `test`, `Homepage Redesign Concept`,
  `Press Releases`, `404`. Delete once the rebuild is live. (Decision 6.)
- **`/synergi-homepage-2026-draft-build/`** — the prototype, published and
  indexable. Unpublish. (Decision 1.)
- The **two thin guides** — `/hr-digital-transformation-guide/` (40 words) and
  `/procurement-readiness/` (906 words) — are the build plan's `guide.php`
  candidates and first on the cut list. The 40-word one should become a section
  of a service page rather than a page of its own.

---

## 9. What I did not decide, and why

One thing genuinely needs business input, and it does not block Stage 6a:

**Are the five Solutions pages content-ready?** The structure says
Build–Operate–Transfer is something *"we already deliver"*, so the offers are
real — but whether there is copy written for all five decides whether Stage 6d
builds five pages or one template and one page. The template is the same either
way, so 6a, 6b and 6c proceed regardless. Ask marketing before 6d starts.
