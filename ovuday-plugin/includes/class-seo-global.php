<?php
namespace OvuDay;
defined('ABSPATH') || exit;

/**
 * Outputs global SEO tags: title tag, home meta, site-wide robots,
 * verification codes, GA, and archive/date/author noindex.
 */
class SEO_Global {

    public function __construct() {
        add_filter( 'pre_get_document_title', [ $this, 'filter_title' ], 20 );
        add_action( 'wp_head',               [ $this, 'output_global_head' ], 2 );
        add_action( 'wp_head',               [ $this, 'output_analytics' ], 99 );
    }

    /* ── Title Tag ────────────────────────────────────────── */

    public function filter_title( string $title ): string {
        $g   = get_option( 'ovuday_global_seo', [] );
        $sep  = $g['title_separator'] ?? '|';
        $site = $g['site_name'] ?? get_bloginfo('name');

        if ( is_singular() ) {
            $post_id   = get_the_ID();
            $custom    = get_post_meta( $post_id, '_ovuday_seo_title', true );
            $base      = $custom ?: get_the_title( $post_id );
            return $base . " {$sep} " . $site;
        }

        if ( is_home() || is_front_page() ) {
            $home_title = $g['home_title'] ?? '';
            return $home_title ? $home_title . " {$sep} " . $site : $site;
        }

        return $title ?: $site;
    }

    /* ── Global <head> ────────────────────────────────────── */

    public function output_global_head(): void {
        $g = get_option( 'ovuday_global_seo', [] );

        // Home page meta
        if ( is_home() || is_front_page() ) {
            if ( ! empty( $g['home_description'] ) ) {
                printf( '<meta name="description" content="%s" />' . "\n", esc_attr($g['home_description']) );
            }
            if ( ! empty( $g['home_og_image'] ) ) {
                printf( '<meta property="og:image" content="%s" />' . "\n", esc_url($g['home_og_image']) );
            }
            $site = $g['site_name'] ?? get_bloginfo('name');
            printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr($g['home_title'] ?? $site) );
            printf( '<meta property="og:type" content="website" />' . "\n" );
            printf( '<meta property="og:url" content="%s" />' . "\n", esc_url(home_url('/')) );
            printf( '<link rel="canonical" href="%s" />' . "\n", esc_url(home_url('/')) );
        }

        // Archive noindex
        if ( is_date() && ($g['noindex_date'] ?? '0') === '1' ) {
            echo '<meta name="robots" content="noindex, follow" />' . "\n";
        }
        if ( is_author() && ($g['noindex_author'] ?? '0') === '1' ) {
            echo '<meta name="robots" content="noindex, follow" />' . "\n";
        }
        if ( is_search() && ($g['noindex_search'] ?? '1') === '1' ) {
            echo '<meta name="robots" content="noindex, follow" />' . "\n";
        }
        if ( is_404() && ($g['noindex_404'] ?? '1') === '1' ) {
            echo '<meta name="robots" content="noindex, follow" />' . "\n";
        }

        // Google Search Console verification
        if ( ! empty( $g['google_site_verify'] ) ) {
            printf( '<meta name="google-site-verification" content="%s" />' . "\n", esc_attr($g['google_site_verify']) );
        }
        // Bing verification
        if ( ! empty( $g['bing_site_verify'] ) ) {
            printf( '<meta name="msvalidate.01" content="%s" />' . "\n", esc_attr($g['bing_site_verify']) );
        }

        // Twitter site-wide
        if ( ! empty( $g['twitter_username'] ) && ! is_singular() ) {
            printf( '<meta name="twitter:card" content="summary_large_image" />' . "\n" );
            printf( '<meta name="twitter:site" content="@%s" />' . "\n", esc_attr(ltrim($g['twitter_username'],'@')) );
        }

        // Organization schema (sitewide)
        $this->output_organization_schema( $g );
    }

    private function output_organization_schema( array $g ): void {
        if ( ! is_front_page() && ! is_home() ) return;

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => $g['organization_name'] ?? get_bloginfo('name'),
            'url'      => home_url('/'),
        ];
        if ( ! empty( $g['organization_logo'] ) ) {
            $schema['logo'] = [ '@type' => 'ImageObject', 'url' => $g['organization_logo'] ];
        }
        foreach ( ['twitter_username','facebook_url','instagram_url'] as $key ) {
            if ( ! empty( $g[$key] ) ) {
                $schema['sameAs'][] = $key === 'twitter_username'
                    ? 'https://twitter.com/' . ltrim($g[$key],'@')
                    : $g[$key];
            }
        }

        $website = [
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            'name'     => $g['site_name'] ?? get_bloginfo('name'),
            'url'      => home_url('/'),
        ];

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "</script>\n";
        echo '<script type="application/ld+json">' . wp_json_encode($website, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "</script>\n";
    }

    /* ── Analytics ────────────────────────────────────────── */

    public function output_analytics(): void {
        $g = get_option( 'ovuday_global_seo', [] );

        if ( ! empty( $g['google_tag_manager'] ) ) {
            $gtm = esc_js( $g['google_tag_manager'] );
            echo "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{$gtm}');</script>\n";
        } elseif ( ! empty( $g['google_analytics'] ) ) {
            $ga = esc_js( $g['google_analytics'] );
            echo "<script async src='https://www.googletagmanager.com/gtag/js?id={$ga}'></script>\n";
            echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{$ga}');</script>\n";
        }
    }
}
