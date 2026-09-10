# synergi.ae — go-live

**For the person launching:** after the 15:00 content sign-off, open Claude Code
in this folder and say:

> Read GO-LIVE.md and run it.

Claude runs the checks and the dry run on its own, then **stops and asks for
"go"** before anything changes on the live site. It stops once more before the
CRM. Plan about 45 minutes end to end.

---

## For Claude: how to run this

### Rules — none of these bend

1. **Two gates.** Stop after the dry run (Gate 1) and wait for the user to say
   "go". Stop again before the CRM (Gate 2). No production write happens before
   its gate, except what Phase A lists.
2. **Servers.** Staging is `novamira-staging-synergi`, production is
   `novamira-synergi-ae`. Confirm which is which in A1 before anything else.
   Never modify, deactivate or delete the Novamira plugin.
3. **Run the committed scripts verbatim.** Paste the file from `tools/` minus
   its opening `<?php` line, then add the call shown here. Never edit a script
   during the launch. If one fails, stop and report — do not improvise a fix
   on production.
4. **Read back every production write** and show the result.
5. **30-second limit** per production call. Nothing below needs more.
6. **The payload is public** from A4 until B3. Always delete it (B3), even if
   the launch stops or rolls back.
7. **Speak plainly.** The user wants to know what happened and what's next,
   not the internals. Tables for results; one line of meaning per finding.

### Read first

`CLAUDE.md` → `launch-day-state.md` (newest; wins every disagreement) →
`launch-runbook.html` → `tools/transfer-export.php`,
`tools/transfer-import.php`, `tools/launch-after-activation.php`,
`tools/launch-verify.sh`.

### Decisions already taken — do not reopen

- `/connect/` stays **published** and **noindexed** (done; a printed QR code).
- The **Elementor stack and the old theme's plugins are deactivated** at
  go-live, never deleted: the list is in `tools/launch-after-activation.php`.
- Homepage title stays `BPO Services in UAE & the Gulf | Synergi Business Solutions`.
- The seven repaired blog posts are carried; ICXI stays a page.
- Every email address is `info@synergibpo.com`; admin is
  `omar.alakara@synergibpo.com`.
- Case studies are hidden from search for now.
- All staging redirects go live, and **every live page whose own URL is a
  redirect is drafted** behind its 301 (8 pages) — as on staging.
- CRM is rewired after the launch, at Gate 2.

---

## Phase A — pre-flight (no "go" needed)

Read-only on production. The only write is the staging export in A4.

**A1. Identity.** One read-only call to each server:

| | Must be |
|---|---|
| production `home_url()` | `https://synergi.ae` |
| production active theme | `theratio`; `synergi` installed, version `0.3.0` |
| production `blog_public` | `1` |
| production `page_on_front` | `320` |
| staging `home_url()` | `https://staging.synergi.ae`, active theme `synergi` |

**A2. Production is as we left it** (read-only): `admin_email` is
`omar.alakara@synergibpo.com`; WP Mail SMTP `from_email` is
`info@synergibpo.com`; `/connect/` (10406) is `publish` with
`_yoast_wpseo_meta-robots-noindex = 1`.

**A3. Staging homepage title** (read-only): `homepage-rebuild` (10547)
`_yoast_wpseo_title` is exactly the production title above.

If anything in A1–A3 differs, **stop and report** — something changed since
10 Sep.

**A4. Export (staging).** Paste all of `tools/transfer-export.php` — it ends in
the export call.

| Key | Must read |
|---|---|
| `ok` | `true` |
| `staging_urls_leaked` | `1` |
| `noindex_suppressed` | `media`, `homepage-rebuild` |
| `content_carried` | exactly the 7 repaired posts, nothing else |
| `form_notifications` | `7560: info@synergibpo.com` |
| `posts_page` / `custom_logo` | `blog` / `10506` |

**A5. Dry run (production).** Paste `tools/transfer-import.php`, then:

```php
return syn_transfer_import( 'https://staging.synergi.ae/wp-content/uploads/syn-transfer/payload.json', true );
```

| Look for | Expect |
|---|---|
| `attachment_summary.missing` | `0` — if not, list the files and include `syn_transfer_import_media()` in the Gate 1 ask |
| `CONFLICTS` | absent |
| `posts` | 20 × WOULD UPDATE (procurement → 9374, blog → 136; ICXI `(id 7892, stays a page)`), the rest WOULD CREATE |
| `content_written` | the 7 repaired posts only |
| `forms` | `7560: WOULD REPLACE` |
| `redirects` | `served_after` 62 |
| `retired` | exactly 8 × WOULD DRAFT: `/our-approach/`, `/our-leadership/`, `/shared-services-uae/`, `/bpo-services-in-saudi-arabia-ksa-riyadh/`, `/synergi-uae-2-2/` (320), `/full-episode…/` (9146), `/hr-digital-transformation-guide/`, `/procurement-readiness/` |
| `posts_page` | `0` → `136` |
| `theme_mods` | logo `10506`, menu `20` |

(`front_page_is_the_rebuild` and `posts_page_is_blog` read FAIL in a dry run.
That is expected: the live run sets them.)

