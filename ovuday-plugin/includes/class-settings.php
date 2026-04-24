<?php
namespace OvuDay;
defined('ABSPATH') || exit;

class Settings {

    private const KEY  = 'ovuday_global_seo';
    private const SLUG = 'ovuday-seo';

    public function __construct() {
        add_action('admin_menu',           [$this,'menu']);
        add_action('admin_init',           [$this,'register']);
        add_action('admin_enqueue_scripts',[$this,'assets']);
    }

    public function menu(): void {
        add_menu_page('OvuDay','OvuDay','manage_options',self::SLUG,[$this,'page'],'dashicons-admin-site-alt3',79);
        add_submenu_page(self::SLUG,'General','⚙️ General','manage_options',self::SLUG,[$this,'page']);
        add_submenu_page(self::SLUG,'Social','🌐 Social','manage_options','ovuday-social',[$this,'page_social']);
        add_submenu_page(self::SLUG,'Sitemap','🗺️ Sitemap','manage_options','ovuday-sitemap',[$this,'page_sitemap']);
        add_submenu_page(self::SLUG,'Redirects','🔀 Redirects','manage_options','ovuday-redirects',[$this,'page_redirects']);
        add_submenu_page(self::SLUG,'Tools','🛠️ Tools','manage_options','ovuday-tools',[$this,'page_tools']);
    }

    public function assets( string $hook ): void {
        $our_pages = ['toplevel_page_ovuday-seo','ovuday_page_ovuday-social','ovuday_page_ovuday-sitemap','ovuday_page_ovuday-redirects','ovuday_page_ovuday-tools'];
        if ( ! in_array( $hook, $our_pages, true ) ) return;
        wp_enqueue_style( 'ovuday-admin-ui', OVUDAY_URL . 'admin/admin-ui.css', [], OVUDAY_VERSION );
    }

    public function register(): void {
        register_setting('ovuday_group', self::KEY, ['sanitize_callback'=>[$this,'sanitize']]);
    }

    public function sanitize(array $in): array {
        $text = ['site_name','title_separator','home_title','home_description','twitter_username',
                 'facebook_url','instagram_url','google_analytics','google_tag_manager',
                 'google_site_verify','bing_site_verify','robots_global','breadcrumbs_home',
                 'breadcrumbs_sep','organization_name'];
        $urls  = ['home_og_image','default_og_image','organization_logo'];
        $bools = ['sitemap_enable','sitemap_posts','sitemap_pages','sitemap_cats',
                  'breadcrumbs_enable','noindex_archives','noindex_date','noindex_author',
                  'noindex_search','noindex_404'];
        $out   = [];
        foreach ($text  as $k) $out[$k] = sanitize_text_field($in[$k] ?? '');
        foreach ($urls  as $k) $out[$k] = esc_url_raw($in[$k] ?? '');
        foreach ($bools as $k) $out[$k] = isset($in[$k]) ? '1' : '0';
        $out['allowed_origins'] = sanitize_textarea_field($in['allowed_origins'] ?? '');
        return $out;
    }

    /* ── Sidebar ─────────────────────────────────────────── */

    private function sidebar( string $active ): void {
        $items = [
            'general'   => ['⚙️',  'General',   'ovuday-seo'],
            'social'    => ['🌐', 'Social',    'ovuday-social'],
            'sitemap'   => ['🗺️', 'Sitemap',   'ovuday-sitemap'],
            'redirects' => ['🔀', 'Redirects', 'ovuday-redirects'],
            'tools'     => ['🛠️', 'Tools',     'ovuday-tools'],
        ];
        ?>
        <aside class="ovd-sidebar">
            <div class="ovd-sidebar-brand">
                <h2><span>🌸</span> OvuDay</h2>
                <p>SEO & Site Settings</p>
            </div>
            <div class="ovd-sidebar-section">
                <div class="ovd-sidebar-label">Settings</div>
                <?php foreach ( $items as $key => $item ): ?>
                    <a href="<?php echo esc_url( admin_url('admin.php?page=' . $item[2]) ); ?>"
                       class="<?php echo $active === $key ? 'active' : ''; ?>">
                        <span class="emoji"><?php echo $item[0]; ?></span> <?php echo $item[1]; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="ovd-sidebar-section" style="margin-top:auto;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);">
                <a href="<?php echo esc_url( admin_url('admin.php?page=ovuday-content') ); ?>">
                    <span class="emoji">🎨</span> Content Builder
                </a>
                <a href="<?php echo esc_url( admin_url('admin.php?page=ovuday-blog') ); ?>">
                    <span class="emoji">📝</span> Blog Posts
                </a>
            </div>
        </aside>
        <?php
    }

