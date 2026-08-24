<?php
/**
 * Scoped performance tuning for the "Connect with Synergi" page (ID 10406) ONLY.
 * - Dequeues theme/plugin CSS+JS this standalone links page never uses.
 * - Removes the emoji detection script on this page.
 * - Preloads the Montserrat font and the logo image (LCP).
 * No effect on any other page. Disable via Novamira disable-file to revert.
 */
if (!defined('ABSPATH')) { exit; }

if (!function_exists('synergi_connect_perf_is_target')) {
  function synergi_connect_perf_is_target() {
    return !is_admin() && is_page(10406);
  }
}

add_action('wp', function () {
  if (!synergi_connect_perf_is_target()) { return; }
  remove_action('wp_head', 'print_emoji_detection_script', 7);
  remove_action('wp_print_styles', 'print_emoji_styles');
});

add_action('wp_enqueue_scripts', function () {
  if (!synergi_connect_perf_is_target()) { return; }
  $styles = [
    'sbi_styles', 'sbistyles',
    'sr7css',
    'wordfenceAJAXcss',
    'ep-styles', 'bdt-uikit', 'ep-helper',
    'theratio-fonts',
    'bootstrap', 'theratio-awesome-font', 'theratio-flaticon-font', 'lightgallery', 'theratio-style',
    'widget-icon-list', 'widget-social-icons', 'widget-spacer', 'elementor-icons',
    'elementor-post-9031',
    'e-theme-ui-light', 'elementor-common',
  ];
  foreach ($styles as $h) { wp_dequeue_style($h); }
  $scripts = [
    'theratio_scripts', 'mousewheel', 'lightgallery', 'isotope', 'easypiechart', 'countdown',
    'theratio-before-after', 'theratio-elementor', 'theratio-elementor-header', 'theratio-scripts',
    'tp-tools', 'sr7',
    'wfi18njs', 'wordfenceAJAXjs',
    'bdt-uikit', 'ep-scripts',
    'elementor-app-loader', 'elementor-common',
    'elementor-frontend',
    'jquery',
  ];
  foreach ($scripts as $h) { wp_dequeue_script($h); }
}, 9999);

// Element Pack re-enqueues after wp_enqueue_scripts; catch it at print time.
add_action('wp_print_styles', function () {
  if (!synergi_connect_perf_is_target()) { return; }
  foreach (['bdt-uikit', 'ep-helper', 'ep-styles', 'sbi_styles', 'sbistyles'] as $h) { wp_dequeue_style($h); }
}, 9999);
add_action('wp_print_scripts', function () {
  if (!synergi_connect_perf_is_target()) { return; }
  foreach (['bdt-uikit', 'ep-scripts', 'elementor-frontend', 'jquery'] as $h) { wp_dequeue_script($h); }
}, 9999);
add_action('wp_print_footer_scripts', function () {
  if (!synergi_connect_perf_is_target()) { return; }
  foreach (['bdt-uikit', 'ep-scripts', 'elementor-frontend'] as $h) { wp_dequeue_script($h); }
}, 1);

add_action('wp_head', function () {
  if (!synergi_connect_perf_is_target()) { return; }
  $up = 'https://synergi.ae/wp-content/uploads/2024/04/';
  echo '<link rel="preload" href="https://synergi.ae/wp-content/fonts/montserrat/JTUHjIg1_i6t8kCHKm4532VJOt5-QNFgpCtr6Hw5aXo.woff2" as="font" type="font/woff2" crossorigin>' . "\n";
  echo '<link rel="preload" as="image" imagesrcset="' . $up . 'ORIGINAL-LOGO-300x94.png 300w, ' . $up . 'ORIGINAL-LOGO-768x240.png 768w, ' . $up . 'ORIGINAL-LOGO-1024x321.png 1024w" imagesizes="(max-width: 480px) 185px, 225px" fetchpriority="high">' . "\n";
}, 2);
