<?php
namespace OvuDay;
defined('ABSPATH') || exit;

/**
 * Handles per-post redirects and the global redirect manager.
 */
class SEO_Redirects {

    public function __construct() {
        add_action( 'template_redirect', [ $this, 'do_redirect' ], 1 );
    }

    public function do_redirect(): void {
        if ( ! is_singular() ) return;
        $post_id = get_the_ID();
        $url     = get_post_meta( $post_id, '_ovuday_redirect_url', true );
        if ( empty($url) ) return;
        $type    = (int) ( get_post_meta($post_id,'_ovuday_redirect_type',true) ?: 301 );
        wp_redirect( esc_url_raw($url), $type );
        exit;
    }
}
