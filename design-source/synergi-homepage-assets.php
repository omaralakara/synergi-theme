<?php
/**
 * Plugin Name: Synergi Homepage Assets
 * Description: Loads the homepage design stylesheet/scripts and renders the homepage content
 *              directly from the original design's own HTML (templates/homepage-content.html)
 *              instead of rebuilding it as native Elementor widgets. Gated to the draft build
 *              page only. See templates/homepage-content.html to edit page copy/markup directly.
 * Version: 2.1.0
 */
defined('ABSPATH') || exit;

/** Draft build page. At go-live: replace the is_page() gate with is_front_page(). */
define('SYNERGI_DRAFT_PAGE_ID', 10479);

add_action('wp_enqueue_scripts', function () {
    if (!is_page(SYNERGI_DRAFT_PAGE_ID)) { return; } // remove/replace on go-live
    $base = plugin_dir_url(__FILE__);
    $dir  = plugin_dir_path(__FILE__);
    // Depend on Elementor's frontend CSS so the design bundle wins same-specificity cascades.
    $deps = wp_style_is('elementor-frontend', 'registered') ? ['elementor-frontend'] : [];
    wp_enqueue_style('synergi-homepage', $base . 'assets/css/main.min.css', $deps, filemtime($dir . 'assets/css/main.min.css'));
    wp_enqueue_script('synergi-homepage', $base . 'assets/js/main.js', [], filemtime($dir . 'assets/js/main.js'), true);
    wp_enqueue_script('synergi-why', $base . 'assets/js/why-section.js', [], filemtime($dir . 'assets/js/why-section.js'), true);

    /*
     * The Instagram section renders through the Instagram Feed plugin, whose
     * posts are painted by its own JavaScript. That plugin decides what to
     * enqueue by scanning the post's content for its shortcode - and this
     * page's content holds only [synergi_homepage_content], so its script can
     * be missed entirely and the feed would stay an empty box. Enqueue its
     * registered handles explicitly (no-ops if the plugin is inactive).
     */
    foreach (['sbi_scripts', 'sbi-scripts'] as $handle) {
        if (wp_script_is($handle, 'registered')) { wp_enqueue_script($handle); }
    }
    foreach (['sbi_styles', 'sbistyles'] as $handle) {
        if (wp_style_is($handle, 'registered')) { wp_enqueue_style($handle); }
    }

    /*
     * Inside the Elementor EDITOR preview only, neutralise two design behaviours that
     * make elements impossible to see or select while editing:
     *  1. `.js .reveal { opacity: 0 }` - main.js adds `.js` to <html>, and an
     *     IntersectionObserver adds `.is-visible` once at load. Elementor re-renders a
     *     widget's DOM whenever you edit it, so the fresh node never gets `.is-visible`
     *     and the content disappears.
     *  2. Decorative absolutely-positioned overlays (.hero-shade and friends) cover the
     *     whole section and swallow clicks, so widgets underneath cannot be selected.
     * The live front end is untouched by this block.
     */
    wp_add_inline_style('synergi-homepage', 'html{font-size:16px !important}');

    $is_editor_preview = class_exists('\Elementor\Plugin')
        && isset(\Elementor\Plugin::$instance->preview)
        && \Elementor\Plugin::$instance->preview->is_preview_mode();

    if ($is_editor_preview) {
        wp_add_inline_style('synergi-homepage',
            '.js .reveal{opacity:1 !important;transform:none !important}'
            . '.hero-shade,.hero-media,.hero-caret{pointer-events:none}'
        );
    }
}, 999);

/**
 * Renders the homepage content straight from the design's own markup file.
 *
 * Why: the design uses fluid rem-based CSS across ~40 tightly-coupled values per
 * section. Re-encoding that into individual Elementor widget settings (native
 * per-widget typography controls, etc.) proved fragile and slow to keep in sync
 * with the design across several correction passes. Serving the original,
 * already-correct HTML directly removes that translation step entirely:
 * pixel-identical by construction, no Elementor per-widget CSS overhead, and
 * future content edits happen by editing templates/homepage-content.html
 * directly rather than re-translating changes into Elementor controls.
 *
 * Used as a single Elementor "Shortcode" widget's value on the draft page,
 * inside the page's one top-level container (keeps the Theme Builder
 * header/footer wrapping intact via the elementor_header_footer template).
 */
