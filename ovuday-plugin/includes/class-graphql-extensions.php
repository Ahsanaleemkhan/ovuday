<?php
namespace OvuDay;
defined('ABSPATH') || exit;

/**
 * Extends WPGraphQL with every OvuDay SEO field + CORS headers.
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  Query example (Next.js / Apollo):                              │
 * │                                                                 │
 * │  query GetPost($slug: String!) {                                │
 * │    postBy(slug: $slug) {                                        │
 * │      title content                                              │
 * │      ovudaySeo {                                                │
 * │        title metaDescription focusKeyword canonical             │
 * │        robotsString readingTime seoScore schemaJson             │
 * │        robots { noindex nofollow noarchive }                    │
 * │        og { title description image imageWidth imageHeight }    │
 * │        twitter { card title description image site }            │
 * │        schema { type reviewedBy authorName customJson }         │
 * │        sitemap { exclude priority changefreq }                  │
 * │        redirect { url type }                                    │
 * │        analysis {                                               │
 * │          wordCount keywordDensity titleLength descLength        │
 * │          keywordInTitle keywordInDesc keywordInContent          │
 * │          hasMetaDesc hasOgImage hasFeaturedImage                │
 * │        }                                                        │
 * │      }                                                          │
 * │    }                                                            │
 * │  }                                                              │
 * │                                                                 │
 * │  query GetGlobalSeo {                                           │
 * │    ovudayGlobalSeo {                                            │
 * │      siteName homeTitle homeDescription homeOgImage             │
 * │      twitterUsername facebookUrl organizationLogo               │
 * │      googleAnalytics googleTagManager                           │
 * │      breadcrumbsEnable breadcrumbsHome breadcrumbsSep           │
 * │      sitemapEnable noindexSearch noindexDate                    │
 * │    }                                                            │
 * │  }                                                              │
 * └─────────────────────────────────────────────────────────────────┘
 */
class GraphQL_Extensions {

    public function __construct() {
        add_action( 'graphql_register_types',   [ $this, 'register_types' ] );
        add_action( 'graphql_response_headers', [ $this, 'add_cors_headers' ] );
        add_filter( 'graphql_jwt_auth_secret_key', [ $this, 'jwt_secret' ] );
    }

    /* ═══════════════════════════════════════════════════════
       TYPE REGISTRATION
    ═══════════════════════════════════════════════════════ */

    public function register_types(): void {
        $this->register_robots_type();
        $this->register_og_type();
        $this->register_twitter_type();
        $this->register_schema_type();
        $this->register_sitemap_type();
        $this->register_redirect_type();
        $this->register_analysis_type();
        $this->register_seo_type();
        $this->register_global_seo_type();

        // Content Builder types
        $this->register_content_types();

        $this->extend_post_type();
        $this->extend_page_type();

        $this->add_global_seo_query();
        $this->add_content_query();
        $this->add_legacy_settings_query(); // keep backwards-compat
    }

    /* ═══════════════════════════════════════════════════════
       CONTENT BUILDER TYPES
    ═══════════════════════════════════════════════════════ */