    /* ── Field helpers ────────────────────────────────────── */

    private function f( string $key, string $label, array $g, string $type = 'text', string $hint = '' ): void {
        $name = 'ovuday_global_seo[' . $key . ']';
        $val  = $g[$key] ?? '';
        echo '<div class="ovd-field"><label>' . esc_html($label) . '</label>';
        if ( $type === 'textarea' ) {
            echo '<textarea name="' . esc_attr($name) . '" rows="3">' . esc_textarea($val) . '</textarea>';
        } else {
            echo '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($val) . '">';
        }
        if ( $hint ) echo '<p class="help">' . esc_html($hint) . '</p>';
        echo '</div>';
    }

    private function cb( string $key, string $label, array $g, string $hint = '' ): void {
        $name = 'ovuday_global_seo[' . $key . ']';
        $chk  = checked( $g[$key] ?? '0', '1', false );
        echo '<div class="ovd-toggle"><input type="checkbox" name="' . esc_attr($name) . '" value="1" id="ovd_' . $key . '" ' . $chk . '>';
        echo '<label for="ovd_' . $key . '">' . esc_html($label) . '</label></div>';
        if ( $hint ) echo '<p class="help" style="margin:-6px 0 10px 28px;">' . esc_html($hint) . '</p>';
    }

    /* ── GENERAL PAGE ────────────────────────────────────── */

