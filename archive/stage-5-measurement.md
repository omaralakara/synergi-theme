# Stage 5 — measured homepage payload

Measured on staging, 26 Aug 2026, Chrome network panel, cache disabled, hard
reload, homepage draft page. Figures as reported by the panel's summary bar.

## What was measured

```
68 requests · 2,165 KB transferred · 4,110 KB resources
Finish 7.59 s · DOMContentLoaded 2.81 s · Load 3.22 s

CSS   17 / 68 requests ·    76.7 KB transferred ·   304 KB resources
JS    32 / 68 requests · 1,152.0 KB transferred · 2,647 KB resources
```

Request count keeps climbing on scroll. That is expected and is two things
working as designed: images below the fold are lazy-loaded, and the analytics
stack is held back until the browser goes idle or the visitor interacts
(CLAUDE.md §11). The figures above are therefore the *initial* load; a
full-scroll total is higher.

## Against the §6 budget

| Metric | Budget | Measured | Verdict |
|---|---|---|---|
| CSS | < 120 KB | **76.7 KB** | pass |
| JavaScript | < 200 KB | **1,152 KB** | **fail — 5.8x over** |
| Total page weight | < 1,000 KB | **2,165 KB** | **fail — 2.2x over** |
| Requests | < 40 | **68** | **fail** |
| Render-blocking scripts in `<head>` | 0 | not yet checked | — |
| LCP | < 2.5 s | not yet measured | Load was 3.22 s |

## Whose bytes these are

The theme's own files, minified and gzipped exactly as LiteSpeed serves them:

| | Theme | Everything else | Total |
|---|---|---|---|
| CSS | **19.3 KB** (13 req) | 57.4 KB (4 req) | 76.7 KB |
| JS | **17.5 KB** (8 req) | **1,134.5 KB** (24 req) | 1,152 KB |

**The theme is 36.8 KB of a 2,165 KB page — about 1.7%.** It is inside every
budget it controls. The overage is plugin payload, and it is almost entirely
JavaScript: twenty-four requests carrying 1.13 MB.

This is not a surprise and not a theme problem, but it is the single largest
risk to the launch numbers, so it is written down here rather than left as a
feeling. CLAUDE.md §2.10's four-question gate exists to stop payload like this
arriving; what it cannot do is remove what was already installed.

## The plugins that could be responsible

From the staging inventory, 26 Aug. Active plugins that ship front-end
JavaScript, in rough order of likely weight:

- **Site Kit by Google** — GA4/GTM. §11 already names this as roughly 556 KB
  and it is deliberately deferred, so it costs nothing before first paint, but
  it still counts against the page total.
- **Instagram Feed Pro** — renders section 10, so it is on the homepage by
  definition. Feed plugins are typically heavy.
- **Wordfence Security** — front-end script on every page.
- **API KEY for Google Maps** — worth checking whether it loads the Maps API
  globally; the homepage has no map.
- **Kirki** — a Customizer framework. This theme does not use the Customizer.
- **WP External Links**, **Bit Integrations**, **WPForms Lite** (which is why
  jQuery is still present, CLAUDE.md §2.4), **Yoast SEO** + **Premium**.

Also active: **Synergi Homepage Assets v2.1.0**, the design source. It gates
everything on `is_page(10479)`, so it only loads its 104.7 KB stylesheet and
54 KB of scripts on that one draft page. If the theme's homepage is being
tested on page 10479, the prototype's CSS is loading on top of the theme's and
both the measurement above and the rendering are unreliable. Worth confirming
which page ID was measured.

## What would bring it inside budget

Cheapest first, none of them theme changes:

1. **Turn on LiteSpeed's CSS/JS combine.** The theme deliberately ships one
   stylesheet per section for conditional loading, which costs 21 requests on
   the homepage — its worst case, and 1 request on a plain page. Combining
   collapses that to two without touching the theme. Biggest single win on the
   request count.
2. **Audit the plugin list against §2.10.** Kirki and the Maps API key look
   like candidates for removal outright; the Elementor family is already
   inactive and goes at Stage 9, a week after launch.
3. **Check Instagram Feed's own settings** for how much it loads, and whether
   its lightbox and header scripts can be turned off — section 10 uses neither.
4. **Re-measure after each**, not at the end, so it is clear which change did
   what.

## What passed

Conditional loading verified on staging the same day: a plain page loads
base.css and nothing from `assets/css/sections/`. That is the second Stage 5
checklist item, and it is the mechanism that keeps the theme at 1.7%.
