<?php
namespace OvuDay;

defined( 'ABSPATH' ) || exit;

/**
 * Fallback REST API endpoints for Next.js if WPGraphQL is unavailable.
 * Endpoint: /wp-json/ovuday/v1/posts
 * Endpoint: /wp-json/ovuday/v1/posts/{slug}
 */
class REST_API {

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        $namespace = 'ovuday/v1';

        register_rest_route( $namespace, '/posts', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_posts' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'per_page' => [
                    'default'           => 12,
                    'sanitize_callback' => 'absint',
                ],
                'page'     => [
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );

        register_rest_route( $namespace, '/posts/(?P<slug>[a-z0-9-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_post' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'slug' => [
                    'sanitize_callback' => 'sanitize_title',
                ],
            ],
        ] );

        register_rest_route( $namespace, '/settings', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_settings' ],
            'permission_callback' => '__return_true',
        ] );
    }

    /* ── Posts ──────────────────────────────────────────── */

    public function get_posts( \WP_REST_Request $request ): \WP_REST_Response {
        $args  = [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $request['per_page'],
            'paged'          => $request['page'],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $query = new \WP_Query( $args );
        $posts = array_map( [ $this, 'format_post' ], $query->posts );

        return new \WP_REST_Response( [
            'posts'     => $posts,
            'total'     => (int) $query->found_posts,
            'totalPages' => (int) $query->max_num_pages,
        ], 200 );
    }

    public function get_post( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        $post = get_page_by_path( $request['slug'], OBJECT, 'post' );
        if ( ! $post || $post->post_status !== 'publish' ) {
            return new \WP_Error( 'not_found', 'Post not found', [ 'status' => 404 ] );
        }
        return new \WP_REST_Response( $this->format_post( $post, true ), 200 );
    }

    private function format_post( \WP_Post $post, bool $full = false ): array {
        $thumbnail_id  = get_post_thumbnail_id( $post->ID );
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : null;
        $category      = get_the_category( $post->ID )[0] ?? null;

        $data = [
            'id'            => $post->ID,
            'slug'          => $post->post_name,
            'title'         => get_the_title( $post->ID ),
            'excerpt'       => get_the_excerpt( $post ),
            'date'          => $post->post_date_gmt,
            'modified'      => $post->post_modified_gmt,
            'featuredImage' => $thumbnail_url,
            'category'      => $category ? [ 'name' => $category->name, 'slug' => $category->slug ] : null,
            'author'        => get_the_author_meta( 'display_name', $post->post_author ),
            'seo'           => [
                'title'       => get_post_meta( $post->ID, '_ovuday_seo_title',        true ) ?: get_the_title( $post->ID ),
                'metaDesc'    => get_post_meta( $post->ID, '_ovuday_meta_description',  true ),
                'canonical'   => get_post_meta( $post->ID, '_ovuday_canonical',         true ) ?: get_permalink( $post->ID ),
                'focusKw'     => get_post_meta( $post->ID, '_ovuday_focus_keyword',     true ),
                'ogTitle'     => get_post_meta( $post->ID, '_ovuday_og_title',          true ),
                'ogDesc'      => get_post_meta( $post->ID, '_ovuday_og_description',    true ),
                'ogImage'     => get_post_meta( $post->ID, '_ovuday_og_image',          true ) ?: $thumbnail_url,
                'ogType'      => get_post_meta( $post->ID, '_ovuday_og_type',           true ) ?: 'article',
                'twitterCard' => get_post_meta( $post->ID, '_ovuday_tw_card',           true ) ?: 'summary_large_image',
                'schemaType'  => get_post_meta( $post->ID, '_ovuday_schema_type',       true ) ?: 'article',
                'noindex'     => get_post_meta( $post->ID, '_ovuday_robots_noindex',    true ) === '1',
                'nofollow'    => get_post_meta( $post->ID, '_ovuday_robots_nofollow',   true ) === '1',
                'readingTime' => (int) get_post_meta( $post->ID, '_ovuday_reading_time', true ),
                'seoScore'    => (int) get_post_meta( $post->ID, '_ovuday_seo_score',    true ),
            ],
        ];

        if ( $full ) {
            $data['content'] = apply_filters( 'the_content', $post->post_content );
        }

        return $data;
    }

    /* ── Settings ───────────────────────────────────────── */

    public function get_settings(): \WP_REST_Response {
        $g = get_option( 'ovuday_global_seo', [] );
        return new \WP_REST_Response( [
            'siteName'         => $g['site_name']          ?? get_bloginfo('name'),
            'siteUrl'          => home_url('/'),
            'homeTitle'        => $g['home_title']         ?? '',
            'homeDescription'  => $g['home_description']   ?? '',
            'homeOgImage'      => $g['home_og_image']       ?? '',
            'titleSeparator'   => $g['title_separator']    ?? '|',
            'organizationName' => $g['organization_name']  ?? get_bloginfo('name'),
            'organizationLogo' => $g['organization_logo']  ?? '',
            'twitterUsername'  => $g['twitter_username']   ?? '',
            'facebookUrl'      => $g['facebook_url']       ?? '',
            'googleAnalytics'  => $g['google_analytics']   ?? '',
            'googleTagManager' => $g['google_tag_manager'] ?? '',
            'sitemapUrl'       => home_url('/sitemap.xml'),
        ], 200 );
    }
}