    public function page(): void {
        $g = get_option(self::KEY, []);
        if ( isset($_GET['settings-updated']) ) echo '<div class="ovd-notice ovd-notice-success" style="margin:20px 20px 0 0;">✅ Settings saved.</div>';
        ?>
        <div class="ovd-layout">
            <?php $this->sidebar('general'); ?>
            <div class="ovd-main">
                <div class="ovd-main-header">
                    <h1>General Settings</h1>
                    <p>Core site identity, indexing, analytics, and breadcrumbs.</p>
                </div>
                <form method="post" action="options.php">
                    <?php settings_fields('ovuday_group'); ?>

                    <div class="ovd-card">
                        <h2>🏢 Site Identity</h2>
                        <div class="ovd-grid-2">
                            <?php $this->f('site_name','Site Name',$g,'text','Used in title tags and schema.'); ?>
                            <?php $this->f('title_separator','Title Separator',$g,'text','E.g. | — • /'); ?>
                            <?php $this->f('organization_name','Organization Name',$g,'text','Used in Organization schema.'); ?>
                            <?php $this->f('organization_logo','Organization Logo URL',$g,'url','512×512px ideal.'); ?>
                        </div>
                    </div>

                    <div class="ovd-card">
                        <h2>🏠 Home Page SEO</h2>
                        <div class="ovd-grid-2">
                            <?php $this->f('home_title','Home SEO Title',$g,'text','Leave blank to use site name.'); ?>
                            <?php $this->f('home_og_image','Home OG Image URL',$g,'url','1200×630px — shown when shared.'); ?>
                        </div>
                        <?php $this->f('home_description','Home Meta Description',$g,'textarea'); ?>
                    </div>

                    <div class="ovd-card">
                        <h2>🤖 Robots & Indexing</h2>
                        <?php $this->cb('noindex_date','Noindex Date Archives',$g); ?>
                        <?php $this->cb('noindex_author','Noindex Author Archives',$g); ?>
                        <?php $this->cb('noindex_search','Noindex Search Results',$g,'Recommended — prevents duplicate content.'); ?>
                        <?php $this->cb('noindex_404','Noindex 404 Pages',$g,'Recommended.'); ?>
                    </div>

                    <div class="ovd-card">
                        <h2>✅ Verification Codes</h2>
                        <div class="ovd-grid-2">
                            <?php $this->f('google_site_verify','Google Search Console',$g,'text','Paste the content="" value.'); ?>
                            <?php $this->f('bing_site_verify','Bing Webmaster',$g,'text','Paste the content="" value.'); ?>
                        </div>
                    </div>

                    <div class="ovd-card">
                        <h2>📊 Analytics & Tracking</h2>
                        <div class="ovd-grid-2">
                            <?php $this->f('google_analytics','Google Analytics (GA4 ID)',$g,'text','E.g. G-XXXXXXXXXX'); ?>
                            <?php $this->f('google_tag_manager','Google Tag Manager (GTM ID)',$g,'text','E.g. GTM-XXXXXXX'); ?>
                        </div>
                    </div>

                    <div class="ovd-card">
                        <h2>🧭 Breadcrumbs</h2>
                        <?php $this->cb('breadcrumbs_enable','Enable Breadcrumbs',$g); ?>
                        <div class="ovd-grid-2">
                            <?php $this->f('breadcrumbs_home','Home Label',$g); ?>
                            <?php $this->f('breadcrumbs_sep','Separator',$g,'text','E.g. › / /'); ?>
                        </div>
                    </div>

                    <div class="ovd-card">
                        <h2>🔗 CORS (Next.js Frontend)</h2>
                        <?php $this->f('allowed_origins','Allowed Origins (one per line)',$g,'textarea','E.g. https://ovuday.com'); ?>
                    </div>

                    <button type="submit" class="ovd-btn ovd-btn-primary">💾 Save General Settings</button>
                </form>
            </div>
        </div>
        <?php
    }

    /* ── SOCIAL PAGE ─────────────────────────────────────── */

    public function page_social(): void {
        $g = get_option(self::KEY, []);
        if ( isset($_GET['settings-updated']) ) echo '<div class="ovd-notice ovd-notice-success" style="margin:20px 20px 0 0;">✅ Settings saved.</div>';
        ?>
        <div class="ovd-layout">
            <?php $this->sidebar('social'); ?>
            <div class="ovd-main">
                <div class="ovd-main-header">
                    <h1>Social Profiles</h1>
                    <p>Social links for Organization schema and Open Graph defaults.</p>
                </div>
                <form method="post" action="options.php">
                    <?php settings_fields('ovuday_group'); ?>
                    <div class="ovd-card">
                        <h2>🔗 Social Accounts</h2>
                        <p class="help" style="margin-bottom:16px;">Added to the Organization schema's sameAs property for E-E-A-T.</p>
                        <div class="ovd-grid-2">
                            <?php $this->f('twitter_username','Twitter / X Username',$g,'text','Without the @'); ?>
                            <?php $this->f('facebook_url','Facebook Page URL',$g,'url'); ?>
                            <?php $this->f('instagram_url','Instagram URL',$g,'url'); ?>
                        </div>
                    </div>
                    <div class="ovd-card">
                        <h2>🖼️ Default Open Graph Image</h2>
                        <?php $this->f('default_og_image','Default OG Image',$g,'url','Used when a post has no featured image. 1200×630px.'); ?>
                    </div>
                    <button type="submit" class="ovd-btn ovd-btn-primary">💾 Save Social Settings</button>
                </form>
            </div>
        </div>
        <?php
    }

    /* ── SITEMAP PAGE ────────────────────────────────────── */