    private function register_content_types(): void {
        // Generic key-value item
        register_graphql_object_type( 'OvuDayKeyValue', [
            'description' => 'Generic key-value pair',
            'fields' => [
                'key'   => [ 'type' => 'String' ],
                'value' => [ 'type' => 'String' ],
            ],
        ]);

        // Trust badge
        register_graphql_object_type( 'OvuDayTrustBadge', [
            'description' => 'Hero trust badge pill',
            'fields' => [
                'icon'  => [ 'type' => 'String', 'description' => 'Lucide icon name' ],
                'text'  => [ 'type' => 'String' ],
                'color' => [ 'type' => 'String', 'description' => 'CSS hex color' ],
            ],
        ]);

        // Stat item
        register_graphql_object_type( 'OvuDayStatItem', [
            'description' => 'A single stat (500K+ women)',
            'fields' => [
                'value'  => [ 'type' => 'String' ],
                'suffix' => [ 'type' => 'String' ],
                'label'  => [ 'type' => 'String' ],
            ],
        ]);

        // Feature card
        register_graphql_object_type( 'OvuDayFeatureItem', [
            'description' => 'Feature card',
            'fields' => [
                'icon'        => [ 'type' => 'String' ],
                'title'       => [ 'type' => 'String' ],
                'description' => [ 'type' => 'String' ],
                'color'       => [ 'type' => 'String' ],
            ],
        ]);

        // Step
        register_graphql_object_type( 'OvuDayStepItem', [
            'description' => 'How-it-works step',
            'fields' => [
                'number'      => [ 'type' => 'String' ],
                'icon'        => [ 'type' => 'String' ],
                'title'       => [ 'type' => 'String' ],
                'description' => [ 'type' => 'String' ],
            ],
        ]);

        // FAQ item
        register_graphql_object_type( 'OvuDayFaqItem', [
            'description' => 'FAQ question and answer',
            'fields' => [
                'question' => [ 'type' => 'String' ],
                'answer'   => [ 'type' => 'String' ],
                'category' => [ 'type' => 'String' ],
            ],
        ]);

        // Footer link
        register_graphql_object_type( 'OvuDayFooterLink', [
            'description' => 'A single footer nav link',
            'fields' => [
                'label' => [ 'type' => 'String' ],
                'url'   => [ 'type' => 'String' ],
            ],
        ]);

        // Footer column
        register_graphql_object_type( 'OvuDayFooterColumn', [
            'description' => 'A footer link column',
            'fields' => [
                'title' => [ 'type' => 'String' ],
                'items' => [ 'type' => [ 'list_of' => 'OvuDayFooterLink' ] ],
            ],
        ]);

        // Navigation link
        register_graphql_object_type( 'OvuDayNavLink', [
            'description' => 'A single navigation link',
            'fields' => [
                'label' => [ 'type' => 'String' ],
                'url'   => [ 'type' => 'String' ],
            ],
        ]);

        // Hero
        register_graphql_object_type( 'OvuDayHero', [
            'description' => 'Hero section content',
            'fields' => [
                'badgeText'         => [ 'type' => 'String' ],
                'headline'          => [ 'type' => 'String' ],
                'headlineAccent'    => [ 'type' => 'String' ],
                'subheadline'       => [ 'type' => 'String' ],
                'ctaPrimaryText'    => [ 'type' => 'String' ],
                'ctaPrimaryUrl'     => [ 'type' => 'String' ],
                'ctaSecondaryText'  => [ 'type' => 'String' ],
                'ctaSecondaryUrl'   => [ 'type' => 'String' ],
                'bgGradientFrom'    => [ 'type' => 'String' ],
                'bgGradientTo'      => [ 'type' => 'String' ],
            ],
        ]);

        // Calculator labels
        register_graphql_object_type( 'OvuDayCalculatorContent', [
            'description' => 'All calculator UI text',
            'fields' => [
                'title'               => [ 'type' => 'String' ],
                'subtitle'            => [ 'type' => 'String' ],
                'lmpLabel'            => [ 'type' => 'String' ],
                'lmpHelp'             => [ 'type' => 'String' ],
                'cycleLabel'          => [ 'type' => 'String' ],
                'cycleHelp'           => [ 'type' => 'String' ],
                'lutealLabel'         => [ 'type' => 'String' ],
                'lutealHelp'          => [ 'type' => 'String' ],
                'calculateBtn'        => [ 'type' => 'String' ],
                'resetBtn'            => [ 'type' => 'String' ],
                'resultTitle'         => [ 'type' => 'String' ],
                'cycleOverviewLabel'  => [ 'type' => 'String' ],
                'fertileWindowTitle'  => [ 'type' => 'String' ],
                'fertileWindowLabel'  => [ 'type' => 'String' ],
                'ovulationDayLabel'   => [ 'type' => 'String' ],
                'nextPeriodLabel'     => [ 'type' => 'String' ],
                'peakDayLabel'        => [ 'type' => 'String' ],
                'tabCurrent'          => [ 'type' => 'String' ],
                'tabNextCycles'       => [ 'type' => 'String' ],
                'tipText'             => [ 'type' => 'String' ],
                'privacyNote'         => [ 'type' => 'String' ],
                'disclaimer'          => [ 'type' => 'String' ],
            ],
        ]);

        // CTA banner
        register_graphql_object_type( 'OvuDayCta', [
            'description' => 'CTA banner content',
            'fields' => [
                'title'     => [ 'type' => 'String' ],
                'subtitle'  => [ 'type' => 'String' ],
                'btnText'   => [ 'type' => 'String' ],
                'btnUrl'    => [ 'type' => 'String' ],
                'bgColor'   => [ 'type' => 'String' ],
                'textColor' => [ 'type' => 'String' ],
            ],
        ]);

        // Blog settings
        register_graphql_object_type( 'OvuDayBlogSettings', [
            'description' => 'Blog listing and detail settings',
            'fields' => [
                'listingBadge'            => [ 'type' => 'String' ],
                'listingTitle'            => [ 'type' => 'String' ],
                'listingSubtitle'         => [ 'type' => 'String' ],
                'postsPerPage'            => [ 'type' => 'Int' ],
                'layout'                  => [ 'type' => 'String' ],
                'showAuthor'              => [ 'type' => 'Boolean' ],
                'showDate'                => [ 'type' => 'Boolean' ],
                'showCategory'            => [ 'type' => 'Boolean' ],
                'showReadingTime'         => [ 'type' => 'Boolean' ],
                'showTags'                => [ 'type' => 'Boolean' ],
                'excerptLength'           => [ 'type' => 'Int' ],
                'featuredPost'            => [ 'type' => 'Boolean' ],
                'noPostsText'             => [ 'type' => 'String' ],
                'readMoreText'            => [ 'type' => 'String' ],
                'detailShowAuthorBox'     => [ 'type' => 'Boolean' ],
                'detailShowRelated'       => [ 'type' => 'Boolean' ],
                'detailRelatedCount'      => [ 'type' => 'Int' ],
                'detailRelatedTitle'      => [ 'type' => 'String' ],
                'detailShowBreadcrumb'    => [ 'type' => 'Boolean' ],
                'detailShowShare'         => [ 'type' => 'Boolean' ],
                'detailShowToc'           => [ 'type' => 'Boolean' ],
                'detailTocTitle'          => [ 'type' => 'String' ],
                'detailMedicalDisclaimer' => [ 'type' => 'String' ],
                'detailShareLabel'        => [ 'type' => 'String' ],
                'detailBackToBlogText'    => [ 'type' => 'String' ],
                'listingTopAdSlot'        => [ 'type' => 'String' ],
                'listingInlineAdSlot'     => [ 'type' => 'String' ],
                'listingInlineEvery'      => [ 'type' => 'Int' ],
                'detailTopAdSlot'         => [ 'type' => 'String' ],
                'detailInlineAdSlot'      => [ 'type' => 'String' ],
                'detailSidebarAdSlot'     => [ 'type' => 'String' ],
                'detailBottomAdSlot'      => [ 'type' => 'String' ],
            ],
        ]);

        // ── Page content sub-types ──────────────────────────

        // About page
        register_graphql_object_type( 'OvuDayAboutValue', [
            'description' => 'A single About page value/mission item',
            'fields' => [
                'icon'        => [ 'type' => 'String' ],
                'title'       => [ 'type' => 'String' ],
                'description' => [ 'type' => 'String' ],
            ],
        ]);
        register_graphql_object_type( 'OvuDayAboutPage', [
            'description' => 'About page content',
            'fields' => [
                'badge'            => [ 'type' => 'String' ],
                'title'            => [ 'type' => 'String' ],
                'intro'            => [ 'type' => 'String' ],
                'storyTitle'       => [ 'type' => 'String' ],
                'storyP1'          => [ 'type' => 'String' ],
                'storyP2'          => [ 'type' => 'String' ],
                'valuesTitle'      => [ 'type' => 'String' ],
                'values'           => [ 'type' => [ 'list_of' => 'OvuDayAboutValue' ] ],
                'disclaimer'       => [ 'type' => 'String' ],
                'ctaPrimaryText'   => [ 'type' => 'String' ],
                'ctaPrimaryUrl'    => [ 'type' => 'String' ],
                'ctaSecondaryText' => [ 'type' => 'String' ],
                'ctaSecondaryUrl'  => [ 'type' => 'String' ],
            ],
        ]);

        // How It Works page
        register_graphql_object_type( 'OvuDayCyclePhase', [
            'description' => 'A single cycle phase (menstrual, follicular, etc.)',
            'fields' => [
                'name'        => [ 'type' => 'String' ],
                'days'        => [ 'type' => 'String' ],
                'description' => [ 'type' => 'String' ],
                'color'       => [ 'type' => 'String' ],
                'textColor'   => [ 'type' => 'String' ],
            ],
        ]);
        register_graphql_object_type( 'OvuDayHowPage', [
            'description' => 'How It Works page content',
            'fields' => [
                'badge'              => [ 'type' => 'String' ],
                'title'              => [ 'type' => 'String' ],
                'intro'              => [ 'type' => 'String' ],
                'phasesTitle'        => [ 'type' => 'String' ],
                'phasesSubtitle'     => [ 'type' => 'String' ],
                'phases'             => [ 'type' => [ 'list_of' => 'OvuDayCyclePhase' ] ],
                'formulaTitle'       => [ 'type' => 'String' ],
                'formulaExample'     => [ 'type' => 'String' ],
                'fertileTitle'       => [ 'type' => 'String' ],
                'fertileExplanation' => [ 'type' => 'String' ],
                'limitationsTitle'   => [ 'type' => 'String' ],
                'limitations'        => [ 'type' => [ 'list_of' => 'String' ] ],
                'ctaText'            => [ 'type' => 'String' ],
                'ctaBtnText'         => [ 'type' => 'String' ],
                'ctaBtnUrl'          => [ 'type' => 'String' ],
            ],
        ]);

        // Contact page
        register_graphql_object_type( 'OvuDayContactPage', [
            'description' => 'Contact page content',
            'fields' => [
                'badge'          => [ 'type' => 'String' ],
                'title'          => [ 'type' => 'String' ],
                'intro'          => [ 'type' => 'String' ],
                'successMessage' => [ 'type' => 'String' ],
                'responseTime'   => [ 'type' => 'String' ],
                'formEmail'      => [ 'type' => 'String' ],
                'formSubject'    => [ 'type' => 'String' ],
                'subjects'       => [ 'type' => [ 'list_of' => 'String' ] ],
            ],
        ]);

        // Legal pages (Privacy / Terms)
        register_graphql_object_type( 'OvuDayLegalSection', [
            'description' => 'A single legal page section',
            'fields' => [
                'heading' => [ 'type' => 'String' ],
                'body'    => [ 'type' => 'String' ],
            ],
        ]);
        register_graphql_object_type( 'OvuDayLegalPage', [
            'description' => 'Privacy Policy or Terms of Use page',
            'fields' => [
                'title'    => [ 'type' => 'String' ],
                'sections' => [ 'type' => [ 'list_of' => 'OvuDayLegalSection' ] ],
            ],
        ]);

        // Footer
        register_graphql_object_type( 'OvuDayFooter', [
            'description' => 'Footer content',
            'fields' => [
                'logoText'             => [ 'type' => 'String' ],
                'tagline'              => [ 'type' => 'String' ],
                'copyright'            => [ 'type' => 'String' ],
                'disclaimer'           => [ 'type' => 'String' ],
                'socialTwitter'        => [ 'type' => 'String' ],
                'socialFacebook'       => [ 'type' => 'String' ],
                'socialInstagram'      => [ 'type' => 'String' ],
                'socialPinterest'      => [ 'type' => 'String' ],
                'newsletterEnable'     => [ 'type' => 'Boolean' ],
                'newsletterTitle'      => [ 'type' => 'String' ],
                'newsletterPlaceholder'=> [ 'type' => 'String' ],
                'newsletterBtn'        => [ 'type' => 'String' ],
                'links'                => [ 'type' => [ 'list_of' => 'OvuDayFooterColumn' ] ],
            ],
        ]);

        // Root content object
        register_graphql_object_type( 'OvuDayContent', [
            'description' => 'All site content managed from Content Builder',
            'fields' => [
                'hero'       => [ 'type' => 'OvuDayHero',              'description' => 'Hero section' ],
                'navigation' => [ 'type' => [ 'list_of' => 'OvuDayNavLink' ], 'description' => 'Header navigation links' ],
                'trust'      => [ 'type' => [ 'list_of' => 'OvuDayTrustBadge' ],  'description' => 'Trust badge pills' ],
                'stats'      => [ 'type' => [ 'list_of' => 'OvuDayStatItem' ],    'description' => 'Stats row' ],
                'features'   => [ 'type' => [ 'list_of' => 'OvuDayFeatureItem' ], 'description' => 'Feature cards' ],
                'featuresSectionTitle'   => [ 'type' => 'String' ],
                'featuresSectionSubtitle'=> [ 'type' => 'String' ],
                'steps'      => [ 'type' => [ 'list_of' => 'OvuDayStepItem' ],    'description' => 'How-it-works steps' ],
                'stepsSectionTitle'   => [ 'type' => 'String' ],
                'stepsSectionSubtitle'=> [ 'type' => 'String' ],
                'faq'        => [ 'type' => [ 'list_of' => 'OvuDayFaqItem' ],     'description' => 'FAQ items' ],
                'faqSectionTitle'   => [ 'type' => 'String' ],
                'faqSectionSubtitle'=> [ 'type' => 'String' ],
                'calculator' => [ 'type' => 'OvuDayCalculatorContent', 'description' => 'Calculator labels' ],
                'cta'        => [ 'type' => 'OvuDayCta',               'description' => 'CTA banner' ],
                'blog'        => [ 'type' => 'OvuDayBlogSettings',      'description' => 'Blog settings' ],
                'footer'      => [ 'type' => 'OvuDayFooter',            'description' => 'Footer content' ],
                'aboutPage'   => [ 'type' => 'OvuDayAboutPage',         'description' => 'About page content' ],
                'howPage'     => [ 'type' => 'OvuDayHowPage',           'description' => 'How It Works page content' ],
                'contactPage' => [ 'type' => 'OvuDayContactPage',       'description' => 'Contact page content' ],
                'privacyPage' => [ 'type' => 'OvuDayLegalPage',         'description' => 'Privacy Policy content' ],
                'termsPage'   => [ 'type' => 'OvuDayLegalPage',         'description' => 'Terms of Use content' ],
                'adsEnabled'    => [ 'type' => 'Boolean', 'description' => 'Enable ad slots' ],
                'adsenseClient' => [ 'type' => 'String',  'description' => 'AdSense client ID (ca-pub-...)' ],
                'adLabel'       => [ 'type' => 'String',  'description' => 'Ad label text' ],
                'sponsoredLabel'=> [ 'type' => 'String',  'description' => 'Sponsored badge text' ],
                'siteCopyJson'  => [ 'type' => 'String',  'description' => 'JSON dictionary for site copy' ],
            ],
        ]);
    }

