# Analytics setup — GA4, GTM, and conversion tracking

Companion to `problems-to-solve.md` (P5–P8) and `seo-action-list.md` Part 10 /
Phase 1. Those two documents describe the *state*. This one records the
*decisions* and is the runbook for carrying them out.

Audited and verified against both sites 9 September 2026 via Novamira.
Everything below marked **verified** was read from the live database, not
inferred.

---

> # ⚠ THIS SECTION IS REVERSED — read this box first (9 Sep, evening)
>
> Section 1 below names `G-EX4ZJYVVPG` as the survivor and `G-F8BHKGB935` as the
> one to retire. **On the evidence now available that is backwards, and acting on
> it would blind the business.**
>
> What changed: **nobody at Synergi can reach property `462310803` at all.**
> Property `499346155` sits in GA account `363654419` — a different account, not
> a second property inside `309167261`. Mario has left; Ahmed Patel has no access
> either. See §1a.
>
> And `499346155` **is collecting, from mid-2025**. Only one tag on the site
> started producing data then: **ASE snippet 8607, created 2 August 2025 and
> never edited since** (**verified** — `post_modified` equals `post_date`). Site
> Kit's property was created 9 October 2024, a year earlier, so it is not the
> source of a mid-2025 start.
>
> **CONFIRMED 9 Sep from the GA Admin screen: property `499346155`'s data stream
> measurement ID is `G-F8BHKGB935`.** So ASE snippet 8607 is the only tag feeding
> the only property anyone at Synergi can open. Retiring it — which §1, §6b and
> `migration-plan.md` all instruct — would end readable analytics for the
> business. Those instructions are void; this box supersedes them.
>
> Corollary: `adsLinked` is true on `462310803`, the **locked** property. The
> Google Ads link therefore points at data nobody can read, and rebuilding it
> against `499346155` is a marketing-side task that is not on this runbook yet.
>
> **The standing instruction is: change nothing.** Snippet 8607 stays
> on. Site Kit stays on. `syn_gtm_id` stays unset. The only ASE snippet to
> disable at launch is **10379** (the H3→H1 rewriter), which has nothing to do
> with analytics.
>
> Tracking survives the theme swap unaided: ASE injects on `wp_head`, the plugin
> is theme-independent, and the new theme calls `wp_head()` in `header.php`
> (**verified**). Option C never touches ASE settings.

## 1. Which GA4 property is authoritative — DECIDED

**`G-EX4ZJYVVPG`, property `462310803`, GA account `309167261`.**

The full Site Kit record on production settles it (**verified**):

| Field | Value | What it means |
|---|---|---|
| `propertyID` | `462310803` | The property Site Kit reports against |
| `measurementID` | `G-EX4ZJYVVPG` | The surviving ID — use this everywhere |
| `adsLinked` | **`true`** | **This property is linked to Google Ads** |
| `adsLinkedLastSyncedAt` | `1729074505` (16 Oct 2024) | The link is real, not a stale flag |
| `propertyCreateTime` | `1728469687000` (9 Oct 2024) | Predates all of our data |
| `googleTagID` | `GT-TXBFKV55` | Delivered via a Google tag container |
| `googleTagContainerDestinationIDs` | `["G-EX4ZJYVVPG"]` | **Only** this ID — see below |
| `adsConversionID` | `""` (empty) | No Ads conversion ID configured |
| `detectedEvents` | `[]` | Corroborates P8: no conversions, ever |

That is four independent reasons to keep it: it is Ads-linked, it is the
property the `datagsc/` landing-page export came from, it is the one Site Kit
has been feeding for eight months, and it is the one Search Console sits beside.

`G-F8BHKGB935` (the ASE snippet titled "Google", post 8607) is the one to
retire. It is a plain gtag snippet with no Ads link, no Site Kit relationship
and no export history. **Verified** it is *not* a destination of the
`GT-TXBFKV55` Google tag — the two GA4s are genuinely independent collectors,
not one container feeding two properties. It does **not** die on its own at launch — the assumption that it
would is wrong, and §6b below has the correction. The snippet has to be switched
off deliberately on production.

