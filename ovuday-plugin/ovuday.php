<?php
/**
 * Plugin Name:       OvuDay SEO
 * Plugin URI:        https://ovuday.com
 * Description:       Complete SEO suite for OvuDay. Replaces Yoast/RankMath — full control over title, meta, OG, Twitter, schema, sitemap, redirects, breadcrumbs, robots, and content analysis. Exposes everything via WPGraphQL for the Next.js frontend.
 * Version:           2.0.0
 * Author:            OvuDay
 * Author URI:        https://ovuday.com
 * License:           GPL-2.0-or-later
 * Text Domain:       ovuday
 * Requires PHP:      8.0
 * Requires at least: 6.3
 */

defined( 'ABSPATH' ) || exit;

define( 'OVUDAY_VERSION', '2.0.0' );
define( 'OVUDAY_DIR',     plugin_dir_path( __FILE__ ) );
define( 'OVUDAY_URL',     plugin_dir_url( __FILE__ ) );
define( 'OVUDAY_FILE',    __FILE__ );

/* ─── Autoloader ─────────────────────────────────────────── */
spl_autoload_register( static function ( string $class ): void {
    if ( ! str_starts_with( $class, 'OvuDay\\' ) ) return;
    $rel  = str_replace( [ 'OvuDay\\', '_' ], [ '', '-' ], $class );
    $file = OVUDAY_DIR . 'includes/class-' . strtolower( $rel ) . '.php';
    if ( file_exists( $file ) ) require_once $file;
} );

/* ─── Bootstrap ──────────────────────────────────────────── */
add_action( 'plugins_loaded', static function (): void {
    new OvuDay\SEO_Meta();
    new OvuDay\SEO_Global();
    new OvuDay\SEO_Sitemap();
    new OvuDay\SEO_Schema();
    new OvuDay\SEO_Breadcrumbs();
    new OvuDay\SEO_Redirects();
    new OvuDay\REST_API();
    new OvuDay\Settings();
    new OvuDay\Content_Builder();
    new OvuDay\Blog_Manager();

    if ( class_exists( 'WPGraphQL' ) ) {
        new OvuDay\GraphQL_Extensions();
    }

    // Create default blog post + images (runs once, guarded by option)
    if ( is_admin() ) {
        OvuDay\Default_Blog::maybe_create();
    }
} );

/* ─── Activation ─────────────────────────────────────────── */
register_activation_hook( __FILE__, static function (): void {
    // Default global SEO options
    if ( ! get_option( 'ovuday_global_seo' ) ) {
        update_option( 'ovuday_global_seo', [
            'site_name'          => get_bloginfo( 'name' ),
            'title_separator'    => '|',
            'home_title'         => '',
            'home_description'   => '',
            'home_og_image'      => '',
            'twitter_username'   => '',
            'facebook_url'       => '',
            'instagram_url'      => '',
            'default_og_image'   => '',
            'organization_name'  => get_bloginfo( 'name' ),
            'organization_logo'  => '',
            'google_analytics'   => '',
            'google_tag_manager' => '',
            'google_site_verify' => '',
            'bing_site_verify'   => '',
            'robots_global'      => 'index, follow',
            'breadcrumbs_enable' => '1',
            'breadcrumbs_home'   => 'Home',
            'breadcrumbs_sep'    => '›',
            'sitemap_enable'     => '1',
            'sitemap_posts'      => '1',
            'sitemap_pages'      => '1',
            'sitemap_cats'       => '1',
            'allowed_origins'    => site_url(),
            'noindex_archives'   => '0',
            'noindex_date'       => '0',
            'noindex_author'     => '0',
            'noindex_search'     => '1',
            'noindex_404'        => '1',
        ] );
    }

    // Default Content Builder data — pre-fill every section
    if ( ! get_option( 'ovuday_site_content' ) ) {
        update_option( 'ovuday_site_content', \OvuDay\Content_Builder::defaults() );
    }

    // Redirect rules table
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    $table   = $wpdb->prefix . 'ovuday_redirects';
    $sql     = "CREATE TABLE IF NOT EXISTS {$table} (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        source_url   VARCHAR(512)    NOT NULL,
        target_url   VARCHAR(512)    NOT NULL,
        redirect_type SMALLINT       NOT NULL DEFAULT 301,
        hits         BIGINT UNSIGNED NOT NULL DEFAULT 0,
        created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY source_url (source_url(191))
    ) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Create default blog post with images
    \OvuDay\Default_Blog::maybe_create();

    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
