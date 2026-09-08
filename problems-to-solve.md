# Problems to solve — a debugging checklist

Written 8 September 2026, from the audit of both sites on 7 September.
Companion to `seo-action-list.md`: that file explains *why*; this one is for
working through the problems one at a time and proving each is fixed.

Each problem gives you the **symptom**, the **evidence** it is real, a **check**
you can run yourself, the **fix**, and a **done when** test. Do not tick
anything off on the fix alone — tick it when the "done when" test passes.

Staging checks run through Novamira on `staging.synergi.ae`. Production checks
are read-only unless stated; CLAUDE.md §2.1 keeps production untouched until
Stage 8.

---

# SEVERITY 1 — Breaks the business

These lose money, not rankings. Nothing else on this list matters until these
are closed.

## P1 — Form notifications go to a dead mailbox

**Symptom.** Someone submits the contact form on the new site. Nobody is told.

**Evidence.** WPForms form 7560's notification is addressed to
`info@y0r.256.myftpupload.com` — a leftover GoDaddy temporary-hosting address
from whenever the form was first created. It is not a synergi mailbox.

**Why it is severity 1.** Combined with P2 and P3, this is the difference
between "the lead didn't reach the CRM" and "the lead does not exist anywhere".

**Check.**
```php
$d = json_decode( get_post( 7560 )->post_content, true );
return $d['settings']['notifications'];
```

**Fix.** WPForms → Simple Contact Form → Settings → Notifications:
- **Send To Email Address** → a real, monitored mailbox
- **From Email** → a synergi.ae address, so it does not land in spam
- **Reply-To** → leave as `{field_id="1"}`; that is correct, it replies to the enquirer

**Done when.** You submit a test enquiry on staging and the email arrives in the
real inbox — not when the setting is saved.

- [ ] Fixed and verified

## P2 — Nothing stores the submission

**Symptom.** If the email fails and the CRM fails, the enquiry is gone. There is
no copy anywhere.

**Evidence.** No `wpforms_entries` table exists — WPForms Lite does not store
entries. Production is currently safer than the new site: Elementor kept 193
submissions in `wp_2rgpy2b7zg_e_submissions`, so a CRM failure was always
recoverable.

**Check.**
```php
global $wpdb;
return $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}wpforms_entries'" ) ?: 'no entry storage';
```

**Fix.** Choose one:
- Accept it, and treat P1's email as the record of last resort *(cheapest, and
  acceptable if P1 and P3 are both solid)*
- Add entry storage — a paid WPForms feature, or a free alternative, or a small
  logging hook

**Done when.** You have made this a decision rather than an accident, and
whoever owns sales knows which it is.

- [ ] Decided and recorded

## P3 — The CRM connection is wired to a plugin that will be switched off

**Symptom.** Enquiries stop reaching Zoho Bigin on launch day. Silently — no
error, no warning, the flows still show as "active".

**Evidence.** Bit Integrations holds two flows (both named "Zoho Bigin",
both `status = 1`) triggered by `elementor_pro/forms/new_record`. Elementor is
deactivated on the new site, and the contact form is now WPForms, which fires
`wpforms_process_complete` instead. The flows will wait forever for an event
that can no longer happen.

**Check.**
```php
global $wpdb;
return $wpdb->get_results(
  "SELECT id, triggered_entity, triggered_entity_id, name, status
   FROM {$wpdb->prefix}btcbi_flow", ARRAY_A
);
```
Any row where `triggered_entity` is `Elementor` is dead on the new site.

**Fix, in this order — the order matters.**
1. Verify the Zoho Bigin authorisation is still valid. OAuth tokens expire; fix
   this first or every later test fails for the wrong reason.
2. Open both flows and establish **why there are two**. Same name, same trigger.
   One may serve the contact form and one a lead magnet; one may be an abandoned
   duplicate. Do not rebuild a duplicate.
