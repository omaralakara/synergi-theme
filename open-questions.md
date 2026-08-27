# Open questions — answers needed from the business

One place for everything the build is waiting on. Answer inline under each
question, in any level of detail. Nothing here blocks the current work; each
item blocks one specific thing, named under **Blocks**.

Last updated 28 August 2026.

---

## 1. Which client names may be published?

The company profile identifies real clients, including one Abu Dhabi government
entity by name. The HR case study currently on the site uses the client **type**
only ("UAE organisation in early growth · Abu Dhabi") and no name.

**Question:** which client names, if any, may appear on the public site? A
blanket "type only, never names" is a perfectly good answer.

**Blocks:** the case-study section on all six service pages. Five of the six are
empty today and simply do not render.

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

**Blocks:** the Contact Us and Global Locations pages, when they are rebuilt.
Not the service pages.

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

**Blocks:** the locations band once it is wired to the record, and the Global
Locations page. The band renders city names correctly without these.

**Answer:**

---

## 5. Do the five Solutions pages have copy written?

Stage 6d — the solutions template, `market.php` and `guide.php` — is held until
this is known.

**Question:** has copy been written for the five Solutions pages, or do they
need to be written from the profile's maturity-lifecycle table (Startup &
Incubation · Relocation or New Market Entry · Mid-stage/SME ·
Mature/Consolidation & M&A · Divestment)?

**Blocks:** all of Stage 6d.

**Answer:**

---

## 6. A photograph for the Marketing service page

Five of the six service pages have a photograph. Marketing does not — nothing in
the media library fits, and the closest match was healthcare staff in scrubs.
It currently falls back to the homepage's own hero image.

**Question:** can you supply one image for Marketing? Brand, events, content or
PR work in a Gulf setting. Landscape, at least 1536px wide.

**Blocks:** nothing functional. The page is complete apart from this, and the
Photograph field has been deliberately left empty so it reads as outstanding.

**Answer:**

---

## Answered — do not re-ask

- **"10–15% direct savings" is cleared for the public site.** (28 Aug) It is in
  the `figures` record and already renders on the homepage and every service
  page.
- **Partner names are not missing.** Five partner logos carry the names in their
  alt text: Menaitech, Odoo, Lexzur, Innovawave, ICXI.
- **Case studies:** use one where it exists, leave the section empty where it
  does not. (28 Aug)
- **Procurement's 92-character page title:** shortened to "Procurement". The
  keyword phrasing survives in the Yoast `<title>`, and the URL is unchanged.
  (28 Aug)
