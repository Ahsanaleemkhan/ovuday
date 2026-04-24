<?php
namespace OvuDay;

defined( 'ABSPATH' ) || exit;

/**
 * Per-post/page SEO meta box.
 * Controls: title, description, canonical, robots, OG, Twitter, schema type, focus keyword, redirects.
 */
class SEO_Meta {

    private const NONCE  = 'ovuday_seo_nonce';
    private const ACTION = 'ovuday_seo_save';

    public function __construct() {
        add_action( 'add_meta_boxes',         [ $this, 'add_meta_box' ] );
        add_action( 'save_post',              [ $this, 'save' ], 10, 2 );
        add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue_assets' ] );
        add_action( 'wp_head',               [ $this, 'output_head_tags' ], 1 );
    }

    /* ── Meta Box ─────────────────────────────────────────── */

    public function add_meta_box(): void {
        $screens = [ 'post', 'page' ];
        foreach ( $screens as $screen ) {
            add_meta_box(
                'ovuday_seo_panel',
                '🔍 OvuDay SEO',
                [ $this, 'render' ],
                $screen,
                'normal',
                'high'
            );
        }
    }

    public function enqueue_assets( string $hook ): void {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
        wp_enqueue_media();
        wp_add_inline_style( 'wp-admin', $this->admin_css() );
        wp_add_inline_script( 'jquery', $this->admin_js() );
    }

    /* ── Render ───────────────────────────────────────────── */