    /* ── Content root query ─────────────────────────────── */

    private function add_content_query(): void {
        register_graphql_field( 'RootQuery', 'ovudayContent', [
            'type'        => 'OvuDayContent',
            'description' => 'All site content (hero, features, FAQ, calculator, footer, etc.)',
            'resolve'     => function() {
                $d = \OvuDay\Content_Builder::get_content();

                return [
                    'hero' => [
                        'badgeText'        => $d['hero']['badge_text']        ?? '',
                        'headline'         => $d['hero']['headline']          ?? '',
                        'headlineAccent'   => $d['hero']['headline_accent']   ?? '',
                        'subheadline'      => $d['hero']['subheadline']       ?? '',
                        'ctaPrimaryText'   => $d['hero']['cta_primary_text']  ?? '',
                        'ctaPrimaryUrl'    => $d['hero']['cta_primary_url']   ?? '',
                        'ctaSecondaryText' => $d['hero']['cta_secondary_text'] ?? '',
                        'ctaSecondaryUrl'  => $d['hero']['cta_secondary_url']  ?? '',
                        'bgGradientFrom'   => $d['hero']['bg_gradient_from']  ?? '',
                        'bgGradientTo'     => $d['hero']['bg_gradient_to']    ?? '',
                    ],
                    'navigation' => array_map( fn($n) => [
                        'label' => $n['label'] ?? '',
                        'url'   => $n['url']   ?? '',
                    ], $d['navigation']['items'] ?? [] ),
                    'trust' => array_map( fn($b) => [
                        'icon'  => $b['icon']  ?? 'ShieldCheck',
                        'text'  => $b['text']  ?? '',
                        'color' => $b['color'] ?? '#10b981',
                    ], $d['trust']['items'] ?? [] ),
                    'stats' => array_map( fn($s) => [
                        'value'  => $s['value']  ?? '',
                        'suffix' => $s['suffix'] ?? '',
                        'label'  => $s['label']  ?? '',
                    ], $d['stats']['items'] ?? [] ),
                    'features' => array_map( fn($f) => [
                        'icon'        => $f['icon']        ?? 'Star',
                        'title'       => $f['title']       ?? '',
                        'description' => $f['description'] ?? '',
                        'color'       => $f['color']       ?? '#E8476E',
                    ], $d['features']['items'] ?? [] ),
                    'featuresSectionTitle'    => $d['features']['section_title']    ?? '',
                    'featuresSectionSubtitle' => $d['features']['section_subtitle'] ?? '',
                    'steps' => array_map( fn($s) => [
                        'number'      => $s['number']      ?? '',
                        'icon'        => $s['icon']        ?? '',
                        'title'       => $s['title']       ?? '',
                        'description' => $s['description'] ?? '',
                    ], $d['steps']['items'] ?? [] ),
                    'stepsSectionTitle'    => $d['steps']['section_title']    ?? '',
                    'stepsSectionSubtitle' => $d['steps']['section_subtitle'] ?? '',
                    'faq' => array_map( fn($f) => [
                        'question' => $f['question'] ?? '',
                        'answer'   => $f['answer']   ?? '',
                        'category' => $f['category'] ?? 'general',
                    ], $d['faq']['items'] ?? [] ),
                    'faqSectionTitle'    => $d['faq']['section_title']    ?? '',
                    'faqSectionSubtitle' => $d['faq']['section_subtitle'] ?? '',
                    'calculator' => [
                        'title'               => $d['calculator']['title']                ?? '',
                        'subtitle'            => $d['calculator']['subtitle']             ?? '',
                        'lmpLabel'            => $d['calculator']['lmp_label']            ?? '',
                        'lmpHelp'             => $d['calculator']['lmp_help']             ?? '',
                        'cycleLabel'          => $d['calculator']['cycle_label']          ?? '',
                        'cycleHelp'           => $d['calculator']['cycle_help']           ?? '',
                        'lutealLabel'         => $d['calculator']['luteal_label']         ?? '',
                        'lutealHelp'          => $d['calculator']['luteal_help']          ?? '',
                        'calculateBtn'        => $d['calculator']['calculate_btn']        ?? '',
                        'resetBtn'            => $d['calculator']['reset_btn']            ?? '',
                        'resultTitle'         => $d['calculator']['result_title']         ?? '',
                        'cycleOverviewLabel'  => $d['calculator']['cycle_overview_label'] ?? '',
                        'fertileWindowTitle'  => $d['calculator']['fertile_window_title'] ?? '',
                        'fertileWindowLabel'  => $d['calculator']['fertile_window_label'] ?? '',
                        'ovulationDayLabel'   => $d['calculator']['ovulation_day_label']  ?? '',
                        'nextPeriodLabel'     => $d['calculator']['next_period_label']    ?? '',
                        'peakDayLabel'        => $d['calculator']['peak_day_label']       ?? '',
                        'tabCurrent'          => $d['calculator']['tab_current']          ?? '',
                        'tabNextCycles'       => $d['calculator']['tab_next_cycles']      ?? '',
                        'tipText'             => $d['calculator']['tip_text']             ?? '',
                        'privacyNote'         => $d['calculator']['privacy_note']         ?? '',
                        'disclaimer'          => $d['calculator']['disclaimer']           ?? '',
                    ],
                    'cta' => [
                        'title'     => $d['cta']['title']      ?? '',
                        'subtitle'  => $d['cta']['subtitle']   ?? '',
                        'btnText'   => $d['cta']['btn_text']   ?? '',
                        'btnUrl'    => $d['cta']['btn_url']    ?? '',
                        'bgColor'   => $d['cta']['bg_color']   ?? '#E8476E',
                        'textColor' => $d['cta']['text_color'] ?? '#ffffff',
                    ],
                    'blog' => [
                        'listingBadge'            => $d['blog']['listing_badge']           ?? '',
                        'listingTitle'            => $d['blog']['listing_title']           ?? '',
                        'listingSubtitle'         => $d['blog']['listing_subtitle']        ?? '',
                        'postsPerPage'            => (int)($d['blog']['posts_per_page']    ?? 12),
                        'layout'                  => $d['blog']['layout']                  ?? 'grid',
                        'showAuthor'              => ($d['blog']['show_author']            ?? '1') === '1',
                        'showDate'                => ($d['blog']['show_date']              ?? '1') === '1',
                        'showCategory'            => ($d['blog']['show_category']          ?? '1') === '1',
                        'showReadingTime'         => ($d['blog']['show_reading_time']      ?? '1') === '1',
                        'showTags'                => ($d['blog']['show_tags']              ?? '1') === '1',
                        'excerptLength'           => (int)($d['blog']['excerpt_length']    ?? 25),
                        'featuredPost'            => ($d['blog']['featured_post']          ?? '1') === '1',
                        'noPostsText'             => $d['blog']['no_posts_text']           ?? '',
                        'readMoreText'            => $d['blog']['read_more_text']          ?? '',
                        'detailShowAuthorBox'     => ($d['blog']['detail_show_author_box'] ?? '1') === '1',
                        'detailShowRelated'       => ($d['blog']['detail_show_related']    ?? '1') === '1',
                        'detailRelatedCount'      => (int)($d['blog']['detail_related_count'] ?? 3),
                        'detailRelatedTitle'      => $d['blog']['detail_related_title']    ?? '',
                        'detailShowBreadcrumb'    => ($d['blog']['detail_show_breadcrumb'] ?? '1') === '1',
                        'detailShowShare'         => ($d['blog']['detail_show_share']      ?? '1') === '1',
                        'detailShowToc'           => ($d['blog']['detail_show_toc']        ?? '1') === '1',
                        'detailTocTitle'          => $d['blog']['detail_toc_title']        ?? '',
                        'detailMedicalDisclaimer' => $d['blog']['detail_medical_disclaimer'] ?? '',
                        'detailShareLabel'        => $d['blog']['detail_share_label'] ?? 'Share this article:',
                        'detailBackToBlogText'    => $d['blog']['detail_back_to_blog_text'] ?? 'Back to Blog',
                        'listingTopAdSlot'        => $d['blog']['listing_top_ad_slot'] ?? '',
                        'listingInlineAdSlot'     => $d['blog']['listing_inline_ad_slot'] ?? '',
                        'listingInlineEvery'      => (int)($d['blog']['listing_inline_every'] ?? 4),
                        'detailTopAdSlot'         => $d['blog']['detail_top_ad_slot'] ?? '',
                        'detailInlineAdSlot'      => $d['blog']['detail_inline_ad_slot'] ?? '',
                        'detailSidebarAdSlot'     => $d['blog']['detail_sidebar_ad_slot'] ?? '',
                        'detailBottomAdSlot'      => $d['blog']['detail_bottom_ad_slot'] ?? '',
                    ],
                    'footer' => [
                        'logoText'              => $d['footer']['logo_text']               ?? '',
                        'tagline'               => $d['footer']['tagline']                 ?? '',
                        'copyright'             => $d['footer']['copyright']               ?? '',
                        'disclaimer'            => $d['footer']['disclaimer']              ?? '',
                        'socialTwitter'         => $d['footer']['social_twitter']          ?? '',
                        'socialFacebook'        => $d['footer']['social_facebook']         ?? '',
                        'socialInstagram'       => $d['footer']['social_instagram']        ?? '',
                        'socialPinterest'       => $d['footer']['social_pinterest']        ?? '',
                        'newsletterEnable'      => ($d['footer']['newsletter_enable']      ?? '1') === '1',
                        'newsletterTitle'       => $d['footer']['newsletter_title']        ?? '',
                        'newsletterPlaceholder' => $d['footer']['newsletter_placeholder']  ?? '',
                        'newsletterBtn'         => $d['footer']['newsletter_btn']          ?? '',
                        'links' => array_map( fn($col) => [
                            'title' => $col['title'] ?? '',
                            'items' => array_map( fn($lnk) => [
                                'label' => $lnk['label'] ?? '',
                                'url'   => $lnk['url']   ?? '#',
                            ], $col['items'] ?? [] ),
                        ], $d['footer']['links'] ?? [] ),
                    ],
                    'aboutPage' => [
                        'badge'            => $d['about_page']['badge']              ?? '',
                        'title'            => $d['about_page']['title']              ?? '',
                        'intro'            => $d['about_page']['intro']              ?? '',
                        'storyTitle'       => $d['about_page']['story_title']        ?? '',
                        'storyP1'          => $d['about_page']['story_p1']           ?? '',
                        'storyP2'          => $d['about_page']['story_p2']           ?? '',
                        'valuesTitle'      => $d['about_page']['values_title']       ?? '',
                        'values'           => array_map( fn($v) => [
                            'icon'        => $v['icon']        ?? '',
                            'title'       => $v['title']       ?? '',
                            'description' => $v['description'] ?? '',
                        ], $d['about_page']['values'] ?? [] ),
                        'disclaimer'       => $d['about_page']['disclaimer']         ?? '',
                        'ctaPrimaryText'   => $d['about_page']['cta_primary_text']   ?? '',
                        'ctaPrimaryUrl'    => $d['about_page']['cta_primary_url']    ?? '',
                        'ctaSecondaryText' => $d['about_page']['cta_secondary_text'] ?? '',
                        'ctaSecondaryUrl'  => $d['about_page']['cta_secondary_url']  ?? '',
                    ],
                    'howPage' => [
                        'badge'              => $d['how_page']['badge']               ?? '',
                        'title'              => $d['how_page']['title']               ?? '',
                        'intro'              => $d['how_page']['intro']               ?? '',
                        'phasesTitle'        => $d['how_page']['phases_title']        ?? '',
                        'phasesSubtitle'     => $d['how_page']['phases_subtitle']     ?? '',
                        'phases'             => array_map( fn($p) => [
                            'name'        => $p['name']        ?? '',
                            'days'        => $p['days']        ?? '',
                            'description' => $p['description'] ?? '',
                            'color'       => $p['color']       ?? '',
                            'textColor'   => $p['text_color']  ?? '',
                        ], $d['how_page']['phases'] ?? [] ),
                        'formulaTitle'       => $d['how_page']['formula_title']       ?? '',
                        'formulaExample'     => $d['how_page']['formula_example']     ?? '',
                        'fertileTitle'       => $d['how_page']['fertile_title']       ?? '',
                        'fertileExplanation' => $d['how_page']['fertile_explanation'] ?? '',
                        'limitationsTitle'   => $d['how_page']['limitations_title']   ?? '',
                        'limitations'        => array_map( fn($l) => $l['text'] ?? '', $d['how_page']['limitations'] ?? [] ),
                        'ctaText'            => $d['how_page']['cta_text']            ?? '',
                        'ctaBtnText'         => $d['how_page']['cta_btn_text']        ?? '',
                        'ctaBtnUrl'          => $d['how_page']['cta_btn_url']         ?? '',
                    ],
                    'contactPage' => [
                        'badge'          => $d['contact_page']['badge']           ?? '',
                        'title'          => $d['contact_page']['title']           ?? '',
                        'intro'          => $d['contact_page']['intro']           ?? '',
                        'successMessage' => $d['contact_page']['success_message'] ?? '',
                        'responseTime'   => $d['contact_page']['response_time']   ?? '',
                        'formEmail'      => $d['contact_page']['form_email']      ?? '',
                        'formSubject'    => $d['contact_page']['form_subject']    ?? '',
                        'subjects'       => array_map( fn($s) => $s['text'] ?? '', $d['contact_page']['subjects'] ?? [] ),
                    ],
                    'privacyPage' => [
                        'title'    => $d['privacy_page']['title'] ?? '',
                        'sections' => array_map( fn($s) => [
                            'heading' => $s['heading'] ?? '',
                            'body'    => $s['body']    ?? '',
                        ], $d['privacy_page']['sections'] ?? [] ),
                    ],
                    'termsPage' => [
                        'title'    => $d['terms_page']['title'] ?? '',
                        'sections' => array_map( fn($s) => [
                            'heading' => $s['heading'] ?? '',
                            'body'    => $s['body']    ?? '',
                        ], $d['terms_page']['sections'] ?? [] ),
                    ],
                    'adsEnabled'    => ($d['global']['ads_enabled'] ?? '0') === '1',
                    'adsenseClient' => $d['global']['adsense_client'] ?? '',
                    'adLabel'       => $d['global']['ad_label'] ?? 'Advertisement',
                    'sponsoredLabel'=> $d['global']['sponsored_label'] ?? 'Sponsored',
                    'siteCopyJson'  => $d['global']['site_copy_json'] ?? '',
                ];
            },
        ]);
    }