3. Change the trigger to **WPForms → Simple Contact Form (7560)**.
4. **Re-map every field by hand.** This is the step that gets skipped. Elementor
   and WPForms name fields differently, so name / email / message must each be
   reconnected to the right Bigin field. Note the WPForms form has only **three**
   fields where the Elementor form may have had more — anything Bigin expects
   that no longer exists must be dropped, or the form extended.

**Done when.** A real test enquiry submitted on staging appears **in Bigin**,
with the right values in the right fields. Not "the flow saved". Not "the log
shows a run". Then repeat the same test on production immediately after launch —
a flow verified on staging can still point at the wrong Bigin pipeline once live.

- [ ] Authorisation valid
- [ ] Duplicate flow resolved
- [ ] Trigger changed to WPForms
- [ ] Fields re-mapped
- [ ] Test enquiry confirmed in Bigin (staging)
- [ ] Test enquiry confirmed in Bigin (production, post-launch)

## P4 — Enquiries fell 85% in December 2025 and nobody noticed

**Symptom.** Unknown. That is the problem.

**Evidence.** Elementor's stored submissions, by month:

| Month | Count | | Month | Count |
|---|---|---|---|---|
| Aug 2025 | 36 | | Feb 2026 | 5 |
| Sep 2025 | 29 | | Mar 2026 | 1 |
| Oct 2025 | 45 | | Apr 2026 | 6 |
| Nov 2025 | 38 | | May 2026 | 3 |
| Dec 2025 | 8 | | Jun 2026 | 6 |
| Jan 2026 | 2 | | Jul 2026 | 8 |
| | | | Aug 2026 | 6 |

From ~37 a month to ~5 a month, starting December 2025.

Two possibilities, with opposite implications:
- The 2025 volume was **spam** that a filter later caught → nothing is wrong,
  and your real lead rate has always been ~5/month
- Real enquiries **collapsed by 85%** → there is a business problem that has
  been running for nine months unexamined

**Check.** Open a handful of submissions from October 2025 and a handful from
July 2026 in wp-admin (Elementor → Submissions) and compare. Spam is obvious on
sight — gibberish names, link-stuffed messages, non-Latin bodies, addresses at
throwaway domains.

**Fix.** Depends entirely on the answer. Do not guess.

**Done when.** You can say which of the two it was. This check takes ten minutes
and is worth more than anything else on this page.

**Related.** The HR guide form's last submission was **7 December 2025** — that
offer has been dead nine months, which supports the decision to draft it.

- [ ] Checked, and the answer written down

---

# SEVERITY 2 — Loses your measurement

## P5 — The new site emits no analytics at all

**Symptom.** Launch, and traffic data stops.

**Evidence.** Three loaders, all silent on staging:
- Site Kit → Analytics has `useSnippet: false`
- The theme's `inc/integrations.php` GTM loader has no container ID
  (`syn_gtm_id` unset, `SYN_GTM_ID` undefined) so it renders nothing
- The ASE code-snippet feature is disabled, so the snippets that carry GA4 and
  LinkedIn on production do not run

This is *correct for staging*. The problem is that nothing has been prepared to
switch on at launch.

**Check.**
```php
return [
  'sitekit_useSnippet' => get_option( 'googlesitekit_analytics-4_settings' )['useSnippet'] ?? null,
  'theme_gtm_id'       => syn_gtm_container_id() ?: '(empty — nothing rendered)',
];
```

**Fix.** P6 below.

- [ ] Closed by P6

## P6 — The theme cannot load GA4, and no GTM container exists

**Symptom.** You cannot simply "put the GA4 ID in the theme". It will be
rejected.

**Evidence.** `inc/integrations.php` validates its ID against
`/^GTM-[A-Z0-9]{4,}$/`. It accepts a Tag Manager container ID and nothing else —
there is no gtag path. And Site Kit's Tag Manager module is inactive with an
empty container ID, so **no GTM container has ever existed for this site.**

An earlier note in `seo-action-list.md` said conversion tracking was "20 minutes
in GTM". That was wrong and is corrected: there is no GTM to configure yet.