### 1a. Property `499346155` — do not adopt it

> **Update, 9 Sep (evening).** The access question below is now answered, and the
> answer is worse than assumed. **Property `499346155` lives in GA account
> `363654419`** — a *different account*, not a second property inside
> `309167261`. Confirmed from the GA Admin screen. Ahmed Patel
> (`ahmed.patel@synergi.ae`, WP user 6, an administrator since Sep 2025 and
> therefore predating mario) has no access either. **Nobody at Synergi can reach
> account `309167261` through the GA interface.** Mario has also left, so the
> first two of the three recovery routes below are both dead.
>
> One route survives, and it is the mailbox, not the GA permission. GA access was
> granted to a *Google account* built on an `@synergi.ae` address. Synergi still
> controls that mail domain — **verified**: WP Mail SMTP on production is set to
> the `outlook` mailer, sending from `info@synergi.ae`, so the domain is on
> Microsoft 365 and the mailbox is recoverable by IT, not by Google. Therefore:
> restore or alias `mario@synergi.ae` → accounts.google.com → Forgot password →
> the reset code lands in that mailbox → sign in → Admin → Account access
> management → add `omar.alakara@synergibpo.com` as Administrator.
>
> **The decision has a deadline, and it is the GTM container build, not launch.**
> Site Kit keeps `462310803` collecting through the theme swap regardless (§6a),
> so every day of delay banks data in case the mailbox comes back. Whichever
> property is reachable when the container is built is the one that goes in it.
> If the mailbox route has failed by then, `499346155` becomes the survivor and
> that is the written decision this section asked for — record the date here.
>
> Whatever happens, `datagsc/Landing_page_Landing_page.csv` (1 Jan – 7 Sep 2026,
> 126 landing pages) is a local, permanent answer to "where were customers
> going". The history is locked, not deleted.

This is a third property, and it is almost certainly not the answer.