    public function render( \WP_Post $post ): void {
        wp_nonce_field( self::ACTION, self::NONCE );
        $m = $this->get_all_meta( $post->ID );
        $global = get_option( 'ovuday_global_seo', [] );
        $site   = $global['site_name'] ?? get_bloginfo( 'name' );
        $sep    = $global['title_separator'] ?? '|';
        ?>
        <div class="ovd-seo-wrap">

          <!-- TABS -->
          <div class="ovd-tabs">
            <button class="ovd-tab active" data-tab="general">General</button>
            <button class="ovd-tab" data-tab="social">Social / OG</button>
            <button class="ovd-tab" data-tab="schema">Schema</button>
            <button class="ovd-tab" data-tab="advanced">Advanced</button>
            <button class="ovd-tab" data-tab="analysis">Analysis</button>
          </div>

          <!-- ── TAB: GENERAL ──────────────────────────────── -->
          <div class="ovd-panel active" data-panel="general">

            <!-- Focus Keyword -->
            <div class="ovd-field">
              <label for="ovd_focus_keyword">Focus Keyword</label>
              <input type="text" id="ovd_focus_keyword" name="ovd_focus_keyword"
                     value="<?php echo esc_attr( $m['focus_keyword'] ); ?>"
                     placeholder="e.g. ovulation calculator" />
              <p class="ovd-hint">The main keyword you want this page to rank for.</p>
            </div>

            <!-- SEO Title -->
            <div class="ovd-field">
              <label for="ovd_seo_title">
                SEO Title
                <span class="ovd-badge">Recommended: 50–60 chars</span>
              </label>
              <input type="text" id="ovd_seo_title" name="ovd_seo_title"
                     value="<?php echo esc_attr( $m['seo_title'] ); ?>"
                     placeholder="Leave blank to use post title" maxlength="80" />
              <div class="ovd-counter-bar">
                <div class="ovd-counter-fill" id="ovd_title_fill"></div>
              </div>
              <p class="ovd-counter-text" id="ovd_title_count">0 characters</p>

              <!-- Live SERP Preview -->
              <div class="ovd-serp">
                <p class="ovd-serp-title" id="ovd_serp_title">
                  <?php echo esc_html( $m['seo_title'] ?: get_the_title( $post->ID ) ); ?> <?php echo esc_html( $sep ); ?> <?php echo esc_html( $site ); ?>
                </p>
                <p class="ovd-serp-url"><?php echo esc_url( get_permalink( $post->ID ) ); ?></p>
                <p class="ovd-serp-desc" id="ovd_serp_desc">
                  <?php echo esc_html( $m['meta_description'] ?: wp_trim_words( get_the_excerpt( $post ), 30 ) ); ?>
                </p>
              </div>
            </div>

            <!-- Meta Description -->
            <div class="ovd-field">
              <label for="ovd_meta_description">
                Meta Description
                <span class="ovd-badge">Recommended: 150–160 chars</span>
              </label>
              <textarea id="ovd_meta_description" name="ovd_meta_description"
                        rows="3" maxlength="200"
                        placeholder="Write a compelling description of this page..."><?php echo esc_textarea( $m['meta_description'] ); ?></textarea>
              <div class="ovd-counter-bar">
                <div class="ovd-counter-fill" id="ovd_desc_fill"></div>
              </div>
              <p class="ovd-counter-text" id="ovd_desc_count">0 characters</p>
            </div>

            <!-- Robots -->
            <div class="ovd-field">
              <label>Robots Meta</label>
              <div class="ovd-checkboxes">
                <?php $robots = $m['robots'] ?? []; ?>
                <label><input type="checkbox" name="ovd_robots[]" value="noindex"   <?php checked( in_array('noindex',   $robots, true) ); ?>> <strong>No Index</strong> — Hide from search engines</label>
                <label><input type="checkbox" name="ovd_robots[]" value="nofollow"  <?php checked( in_array('nofollow',  $robots, true) ); ?>> <strong>No Follow</strong> — Don't follow links</label>
                <label><input type="checkbox" name="ovd_robots[]" value="noarchive" <?php checked( in_array('noarchive', $robots, true) ); ?>> <strong>No Archive</strong> — Don't cache this page</label>
                <label><input type="checkbox" name="ovd_robots[]" value="nosnippet" <?php checked( in_array('nosnippet', $robots, true) ); ?>> <strong>No Snippet</strong> — Don't show description in results</label>
                <label><input type="checkbox" name="ovd_robots[]" value="noimageindex" <?php checked( in_array('noimageindex', $robots, true) ); ?>> <strong>No Image Index</strong> — Don't index images</label>
              </div>
            </div>

            <!-- Canonical -->
            <div class="ovd-field">
              <label for="ovd_canonical">Canonical URL</label>
              <input type="url" id="ovd_canonical" name="ovd_canonical"
                     value="<?php echo esc_attr( $m['canonical'] ); ?>"
                     placeholder="<?php echo esc_attr( get_permalink( $post->ID ) ); ?>" />
              <p class="ovd-hint">Leave blank to use the post's default URL. Set only if this content is duplicated elsewhere.</p>
            </div>

            <!-- Priority & Frequency (Sitemap) -->
            <div class="ovd-field ovd-row">
              <div>
                <label for="ovd_sitemap_priority">Sitemap Priority</label>
                <select id="ovd_sitemap_priority" name="ovd_sitemap_priority">
                  <?php foreach ( ['1.0','0.9','0.8','0.7','0.6','0.5','0.4','0.3','0.2','0.1'] as $p ) : ?>
                    <option value="<?php echo $p; ?>" <?php selected( $m['sitemap_priority'], $p ); ?>><?php echo $p; ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label for="ovd_sitemap_freq">Change Frequency</label>
                <select id="ovd_sitemap_freq" name="ovd_sitemap_freq">
                  <?php foreach ( ['always','hourly','daily','weekly','monthly','yearly','never'] as $f ) : ?>
                    <option value="<?php echo $f; ?>" <?php selected( $m['sitemap_freq'], $f ); ?>><?php echo ucfirst($f); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div>
                <label>
                  <input type="checkbox" name="ovd_sitemap_exclude" value="1" <?php checked( $m['sitemap_exclude'] ); ?>>
                  Exclude from Sitemap
                </label>
              </div>
            </div>

          </div><!-- /general -->

          <!-- ── TAB: SOCIAL ───────────────────────────────── -->
          <div class="ovd-panel" data-panel="social">

            <h4 class="ovd-section-title">Open Graph (Facebook, LinkedIn, WhatsApp)</h4>

            <div class="ovd-field">
              <label for="ovd_og_title">OG Title</label>
              <input type="text" id="ovd_og_title" name="ovd_og_title"
                     value="<?php echo esc_attr( $m['og_title'] ); ?>"
                     placeholder="Defaults to SEO title" maxlength="95" />
            </div>

            <div class="ovd-field">
              <label for="ovd_og_description">OG Description</label>
              <textarea id="ovd_og_description" name="ovd_og_description" rows="2"
                        placeholder="Defaults to meta description"><?php echo esc_textarea( $m['og_description'] ); ?></textarea>
            </div>

            <div class="ovd-field">
              <label for="ovd_og_image">OG Image (1200×630 px recommended)</label>
              <div class="ovd-media-row">
                <input type="url" id="ovd_og_image" name="ovd_og_image"
                       value="<?php echo esc_attr( $m['og_image'] ); ?>"
                       placeholder="https://..." />
                <button type="button" class="button ovd-media-btn" data-target="ovd_og_image">Choose Image</button>
              </div>
              <?php if ( $m['og_image'] ) : ?>
                <img src="<?php echo esc_url( $m['og_image'] ); ?>" class="ovd-img-preview" alt="OG preview" />
              <?php endif; ?>
            </div>

            <div class="ovd-field">
              <label for="ovd_og_type">OG Type</label>
              <select id="ovd_og_type" name="ovd_og_type">
                <?php foreach ( ['article','website','product','book','profile'] as $t ) : ?>
                  <option value="<?php echo $t; ?>" <?php selected( $m['og_type'] ?: 'article', $t ); ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <h4 class="ovd-section-title" style="margin-top:24px">Twitter / X Card</h4>

            <div class="ovd-field">
              <label for="ovd_tw_card">Card Type</label>
              <select id="ovd_tw_card" name="ovd_tw_card">
                <option value="summary_large_image" <?php selected( $m['tw_card'], 'summary_large_image' ); ?>>Summary Large Image</option>
                <option value="summary" <?php selected( $m['tw_card'], 'summary' ); ?>>Summary</option>
              </select>
            </div>

            <div class="ovd-field">
              <label for="ovd_tw_title">Twitter Title</label>
              <input type="text" id="ovd_tw_title" name="ovd_tw_title"
                     value="<?php echo esc_attr( $m['tw_title'] ); ?>"
                     placeholder="Defaults to OG/SEO title" maxlength="70" />
            </div>

            <div class="ovd-field">
              <label for="ovd_tw_description">Twitter Description</label>
              <textarea id="ovd_tw_description" name="ovd_tw_description" rows="2"
                        placeholder="Defaults to meta description"><?php echo esc_textarea( $m['tw_description'] ); ?></textarea>
            </div>

            <div class="ovd-field">
              <label for="ovd_tw_image">Twitter Image URL</label>
              <div class="ovd-media-row">
                <input type="url" id="ovd_tw_image" name="ovd_tw_image"
                       value="<?php echo esc_attr( $m['tw_image'] ); ?>"
                       placeholder="Defaults to OG image" />
                <button type="button" class="button ovd-media-btn" data-target="ovd_tw_image">Choose Image</button>
              </div>
            </div>

          </div><!-- /social -->

          <!-- ── TAB: SCHEMA ───────────────────────────────── -->
          <div class="ovd-panel" data-panel="schema">

            <div class="ovd-field">
              <label for="ovd_schema_type">Schema Type</label>
              <select id="ovd_schema_type" name="ovd_schema_type">
                <?php
                $schema_types = [
                  'Article'        => 'Article (default for blog posts)',
                  'BlogPosting'    => 'BlogPosting',
                  'NewsArticle'    => 'NewsArticle',
                  'MedicalWebPage' => 'MedicalWebPage (recommended for health content)',
                  'WebPage'        => 'WebPage',
                  'FAQPage'        => 'FAQPage',
                  'HowTo'          => 'HowTo',
                  'Product'        => 'Product',
                  'none'           => 'None (no auto schema)',
                ];
                foreach ( $schema_types as $val => $label ) : ?>
                  <option value="<?php echo esc_attr($val); ?>" <?php selected( $m['schema_type'] ?: 'Article', $val ); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Article specific -->
            <div class="ovd-field">
              <label for="ovd_schema_author_name">Author Name (Schema)</label>
              <input type="text" id="ovd_schema_author_name" name="ovd_schema_author_name"
                     value="<?php echo esc_attr( $m['schema_author_name'] ); ?>"
                     placeholder="Defaults to post author" />
            </div>

            <div class="ovd-field">
              <label for="ovd_schema_author_url">Author Profile URL</label>
              <input type="url" id="ovd_schema_author_url" name="ovd_schema_author_url"
                     value="<?php echo esc_attr( $m['schema_author_url'] ); ?>"
                     placeholder="https://..." />
            </div>

            <div class="ovd-field">
              <label for="ovd_schema_reviewed_by">Medically Reviewed By (name)</label>
              <input type="text" id="ovd_schema_reviewed_by" name="ovd_schema_reviewed_by"
                     value="<?php echo esc_attr( $m['schema_reviewed_by'] ); ?>"
                     placeholder="Dr. Jane Smith" />
              <p class="ovd-hint">For health content — adds reviewer to MedicalWebPage schema (E-E-A-T signal).</p>
            </div>

            <div class="ovd-field">
              <label for="ovd_schema_custom">Custom JSON-LD (advanced)</label>
              <textarea id="ovd_schema_custom" name="ovd_schema_custom" rows="8"
                        style="font-family:monospace"
                        placeholder='{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[]}'><?php echo esc_textarea( $m['schema_custom'] ); ?></textarea>
              <p class="ovd-hint">Paste raw JSON-LD here. Output as-is inside &lt;script type="application/ld+json"&gt;. Validates at <a href="https://search.google.com/test/rich-results" target="_blank">Rich Results Test</a>.</p>
            </div>

          </div><!-- /schema -->

          <!-- ── TAB: ADVANCED ─────────────────────────────── -->
          <div class="ovd-panel" data-panel="advanced">

            <div class="ovd-field">
              <label for="ovd_redirect_url">301 Redirect this URL to</label>
              <input type="url" id="ovd_redirect_url" name="ovd_redirect_url"
                     value="<?php echo esc_attr( $m['redirect_url'] ); ?>"
                     placeholder="https://ovuday.com/new-url" />
              <p class="ovd-hint">If set, visitors and crawlers are redirected here. Use 301 for permanent moves.</p>
            </div>

            <div class="ovd-field">
              <label for="ovd_redirect_type">Redirect Type</label>
              <select id="ovd_redirect_type" name="ovd_redirect_type">
                <option value="301" <?php selected($m['redirect_type'],'301'); ?>>301 — Permanent</option>
                <option value="302" <?php selected($m['redirect_type'],'302'); ?>>302 — Temporary</option>
                <option value="307" <?php selected($m['redirect_type'],'307'); ?>>307 — Temporary (preserve method)</option>
              </select>
            </div>

            <div class="ovd-field">
              <label for="ovd_breadcrumb_title">Breadcrumb Title</label>
              <input type="text" id="ovd_breadcrumb_title" name="ovd_breadcrumb_title"
                     value="<?php echo esc_attr( $m['breadcrumb_title'] ); ?>"
                     placeholder="Defaults to post title (can be shorter)" />
            </div>

            <div class="ovd-field">
              <label for="ovd_reading_time_override">Reading Time Override (minutes)</label>
              <input type="number" id="ovd_reading_time_override" name="ovd_reading_time_override"
                     value="<?php echo esc_attr( $m['reading_time_override'] ); ?>"
                     min="1" max="120" placeholder="Auto-calculated" />
            </div>

            <div class="ovd-field ovd-checkboxes">
              <label>
                <input type="checkbox" name="ovd_exclude_from_search" value="1"
                       <?php checked( $m['exclude_from_search'] ); ?>>
                Exclude from WordPress search results
              </label>
              <label>
                <input type="checkbox" name="ovd_hide_from_rss" value="1"
                       <?php checked( $m['hide_from_rss'] ); ?>>
                Hide from RSS feed
              </label>
            </div>

          </div><!-- /advanced -->

          <!-- ── TAB: ANALYSIS ─────────────────────────────── -->
          <div class="ovd-panel" data-panel="analysis">
            <div id="ovd-analysis-output">
              <p class="ovd-hint">Content analysis runs automatically based on your Focus Keyword and post content.</p>
              <?php $this->render_analysis( $post ); ?>
            </div>
          </div>

        </div><!-- /ovd-seo-wrap -->
        <?php
    }

