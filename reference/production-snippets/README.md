# Production snippets — preserved before Novamira is deactivated

Code that was running on **production** from inside Novamira's sandbox (`wp-content/novamira-sandbox/`). Preserved here on 24 Aug 2026 because sandbox files only load while Novamira is active — deactivating it silently stops them.

Identical copies also exist in staging's sandbox, so nothing is lost either way.

## `connect-page-performance.php` (3,087 bytes, dated 6 Aug 2026)

**Live functionality.** Scoped to the published page **10406, "Connect with Synergi | Official Links"** — a standalone links page (the sort used from a social-media bio). On that page only, it:

- dequeues ~20 stylesheets and ~20 scripts the page never uses (Theratio, Bootstrap, Element Pack, UIkit, Slider Revolution, Instagram Feed, Wordfence AJAX, Elementor frontend, jQuery)
- removes the emoji detection script
- preloads the Montserrat woff2 and the logo image, with `fetchpriority="high"` on the LCP image

**Why it matters to us:** this is the previous developer solving, by hand and for one page, exactly the problem the new theme solves site-wide. It is also a useful reference list of which handles are safe to drop.

**What happens if Novamira is deactivated and this is not moved:** page 10406 keeps working but reverts to loading the full builder payload — noticeably slower, on a page most likely opened from a phone.

**Options:** copy it into `wp-content/mu-plugins/` on production (mu-plugins load automatically and independently of any plugin), or accept the slowdown until the new theme launches and makes it redundant.

**At launch:** retire it. The new theme's conditional section loading covers this page properly, and page 10406 becomes an ordinary template.

## Not copied here

- `synergi-homepage-concept-sections.php` (51.9 KB) — scoped to **draft** page 10382 only, so it has no public effect. Still present in staging's sandbox if ever needed.
- `backup-instagram-links-20260819.json` (130 KB) and `synergi-backups/page-10479-pre-shortcode-20260813-135955.json` (374 KB) — data backups the previous developer made before content changes. Left on the server; retrievable through hPanel File Manager.
