# Token reference notes (verified 20 Aug 2026)

Preserved from the superseded scaffold's README — these findings were verified against the live design (`main.min.css` in `synergi-homepage-assets`) and the Elementor global kit, and they matter during Stage 2 and the Stage 5 CSS split.

## Font

Montserrat is the only font the design ships — variable weight 100–900, latin subset, self-hosted. `montserrat-latin.woff2` in this folder was copied from the server and SHA-1 verified. The old spec's Josefin Sans / Inter / IBM Plex Mono were wrong (Josefin Sans belongs to the outgoing Theratio theme).

## Palette

The prototype CSS contains 79 distinct hex values across 51 custom properties. Five are aliases pointing at other tokens (`--color-signal` → secondary, `--color-charcoal` → primary-ink, `--color-paper` → surface-soft, `--color-line` → border, `--color-mint-soft` → surface-blue), collapsing the real palette to **14**: the 11 editor colors plus 3 utility values (border `#d7e1e9`, focus `#ffd15c`, success `#8dd8b4`) which stay out of the editor picker.

## The `:root` cascade problem

`main.min.css` defines `:root` **eight times** — three base declarations that override each other, five inside media queries. Use the values that actually win:

| Token | First `:root` | Later `:root` | Effective |
|---|---|---|---|
| `--container` | `80rem` | `82rem` | **82rem** |
| `--radius-sm` | `0.5rem` | `4px` | **4px** |
| `--radius-md` | `1rem` | `6px` | **6px** |
| `--radius-lg` | `1.5rem` | `8px` | **8px** |
| `--radius-xl` | `2rem` | `8px` | **8px** |
| `--section-space` | `clamp(5rem, 9vw, 8.5rem)` | `7.5rem` | **7.5rem** |

`reference/theme.json` uses the effective values. One deliberate improvement: section spacing is restored to a single clamp — `clamp(2.8rem, 4.5vw + 1rem, 7.5rem)` — matching both desktop and mobile without a media query. **Eyeball on staging.**

## Two open eyeball checks for staging

1. **`contentSize: 48rem`** (768px) was chosen as the blog reading measure — the prototype has no prose width to copy. Widen in `theme.json` if articles feel narrow.
2. **`--gutter`** uses the first `:root` clamp `clamp(1.25rem, 4vw, 3.5rem)`; the media-query overrides (`1.15rem`, `0.85rem`) are narrower than the clamp floor. Check mobile padding on staging.

## Other verified facts for the build

- The prototype CSS carries **25 `!important` declarations** — each needs a decision during the Stage 5 component split (target after split: zero, per CLAUDE.md).
- **jQuery cannot simply be deregistered:** WPForms Lite declares it as a front-end dependency and deregistering silently breaks form validation. Use the audit approach in CLAUDE.md §2.4; drop jQuery when the form stack is consolidated.
- The service rail can be a core Query Loop over children of page 2124 (parent `/our-services/`) rather than custom query code — stays current automatically when a service line is added.
- `reference/theme.json` was written for the superseded FSE scaffold: its **token values are canonical**, but at Stage 2 it gets trimmed for the hybrid theme (drop `templateParts`/FSE-specific entries; keep settings, palette, typography, spacing).