    /* ── Analysis ─────────────────────────────────────────── */

    private function render_analysis( \WP_Post $post ): void {
        $content      = wp_strip_all_tags( apply_filters( 'the_content', $post->post_content ) );
        $word_count   = str_word_count( $content );
        $reading_time = max( 1, (int) ceil( $word_count / 200 ) );
        $keyword      = get_post_meta( $post->ID, '_ovuday_focus_keyword', true );
        $title        = get_post_meta( $post->ID, '_ovuday_seo_title', true ) ?: get_the_title( $post->ID );
        $desc         = get_post_meta( $post->ID, '_ovuday_meta_description', true );
        $title_len    = mb_strlen( $title );
        $desc_len     = mb_strlen( $desc );

        $checks = [];

        // Word count
        $checks[] = [ $word_count >= 800, "Word count: <strong>{$word_count}</strong>" . ( $word_count >= 800 ? ' — Good (800+ words)' : ' — Aim for 800+ words for better ranking' ) ];

        // Title length
        $checks[] = [ $title_len >= 50 && $title_len <= 60, "SEO title length: <strong>{$title_len}</strong> chars" . ( $title_len >= 50 && $title_len <= 60 ? ' — Perfect (50–60)' : ' — Aim for 50–60 characters' ) ];

        // Desc length
        $checks[] = [ $desc_len >= 150 && $desc_len <= 160, "Meta description length: <strong>{$desc_len}</strong> chars" . ( $desc_len >= 150 && $desc_len <= 160 ? ' — Perfect (150–160)' : ' — Aim for 150–160 characters' ) ];

        // Keyword checks
        if ( $keyword ) {
            $kw_lower    = strtolower( $keyword );
            $in_title    = str_contains( strtolower( $title ), $kw_lower );
            $in_desc     = str_contains( strtolower( $desc ), $kw_lower );
            $in_content  = str_contains( strtolower( $content ), $kw_lower );
            $density     = $word_count > 0 ? round( (substr_count( strtolower( $content ), $kw_lower ) / $word_count) * 100, 2 ) : 0;
            $in_h1       = str_contains( strtolower( get_the_title( $post->ID ) ), $kw_lower );

            $checks[] = [ $in_title,   "Focus keyword in SEO title" ];
            $checks[] = [ $in_h1,      "Focus keyword in post title (H1)" ];
            $checks[] = [ $in_desc,    "Focus keyword in meta description" ];
            $checks[] = [ $in_content, "Focus keyword in content body" ];
            $checks[] = [ $density >= 0.5 && $density <= 2.5, "Keyword density: <strong>{$density}%</strong>" . ( $density >= 0.5 && $density <= 2.5 ? ' — Good (0.5–2.5%)' : ' — Aim for 0.5–2.5%' ) ];
        }

        // Reading time
        $checks[] = [ true, "Estimated reading time: <strong>{$reading_time} min</strong>" ];

        // Has featured image
        $checks[] = [ has_post_thumbnail( $post->ID ), "Featured image: " . ( has_post_thumbnail( $post->ID ) ? 'Set' : 'Missing — add a featured image for OG sharing' ) ];

        $pass  = count( array_filter( $checks, fn($c) => $c[0] ) );
        $total = count( $checks );
        $score = (int) round( ($pass / $total) * 100 );
        $color = $score >= 80 ? '#059669' : ( $score >= 50 ? '#D97706' : '#DC2626' );

        echo "<div class='ovd-score' style='--score-color:{$color}'>";
        echo "<div class='ovd-score-ring'><strong style='color:{$color}'>{$score}</strong><span>/100</span></div>";
        echo "<p>SEO Score — " . ( $score >= 80 ? 'Great' : ($score >= 50 ? 'Needs Work' : 'Poor') ) . "</p>";
        echo "</div>";

        echo "<ul class='ovd-checks'>";
        foreach ( $checks as [ $pass_check, $label ] ) {
            $icon  = $pass_check ? '✓' : '✗';
            $color = $pass_check ? '#059669' : '#DC2626';
            echo "<li style='color:{$color}'><span class='ovd-icon'>{$icon}</span> <span>{$label}</span></li>";
        }
        echo "</ul>";
    }