**Fix — option (a), recommended.**
1. tagmanager.google.com → Create Account → container type **Web** for synergi.ae
2. Copy the `GTM-XXXXXXX` ID
3. In GTM: add a **Google Tag** with the surviving GA4 measurement ID (see P7),
   trigger **All Pages**
4. In GTM: add the **LinkedIn Insight Tag** (partner ID `9021449`) as a Custom
   HTML tag, trigger **All Pages** — currently loaded by an ASE snippet that
   will not run on the new site
5. **Publish** the container. A saved-but-unpublished container does nothing
6. On staging, set the `syn_gtm_id` option, or
   `define( 'SYN_GTM_ID', 'GTM-XXXXXXX' );` in `wp-config.php`

Why (a): CLAUDE.md §11 already names GTM as the intended single injection point,
the theme was written for it, it fixes LinkedIn at the same time, and it gives
conversion tracking somewhere to live.

**Fix — option (b), the fallback.** Leave Site Kit's snippet on. Zero work, GA4
only, LinkedIn stays dead, and it loads in `<head>` rather than deferred.

**Critical sequencing.** Leave Site Kit's `useSnippet` **on in production** until
(a) is confirmed firing live. It is theme-independent and will keep GA4 running
through launch whatever else happens. Only turn it off afterwards, to avoid
double-counting.

**Done when.** GTM Preview mode against staging shows both the GA4 tag and the
LinkedIn tag firing, and view-source shows the `syn-gtm-loader` script in the
footer.

- [ ] Container created and published
- [ ] `syn_gtm_id` set on staging
- [ ] Both tags confirmed firing in Preview
- [ ] Site Kit snippet left on until GTM verified on production

## P7 — Two GA4 properties are collecting at once

**Symptom.** Every analytics number is ambiguous — you cannot say which property
is the truth.

**Evidence.**
- `G-EX4ZJYVVPG` (property 462310803) via Site Kit — **this is the one the
  `datagsc/` landing-page export came from**
- `G-F8BHKGB935` via the ASE code snippet titled "Google"

Both are live on production today.

**Check.** analytics.google.com → check both properties for data volume, and
which one is linked to Google Ads (`adsLinked` is true on one of them).

**Fix.** Pick the survivor. Use its measurement ID in P6. Retire the other, or
at minimum stop adding to the confusion.

**Done when.** One property is named as authoritative in writing, and only one
tag loads on the new site.

- [ ] Survivor chosen and recorded

## P8 — Conversions have never been tracked

**Symptom.** You cannot answer "does the new site generate more enquiries than
the old one".

**Evidence.** Every row of the GA4 landing-page export shows `Key events = 0`,
across eight months and 5,072 sessions.

**Fix.**
1. In GTM, add a trigger for the WPForms confirmation
2. Add a GA4 Event tag named `generate_lead`
3. GA4 → Admin → Events → mark `generate_lead` as a **Key event**

**On the baseline — good news.** You do **not** need to rush this to get a
before-picture. P4's Elementor submissions table already gives twelve months of
real enquiry counts, which is a better baseline than anything GA could gather in
the weeks remaining. Set the tracking up properly rather than hastily.

**Done when.** A test submission appears in GA4 Realtime as `generate_lead`.

- [ ] Event firing and marked as a Key event

## P9 — Three flags that must not reach production

**Symptom.** The entire site, or the homepage alone, disappears from Google.

**Evidence.** On staging: `blog_public = 0`, plus page-level Yoast noindex on
the homepage (`homepage-rebuild`, ID 10547) and on `/media/`. All correct for
staging. All fatal if migrated. The homepage alone is 55% of clicks and 60% of
sessions.

These are **independent** — clearing the site setting does not clear the page
flags.

**Check, run on production immediately after the database lands.**
```php
return [
  'blog_public'      => get_option( 'blog_public' ),
  'homepage_noindex' => get_post_meta( (int) get_option( 'page_on_front' ), '_yoast_wpseo_meta-robots-noindex', true ) ?: '0',
  'media_noindex'    => get_post_meta( get_page_by_path( 'media' )->ID, '_yoast_wpseo_meta-robots-noindex', true ) ?: '0',
];
```
All three must read `1`, `0`, `0` respectively.

