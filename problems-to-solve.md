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

- [x] Address changed to `info@synergibpo.com` — **8 Sep**
- [x] A second, hidden fault found and fixed — see below
- [ ] Confirmed by a real submission arriving in the inbox

### What was changed, 8 September

**The obvious fault — where the mail was going.**

| Setting | Was | Now |
|---|---|---|
| Send To | `info@y0r.256.myftpupload.com` | `info@synergibpo.com` |
| From address | `info@y0r.256.myftpupload.com` | `info@synergibpo.com` |
| From name | `synergi` | `Synergi Website` |
| Subject | `New Entry: Simple Contact Form` | `New enquiry from synergi.ae` |

Reply-To was left as `{field_id="1"}`, which is correct — replying goes to the
enquirer.

**The hidden fault — why the mail would not have sent at all.**

Fixing the address alone would not have worked. WP Mail SMTP sends through
**Outlook**, and the authenticated mailbox is `info@synergibpo.com`. But its
From address was set to `info@staging.synergi.ae` with **Force From Email
turned on**.

Microsoft 365 refuses to send as an address the authenticated account does not
own. So every message was being forced into a sender the mailbox cannot use —
meaning form notifications on staging were most likely failing outright, not
just going to the wrong place.

`wp_mail_smtp.mail.from_email` is now `info@synergibpo.com`, matching the
authenticated mailbox. Force From Email was left on, which is now correct rather
than harmful.

**Check this on production too.** Production has its own WP Mail SMTP settings.
If its From address is similarly mismatched, live form mail has the same
problem — and that is one candidate explanation for P4's drop in recorded
enquiries.

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

- [x] Authorisation valid — **verified 8 Sep**, see below
- [x] "Duplicate" flow resolved — **they were not duplicates**, see below
- [x] Trigger changed to WPForms
- [x] Fields re-mapped
- [ ] Test enquiry confirmed in Bigin (staging) — *the last step, needs a human*
- [ ] Test enquiry confirmed in Bigin (production, post-launch)

### What was done, 8 September

Both flow rows were backed up first to the option
`syn_btcbi_flow_backup_2026_09_08` before anything was changed. Restoring is a
straight write-back of that option.

**They were never duplicates.** Flow 1 served the Contact Us form
(Elementor form `ff6e75b` on page 2014). Flow 2 served the HR Digital
Transformation Guide form (`6781c14` on page 9115) — a different form with a
different field map, including Company and Designation. The earlier note in this
file guessing at "an abandoned duplicate" was wrong.

| Flow | Was | Now |
|---|---|---|
| 1 — Contact Us | `Elementor` / `elementor_pro/forms/new_record`, active | **`WPF` / `7560`, active** |
| 2 — HR guide | `Elementor`, active | **disabled** (`status = 0`), not deleted |

Flow 2 is disabled rather than deleted because its source page is drafted and
the offer retired; if the guide ever returns, the flow and its mapping are intact.

**The new field mapping.** Bit Integrations' free plugin ships a WPForms trigger
(`WPF`), which hooks `wpforms_process_complete`. Its field names were generated
by the plugin's own `WPFController::fields(7560)` rather than guessed:

| WPForms field | → | Zoho Bigin (Contacts) |
|---|---|---|
| `0:last` — Last Name | → | `Last_Name` *(the module's only mandatory field)* |
| `0:first` — First Name | → | `First_Name` |
| `1` — Email | → | `Email` |
| `2` — Comment or Message | → | `Description` |

The WPForms Name field is already set to `first-last` format and is required, so
`Last_Name` can never arrive empty — which matters, because Bigin rejects a
Contact without it.

### Verified without submitting the form

1. `Flow::exists('WPF', '7560')` → **1 flow matched.** This is the exact call
   the plugin makes on submission, so the flow will be found.
2. The same lookup for the old Elementor trigger now returns **false** — the
   dead path is genuinely dead, and Elementor is confirmed inactive.
3. `wpforms_process_complete` is **registered**, and WPForms is active.
4. **Zoho authorisation is live.** The stored access token had expired (issued
   21 Aug, one-hour life). Refreshing it against `accounts.zoho.com` returned
   **HTTP 200** with a new token — Zoho accepted the client ID, client secret and
   refresh token. The fresh token was written back to the flow.
5. **The Bigin API answers.** A read-only call to
   `/bigin/v1/settings/fields?module=Contacts` returned **HTTP 200**, 33 fields,
   with `Last_Name` as the only mandatory one and all four mapped targets
   present. The granted scope includes `ZohoBigin.modules.ALL`, which covers
   creating Contacts.

Everything up to the submission itself is confirmed working. The one remaining
step is a real form submission, which was deliberately not done because it would
send the notification email.

### Phone added, 8 September

The business confirmed phone numbers are wanted. WPForms here is **Lite**, and
its dedicated Phone field is a Pro feature — so a Single Line Text field
labelled "Phone" was added instead. It captures a number identically and maps to
Bigin the same way; the only thing missing is input masking, which does not
affect what reaches the CRM.

It sits between Email and the message box, and is **optional**. Making a phone
number mandatory costs completed enquiries, and the old Elementor form did not
require one either. Say so if you would rather it were required.

**The mapping is now:**

| WPForms field | → | Zoho Bigin (Contacts) |
|---|---|---|
| `0:last` — Last Name | → | `Last_Name` *(mandatory)* |
| `0:first` — First Name | → | `First_Name` |
| `1` — Email | → | `Email` |
| `3` — Phone | → | `Mobile` |
| `2` — Comment or Message | → | `Description` |

Verified against the plugin's own field list: every mapped field exists.

### Staging and production share one Zoho connection — read before testing

Staging flow 1 and production flow 1 carry the **identical refresh token**
(fingerprint `5590989126da`) and the same client ID. This is not a copy of the
connection; it is the same connection.

**Consequence: a test submitted on staging creates a real contact in the live
Zoho Bigin.** Nothing is sandboxed.

- Use an obviously fake name — "Test Bigin", not a plausible person — so sales
  can spot and delete it
- Tell whoever watches Bigin that a test record is coming
- Delete it afterwards

This is not a fault to fix. It is how the connection was set up, and it is
actually convenient: proving it on staging proves the production credentials
too. It just must not be a surprise.

### Three fields no longer reach the CRM

The WPForms form collects less than the Elementor form did. Sales should know:

| Zoho field | Was fed by | Now |
|---|---|---|
| ~~`Mobile`~~ | Elementor Phone field | **Restored 8 Sep** — Phone field added and mapped |
| `Source_URL` | Elementor hidden "Page Url" field | **Nothing** — hidden fields are a WPForms Pro feature |
| `Account_Name`, `Title` | HR guide form (Company, Designation) | **Nothing** — that offer is retired |

Losing `Source_URL` matters little while the contact form lives on one page. If
it is ever wanted back, it needs either WPForms Pro or a small hidden input
added by the theme.

## The 9 September test failed — two separate faults, both now fixed

Two test enquiries were submitted on staging. Neither the email nor the Zoho
record arrived. The logs identified two unrelated causes.

### Fault 1 — the email: an expired Microsoft token

WP Mail SMTP's own log shows both submissions **did** fire, at 05:49:14 and
05:52:31, correctly addressed to `info@synergibpo.com` with the new subject. They
failed on send with:

> `InvalidAuthenticationToken: Lifetime validation failed, the token is expired.`

Every message staging has attempted since at least 1 September failed the same
way — weekly summaries, password-reset mails, everything. So this was not caused
by the notification change; staging's mail had been dead for over a week and
nobody noticed because nothing was watching it.

**Fixed.** The stored refresh token was still valid, so a refresh against
`login.microsoftonline.com` returned a new access token (HTTP 200), now saved.
Verified against Microsoft Graph: HTTP 200, mailbox `info@synergibpo.com`,
matching the configured From address exactly.

### Fault 2 — Zoho: a cache that hid the new trigger

Bit Integrations' log had **no entry at all** for either submission — the flow
never ran. The cause was a transient, `bit_integrations_active_trigger_entities`,
holding the list of trigger types the plugin bothers to load hooks for.

It contained `["Elementor"]`.

Because the flow was repointed **directly in the database** rather than through
the plugin's own UI, nothing invalidated that cache. The plugin went on loading
only the Elementor hook, so `wpforms_process_complete` was never even listened
for.

**Fixed.** The transient was deleted and rebuilt through the plugin's own
`StoreInCache::getActiveFlowEntities(true)`. It now reads `["WPF"]`.

**Lesson worth keeping:** editing this plugin's flow table directly works, but
its caches must be cleared afterwards. Anyone repeating this on production must
clear the same three transients (`..._active_trigger_entities`,
`..._fallback_trigger_entities`, `..._action_hook_flows`) or the flow will look
correct in the database and silently never fire.

## P1a — CLOSED: production email is healthy

Production sends as `info@synergi.ae` while authenticating as
`info@synergibpo.com`, which looked like the same mismatch that was breaking
staging. **It is not a fault.** Production's own mail log settles it:

| Last 60 days | |
|---|---|
| Sent successfully | **31** |
| Failed | **0** |

Contact-form notifications are arriving at `info@synergibpo.com` — the most
recent on 27 August, matching that day's form submission and its Bigin contact.
So `info@synergi.ae` **is** a valid send-as alias on that Microsoft account, and
production email needs no change.

This also rules production email out as an explanation for P4's drop in
submissions. Notifications have been going out fine; the enquiries themselves
are what fell away.

**One thing to carry into the launch:** production's mail works because its
Microsoft token is being kept fresh. Staging's died silently and stayed dead for
over a week. After launch, the same failure would be invisible — nothing watches
it. Worth an occasional glance at WP Mail SMTP → Email Log, or its weekly
summary going to someone who reads it.

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

# Audit results — 8 September

## Redirects: clean

All 40 rules checked for chains, loops, dead targets, and origins still
published live enough to shadow their own rule.

**One real fault, and it was mine.** `our-services-2` pointed at
`procurement-readiness`, which I drafted earlier that day — so it had become a
two-hop chain into a drafted page. Repointed straight to
`our-services/procurement`.

Two others flagged by the first pass were **false positives**, checked and
cleared rather than "fixed":

- `category/synerginews → category/synergi-news` — the target is a *category
  term*, not a page, so the page lookup missed it. The term exists (#3, 6 posts).
- `ig → ?utm_source=instagram…` — a marketing short link to the homepage with
  campaign tags. Working as intended.

No chains, no loops, no dead targets remain.

## Dead ends: every historical URL checked

The brief was "no customer from the old site should reach a dead end". So every
URL that appears anywhere in the GSC exports or the GA4 landing-page export was
collected — **136 unique paths** — and each one checked against staging: does it
still exist, does a redirect catch it, or does it 404?

**Result: 26 already redirected, 44 still live, 7 archives, 7 system paths, and
52 dead ends.** Of those 52, twenty-two were real customer intent and now
redirect. The rest 404 correctly.

**Added 8 September — 22 rules** (61 total, still no chains or loops):

| Old URL | Now goes to | Why |
|---|---|---|
| `/contact-details/`, `/contacts/`, `/get-in-touch/`, `/reach-us/` | `/contact-us/` | ~18 GA sessions of people guessing at a contact URL |
| `/location/`, `/locations/` | `/global-locations/` | Same, for offices |
| `/services/`, `/what-we-do/` | `/our-services/` | People guessing at the services URL |
| `/our-services/digital-solutions/`, `/our-services/it-support/` | `/our-services/technology-ai/` | Old service names |
| `/our-services/finance-solutions/` | `/our-services/accounting/` | Old service name |
| `/our-services/human-capital-solutions/` | `/our-services/human-resources/` | Old service name |
| `/our-services/public-relations-services/` | `/our-services/marketing/` | PR now sits inside Marketing |
| `/our-impact/` + 7 children | the matching service page | An entire retired IA branch |
| `/our-approach/automotive-trading/` | `/about-us/` | Its seven siblings already went there |

**Deliberately left to 404** — these are not Synergi URLs and never were:

- The old theme's demo content: 13 `/portfolio/*` and `/portfolio-category/*`
  paths, plus `/demo/`, `/pricing/`, `/help/`, `/support/`, `/sales/`
- Broken machine paths: `/404-2/`, `/headers/`, `/s-dashborad/`,
  `/ot_mega_menu/*`, `/category/eihnews/`
- Old build drafts: `/synergi-homepage-2026-draft-build/`, `/synergi-uae-2/`
- `/ar/our-services/…` — Arabic, which should not be public until that phase
- Two news posts that are drafts on **both** sites, so they 404 today too

A 404 is the honest answer for all of these. Redirecting junk to the homepage
teaches Google the site is full of soft-404s, which is worse than the 404 itself.

## Structure: matches the plan, with one exception

52 published items on staging, checked against the agreed sitemap and the menu.
Everything is where it should be — six service pages, five solutions, three
market pages, the listings, the hub pages, the blog.

The menu is complete: 29 items across About, Services, Solutions, Markets,
Media and Contact.

**One page was not in the agreed structure: `/connect/`.** A link-in-bio page —
LinkedIn, Instagram, website — built entirely in Elementor, with 24 words of
actual post content. Its links live in the Elementor data, which will not
render, so it would launch as unstyled text with no working links: the same
fault as the HR guide page.

It was briefly drafted and redirected. **That was reverted the same day** — the
business confirmed `/connect/` is a deliberate link-tree for an event and **a
printed QR code points at it.** It is published again and carries no redirect.

- [ ] **Rebuild `/connect/` so it renders without Elementor.** It cannot ship as
      it stands: the QR code would lead to a broken page, which is worse than a
      404 because the visitor is standing in front of you at the time. It needs
      a small page template — brand header, the link buttons, nothing else.
      Roughly an hour of theme work, and it must be done before launch, not
      after.

## Two posts are too thin to index, and one of them still ranks

Not acted on — blog content is editorial, and these need a decision rather than
a switch:

| Post | Words | GSC |
|---|---|---|
| `beyond-payroll-how-modern-hr-services-drive-organizational-growth` | **92** | 1 click, 49 impressions |
| `how-smart-procurement-transforms-business-performance-in-the-uae` | **106** | 2 clicks, **475 impressions** |

Both are far below what Google will normally index, and are strong candidates
for the "Crawled – currently not indexed" group in P10.

- **`beyond-payroll`** earns almost nothing. Expand it or draft it.
- **`how-smart-procurement`** should **not** be drafted — 475 impressions on 106
  words means the topic has demand the page is too thin to convert. That is a
  rewrite, and one of the better content opportunities on the site.

## Minor, worth knowing

The **United Arab Emirates** menu item is a *custom link* (`/markets/united-arab-emirates/`)
while every sibling is a proper page link. It works, and the page is also linked
from three blog posts — but a custom link does not follow the page if its slug
ever changes. Worth converting to a page link when someone is next in the menu.

---

# Launch readiness sweep — 8 September

Seventeen pages plus a deliberate 404 were requested over HTTP from staging and
the responses inspected.

| Check | Result |
|---|---|
| HTTP status | **200 on all 17**; the 404 test correctly returns 404 with its own heading |
| PHP errors, warnings, notices, deprecations | **None on any page** |
| `<h1>` count | **Exactly one on every page**, as CLAUDE.md §8 requires |
| HTML weight | 34–110 KB |
| Broken image references in fields | **None** |
| Case studies missing a service term | **None** — all 12 assigned |
| Site records the theme reads | **All 9 present and populated** |
| Placeholder copy (TEST / Lorem / TODO) | **None.** The one apparent hit was the word "test" inside "Migrate and test" |

Every page also contains `staging.synergi.ae` strings. That is correct — it *is*
staging. It only becomes a fault if those strings survive the migration, which
is what the search-replace step in `migration-plan.md` exists to catch. Worth
re-checking after the push, not before.

## Cruft that should be cleaned, but blocks nothing

Eleven meta fields across four keys (`_syn_artifact_backup_2026_08_25`,
`_syn_content_backup_2026_08_28`, `_syn_content_backup_pre_template`,
`_syn_heading_backup_2026_08_25`) hold about 93 KB of pre-edit backups. **The
theme reads none of them.** Left in place deliberately — they are somebody's
safety net and they cause no harm.

One more worth naming, because it is a trap rather than clutter:
`_syn_content_before_posts_page` on `/blog/` holds 5,676 characters of
hand-pasted HTML — six frozen blog cards from late 2025, with hard-coded
`staging.synergi.ae` image sources and links. **The theme does not render it**,
so nothing breaks today. But if anyone ever wires that field up, or a
search-replace misses it, it would send visitors from production to staging. It
is orphaned legacy from the Elementor blog page and should simply be deleted.

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

---

# Test 3 — 9 September, 06:00

## Zoho: the flow works

Bit Integrations log entry 52, `06:00:51`:

```
triggered_entity: WPF      triggered_entity_id: 7560
0:first  test new 9:00
0:last   test new 9:00
1        omar.alakara@synergibpo.com
3        +961 71 384 052      <- the phone field mapped correctly
2        test new 9:00
```

The new trigger fired, every field mapped including the phone number, and Zoho
answered. **The integration is proven end to end.**

Zoho's answer was `DUPLICATE_DATA` — it declined to create the contact because
`omar.alakara@synergibpo.com` already exists in Bigin (record
`6777506000002222008`). That is Zoho working as designed, not a fault in the
connection.

### But it exposes a real behaviour worth knowing

`RecordApiHelper` only ever calls `insertRecord` — a plain POST to
`/bigin/v1/{module}`. **There is no upsert.** So when somebody already in Bigin
submits the form again, their enquiry never reaches the CRM.

This is **not new** — production log entry 45 shows the same rejection on
29 July. It has always behaved this way. But it means:

- The **email notification is the only backstop** for a returning enquirer,
  which is a second reason the mail fix matters
- Anyone measuring "leads in Bigin" is undercounting repeat enquiries

Also noted for later: the field map supports `formField: 'custom'` with a
`customValue`, so `Source_URL` could be set to a static string if it is ever
wanted back.

## Email: still failing, and it was my fault

Test 3 failed with a **different** error: `The selected mailer not found.`

Yesterday's token refresh wrote the access token as a plain string. WP Mail SMTP
stores it as a structured array — `new AccessToken( (array) $options['access_token'] )`
— so a string produced an array with no `access_token` key, the OAuth client
could not be built, and the mailer never existed.

**Fixed.** The token is written back in the correct shape:

```
access_token  => [ access_token, refresh_token, expires ]
```

with the `expires` timestamp the structure needs, and the two stray keys added
yesterday removed. Verified: `is_clients_saved` true, `is_auth_required` **false**,
the OAuth client builds, and `get_mailer` returns the Outlook mailer.

The missing `expires` value is also the likeliest explanation for the original
failure: without it the plugin never knew the token had expired, so it never
refreshed and kept presenting a stale one.

---

# Test 4 — 9 September, 06:11 — PASSED. P1 and P3 are closed.

Submitted with an email not already in Bigin. All three links in the chain
worked.

**The CRM record exists.** Read back from Bigin directly by ID:

| Field | Value |
|---|---|
| Record ID | `6777506000002480059` |
| First / Last name | omar test 4 |
| Email | omar.alakara3@gmail.com |
| **Mobile** | **+961 71 384 052** |
| Description | test personal email |
| Created | 2026-09-09 10:11:15 +04:00 |
| Owner | Ahmed Patel |

Bit Integrations log 53: `response_type: success`, `message: "record added"`.

**The email sent.** WP Mail SMTP log 197, 06:11:13, "New enquiry from
synergi.ae" to `info@synergibpo.com`, **status 1**, no error.

So the full chain now works: WPForms → `wpforms_process_complete` → Bit
Integrations `WPF` trigger → Zoho Bigin, and separately → Outlook → inbox. The
phone field carried through, which was the one piece of data the rebuild had
dropped.

- [x] **P1 — form notifications** — CLOSED
- [x] **P3 — CRM connection** — CLOSED on staging
- [ ] Repeat the same test on production immediately after launch

## Housekeeping

- **Delete the test contact** `6777506000002480059` from Bigin. Staging shares
  the live Zoho connection, so this is a real record in the production CRM.
- The 06:00 attempt created nothing — Zoho rejected it as a duplicate — so there
  is only one test record to remove.

## What has to be repeated on production at launch

Both fixes live in the **database**, not in the theme, so neither travels with a
Git deploy. After the content migration:

1. Confirm the flow row reads `triggered_entity = WPF`, `triggered_entity_id = 7560`
2. **Clear the three Bit Integrations transients** — `..._active_trigger_entities`,
   `..._fallback_trigger_entities`, `..._action_hook_flows`. Skip this and the
   flow will look perfect and silently never fire, exactly as it did here
3. Check the WPForms notification still reads `info@synergibpo.com`
4. Leave production's own `from_email` (`info@synergi.ae`) alone — it is a valid
   send-as alias there, proven by 31 successful sends
5. Submit one real enquiry and confirm it reaches Bigin, then delete it

## Where the record actually was — closed 9 September

The team reported the contact had not arrived. It had. Verified three ways
after the report:

- Fetched by ID `6777506000002480059` — **HTTP 200**, record present
- Searched Bigin by email — **1 record found**
- Token confirmed as acting for **Ahmed Patel** (`ahmed.patel@synergibpo.com`),
  who is also the record's owner

The record was never missing. It was being looked for in the wrong place, and
the reason is worth keeping.

**The integration writes to the `Contacts` module only.** The flow's `module` is
`Contacts` and `RecordApiHelper` posts to `/bigin/v1/Contacts`. It has never
created anything in **Pipelines** — which is Bigin's main working screen and
where a sales team spends its day. Contacts sits behind that, closer to an
address book.

So every website enquiry becomes a Contact, owned by Ahmed Patel, attached to no
pipeline and no deal. It is in the system but not in the workflow. That is not
something the rebuild changed — it is how the integration was set up originally.

**Where to look in Bigin:** `bigin.zoho.com` (not `crm.zoho.com`, a different
product) → **Contacts** (not Pipelines) → view set to **All Contacts** (not "My
Contacts", since the owner is Ahmed Patel) → search the email.

**Where to look in WordPress:** log in at `/s-dashboard/` (`/wp-admin` is hidden
by WPS Hide Login), then:

| What | Where |
|---|---|
| Did the CRM flow run, and what did Zoho reply? | `admin.php?page=bit-integrations` → the Zoho Bigin flow → Logs |
| Did the notification email send? | `admin.php?page=wp-mail-smtp-logs` |
| Stored form entries | Nowhere — WPForms Lite keeps none (P2) |

### Open question for the business, not a blocker

**Should a website enquiry create a deal in a pipeline, or only a contact?**

If it should create a deal, the flow needs pointing at the Pipelines module, or
a second action adding one — which needs a decision on which pipeline and which
stage new enquiries enter. Until then, web leads will keep arriving somewhere
the sales team does not habitually look.

**P1 and P3 are closed.** The remaining task is the production repeat at launch,
listed above.
