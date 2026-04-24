<?php
namespace OvuDay;
defined('ABSPATH') || exit;

/**
 * Per-post Schema.org output.
 *
 * Reads the schema type chosen in the SEO meta box and
 * generates the appropriate JSON-LD block in <head>.
 *
 * Supported types:
 *   article | blog_post | medical | how_to | faq |
 *   product | person | web_page
 */
class SEO_Schema {

    public function __construct() {
        add_action( 'wp_head', [ $this, 'output' ], 5 );
    }

    /* ── Router ───────────────────────────────────────────── */

    public function output(): void {
        if ( ! is_singular() ) return;

        $post_id  = get_the_ID();
        $type_raw = get_post_meta( $post_id, '_ovuday_schema_type', true ) ?: 'article';

        // Normalize PascalCase values from the meta box dropdown
        // to the snake_case values expected by this switch.
        $type_map = [
            'Article'        => 'article',
            'BlogPosting'    => 'blog_post',
            'NewsArticle'    => 'blog_post',
            'MedicalWebPage' => 'medical',
            'WebPage'        => 'web_page',
            'FAQPage'        => 'faq',
            'HowTo'          => 'how_to',
            'Product'        => 'product',
            'Person'         => 'person',
        ];
        $type = $type_map[ $type_raw ] ?? $type_raw;

        $schema = null;

        switch ( $type ) {
            case 'article':    $schema = $this->article( $post_id );    break;
            case 'blog_post':  $schema = $this->blog_post( $post_id );  break;
            case 'medical':    $schema = $this->medical( $post_id );    break;
            case 'how_to':     $schema = $this->how_to( $post_id );     break;
            case 'faq':        $schema = $this->faq( $post_id );        break;
            case 'product':    $schema = $this->product( $post_id );    break;
            case 'person':     $schema = $this->person( $post_id );     break;
            case 'web_page':   $schema = $this->web_page( $post_id );   break;
            case 'none':       return;
        }

        // Allow custom raw JSON-LD override
        $custom = get_post_meta( $post_id, '_ovuday_schema_custom', true );
        if ( $custom ) {
            $decoded = json_decode( $custom, true );
            if ( $decoded ) {
                $this->print_ld( $decoded );
                return;
            }
        }

        if ( $schema ) {
            $this->print_ld( $schema );
        }

        // Always output WebPage wrapper for singular content
        $this->print_ld( $this->web_page_wrapper( $post_id ) );
    }

    /* ── Helpers ──────────────────────────────────────────── */

