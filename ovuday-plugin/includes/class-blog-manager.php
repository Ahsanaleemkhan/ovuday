<?php
namespace OvuDay;
defined('ABSPATH') || exit;

/**
 * OvuDay Blog Manager
 *
 * Custom blog post management UI inside the plugin.
 * Posts are stored as standard wp_posts so WPGraphQL works automatically.
 */
class Blog_Manager {

    private string $list_page = 'ovuday-blog';
    private string $edit_page = 'ovuday-blog-edit';

    public function __construct() {
        add_action( 'admin_menu',    [ $this, 'add_menu' ] );
        add_action( 'admin_init',    [ $this, 'handle_save' ] );
        add_action( 'admin_init',    [ $this, 'handle_delete' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /* ── Menu ──────────────────────────────────────────────── */

    public function add_menu(): void {
        add_submenu_page(
            'ovuday-seo',
            'Blog Posts',
            '📝 Blog Posts',
            'edit_posts',
            $this->list_page,
            [ $this, 'render_list_page' ]
        );
        // Hidden editor page (no direct menu link)
        add_submenu_page(
            null,
            'Edit Post',
            'Edit Post',
            'edit_posts',
            $this->edit_page,
            [ $this, 'render_edit_page' ]
        );
    }

    /* ── Assets ────────────────────────────────────────────── */

    public function enqueue_assets( string $hook ): void {
        if ( strpos($hook, $this->list_page) === false && strpos($hook, $this->edit_page) === false ) return;
        wp_enqueue_media();
        wp_enqueue_style( 'ovuday-admin-ui', OVUDAY_URL . 'admin/admin-ui.css', [], OVUDAY_VERSION );
        wp_enqueue_editor();
    }

    /* ── Save post ─────────────────────────────────────────── */

    public function handle_save(): void {
        if (
            ! isset( $_POST['ovuday_blog_nonce'] ) ||
            ! wp_verify_nonce( $_POST['ovuday_blog_nonce'], 'ovuday_save_blog_post' ) ||
            ! current_user_can( 'edit_posts' )
        ) return;

        $post_id = (int) ( $_POST['post_id'] ?? 0 );

        $post_data = [
            'post_title'   => sanitize_text_field( $_POST['post_title'] ?? '' ),
            'post_name'    => sanitize_title( $_POST['post_slug'] ?? '' ),
            'post_content' => wp_kses_post( $_POST['post_content'] ?? '' ),
            'post_excerpt' => sanitize_textarea_field( $_POST['post_excerpt'] ?? '' ),
            'post_status'  => in_array( $_POST['post_status'] ?? 'draft', ['draft', 'publish', 'pending'] )
                              ? $_POST['post_status'] : 'draft',
            'post_type'    => 'post',
            'post_author'  => get_current_user_id(),
        ];

        if ( $post_id > 0 ) {
            $post_data['ID'] = $post_id;
            wp_update_post( $post_data );
        } else {
            $post_id = wp_insert_post( $post_data );
        }

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            wp_redirect( admin_url( 'admin.php?page=' . $this->list_page . '&error=save' ) );
            exit;
        }

        // Featured image
        $thumb_id = (int) ( $_POST['featured_image_id'] ?? 0 );
        if ( $thumb_id > 0 ) {
            set_post_thumbnail( $post_id, $thumb_id );
        } else {
            delete_post_thumbnail( $post_id );
        }

        // Categories
        $cats = array_filter( array_map( 'intval', explode( ',', $_POST['post_categories'] ?? '' ) ) );
        if ( ! empty( $cats ) ) {
            wp_set_post_categories( $post_id, $cats );
        }

        // Tags
        $tags = sanitize_text_field( $_POST['post_tags'] ?? '' );
        if ( $tags ) {
            wp_set_post_tags( $post_id, $tags );
        } else {
            wp_set_post_tags( $post_id, [] );
        }

        // SEO meta
        $seo_fields = [
            '_ovuday_title'            => sanitize_text_field( $_POST['seo_title'] ?? '' ),
            '_ovuday_meta_description' => sanitize_textarea_field( $_POST['seo_meta_description'] ?? '' ),
            '_ovuday_focus_keyword'    => sanitize_text_field( $_POST['seo_focus_keyword'] ?? '' ),
            '_ovuday_breadcrumb_title' => sanitize_text_field( $_POST['seo_breadcrumb_title'] ?? '' ),
            '_ovuday_schema_type'      => sanitize_text_field( $_POST['seo_schema_type'] ?? 'Article' ),
            '_ovuday_schema_reviewed_by' => sanitize_text_field( $_POST['seo_reviewed_by'] ?? '' ),
        ];
        foreach ( $seo_fields as $key => $value ) {
            update_post_meta( $post_id, $key, $value );
        }

        wp_redirect( admin_url( 'admin.php?page=' . $this->edit_page . '&post_id=' . $post_id . '&saved=1' ) );
        exit;
    }

    /* ── Delete post ───────────────────────────────────────── */

    public function handle_delete(): void {
        if (
            ! isset( $_GET['ovuday_delete_post'] ) ||
            ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'ovuday_delete_' . $_GET['ovuday_delete_post'] ) ||
            ! current_user_can( 'delete_posts' )
        ) return;

        $post_id = (int) $_GET['ovuday_delete_post'];
        wp_trash_post( $post_id );
        wp_redirect( admin_url( 'admin.php?page=' . $this->list_page . '&deleted=1' ) );
        exit;
    }

    /* ══════════════════════════════════════════════════════════
       LIST PAGE
    ══════════════════════════════════════════════════════════ */

    public function render_list_page(): void {
        if ( ! current_user_can('edit_posts') ) return;

        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => ['publish', 'draft', 'pending'],
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
        ?>
        <div class="ovd-layout">
            <?php $this->render_sidebar('list'); ?>
            <div class="ovd-main">
                <div class="ovd-main-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h1>Blog Posts</h1>
                        <p><?php echo count($posts); ?> articles</p>
                    </div>
                    <a href="<?php echo esc_url( admin_url('admin.php?page=' . $this->edit_page) ); ?>"
                       class="ovd-btn ovd-btn-primary">✏️ New Post</a>
                </div>

                <?php if ( isset($_GET['deleted']) ): ?>
                    <div class="ovd-notice ovd-notice-success">✅ Post moved to trash.</div>
                <?php endif; ?>

                <?php if ( empty($posts) ): ?>
                    <div class="ovd-empty">
                        <span class="emoji">📝</span>
                        <p>No blog posts yet.</p>
                        <a href="<?php echo esc_url( admin_url('admin.php?page=' . $this->edit_page) ); ?>"
                           class="ovd-btn ovd-btn-primary" style="margin-top:12px;">Create Your First Post</a>
                    </div>
                <?php else: ?>
                    <div class="ovd-card" style="padding:0;overflow:hidden;">
                    <table class="ovd-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ( $posts as $p ):
                            $cats = get_the_category($p->ID);
                            $cat_name = $cats ? $cats[0]->name : '—';
                            $status = $p->post_status;
                            $status_class = $status === 'publish' ? 'green' : ($status === 'draft' ? 'yellow' : 'red');
                        ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url( admin_url('admin.php?page=' . $this->edit_page . '&post_id=' . $p->ID) ); ?>"
                                       style="font-weight:600;color:var(--ovd-text);text-decoration:none;">
                                        <?php echo esc_html( $p->post_title ?: '(untitled)' ); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($cat_name); ?></td>
                                <td><span class="ovd-badge ovd-badge-<?php echo $status_class; ?>"><?php echo ucfirst($status); ?></span></td>
                                <td style="color:var(--ovd-muted);"><?php echo get_the_date('M j, Y', $p->ID); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="<?php echo esc_url( admin_url('admin.php?page=' . $this->edit_page . '&post_id=' . $p->ID) ); ?>"
                                           class="ovd-btn ovd-btn-secondary ovd-btn-sm">Edit</a>
                                        <a href="<?php echo wp_nonce_url(
                                            admin_url('admin.php?page=' . $this->list_page . '&ovuday_delete_post=' . $p->ID),
                                            'ovuday_delete_' . $p->ID
                                        ); ?>" class="ovd-btn ovd-btn-danger ovd-btn-sm"
                                           onclick="return confirm('Move this post to trash?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /* ══════════════════════════════════════════════════════════
       EDIT PAGE
    ══════════════════════════════════════════════════════════ */

    public function render_edit_page(): void {
        if ( ! current_user_can('edit_posts') ) return;

        $post_id = (int) ( $_GET['post_id'] ?? 0 );
        $post = $post_id ? get_post( $post_id ) : null;

        $title     = $post ? $post->post_title   : '';
        $slug      = $post ? $post->post_name    : '';
        $content   = $post ? $post->post_content  : '';
        $excerpt   = $post ? $post->post_excerpt   : '';
        $status    = $post ? $post->post_status    : 'draft';
        $thumb_id  = $post ? (int) get_post_thumbnail_id( $post_id ) : 0;
        $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium' ) : '';

        $cats = $post ? wp_get_post_categories( $post_id ) : [];
        $tags = $post ? implode( ', ', wp_get_post_tags( $post_id, ['fields' => 'names'] ) ) : '';

        $seo_title       = $post ? get_post_meta( $post_id, '_ovuday_title', true ) : '';
        $seo_desc        = $post ? get_post_meta( $post_id, '_ovuday_meta_description', true ) : '';
        $seo_keyword     = $post ? get_post_meta( $post_id, '_ovuday_focus_keyword', true ) : '';
        $seo_breadcrumb  = $post ? get_post_meta( $post_id, '_ovuday_breadcrumb_title', true ) : '';
        $seo_schema_type = $post ? get_post_meta( $post_id, '_ovuday_schema_type', true ) : 'Article';
        $seo_reviewed_by = $post ? get_post_meta( $post_id, '_ovuday_schema_reviewed_by', true ) : '';

        $all_cats = get_categories(['hide_empty' => false]);
        ?>
        <div class="ovd-layout">
            <?php $this->render_sidebar('edit'); ?>
            <div class="ovd-main">
                <div class="ovd-main-header" style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <h1><?php echo $post ? 'Edit Post' : 'New Post'; ?></h1>
                        <p><?php echo $post ? esc_html($title) : 'Create a new blog article'; ?></p>
                    </div>
                    <a href="<?php echo esc_url( admin_url('admin.php?page=' . $this->list_page) ); ?>"
                       class="ovd-btn ovd-btn-secondary">← All Posts</a>
                </div>

                <?php if ( isset($_GET['saved']) ): ?>
                    <div class="ovd-notice ovd-notice-success">✅ Post saved successfully!</div>
                <?php endif; ?>

                <form method="post" action="">
                    <?php wp_nonce_field('ovuday_save_blog_post', 'ovuday_blog_nonce'); ?>
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

                    <!-- ── Content Card ──────────────────────── -->
                    <div class="ovd-card">
                        <h2>📄 Content</h2>
                        <div class="ovd-field">
                            <label>Title</label>
                            <input type="text" name="post_title" value="<?php echo esc_attr($title); ?>"
                                   placeholder="Enter post title…" required>
                        </div>
                        <div class="ovd-field">
                            <label>Slug (URL)</label>
                            <input type="text" name="post_slug" value="<?php echo esc_attr($slug); ?>"
                                   placeholder="auto-generated-from-title">
                            <p class="help">Leave empty to auto-generate from title.</p>
                        </div>
                        <div class="ovd-field">
                            <label>Content</label>
                            <?php wp_editor( $content, 'post_content', [
                                'textarea_rows' => 20,
                                'media_buttons' => true,
                                'teeny'         => false,
                                'quicktags'     => true,
                            ]); ?>
                        </div>
                        <div class="ovd-field">
                            <label>Excerpt</label>
                            <textarea name="post_excerpt" rows="3"
                                      placeholder="Brief summary of the article…"><?php echo esc_textarea($excerpt); ?></textarea>
                            <p class="help">Appears in blog listing cards and meta description fallback.</p>
                        </div>
                    </div>

                    <!-- ── Media Card ──────────────────────── -->
                    <div class="ovd-card">
                        <h2>🖼️ Featured Image</h2>
                        <input type="hidden" name="featured_image_id" id="featured_image_id"
                               value="<?php echo $thumb_id; ?>">
                        <div id="featured-image-preview" style="margin-bottom:12px;">
                            <?php if ($thumb_url): ?>
                                <img src="<?php echo esc_url($thumb_url); ?>" class="ovd-img-preview">
                            <?php endif; ?>
                        </div>
                        <button type="button" id="upload-featured-image" class="ovd-img-upload-btn">
                            📷 <?php echo $thumb_id ? 'Change Image' : 'Upload Image'; ?>
                        </button>
                        <?php if ($thumb_id): ?>
                            <button type="button" id="remove-featured-image"
                                    class="ovd-btn ovd-btn-danger ovd-btn-sm" style="margin-left:8px;">Remove</button>
                        <?php endif; ?>
                    </div>

                    <!-- ── Taxonomy Card ───────────────────── -->
                    <div class="ovd-card">
                        <h2>🏷️ Category & Tags</h2>
                        <div class="ovd-grid-2">
                            <div class="ovd-field">
                                <label>Category</label>
                                <select name="post_categories">
                                    <option value="">— Select category —</option>
                                    <?php foreach ($all_cats as $cat): ?>
                                        <option value="<?php echo $cat->term_id; ?>"
                                                <?php selected( in_array($cat->term_id, $cats) ); ?>>
                                            <?php echo esc_html($cat->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="help">Choose the primary category for this post.</p>
                            </div>
                            <div class="ovd-field">
                                <label>Tags</label>
                                <input type="text" name="post_tags" value="<?php echo esc_attr($tags); ?>"
                                       placeholder="fertility, ovulation, cycle">
                                <p class="help">Comma-separated tags.</p>
                            </div>
                        </div>
                    </div>

                    <!-- ── SEO Card ─────────────────────────── -->
                    <div class="ovd-card">
                        <h2>🔍 SEO</h2>
                        <div class="ovd-grid-2">
                            <div class="ovd-field">
                                <label>SEO Title</label>
                                <input type="text" name="seo_title" value="<?php echo esc_attr($seo_title); ?>"
                                       placeholder="Custom title for search engines">
                                <p class="help">Leave empty to use the post title.</p>
                            </div>
                            <div class="ovd-field">
                                <label>Focus Keyword</label>
                                <input type="text" name="seo_focus_keyword"
                                       value="<?php echo esc_attr($seo_keyword); ?>"
                                       placeholder="ovulation calculator">
                            </div>
                        </div>
                        <div class="ovd-field">
                            <label>Meta Description</label>
                            <textarea name="seo_meta_description" rows="2"
                                      placeholder="Describe this post for search engines…"><?php echo esc_textarea($seo_desc); ?></textarea>
                        </div>
                        <div class="ovd-grid-3">
                            <div class="ovd-field">
                                <label>Breadcrumb Title</label>
                                <input type="text" name="seo_breadcrumb_title"
                                       value="<?php echo esc_attr($seo_breadcrumb); ?>"
                                       placeholder="Short title for breadcrumb">
                            </div>
                            <div class="ovd-field">
                                <label>Schema Type</label>
                                <select name="seo_schema_type">
                                    <?php foreach (['Article','MedicalWebPage','FAQPage','HowTo','BlogPosting'] as $type): ?>
                                        <option value="<?php echo $type; ?>"
                                                <?php selected($seo_schema_type, $type); ?>>
                                            <?php echo $type; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="ovd-field">
                                <label>Reviewed By</label>
                                <input type="text" name="seo_reviewed_by"
                                       value="<?php echo esc_attr($seo_reviewed_by); ?>"
                                       placeholder="Dr. Name (optional)">
                            </div>
                        </div>
                    </div>

                    <!-- ── Publish Card ─────────────────────── -->
                    <div class="ovd-card">
                        <h2>🚀 Publish</h2>
                        <div class="ovd-grid-2">
                            <div class="ovd-field">
                                <label>Status</label>
                                <select name="post_status">
                                    <option value="draft"   <?php selected($status, 'draft');   ?>>Draft</option>
                                    <option value="publish" <?php selected($status, 'publish'); ?>>Published</option>
                                    <option value="pending" <?php selected($status, 'pending'); ?>>Pending Review</option>
                                </select>
                            </div>
                            <div style="display:flex;align-items:flex-end;gap:10px;padding-bottom:18px;">
                                <button type="submit" class="ovd-btn ovd-btn-primary">
                                    💾 <?php echo $post ? 'Update Post' : 'Create Post'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <script>
        jQuery(function($) {
            // Featured image uploader
            var frame;
            $('#upload-featured-image').on('click', function(e) {
                e.preventDefault();
                if (frame) { frame.open(); return; }
                frame = wp.media({ title: 'Select Featured Image', multiple: false, library: { type: 'image' } });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#featured_image_id').val(attachment.id);
                    var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                    $('#featured-image-preview').html('<img src="' + url + '" class="ovd-img-preview">');
                    $('#upload-featured-image').text('📷 Change Image');
                    if (!$('#remove-featured-image').length) {
                        $('<button type="button" id="remove-featured-image" class="ovd-btn ovd-btn-danger ovd-btn-sm" style="margin-left:8px;">Remove</button>').insertAfter('#upload-featured-image');
                    }
                });
                frame.open();
            });
            $(document).on('click', '#remove-featured-image', function() {
                $('#featured_image_id').val('0');
                $('#featured-image-preview').empty();
                $('#upload-featured-image').text('📷 Upload Image');
                $(this).remove();
            });
        });
        </script>
        <?php
    }