**Also must not travel:**
- The ASE snippet **"SEO: single post title H3 to H1"** — the new theme emits a
  correct single `<h1>` natively (CLAUDE.md §8) and the snippet would fight it
- Site Kit's Search Console property set to `staging.synergi.ae`

**Done when.** View-source on the live homepage and one service page contains no
`noindex`.

- [ ] `blog_public` = 1
- [ ] Homepage noindex cleared
- [ ] `/media/` noindex cleared
- [ ] H3→H1 snippet not carried across
- [ ] Search Console property reads `https://synergi.ae/`

---

# SEVERITY 3 — Holds back visibility

## P10 — Half the site is not indexed

**Symptom.** 34 pages indexed, 35 not. Google has *crawled or discovered* 18 of
them and chosen not to index them.

**Evidence.** GSC Coverage export, 4 September:

| Reason | Pages |
|---|---|
| Discovered – currently not indexed | 9 |
| Crawled – currently not indexed | 9 |
| Page with redirect | 8 |
| Not found (404) | 3 |
| Excluded by 'noindex' | 3 |
| Blocked due to other 4xx | 1 |
| Blocked by robots.txt | 1 |
| Alternate page with proper canonical | 1 |

"Crawled – currently not indexed" is Google saying *this page is not worth an
index slot*. Nine of those on a 48-URL site is a quality signal, not a bug.

**Check.** GSC → Indexing → Pages → click into each of those two rows and
**export the URL list**. The summary CSV in `datagsc/` carries counts only —
this is one of the two exports still missing.

**Fix.** For each of the 18: does it still exist on the new site, is it
redirected, or should it go? Then re-request indexing after launch.

**Done when.** You have the 18 URLs and a decision against each.

- [ ] URL lists exported
- [ ] Each URL dispositioned

## P11 — Non-brand search barely functions

**Symptom.** The site is seen constantly and clicked almost never.

**Evidence.** 993 clicks over 12 months against 76,384 impressions. **89% of
homepage clicks are people typing "synergi".** Non-brand search delivers roughly
**one click a week** — about 10 visits a month out of ~630 total sessions, so
**1.6% of your traffic**.

Impressions grew 10× since September 2025 (1,204 → 12,093/month) while clicks
stayed flat.

**Why this is severity 3, not 1.** There is almost nothing to lose, which makes
the migration far less risky than it feels. The work is building, not
protecting.

**Fix.** Not a single action — it is what the rebuild is for. After launch,
rewrite titles and descriptions against real query phrasing. The near-miss
queries worth attacking first, all currently earning **zero** clicks:

| Query | Impressions | Position | Should be owned by |
|---|---|---|---|
| hr bpo companies in uae | 939 | 8.2 | `/our-services/human-resources/` |
| sourcing and procurement providers | 888 | 8.1 | `/our-services/procurement/` |
| procurement bpo companies | 837 | 19.0 | `/our-services/procurement/` |
| procurement operations services | 700 | 4.4 | `/our-services/procurement/` |
| hr bpo companies in dubai | 680 | 17.4 | `/our-services/human-resources/` |
| bpo companies in dubai | 677 | 11.5 | `/markets/united-arab-emirates/` |

Note the pattern: the **homepage** is ranking for HR and procurement queries
that belong to the service pages.

**Done when.** Non-brand clicks per month is being tracked as the headline
number instead of impressions.

- [ ] Titles rewritten against query phrasing (post-launch)

## P12 — Old theme demo content is live and crawlable

**Symptom.** A B2B outsourcing site publishing interior-design portfolio pages.

**Evidence.** Live on production now: `/portfolio/modern-villa-in-belgium`,
`/portfolio/minimalistic-style-appartment`, `/portfolio-category/architecture`,
`/demo`, `/pricing`, `/help`, `/support`, `/sales`. About 35 GA sessions between
them. None exist on staging; none are redirected.

Likely contributors to P10's "crawled – not indexed" count.