    private function print_ld( array $schema ): void {
        echo '<script type="application/ld+json">'
            . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT )
            . "</script>\n";
    }

    private function get_image( int $post_id ): ?array {
        $thumb_id = get_post_thumbnail_id( $post_id );
        if ( ! $thumb_id ) return null;

        $img = wp_get_attachment_image_src( $thumb_id, 'full' );
        if ( ! $img ) return null;

        return [
            '@type'  => 'ImageObject',
            'url'    => $img[0],
            'width'  => $img[1],
            'height' => $img[2],
        ];
    }

    private function get_author( int $post_id ): array {
        $custom_author = get_post_meta( $post_id, '_ovuday_schema_author', true );
        $custom_url    = get_post_meta( $post_id, '_ovuday_schema_author_url', true );

        if ( $custom_author ) {
            $a = [ '@type' => 'Person', 'name' => $custom_author ];
            if ( $custom_url ) $a['url'] = $custom_url;
            return $a;
        }

        $author_id = get_post_field( 'post_author', $post_id );
        return [
            '@type' => 'Person',
            'name'  => get_the_author_meta( 'display_name', $author_id ),
            'url'   => get_author_posts_url( $author_id ),
        ];
    }

    private function get_publisher(): array {
        $g = get_option( 'ovuday_global_seo', [] );
        $pub = [
            '@type' => 'Organization',
            'name'  => $g['organization_name'] ?? get_bloginfo('name'),
            'url'   => home_url('/'),
        ];
        if ( ! empty( $g['organization_logo'] ) ) {
            $pub['logo'] = [
                '@type' => 'ImageObject',
                'url'   => $g['organization_logo'],
            ];
        }
        return $pub;
    }

    private function base_article( int $post_id, string $type ): array {
        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => $type,
            'headline'         => get_post_meta( $post_id, '_ovuday_seo_title', true ) ?: get_the_title( $post_id ),
            'description'      => get_post_meta( $post_id, '_ovuday_meta_description', true ) ?: wp_trim_words( get_the_excerpt( $post_id ), 30 ),
            'url'              => get_permalink( $post_id ),
            'datePublished'    => get_the_date( 'c', $post_id ),
            'dateModified'     => get_the_modified_date( 'c', $post_id ),
            'author'           => $this->get_author( $post_id ),
            'publisher'        => $this->get_publisher(),
            'mainEntityOfPage' => [ '@type' => 'WebPage', '@id' => get_permalink( $post_id ) ],
            'inLanguage'       => get_bloginfo('language'),
        ];

        $img = $this->get_image( $post_id );
        if ( $img ) $schema['image'] = $img;

        $cats = get_the_category( $post_id );
        if ( $cats ) {
            $schema['articleSection'] = array_map( fn($c) => $c->name, $cats );
        }

        $tags = get_the_tags( $post_id );
        if ( $tags ) {
            $schema['keywords'] = implode( ', ', array_map( fn($t) => $t->name, $tags ) );
        }

        // Reading time
        $rt = get_post_meta( $post_id, '_ovuday_reading_time', true );
        if ( ! $rt ) {
            $content = get_post_field( 'post_content', $post_id );
            $words   = str_word_count( strip_tags( $content ) );
            $rt      = max( 1, (int) round( $words / 200 ) );
        }
        $schema['timeRequired'] = 'PT' . $rt . 'M';

        return $schema;
    }

    /* ── Schema Types ─────────────────────────────────────── */

    private function article( int $post_id ): array {
        return $this->base_article( $post_id, 'Article' );
    }

    private function blog_post( int $post_id ): array {
        return $this->base_article( $post_id, 'BlogPosting' );
    }

    private function medical( int $post_id ): array {
        $schema = $this->base_article( $post_id, 'MedicalWebPage' );

        $schema['@type']       = ['Article', 'MedicalWebPage'];
        $schema['medicalAudience'] = [
            '@type' => 'MedicalAudience',
            'audienceType' => 'Patient',
        ];
        $schema['lastReviewed'] = get_the_modified_date('c', $post_id);

        $reviewed = get_post_meta( $post_id, '_ovuday_schema_reviewed_by', true );
        if ( $reviewed ) {
            $schema['reviewedBy'] = [
                '@type' => 'Person',
                'name'  => $reviewed,
            ];
        }

        $g = get_option('ovuday_global_seo', []);
        $schema['publisher'] = array_merge(
            $this->get_publisher(),
            ['@type' => ['Organization', 'MedicalOrganization']]
        );

        return $schema;
    }

    private function how_to( int $post_id ): array {
        $content = get_post_field( 'post_content', $post_id );

        // Extract H2/H3 headings as steps
        preg_match_all( '/<h[23][^>]*>(.*?)<\/h[23]>/i', $content, $m );
        $steps = [];
        foreach ( $m[1] as $i => $heading ) {
            $steps[] = [
                '@type' => 'HowToStep',
                'position' => $i + 1,
                'name'  => wp_strip_all_tags( $heading ),
                'url'   => get_permalink( $post_id ) . '#step-' . ( $i + 1 ),
            ];
        }

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'HowTo',
            'name'        => get_post_meta( $post_id, '_ovuday_seo_title', true ) ?: get_the_title( $post_id ),
            'description' => get_post_meta( $post_id, '_ovuday_meta_description', true ) ?: wp_trim_words( get_the_excerpt( $post_id ), 30 ),
            'url'         => get_permalink( $post_id ),
            'step'        => $steps ?: [['@type' => 'HowToStep', 'position' => 1, 'name' => 'Follow the instructions']],
        ];

        $img = $this->get_image( $post_id );
        if ( $img ) $schema['image'] = $img;

        return $schema;
    }

    private function faq( int $post_id ): array {
        $content = get_post_field( 'post_content', $post_id );

        // Try to extract Q&A from h2/h3 (question) + next p (answer) pattern
        $pairs = [];
        preg_match_all(
            '/<h[23][^>]*>(.*?)<\/h[23]>\s*<p>(.*?)<\/p>/is',
            $content,
            $m,
            PREG_SET_ORDER
        );
        foreach ( $m as $pair ) {
            $pairs[] = [
                '@type'          => 'Question',
                'name'           => wp_strip_all_tags( $pair[1] ),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => wp_strip_all_tags( $pair[2] ),
                ],
            ];
        }

        // Fallback: at least one entry
        if ( empty($pairs) ) {
            $pairs[] = [
                '@type' => 'Question',
                'name'  => get_the_title( $post_id ),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => wp_trim_words( get_the_excerpt( $post_id ), 50 ),
                ],
            ];
        }

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'name'       => get_the_title( $post_id ),
            'url'        => get_permalink( $post_id ),
            'mainEntity' => $pairs,
        ];
    }

    private function product( int $post_id ): array {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'SoftwareApplication',
            'name'        => get_post_meta( $post_id, '_ovuday_seo_title', true ) ?: get_the_title( $post_id ),
            'description' => get_post_meta( $post_id, '_ovuday_meta_description', true ) ?: wp_trim_words( get_the_excerpt( $post_id ), 30 ),
            'url'         => get_permalink( $post_id ),
            'applicationCategory' => 'HealthApplication',
            'operatingSystem'     => 'Web',
            'offers' => [
                '@type'        => 'Offer',
                'price'        => '0',
                'priceCurrency'=> 'USD',
                'availability' => 'https://schema.org/InStock',
            ],
            'author'    => $this->get_author( $post_id ),
            'publisher' => $this->get_publisher(),
        ];

        $img = $this->get_image( $post_id );
        if ( $img ) $schema['image'] = $img;

        return $schema;
    }

    private function person( int $post_id ): array {
        $author_id = get_post_field( 'post_author', $post_id );
        $schema = [
            '@context'   => 'https://schema.org',
            '@type'      => 'Person',
            'name'       => get_post_meta( $post_id, '_ovuday_schema_author', true ) ?: get_the_author_meta('display_name', $author_id),
            'url'        => get_post_meta( $post_id, '_ovuday_schema_author_url', true ) ?: get_author_posts_url( $author_id ),
            'description'=> wp_trim_words( get_the_excerpt( $post_id ), 40 ),
        ];

        $img = $this->get_image( $post_id );
        if ( $img ) $schema['image'] = $img['url'];

        return $schema;
    }

    private function web_page( int $post_id ): array {
        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'WebPage',
            'name'             => get_post_meta( $post_id, '_ovuday_seo_title', true ) ?: get_the_title( $post_id ),
            'description'      => get_post_meta( $post_id, '_ovuday_meta_description', true ) ?: wp_trim_words( get_the_excerpt( $post_id ), 30 ),
            'url'              => get_permalink( $post_id ),
            'datePublished'    => get_the_date( 'c', $post_id ),
            'dateModified'     => get_the_modified_date( 'c', $post_id ),
            'author'           => $this->get_author( $post_id ),
            'publisher'        => $this->get_publisher(),
            'inLanguage'       => get_bloginfo('language'),
            'isPartOf'         => [ '@id' => home_url('/') ],
        ];

        $img = $this->get_image( $post_id );
        if ( $img ) $schema['primaryImageOfPage'] = $img;

        return $schema;
    }

    /**
     * Lightweight WebPage schema always emitted (as secondary block).
     * This tells Google which page-type this is, separate from article/faq etc.
     */
    private function web_page_wrapper( int $post_id ): array {
        $g    = get_option('ovuday_global_seo', []);
        $type = get_post_meta( $post_id, '_ovuday_schema_type', true ) ?: 'article';
        $wp_type = match($type) {
            'medical' => 'MedicalWebPage',
            'faq'     => 'FAQPage',
            'how_to'  => 'WebPage',
            default   => 'WebPage',
        };

        return [
            '@context'    => 'https://schema.org',
            '@type'       => $wp_type,
            '@id'         => get_permalink( $post_id ) . '#webpage',
            'url'         => get_permalink( $post_id ),
            'name'        => get_post_meta( $post_id, '_ovuday_seo_title', true ) ?: get_the_title( $post_id ),
            'description' => get_post_meta( $post_id, '_ovuday_meta_description', true ) ?: wp_trim_words( get_the_excerpt( $post_id ), 30 ),
            'inLanguage'  => get_bloginfo('language'),
            'isPartOf'    => [ '@type' => 'WebSite', 'name' => $g['site_name'] ?? get_bloginfo('name'), 'url' => home_url('/') ],
            'breadcrumb'  => [ '@id' => get_permalink( $post_id ) . '#breadcrumb' ],
            'datePublished'  => get_the_date('c', $post_id),
            'dateModified'   => get_the_modified_date('c', $post_id),
        ];
    }
}