The reasoning: property `462310803` was created in October 2024 and authorised
into Site Kit by **WordPress user ID 2, which no longer exists** (**verified** —
`get_user_by( 'id', 2 )` returns nothing; the deleted user is still the GA4
module's recorded owner). The Google account that granted that access left with
them.

So the likely story is not "there are three properties and one is mine" — it is
**"you cannot see the real property because the person who owned the access is
gone, and `499346155` is what your account happens to have."** That is an access
problem wearing a property problem's clothes.

Switching the site to `499346155` would throw away eight months of history, the
Google Ads link, and the continuity of the `datagsc/` baseline, in order to
solve a permissions issue.

**Do this instead.** Ask for your Google account to be granted **Administrator**
on GA account `309167261` / property `462310803`. The people who can grant it,
in order of likelihood:

- `mario@synergi.ae` — WordPress user 7, Site Kit's recorded site owner
- whoever holds the Google account of the departed user 2
- any existing Administrator on GA account `309167261` (visible under
  GA → Admin → Account access management to anyone who already has access)

**If that access genuinely cannot be recovered**, then and only then does
`499346155` become the survivor — and that is a different, larger decision (new
baseline from zero, Ads link rebuilt, `datagsc/` comparisons void). Record it
here before acting on it; do not drift into it.

Worth doing once you are in either way: open `499346155` → Admin → Data streams
→ the stream → Measurement ID, and write down what it is, so the property is
identified rather than mysterious.

---

## 2. The GTM container — build sheet

Option (a) from P6. Roughly an hour, once.

### 2a. Create it

1. tagmanager.google.com → Create Account → container type **Web**, for
   `synergi.ae`
2. Copy the `GTM-XXXXXXX` ID

> **Trap — do not connect GTM through Site Kit.** **Verified** on both
> production and staging: Site Kit's Tag Manager module has `useSnippet: true`
> with an empty `containerID`. It is inert *only* because the ID is blank. Put a
> container ID in there and Site Kit will emit `gtm.js` in `<head>` while the
> theme emits it in the footer — the container loads twice and **every tag in it
> fires twice**. Create the container at tagmanager.google.com and leave Site
> Kit's Tag Manager module alone.

### 2b. Tags to build

| # | Tag | Type | Trigger |
|---|---|---|---|
| 1 | GA4 — **`G-F8BHKGB935`** (property `499346155`) — *corrected 9 Sep; `G-EX4ZJYVVPG` is the locked property, see the box at the top of this file* | Google Tag | Initialization — All Pages |
| 2 | LinkedIn Insight — `9021449` | Custom HTML | All Pages |
| 3 | WPForms success listener | Custom HTML | All Pages |
| 4 | GA4 event — `generate_lead` | GA4 Event | Custom Event `wpforms_submit_success` |

**Tag 2 — LinkedIn**, recovered verbatim from production ASE snippet 9378
(**verified**). Drop the trailing `<noscript>` pixel the snippet carries: GTM
injects via script, so a noscript fallback inside a Custom HTML tag never does
anything, and the theme deliberately emits no noscript either
(`inc/integrations.php` header explains why).

```html
<script type="text/javascript">
_linkedin_partner_id = "9021449";
window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
window._linkedin_data_partner_ids.push(_linkedin_partner_id);
</script>
<script type="text/javascript">
(function(l) {
  if (!l){window.lintrk = function(a,b){window.lintrk.q.push([a,b])}; window.lintrk.q=[]}
  var s = document.getElementsByTagName("script")[0];
  var b = document.createElement("script");
  b.type = "text/javascript"; b.async = true;
  b.src = "https://snap.licdn.com/li.lms-analytics/insight.min.js";
  s.parentNode.insertBefore(b, s);
})(window.lintrk);
</script>
```

3. **Publish** the container. A saved-but-unpublished container does nothing.

---

## 3. `generate_lead` — the audit's instruction does not work as written

P8 and Phase 1.5 both say "add a trigger for the WPForms confirmation". There is
no such trigger in GTM, and the two obvious substitutes both fail here.

**Why. Verified** on staging: the site has exactly one WPForms form, **ID 7560
"Simple Contact Form"**, configured `ajax_submit: 1` with a confirmation of type
`message`.

- There is **no page load** on submit, so a thank-you-page URL trigger has
  nothing to fire on
- GTM's built-in **Form Submission** trigger listens for the native `submit`
  event, which WPForms cancels before it propagates — it will not fire

The working approach is to have WPForms' own success event push to the
dataLayer, and trigger GA4 off that.

**This lives in the GTM container, not in theme code.** `inc/integrations.php`
says in its header that it is the theme's only tag injection point and that
"nothing else belongs in this file", and CLAUDE.md §11 says future tags go
through the GTM container rather than into theme code. A form-success listener
is a tag concern. It also means marketing can adjust it without a deploy.

**Tag 3 — WPForms success listener.** Custom HTML, trigger All Pages:

```html
<script>
(function () {
  window.dataLayer = window.dataLayer || [];

  function push(formId, formName) {
    window.dataLayer.push({
      event: 'wpforms_submit_success',
      form_id: String(formId || ''),
      form_name: String(formName || '')
    });
  }

  // AJAX submissions (form 7560 is one). WPForms fires this jQuery event on the
  // form element; delegated binding on document catches every form on the page.
  if (window.jQuery) {
    jQuery(document).on('wpformsAjaxSubmitSuccess', 'form.wpforms-form', function () {
      push(jQuery(this).data('formid'), jQuery(this).attr('data-formid'));
    });
  }

  // Non-AJAX submissions render the confirmation on a fresh page load. Harmless
  // today; here so a future non-AJAX form is not silently untracked.
  document.addEventListener('DOMContentLoaded', function () {
    var c = document.querySelector('.wpforms-confirmation-container-full');
    if (c) { push(c.getAttribute('data-formid') || '', ''); }
  });
}());
</script>
```

> jQuery here is fine: CLAUDE.md §2.4 forbids jQuery in **theme code**, and a GTM
> container is not theme code. WPForms declares jQuery as a front-end dependency
> (the same fact §2.4 records), so it is present wherever a form is. If the form
> stack is ever consolidated off WPForms, this tag is one of the things that must
> be revisited — note it in that work, it will not announce itself.

**Tag 4 — GA4 event.** Type GA4 Event, event name `generate_lead`, configuration
tag = tag 1. Parameters `form_id` and `form_name` from Data Layer Variables of
the same names. Trigger: **Custom Event**, event name `wpforms_submit_success`.

**GA4 side.** Admin → Key events → **New key event** → name it `generate_lead`.
It can be created by name before it has ever fired — you do not have to wait for
the first submission to appear in the events list.

**Done when.** A test submission on staging appears in GA4 Realtime as
`generate_lead`.

---

## 4. Two things that will distort the before/after comparison

Neither is in the audit, and both bite after launch, when they are hard to undo.

### 4a. Internal traffic will start counting

**Verified**: production Site Kit has `trackingDisabled: ["loggedinUsers"]` — its
snippet has never fired for logged-in users. **The theme's GTM loader has no such
exclusion**, and it should not have one: excluding logged-in users in PHP would
also kill the LinkedIn tag and every future tag for anyone signed in, and it puts
measurement policy into theme code.

The right home is GA4. **Before launch**: GA4 → Admin → Data streams → the stream
→ Configure tag settings → **Define internal traffic** (add the office IP
ranges), then Admin → **Data filters** → Internal Traffic → set to **Active** (it
ships as "Testing", which does not actually filter).

Without this, staff browsing the new site becomes traffic, and "the new site gets
more sessions" stops meaning anything.

### 4b. The deferred loader will look like a traffic drop

The theme loads `gtm.js` on `requestIdleCallback` after `load`, or on first
scroll/pointer/key — deliberately, so the ~556 KB analytics payload never
competes with LCP (CLAUDE.md §6, §11). Site Kit's snippet loads in `<head>`.

So a visitor who leaves before the page goes idle **is counted by Site Kit today
and will not be counted by GTM after the switch.** Sessions will step down at
cutover by some small percentage that is measurement timing, not lost traffic.

This is the correct trade — those are the least engaged visitors, and the payload
budget is worth more — but it has to be recorded or someone will read it as a
launch regression. **Add a GA4 annotation on the cutover date** saying what
changed.

---

## 5. Sequencing — what order, and what is already proven

### 5a. Already proven (9 Sep, staging)

The theme side needs no work. **Verified** by setting `syn_gtm_id` on staging,
capturing `syn_render_gtm_loader()`'s output, and reverting to unset:

- `GTM-TEST123` → accepted; renders the full `syn-gtm-loader` script with
  `gtm.js?id=GTM-TEST123`
- `G-EX4ZJYVVPG` → rejected, as documented — the loader takes container IDs only
- `gtm-test123` → **rejected: the ID is case-sensitive.** Paste it uppercase

The option was left unset. Staging still emits nothing — confirmed by fetching
the staging homepage: no `licdn`, no gtag, no container. (The `linkedin` and
`googletagmanager` strings that do appear in the HTML are the footer social link,
the Yoast `sameAs` schema, and a leftover `dns-prefetch` hint. None of them is a
tag.)

**A caveat on verifying by view-source:** LiteSpeed has `optm-html_min = 1` on
staging, and HTML minification strips comments. The `syn-gtm-loader` script tag
itself survives, so step 4 below still works — but the `SYN_DEBUG` diagnostic
comments, and the `<!-- syn-section: … -->` traceability markers that CLAUDE.md
§13 relies on, are invisible on the cached front end. Worth knowing before
debugging anything by view-source, and worth fixing separately.

Because that lowercase rejection was silent, `inc/integrations.php` now prints
which of the two failures happened when `SYN_DEBUG` is on — `no GTM container
configured` versus `GTM container ID rejected, expected GTM-XXXXXXX, got …`
(CLAUDE.md §13: never fail silently). Nothing else in the file changed.

### 5b. The order to work in

1. **Get GA access sorted first** (§1a). Everything downstream needs the
   measurement ID from a property you can actually open
2. Build and **publish** the container (§2)
3. Set `syn_gtm_id` on staging — hand over the container ID and it goes in, or
   `define( 'SYN_GTM_ID', 'GTM-XXXXXXX' );` in staging's `wp-config.php`
4. View-source a staging page: `syn-gtm-loader` in the footer. If GTM **Preview**
   reports "not connected", **scroll or click once** — the loader is deliberately
   idle-deferred and Preview can reach its timeout first. Expected, not a fault
5. Confirm GA4 and LinkedIn both fire in Preview
6. Set the internal traffic filter to Active (§4a)
7. Test a form submission → `generate_lead` in GA4 Realtime (§3)

### 5c. The Site Kit handover, and the crack in the safety net

Leave Site Kit's `useSnippet` **on in production** until GTM is confirmed firing
*on production*, then turn it off. The overlap double-counts, so keep it short
and deliberate — hours, not weeks — and annotate both dates in GA4.

One caveat on calling Site Kit "the safety net": its GA4 module is owned by a
WordPress user that no longer exists (§1a). The snippet output itself is
option-driven and unaffected — it will keep running through launch, which is the
point. But **re-authorising the module, or changing its property, needs a Google
account with access to `462310803`** — the exact access that is currently
missing. So the safety net holds as long as nobody touches it. Resolve §1a rather
than relying on that.

---

## 6. Launch mechanics — verified 9 Sep, and it inverts two assumptions

The migration is **option C** (`migration-plan.md`): a scripted content transfer,
not a database push. Production keeps every one of its own options. Two
consequences follow, both the opposite of what Part 10 assumed.

### 6a. GA4 continuity is already safe — you will not lose tracking

Site Kit emits its GA4 snippet itself, out of
`googlesitekit_analytics-4_settings`, independently of the active theme. Option C
never touches that option. So `useSnippet: true` survives launch,
`G-EX4ZJYVVPG` keeps collecting straight through the theme swap, and **the
continuity risk is covered by doing nothing.**

That matters for prioritisation. GTM is not a rescue here, it is an upgrade:
what it buys is conversion tracking, the LinkedIn tag in one managed place, and
the deferred load. None of those is a reason to rush it badly, and none of them
is why tracking would stop — because it will not.

### 6b. The ASE snippets do NOT die at launch — they must be switched off

Part 10 says the three ASE snippets do not survive the new theme, "because the
ASE snippets feature is off on staging". Under option C, staging's ASE settings
never reach production at all. **Verified 9 Sep:** `enable_code_snippets_manager`
is `true` on production *and* on staging, and all three snippets are `publish` on
both.

So on production, after launch, all three keep running:

| Snippet | Effect under the new theme | Action |
|---|---|---|
| **"SEO: single post title H3 to H1"** (10379) | PHP on `plugins_loaded`, rewriting headings through an output buffer — against a theme that already emits one correct `<h1>` (CLAUDE.md §8). **This is the one that does damage** | Disable in wp-admin before or at launch |
| **"Google"** (8607) | The retired `G-F8BHKGB935` goes on collecting | Disable once GTM is verified |
| **"LinkedIn Tag"** (9378) | Keeps working — retargeting is *not* interrupted at launch | Disable only after the GTM LinkedIn tag is confirmed firing, or it double-fires |

Each needs the wp-admin screen: ASE ignores `post_status` and the `_active`
meta, so none of these can be toggled from the database.

### 6c. `syn_gtm_id` will not travel on its own

It is an option, and the migration script's carry list is content. Setting it on
staging does **not** put it on production. Either add it to the carry list — now
noted in `migration-plan.md` — or set it directly on production at launch.