    /* ── Sub-types ──────────────────────────────────────── */

    private function register_robots_type(): void {
        register_graphql_object_type( 'OvuDayRobots', [
            'description' => 'Per-post robots meta directives',
            'fields' => [
                'noindex'      => [ 'type' => 'Boolean', 'description' => 'Add noindex' ],
                'nofollow'     => [ 'type' => 'Boolean', 'description' => 'Add nofollow' ],
                'noarchive'    => [ 'type' => 'Boolean', 'description' => 'Add noarchive' ],
                'nosnippet'    => [ 'type' => 'Boolean', 'description' => 'Add nosnippet' ],
                'noimageindex' => [ 'type' => 'Boolean', 'description' => 'Add noimageindex' ],
            ],
        ]);
    }

    private function register_og_type(): void {
        register_graphql_object_type( 'OvuDayOG', [
            'description' => 'Open Graph meta tags',
            'fields' => [
                'title'       => [ 'type' => 'String', 'description' => 'og:title' ],
                'description' => [ 'type' => 'String', 'description' => 'og:description' ],
                'image'       => [ 'type' => 'String', 'description' => 'og:image URL' ],
                'imageWidth'  => [ 'type' => 'Int',    'description' => 'og:image:width' ],
                'imageHeight' => [ 'type' => 'Int',    'description' => 'og:image:height' ],
                'type'        => [ 'type' => 'String', 'description' => 'og:type (article|website)' ],
                'siteName'    => [ 'type' => 'String', 'description' => 'og:site_name' ],
                'locale'      => [ 'type' => 'String', 'description' => 'og:locale' ],
            ],
        ]);
    }

