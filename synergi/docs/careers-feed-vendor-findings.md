# Synergi Careers plugin 1.0.0 — findings for the portal team

*Written for the developers of `wordpress/synergi-careers` in the portal
repository. Sent after the plugin was installed unmodified on the website's
staging site on 30 September 2026 and wired into the theme.*

## How the website uses the plugin

The plugin is installed as shipped; none of its files are edited. The theme
uses it as a **data and routing layer** and keeps the markup itself:

- `templates/careers.php` calls `\SynergiCareers\vacancies()` and hands the
  rows to the theme's own positions band. The `[synergi_careers]` shortcode and
  `render_positions()` are not used.
- The theme ships `synergi-careers/single-vacancy.php`, the override your
  `template()` filter already looks for. It renders the role inside the theme's
  header, title band and positions band.
- The theme calls `cache_hint()` itself, so **Fetch now** still purges the
  careers page.
- Everything else — fetch, validation, caching, the 60-second transient, the
  last-good copy, the `/careers/<title>-<id>/` route, the 301 from an old title,
  the 404 for a closed role, the `<title>`, Yoast canonical/OG, the sitemap
  entries and the `JobPosting` markup — is the plugin's, untouched.

That boundary works. The items below are things the theme **cannot** fix from
outside the plugin. None blocks the staging test; the JobPosting ones should
be fixed before the role pages are indexed.

## 1. The bundled role template opens a second `<main>`

`templates/single-vacancy.php` opens `<main id="main-content">`. This theme's
`header.php` already opens that element (and its skip link targets that id),
so the bundled template produces two `<main>` landmarks with the same id. The
theme override avoids it, but the default should not assume the theme's
structure: either omit `<main>` or make it a filter (`synergi_careers_open_main`,
default true) so a theme can turn it off without replacing the whole template.

Answer to your README question 2: `header.php` opens `<main>`, the template
does not.

## 2. A role page leaves WordPress believing it is the blog home

A request with only the `synergi_career` query var makes `WP_Query` set
`is_home = true`. Consequences seen: `body_class` says `home blog`, the theme's
asset loader would ship the blog stylesheet, and Yoast describes the page as
the posts page until your `wpseo_*` filters overwrite each piece. The theme
now asks `current_role()` directly rather than trusting `is_home()`.

Suggested fix in `route()` or on `parse_query`: set `$wp_query->is_home = false`
and `$wp_query->is_singular = true` for a resolved role, and expose a public
`\SynergiCareers\is_role_page(): bool` so themes have one documented question
to ask.

## 3. `hiringOrganization` is always Synergi

`job_posting()` names Synergi as the hiring organisation on every role, and the
visible page (both your template and ours) prints "Hiring on behalf of …" from
`hiringFor`. For a mandate placed for a client the markup and the page then
contradict each other, and Google's JobPosting documentation names the
convention for exactly this case:

> If the organization is hiring anonymously (for example, a staffing agency on
> behalf of an anonymous employer …), use the `confidential` value for the
> `hiringOrganization.name` field.

So: when `hiringFor` is set and is not Synergi, emit
`"hiringOrganization": { "@type": "Organization", "name": "confidential" }`
(lowercase, exactly as Google writes it). A named client should only ever be
emitted with the client's written permission; the feed does not carry that
flag today, so `confidential` is the safe default for every non-Synergi role.

## 4. `directApply` is `false` on a page that lists the application email

Google counts "the job posting lists the email address … where they can submit
the application" as a direct-apply experience. When `application.method` is
`email` the property should be `true`; `false` is only right when there is no
way to apply from the page.

## 5. `datePosted` is dropped when `publishedAt` is missing

`datePosted` is a **required** property. `array_filter` removes the null, which
leaves a JobPosting Google will flag. A role with no `publishedAt` should
either not emit JobPosting at all, or the portal should guarantee the field
(it is the publish action's timestamp, so it should always exist — please
confirm in the feed contract).

`validThrough` passes the feed's `closingAt` through verbatim. That is fine if
the feed always sends ISO 8601 with a timezone; please state that in §6b of
the design document. Also note that the website derives "last day to apply"
as `closingAt − 1 second`, the same as your template — worth writing into the
contract so both sides keep doing it.

## 6. The country comes from a text field and a hard-coded table

`location_parts()` splits "Beirut, Lebanon" and maps a dozen country names to
ISO codes; anything else passes through by name. `addressCountry` must be a
code, a wrong country sends the listing to the wrong job seekers, and a table
in code is wrong for the first office outside it. The portal already knows the
country of a vacancy: please add `country` (ISO 3166-1 alpha-2) and `city` to
the feed as separate fields, and **omit `jobLocation` entirely** when the
country is unknown rather than guessing. For `REMOTE` roles,
`applicantLocationRequirements.name` should be the country's name, not its
code, and Google requires at least one.

## 7. No Arabic route, and the labels cannot be translated

The website is bilingual (Polylang, Arabic under `/ar/`). Today:

- `/ar/careers/<slug>/` has no rewrite rule and 404s. The English role URL is
  the only one. The Arabic careers page lists the roles (in English, marked
  `lang="en"`) and links to the English role pages, which is acceptable for a
  first release but should be stated.
- `departments()`, `employment_label()`, `work_mode_label()`, `salary_text()`
  and `status_tag()` return finished labels in the `synergi-careers` text
  domain, and the plugin ships no translation files. The theme therefore words
  the type, mode and salary chips itself, from the raw feed values, in its own
  text domain — a second copy of that mapping.

Two small API changes would remove the duplication: return **keys**, not
labels — `status_key( $vacancy, $now ): 'new'|'closing'|'available'` and the
raw `employmentType`/`workMode` values already exist — and let the theme do
the wording; and register the rewrite rule under Polylang's language prefix
as well (`^(ar/)?careers/([a-z0-9-]+)/?$`) when Polylang is active.

## 8. `render.php` reproduces the theme's classes

The listing in `render.php` copies `syn-positions__*` class for class. The
theme's rule is that every class name lives in exactly one template file, so
the website will never use `render_positions()`; the copy will drift the first
time the theme's band changes. Suggest documenting `vacancies()`,
`vacancy()`, `current_role()`, `role_url()`, `settings()` and `cache_hint()`
as the supported API and marking the shortcode as a convenience for themes
without their own band. `cache_hint()` in particular is only called from
`render_positions()` today, so a theme that renders its own markup must know
to call it — a line in the README would save the next integrator an
afternoon.

## 9. Sitemap under Polylang — please verify

`wpseo_sitemap_page_content` appends the roles to the page sitemap. With
Polylang active Yoast may build one page sitemap per language; the roles
would then appear in the Arabic sitemap pointing at English URLs. Not yet
verified on staging — noted so the test is not forgotten.

## Answers to the README's questions

1. `templates/careers.php` renders the roles from a hand-built repeater
   (postmeta on the careers page). It now maps `vacancies()` into the same
   band when the plugin is active, and falls back to the repeater when it is
   not. No shortcode.
2. `header.php` opens `<main>`; see §1.
3. "Still available" is the editor's default tag on the repeater. The plugin's
   date-derived New / Closing soon / Still available is fine and is what the
   theme now shows for portal roles; the theme added a `new` state to its own
   vocabulary to match.
4. Styled in `assets/css/sections/positions.css`: `syn-positions__permalink`,
   `syn-positions__tag--new`, and the single-role layout under
   `syn-positions--single` (`__lead`, `__hiring-for`, `__closing`, `__back`,
   with a bottom margin on `__meta`).
