# JobPosting structured data — the decision, and the build it needs

*Written for whoever picks up Google Jobs for synergi.ae — a developer who
knows this theme, and the person deciding whether it is worth the work.*

Checked against
[Google Search Central: JobPosting structured data](https://developers.google.com/search/docs/appearance/structured-data/job-posting)
as revised **8 September 2026**. Re-check it before building; this page moves.

> **Overtaken on 30 September 2026.** The one-URL-per-role build below was
> not done as a `syn_role` post type. The portal team's **Synergi Careers**
> plugin gives every role published in the portal its own page at
> `/careers/<title>-<id>/` and emits the `JobPosting` there; the theme keeps
> the markup through `inc/careers-feed.php` and
> `synergi-careers/single-vacancy.php`. The policy analysis and the property
> mapping below still stand and were used to review the plugin's markup —
> the gaps found (hiring organisation, `directApply`, `datePosted`, country)
> are in `careers-feed-vendor-findings.md`. The repeater remains the source
> only when the plugin is absent.

---

## What was there, and why it is gone

`sections/positions.php` emitted one `JobPosting` object per dated role, all on
`/careers/`. That was removed on 16 September 2026. It was not a bug in the
markup — the markup was fine. The page was the problem.

Google's job posting content policies, under *Irrelevant content*:

> The `JobPosting` markup must only be used on pages that contain a single job
> posting. We don't allow the use of `JobPosting` markup in any other page,
> including pages that do not list any job.

And the troubleshooting page, under *Structured data is on the wrong page*:

> A job listing page (a search results page that contains one or more job
> postings) has `JobPosting` structured data on the page. `JobPosting`
> structured data must only be on a job posting page (a page that contains a
> single job and isn't a search results page). You may have received the Search
> Console message: "Structured data policy violation - A list page should not
> include structured data for individual jobs".
>
> **Fix the issue:** Remove `JobPosting` structured data from the listing page.
> […] After you resolve the issue, submit your site for reconsideration.

This is a **policy**, not a technical preference. The penalty path is a manual
action and a reconsideration request, not silent non-display. `/careers/` lists
three roles, so it has been a list page since the day it shipped.

**It never actually fired.** The page carries a Yoast `noindex` by the
business's own instruction (14 Sep), so Google has never processed it. The
markup was inert — and would have become a live violation the hour that noindex
came off, which is the single most likely change anyone would ever make to this
page. That is why it was removed now rather than left for later.

`ItemList` is not a way out. The JobPosting documentation does not mention it,
and JobPosting is not an eligible type in either of Google's carousel
documents.

## The consequence

**Google Jobs requires one URL per role.** There is no compliant way to get
three roles into Google Jobs from one page. Either the roles get their own
URLs, or the site does not appear in Google Jobs. Those are the only two
options, and doing nothing is a legitimate choice — see the trade-off below.

---

## The build, if it is approved: a `syn_role` post type

`inc/careers-fields.php` has predicted this from its first line:

> That changes the day a position needs its own URL — a shareable listing with
> its own title tag, say — at which point it becomes a post type with an
> archive, and this repeater is migrated into it.

Follow `inc/case-study-post-type.php` throughout; it is the same shape of
change and it solved the same problems (CLAUDE.md §13, one name everywhere).

### URLs

| URL | What |
|---|---|
| `/careers/` | Stays exactly as it is — the ordinary page on `templates/careers.php`, keeping its editable intro and its perks band. |
| `/careers/{slug}/` | New. One role, one page, one `JobPosting`. |

Register with `'has_archive' => false` and `'rewrite' => array( 'slug' => 'careers' )`,
exactly as the case studies do, so `/careers/` stays the page and does not
become an archive that eats the copy. **No existing URL changes** (CLAUDE.md
§2.8); this only adds.

### The content move

The three live roles move out of the `careers_positions_list` repeater and
become three posts. Write it as a one-shot migration that reads the repeater
and creates the posts, run on staging first, and leave the repeater data in
place afterwards as the rollback — the same discipline as `_elementor_data`
(CLAUDE.md §2.9). `sections/positions.php` then lists the posts instead of the
rows, and keeps its markup, its accents and its `<details>` behaviour.

### Two fields the repeater does not have

1. **`hiring_organization`** — text, optional, labelled "Hiring company, if not
   Synergi". See the recruiter section below.
2. **`valid_through`** — already added, 16 Sep, and already in the repeater. It
   carries over to the post type unchanged.

### The mapping

Emitted from `inc/careers-schema.php` on the single-role template only.

| Field | Property | Notes |
|---|---|---|
| `title` | `title` | Required. |
| `description` | `description` | Required. HTML is allowed and encouraged; keep the `wp_kses_post()` sanitising. |
| `posted` | `datePosted` | **Required.** Skip any role without one — a guessed date is worse than no markup. |
| `valid_through` | `validThrough` | Listed as Recommended, but the docs add "Note: This is required for job postings that have an expiration date." Treat it as required. Prefer the `T00:00` form Google's own examples use; it is a `DateTime`, and "populated and in the past" is the pass/fail test. |
| `type` | `employmentType` | `FULL_TIME` · `PART_TIME` · `CONTRACTOR` · `INTERN` · `TEMPORARY`. Case-sensitive. The stored keys are these values lowercased, so `strtoupper()` is the whole mapping — that was deliberate when the select was written. |
| `department` | `occupationalCategory` | The label from `syn_careers_department_choices()`, never the slug. |
| `location` | `jobLocation` → `Place` → `PostalAddress` | `addressLocality` plus an ISO 3166-1 alpha-2 `addressCountry` — "you must include the `addressCountry` property". See *Locations* below. |
| `apply_url`, else `apply_email` | `url` / `directApply` | `directApply` is still current and still Recommended; it is not deprecated. A `mailto:` qualifies: Google counts "the job posting lists the email address […] where they can submit the application" as a direct apply experience. |
| — | `hiringOrganization` | Required. See below. |

### Locations

**Do not write a city→country table in the theme.** `inc/records.php` settled
this already, for the locations record:

> The country code is typed rather than derived. Deriving it would mean a
> country-name-to-code table living in the theme, which is data in code, and it
> would be wrong for the first office opened somewhere the table did not
> anticipate.

That reasoning holds here and the data already exists. Resolve a role's
location against the **locations site record**, which stores city, country and
a typed two-letter `code` per office, edited at Settings → Site records
(CLAUDE.md §7a: stored once, read everywhere). A role in a city with an office
resolves for free, and a new office added at the settings screen starts working
for job postings with no code change.

When a role sits somewhere with no office, add a country field to the role
rather than guessing from the string. **If the country cannot be resolved
confidently, omit `jobLocation` entirely** — a wrong country sends the listing
to the wrong job seekers, which is worse than no listing.

**Remote roles** are a separate path, and the required-properties table alone
will mislead you:

> For jobs in which the employee may or must work remotely 100% of the time,
> you must use `jobLocationType`. The `jobLocation` property isn't required if
> `applicantLocationRequirements` is present.

So a fully-remote role gets `jobLocationType: "TELECOMMUTE"` **and at least one
country in `applicantLocationRequirements`** — Google requires a minimum of one
country, and without it shows the job to anyone in the `jobLocation` country.
Google also requires the visible description to state that the role is 100%
remote, and forbids marking up roles that are only occasionally remote. That is
an editorial rule, so put it in the field's help text.

### Roles placed on behalf of a client

This is not hypothetical: the live Fractional Chief Commercial Officer role is
a 12-month mandate for a client in flexible workspace and commercial real
estate. Synergi is the recruiter there, not the employer.

`hiringOrganization` is **required** and is defined as "The organization
offering the job position". Asserting Synergi for a client's role is
inaccurate, and Google's misrepresentation policy separately names "Job
postings on behalf of an organization or company without authorization".

There is an official convention for exactly this case:

> If the organization is hiring anonymously (for example, a staffing agency on
> behalf of an anonymous employer or an employer directly on your platform),
> use the `confidential` value for the `hiringOrganization.name` field.

So: the new `hiring_organization` field, with three outcomes —

- **blank** → Synergi is the employer; emit the Synergi organization;
- **a client name** → emit that name, and only ever with the client's written
  permission, which `inc/service-fields.php` already demands for case studies;
- **the literal `confidential`** → for a named-nowhere client, exactly as
  Google's example writes it, lowercase.

Two things to note if you are working from memory rather than the live page:
the old guidance *"do not provide the hiring organization as the job board"* is
**no longer in the documentation**, and substituting the recruiter's name is
not a documented option.

### Connecting to Yoast's graph

Emit a **full Organization object carrying Yoast's `@id`** — not a bare `@id`
reference. Consumers merge nodes that share an `@id`, so the graph stays
connected and is not duplicated; but if Yoast's organization node is ever
absent (the "site represents a person" setting, or Yoast deactivated), a bare
reference would dangle and `hiringOrganization` would resolve to nothing, which
fails a required property. A full node degrades to a correct standalone one.

### Do not forget

- Emit on the single-role template **only**. Nothing on `/careers/`, nothing on
  `/`, nothing anywhere else.
- **Skip `filled` roles.** "Jobs that are no longer open for applications must
  be expired […] Failure to take timely action on expired jobs may result in a
  manual action."
- Removing a role should remove or 404 its page, or leave `validThrough` in the
  past. A filled role whose page still returns 200 with live markup is the
  exact case the policy punishes.
- Yoast has no job block, so unlike the FAQ band there is nothing to stand down
  for (CLAUDE.md §8).

---

## The trade-off, stated plainly

**Building it** costs a post type, a migration of three roles, a single-role
template, a rewritten listing band and two new fields — and it adds URLs to a
site that has been deliberately conservative about URLs. It also gives every
role a shareable link with its own title, which is useful on LinkedIn whether
or not Google Jobs ever matters.

**Not building it** costs nothing and loses nothing that exists today. The
careers page is `noindex`, the old markup never reached Google, and three roles
posted to a page nobody indexes is not a recruitment channel. If hiring stays
at this volume, the honest answer may be that Google Jobs is not worth a post
type.

**Recommendation: do not build it for Google Jobs alone.** Build it when the
business wants a shareable URL per role — that benefit is real today and does
not depend on Google — and take the job markup as the thing that becomes
possible once the URLs exist. Until then the page is correct as it stands: no
markup, no violation, nothing lost.