    /* ── Save ─────────────────────────────────────────────── */

    public function save( int $post_id, \WP_Post $post ): void {
        if ( ! isset( $_POST[ self::NONCE ] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::ACTION ) ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $text_fields = [
            '_ovuday_focus_keyword', '_ovuday_seo_title', '_ovuday_meta_description',
            '_ovuday_canonical', '_ovuday_og_title', '_ovuday_og_description',
            '_ovuday_og_image', '_ovuday_og_type', '_ovuday_tw_card',
            '_ovuday_tw_title', '_ovuday_tw_description', '_ovuday_tw_image',
            '_ovuday_schema_type', '_ovuday_schema_author_name', '_ovuday_schema_author_url',
            '_ovuday_schema_reviewed_by', '_ovuday_redirect_url', '_ovuday_redirect_type',
            '_ovuday_breadcrumb_title', '_ovuday_sitemap_priority', '_ovuday_sitemap_freq',
        ];
        $map = [
            '_ovuday_focus_keyword'       => 'ovd_focus_keyword',
            '_ovuday_seo_title'           => 'ovd_seo_title',
            '_ovuday_meta_description'    => 'ovd_meta_description',
            '_ovuday_canonical'           => 'ovd_canonical',
            '_ovuday_og_title'            => 'ovd_og_title',
            '_ovuday_og_description'      => 'ovd_og_description',
            '_ovuday_og_image'            => 'ovd_og_image',
            '_ovuday_og_type'             => 'ovd_og_type',
            '_ovuday_tw_card'             => 'ovd_tw_card',
            '_ovuday_tw_title'            => 'ovd_tw_title',
            '_ovuday_tw_description'      => 'ovd_tw_description',
            '_ovuday_tw_image'            => 'ovd_tw_image',
            '_ovuday_schema_type'         => 'ovd_schema_type',
            '_ovuday_schema_author_name'  => 'ovd_schema_author_name',
            '_ovuday_schema_author_url'   => 'ovd_schema_author_url',
            '_ovuday_schema_reviewed_by'  => 'ovd_schema_reviewed_by',
            '_ovuday_redirect_url'        => 'ovd_redirect_url',
            '_ovuday_redirect_type'       => 'ovd_redirect_type',
            '_ovuday_breadcrumb_title'    => 'ovd_breadcrumb_title',
            '_ovuday_sitemap_priority'    => 'ovd_sitemap_priority',
            '_ovuday_sitemap_freq'        => 'ovd_sitemap_freq',
            '_ovuday_reading_time_override' => 'ovd_reading_time_override',
        ];
        foreach ( $map as $meta_key => $post_key ) {
            if ( isset( $_POST[ $post_key ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $post_key ] ) ) );
            }
        }