    private function register_twitter_type(): void {
        register_graphql_object_type( 'OvuDayTwitter', [
            'description' => 'Twitter Card meta tags',
            'fields' => [
                'card'        => [ 'type' => 'String', 'description' => 'twitter:card' ],
                'title'       => [ 'type' => 'String', 'description' => 'twitter:title' ],
                'description' => [ 'type' => 'String', 'description' => 'twitter:description' ],
                'image'       => [ 'type' => 'String', 'description' => 'twitter:image URL' ],
                'site'        => [ 'type' => 'String', 'description' => 'twitter:site @handle' ],
                'creator'     => [ 'type' => 'String', 'description' => 'twitter:creator @handle' ],
            ],
        ]);
    }

    private function register_schema_type(): void {
        register_graphql_object_type( 'OvuDaySchema', [
            'description' => 'Structured data / JSON-LD controls',
            'fields' => [
                'type'       => [ 'type' => 'String', 'description' => 'article|medical|faq|how_to|product|person|web_page|none' ],
                'reviewedBy' => [ 'type' => 'String', 'description' => 'Medically reviewed by name' ],
                'authorName' => [ 'type' => 'String', 'description' => 'Custom schema author name' ],
                'authorUrl'  => [ 'type' => 'String', 'description' => 'Custom schema author URL' ],
                'customJson' => [ 'type' => 'String', 'description' => 'Raw JSON-LD override string' ],
            ],
        ]);
    }