### ⛔ Gate 1

Show the user a short table: each A-check, expected vs actual. Then ask:
**"Everything matches. Say go to launch."** Wait.

---

## Phase B — the cutover (after "go"), in this order, no pauses

**B1. Import and switch the theme — one call (production).** Paste
`tools/transfer-import.php`, then:

```php
$r = syn_transfer_import( 'https://staging.synergi.ae/wp-content/uploads/syn-transfer/payload.json', false );
if ( empty( $r['error'] ) ) {
	switch_theme( 'synergi' );
	$r['theme_now'] = get_stylesheet();
}
return $r;
```

One call removes the gap where the new homepage shows through the old theme.
If `$r['error']` is set, the importer refused **before writing anything** and
the theme was not switched: stop, report, and run B3.

Expect: `retired` lists the 8 pages as `drafted`; `ASSERTIONS` all PASS;
`SAFE_TO_ANNOUNCE` true; `theme_now` `synergi`.

**B2. After-activation (production).** Paste
`tools/launch-after-activation.php`, then run it twice:

```php
return syn_launch_after_activation( true );   // show it
return syn_launch_after_activation( false );  // then do it
```

This deactivates the Elementor stack and the old theme's plugins (list saved in
`syn_launch_deactivated_plugins`), hides case studies from search, clears the
Yoast sitemap cache and purges LiteSpeed. Every `checks` value must be `true`
and `SAFE_TO_ANNOUNCE` must be `true`.

**B3. Delete the payload (staging).** Paste only `syn_transfer_cleanup()` from
`tools/transfer-export.php`, then `return syn_transfer_cleanup();`. Expect
`still_present => false`, and the public URL answering 404.

**B4. Verify from outside (local).**

```bash
bash tools/launch-verify.sh
```

It checks all 48 URLs that were live before launch (each a 200, or one 301 to
a 200), all 62 redirect origins, the homepage title, robots meta, canonical,
one `<h1>`, the analytics tag `G-F8BHKGB935`, no `staging.synergi.ae` in any
page, the golden pages, the contact form's Phone field, `/connect/` still up
and noindexed, `robots.txt`, `sitemap_index.xml` and `page-sitemap.xml` (new
pages in; `/connect/` and drafted pages out; no case-study sitemap).

Expected result: **0 failures**, except one known line —
`page-not-found/ -> 301 …/404-2/ -> 404` (a production redirect that already
pointed at an unpublished page before launch). Anything else: report it plainly
and propose the fix; do not change production without a "go".

**B5. Report to the user**, in a table: what went live, the check results,
and this list for them to look at in a browser (desktop and phone): the
homepage, one service page, `/blog/`, one repaired post, `/contact-us/` (the
form), `/connect/`, `/terms/`, `/privacy-policy/`, the mobile menu.

### ⛔ Gate 2 — CRM

Show runbook steps 21–23 (backup + repoint flow 1 with staging's field map +
flow 2 off; clear the three transients; one real test enquiry) and ask:
**"Say go to rewire the CRM."** After "go", run 21 and 22 as two calls, read
back the flow rows and the transients, then ask the user to send one real
enquiry and confirm it reached Bigin (Contacts → All Contacts) and
`info@synergibpo.com`.

---

## Phase C — after launch (people, not Claude)

- **Search Console:** submit `https://synergi.ae/sitemap_index.xml`; request
  indexing for the homepage and the six service pages.
- **Site Kit:** Omar signs in with Google in wp-admin → Site Kit; Search
  Console `https://synergi.ae/`; Analytics `G-F8BHKGB935` with Site Kit's
  "place the Analytics code" **unticked** (ASE snippet 8607 already sends it).
- **Keep running:** ASE (snippet 8607 is the analytics), Yoast, WPForms,
  WP Mail SMTP, Bit Integrations, Instagram Feed, Site Kit, LiteSpeed,
  Wordfence.
- **After one clean week:** delete the deactivated plugins and the Theratio
  theme; not before (CLAUDE.md §2.9).
- **Week 1:** rebuild `/connect/` on a theme template; security headers in
  LiteSpeed or `.htaccess`; delete `readme.html` and `license.txt`; move
  Novamira off production.

---

## If it has to be rolled back

Quick rollback — one call on production, then purge LiteSpeed:

```php
switch_theme( 'theratio' );
activate_plugins( (array) get_option( 'syn_launch_deactivated_plugins', array() ) );
update_option( 'page_on_front', 320 );
update_option( 'page_for_posts', 0 );
foreach ( array( 320, 2302, 6304, 8877, 8991, 9067, 9115, 9146 ) as $id ) {
	wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
}
$form = get_option( 'syn_wpforms_7560_backup_launch' );
if ( $form ) {
	wp_update_post( wp_slash( array( 'ID' => 7560, 'post_content' => $form ) ) );
}
return get_stylesheet();
```

The merged redirects stay; they point at the new pages, which still exist. If
the CRM was already rewired, restore `syn_btcbi_flow_backup_launch` and clear
the three transients again. A full restore from the 10 Sep 05:55 backup is the
last resort: it predates the images, the theme install, the email change and
the admin changes made that day.