    public function page_sitemap(): void {
        $g = get_option(self::KEY, []);
        if ( isset($_GET['settings-updated']) ) echo '<div class="ovd-notice ovd-notice-success" style="margin:20px 20px 0 0;">✅ Settings saved.</div>';
        ?>
        <div class="ovd-layout">
            <?php $this->sidebar('sitemap'); ?>
            <div class="ovd-main">
                <div class="ovd-main-header">
                    <h1>Sitemap</h1>
                    <p>XML sitemap configuration for search engines.</p>
                </div>
                <form method="post" action="options.php">
                    <?php settings_fields('ovuday_group'); ?>
                    <div class="ovd-card">
                        <h2>🗺️ Sitemap Settings</h2>
                        <?php $this->cb('sitemap_enable','Enable XML Sitemap',$g,'Generates /sitemap.xml automatically.'); ?>
                        <?php $this->cb('sitemap_posts','Include Blog Posts',$g); ?>
                        <?php $this->cb('sitemap_pages','Include Pages',$g); ?>
                        <?php $this->cb('sitemap_cats','Include Category Archives',$g); ?>
                        <p class="help" style="margin-top:16px;">
                            Sitemap URL: <a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank"><?php echo esc_url(home_url('/sitemap.xml')); ?></a>
                        </p>
                    </div>
                    <button type="submit" class="ovd-btn ovd-btn-primary">💾 Save Sitemap Settings</button>
                </form>
            </div>
        </div>
        <?php
    }

    /* ── REDIRECTS PAGE ──────────────────────────────────── */

