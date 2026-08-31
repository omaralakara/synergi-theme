# Open questions — answers needed from the business

One place for everything the build is waiting on. Answer inline under each
question, in any level of detail. Nothing here blocks the current work; each
item blocks one specific thing, named under **Blocks**.

Last updated 31 August 2026.

---

## 1. Which client names may be published?

The company profile identifies real clients by name, including an Abu Dhabi
government entity, several companies inside a sovereign investor's portfolio,
and an entity of a PIF group.

**What was done on 31 Aug, pending your answer:** twelve case studies were
written from the profile and published, and every one of them describes the
**kind** of organisation rather than naming it — "a Dubai-based company in the
digital asset and cryptocurrency sector" where the deck says the company name.
That was the safe default, not a decision: CLAUDE.md records that no client is
cleared for the public site, and publishing a government or sovereign-fund
client without written clearance is a commercial risk that should not be taken
by assumption.

**Complication worth knowing:** the live site already contradicts this.
`synergi.ae/our-approach/` names four clients in public today. That page is now
a draft on staging, so the contradiction does not carry into the new site — but
it does suggest the "no names" rule may simply be out of date.

**Question:** which client names, if any, may appear on the public site? A
blanket "type only, never names" is a perfectly good answer and needs no
further work. Naming specific clients is one field per case study.

**Blocks:** nothing is broken today. It decides whether the twelve case studies
carry names, and whether the case-study band can be switched on for the four
service pages that currently render nothing there.

**Answer:**

---

## 2. The fifth HR FAQ — mobilisation time

Four HR questions are answered from the profile. A fifth was drafted and left
out because nothing anywhere answers it:

> *"How quickly can you mobilise a team?"*

**Question:** what is the honest answer? A typical range is fine
("usually 4–6 weeks, faster for augmentation than for full BPO").

**Blocks:** nothing. The HR page ships with four questions. This only adds a
fifth.

**Answer:**

---

## 3. Office contact details, and which domain they use

No page currently lists a phone number or address per office. The complication
is the domain: the company deck uses `@synergibpo.com`, the live site is
`synergi.ae`, and the move to `synergibpo.com` is a separate, later event.

**Question, two parts:**

- Which phone numbers and email addresses are public, per office?
- Which domain should they use **today**, before the domain move?

**Blocks:** the address cards on Contact Us and on the new Global Locations
page. Both render correctly without them — an office simply shows no phone
number.

**Answer:**

---

## 4. Legal entity and delivered function, per office

The `locations` site record (Settings → Site records) has a slot for each of
these and they are empty.

**Question:** for each office — Abu Dhabi, Riyadh, Doha, Beirut, Damascus —

- What is the legal entity? (a UAE free-zone company, a KSA branch, a
  representative office, and so on)
- What does the office actually do? (delivery centre, sales presence, both)

If the honest answer is "just show the city names for now", that is a complete
answer and closes this.

**Blocks:** nothing. Both location bands render city names correctly without it.

**Answer:**

---

## 5. A photograph for the Marketing service page

Five of the six service pages have a photograph. Marketing does not — nothing in
the media library fits, and the closest match was healthcare staff in scrubs.
It currently falls back to the homepage's own hero image.

**Question:** can you supply one image for Marketing? Brand, events, content or
PR work in a Gulf setting. Landscape, at least 1536px wide.

**Blocks:** nothing functional.

**Answer:**

---

## 6. Three legacy landing pages that now compete with the new market pages

**Raised 31 August 2026.** This is the one to decide before Stage 7 finishes,
because it is the only item on this list that can actively cost traffic.

Three pages were written as SEO landing pages under the old site. Two of them
now target the same searches as pages built this month:

| Page | Words | What it targets | Collides with |
|---|---|---|---|
| `/bpo-services-in-saudi-arabia-ksa-riyadh/` | 714 | "BPO services in Saudi Arabia / Riyadh / KSA" | **`/markets/saudi-arabia/`**, whose title is *BPO Services in Saudi Arabia* |
| `/synergi-uae-2-2/` | 1,043 | "BPO services in UAE & the Gulf" | **`/markets/`**, whose title is *Business Process Outsourcing in the GCC* |
| `/shared-services-uae/` | 459 | "Shared services UAE / Dubai / GCC" | Nothing — but see the note below |

