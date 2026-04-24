<?php
namespace OvuDay;
defined('ABSPATH') || exit;

/**
 * OvuDay Content Builder
 *
 * Full admin control over every piece of site content:
 * Hero, Trust Badges, Stats, Features, How-It-Works Steps,
 * FAQ, Calculator Labels, CTA Banner, Blog Settings, Footer.
 *
 * All data is saved to `ovuday_site_content` option and exposed
 * via WPGraphQL (see class-graphql-extensions.php → register_content_types).
 */
class Content_Builder {

    private string $option = 'ovuday_site_content';
    private string $page   = 'ovuday-content';

    public function __construct() {
        add_action( 'admin_menu',    [ $this, 'add_menu' ] );
        add_action( 'admin_init',    [ $this, 'handle_save' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /* ── Menu ──────────────────────────────────────────────── */

    public function add_menu(): void {
        add_submenu_page(
            'ovuday-seo',
            'Content Builder',
            '🎨 Content Builder',
            'manage_options',
            $this->page,
            [ $this, 'render_page' ]
        );
    }

    /* ── Assets ────────────────────────────────────────────── */

    public function enqueue_assets( string $hook ): void {
        if ( strpos($hook, $this->page) === false ) return;

        wp_enqueue_media();
        wp_enqueue_style( 'ovuday-admin-ui', OVUDAY_URL . 'admin/admin-ui.css', [], OVUDAY_VERSION );
        wp_enqueue_script(
            'ovuday-content-builder',
            OVUDAY_URL . 'admin/content-builder.js',
            [ 'jquery', 'wp-color-picker' ],
            OVUDAY_VERSION,
            true
        );
        wp_enqueue_style( 'wp-color-picker' );
    }

    /* ── Save ──────────────────────────────────────────────── */

    public function handle_save(): void {
        if (
            ! isset( $_POST['ovuday_content_nonce'] ) ||
            ! wp_verify_nonce( $_POST['ovuday_content_nonce'], 'ovuday_save_content' ) ||
            ! current_user_can( 'manage_options' )
        ) return;

        $section = sanitize_key( $_POST['active_section'] ?? 'hero' );
        $data    = $this->get_content();

        switch ( $section ) {
            case 'hero':        $data['hero']        = $this->sanitize_hero();        break;
            case 'navigation':  $data['navigation']  = $this->sanitize_navigation(); break;
            case 'trust':       $data['trust']       = $this->sanitize_trust();       break;
            case 'stats':       $data['stats']       = $this->sanitize_stats();       break;
            case 'features':    $data['features']    = $this->sanitize_features();    break;
            case 'steps':       $data['steps']       = $this->sanitize_steps();       break;
            case 'faq':         $data['faq']         = $this->sanitize_faq();         break;
            case 'calculator':  $data['calculator']  = $this->sanitize_calculator();  break;
            case 'cta':         $data['cta']         = $this->sanitize_cta();         break;
            case 'blog':        $data['blog']        = $this->sanitize_blog();        break;
            case 'footer':      $data['footer']      = $this->sanitize_footer();      break;
            case 'global':      $data['global']      = $this->sanitize_global();      break;
            case 'about_page':   $data['about_page']   = $this->sanitize_about_page();   break;
            case 'how_page':     $data['how_page']     = $this->sanitize_how_page();     break;
            case 'contact_page': $data['contact_page'] = $this->sanitize_contact_page(); break;
            case 'privacy_page': $data['privacy_page'] = $this->sanitize_privacy_page(); break;
            case 'terms_page':   $data['terms_page']   = $this->sanitize_terms_page();   break;
        }

        update_option( $this->option, $data );
        add_settings_error( $this->option, 'saved', '✅ Content saved!', 'updated' );
    }

    /* ── Sanitizers ─────────────────────────────────────────── */

    private function sanitize_hero(): array {
        $p = $_POST['hero'] ?? [];
        return [
            'badge_text'       => sanitize_text_field( $p['badge_text']       ?? '' ),
            'headline'         => sanitize_text_field( $p['headline']         ?? '' ),
            'headline_accent'  => sanitize_text_field( $p['headline_accent']  ?? '' ),
            'subheadline'      => sanitize_textarea_field( $p['subheadline']  ?? '' ),
            'cta_primary_text' => sanitize_text_field( $p['cta_primary_text'] ?? '' ),
            'cta_primary_url'  => esc_url_raw( $p['cta_primary_url']          ?? '' ),
            'cta_secondary_text'=> sanitize_text_field( $p['cta_secondary_text'] ?? '' ),
            'cta_secondary_url' => esc_url_raw( $p['cta_secondary_url']       ?? '' ),
            'bg_gradient_from' => sanitize_hex_color( $p['bg_gradient_from']  ?? '' ),
            'bg_gradient_to'   => sanitize_hex_color( $p['bg_gradient_to']    ?? '' ),
        ];
    }

    private function sanitize_navigation(): array {
        $items = $_POST['navigation']['items'] ?? [];
        $out   = [];
        foreach ( $items as $item ) {
            if ( empty($item['label']) ) continue;
            $out[] = [
                'label' => sanitize_text_field( $item['label'] ),
                'url'   => esc_url_raw( $item['url'] ?? '#' ),
            ];
        }
        return [ 'items' => $out ];
    }

    private function sanitize_trust(): array {
        $items = $_POST['trust']['items'] ?? [];
        $out   = [];
        foreach ( $items as $item ) {
            if ( empty($item['text']) ) continue;
            $out[] = [
                'icon' => sanitize_text_field( $item['icon'] ?? 'Shield' ),
                'text' => sanitize_text_field( $item['text'] ),
                'color'=> sanitize_hex_color( $item['color'] ?? '#10b981' ),
            ];
        }
        return [ 'items' => $out ];
    }

    private function sanitize_stats(): array {
        $items = $_POST['stats']['items'] ?? [];
        $out   = [];
        foreach ( $items as $item ) {
            if ( empty($item['value']) ) continue;
            $out[] = [
                'value'  => sanitize_text_field( $item['value'] ),
                'label'  => sanitize_text_field( $item['label'] ?? '' ),
                'suffix' => sanitize_text_field( $item['suffix'] ?? '' ),
            ];
        }
        return [ 'items' => $out ];
    }

    private function sanitize_features(): array {
        $items = $_POST['features']['items'] ?? [];
        $out   = [];
        foreach ( $items as $item ) {
            if ( empty($item['title']) ) continue;
            $out[] = [
                'icon'        => sanitize_text_field( $item['icon']        ?? 'Star' ),
                'title'       => sanitize_text_field( $item['title'] ),
                'description' => sanitize_textarea_field( $item['description'] ?? '' ),
                'color'       => sanitize_hex_color( $item['color'] ?? '#E8476E' ),
            ];
        }
        return [
            'section_title'   => sanitize_text_field( $_POST['features']['section_title']   ?? '' ),
            'section_subtitle'=> sanitize_text_field( $_POST['features']['section_subtitle'] ?? '' ),
            'items'           => $out,
        ];
    }

    private function sanitize_steps(): array {
        $items = $_POST['steps']['items'] ?? [];
        $out   = [];
        foreach ( $items as $item ) {
            if ( empty($item['title']) ) continue;
            $out[] = [
                'number'      => sanitize_text_field( $item['number'] ?? '' ),
                'title'       => sanitize_text_field( $item['title'] ),
                'description' => sanitize_textarea_field( $item['description'] ?? '' ),
                'icon'        => sanitize_text_field( $item['icon']  ?? '' ),
            ];
        }
        return [
            'section_title'   => sanitize_text_field( $_POST['steps']['section_title']   ?? '' ),
            'section_subtitle'=> sanitize_text_field( $_POST['steps']['section_subtitle'] ?? '' ),
            'items'           => $out,
        ];
    }

    private function sanitize_faq(): array {
        $items = $_POST['faq']['items'] ?? [];
        $out   = [];
        foreach ( $items as $item ) {
            if ( empty($item['question']) ) continue;
            $out[] = [
                'question' => sanitize_text_field( $item['question'] ),
                'answer'   => wp_kses_post( $item['answer'] ?? '' ),
                'category' => sanitize_text_field( $item['category'] ?? 'general' ),
            ];
        }
        return [
            'section_title'   => sanitize_text_field( $_POST['faq']['section_title']   ?? '' ),
            'section_subtitle'=> sanitize_text_field( $_POST['faq']['section_subtitle'] ?? '' ),
            'items'           => $out,
        ];
    }

    private function sanitize_calculator(): array {
        $p = $_POST['calculator'] ?? [];
        return [
            'title'                => sanitize_text_field( $p['title']                ?? '' ),
            'subtitle'             => sanitize_textarea_field( $p['subtitle']          ?? '' ),
            'lmp_label'            => sanitize_text_field( $p['lmp_label']            ?? '' ),
            'lmp_help'             => sanitize_text_field( $p['lmp_help']             ?? '' ),
            'cycle_label'          => sanitize_text_field( $p['cycle_label']          ?? '' ),
            'cycle_help'           => sanitize_text_field( $p['cycle_help']           ?? '' ),
            'luteal_label'         => sanitize_text_field( $p['luteal_label']         ?? '' ),
            'luteal_help'          => sanitize_text_field( $p['luteal_help']          ?? '' ),
            'calculate_btn'        => sanitize_text_field( $p['calculate_btn']        ?? '' ),
            'reset_btn'            => sanitize_text_field( $p['reset_btn']            ?? '' ),
            'result_title'         => sanitize_text_field( $p['result_title']         ?? '' ),
            'cycle_overview_label' => sanitize_text_field( $p['cycle_overview_label'] ?? '' ),
            'fertile_window_title' => sanitize_text_field( $p['fertile_window_title'] ?? '' ),
            'fertile_window_label' => sanitize_text_field( $p['fertile_window_label'] ?? '' ),
            'ovulation_day_label'  => sanitize_text_field( $p['ovulation_day_label']  ?? '' ),
            'next_period_label'    => sanitize_text_field( $p['next_period_label']    ?? '' ),
            'peak_day_label'       => sanitize_text_field( $p['peak_day_label']       ?? '' ),
            'tab_current'          => sanitize_text_field( $p['tab_current']          ?? '' ),
            'tab_next_cycles'      => sanitize_text_field( $p['tab_next_cycles']      ?? '' ),
            'tip_text'             => sanitize_textarea_field( $p['tip_text']          ?? '' ),
            'privacy_note'         => sanitize_text_field( $p['privacy_note']         ?? '' ),
            'disclaimer'           => sanitize_textarea_field( $p['disclaimer']        ?? '' ),
        ];
    }

    private function sanitize_cta(): array {
        $p = $_POST['cta'] ?? [];
        return [
            'title'       => sanitize_text_field( $p['title']       ?? '' ),
            'subtitle'    => sanitize_textarea_field( $p['subtitle'] ?? '' ),
            'btn_text'    => sanitize_text_field( $p['btn_text']    ?? '' ),
            'btn_url'     => esc_url_raw( $p['btn_url']             ?? '' ),
            'bg_color'    => sanitize_hex_color( $p['bg_color']     ?? '' ),
            'text_color'  => sanitize_hex_color( $p['text_color']   ?? '' ),
        ];
    }

    private function sanitize_blog(): array {
        $p = $_POST['blog'] ?? [];
        return [
            'listing_title'     => sanitize_text_field( $p['listing_title']    ?? '' ),
            'listing_subtitle'  => sanitize_text_field( $p['listing_subtitle'] ?? '' ),
            'listing_badge'     => sanitize_text_field( $p['listing_badge']    ?? '' ),
            'posts_per_page'    => (int) ( $p['posts_per_page'] ?? 12 ),
            'layout'            => in_array( $p['layout'] ?? 'grid', ['grid','list','masonry'] ) ? $p['layout'] : 'grid',
            'show_author'       => isset($p['show_author'])   ? '1' : '0',
            'show_date'         => isset($p['show_date'])     ? '1' : '0',
            'show_category'     => isset($p['show_category']) ? '1' : '0',
            'show_reading_time' => isset($p['show_reading_time']) ? '1' : '0',
            'show_tags'         => isset($p['show_tags'])     ? '1' : '0',
            'excerpt_length'    => (int) ( $p['excerpt_length'] ?? 25 ),
            'featured_post'     => isset($p['featured_post']) ? '1' : '0',
            'no_posts_text'     => sanitize_text_field( $p['no_posts_text'] ?? '' ),
            'read_more_text'    => sanitize_text_field( $p['read_more_text'] ?? '' ),
            // Post detail
            'detail_show_author_box'  => isset($p['detail_show_author_box'])  ? '1' : '0',
            'detail_show_related'     => isset($p['detail_show_related'])     ? '1' : '0',
            'detail_related_count'    => (int) ( $p['detail_related_count'] ?? 3 ),
            'detail_related_title'    => sanitize_text_field( $p['detail_related_title'] ?? '' ),
            'detail_show_breadcrumb'  => isset($p['detail_show_breadcrumb'])  ? '1' : '0',
            'detail_show_share'       => isset($p['detail_show_share'])       ? '1' : '0',
            'detail_show_toc'         => isset($p['detail_show_toc'])         ? '1' : '0',
            'detail_toc_title'        => sanitize_text_field( $p['detail_toc_title'] ?? '' ),
            'detail_medical_disclaimer'=> sanitize_textarea_field( $p['detail_medical_disclaimer'] ?? '' ),
            // AdSense-friendly ad slots
            'listing_top_ad_slot'     => sanitize_text_field( $p['listing_top_ad_slot'] ?? '' ),
            'listing_inline_ad_slot'  => sanitize_text_field( $p['listing_inline_ad_slot'] ?? '' ),
            'listing_inline_every'    => max( 2, (int) ( $p['listing_inline_every'] ?? 4 ) ),
            'detail_top_ad_slot'      => sanitize_text_field( $p['detail_top_ad_slot'] ?? '' ),
            'detail_inline_ad_slot'   => sanitize_text_field( $p['detail_inline_ad_slot'] ?? '' ),
            'detail_sidebar_ad_slot'  => sanitize_text_field( $p['detail_sidebar_ad_slot'] ?? '' ),
            'detail_bottom_ad_slot'   => sanitize_text_field( $p['detail_bottom_ad_slot'] ?? '' ),
            'detail_share_label'      => sanitize_text_field( $p['detail_share_label'] ?? '' ),
            'detail_back_to_blog_text'=> sanitize_text_field( $p['detail_back_to_blog_text'] ?? '' ),
        ];
    }

    private function sanitize_global(): array {
        $p = $_POST['global'] ?? [];

        $site_copy_raw = isset( $p['site_copy_json'] ) ? wp_unslash( (string) $p['site_copy_json'] ) : '';
        $decoded       = json_decode( $site_copy_raw, true );
        $site_copy_json = '';

        if ( is_array( $decoded ) ) {
            $site_copy_json = wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
        }

        return [
            'ads_enabled'      => isset( $p['ads_enabled'] ) ? '1' : '0',
            'adsense_client'   => sanitize_text_field( $p['adsense_client'] ?? '' ),
            'ad_label'         => sanitize_text_field( $p['ad_label'] ?? '' ),
            'sponsored_label'  => sanitize_text_field( $p['sponsored_label'] ?? '' ),
            'site_copy_json'   => $site_copy_json,
        ];
    }

    private function sanitize_footer(): array {
        $p     = $_POST['footer'] ?? [];
        $links = [];
        foreach ( ($p['links'] ?? []) as $col ) {
            $rows = [];
            foreach ( ($col['items'] ?? []) as $link ) {
                if ( empty($link['label']) ) continue;
                $rows[] = [
                    'label' => sanitize_text_field( $link['label'] ),
                    'url'   => esc_url_raw( $link['url'] ?? '#' ),
                ];
            }
            if ( ! empty($col['title']) || $rows ) {
                $links[] = [ 'title' => sanitize_text_field($col['title'] ?? ''), 'items' => $rows ];
            }
        }
        return [
            'logo_text'      => sanitize_text_field( $p['logo_text']      ?? '' ),
            'tagline'        => sanitize_text_field( $p['tagline']         ?? '' ),
            'copyright'      => sanitize_text_field( $p['copyright']       ?? '' ),
            'disclaimer'     => sanitize_textarea_field( $p['disclaimer']  ?? '' ),
            'links'          => $links,
            'social_twitter' => esc_url_raw( $p['social_twitter']         ?? '' ),
            'social_facebook'=> esc_url_raw( $p['social_facebook']        ?? '' ),
            'social_instagram'=> esc_url_raw( $p['social_instagram']      ?? '' ),
            'social_pinterest'=> esc_url_raw( $p['social_pinterest']      ?? '' ),
            'newsletter_enable'=> isset($p['newsletter_enable']) ? '1' : '0',
            'newsletter_title' => sanitize_text_field( $p['newsletter_title'] ?? '' ),
            'newsletter_placeholder' => sanitize_text_field( $p['newsletter_placeholder'] ?? '' ),
            'newsletter_btn'   => sanitize_text_field( $p['newsletter_btn'] ?? '' ),
        ];
    }

    /* ── Page section sanitizers ──────────────────────────────── */

    private function sanitize_about_page(): array {
        $p = $_POST['about_page'] ?? [];
        $values = [];
        foreach (($p['values'] ?? []) as $item) {
            if (empty($item['title'])) continue;
            $values[] = [
                'icon'        => sanitize_text_field($item['icon'] ?? '🔒'),
                'title'       => sanitize_text_field($item['title']),
                'description' => sanitize_textarea_field($item['description'] ?? ''),
            ];
        }
        return [
            'badge'              => sanitize_text_field($p['badge'] ?? ''),
            'title'              => sanitize_text_field($p['title'] ?? ''),
            'intro'              => sanitize_textarea_field($p['intro'] ?? ''),
            'story_title'        => sanitize_text_field($p['story_title'] ?? ''),
            'story_p1'           => sanitize_textarea_field($p['story_p1'] ?? ''),
            'story_p2'           => sanitize_textarea_field($p['story_p2'] ?? ''),
            'values_title'       => sanitize_text_field($p['values_title'] ?? ''),
            'values'             => $values,
            'disclaimer'         => sanitize_textarea_field($p['disclaimer'] ?? ''),
            'cta_primary_text'   => sanitize_text_field($p['cta_primary_text'] ?? ''),
            'cta_primary_url'    => esc_url_raw($p['cta_primary_url'] ?? ''),
            'cta_secondary_text' => sanitize_text_field($p['cta_secondary_text'] ?? ''),
            'cta_secondary_url'  => esc_url_raw($p['cta_secondary_url'] ?? ''),
        ];
    }

    private function sanitize_how_page(): array {
        $p = $_POST['how_page'] ?? [];
        $phases = [];
        foreach (($p['phases'] ?? []) as $item) {
            if (empty($item['name'])) continue;
            $phases[] = [
                'name'        => sanitize_text_field($item['name']),
                'days'        => sanitize_text_field($item['days'] ?? ''),
                'description' => sanitize_textarea_field($item['description'] ?? ''),
                'color'       => sanitize_hex_color($item['color'] ?? '#FEE2E2'),
                'text_color'  => sanitize_hex_color($item['text_color'] ?? '#991B1B'),
            ];
        }
        $limitations = [];
        foreach (($p['limitations'] ?? []) as $item) {
            $text = sanitize_textarea_field($item['text'] ?? '');
            if ($text) $limitations[] = ['text' => $text];
        }
        return [
            'badge'               => sanitize_text_field($p['badge'] ?? ''),
            'title'               => sanitize_text_field($p['title'] ?? ''),
            'intro'               => sanitize_textarea_field($p['intro'] ?? ''),
            'phases_title'        => sanitize_text_field($p['phases_title'] ?? ''),
            'phases_subtitle'     => sanitize_text_field($p['phases_subtitle'] ?? ''),
            'phases'              => $phases,
            'formula_title'       => sanitize_text_field($p['formula_title'] ?? ''),
            'formula_example'     => sanitize_textarea_field($p['formula_example'] ?? ''),
            'fertile_title'       => sanitize_text_field($p['fertile_title'] ?? ''),
            'fertile_explanation' => sanitize_textarea_field($p['fertile_explanation'] ?? ''),
            'limitations_title'   => sanitize_text_field($p['limitations_title'] ?? ''),
            'limitations'         => $limitations,
            'cta_text'            => sanitize_text_field($p['cta_text'] ?? ''),
            'cta_btn_text'        => sanitize_text_field($p['cta_btn_text'] ?? ''),
            'cta_btn_url'         => esc_url_raw($p['cta_btn_url'] ?? ''),
        ];
    }

    private function sanitize_contact_page(): array {
        $p = $_POST['contact_page'] ?? [];
        $subjects = [];
        foreach (($p['subjects'] ?? []) as $item) {
            $text = sanitize_text_field($item['text'] ?? '');
            if ($text) $subjects[] = ['text' => $text];
        }
        return [
            'badge'            => sanitize_text_field($p['badge'] ?? ''),
            'title'            => sanitize_text_field($p['title'] ?? ''),
            'intro'            => sanitize_textarea_field($p['intro'] ?? ''),
            'success_message'  => sanitize_textarea_field($p['success_message'] ?? ''),
            'response_time'    => sanitize_textarea_field($p['response_time'] ?? ''),
            'form_email'       => sanitize_email($p['form_email'] ?? ''),
            'form_subject'     => sanitize_text_field($p['form_subject'] ?? ''),
            'subjects'         => $subjects,
        ];
    }

    private function sanitize_legal_sections(): array {
        $p = $_POST;
        $key = sanitize_key($p['active_section'] ?? '');
        $data = $p[$key] ?? [];
        $sections = [];
        foreach (($data['sections'] ?? []) as $item) {
            if (empty($item['heading'])) continue;
            $sections[] = [
                'heading' => sanitize_text_field($item['heading']),
                'body'    => wp_kses_post($item['body'] ?? ''),
            ];
        }
        return [
            'title'    => sanitize_text_field($data['title'] ?? ''),
            'sections' => $sections,
        ];
    }

    private function sanitize_privacy_page(): array { return $this->sanitize_legal_sections(); }
    private function sanitize_terms_page(): array   { return $this->sanitize_legal_sections(); }

    /* ── Data getter with defaults ─────────────────────────── */

    public static function get_content(): array {
        $saved = get_option('ovuday_site_content', []);
        return array_merge( self::defaults(), $saved );
    }

    public static function defaults(): array {
        return [
            'hero' => [
                'badge_text'        => '🌸 Trusted by 500,000+ women',
                'headline'          => 'Know Your',
                'headline_accent'   => 'Fertile Days',
                'subheadline'       => 'The most accurate ovulation calculator — know exactly when you\'re most fertile with personalized cycle insights.',
                'cta_primary_text'  => 'Calculate Now',
                'cta_primary_url'   => '#calculator',
                'cta_secondary_text'=> 'How It Works',
                'cta_secondary_url' => '/how-it-works',
                'bg_gradient_from'  => '#fff1f5',
                'bg_gradient_to'    => '#f3f0ff',
            ],
            'navigation' => [
                'items' => [
                    [ 'label' => 'Calculator', 'url' => '/' ],
                    [ 'label' => 'How It Works', 'url' => '/how-it-works' ],
                    [ 'label' => 'Blog', 'url' => '/blog' ],
                    [ 'label' => 'FAQ', 'url' => '/faq' ],
                    [ 'label' => 'About', 'url' => '/about' ],
                ],
            ],
            'trust' => [
                'items' => [
                    [ 'icon' => 'ShieldCheck', 'text' => 'Clinically informed',   'color' => '#10b981' ],
                    [ 'icon' => 'Lock',        'text' => '100% private — no data stored', 'color' => '#6366f1' ],
                    [ 'icon' => 'Zap',         'text' => 'Instant results',       'color' => '#f59e0b' ],
                    [ 'icon' => 'Heart',       'text' => 'Trusted by 500K+ women','color' => '#E8476E' ],
                ],
            ],
            'stats' => [
                'items' => [
                    [ 'value' => '500K', 'label' => 'Women helped', 'suffix' => '+' ],
                    [ 'value' => '98',   'label' => 'Accuracy rate', 'suffix' => '%' ],
                    [ 'value' => '4.9',  'label' => 'Average rating', 'suffix' => '★' ],
                    [ 'value' => '0',    'label' => 'Data stored', 'suffix' => '' ],
                ],
            ],
            'features' => [
                'section_title'    => 'Everything You Need to Know',
                'section_subtitle' => 'Accurate, private, and beautifully simple.',
                'items' => [
                    [ 'icon' => 'Lock',       'title' => '100% Private',        'description' => 'No account needed. All calculations happen in your browser — nothing is ever sent to our servers.', 'color' => '#6366f1' ],
                    [ 'icon' => 'Zap',        'title' => 'Instant Results',     'description' => 'Get your full fertile window, ovulation day, and next 3 cycles in seconds.', 'color' => '#f59e0b' ],
                    [ 'icon' => 'CalendarDays','title' => '3-Cycle Forecast',   'description' => 'Plan ahead with predictions for your next three menstrual cycles.', 'color' => '#E8476E' ],
                    [ 'icon' => 'Gift',       'title' => 'Completely Free',     'description' => 'No paywalls, no subscriptions. Always free to use.', 'color' => '#10b981' ],
                    [ 'icon' => 'Heart',      'title' => 'Medically Informed',  'description' => 'Based on established cycle science and reviewed by health professionals.', 'color' => '#ec4899' ],
                    [ 'icon' => 'Star',       'title' => 'Personalised',        'description' => 'Adapts to your unique cycle length and luteal phase for maximum accuracy.', 'color' => '#8b5cf6' ],
                ],
            ],
            'steps' => [
                'section_title'    => 'How It Works',
                'section_subtitle' => 'Three simple steps to your full fertility forecast.',
                'items' => [
                    [ 'number' => '01', 'title' => 'Enter Your Last Period',  'description' => 'Tell us the date your last period started. This is the anchor point for all calculations.', 'icon' => 'Calendar' ],
                    [ 'number' => '02', 'title' => 'Set Your Cycle Length',  'description' => 'Enter your average cycle length (typically 21–35 days). Not sure? Use the default 28 days.', 'icon' => 'Clock' ],
                    [ 'number' => '03', 'title' => 'See Your Fertile Window','description' => 'Instantly see your fertile days, peak ovulation day, and next period date.', 'icon' => 'Sparkles' ],
                ],
            ],
            'faq' => [
                'section_title'    => 'Frequently Asked Questions',
                'section_subtitle' => 'Everything you need to know about ovulation and fertile windows.',
                'items' => [
                    [ 'question' => 'How accurate is the ovulation calculator?', 'answer' => 'Our calculator is highly accurate for women with regular cycles, with an accuracy rate of approximately 98% when cycle data is entered correctly.', 'category' => 'accuracy' ],
                    [ 'question' => 'Is my data private?',                       'answer' => 'Yes. All calculations happen entirely in your browser. No data is ever sent to our servers or stored anywhere.', 'category' => 'privacy' ],
                    [ 'question' => 'What is the fertile window?',               'answer' => 'Your fertile window is the 6-day period ending on ovulation day — the 5 days before ovulation plus ovulation day itself.', 'category' => 'fertility' ],
                    [ 'question' => 'When do I ovulate?',                        'answer' => 'Most women ovulate about 14 days before their next period. For a 28-day cycle that\'s day 14; for a 30-day cycle it\'s approximately day 16.', 'category' => 'fertility' ],
                    [ 'question' => 'Can I use this to avoid pregnancy?',        'answer' => 'This calculator is for informational purposes only and should not be used as a contraceptive method. Consult your doctor for family planning advice.', 'category' => 'general' ],
                ],
            ],
            'calculator' => [
                'title'                => 'Ovulation Calculator',
                'subtitle'             => 'Enter your details to see your fertile window',
                'lmp_label'            => 'First day of last period',
                'lmp_help'             => 'The date your most recent period started',
                'cycle_label'          => 'Average cycle length',
                'cycle_help'           => 'How many days between the first day of one period and the first day of the next',
                'luteal_label'         => 'Luteal phase length',
                'luteal_help'          => 'Days between ovulation and next period. Most women have a 14-day luteal phase.',
                'calculate_btn'        => 'Calculate My Fertile Window',
                'reset_btn'            => 'Start Over',
                'result_title'         => 'Your Fertility Forecast',
                'cycle_overview_label' => 'Your {cycleLength}-day cycle overview',
                'fertile_window_title' => 'Your 6-Day Fertile Window',
                'fertile_window_label' => 'Fertile Window',
                'ovulation_day_label'  => 'Ovulation Day',
                'next_period_label'    => 'Next Period',
                'peak_day_label'       => 'Peak Fertility',
                'tab_current'          => 'Current Cycle',
                'tab_next_cycles'      => 'Next 3 Cycles',
                'tip_text'             => 'Sperm can survive up to 5 days in the reproductive tract. Having sex in the days leading up to ovulation significantly increases your chances of conception.',
                'privacy_note'         => 'Your data stays in your browser — never stored or shared.',
                'disclaimer'           => 'This calculator is for informational purposes only and is not a substitute for professional medical advice.',
            ],
            'cta' => [
                'title'      => 'Ready to Know Your Fertile Window?',
                'subtitle'   => 'Join half a million women who plan smarter with OvuDay. Free, private, instant.',
                'btn_text'   => 'Calculate Now — It\'s Free',
                'btn_url'    => '#calculator',
                'bg_color'   => '#E8476E',
                'text_color' => '#ffffff',
            ],
            'blog' => [
                'listing_badge'          => 'Fertility Blog',
                'listing_title'          => 'Women\'s Health Blog',
                'listing_subtitle'       => 'Expert articles on fertility, ovulation, and reproductive health.',
                'posts_per_page'         => 12,
                'layout'                 => 'grid',
                'show_author'            => '1',
                'show_date'              => '1',
                'show_category'          => '1',
                'show_reading_time'      => '1',
                'show_tags'              => '1',
                'excerpt_length'         => 25,
                'featured_post'          => '1',
                'no_posts_text'          => 'No articles found.',
                'read_more_text'         => 'Read Article',
                'detail_show_author_box' => '1',
                'detail_show_related'    => '1',
                'detail_related_count'   => 3,
                'detail_related_title'   => 'Related Articles',
                'detail_show_breadcrumb' => '1',
                'detail_show_share'      => '1',
                'detail_show_toc'        => '1',
                'detail_toc_title'       => 'In This Article',
                'detail_medical_disclaimer' => 'This article is for informational purposes only and does not constitute medical advice. Always consult a qualified healthcare professional.',
                'listing_top_ad_slot'    => '',
                'listing_inline_ad_slot' => '',
                'listing_inline_every'   => 4,
                'detail_top_ad_slot'     => '',
                'detail_inline_ad_slot'  => '',
                'detail_sidebar_ad_slot' => '',
                'detail_bottom_ad_slot'  => '',
                'detail_share_label'     => 'Share this article:',
                'detail_back_to_blog_text' => 'Back to Blog',
            ],
            'footer' => [
                'logo_text'       => 'OvuDay',
                'tagline'         => 'Your trusted fertility companion.',
                'copyright'       => '© ' . date('Y') . ' OvuDay. All rights reserved.',
                'disclaimer'      => 'This website is for informational purposes only and is not a substitute for professional medical advice, diagnosis, or treatment.',
                'social_twitter'  => '',
                'social_facebook' => '',
                'social_instagram'=> '',
                'social_pinterest'=> '',
                'newsletter_enable'     => '1',
                'newsletter_title'      => 'Get Fertility Tips',
                'newsletter_placeholder'=> 'Enter your email',
                'newsletter_btn'        => 'Subscribe Free',
                'links' => [
                    [
                        'title' => 'Tools',
                        'items' => [
                            [ 'label' => 'Ovulation Calculator', 'url' => '/#calculator' ],
                            [ 'label' => 'How It Works',         'url' => '/how-it-works' ],
                            [ 'label' => 'FAQ',                  'url' => '/faq' ],
                        ],
                    ],
                    [
                        'title' => 'Learn',
                        'items' => [
                            [ 'label' => 'Blog',        'url' => '/blog' ],
                            [ 'label' => 'About Us',    'url' => '/about' ],
                            [ 'label' => 'Contact',     'url' => '/contact' ],
                        ],
                    ],
                    [
                        'title' => 'Legal',
                        'items' => [
                            [ 'label' => 'Privacy Policy', 'url' => '/privacy-policy' ],
                            [ 'label' => 'Terms of Use',   'url' => '/terms' ],
                        ],
                    ],
                ],
            ],
            'about_page' => [
                'badge'              => 'About Us',
                'title'              => 'Our Mission',
                'intro'              => 'OvuDay was built with one goal: to give every woman a simple, private, and accurate way to understand her cycle — completely free, no strings attached.',
                'story_title'        => 'Why We Built OvuDay',
                'story_p1'           => 'Fertility tracking should not require expensive apps, subscriptions, or giving away your most personal health data.',
                'story_p2'           => 'OvuDay exists to fix that. We built a fast, clean, browser-based calculator that respects your privacy completely — no accounts, no data collection, and transparent policies.',
                'values_title'       => 'Our Values',
                'values'             => [
                    ['icon' => '🔒', 'title' => 'Privacy First',      'description' => 'All calculations happen in your browser. We never store personal health data.'],
                    ['icon' => '✅', 'title' => 'Science-Based',      'description' => 'Our calculator uses medically recognised methods reviewed against clinical guidelines.'],
                    ['icon' => '🌍', 'title' => 'Accessible to All',  'description' => 'OvuDay is free, works on any device, and requires no account or sign-up.'],
                    ['icon' => '💬', 'title' => 'Honest & Clear',     'description' => 'We explain our calculations transparently and acknowledge limitations openly.'],
                ],
                'disclaimer'         => 'Our calculator is not a substitute for professional medical advice, diagnosis, or treatment. Consult your healthcare provider for fertility concerns.',
                'cta_primary_text'   => 'Try the Calculator',
                'cta_primary_url'    => '/',
                'cta_secondary_text' => 'Contact Us',
                'cta_secondary_url'  => '/contact',
            ],
            'how_page' => [
                'badge'               => 'The Science',
                'title'               => 'How the Ovulation Calculator Works',
                'intro'               => 'OvuDay uses the calendar method — the most widely understood approach for estimating ovulation. Here\'s the science behind every calculation.',
                'phases_title'        => 'The 4 Phases of the Menstrual Cycle',
                'phases_subtitle'     => 'A typical cycle is 21–35 days. Understanding each phase helps you interpret your calculator results.',
                'phases'              => [
                    ['name' => 'Menstrual Phase',  'days' => 'Day 1–5',       'description' => 'Your period starts. The uterine lining sheds. Hormone levels (estrogen and progesterone) are at their lowest.',                                           'color' => '#FEE2E2', 'text_color' => '#991B1B'],
                    ['name' => 'Follicular Phase', 'days' => 'Day 1–13',      'description' => 'The pituitary gland releases FSH (Follicle Stimulating Hormone), triggering several follicles to develop. Estrogen rises.',                                'color' => '#FEF9C3', 'text_color' => '#854D0E'],
                    ['name' => 'Ovulation',        'days' => 'Day 14 (avg)',   'description' => 'A surge of LH (Luteinizing Hormone) triggers the dominant follicle to release an egg. This egg survives 12–24 hours. This is your peak fertility.',        'color' => '#FCE4EC', 'text_color' => '#9D174D'],
                    ['name' => 'Luteal Phase',     'days' => 'Day 15–28',      'description' => 'The follicle becomes the corpus luteum, producing progesterone to prepare the uterus. If no fertilization occurs, hormone levels drop, triggering your next period.', 'color' => '#EDE9FE', 'text_color' => '#5B21B6'],
                ],
                'formula_title'       => 'The Calculation Formula',
                'formula_example'     => 'If your last period was March 1st, cycle is 28 days, and luteal phase is 14 days — ovulation = March 1 + (28 − 14) = March 15. Fertile window: March 10–16.',
                'fertile_title'       => 'Why the Fertile Window is 6 Days',
                'fertile_explanation' => 'Sperm can survive in the reproductive tract for up to 5 days. The egg, however, only survives 12–24 hours after ovulation. So intercourse in the 5 days before ovulation — plus ovulation day itself — gives the best chance of conception.',
                'limitations_title'   => 'Limitations to Know',
                'limitations'         => [
                    ['text' => 'Irregular cycles make predictions less precise — track your last 3–6 months to find your average.'],
                    ['text' => 'Stress, illness, and lifestyle changes can shift ovulation by several days.'],
                    ['text' => 'The calendar method is best paired with BBT (basal body temperature) tracking or LH surge test strips for highest accuracy.'],
                    ['text' => 'This tool is not a contraceptive method.'],
                ],
                'cta_text'            => 'Ready to calculate your ovulation date?',
                'cta_btn_text'        => 'Use the Free Calculator →',
                'cta_btn_url'         => '/',
            ],
            'contact_page' => [
                'badge'           => 'Contact',
                'title'           => 'Get in Touch',
                'intro'           => 'Have a question, suggestion, or spotted an issue? We\'d love to hear from you.',
                'success_message' => 'Thanks for contacting us. Your message has been sent successfully.',
                'response_time'   => 'We typically respond within 2 business days. For urgent fertility questions, please consult a healthcare professional.',
                'form_email'      => 'ahsanaleemofficial@gmail.com',
                'form_subject'    => 'OvuDay Contact Form',
                'subjects'        => [
                    ['text' => 'Calculator feedback'],
                    ['text' => 'Bug report'],
                    ['text' => 'Content suggestion'],
                    ['text' => 'Partnership inquiry'],
                    ['text' => 'Other'],
                ],
            ],
            'privacy_page' => [
                'title'    => 'Privacy Policy',
                'sections' => [
                    ['heading' => '1. Overview',                            'body' => 'OvuDay ("we", "our", "us") is committed to protecting your privacy. This policy explains what information we collect, how we use it, and your rights regarding your data.'],
                    ['heading' => '2. Information We Do NOT Collect',       'body' => "OvuDay's ovulation calculator runs entirely in your browser. We do not collect, store, or transmit:\n• Last period dates or cycle data entered into the calculator\n• Health or medical information of any kind\n• Personal identifying information (name, email) unless voluntarily submitted via our contact form"],
                    ['heading' => '3. Information We Collect Automatically','body' => "Like most websites, we may collect basic analytics data:\n• Pages visited and time spent\n• Approximate geographic location (country/region level)\n• Device type and browser\n• Referring URL\n\nThis data is anonymised and used only to improve the website."],
                    ['heading' => '4. Cookies',                            'body' => 'We use essential cookies required for basic website functionality. We may also use analytics cookies and advertising cookies (for example, Google AdSense) to measure performance and fund the service.'],
                    ['heading' => '5. Contact Form',                       'body' => 'If you contact us via our contact form, we receive your name, email, and message. This information is used solely to respond to your inquiry. Form submissions are processed through a third-party form delivery provider (FormSubmit).'],
                    ['heading' => '6. Third-Party Services',               'body' => 'Our website may use third-party services such as analytics, ad serving, contact form processing, and font delivery providers.'],
                    ['heading' => '7. Children\'s Privacy',                'body' => 'OvuDay is not directed at children under 13. We do not knowingly collect information from children.'],
                    ['heading' => '8. Your Rights',                        'body' => 'You have the right to request access to, correction of, or deletion of any personal data we hold about you. Contact us at our contact page to exercise these rights.'],
                    ['heading' => '9. Changes to This Policy',             'body' => 'We may update this Privacy Policy periodically. Changes are effective when posted on this page. Continued use of OvuDay after changes constitutes acceptance.'],
                    ['heading' => '10. Contact',                           'body' => 'For privacy-related questions, contact us via our contact page.'],
                ],
            ],
            'terms_page' => [
                'title'    => 'Terms of Use',
                'sections' => [
                    ['heading' => '1. Acceptance of Terms',       'body' => 'By accessing and using OvuDay ("the Service"), you agree to be bound by these Terms of Use. If you do not agree, please discontinue use of the Service.'],
                    ['heading' => '2. Not Medical Advice',        'body' => 'OvuDay provides educational information and estimation tools only. The ovulation calculator and all content on this website are not medical advice and should not be used as a substitute for professional medical guidance. Always consult a qualified healthcare professional for fertility, reproductive health, or contraception decisions.'],
                    ['heading' => '3. No Contraceptive Guarantee','body' => 'OvuDay is not a contraceptive method. The fertile window predictions are estimates only. We make no guarantee of the accuracy of ovulation predictions for the purpose of avoiding pregnancy.'],
                    ['heading' => '4. Use of the Service',        'body' => "You agree not to:\n• Use the Service for any unlawful purpose\n• Attempt to reverse engineer or copy the Service\n• Use automated tools to scrape or crawl the Service without permission"],
                    ['heading' => '5. Intellectual Property',     'body' => 'All content, design, and code on OvuDay is owned by OvuDay and protected by applicable copyright laws. You may not reproduce or distribute content without written permission.'],
                    ['heading' => '6. Disclaimer of Warranties',  'body' => 'The Service is provided "as is" without warranties of any kind. OvuDay does not warrant that the Service will be uninterrupted, error-free, or completely accurate.'],
                    ['heading' => '7. Limitation of Liability',   'body' => 'To the maximum extent permitted by law, OvuDay shall not be liable for any indirect, incidental, or consequential damages arising from use of the Service.'],
                    ['heading' => '8. Changes to Terms',          'body' => 'We reserve the right to modify these Terms at any time. Continued use of the Service after changes constitutes acceptance of the new Terms.'],
                    ['heading' => '9. Contact',                   'body' => 'For questions about these Terms, contact us via our contact page.'],
                ],
            ],
            'global' => [
                'ads_enabled'     => '0',
                'adsense_client'  => '',
                'ad_label'        => 'Advertisement',
                'sponsored_label' => 'Sponsored',
                'site_copy_json'  => wp_json_encode([
                    'about.story.title' => 'Why We Built OvuDay',
                    'about.story.p1' => 'Fertility tracking should not require subscriptions or sharing sensitive data.',
                    'about.story.p2' => 'OvuDay is built to be simple, respectful, and medically responsible.',
                    'contact.success' => 'Thanks for your message. We usually reply within 2 business days.',
                    'legal.privacy.cookies' => 'We use essential cookies and may use advertising cookies from Google AdSense to fund the site.',
                    'legal.privacy.thirdParties' => 'Third parties may process limited technical data when required for analytics, forms, and ads delivery.',
                    'blog.emptyState' => 'No articles available yet. Please check back soon.',
                    'blog.adDisclosure' => 'Some pages may include ads to keep OvuDay free.',
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
            ],
        ];
    }

    /* ══════════════════════════════════════════════════════════
       RENDER
    ══════════════════════════════════════════════════════════ */

    public function render_page(): void {
        if ( ! current_user_can('manage_options') ) return;
        settings_errors( $this->option );
        $d       = $this->get_content();
        $section = sanitize_key( $_GET['tab'] ?? 'hero' );

        $tab_groups = [
            'Homepage' => [
                'hero'       => ['🏠', 'Hero'],
                'navigation' => ['🧭', 'Navigation'],
                'trust'      => ['🛡️', 'Trust Badges'],
                'stats'      => ['📊', 'Stats'],
                'features'   => ['✨', 'Features'],
                'steps'      => ['🔢', 'How It Works'],
                'faq'        => ['❓', 'FAQ'],
                'calculator' => ['🧮', 'Calculator'],
                'cta'        => ['🎯', 'CTA Banner'],
            ],
            'Pages' => [
                'about_page'   => ['📖', 'About'],
                'how_page'     => ['🔬', 'How It Works'],
                'contact_page' => ['📬', 'Contact'],
                'privacy_page' => ['🔒', 'Privacy Policy'],
                'terms_page'   => ['📜', 'Terms of Use'],
            ],
            'Global' => [
                'blog'   => ['📝', 'Blog Settings'],
                'footer' => ['🦶', 'Footer'],
                'global' => ['🌐', 'Ads & Copy'],
            ],
        ];
        ?>
        <div class="ovd-layout">
            <aside class="ovd-sidebar">
                <div class="ovd-sidebar-brand">
                    <h2><span>🎨</span> OvuDay</h2>
                    <p>Content Builder</p>
                </div>
                <?php foreach ( $tab_groups as $group_label => $tabs ): ?>
                    <div class="ovd-sidebar-section">
                        <div class="ovd-sidebar-label"><?php echo esc_html($group_label); ?></div>
                        <?php foreach ( $tabs as $key => $item ): ?>
                            <a href="?page=<?php echo esc_attr($this->page); ?>&tab=<?php echo esc_attr($key); ?>"
                               class="<?php echo $section === $key ? 'active' : ''; ?>">
                                <span class="emoji"><?php echo $item[0]; ?></span> <?php echo esc_html($item[1]); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <div class="ovd-sidebar-section" style="margin-top:auto;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);">
                    <a href="<?php echo esc_url( admin_url('admin.php?page=ovuday-seo') ); ?>">
                        <span class="emoji">⚙️</span> Settings
                    </a>
                    <a href="<?php echo esc_url( admin_url('admin.php?page=ovuday-blog') ); ?>">
                        <span class="emoji">📝</span> Blog Posts
                    </a>
                </div>
            </aside>

            <div class="ovd-main">
                <div class="ovd-main-header">
                    <h1><?php
                        foreach ($tab_groups as $tabs) {
                            if (isset($tabs[$section])) { echo esc_html($tabs[$section][0] . ' ' . $tabs[$section][1]); break; }
                        }
                    ?></h1>
                    <p>Edit this section and click Save.</p>
                </div>

                <form method="post" action="">
                    <?php wp_nonce_field('ovuday_save_content', 'ovuday_content_nonce'); ?>
                    <input type="hidden" name="active_section" value="<?php echo esc_attr($section); ?>">

                    <div class="ovd-card">
                        <?php
                        switch ($section) {
                            case 'hero':       $this->render_hero($d['hero']);             break;
                            case 'navigation': $this->render_navigation($d['navigation']); break;
                            case 'trust':      $this->render_trust($d['trust']);           break;
                            case 'stats':      $this->render_stats($d['stats']);           break;
                            case 'features':   $this->render_features($d['features']);     break;
                            case 'steps':      $this->render_steps($d['steps']);           break;
                            case 'faq':        $this->render_faq($d['faq']);               break;
                            case 'calculator': $this->render_calculator($d['calculator']); break;
                            case 'cta':        $this->render_cta($d['cta']);               break;
                            case 'blog':       $this->render_blog($d['blog']);             break;
                            case 'footer':     $this->render_footer($d['footer']);         break;
                            case 'global':     $this->render_global($d['global'] ?? []);   break;
                            case 'about_page':   $this->render_about_page($d['about_page'] ?? []);     break;
                            case 'how_page':     $this->render_how_page($d['how_page'] ?? []);         break;
                            case 'contact_page': $this->render_contact_page($d['contact_page'] ?? []); break;
                            case 'privacy_page': $this->render_privacy_page($d['privacy_page'] ?? []); break;
                            case 'terms_page':   $this->render_terms_page($d['terms_page'] ?? []);     break;
                        }
                        ?>
                    </div>

                    <button type="submit" class="ovd-btn ovd-btn-primary">💾 Save Changes</button>
                </form>
            </div>
        </div>

        <style>
        /* Backward compat aliases — existing renderers use cb-* classes */
        .cb-row { margin-bottom:18px; }
        .cb-row label { display:block;font-size:13px;font-weight:600;color:var(--ovd-text);margin-bottom:6px; }
        .cb-row input[type=text],.cb-row input[type=url],.cb-row input[type=number],.cb-row textarea,.cb-row select { width:100%;padding:9px 14px;border:1px solid var(--ovd-border);border-radius:8px;font-size:13px;color:var(--ovd-text);box-sizing:border-box; }
        .cb-row input:focus,.cb-row textarea:focus,.cb-row select:focus { outline:none;border-color:var(--ovd-primary);box-shadow:0 0 0 3px rgba(232,71,110,.1); }
        .cb-row textarea { min-height:80px;resize:vertical; }
        .cb-row .help { font-size:11px;color:var(--ovd-muted);margin-top:4px; }
        .cb-section-title { font-size:14px;font-weight:700;color:var(--ovd-text);margin:24px 0 14px;padding-bottom:8px;border-bottom:1px solid var(--ovd-border); }
        .cb-section-title:first-child { margin-top:0; }
        .cb-grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:16px; }
        .cb-grid-3 { display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px; }
        @media(max-width:640px){.cb-grid-2,.cb-grid-3{grid-template-columns:1fr;}}
        .cb-repeater-item { background:var(--ovd-surface);border:1px solid var(--ovd-border);border-radius:8px;padding:16px;margin-bottom:10px;position:relative; }
        .cb-repeater-item .remove-item { position:absolute;top:10px;right:10px;background:#fee2e2;color:var(--ovd-danger);border:none;border-radius:6px;padding:4px 10px;cursor:pointer;font-size:11px;font-weight:600; }
        .cb-add-item { background:#f0fdf4;color:#16a34a;border:1px dashed #86efac;border-radius:8px;padding:10px 20px;cursor:pointer;width:100%;font-weight:600;font-size:13px;margin-top:6px;transition:background .15s; }
        .cb-add-item:hover { background:#dcfce7; }
        .cb-icon-grid { display:flex;flex-wrap:wrap;gap:6px;margin-top:8px; }
        .cb-icon-btn { padding:5px 10px;border:1px solid var(--ovd-border);border-radius:6px;cursor:pointer;font-size:11px;background:#fff;transition:all .15s; }
        .cb-icon-btn.selected,.cb-icon-btn:hover { border-color:var(--ovd-primary);background:var(--ovd-primary-light);color:var(--ovd-primary); }
        .cb-color-row { display:flex;align-items:center;gap:8px; }
        .cb-color-row input[type=color] { width:40px;height:36px;padding:2px;border:1px solid var(--ovd-border);border-radius:6px;cursor:pointer; }
        .cb-toggle { display:flex;align-items:center;gap:10px;margin-bottom:12px; }
        .cb-toggle input[type=checkbox] { width:18px;height:18px;accent-color:var(--ovd-primary);cursor:pointer; }
        </style>
        <?php
    }

    /* ── Section renderers ──────────────────────────────────── */

    private function f( string $name, string $value = '', string $type = 'text', string $label = '', string $help = '' ): void {
        ?>
        <div class="cb-row">
            <?php if ($label): ?><label><?= esc_html($label) ?></label><?php endif; ?>
            <?php if ($type === 'textarea'): ?>
                <textarea name="<?= esc_attr($name) ?>"><?= esc_textarea($value) ?></textarea>
            <?php else: ?>
                <input type="<?= esc_attr($type) ?>" name="<?= esc_attr($name) ?>" value="<?= esc_attr($value) ?>">
            <?php endif; ?>
            <?php if ($help): ?><p class="help"><?= esc_html($help) ?></p><?php endif; ?>
        </div>
        <?php
    }

    private function icon_picker( string $name, string $current ): void {
        $icons = ['ShieldCheck','Lock','Zap','Heart','Star','CalendarDays','Gift','Clock','Calendar',
                  'Check','Circle','Globe','Moon','Sun','Leaf','Flower','Sparkles','Award',
                  'Activity','AlertCircle','BookOpen','Brain','Clipboard','Eye','Home',
                  'Info','Lightbulb','Mail','Map','Phone','Smile','Target','Thermometer',
                  'TrendingUp','User','Users','Video','Watch','X','ArrowRight','ChevronDown'];
        echo '<div class="cb-row"><label>Icon</label>';
        echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($current) . '" class="icon-value">';
        echo '<div class="cb-icon-grid">';
        foreach ($icons as $icon) {
            $sel = $icon === $current ? 'selected' : '';
            echo '<button type="button" class="cb-icon-btn ' . $sel . '" data-icon="' . esc_attr($icon) . '">' . esc_html($icon) . '</button>';
        }
        echo '</div></div>';
    }

    private function render_hero( array $d ): void {
        echo '<div class="cb-section-title">Hero Section</div>';
        echo '<div class="cb-grid-2">';
        $this->f('hero[headline]',       $d['headline'],        'text', 'Main Headline', 'E.g. "Know Your"');
        $this->f('hero[headline_accent]',$d['headline_accent'], 'text', 'Accent Word',   'Highlighted in gradient color');
        echo '</div>';
        $this->f('hero[badge_text]',       $d['badge_text'],       'text',     'Top Badge Text');
        $this->f('hero[subheadline]',      $d['subheadline'],      'textarea', 'Subheadline / Description');
        echo '<div class="cb-grid-2">';
        $this->f('hero[cta_primary_text]', $d['cta_primary_text'], 'text', 'Primary Button Text');
        $this->f('hero[cta_primary_url]',  $d['cta_primary_url'],  'url',  'Primary Button URL');
        $this->f('hero[cta_secondary_text]',$d['cta_secondary_text'],'text','Secondary Button Text');
        $this->f('hero[cta_secondary_url]', $d['cta_secondary_url'], 'url', 'Secondary Button URL');
        echo '</div>';
        echo '<div class="cb-section-title">Background Gradient</div><div class="cb-grid-2">';
        echo '<div class="cb-row"><label>Gradient From</label><div class="cb-color-row"><input type="color" name="hero[bg_gradient_from]" value="' . esc_attr($d['bg_gradient_from']) . '"><input type="text" name="hero[bg_gradient_from]" value="' . esc_attr($d['bg_gradient_from']) . '"></div></div>';
        echo '<div class="cb-row"><label>Gradient To</label><div class="cb-color-row"><input type="color" name="hero[bg_gradient_to]" value="' . esc_attr($d['bg_gradient_to']) . '"><input type="text" name="hero[bg_gradient_to]" value="' . esc_attr($d['bg_gradient_to']) . '"></div></div>';
        echo '</div>';
    }

    private function render_navigation( array $d ): void {
        echo '<div class="cb-section-title">Header Navigation</div>';
        echo '<p class="help" style="margin-bottom:16px;">Configure the main navigation menu links.</p>';
        echo '<div id="nav-repeater">';
        foreach ( ($d['items'] ?? []) as $i => $item ) {
            $this->nav_item_html($i, $item);
        }
        echo '</div>';
        echo '<button type="button" class="cb-add-item" data-repeater="nav">+ Add Navigation Link</button>';
    }

    private function nav_item_html( int $i, array $item ): void {
        echo '<div class="cb-repeater-item">';
        echo '<button type="button" class="remove-item">Remove</button>';
        echo '<div class="cb-grid-2">';
        $this->f("navigation[items][{$i}][label]", $item['label'] ?? '', 'text', 'Link Text');
        $this->f("navigation[items][{$i}][url]",   $item['url']   ?? '', 'url',  'URL');
        echo '</div>';
        echo '</div>';
    }

    private function render_trust( array $d ): void {
        echo '<div class="cb-section-title">Trust Badge Pills</div>';
        echo '<p class="help" style="margin-bottom:16px;">These appear in the hero section below the headline. Drag to reorder.</p>';
        echo '<div id="trust-repeater">';
        foreach ( ($d['items'] ?? []) as $i => $item ) {
            $this->trust_item_html($i, $item);
        }
        echo '</div>';
        echo '<button type="button" class="cb-add-item" data-repeater="trust">+ Add Trust Badge</button>';
    }

    private function trust_item_html( int $i, array $item ): void {
        echo '<div class="cb-repeater-item">';
        echo '<button type="button" class="remove-item">Remove</button>';
        echo '<div class="cb-grid-3">';
        $this->icon_picker("trust[items][{$i}][icon]", $item['icon'] ?? 'ShieldCheck');
        $this->f("trust[items][{$i}][text]",  $item['text']  ?? '', 'text', 'Badge Text');
        echo '<div class="cb-row"><label>Color</label><div class="cb-color-row"><input type="color" name="trust[items][' . $i . '][color]" value="' . esc_attr($item['color'] ?? '#10b981') . '"><input type="text" name="trust[items][' . $i . '][color]" value="' . esc_attr($item['color'] ?? '#10b981') . '"></div></div>';
        echo '</div></div>';
    }

    private function render_stats( array $d ): void {
        echo '<div class="cb-section-title">Stats Row</div>';
        echo '<div id="stats-repeater">';
        foreach ( ($d['items'] ?? []) as $i => $item ) {
            echo '<div class="cb-repeater-item"><button type="button" class="remove-item">Remove</button><div class="cb-grid-3">';
            $this->f("stats[items][{$i}][value]",  $item['value']  ?? '', 'text', 'Number / Value');
            $this->f("stats[items][{$i}][suffix]", $item['suffix'] ?? '', 'text', 'Suffix (%, K, ★)');
            $this->f("stats[items][{$i}][label]",  $item['label']  ?? '', 'text', 'Label below number');
            echo '</div></div>';
        }
        echo '</div>';
        echo '<button type="button" class="cb-add-item" data-repeater="stats">+ Add Stat</button>';
    }

    private function render_features( array $d ): void {
        echo '<div class="cb-section-title">Section Header</div><div class="cb-grid-2">';
        $this->f('features[section_title]',    $d['section_title']    ?? '', 'text', 'Section Title');
        $this->f('features[section_subtitle]', $d['section_subtitle'] ?? '', 'text', 'Section Subtitle');
        echo '</div><div class="cb-section-title">Feature Cards</div><div id="features-repeater">';
        foreach ( ($d['items'] ?? []) as $i => $item ) {
            echo '<div class="cb-repeater-item"><button type="button" class="remove-item">Remove</button>';
            echo '<div class="cb-grid-2">';
            $this->icon_picker("features[items][{$i}][icon]", $item['icon'] ?? 'Star');
            echo '<div class="cb-row"><label>Card Accent Color</label><div class="cb-color-row"><input type="color" name="features[items][' . $i . '][color]" value="' . esc_attr($item['color'] ?? '#E8476E') . '"><input type="text" name="features[items][' . $i . '][color]" value="' . esc_attr($item['color'] ?? '#E8476E') . '"></div></div>';
            $this->f("features[items][{$i}][title]",       $item['title']       ?? '', 'text',     'Feature Title');
            $this->f("features[items][{$i}][description]", $item['description'] ?? '', 'textarea', 'Description');
            echo '</div></div>';
        }
        echo '</div>';
        echo '<button type="button" class="cb-add-item" data-repeater="features">+ Add Feature Card</button>';
    }

    private function render_steps( array $d ): void {
        echo '<div class="cb-section-title">Section Header</div><div class="cb-grid-2">';
        $this->f('steps[section_title]',    $d['section_title']    ?? '', 'text', 'Section Title');
        $this->f('steps[section_subtitle]', $d['section_subtitle'] ?? '', 'text', 'Section Subtitle');
        echo '</div><div class="cb-section-title">Steps</div><div id="steps-repeater">';
        foreach ( ($d['items'] ?? []) as $i => $item ) {
            echo '<div class="cb-repeater-item"><button type="button" class="remove-item">Remove</button><div class="cb-grid-2">';
            $this->f("steps[items][{$i}][number]",      $item['number']      ?? (string)($i+1), 'text',     'Step Number / Label');
            $this->icon_picker("steps[items][{$i}][icon]", $item['icon'] ?? 'Circle');
            $this->f("steps[items][{$i}][title]",       $item['title']       ?? '', 'text',     'Step Title');
            $this->f("steps[items][{$i}][description]", $item['description'] ?? '', 'textarea', 'Step Description');
            echo '</div></div>';
        }
        echo '</div>';
        echo '<button type="button" class="cb-add-item" data-repeater="steps">+ Add Step</button>';
    }

    private function render_faq( array $d ): void {
        echo '<div class="cb-section-title">Section Header</div><div class="cb-grid-2">';
        $this->f('faq[section_title]',    $d['section_title']    ?? '', 'text', 'Section Title');
        $this->f('faq[section_subtitle]', $d['section_subtitle'] ?? '', 'text', 'Section Subtitle');
        echo '</div><div class="cb-section-title">FAQ Items</div><div id="faq-repeater">';
        foreach ( ($d['items'] ?? []) as $i => $item ) {
            echo '<div class="cb-repeater-item"><button type="button" class="remove-item">Remove</button>';
            $this->f("faq[items][{$i}][question]", $item['question'] ?? '', 'text',     'Question');
            $this->f("faq[items][{$i}][answer]",   $item['answer']   ?? '', 'textarea', 'Answer (HTML allowed)');
            echo '<div class="cb-row"><label>Category (for filtering)</label><input type="text" name="faq[items][' . $i . '][category]" value="' . esc_attr($item['category'] ?? 'general') . '" placeholder="general, fertility, privacy, accuracy..."></div>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="cb-add-item" data-repeater="faq">+ Add FAQ Item</button>';
    }

    private function render_calculator( array $d ): void {
        echo '<div class="cb-section-title">Calculator Card</div><div class="cb-grid-2">';
        $this->f('calculator[title]',    $d['title']    ?? '', 'text',     'Calculator Title');
        $this->f('calculator[subtitle]', $d['subtitle'] ?? '', 'textarea', 'Calculator Subtitle');
        echo '</div><div class="cb-section-title">Field Labels</div><div class="cb-grid-2">';
        $this->f('calculator[lmp_label]',   $d['lmp_label']   ?? '', 'text', 'LMP Field Label');
        $this->f('calculator[lmp_help]',    $d['lmp_help']    ?? '', 'text', 'LMP Tooltip Text');
        $this->f('calculator[cycle_label]', $d['cycle_label'] ?? '', 'text', 'Cycle Length Label');
        $this->f('calculator[cycle_help]',  $d['cycle_help']  ?? '', 'text', 'Cycle Length Tooltip');
        $this->f('calculator[luteal_label]',$d['luteal_label']?? '', 'text', 'Luteal Phase Label');
        $this->f('calculator[luteal_help]', $d['luteal_help'] ?? '', 'text', 'Luteal Phase Tooltip');
        echo '</div><div class="cb-section-title">Buttons</div><div class="cb-grid-2">';
        $this->f('calculator[calculate_btn]', $d['calculate_btn'] ?? '', 'text', 'Calculate Button');
        $this->f('calculator[reset_btn]',     $d['reset_btn']     ?? '', 'text', 'Reset Button');
        echo '</div><div class="cb-section-title">Result Labels</div><div class="cb-grid-2">';
        $this->f('calculator[result_title]',         $d['result_title']         ?? '', 'text', 'Results Panel Title');
        $this->f('calculator[cycle_overview_label]', $d['cycle_overview_label'] ?? '', 'text', 'Cycle Overview Label');
        $this->f('calculator[fertile_window_title]', $d['fertile_window_title'] ?? '', 'text', 'Fertile Window Title');
        $this->f('calculator[fertile_window_label]', $d['fertile_window_label'] ?? '', 'text', 'Fertile Window Label');
        $this->f('calculator[ovulation_day_label]',  $d['ovulation_day_label']  ?? '', 'text', 'Ovulation Day Label');
        $this->f('calculator[next_period_label]',    $d['next_period_label']    ?? '', 'text', 'Next Period Label');
        $this->f('calculator[peak_day_label]',       $d['peak_day_label']       ?? '', 'text', 'Peak Fertility Label');
        $this->f('calculator[tab_current]',          $d['tab_current']          ?? '', 'text', 'Tab: Current Cycle');
        $this->f('calculator[tab_next_cycles]',      $d['tab_next_cycles']      ?? '', 'text', 'Tab: Next 3 Cycles');
        echo '</div>';
        $this->f('calculator[tip_text]',    $d['tip_text']    ?? '', 'textarea', 'Tip Box Text');
        $this->f('calculator[privacy_note]',$d['privacy_note']?? '', 'text',     'Privacy Note');
        $this->f('calculator[disclaimer]',  $d['disclaimer']  ?? '', 'textarea', 'Medical Disclaimer');
    }

    private function render_cta( array $d ): void {
        echo '<div class="cb-section-title">CTA Banner</div>';
        $this->f('cta[title]',   $d['title']   ?? '', 'text',     'CTA Title');
        $this->f('cta[subtitle]',$d['subtitle'] ?? '', 'textarea', 'CTA Subtitle');
        echo '<div class="cb-grid-2">';
        $this->f('cta[btn_text]',$d['btn_text'] ?? '', 'text', 'Button Text');
        $this->f('cta[btn_url]', $d['btn_url']  ?? '', 'url',  'Button URL');
        echo '<div class="cb-row"><label>Background Color</label><div class="cb-color-row"><input type="color" name="cta[bg_color]" value="' . esc_attr($d['bg_color'] ?? '#E8476E') . '"><input type="text" name="cta[bg_color]" value="' . esc_attr($d['bg_color'] ?? '#E8476E') . '"></div></div>';
        echo '<div class="cb-row"><label>Text Color</label><div class="cb-color-row"><input type="color" name="cta[text_color]" value="' . esc_attr($d['text_color'] ?? '#ffffff') . '"><input type="text" name="cta[text_color]" value="' . esc_attr($d['text_color'] ?? '#ffffff') . '"></div></div>';
        echo '</div>';
    }

    private function render_blog( array $d ): void {
        echo '<div class="cb-section-title">Blog Listing Page</div><div class="cb-grid-2">';
        $this->f('blog[listing_badge]',   $d['listing_badge']   ?? '', 'text', 'Badge Text');
        $this->f('blog[listing_title]',   $d['listing_title']   ?? '', 'text', 'Page Title');
        $this->f('blog[listing_subtitle]',$d['listing_subtitle'] ?? '', 'text', 'Page Subtitle');
        $this->f('blog[posts_per_page]',  (string)($d['posts_per_page'] ?? 12), 'number', 'Posts Per Page');
        echo '<div class="cb-row"><label>Layout</label><select name="blog[layout]"><option value="grid" ' . selected($d['layout']??'grid','grid',false) . '>Grid (3 cols)</option><option value="list" ' . selected($d['layout']??'grid','list',false) . '>List</option><option value="masonry" ' . selected($d['layout']??'grid','masonry',false) . '>Masonry</option></select></div>';
        $this->f('blog[excerpt_length]',  (string)($d['excerpt_length'] ?? 25), 'number', 'Excerpt Word Count');
        $this->f('blog[no_posts_text]',   $d['no_posts_text']   ?? '', 'text', 'No Posts Found Text');
        $this->f('blog[read_more_text]',  $d['read_more_text']  ?? '', 'text', 'Read More Button Text');
        echo '</div>';
        echo '<div class="cb-section-title">Show / Hide on Card</div>';
        $opts = ['show_author'=>'Author name','show_date'=>'Publish date','show_category'=>'Category badge','show_reading_time'=>'Reading time','show_tags'=>'Tags','featured_post'=>'Feature top post'];
        foreach ($opts as $key => $label) {
            echo '<div class="cb-toggle"><input type="checkbox" name="blog[' . $key . ']" id="blog_' . $key . '" value="1" ' . checked($d[$key]??'1','1',false) . '><label for="blog_' . $key . '" style="font-weight:400;">' . esc_html($label) . '</label></div>';
        }
        echo '<div class="cb-section-title">Blog Post Detail Page</div>';
        $detail_opts = ['detail_show_author_box'=>'Author info box','detail_show_related'=>'Related articles','detail_show_breadcrumb'=>'Breadcrumb nav','detail_show_share'=>'Social share buttons','detail_show_toc'=>'Table of contents'];
        foreach ($detail_opts as $key => $label) {
            echo '<div class="cb-toggle"><input type="checkbox" name="blog[' . $key . ']" id="blog_' . $key . '" value="1" ' . checked($d[$key]??'1','1',false) . '><label for="blog_' . $key . '" style="font-weight:400;">' . esc_html($label) . '</label></div>';
        }
        echo '<div class="cb-grid-2" style="margin-top:16px;">';
        $this->f('blog[detail_toc_title]',      $d['detail_toc_title']      ?? '', 'text',   'TOC Box Title');
        $this->f('blog[detail_related_title]',  $d['detail_related_title']  ?? '', 'text',   'Related Articles Title');
        $this->f('blog[detail_related_count]',  (string)($d['detail_related_count'] ?? 3), 'number', 'Related Articles Count');
        $this->f('blog[detail_share_label]',    $d['detail_share_label']    ?? '', 'text',   'Share Label');
        $this->f('blog[detail_back_to_blog_text]', $d['detail_back_to_blog_text'] ?? '', 'text', 'Back-to-blog Button Text');
        echo '</div>';
        $this->f('blog[detail_medical_disclaimer]', $d['detail_medical_disclaimer'] ?? '', 'textarea', 'Medical Disclaimer (shown at bottom of every post)');

        echo '<div class="cb-section-title">Ad Slots (AdSense Ready)</div><div class="cb-grid-2">';
        $this->f('blog[listing_top_ad_slot]',    $d['listing_top_ad_slot']    ?? '', 'text', 'Listing Top Ad Slot ID');
        $this->f('blog[listing_inline_ad_slot]', $d['listing_inline_ad_slot'] ?? '', 'text', 'Listing Inline Ad Slot ID');
        $this->f('blog[listing_inline_every]',   (string)($d['listing_inline_every'] ?? 4), 'number', 'Insert Inline Ad Every N Cards');
        $this->f('blog[detail_top_ad_slot]',     $d['detail_top_ad_slot']     ?? '', 'text', 'Detail Top Ad Slot ID');
        $this->f('blog[detail_inline_ad_slot]',  $d['detail_inline_ad_slot']  ?? '', 'text', 'Detail Inline Ad Slot ID');
        $this->f('blog[detail_sidebar_ad_slot]', $d['detail_sidebar_ad_slot'] ?? '', 'text', 'Detail Sidebar Ad Slot ID');
        $this->f('blog[detail_bottom_ad_slot]',  $d['detail_bottom_ad_slot']  ?? '', 'text', 'Detail Bottom Ad Slot ID');
        echo '</div>';
    }

    private function render_global( array $d ): void {
        echo '<div class="cb-section-title">Global Ads Settings</div>';
        echo '<div class="cb-toggle"><input type="checkbox" name="global[ads_enabled]" id="global_ads_enabled" value="1" ' . checked($d['ads_enabled'] ?? '0', '1', false) . '><label for="global_ads_enabled">Enable ad slots site-wide</label></div>';
        echo '<div class="cb-grid-2">';
        $this->f('global[adsense_client]', $d['adsense_client'] ?? '', 'text', 'AdSense Client ID', 'Format: ca-pub-xxxxxxxxxxxxxxxx');
        $this->f('global[ad_label]', $d['ad_label'] ?? 'Advertisement', 'text', 'Ad Label');
        $this->f('global[sponsored_label]', $d['sponsored_label'] ?? 'Sponsored', 'text', 'Sponsored Label');
        echo '</div>';

        $this->f(
            'global[site_copy_json]',
            $d['site_copy_json'] ?? '',
            'textarea',
            'Site Copy JSON Dictionary',
            'Use this to control text across pages. Example: {"blog.emptyState":"No articles yet"}'
        );
    }

    private function render_footer( array $d ): void {
        echo '<div class="cb-section-title">Brand</div><div class="cb-grid-2">';
        $this->f('footer[logo_text]', $d['logo_text'] ?? '', 'text',     'Logo / Brand Name');
        $this->f('footer[tagline]',   $d['tagline']   ?? '', 'text',     'Tagline under logo');
        $this->f('footer[copyright]', $d['copyright'] ?? '', 'text',     'Copyright Line');
        echo '</div>';
        $this->f('footer[disclaimer]', $d['disclaimer'] ?? '', 'textarea', 'Medical Disclaimer Text');
        echo '<div class="cb-section-title">Social Links</div><div class="cb-grid-2">';
        $this->f('footer[social_twitter]',   $d['social_twitter']  ?? '', 'url', 'Twitter / X URL');
        $this->f('footer[social_facebook]',  $d['social_facebook'] ?? '', 'url', 'Facebook URL');
        $this->f('footer[social_instagram]', $d['social_instagram']?? '', 'url', 'Instagram URL');
        $this->f('footer[social_pinterest]', $d['social_pinterest']?? '', 'url', 'Pinterest URL');
        echo '</div>';
        echo '<div class="cb-section-title">Newsletter Bar</div>';
        echo '<div class="cb-toggle"><input type="checkbox" name="footer[newsletter_enable]" id="newsletter_enable" value="1" ' . checked($d['newsletter_enable']??'1','1',false) . '><label for="newsletter_enable">Enable newsletter signup bar</label></div>';
        echo '<div class="cb-grid-3">';
        $this->f('footer[newsletter_title]',      $d['newsletter_title']       ?? '', 'text', 'Bar Title');
        $this->f('footer[newsletter_placeholder]',$d['newsletter_placeholder'] ?? '', 'text', 'Input Placeholder');
        $this->f('footer[newsletter_btn]',        $d['newsletter_btn']         ?? '', 'text', 'Button Text');
        echo '</div>';
        echo '<div class="cb-section-title">Footer Link Columns</div>';
        echo '<p class="help" style="margin-bottom:12px;">Up to 3 columns. Each column has a title and a list of links.</p>';
        echo '<div id="footer-col-repeater">';
        foreach ( ($d['links'] ?? []) as $ci => $col ) {
            echo '<div class="cb-repeater-item"><button type="button" class="remove-item">Remove Column</button>';
            $this->f("footer[links][{$ci}][title]", $col['title'] ?? '', 'text', 'Column Heading');
            echo '<div id="footer-link-repeater-' . $ci . '">';
            foreach ( ($col['items'] ?? []) as $li => $link ) {
                echo '<div class="cb-repeater-item" style="background:#fff;"><button type="button" class="remove-item">✕</button><div class="cb-grid-2">';
                $this->f("footer[links][{$ci}][items][{$li}][label]", $link['label'] ?? '', 'text', 'Link Label');
                $this->f("footer[links][{$ci}][items][{$li}][url]",   $link['url']   ?? '', 'url',  'Link URL');
                echo '</div></div>';
            }
            echo '</div>';
            echo '<button type="button" class="cb-add-item" style="margin-top:8px;" data-repeater="footer-link-' . $ci . '">+ Add Link</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" class="cb-add-item" data-repeater="footer-col" style="margin-top:8px;">+ Add Footer Column</button>';
    }

    /* ── Page-specific section renderers ─────────────────────── */

    private function render_about_page( array $d ): void {
        echo '<div class="cb-section-title">About Page — Hero</div><div class="cb-grid-2">';
        $this->f('about_page[badge]', $d['badge'] ?? '', 'text', 'Badge Text');
        $this->f('about_page[title]', $d['title'] ?? '', 'text', 'Page Title');
        echo '</div>';
        $this->f('about_page[intro]', $d['intro'] ?? '', 'textarea', 'Introduction Paragraph');
        echo '<div class="cb-section-title">Story Section</div>';
        $this->f('about_page[story_title]', $d['story_title'] ?? '', 'text', 'Story Section Title');
        $this->f('about_page[story_p1]', $d['story_p1'] ?? '', 'textarea', 'Story Paragraph 1');
        $this->f('about_page[story_p2]', $d['story_p2'] ?? '', 'textarea', 'Story Paragraph 2');
        echo '<div class="cb-section-title">Values</div>';
        $this->f('about_page[values_title]', $d['values_title'] ?? '', 'text', 'Values Section Title');
        echo '<div id="about-values-repeater">';
        foreach ( ($d['values'] ?? []) as $i => $item ) {
            echo '<div class="cb-repeater-item"><button type="button" class="remove-item">Remove</button><div class="cb-grid-2">';
            $this->f("about_page[values][{$i}][icon]",  $item['icon']  ?? '🔒', 'text',     'Emoji Icon');
            $this->f("about_page[values][{$i}][title]", $item['title'] ?? '',    'text',     'Value Title');
            echo '</div>';
            $this->f("about_page[values][{$i}][description]", $item['description'] ?? '', 'textarea', 'Description');
            echo '</div>';
        }
        echo '</div><button type="button" class="cb-add-item" data-repeater="about-values">+ Add Value</button>';
        echo '<div class="cb-section-title">Disclaimer & CTAs</div>';
        $this->f('about_page[disclaimer]', $d['disclaimer'] ?? '', 'textarea', 'Medical Disclaimer');
        echo '<div class="cb-grid-2">';
        $this->f('about_page[cta_primary_text]',   $d['cta_primary_text']   ?? '', 'text', 'Primary Button Text');
        $this->f('about_page[cta_primary_url]',    $d['cta_primary_url']    ?? '', 'url',  'Primary Button URL');
        $this->f('about_page[cta_secondary_text]', $d['cta_secondary_text'] ?? '', 'text', 'Secondary Button Text');
        $this->f('about_page[cta_secondary_url]',  $d['cta_secondary_url']  ?? '', 'url',  'Secondary Button URL');
        echo '</div>';
    }

    private function render_how_page( array $d ): void {
        echo '<div class="cb-section-title">How It Works — Header</div><div class="cb-grid-2">';
        $this->f('how_page[badge]', $d['badge'] ?? '', 'text', 'Badge Text');
        $this->f('how_page[title]', $d['title'] ?? '', 'text', 'Page Title');
        echo '</div>';
        $this->f('how_page[intro]', $d['intro'] ?? '', 'textarea', 'Introduction Text');
        echo '<div class="cb-section-title">Cycle Phases</div><div class="cb-grid-2">';
        $this->f('how_page[phases_title]',    $d['phases_title']    ?? '', 'text', 'Phases Section Title');
        $this->f('how_page[phases_subtitle]', $d['phases_subtitle'] ?? '', 'text', 'Phases Subtitle');
        echo '</div><div id="phases-repeater">';
        foreach ( ($d['phases'] ?? []) as $i => $item ) {
            echo '<div class="cb-repeater-item"><button type="button" class="remove-item">Remove</button><div class="cb-grid-2">';
            $this->f("how_page[phases][{$i}][name]", $item['name'] ?? '', 'text', 'Phase Name');
            $this->f("how_page[phases][{$i}][days]", $item['days'] ?? '', 'text', 'Day Range');
            echo '</div>';
            $this->f("how_page[phases][{$i}][description]", $item['description'] ?? '', 'textarea', 'Description');
            echo '<div class="cb-grid-2">';
            echo '<div class="cb-row"><label>Background Color</label><div class="cb-color-row"><input type="color" name="how_page[phases][' . $i . '][color]" value="' . esc_attr($item['color'] ?? '#FEE2E2') . '"><input type="text" name="how_page[phases][' . $i . '][color]" value="' . esc_attr($item['color'] ?? '#FEE2E2') . '"></div></div>';
            echo '<div class="cb-row"><label>Text Color</label><div class="cb-color-row"><input type="color" name="how_page[phases][' . $i . '][text_color]" value="' . esc_attr($item['text_color'] ?? '#991B1B') . '"><input type="text" name="how_page[phases][' . $i . '][text_color]" value="' . esc_attr($item['text_color'] ?? '#991B1B') . '"></div></div>';
            echo '</div></div>';
        }
        echo '</div><button type="button" class="cb-add-item" data-repeater="phases">+ Add Phase</button>';
        echo '<div class="cb-section-title">Formula Section</div>';
        $this->f('how_page[formula_title]',   $d['formula_title']   ?? '', 'text',     'Formula Section Title');
        $this->f('how_page[formula_example]', $d['formula_example'] ?? '', 'textarea', 'Example Calculation');
        echo '<div class="cb-section-title">Fertile Window</div>';
        $this->f('how_page[fertile_title]',       $d['fertile_title']       ?? '', 'text',     'Fertile Window Title');
        $this->f('how_page[fertile_explanation]', $d['fertile_explanation'] ?? '', 'textarea', 'Explanation Text');
        echo '<div class="cb-section-title">Limitations</div>';
        $this->f('how_page[limitations_title]', $d['limitations_title'] ?? '', 'text', 'Section Title');
        echo '<div id="limitations-repeater">';
        foreach ( ($d['limitations'] ?? []) as $i => $item ) {
            echo '<div class="cb-repeater-item"><button type="button" class="remove-item">Remove</button>';
            $this->f("how_page[limitations][{$i}][text]", $item['text'] ?? '', 'textarea', 'Limitation');
            echo '</div>';
        }
        echo '</div><button type="button" class="cb-add-item" data-repeater="limitations">+ Add Limitation</button>';
        echo '<div class="cb-section-title">CTA</div><div class="cb-grid-2">';
        $this->f('how_page[cta_text]',     $d['cta_text']     ?? '', 'text', 'CTA Heading');
        $this->f('how_page[cta_btn_text]', $d['cta_btn_text'] ?? '', 'text', 'Button Text');
        $this->f('how_page[cta_btn_url]',  $d['cta_btn_url']  ?? '', 'url',  'Button URL');
        echo '</div>';
    }

    private function render_contact_page( array $d ): void {
        echo '<div class="cb-section-title">Contact Page</div><div class="cb-grid-2">';
        $this->f('contact_page[badge]', $d['badge'] ?? '', 'text', 'Badge Text');
        $this->f('contact_page[title]', $d['title'] ?? '', 'text', 'Page Title');
        echo '</div>';
        $this->f('contact_page[intro]', $d['intro'] ?? '', 'textarea', 'Introduction Text');
        echo '<div class="cb-section-title">Form Settings</div>';
        $this->f('contact_page[form_email]',   $d['form_email']   ?? '', 'text', 'Contact Email (FormSubmit)');
        $this->f('contact_page[form_subject]', $d['form_subject'] ?? '', 'text', 'Email Subject Line');
        echo '<div class="cb-section-title">Subject Dropdown Options</div><div id="subjects-repeater">';
        foreach ( ($d['subjects'] ?? []) as $i => $item ) {
            echo '<div class="cb-repeater-item"><button type="button" class="remove-item">Remove</button>';
            $this->f("contact_page[subjects][{$i}][text]", $item['text'] ?? '', 'text', 'Subject Option');
            echo '</div>';
        }
        echo '</div><button type="button" class="cb-add-item" data-repeater="subjects">+ Add Subject</button>';
        echo '<div class="cb-section-title">Messages</div>';
        $this->f('contact_page[success_message]', $d['success_message'] ?? '', 'textarea', 'Success Message (after submit)');
        $this->f('contact_page[response_time]',   $d['response_time']   ?? '', 'textarea', 'Response Time Note');
    }

    private function render_legal_page( string $key, string $label, array $d ): void {
        echo '<div class="cb-section-title">' . esc_html($label) . '</div>';
        $this->f("{$key}[title]", $d['title'] ?? '', 'text', 'Page Title');
        echo '<div class="cb-section-title">Sections</div><p class="help" style="margin-bottom:12px;">Each section is a heading + body block. HTML is allowed in body.</p>';
        echo '<div id="' . esc_attr($key) . '-sections-repeater">';
        foreach ( ($d['sections'] ?? []) as $i => $item ) {
            echo '<div class="cb-repeater-item"><button type="button" class="remove-item">Remove</button>';
            $this->f("{$key}[sections][{$i}][heading]", $item['heading'] ?? '', 'text',     'Section Heading');
            $this->f("{$key}[sections][{$i}][body]",    $item['body']    ?? '', 'textarea', 'Section Body (HTML allowed)');
            echo '</div>';
        }
        echo '</div><button type="button" class="cb-add-item" data-repeater="' . esc_attr($key) . '-sections">+ Add Section</button>';
    }

    private function render_privacy_page( array $d ): void { $this->render_legal_page('privacy_page', 'Privacy Policy', $d); }
    private function render_terms_page( array $d ): void   { $this->render_legal_page('terms_page',   'Terms of Use',   $d); }
}