    /* ── Shared sidebar ───────────────────────────────────── */

    private function render_sidebar( string $active ): void {
        $list_url = admin_url('admin.php?page=' . $this->list_page);
        $new_url  = admin_url('admin.php?page=' . $this->edit_page);
        ?>
        <aside class="ovd-sidebar">
            <div class="ovd-sidebar-brand">
                <h2><span>🌸</span> OvuDay</h2>
                <p>Blog Manager</p>
            </div>
            <div class="ovd-sidebar-section">
                <div class="ovd-sidebar-label">Blog</div>
                <a href="<?php echo esc_url($list_url); ?>"
                   class="<?php echo $active === 'list' ? 'active' : ''; ?>">
                    <span class="emoji">📋</span> All Posts
                </a>
                <a href="<?php echo esc_url($new_url); ?>"
                   class="<?php echo $active === 'edit' && !isset($_GET['post_id']) ? 'active' : ''; ?>">
                    <span class="emoji">✏️</span> New Post
                </a>
            </div>
            <div class="ovd-sidebar-section" style="margin-top:auto;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);">
                <a href="<?php echo esc_url( admin_url('admin.php?page=ovuday-content&tab=blog') ); ?>">
                    <span class="emoji">⚙️</span> Blog Settings
                </a>
                <a href="<?php echo esc_url( admin_url('admin.php?page=ovuday-seo') ); ?>">
                    <span class="emoji">🏠</span> Back to Plugin
                </a>
            </div>
        </aside>
        <?php
    }
}
