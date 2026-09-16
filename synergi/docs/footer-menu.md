# The footer menu

*Written for the person who maintains synergi.ae in wp-admin, and for whoever
rebuilds this site's menus on a fresh database.*

The four link columns in the site footer — Services, Solutions, Company,
Insights — are a WordPress menu. Changing them is an editor's job now, not a
developer's: **Appearance → Menus**, no deploy.

Until a menu is assigned, the footer renders a written-out copy of the list
below from `inc/footer-menu.php`, so it is never empty and never wrong. That
fallback is the safety net, not the source: once you assign a menu, the menu
wins completely.

---

## How the structure maps to the footer

| In Appearance → Menus | In the footer |
|---|---|
| A **top-level** item | A column heading |
| Its **children** (indented one level under it) | The links in that column |
| A **grandchild** (indented twice) | Nothing — ignored |

Two rules that surprise people:

1. **A top-level item is never a link.** It renders as the column's label, and
   whatever URL you give it is ignored. WordPress will not let you add a menu
   item without a URL, so use a Custom Link with `#` as the address and the
   column name as the label.
2. **The footer has room for exactly four columns.** A fifth would wrap under
   the logo and look broken, so the theme drops anything past the fourth. If
   you genuinely need five, that is a stylesheet change — talk to a developer.

## Building it

Appearance → Menus → **create a new menu**, name it `Footer links`, and tick
**Footer links** under *Display location*. Then add the items below, using
*Custom Links* for the four headings and *Pages* for everything underneath.

```
Services                      (Custom Link, URL: #)
    Human Resources           /our-services/human-resources/
    Technology & AI           /our-services/technology-ai/
    Accounting                /our-services/accounting/
    Marketing                 /our-services/marketing/
    Procurement               /our-services/procurement/
    Project Management        /our-services/project-management/

Solutions                     (Custom Link, URL: #)
    Shared Services           /our-solutions/shared-services/
    Build-Operate-Transfer    /our-solutions/build-operate-transfer/
    Systems Implementation    /our-solutions/systems-implementation/
    Carve-Out & Integration   /our-solutions/carve-out-integration/
    Fractional Leadership     /our-solutions/fractional-leadership/

Company                       (Custom Link, URL: #)
    About Us                  /about-us/
    Engagement Team           /engagement-team/
    Global Locations          /global-locations/
    Markets                   /markets/
    Careers                   /careers/
    Contact Us                /contact-us/

Insights                      (Custom Link, URL: #)
    Case Studies              /case-studies/
    Blog                      /blog/
    Executive Podcast         /executive-podcast/
    Media                     /media/
```

Add the children as **Pages**, not Custom Links, wherever a page exists. It
costs nothing extra and it is what makes the safety net in the next section
work — a Custom Link is just a typed string and the theme cannot check it.

## What happens when a page stops being published

If a page linked from the footer is moved back to **Draft**, **Pending** or
**Private**, the theme prints its label as plain grey text instead of a link.

This matters more than it sounds. The footer is on every URL on the site, so
one unpublished page would otherwise be a broken link on every page at once —
and you would not see it, because a logged-in editor can open a draft
perfectly well while a visitor gets a 404. Nothing needs doing when this
happens: publish the page again and the link comes back by itself.

Deleted and trashed pages drop out of the menu entirely; that is WordPress's
own behaviour, not the theme's.

## Arabic

Menus are per-language. The Arabic footer is a **separate menu** — build a
second one, name it `Footer links (AR)`, and assign it to the Footer links
location in the Arabic column of the languages table. Its items point at the
Arabic pages directly; there is no automatic mirroring of the English menu.

## For developers

`inc/footer-menu.php` owns all of this. `footer.php` only loops what
`syn_footer_columns()` returns and hands each column to
`parts/footer-links.php`. With `SYN_DEBUG` on, the footer's HTML source says
which source it used and names anything it dropped, so "why is that link
missing?" answers itself in view-source (CLAUDE.md §13).