    private function register_sitemap_type(): void {
        register_graphql_object_type( 'OvuDaySitemap', [
            'description' => 'Sitemap settings per post',
            'fields' => [
                'exclude'    => [ 'type' => 'Boolean', 'description' => 'Exclude from sitemap' ],
                'priority'   => [ 'type' => 'String',  'description' => 'Priority 0.0–1.0' ],
                'changefreq' => [ 'type' => 'String',  'description' => 'Change frequency' ],
            ],
        ]);
    }

    private function register_redirect_type(): void {
        register_graphql_object_type( 'OvuDayRedirect', [
            'description' => 'Per-post redirect',
            'fields' => [
                'url'  => [ 'type' => 'String', 'description' => 'Redirect destination URL' ],
                'type' => [ 'type' => 'Int',    'description' => 'HTTP code: 301|302|307' ],
            ],
        ]);
    }

    private function register_analysis_type(): void {
        register_graphql_object_type( 'OvuDayAnalysis', [
            'description' => 'Content SEO analysis',
            'fields' => [
                'wordCount'        => [ 'type' => 'Int',     'description' => 'Word count' ],
                'keywordDensity'   => [ 'type' => 'Float',   'description' => 'Keyword density %' ],
                'keywordInTitle'   => [ 'type' => 'Boolean', 'description' => 'Keyword in SEO title' ],
                'keywordInDesc'    => [ 'type' => 'Boolean', 'description' => 'Keyword in meta description' ],
                'keywordInContent' => [ 'type' => 'Boolean', 'description' => 'Keyword in body content' ],
                'keywordInH1'      => [ 'type' => 'Boolean', 'description' => 'Keyword in H1' ],
                'hasMetaDesc'      => [ 'type' => 'Boolean', 'description' => 'Meta description set' ],
                'hasOgImage'       => [ 'type' => 'Boolean', 'description' => 'OG image set' ],
                'hasFeaturedImage' => [ 'type' => 'Boolean', 'description' => 'Featured image set' ],
                'titleLength'      => [ 'type' => 'Int',     'description' => 'SEO title char length' ],
                'descLength'       => [ 'type' => 'Int',     'description' => 'Meta desc char length' ],
            ],
        ]);
    }

    private function register_seo_type(): void {
        register_graphql_object_type( 'OvuDaySeo', [
            'description' => 'Complete OvuDay SEO data for a post or page',
            'fields' => [
                'title'           => [ 'type' => 'String',         'description' => 'Computed SEO title' ],
                'metaDescription' => [ 'type' => 'String',         'description' => 'Meta description' ],
                'focusKeyword'    => [ 'type' => 'String',         'description' => 'Primary focus keyword' ],
                'canonical'       => [ 'type' => 'String',         'description' => 'Canonical URL' ],
                'robotsString'    => [ 'type' => 'String',         'description' => 'Full robots content string' ],
                'breadcrumbTitle' => [ 'type' => 'String',         'description' => 'Custom breadcrumb label' ],
                'readingTime'     => [ 'type' => 'Int',            'description' => 'Reading time in minutes' ],
                'seoScore'        => [ 'type' => 'Int',            'description' => 'SEO score 0–100' ],
                'schemaJson'      => [ 'type' => 'String',         'description' => 'Full rendered JSON-LD string' ],
                'robots'          => [ 'type' => 'OvuDayRobots',  'description' => 'Robots directives' ],
                'og'              => [ 'type' => 'OvuDayOG',      'description' => 'Open Graph tags' ],
                'twitter'         => [ 'type' => 'OvuDayTwitter', 'description' => 'Twitter Card tags' ],
                'schema'          => [ 'type' => 'OvuDaySchema',  'description' => 'Schema/JSON-LD settings' ],
                'sitemap'         => [ 'type' => 'OvuDaySitemap', 'description' => 'Sitemap settings' ],
                'redirect'        => [ 'type' => 'OvuDayRedirect','description' => 'Redirect settings' ],
                'analysis'        => [ 'type' => 'OvuDayAnalysis','description' => 'Content analysis' ],
            ],
        ]);
    }

    private function register_global_seo_type(): void {
        register_graphql_object_type( 'OvuDayGlobalSeo', [
            'description' => 'Site-wide OvuDay SEO settings',
            'fields' => [
                'siteName'          => [ 'type' => 'String',  'description' => 'Site name' ],
                'titleSeparator'    => [ 'type' => 'String',  'description' => 'Title separator' ],
                'homeTitle'         => [ 'type' => 'String',  'description' => 'Homepage SEO title' ],
                'homeDescription'   => [ 'type' => 'String',  'description' => 'Homepage meta description' ],
                'homeOgImage'       => [ 'type' => 'String',  'description' => 'Homepage OG image URL' ],
                'organizationName'  => [ 'type' => 'String',  'description' => 'Organization name' ],
                'organizationLogo'  => [ 'type' => 'String',  'description' => 'Organization logo URL' ],
                'twitterUsername'   => [ 'type' => 'String',  'description' => 'Twitter @handle' ],
                'facebookUrl'       => [ 'type' => 'String',  'description' => 'Facebook page URL' ],
                'instagramUrl'      => [ 'type' => 'String',  'description' => 'Instagram URL' ],
                'googleSiteVerify'  => [ 'type' => 'String',  'description' => 'GSC verification code' ],
                'bingSiteVerify'    => [ 'type' => 'String',  'description' => 'Bing verification code' ],
                'googleAnalytics'   => [ 'type' => 'String',  'description' => 'GA4 measurement ID' ],
                'googleTagManager'  => [ 'type' => 'String',  'description' => 'GTM container ID' ],
                'noindexDate'       => [ 'type' => 'Boolean', 'description' => 'Noindex date archives' ],
                'noindexAuthor'     => [ 'type' => 'Boolean', 'description' => 'Noindex author archives' ],
                'noindexSearch'     => [ 'type' => 'Boolean', 'description' => 'Noindex search results' ],
                'noindex404'        => [ 'type' => 'Boolean', 'description' => 'Noindex 404 pages' ],
                'breadcrumbsEnable' => [ 'type' => 'Boolean', 'description' => 'Breadcrumbs enabled' ],
                'breadcrumbsHome'   => [ 'type' => 'String',  'description' => 'Breadcrumbs home label' ],
                'breadcrumbsSep'    => [ 'type' => 'String',  'description' => 'Breadcrumbs separator' ],
                'sitemapEnable'     => [ 'type' => 'Boolean', 'description' => 'Sitemap enabled' ],
                'sitemapPosts'      => [ 'type' => 'Boolean', 'description' => 'Posts in sitemap' ],
                'sitemapPages'      => [ 'type' => 'Boolean', 'description' => 'Pages in sitemap' ],
                'sitemapCats'       => [ 'type' => 'Boolean', 'description' => 'Categories in sitemap' ],
                'allowedOrigins'    => [ 'type' => 'String',  'description' => 'CORS allowed origins' ],
            ],
        ]);
    }

    /* ═══════════════════════════════════════════════════════
       FIELD EXTENSIONS
    ═══════════════════════════════════════════════════════ */

    private function extend_post_type(): void {
        // Extend built-in Post type + any custom post types that opt-in
        $types = apply_filters( 'ovuday_graphql_seo_post_types', ['Post'] );
        foreach ( $types as $graphql_type ) {
            register_graphql_field( $graphql_type, 'ovudaySeo', [
                'type'        => 'OvuDaySeo',
                'description' => 'Full OvuDay SEO data',
                'resolve'     => fn( $post ) => $this->resolve_seo( $post->databaseId ),
            ]);
        }
    }