add_shortcode('synergi_homepage_content', function () {
    if (!is_page(SYNERGI_DRAFT_PAGE_ID)) { return ''; }
    $path = plugin_dir_path(__FILE__) . 'templates/homepage-content.html';
    if (!is_readable($path)) { return ''; }
    $content = file_get_contents($path);
    if (!is_string($content)) { return ''; }
    // Strip the template's developer comments (section markers etc.) from the
    // served HTML so they never appear in the page source. The template file
    // keeps them for maintainability. Conditional comments are preserved
    // (none exist today, but the guard is free).
    $content = preg_replace('/<!--(?!\[if)(?!<!)[^\[>].*?-->/s', '', $content);
    if (!is_string($content)) { return ''; }
    // The blog section is the one dynamic part of the template: the token
    // below is replaced with the three newest published posts on every
    // render, so a new article automatically appears first.
    if (strpos($content, '{{SYNERGI_BLOG_POSTS}}') !== false) {
        $content = str_replace('{{SYNERGI_BLOG_POSTS}}', synergi_homepage_blog_cards(), $content);
    }
    // The Instagram section is the plugin's own feed. Nested shortcodes are
    // not expanded inside a shortcode's return value, so it is run here.
    if (strpos($content, '{{SYNERGI_INSTAGRAM_FEED}}') !== false) {
        $feed = shortcode_exists('instagram-feed')
            ? do_shortcode('[instagram-feed feed=1]')
            : '';
        $content = str_replace('{{SYNERGI_INSTAGRAM_FEED}}', $feed, $content);
    }
    return $content;
});

/**
 * Renders the five newest published posts as cards for the homepage blog
 * section. Newest first is get_posts()' default ordering (date DESC), so a
 * freshly published article automatically takes the first slot. All values
 * are escaped; the featured image comes from wp_get_attachment_image() so it
 * ships WordPress' own srcset/sizes/alt, lazy-loaded.
 */
function synergi_homepage_blog_cards() {
    $posts = get_posts(['numberposts' => 5, 'post_status' => 'publish']);
    if (!$posts) { return ''; }
    $cards = '';
    foreach ($posts as $p) {
        $url       = esc_url(get_permalink($p));
        $title     = esc_html(get_the_title($p));
        $date_attr = esc_attr(get_the_date('c', $p));
        $date_text = esc_html(get_the_date('j M Y', $p));
        $excerpt   = esc_html(wp_trim_words(get_the_excerpt($p), 22, '…'));
        $image     = get_post_thumbnail_id($p)
            ? wp_get_attachment_image(get_post_thumbnail_id($p), 'medium_large', false, ['decoding' => 'async'])
            : '';

        $cards .= '<li class="syg-blog-card">'
            . '<div class="syg-blog-card-media" aria-hidden="true">' . $image . '</div>'
            . '<div class="syg-blog-card-body">'
            . '<p class="syg-blog-card-meta">'
            . '<time datetime="' . $date_attr . '">' . $date_text . '</time>'
            . '</p>'
            . '<h3 class="syg-blog-card-title"><a href="' . $url . '">' . $title . '</a></h3>'
            . ($excerpt !== '' ? '<p class="syg-blog-card-excerpt">' . $excerpt . '</p>' : '')
            . '<span class="syg-blog-card-more" aria-hidden="true">Read more &rarr;</span>'
            . '</div>'
            . '</li>';
    }
    return $cards;
}

/**
 * Force a single Montserrat across the whole page, header and footer included.
 *
 * Elementor ships its own local Google-Fonts copy of Montserrat (90 @font-face
 * rules: static instances 100-900, plus italics) and enqueues it AFTER this
 * plugin stylesheet. Both declare the same family name, so the later
 * declaration wins for any weight it matches exactly. The page was therefore
 * rendering weights 400/700/800 from Elementor static files, and weights
 * 650/750/850 from the design variable font: two different Montserrat files on
 * one page, with slightly different metrics.
 *
 * Dequeuing the Elementor copy leaves the design single variable font
 * (font-weight: 100 900) to serve every weight in the header, the content and
 * the footer, and saves ten font downloads.
 *
 * Verified before enabling: this page renders zero italic text, so losing the
 * italic faces changes nothing, and the icon fonts (Flaticon, Font Awesome
 * Brands) are separate families and unaffected.
 *
 * Scoped to the draft page. Every other page on the site is untouched.
 */
