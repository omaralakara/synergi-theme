# tools/

Four throwaway-quality scripts that turned out to be worth keeping. **None of
them is part of the theme.** They live outside `synergi/`, nothing in the theme
requires them, and the site runs identically if this folder is deleted — they
are analysis aids, in the same spirit as `reference/`, not a build pipeline
(CLAUDE.md §2.3).

They need Node (any recent version) and, for the zip builder, Windows
PowerShell. All paths resolve from the scripts' own location, so they can be run
from anywhere.

---

## Why the CSS tools exist

`design-source/assets/css/main.min.css` styles the homepage across **eight
passes**, and later passes reverse earlier ones outright. Reading it by eye does
not work, and we have the scars to prove it twice:

- **Stage 2** — the `:root` audit stopped at the sixth of eight blocks, so
  `--section-space` was recorded as `7.5rem` when the value that actually wins is
  `clamp(2.8rem, 4.5vw, 4.9rem)`. Corrected on 24 Aug (CLAUDE.md §3).
- **Stage 5, section 02** — `.service-card-summary` looked like readable body
  text in the source. It is not: `.service-card-intro>p:first-of-type` outranks
  it on specificity (0,2,1 against 0,2,0), so what renders is uppercase, 800
  weight, 11px. Nothing about reading the file top to bottom reveals that.

Every remaining section has the same eight-layer problem. Resolve it with
`cascade.js` before writing a line of a section stylesheet.

---

## `cascade.js` — what actually wins

Parses the whole source, then for a described element at a described viewport
returns the declarations that survive the cascade. It accounts for the three
things that make eyeballing fail:

- **source order** across all eight passes,
- **specificity**, including `:not()`, attribute selectors and pseudo-classes,
- **shorthand resets** — `margin: 0` in pass 8 wipes `margin-bottom: 1.1rem`
  from pass 2. Without this the card measurements come out wrong; it is the bug
  that hid the real kicker and heading spacing until it was fixed.

```
node tools/cascade.js tools/examples/services.json
```

The spec file lists the viewports to evaluate and the elements to resolve. An
element is a **chain** — the ancestor path down to the target — because
descendant selectors need it. Copy `examples/services.json` and edit:

```json
{
  "envs": [ { "name": "desktop", "width": 90 }, { "name": "<=48rem", "width": 40 } ],
  "elements": [
    { "name": ".ssb-card", "chain": [
      { "tag": "html", "classes": ["js"] },
      { "tag": "body" },
      { "tag": "section", "classes": ["section", "ssb-section"] },
      { "tag": "div", "classes": ["ssb-card"], "states": ["hover"], "attrs": { "data-index": "1" } }
    ] }
  ]
}
```

`width` is in **rem** (the source's breakpoints are in rem, so 90 is a wide
desktop, 40 is a phone). Per element: `tag`, `classes`, `states` (for
`:hover`, `:focus-visible`, `:first-of-type`…), `attrs`, `pseudoEl`
(`"before"` / `"after"`).

Drop `"classes": ["js"]` from the `html` entry to resolve the **no-JavaScript**
variant — the source gates a lot of layout on a `.js` class, and that fallback
has to be checked too (definition of done, CLAUDE.md §10).

## `diff.js` — the same thing, readable

`cascade.js` on a real section prints a few thousand lines. `diff.js` prints the
first viewport in full and then only what *changes* at each narrower one, which
is the shape a stylesheet is actually written in.

```
node tools/diff.js tools/examples/services.json
```

Use this one by default; reach for `cascade.js` when you need to see which
selector and which `@media` block won a particular property.

## `extract.js` — every rule matching a pattern

A grep that understands rule boundaries and `@media` context. Useful for a first
look at a section before writing its spec file, and for finding rules whose
selectors you did not know existed.

```
node tools/extract.js "(^|[ ,.])ssb"
```

## `audit.js` — the structural rules, checked

Turns the parts of CLAUDE.md that can be checked mechanically into one command,
so "is the structure still right?" stops being an afternoon of grepping. Run it
at the end of every section, and before tagging a stage.

```
node tools/audit.js
```

Eight checks, each naming the rule it enforces: section trios and orphans (§4),
declared-vs-rendered sections and their order (§4), the `ABSPATH` guard and
`syn_` prefixes (§4), colour literals and physical directions (§2.7, §2.11),
every `!important` having a reason above it (§2.12), each class being defined in
exactly one stylesheet and every class in a partial resolving to a rule (§13),
and the CSS/JS budget (§6).

Exits non-zero on any failure, so it can gate a stage.

Two things it deliberately does not check, because it cannot: whether a page is
under budget *as measured in a browser* (the budget check sums the whole theme
before conditional loading, which is the pessimistic case), and anything about
how a section actually looks.

## `build-zip.ps1` — a zip WordPress will accept

Windows PowerShell 5.1's `Compress-Archive` writes **backslashes** into ZIP entry
paths. The ZIP spec requires forward slashes, so on a Linux host PHP reads the
archive as one flat file literally named `synergi\style.css` — and the upload
fails with *"The theme is missing the style.css stylesheet."* Windows-hosted PHP
tolerates it, which is why this only bites on staging.

```
powershell -File tools/build-zip.ps1
powershell -File tools/build-zip.ps1 -ZipName synergi-theme-stage-5-section-03.zip
```

Writes to the repo root and prints a verification line: entry count, backslash
count (must be `0`), the single top-level folder (must be `synergi`), and
whether `synergi/style.css` is present. If any of those look wrong, do not
upload it.

Windows Explorer's *Send to → Compressed (zipped) folder* is also correct, if
you would rather not run a script.

---

## Limits worth knowing

`cascade.js` is a good-enough CSS engine, not a browser. It does not implement
`@layer`, `@container`, cascade layers, or `:has()`; it treats `@supports` as
always true; and it resolves `!important` by rank rather than by full origin
rules. The design source uses none of those, which is why it is good enough —
check before trusting it on a different stylesheet.