        // Robots (array)
        $robots = isset( $_POST['ovd_robots'] ) ? array_map( 'sanitize_text_field', (array) $_POST['ovd_robots'] ) : [];
        update_post_meta( $post_id, '_ovuday_robots', $robots );

        // Checkboxes
        update_post_meta( $post_id, '_ovuday_sitemap_exclude',     isset( $_POST['ovd_sitemap_exclude'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_ovuday_exclude_from_search', isset( $_POST['ovd_exclude_from_search'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_ovuday_hide_from_rss',       isset( $_POST['ovd_hide_from_rss'] ) ? '1' : '0' );

        // Custom JSON-LD (sanitize but preserve JSON structure)
        if ( isset( $_POST['ovd_schema_custom'] ) ) {
            $raw = wp_unslash( $_POST['ovd_schema_custom'] );
            update_post_meta( $post_id, '_ovuday_schema_custom', wp_kses_post( $raw ) );
        }
    }

    /* ── Output <head> tags ───────────────────────────────── */

    public function output_head_tags(): void {
        if ( ! is_singular() ) return;
        $post_id  = get_the_ID();
        $m        = $this->get_all_meta( $post_id );
        $global   = get_option( 'ovuday_global_seo', [] );
        $site     = $global['site_name'] ?? get_bloginfo('name');
        $sep      = $global['title_separator'] ?? '|';

        // Robots
        $robots = array_filter( $m['robots'] ?? [] );
        if ( ! empty( $robots ) ) {
            printf( '<meta name="robots" content="%s" />' . "\n", esc_attr( implode(', ', $robots) ) );
        }

        // Canonical
        $canon = $m['canonical'] ?: get_permalink( $post_id );
        printf( '<link rel="canonical" href="%s" />' . "\n", esc_url($canon) );

        // OG
        $og_title = $m['og_title'] ?: ( $m['seo_title'] ?: get_the_title($post_id) );
        $og_desc  = $m['og_description'] ?: $m['meta_description'];
        $og_image = $m['og_image'] ?: ( get_the_post_thumbnail_url($post_id,'full') ?: ($global['default_og_image'] ?? '') );
        $og_type  = $m['og_type'] ?: 'article';
        printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr($og_title) );
        printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr($og_desc) );
        printf( '<meta property="og:url" content="%s" />' . "\n", esc_url(get_permalink($post_id)) );
        printf( '<meta property="og:type" content="%s" />' . "\n", esc_attr($og_type) );
        printf( '<meta property="og:site_name" content="%s" />' . "\n", esc_attr($site) );
        if ( $og_image ) printf( '<meta property="og:image" content="%s" />' . "\n", esc_url($og_image) );