add_action('wp_print_styles', function () {
    if (!is_page(SYNERGI_DRAFT_PAGE_ID)) { return; }
    wp_dequeue_style('elementor-gf-local-montserrat');

    /*
     * Also drop Josefin Sans (theratio-fonts, a render-blocking Google Fonts
     * request). Verified 18 Aug 2026: every selector Theratio styles with
     * Josefin Sans (blog widgets, breadcrumbs, portfolio meta, .ot-heading,
     * .ot-counter, team/projects grids) matches zero elements in this page's
     * rendered markup - header, content and footer included. Scoped to this
     * page; the rest of the site is untouched.
     */
    wp_dequeue_style('theratio-fonts');

    /*
     * Slider Revolution and Element Pack enqueue their assets site-wide, but
     * zero widgets on this page use them - verified 18 Aug 2026 against the
     * _elementor_data of page 10479 and Theme Builder posts 2724/2888/9031.
     * (Only live #320 uses slider_revolution + bdt-carousel.) Together they
     * are ~1.7 MB of dead CSS/JS on this page, and their global observers
     * compete with the carousel animations for main-thread time. Scoped to
     * this page; #320 and the rest of the site keep loading them normally.
     */
    foreach (['sr7css', 'bdt-uikit', 'ep-styles', 'ep-helper', 'context-menu'] as $handle) {
        wp_dequeue_style($handle);
    }

    // Telephone field styles - see the matching script dequeue below.
    foreach (['intlTelInput', 'elementor_tel'] as $handle) {
        wp_dequeue_style($handle);
    }
}, 0);

add_action('wp_print_scripts', function () {
    if (!is_page(SYNERGI_DRAFT_PAGE_ID)) { return; }
    // See the note on the matching style dequeues above.
    foreach (['sr7', 'tp-tools', 'ep-scripts', 'bdt-uikit'] as $handle) {
        wp_dequeue_script($handle);
    }

}, 0);

/*
 * Element Pack's context-menu stylesheet is enqueued during the page body,
 * after the head-time dequeues above have already run, so it needs a
 * footer-time dequeue (before late styles print at wp_footer priority 20).
 */
add_action('wp_footer', function () {
    if (!is_page(SYNERGI_DRAFT_PAGE_ID)) { return; }
    wp_dequeue_style('context-menu');

}, 1);

/*
 * The international telephone field (121 KB of JavaScript plus its stylesheets)
 * only initialises on an Elementor form's tel input, and this page has no form
 * at all - its calls to action are links. Elementor Pro registers custom form
 * field assets on every page and they are enqueued late enough to print in the
 * FOOTER, past every hook used above; `wp_print_footer_scripts` at priority 0
 * is the last point before core's `_wp_footer_scripts()` prints them. Both
 * halves go together: dropping the library while leaving its initialiser would
 * throw. If a form is ever added to this page, remove these handles first.
 */
add_action('wp_print_footer_scripts', function () {
    if (!is_page(SYNERGI_DRAFT_PAGE_ID)) { return; }
    foreach (['intlTelInput_elementor', 'elementor_tel'] as $handle) {
        wp_dequeue_script($handle);
    }
    foreach (['intlTelInput', 'elementor_tel'] as $handle) {
        wp_dequeue_style($handle);
    }
}, 0);

/**
 * Preload the two assets that gate first paint of the hero, on this page only:
 * the LCP hero image and the single variable Montserrat that renders all text.
 * Both URLs must stay query-string-free and byte-identical to how the CSS/HTML
 * reference them, or the browser double-downloads.
 */
add_action('wp_head', function () {
    if (!is_page(SYNERGI_DRAFT_PAGE_ID)) { return; }
    $base = plugin_dir_url(__FILE__);
    echo '<link rel="preload" href="https://staging.synergi.ae/wp-content/uploads/2026/08/hero-dubai-team.webp" as="image" type="image/webp" fetchpriority="high">' . "\n";
    echo '<link rel="preload" href="' . esc_url($base . 'assets/fonts/montserrat-latin.woff2') . '" as="font" type="font/woff2" crossorigin>' . "\n";
}, 2);
