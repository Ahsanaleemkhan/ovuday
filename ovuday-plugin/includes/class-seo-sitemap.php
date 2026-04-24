<?php
namespace OvuDay;
defined('ABSPATH') || exit;

/**
 * XML Sitemap generator. Registers /sitemap.xml and /sitemap-posts.xml etc.
 */
class SEO_Sitemap {

    public function __construct() {
        $g = get_option('ovuday_global_seo', []);
        if ( ($g['sitemap_enable'] ?? '1') !== '1' ) return;

        add_action( 'init',                  [ $this, 'add_rewrite_rules' ] );
        add_filter( 'query_vars',            [ $this, 'add_query_vars' ] );
        add_action( 'template_redirect',     [ $this, 'serve_sitemap' ] );
        add_action( 'save_post',             [ $this, 'clear_cache' ] );
        add_action( 'wp_head',               [ $this, 'add_sitemap_link_tag' ] );
        add_action( 'robots_txt',            [ $this, 'add_to_robots' ], 10, 2 );
    }

    public function add_rewrite_rules(): void {
        add_rewrite_rule( '^sitemap\.xml$',       'index.php?ovuday_sitemap=index',    'top' );
        add_rewrite_rule( '^sitemap-posts\.xml$', 'index.php?ovuday_sitemap=posts',   'top' );
        add_rewrite_rule( '^sitemap-pages\.xml$', 'index.php?ovuday_sitemap=pages',   'top' );
        add_rewrite_rule( '^sitemap-cats\.xml$',  'index.php?ovuday_sitemap=cats',    'top' );
    }

    public function add_query_vars( array $vars ): array {
        $vars[] = 'ovuday_sitemap';
        return $vars;
    }

    public function add_sitemap_link_tag(): void {
        echo '<link rel="sitemap" type="application/xml" title="Sitemap" href="' . esc_url(home_url('/sitemap.xml')) . '" />' . "\n";
    }

    public function add_to_robots( string $output, bool $public ): string {
        if ( $public ) {
            $output .= "\nSitemap: " . home_url('/sitemap.xml') . "\n";
        }
        return $output;
    }

    public function clear_cache(): void {
        delete_transient('ovuday_sitemap_posts');
        delete_transient('ovuday_sitemap_pages');
        delete_transient('ovuday_sitemap_cats');
    }

    public function serve_sitemap(): void {
        $type = get_query_var('ovuday_sitemap');
        if ( ! $type ) return;

        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex');

        switch ( $type ) {
            case 'index': $this->sitemap_index(); break;
            case 'posts': $this->sitemap_posts(); break;
            case 'pages': $this->sitemap_pages(); break;
            case 'cats':  $this->sitemap_cats();  break;
        }
        exit;
    }

    private function xml_header(): void {
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?xml-stylesheet type="text/xsl" href="' . esc_url(OVUDAY_URL . 'admin/sitemap.xsl') . '"?>' . "\n";
    }

    private function sitemap_index(): void {
        $this->xml_header();
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $g = get_option('ovuday_global_seo', []);
        if (($g['sitemap_posts']??'1')==='1') echo $this->sitemap_entry(home_url('/sitemap-posts.xml'));
        if (($g['sitemap_pages']??'1')==='1') echo $this->sitemap_entry(home_url('/sitemap-pages.xml'));
        if (($g['sitemap_cats']??'1')==='1')  echo $this->sitemap_entry(home_url('/sitemap-cats.xml'));
        echo '</sitemapindex>';
    }

    private function sitemap_entry( string $loc ): string {
        return "<sitemap>\n<loc>" . esc_url($loc) . "</loc>\n<lastmod>" . date('c') . "</lastmod>\n</sitemap>\n";
    }

    private function sitemap_posts(): void {
        $posts = get_transient('ovuday_sitemap_posts');
        if ( ! $posts ) {
            $posts = get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'modified','order'=>'DESC']);
            set_transient('ovuday_sitemap_posts', $posts, HOUR_IN_SECONDS * 6);
        }
        $this->xml_header();
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ( $posts as $post ) {
            if ( get_post_meta($post->ID,'_ovuday_sitemap_exclude',true) === '1' ) continue;
            $priority = get_post_meta($post->ID,'_ovuday_sitemap_priority',true) ?: '0.7';
            $freq     = get_post_meta($post->ID,'_ovuday_sitemap_freq',true) ?: 'monthly';
            echo "<url>\n";
            echo "<loc>" . esc_url(get_permalink($post->ID)) . "</loc>\n";
            echo "<lastmod>" . mysql2date('c',$post->post_modified_gmt,false) . "</lastmod>\n";
            echo "<changefreq>{$freq}</changefreq>\n";
            echo "<priority>{$priority}</priority>\n";
            echo "</url>\n";
        }
        echo '</urlset>';
    }

    private function sitemap_pages(): void {
        $pages = get_transient('ovuday_sitemap_pages');
        if ( ! $pages ) {
            $pages = get_posts(['post_type'=>'page','post_status'=>'publish','numberposts'=>-1]);
            set_transient('ovuday_sitemap_pages', $pages, HOUR_IN_SECONDS * 6);
        }
        $this->xml_header();
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ( $pages as $page ) {
            if ( get_post_meta($page->ID,'_ovuday_sitemap_exclude',true) === '1' ) continue;
            echo "<url>\n";
            echo "<loc>" . esc_url(get_permalink($page->ID)) . "</loc>\n";
            echo "<lastmod>" . mysql2date('c',$page->post_modified_gmt,false) . "</lastmod>\n";
            echo "<changefreq>monthly</changefreq>\n";
            echo "<priority>0.6</priority>\n";
            echo "</url>\n";
        }
        echo '</urlset>';
    }

    private function sitemap_cats(): void {
        $this->xml_header();
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $cats = get_categories(['hide_empty'=>true]);
        foreach ( $cats as $cat ) {
            echo "<url>\n<loc>" . esc_url(get_category_link($cat->term_id)) . "</loc>\n<changefreq>weekly</changefreq>\n<priority>0.5</priority>\n</url>\n";
        }
        echo '</urlset>';
    }
}
