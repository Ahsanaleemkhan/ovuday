<?php
namespace OvuDay;
defined('ABSPATH') || exit;

/**
 * Breadcrumb generator + BreadcrumbList schema.
 * Usage in theme: do_action('ovuday_breadcrumbs');
 */
class SEO_Breadcrumbs {

    public function __construct() {
        $g = get_option('ovuday_global_seo', []);
        if ( ($g['breadcrumbs_enable'] ?? '1') !== '1' ) return;
        add_action( 'ovuday_breadcrumbs', [ $this, 'render' ] );
        add_action( 'wp_head',           [ $this, 'schema' ] );
    }

    private function get_crumbs(): array {
        $g     = get_option('ovuday_global_seo', []);
        $home  = $g['breadcrumbs_home'] ?? 'Home';
        $crumbs = [[ 'label' => $home, 'url' => home_url('/') ]];

        if ( is_singular() ) {
            $post = get_post();
            $cats = get_the_category();
            if ( $cats ) {
                $crumbs[] = [ 'label' => $cats[0]->name, 'url' => get_category_link($cats[0]->term_id) ];
            }
            $breadcrumb_title = get_post_meta($post->ID, '_ovuday_breadcrumb_title', true) ?: get_the_title();
            $crumbs[] = [ 'label' => $breadcrumb_title, 'url' => get_permalink() ];

        } elseif ( is_category() ) {
            $crumbs[] = [ 'label' => single_cat_title('',false), 'url' => '' ];

        } elseif ( is_tag() ) {
            $crumbs[] = [ 'label' => single_tag_title('',false), 'url' => '' ];

        } elseif ( is_archive() ) {
            $crumbs[] = [ 'label' => get_the_archive_title(), 'url' => '' ];

        } elseif ( is_search() ) {
            $crumbs[] = [ 'label' => 'Search: ' . get_search_query(), 'url' => '' ];
        }

        return $crumbs;
    }

    public function render(): void {
        $g      = get_option('ovuday_global_seo', []);
        $sep    = $g['breadcrumbs_sep'] ?? '›';
        $crumbs = $this->get_crumbs();
        $last   = count($crumbs) - 1;

        echo '<nav aria-label="Breadcrumb" class="ovuday-breadcrumbs"><ol itemscope itemtype="https://schema.org/BreadcrumbList">';
        foreach ( $crumbs as $i => $crumb ) {
            $is_last = $i === $last;
            echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
            if ( ! $is_last && $crumb['url'] ) {
                echo '<a itemprop="item" href="' . esc_url($crumb['url']) . '"><span itemprop="name">' . esc_html($crumb['label']) . '</span></a>';
            } else {
                echo '<span itemprop="name" aria-current="page">' . esc_html($crumb['label']) . '</span>';
            }
            echo '<meta itemprop="position" content="' . ($i+1) . '" />';
            echo '</li>';
            if ( ! $is_last ) echo '<li aria-hidden="true" class="ovuday-bc-sep">' . esc_html($sep) . '</li>';
        }
        echo '</ol></nav>';
    }

    public function schema(): void {
        $crumbs = $this->get_crumbs();
        $items  = [];
        foreach ( $crumbs as $i => $crumb ) {
            $item = [ '@type' => 'ListItem', 'position' => $i+1, 'name' => $crumb['label'] ];
            if ( $crumb['url'] ) $item['item'] = $crumb['url'];
            $items[] = $item;
        }
        $schema = [ '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items ];
        if ( count($items) > 1 ) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "</script>\n";
        }
    }
}