        // Twitter
        $tw_card  = $m['tw_card'] ?: 'summary_large_image';
        $tw_title = $m['tw_title'] ?: $og_title;
        $tw_desc  = $m['tw_description'] ?: $og_desc;
        $tw_image = $m['tw_image'] ?: $og_image;
        $tw_user  = $global['twitter_username'] ?? '';
        printf( '<meta name="twitter:card" content="%s" />' . "\n", esc_attr($tw_card) );
        printf( '<meta name="twitter:title" content="%s" />' . "\n", esc_attr($tw_title) );
        printf( '<meta name="twitter:description" content="%s" />' . "\n", esc_attr($tw_desc) );
        if ( $tw_image ) printf( '<meta name="twitter:image" content="%s" />' . "\n", esc_url($tw_image) );
        if ( $tw_user )  printf( '<meta name="twitter:site" content="@%s" />' . "\n", esc_attr(ltrim($tw_user,'@')) );

        // Schema
        $this->output_schema( $post_id, $m, $global );
    }

    private function output_schema( int $post_id, array $m, array $global ): void {
        // Custom JSON-LD takes priority
        if ( ! empty( $m['schema_custom'] ) ) {
            echo '<script type="application/ld+json">' . $m['schema_custom'] . "</script>\n";
            return;
        }

        $type = $m['schema_type'] ?: 'Article';
        if ( $type === 'none' ) return;

        $author_name = $m['schema_author_name'] ?: get_the_author_meta('display_name', get_post_field('post_author', $post_id));
        $author_url  = $m['schema_author_url'] ?: get_author_posts_url( get_post_field('post_author', $post_id) );

        $schema = [
            '@context'  => 'https://schema.org',
            '@type'     => $type,
            'headline'  => get_the_title($post_id),
            'url'       => get_permalink($post_id),
            'datePublished' => get_post_time('c', true, $post_id),
            'dateModified'  => get_post_modified_time('c', true, $post_id),
            'author'    => [ '@type' => 'Person', 'name' => $author_name, 'url' => $author_url ],
            'publisher' => [
                '@type' => 'Organization',
                'name'  => $global['organization_name'] ?? $global['site_name'] ?? get_bloginfo('name'),
                'logo'  => [ '@type' => 'ImageObject', 'url' => $global['organization_logo'] ?? '' ],
            ],
            'description' => $m['meta_description'] ?: '',
        ];

        $img = $m['og_image'] ?: get_the_post_thumbnail_url($post_id,'full');
        if ( $img ) $schema['image'] = [ '@type' => 'ImageObject', 'url' => $img ];

        if ( $m['schema_reviewed_by'] ) {
            $schema['reviewedBy'] = [ '@type' => 'Person', 'name' => $m['schema_reviewed_by'] ];
        }

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "</script>\n";
    }

    /* ── Helpers ──────────────────────────────────────────── */

    private function get_all_meta( int $post_id ): array {
        $keys = [
            'focus_keyword','seo_title','meta_description','canonical',
            'og_title','og_description','og_image','og_type',
            'tw_card','tw_title','tw_description','tw_image',
            'schema_type','schema_author_name','schema_author_url','schema_reviewed_by','schema_custom',
            'redirect_url','redirect_type','breadcrumb_title',
            'sitemap_priority','sitemap_freq','reading_time_override',
        ];
        $out = [];
        foreach ( $keys as $k ) {
            $out[ $k ] = get_post_meta( $post_id, "_ovuday_{$k}", true );
        }
        $out['robots']           = (array) get_post_meta( $post_id, '_ovuday_robots', true );
        $out['sitemap_exclude']  = (bool) get_post_meta( $post_id, '_ovuday_sitemap_exclude', true );
        $out['exclude_from_search'] = (bool) get_post_meta( $post_id, '_ovuday_exclude_from_search', true );
        $out['hide_from_rss']    = (bool) get_post_meta( $post_id, '_ovuday_hide_from_rss', true );
        return $out;
    }

    /* ── CSS ──────────────────────────────────────────────── */

    private function admin_css(): string { return '
.ovd-seo-wrap { font-size:13px; }
.ovd-tabs { display:flex; gap:4px; border-bottom:2px solid #E8476E20; margin-bottom:20px; padding-bottom:0; }
.ovd-tab { background:none; border:none; border-bottom:2px solid transparent; margin-bottom:-2px; padding:8px 16px; cursor:pointer; font-weight:600; font-size:13px; color:#666; transition:.15s; }
.ovd-tab:hover { color:#E8476E; }
.ovd-tab.active { color:#E8476E; border-bottom-color:#E8476E; }
.ovd-panel { display:none; }
.ovd-panel.active { display:block; }
.ovd-field { margin-bottom:18px; }
.ovd-field label { display:block; font-weight:600; margin-bottom:5px; }
.ovd-field input[type=text],.ovd-field input[type=url],.ovd-field select,.ovd-field textarea { width:100%; }
.ovd-hint { color:#888; font-size:11px; margin-top:4px; }
.ovd-badge { background:#E8476E15; color:#c2185b; border-radius:20px; padding:1px 8px; font-size:10px; font-weight:600; margin-left:8px; }
.ovd-counter-bar { height:4px; background:#eee; border-radius:2px; margin:6px 0 2px; overflow:hidden; }
.ovd-counter-fill { height:100%; background:#E8476E; border-radius:2px; transition:width .2s; width:0; }
.ovd-counter-text { font-size:11px; color:#888; }
.ovd-serp { border:1px solid #ddd; border-radius:8px; padding:12px 14px; background:#fafafa; margin-top:10px; }
.ovd-serp-title { color:#1a0dab; font-size:18px; font-weight:400; margin:0 0 2px; }
.ovd-serp-url { color:#188038; font-size:12px; margin:0 0 4px; }
.ovd-serp-desc { color:#4d5156; font-size:13px; margin:0; }
.ovd-checkboxes { display:flex; flex-direction:column; gap:8px; }
.ovd-checkboxes label { font-weight:normal; cursor:pointer; }
.ovd-row { display:flex; gap:20px; flex-wrap:wrap; }
.ovd-row > div { flex:1; min-width:160px; }
.ovd-media-row { display:flex; gap:8px; }
.ovd-media-row input { flex:1; }
.ovd-img-preview { max-width:200px; max-height:120px; border-radius:6px; margin-top:8px; }
.ovd-score { display:flex; align-items:center; gap:16px; padding:16px; background:#f9f9f9; border-radius:10px; margin-bottom:16px; }
.ovd-score-ring { display:flex; align-items:baseline; gap:4px; }
.ovd-score-ring strong { font-size:2.5rem; font-family:system-ui; }
.ovd-score-ring span { font-size:1rem; color:#888; }
.ovd-checks { margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:8px; }
.ovd-checks li { display:flex; align-items:flex-start; gap:8px; font-size:12px; }
.ovd-icon { font-weight:bold; font-size:14px; line-height:1.4; }
.ovd-section-title { font-size:13px; font-weight:700; border-bottom:1px solid #eee; padding-bottom:8px; margin-bottom:16px; color:#333; }
'; }

    /* ── JS ───────────────────────────────────────────────── */

    private function admin_js(): string { return <<<'JS'
jQuery(function($){
  // Tab switching
  $('.ovd-tab').on('click', function(){
    var tab = $(this).data('tab');
    $('.ovd-tab').removeClass('active');
    $('.ovd-panel').removeClass('active');
    $(this).addClass('active');
    $('[data-panel="'+tab+'"]').addClass('active');
  });

  // Char counter
  function counter(inputId, fillId, countId, ideal){
    var el = $('#'+inputId), fill = $('#'+fillId), count = $('#'+countId);
    if(!el.length) return;
    function update(){
      var len = el.val().length;
      var pct = Math.min(100, (len/ideal)*100);
      fill.css('width', pct+'%');
      fill.css('background', len > ideal+10 ? '#DC2626' : len > ideal ? '#D97706' : '#E8476E');
      count.text(len+' / '+ideal+' characters');
    }
    el.on('input', update); update();
  }
  counter('ovd_seo_title','ovd_title_fill','ovd_title_count',60);
  counter('ovd_meta_description','ovd_desc_fill','ovd_desc_count',160);

  // Live SERP preview
  $('#ovd_seo_title').on('input', function(){
    var val = $(this).val() || $('input#title').val();
    $('#ovd_serp_title').text(val);
  });
  $('#ovd_meta_description').on('input', function(){
    $('#ovd_serp_desc').text($(this).val());
  });

  // Media picker
  $(document).on('click','.ovd-media-btn', function(){
    var target = $(this).data('target');
    var frame = wp.media({ title:'Select Image', button:{text:'Use Image'}, multiple:false });
    frame.on('select',function(){
      var url = frame.state().get('selection').first().toJSON().url;
      $('#'+target).val(url);
    });
    frame.open();
  });
});
JS;
    }
}