    private function extend_page_type(): void {
        register_graphql_field( 'Page', 'ovudaySeo', [
            'type'        => 'OvuDaySeo',
            'description' => 'Full OvuDay SEO data',
            'resolve'     => fn( $page ) => $this->resolve_seo( $page->databaseId ),
        ]);
    }

    /* ═══════════════════════════════════════════════════════
       ROOT QUERIES
    ═══════════════════════════════════════════════════════ */

    private function add_global_seo_query(): void {
        register_graphql_field( 'RootQuery', 'ovudayGlobalSeo', [
            'type'        => 'OvuDayGlobalSeo',
            'description' => 'Site-wide OvuDay SEO settings',
            'resolve'     => function() {
                $g = get_option('ovuday_global_seo', []);
                return [
                    'siteName'          => $g['site_name']          ?? get_bloginfo('name'),
                    'titleSeparator'    => $g['title_separator']    ?? '|',
                    'homeTitle'         => $g['home_title']         ?? '',
                    'homeDescription'   => $g['home_description']   ?? '',
                    'homeOgImage'       => $g['home_og_image']      ?? '',
                    'organizationName'  => $g['organization_name']  ?? get_bloginfo('name'),
                    'organizationLogo'  => $g['organization_logo']  ?? '',
                    'twitterUsername'   => $g['twitter_username']   ?? '',
                    'facebookUrl'       => $g['facebook_url']       ?? '',
                    'instagramUrl'      => $g['instagram_url']      ?? '',
                    'googleSiteVerify'  => $g['google_site_verify'] ?? '',
                    'bingSiteVerify'    => $g['bing_site_verify']   ?? '',
                    'googleAnalytics'   => $g['google_analytics']   ?? '',
                    'googleTagManager'  => $g['google_tag_manager'] ?? '',
                    'noindexDate'       => ($g['noindex_date']   ?? '0') === '1',
                    'noindexAuthor'     => ($g['noindex_author'] ?? '0') === '1',
                    'noindexSearch'     => ($g['noindex_search'] ?? '1') === '1',
                    'noindex404'        => ($g['noindex_404']    ?? '1') === '1',
                    'breadcrumbsEnable' => ($g['breadcrumbs_enable'] ?? '1') === '1',
                    'breadcrumbsHome'   => $g['breadcrumbs_home'] ?? 'Home',
                    'breadcrumbsSep'    => $g['breadcrumbs_sep']  ?? '›',
                    'sitemapEnable'     => ($g['sitemap_enable']  ?? '1') === '1',
                    'sitemapPosts'      => ($g['sitemap_posts']   ?? '1') === '1',
                    'sitemapPages'      => ($g['sitemap_pages']   ?? '1') === '1',
                    'sitemapCats'       => ($g['sitemap_cats']    ?? '1') === '1',
                    'allowedOrigins'    => $g['allowed_origins']  ?? 'https://ovuday.com',
                ];
            },
        ]);
    }

    /** Backwards-compat: keeps the old ovudaySettings query working */
    private function add_legacy_settings_query(): void {
        register_graphql_object_type( 'OvuDaySiteSettings', [
            'description' => 'Legacy OvuDay site settings (use ovudayGlobalSeo instead)',
            'fields' => [
                'siteName'    => [ 'type' => 'String' ],
                'siteUrl'     => [ 'type' => 'String' ],
                'defaultMeta' => [ 'type' => 'String' ],
                'gaId'        => [ 'type' => 'String' ],
            ],
        ]);

        register_graphql_field( 'RootQuery', 'ovudaySettings', [
            'type'        => 'OvuDaySiteSettings',
            'description' => 'OvuDay legacy global settings',
            'resolve'     => function() {
                $g = get_option('ovuday_global_seo', []);
                return [
                    'siteName'    => $g['site_name']       ?? get_bloginfo('name'),
                    'siteUrl'     => home_url('/'),
                    'defaultMeta' => $g['home_description'] ?? '',
                    'gaId'        => $g['google_analytics'] ?? '',
                ];
            },
        ]);
    }

    /* ═══════════════════════════════════════════════════════
       CORE SEO RESOLVER
    ═══════════════════════════════════════════════════════ */