    public function page_redirects(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'ovuday_redirects';

        if (isset($_POST['ovd_add_redirect']) && check_admin_referer('ovd_redirect_nonce')) {
            $src  = sanitize_text_field(wp_unslash($_POST['ovd_src'] ?? ''));
            $tgt  = esc_url_raw(wp_unslash($_POST['ovd_tgt'] ?? ''));
            $type = (int)($_POST['ovd_type'] ?? 301);
            if ($src && $tgt) {
                $wpdb->replace($table, ['source_url'=>$src,'target_url'=>$tgt,'redirect_type'=>$type], ['%s','%s','%d']);
            }
        }
        if (isset($_GET['delete_redirect']) && check_admin_referer('delete_redirect')) {
            $wpdb->delete($table, ['id'=>(int)$_GET['delete_redirect']], ['%d']);
        }

        $redirects = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC LIMIT 200");
        ?>
        <div class="ovd-layout">
            <?php $this->sidebar('redirects'); ?>
            <div class="ovd-main">
                <div class="ovd-main-header">
                    <h1>Redirects</h1>
                    <p>301/302 redirect rules for moved or renamed content.</p>
                </div>

                <div class="ovd-card">
                    <h2>➕ Add Redirect</h2>
                    <form method="post">
                        <?php wp_nonce_field('ovd_redirect_nonce'); ?>
                        <div class="ovd-grid-3">
                            <div class="ovd-field">
                                <label>Source URL (relative)</label>
                                <input type="text" name="ovd_src" placeholder="/old-page" required>
                            </div>
                            <div class="ovd-field">
                                <label>Target URL</label>
                                <input type="url" name="ovd_tgt" placeholder="https://ovuday.com/new-page" required>
                            </div>
                            <div class="ovd-field">
                                <label>Type</label>
                                <select name="ovd_type">
                                    <option value="301">301 — Permanent</option>
                                    <option value="302">302 — Temporary</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" name="ovd_add_redirect" class="ovd-btn ovd-btn-primary ovd-btn-sm">Add Redirect</button>
                    </form>
                </div>

                <div class="ovd-card" style="padding:0;overflow:hidden;">
                    <div style="padding:16px 24px;border-bottom:1px solid var(--ovd-border);">
                        <h2 style="margin:0;border:none;padding:0;">Active Redirects (<?php echo count($redirects); ?>)</h2>
                    </div>
                    <table class="ovd-table">
                        <thead><tr><th>Source</th><th>Target</th><th>Type</th><th>Hits</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php if (!$redirects): ?>
                            <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--ovd-muted);">No redirects yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($redirects as $r):
                                $del_url = wp_nonce_url(add_query_arg(['page'=>'ovuday-redirects','delete_redirect'=>$r->id],'admin.php'),'delete_redirect'); ?>
                                <tr>
                                    <td><code style="background:var(--ovd-surface);padding:2px 6px;border-radius:4px;font-size:12px;"><?php echo esc_html($r->source_url); ?></code></td>
                                    <td style="font-size:12px;"><?php echo esc_html($r->target_url); ?></td>
                                    <td><span class="ovd-badge ovd-badge-green"><?php echo esc_html($r->redirect_type); ?></span></td>
                                    <td><?php echo esc_html($r->hits); ?></td>
                                    <td><a href="<?php echo esc_url($del_url); ?>" class="ovd-btn ovd-btn-danger ovd-btn-sm" onclick="return confirm('Delete this redirect?');">Delete</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }

    /* ── TOOLS PAGE ──────────────────────────────────────── */

    public function page_tools(): void {
        ?>
        <div class="ovd-layout">
            <?php $this->sidebar('tools'); ?>
            <div class="ovd-main">
                <div class="ovd-main-header">
                    <h1>Tools & Status</h1>
                    <p>Useful links and plugin status overview.</p>
                </div>
                <div class="ovd-grid-2">
                    <div class="ovd-card">
                        <h2>🔗 Quick Links</h2>
                        <ul style="list-style:none;padding:0;margin:0;">
                            <?php
                            $links = [
                                ['🗺️', 'View Sitemap XML', home_url('/sitemap.xml')],
                                ['🔍', 'Google Search Console', 'https://search.google.com/search-console'],
                                ['⭐', 'Rich Results Test', 'https://search.google.com/test/rich-results'],
                                ['⚡', 'PageSpeed Insights', 'https://pagespeed.web.dev/?url=' . urlencode(home_url('/'))],
                                ['📱', 'Mobile-Friendly Test', 'https://search.google.com/test/mobile-friendly?url=' . urlencode(home_url('/'))],
                                ['📚', 'Google Search Central', 'https://developers.google.com/search/docs'],
                            ];
                            foreach ($links as $l):
                            ?>
                                <li style="margin-bottom:10px;">
                                    <a href="<?php echo esc_url($l[2]); ?>" target="_blank"
                                       style="display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--ovd-text);padding:8px 12px;border-radius:8px;border:1px solid var(--ovd-border);transition:all .15s;">
                                        <span style="font-size:16px;"><?php echo $l[0]; ?></span>
                                        <?php echo esc_html($l[1]); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="ovd-card">
                        <h2>📡 Plugin Status</h2>
                        <table style="width:100%;font-size:13px;">
                            <tr style="border-bottom:1px solid var(--ovd-border);">
                                <td style="padding:10px 0;font-weight:600;">WPGraphQL</td>
                                <td style="padding:10px 0;"><?php echo class_exists('WPGraphQL') ? '<span class="ovd-badge ovd-badge-green">✓ Active</span>' : '<span class="ovd-badge ovd-badge-red">✗ Not installed</span>'; ?></td>
                            </tr>
                            <tr style="border-bottom:1px solid var(--ovd-border);">
                                <td style="padding:10px 0;font-weight:600;">GraphQL Endpoint</td>
                                <td style="padding:10px 0;"><code style="font-size:11px;"><?php echo esc_html(home_url('/graphql')); ?></code></td>
                            </tr>
                            <tr style="border-bottom:1px solid var(--ovd-border);">
                                <td style="padding:10px 0;font-weight:600;">Sitemap</td>
                                <td style="padding:10px 0;"><a href="<?php echo esc_url(home_url('/sitemap.xml')); ?>" target="_blank" style="font-size:11px;"><?php echo esc_url(home_url('/sitemap.xml')); ?></a></td>
                            </tr>
                            <tr>
                                <td style="padding:10px 0;font-weight:600;">REST API</td>
                                <td style="padding:10px 0;"><code style="font-size:11px;"><?php echo esc_html(rest_url('ovuday/v1/posts')); ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