**Fix.** They vanish at launch since staging does not have them. Let them 404 —
they have no value to preserve. But confirm they are gone rather than assuming.

**Done when.** A post-launch crawl finds none of them returning 200.

- [ ] Confirmed gone after launch

## P13 — An Arabic URL is publicly reachable

**Symptom.** Arabic is a later phase, but an Arabic URL recorded a real session.

**Evidence.** `/ar/our-services/` plus an Arabic slug appears in the GA4
landing-page export.

**Fix.** Take it offline until the Arabic phase. Polylang is inactive on
staging, so this should not survive launch — verify rather than assume.

**Done when.** The URL 404s or redirects on the new site.

- [ ] Verified not reachable after launch

## P14 — A 404 loop

**Symptom.** `/404` shows **58 sessions from only 4 active users**.

**Evidence.** GA4 landing-page export. Four people generating fifty-eight error
sessions is something looping, not people browsing.

**Fix.** Find what is requesting it. Likely a broken asset reference or a
redirect loop in the old theme. May well disappear with the rebuild.

**Done when.** `/404` sessions per user return to roughly 1:1.

- [ ] Investigated, or confirmed resolved after launch

## P15 — Production is publishing incorrect leadership

**Symptom.** `/our-leadership/` names a Board of Directors and a Strategic
Advisor the business has confirmed are out of date — people who have left.

**Evidence.** Live page ID 2302 on production, 215 words, listing Mansoor
Almheiri (Chairman), Ahmed Ayyoub (Vice Chairman), Mohamad Saker (CEO) and
Sevag Alexandrian (Advisor, with biography).

**Decided.** The new site has **no** board or governance section. Nothing to
build — staging has no such content and `/our-leadership/` redirects to
`/engagement-team/`.

**Open.** The page stays live and wrong until launch unless someone pulls it
sooner. That is a production content change and needs its own explicit
go-ahead under CLAUDE.md §2.1.

- [ ] Decide: pull now, or let it die at launch

---

# Waiting on a decision

| # | Decision | Blocks |
|---|---|---|
| D1 | Which GA4 property survives — `G-EX4ZJYVVPG` or `G-F8BHKGB935` | P6, P7 |
| D2 | Entry storage: accept the email as the only record, or add storage | P2 |
| D3 | Pull `/our-leadership/` from production now, or let it die at launch | P15 |
| D4 | Procurement wording: Gulf-focused or generic — affects copy only, not launch | P11 |
| D5 | Client names, offices, legal entities — see `open-questions.md` | Case-study indexing, Global Locations |

---

# Already solved — do not re-open

Recorded so this list stays honest about what is left.

| | Status |
|---|---|
| Redirects | 39 rules; every production URL with impressions is covered |
| Yoast titles and descriptions | Zero missing across all published content |
| Content depth on new pages | 570–1,140 words per page; beats what it replaces |
| `/shared-services-uae/` redirect | Retargeted to `our-solutions/shared-services` |
| `/synergi-uae-2-2/` redirect | Retargeted to `/` |
| Case studies + term archives | Noindexed at post-type level; `/case-studies/` stays indexed |
| `/hr-digital-transformation-guide/`, `/procurement-readiness/` | Drafted, each with a 301 |
| `/careers/` | Redirected to `/contact-us/` |
| Thank You page | Redirected to `/` |
| `why` / `why_cards` records | Populated; band now editable in one place |
| `/procurement-bpo-readiness-checklist/` | Offer retired; post has no form or gate, no work needed |
| `/our-approach/` | Dumped; draft + 301 to `/about-us/` |
| Board / governance section | Not wanted; nothing to build |
| Project folder | 20 files → 11, with an index and an `archive/` |

---

# The short version

Fix **P1** today — it takes two minutes and it is the difference between losing
a lead and losing it silently forever. Then **P3**, because it is the only true
launch blocker. Then **P4**, because ten minutes of reading old form submissions
tells you whether you have a business problem nobody has noticed.

Everything else can run alongside the launch.