**Why it matters.** Two pages aimed at the same search do not rank twice as
well; they split the signal and Google picks one, often not the one you would
have chosen. Right now the old pages have the history and the new ones have the
design, which is the worst combination of the two.

**Two further facts:**

- Both legacy pages are still Elementor and are **not** on a theme template, so
  they render their raw stored content. `/synergi-uae-2-2/` also opens with a
  Slider Revolution shortcode for a plugin that is deactivated.
- `/bpo-services-in-saudi-arabia-ksa-riyadh/` lost its only internal link when
  the footer was rebuilt on 31 Aug. Nothing on the site links to it today, so
  whatever is decided, it should not stay in this state.

**Question:** for each of the two collisions, which page survives?

- **Keep the old URL, retire the new one.** Safest for traffic. The old URL gets
  rebuilt on `templates/market.php` and the new `/markets/...` page is deleted.
- **Keep the new URL, redirect the old one.** Cleaner structure, and a 301
  passes most of the ranking across — but it is a URL change, which CLAUDE.md
  §2.8 otherwise forbids, so it needs to be a deliberate decision with a written
  record.
- **Keep both, differentiated.** Only if they genuinely answer different
  questions. Given the titles are near-identical today, this needs real editing
  rather than a decision.

**Our recommendation:** keep the old URLs and retire the new market pages. The
market pages are days old and have no history; the landing pages have both.
Rebuilding them on the market template gets the new design onto the URL that
already ranks, which is the outcome with no downside.

**On `/shared-services-uae/` specifically** — no decision needed unless you
disagree with what was done. It was moved onto the solution template on 31 Aug
and is now the Shared Services solution page, because
`sitemap-and-navigation.md` §4 says that solution lives at this existing URL. A
second page had been built at `/our-solutions/shared-services-design/` three
days earlier; that duplicate has been trashed and the menu and the site record
now point here. Its URL, page title and Yoast description are all geographic
("Shared Services UAE – Dubai & GCC"), which reads oddly for a solution page but
matches what people search for — so it was left alone deliberately.

**Blocks:** Stage 7's structural decisions, and the Stage 8 migration of these
two pages. Nothing renders incorrectly today.

**Answer:**

---

## Answered — do not re-ask

- **Do the five Solutions pages have copy?** Yes. (31 Aug) All five are built
  and filled — five or six scope areas and four method stages each, and all five
  now carry FAQs.
- **"10–15% direct savings" is cleared for the public site.** (28 Aug) It is in
  the `figures` record and already renders on the homepage and every service
  page.
- **Partner names are not missing.** Five partner logos carry the names in their
  alt text: Menaitech, Odoo, Lexzur, Innovawave, ICXI.
- **Case studies:** use one where it exists, leave the section empty where it
  does not. (28 Aug) Superseded in part by question 1 — twelve now exist as
  pages of their own.
- **Procurement's 92-character page title:** shortened to "Procurement". The
  keyword phrasing survives in the Yoast `<title>`, and the URL is unchanged.
  (28 Aug)
- **`/our-partners/` and the `partners` record:** not needed for now — partners
  stay on the homepage band. (31 Aug)
- **Upcoming events:** deferred, to be added later. It is one record plus one
  section and nothing else depends on it. (31 Aug)
- **`/media/`:** it is the blog and the Instagram feed, and it was built from
  those two existing bands. (31 Aug)
- **`/our-approach/`:** content was old and was not rendering; set to draft.
  (31 Aug)
- **`/connect/`:** an event QR-code linktree, no longer needed; set to draft.
  (31 Aug)
- **The images:** do not backfill alt text across the library — the pictures are
  being replaced. 459 of the 914 images are attached to nothing at all. Alt text
  is written per page as each page is migrated. (31 Aug)