    private function resolve_seo( int $post_id ): array {
        $g = get_option('ovuday_global_seo', []);

        /* ── Core ── */
        $focus_kw  = (string) get_post_meta( $post_id, '_ovuday_focus_keyword',    true );
        $seo_title = (string) get_post_meta( $post_id, '_ovuday_seo_title',        true );
        $meta_desc = (string) get_post_meta( $post_id, '_ovuday_meta_description', true );
        $canonical = (string) get_post_meta( $post_id, '_ovuday_canonical',        true );

        // Compute title fallback
        $sep  = $g['title_separator'] ?? '|';
        $site = $g['site_name']       ?? get_bloginfo('name');
        if ( ! $seo_title ) {
            $seo_title = get_the_title( $post_id ) . " {$sep} " . $site;
        }
        if ( ! $canonical ) {
            $canonical = (string) get_permalink( $post_id );
        }

        /* ── Robots ── */
        $robots_arr   = (array) get_post_meta( $post_id, '_ovuday_robots', true );
        $noindex      = in_array( 'noindex',      $robots_arr, true );
        $nofollow     = in_array( 'nofollow',     $robots_arr, true );
        $noarchive    = in_array( 'noarchive',    $robots_arr, true );
        $nosnippet    = in_array( 'nosnippet',    $robots_arr, true );
        $noimageindex = in_array( 'noimageindex', $robots_arr, true );

        $parts = [];
        $parts[] = $noindex  ? 'noindex'  : 'index';
        $parts[] = $nofollow ? 'nofollow' : 'follow';
        if ( $noarchive )    $parts[] = 'noarchive';
        if ( $nosnippet )    $parts[] = 'nosnippet';
        if ( $noimageindex ) $parts[] = 'noimageindex';

        /* ── OG image resolution ── */
        $og_img_raw = (string) get_post_meta( $post_id, '_ovuday_og_image', true );
        $og_w = $og_h = null;

        if ( $og_img_raw ) {
            $img_id = attachment_url_to_postid( $og_img_raw );
            if ( $img_id ) {
                $src = wp_get_attachment_image_src( $img_id, 'full' );
                if ( $src ) { $og_w = (int)$src[1]; $og_h = (int)$src[2]; }
            }
        } else {
            // Fallback to featured image
            $thumb_id = get_post_thumbnail_id( $post_id );
            if ( $thumb_id ) {
                $src = wp_get_attachment_image_src( $thumb_id, 'full' );
                if ( $src ) {
                    $og_img_raw = $src[0];
                    $og_w = (int)$src[1];
                    $og_h = (int)$src[2];
                }
            }
        }

        /* ── OG fields ── */
        $og_title = (string) get_post_meta( $post_id, '_ovuday_og_title',       true ) ?: $seo_title;
        $og_desc  = (string) get_post_meta( $post_id, '_ovuday_og_description', true ) ?: $meta_desc;
        $og_type  = (string) get_post_meta( $post_id, '_ovuday_og_type',        true ) ?: 'article';

        /* ── Twitter fields ── */
        $tw_site  = ltrim( $g['twitter_username'] ?? '', '@' );
        $tw_card  = (string) get_post_meta( $post_id, '_ovuday_tw_card',        true ) ?: 'summary_large_image';
        $tw_title = (string) get_post_meta( $post_id, '_ovuday_tw_title',       true ) ?: $og_title;
        $tw_desc  = (string) get_post_meta( $post_id, '_ovuday_tw_description', true ) ?: $og_desc;
        $tw_img   = (string) get_post_meta( $post_id, '_ovuday_tw_image',       true ) ?: $og_img_raw;

        /* ── Schema fields ── */
        $schema_type   = (string) get_post_meta( $post_id, '_ovuday_schema_type',        true ) ?: 'article';
        $reviewed_by   = (string) get_post_meta( $post_id, '_ovuday_schema_reviewed_by', true );
        $schema_author = (string) get_post_meta( $post_id, '_ovuday_schema_author',      true );
        $schema_url    = (string) get_post_meta( $post_id, '_ovuday_schema_author_url',  true );
        $custom_json   = (string) get_post_meta( $post_id, '_ovuday_schema_custom',      true );

        /* ── Sitemap ── */
        $sm_exclude  = get_post_meta( $post_id, '_ovuday_sitemap_exclude',  true ) === '1';
        $sm_priority = (string) get_post_meta( $post_id, '_ovuday_sitemap_priority', true ) ?: '0.7';
        $sm_freq     = (string) get_post_meta( $post_id, '_ovuday_sitemap_freq',     true ) ?: 'monthly';

        /* ── Redirect ── */
        $redir_url  = (string) get_post_meta( $post_id, '_ovuday_redirect_url',  true );
        $redir_type = (int)   ( get_post_meta( $post_id, '_ovuday_redirect_type', true ) ?: 301 );

        /* ── Misc ── */
        $bc_title = (string) get_post_meta( $post_id, '_ovuday_breadcrumb_title', true );
        $score    = (int)    get_post_meta( $post_id, '_ovuday_seo_score',         true );

        $rt = (int) get_post_meta( $post_id, '_ovuday_reading_time_override', true );
        if ( ! $rt ) {
            $content = get_post_field( 'post_content', $post_id );
            $rt      = max( 1, (int) round( str_word_count( strip_tags($content) ) / 200 ) );
        }

        /* ── Analysis ── */
        $analysis = $this->compute_analysis( $post_id, $focus_kw, $seo_title, $meta_desc, $og_img_raw );

        /* ── Schema JSON-LD string ── */
        $schema_json = $this->render_schema_json( $post_id );

        return [
            'title'           => $seo_title,
            'metaDescription' => $meta_desc ?: null,
            'focusKeyword'    => $focus_kw  ?: null,
            'canonical'       => $canonical,
            'robotsString'    => implode(', ', $parts),
            'breadcrumbTitle' => $bc_title  ?: null,
            'readingTime'     => $rt,
            'seoScore'        => $score,
            'schemaJson'      => $schema_json ?: null,

            'robots' => [
                'noindex'      => $noindex,
                'nofollow'     => $nofollow,
                'noarchive'    => $noarchive,
                'nosnippet'    => $nosnippet,
                'noimageindex' => $noimageindex,
            ],
            'og' => [
                'title'       => $og_title    ?: null,
                'description' => $og_desc     ?: null,
                'image'       => $og_img_raw  ?: null,
                'imageWidth'  => $og_w,
                'imageHeight' => $og_h,
                'type'        => $og_type,
                'siteName'    => $site,
                'locale'      => str_replace('-', '_', get_bloginfo('language')),
            ],
            'twitter' => [
                'card'        => $tw_card,
                'title'       => $tw_title   ?: null,
                'description' => $tw_desc    ?: null,
                'image'       => $tw_img     ?: null,
                'site'        => $tw_site    ? '@' . $tw_site : null,
                'creator'     => $tw_site    ? '@' . $tw_site : null,
            ],
            'schema' => [
                'type'       => $schema_type,
                'reviewedBy' => $reviewed_by   ?: null,
                'authorName' => $schema_author ?: null,
                'authorUrl'  => $schema_url    ?: null,
                'customJson' => $custom_json   ?: null,
            ],
            'sitemap' => [
                'exclude'    => $sm_exclude,
                'priority'   => $sm_priority,
                'changefreq' => $sm_freq,
            ],
            'redirect' => [
                'url'  => $redir_url  ?: null,
                'type' => $redir_type,
            ],
            'analysis' => $analysis,
        ];
    }

    /* ═══════════════════════════════════════════════════════
       HELPERS
    ═══════════════════════════════════════════════════════ */

    private function compute_analysis( int $post_id, string $kw, string $title, string $desc, string $og_img ): array {
        $content  = get_post_field('post_content', $post_id);
        $plain    = strtolower( strip_tags($content) );
        $kw_l     = strtolower( $kw );
        $words    = str_word_count($plain) ?: 0;

        $density = 0.0;
        if ( $kw_l && $words > 0 ) {
            $density = round( ( substr_count($plain, $kw_l) / $words ) * 100, 2 );
        }

        preg_match_all( '/<h1[^>]*>(.*?)<\/h1>/is', $content, $h1m );
        $h1_text = strtolower( strip_tags( implode(' ', $h1m[1] ?? []) ) );

        $has_thumb = (bool) get_post_thumbnail_id($post_id);

        return [
            'wordCount'        => $words,
            'keywordDensity'   => $density,
            'keywordInTitle'   => $kw_l ? str_contains(strtolower($title), $kw_l) : false,
            'keywordInDesc'    => $kw_l ? str_contains(strtolower($desc),  $kw_l) : false,
            'keywordInContent' => $kw_l ? str_contains($plain, $kw_l)              : false,
            'keywordInH1'      => $kw_l ? str_contains($h1_text, $kw_l)            : false,
            'hasMetaDesc'      => $desc !== '',
            'hasOgImage'       => $og_img !== '' || $has_thumb,
            'hasFeaturedImage' => $has_thumb,
            'titleLength'      => mb_strlen($title),
            'descLength'       => mb_strlen($desc),
        ];
    }

    /**
     * Renders the JSON-LD that SEO_Schema::output() would echo,
     * capturing it as a string for the Next.js frontend to inject.
     */
    private function render_schema_json( int $post_id ): string {
        if ( ! class_exists('OvuDay\SEO_Schema') ) return '';

        $post = get_post( $post_id );
        if ( ! $post ) return '';

        $prev_post         = $GLOBALS['post'] ?? null;
        $GLOBALS['post']   = $post;
        setup_postdata( $post );

        ob_start();
        ( new SEO_Schema() )->output();
        $html = (string) ob_get_clean();

        if ( $prev_post ) {
            $GLOBALS['post'] = $prev_post;
            setup_postdata( $prev_post );
        } else {
            wp_reset_postdata();
        }

        return $html;
    }

    /* ═══════════════════════════════════════════════════════
       CORS + JWT
    ═══════════════════════════════════════════════════════ */

    public function add_cors_headers(): void {
        $g       = get_option('ovuday_global_seo', []);
        $origins = $g['allowed_origins'] ?? 'https://ovuday.com';

        $origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowed = array_map( 'trim', explode(',', $origins) );

        if ( in_array('*', $allowed, true) ) {
            header('Access-Control-Allow-Origin: *');
        } elseif ( $origin && in_array($origin, $allowed, true) ) {
            header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
            header('Vary: Origin');
        } else {
            return;
        }

        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
    }

    public function jwt_secret( string $secret ): string {
        return defined('OVUDAY_JWT_SECRET') ? OVUDAY_JWT_SECRET : $secret;
    }
}
