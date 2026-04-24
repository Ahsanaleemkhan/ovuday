<?php
namespace OvuDay;
defined('ABSPATH') || exit;

/**
 * Creates a default blog post on first load.
 * Uploads bundled images to the media library and inserts a fully SEO-optimized post.
 */
class Default_Blog {

    /**
     * Called from plugins_loaded — guarded so it only runs once.
     */
    public static function maybe_create(): void {
        self::maybe_create_blog1();
        self::maybe_create_blog2();
    }

    /* ── Blog 1: Ovulation Calculator Guide ──────────────── */

    private static function maybe_create_blog1(): void {
        if ( get_option( 'ovuday_default_blog_created' ) === 'v2' ) {
            // Verify the post actually exists — if not, reset the flag and recreate
            $existing = get_posts([
                'name'        => 'ovulation-calculator-guide',
                'post_type'   => 'post',
                'post_status' => ['publish', 'draft', 'pending', 'private'],
                'numberposts' => 1,
            ]);
            if ( ! empty( $existing ) ) return;
        }
        update_option( 'ovuday_default_blog_created', 'v2' );

        $hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
        if ( $hello ) wp_delete_post( $hello->ID, true );

        $images  = self::upload_images( self::blog1_images() );
        $content = self::build_blog1_content( $images );
        $author  = self::get_author();

        $post_id = wp_insert_post([
            'post_title'   => 'How to Use an Ovulation Calculator to Know Your Most Fertile Days',
            'post_name'    => 'ovulation-calculator-guide',
            'post_content' => $content,
            'post_excerpt' => 'Trying to conceive? Learn how an ovulation calculator works, understand your fertile window, and discover the best days to try — all backed by science, explained with heart.',
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_author'  => $author,
        ]);

        if ( is_wp_error( $post_id ) || ! $post_id ) return;

        if ( ! empty( $images['hero_id'] ) ) set_post_thumbnail( $post_id, $images['hero_id'] );

        self::assign_category( $post_id, 'Fertility' );
        wp_set_post_tags( $post_id, 'ovulation calculator, fertile window, trying to conceive, menstrual cycle, ovulation tracking, fertility tips' );

        update_post_meta( $post_id, '_ovuday_title', 'How to Use an Ovulation Calculator — Your Complete Fertility Guide | OvuDay' );
        update_post_meta( $post_id, '_ovuday_meta_description', 'Learn how an ovulation calculator works, when you are most fertile, and how to increase your chances of conceiving naturally. Free, private, science-backed guide.' );
        update_post_meta( $post_id, '_ovuday_focus_keyword', 'ovulation calculator' );
        update_post_meta( $post_id, '_ovuday_breadcrumb_title', 'Ovulation Calculator Guide' );
        update_post_meta( $post_id, '_ovuday_schema_type', 'MedicalWebPage' );
        update_post_meta( $post_id, '_ovuday_schema_reviewed_by', '' );
    }

    /* ── Blog 2: Irregular Periods & Ovulation ───────────── */

    private static function maybe_create_blog2(): void {
        if ( get_option( 'ovuday_blog2_created' ) === 'v2' ) {
            // Verify the post actually exists — if not, reset the flag and recreate
            $existing = get_posts([
                'name'        => 'ovulation-calculator-irregular-periods',
                'post_type'   => 'post',
                'post_status' => ['publish', 'draft', 'pending', 'private'],
                'numberposts' => 1,
            ]);
            if ( ! empty( $existing ) ) return;
        }
        update_option( 'ovuday_blog2_created', 'v2' );

        $images  = self::upload_images( self::blog2_images() );
        $content = self::build_blog2_content( $images );
        $author  = self::get_author();

        $post_id = wp_insert_post([
            'post_title'   => 'How to Calculate Ovulation with Irregular Periods — A Practical Guide',
            'post_name'    => 'ovulation-calculator-irregular-periods',
            'post_content' => $content,
            'post_excerpt' => 'Irregular periods do not mean you cannot predict ovulation. Learn 5 proven methods to track your fertile window even when your cycle length changes every month.',
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_author'  => $author,
        ]);

        if ( is_wp_error( $post_id ) || ! $post_id ) return;

        if ( ! empty( $images['journal_id'] ) ) set_post_thumbnail( $post_id, $images['journal_id'] );

        self::assign_category( $post_id, 'Fertility' );
        wp_set_post_tags( $post_id, 'irregular periods ovulation, ovulation calculator irregular periods, irregular cycle, PCOS ovulation, cycle tracking, fertility' );

        update_post_meta( $post_id, '_ovuday_title', 'How to Calculate Ovulation with Irregular Periods — 5 Proven Methods | OvuDay' );
        update_post_meta( $post_id, '_ovuday_meta_description', 'Irregular periods make ovulation tricky — but not impossible. Discover 5 science-backed methods to find your fertile window, even with unpredictable cycles.' );
        update_post_meta( $post_id, '_ovuday_focus_keyword', 'ovulation calculator irregular periods' );
        update_post_meta( $post_id, '_ovuday_breadcrumb_title', 'Irregular Periods Guide' );
        update_post_meta( $post_id, '_ovuday_schema_type', 'MedicalWebPage' );
        update_post_meta( $post_id, '_ovuday_schema_reviewed_by', '' );
    }

    /* ── Helpers ──────────────────────────────────────────── */

    private static function get_author(): int {
        $id = get_current_user_id();
        if ( ! $id ) {
            $admins = get_users( [ 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ] );
            $id = ! empty( $admins ) ? (int) $admins[0] : 1;
        }
        return $id;
    }

    private static function assign_category( int $post_id, string $name ): void {
        $cat = term_exists( $name, 'category' );
        if ( ! $cat ) {
            $cat = wp_insert_term( $name, 'category', [
                'description' => 'Articles about fertility, ovulation, and reproductive health.',
                'slug'        => sanitize_title( $name ),
            ]);
        }
        if ( $cat && ! is_wp_error( $cat ) ) {
            $cat_id = is_array($cat) ? (int) $cat['term_id'] : (int) $cat;
            wp_set_post_categories( $post_id, [ $cat_id ] );
        }
    }

    /* ── Image definitions ────────────────────────────────── */

    private static function blog1_images(): array {
        return [
            'hero'   => ['file' => 'ovulation-calculator-hero.png', 'title' => 'Woman Planning Her Fertility Journey with Ovulation Calendar', 'alt' => 'A woman sitting at a cozy desk with a laptop and calendar, planning her ovulation cycle with morning sunlight'],
            'cycle'  => ['file' => 'menstrual-cycle-phases.png', 'title' => 'The Four Phases of the Menstrual Cycle Infographic', 'alt' => 'Infographic showing the four menstrual cycle phases: menstrual, follicular, ovulation, and luteal with color-coded timeline'],
            'couple' => ['file' => 'fertile-window-couple.png', 'title' => 'Couple Holding Hands During Fertile Window', 'alt' => 'A couple gently holding hands in soft morning light with a calendar visible in the background, symbolizing hope during the fertile window'],
        ];
    }

    private static function blog2_images(): array {
        return [
            'journal'    => ['file' => 'irregular-period-tracking-journal.png', 'title' => 'Menstrual Cycle Tracking Journal for Irregular Periods', 'alt' => 'Flat lay of a menstrual cycle tracking journal with a pink pen, phone calendar app, and herbal tea on a marble desk'],
            'comparison' => ['file' => 'regular-vs-irregular-cycle-comparison.png', 'title' => 'Regular vs Irregular Menstrual Cycle Comparison Infographic', 'alt' => 'Side-by-side comparison infographic showing a regular 28-day cycle timeline versus an irregular cycle with varying lengths'],
            'bbt'        => ['file' => 'basal-body-temperature-tracking.png', 'title' => 'Basal Body Temperature Tracking for Ovulation Detection', 'alt' => 'Digital basal body thermometer on a white pillow next to a BBT chart notebook in early morning sunrise light'],
        ];
    }

    private static function upload_images( array $files ): array {
        $results    = [];
        $assets_dir = OVUDAY_DIR . 'admin/blog-assets/';

        // If the directory doesn't exist, return empty — post will be created without images
        if ( ! is_dir( $assets_dir ) ) return $results;

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        foreach ( $files as $key => $meta ) {
            $path = $assets_dir . $meta['file'];
            if ( ! file_exists( $path ) ) continue;

            // Read file content
            $file_data = @file_get_contents( $path );
            if ( ! $file_data ) continue;

            $upload = wp_upload_bits( $meta['file'], null, $file_data );
            if ( ! empty( $upload['error'] ) ) continue;

            $filetype  = wp_check_filetype( $upload['file'] );
            $attach_id = wp_insert_attachment([
                'post_mime_type' => $filetype['type'] ?: 'image/png',
                'post_title'     => $meta['title'],
                'post_content'   => '',
                'post_status'    => 'inherit',
            ], $upload['file']);

            if ( is_wp_error( $attach_id ) ) continue;

            $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
            wp_update_attachment_metadata( $attach_id, $attach_data );
            update_post_meta( $attach_id, '_wp_attachment_image_alt', $meta['alt'] );

            $results[ $key ]         = wp_get_attachment_url( $attach_id );
            $results[ $key . '_id' ] = $attach_id;
        }

        return $results;
    }

    /**
     * Build the full HTML blog content.
     */
    private static function build_blog1_content( array $images ): string {
        $hero   = $images['hero'] ?? '';
        $cycle  = $images['cycle'] ?? '';
        $couple = $images['couple'] ?? '';

        $hero_img   = $hero   ? '<figure class="wp-block-image size-large"><img src="' . esc_url($hero)   . '" alt="Woman using an ovulation calculator on her laptop while planning her cycle with a paper calendar" /><figcaption>Planning your fertile window does not have to feel overwhelming — a simple ovulation calculator can change everything.</figcaption></figure>' : '';
        $cycle_img  = $cycle  ? '<figure class="wp-block-image size-large"><img src="' . esc_url($cycle)  . '" alt="Infographic of the four menstrual cycle phases with color-coded timeline showing menstrual, follicular, ovulation, and luteal phases" /><figcaption>Understanding these four phases is the foundation of every ovulation calculator.</figcaption></figure>' : '';
        $couple_img = $couple ? '<figure class="wp-block-image size-large"><img src="' . esc_url($couple) . '" alt="Couple holding hands gently in morning light with a fertility calendar in the background" /><figcaption>Knowing your fertile window turns uncertainty into a plan — and a plan into hope.</figcaption></figure>' : '';

        $html  = '<p>If you have ever found yourself staring at a calendar, counting days, and wondering <em>"Is this the right time?"</em> — you are not alone. Millions of women around the world go through the same quiet calculation every month, trying to figure out when their body is most ready to conceive.</p>' . "\n\n";
        $html .= '<p>That is exactly what an <strong>ovulation calculator</strong> is designed to help with. It takes the guesswork out of something deeply personal and gives you a clear, science-backed answer: <em>these are your most fertile days.</em></p>' . "\n\n";
        $html .= $hero_img . "\n\n";

        $html .= '<h2>What Exactly Is an Ovulation Calculator?</h2>' . "\n\n";
        $html .= '<p>An ovulation calculator is a tool that estimates when you are most likely to ovulate — the moment your ovary releases a mature egg — based on information about your menstrual cycle. You typically enter two pieces of information:</p>' . "\n\n";
        $html .= '<ul><li><strong>The first day of your last menstrual period (LMP)</strong></li><li><strong>Your average cycle length</strong> (most women fall between 21 and 35 days)</li></ul>' . "\n\n";
        $html .= '<p>Some calculators, like the one here at <a href="/">OvuDay</a>, also let you adjust your <strong>luteal phase length</strong> — the time between ovulation and your next period — for even more accuracy.</p>' . "\n\n";
        $html .= '<p>The result? A personalised <strong>fertile window</strong>: the roughly six days each cycle when conception is biologically possible.</p>' . "\n\n";

        $html .= '<h2>Why Your Fertile Window Is Only 6 Days</h2>' . "\n\n";
        $html .= '<p>This surprises many people. Out of an entire cycle — which can be 28, 30, or even 35 days — there are only about <strong>six days</strong> when pregnancy is possible. Here is why:</p>' . "\n\n";
        $html .= '<ul><li>After ovulation, the egg survives for just <strong>12 to 24 hours</strong>.</li><li>But sperm can survive inside the reproductive tract for up to <strong>5 days</strong>.</li></ul>' . "\n\n";
        $html .= '<p>So the fertile window stretches from about 5 days <em>before</em> ovulation to 1 day <em>after</em>. That is it. Miss it, and you will need to wait for the next cycle.</p>' . "\n\n";
        $html .= '<p>This is exactly why an ovulation calculator matters — it tells you precisely when those six days fall, based on <em>your</em> unique cycle.</p>' . "\n\n";
        $html .= $couple_img . "\n\n";

        $html .= '<h2>Understanding Your Menstrual Cycle: The 4 Phases</h2>' . "\n\n";
        $html .= '<p>To truly understand how an ovulation calculator works, it helps to know what is happening inside your body throughout the month. Your cycle has four distinct phases:</p>' . "\n\n";
        $html .= $cycle_img . "\n\n";

        $html .= '<h3>1. Menstrual Phase (Days 1–5)</h3>' . "\n";
        $html .= '<p>This is your period. The uterine lining sheds because no fertilised egg implanted during the previous cycle. Hormone levels — especially estrogen and progesterone — are at their lowest. It is a reset.</p>' . "\n\n";

        $html .= '<h3>2. Follicular Phase (Days 1–13)</h3>' . "\n";
        $html .= '<p>While you are still menstruating, your brain is already preparing for the next chance. The pituitary gland releases <strong>FSH (follicle-stimulating hormone)</strong>, which tells your ovaries to start developing follicles. One of these will become the dominant follicle — the one that releases the egg. Estrogen rises steadily.</p>' . "\n\n";

        $html .= '<h3>3. Ovulation (Around Day 14)</h3>' . "\n";
        $html .= '<p>A surge of <strong>LH (luteinising hormone)</strong> triggers the dominant follicle to release a mature egg into the fallopian tube. This is your <strong>peak fertility moment</strong>. The egg will survive for 12–24 hours. If sperm are present, fertilisation can occur.</p>' . "\n\n";

        $html .= '<h3>4. Luteal Phase (Days 15–28)</h3>' . "\n";
        $html .= '<p>After releasing the egg, the empty follicle transforms into the <strong>corpus luteum</strong>, which produces progesterone. This hormone thickens the uterine lining, preparing it for a potential pregnancy. If the egg is not fertilised, progesterone drops, and the cycle begins again with your next period.</p>' . "\n\n";

        $html .= '<h2>The Formula Behind Every Ovulation Calculator</h2>' . "\n\n";
        $html .= '<p>Every reliable ovulation calculator uses the same core logic — the <strong>calendar method</strong>:</p>' . "\n\n";
        $html .= '<blockquote><p><strong>Estimated Ovulation Day</strong> = First day of last period + (Cycle length − Luteal phase length)</p></blockquote>' . "\n\n";
        $html .= '<p>For most women, the luteal phase is around <strong>14 days</strong>, though it can range from 10 to 16. Here is a worked example:</p>' . "\n\n";
        $html .= '<ul><li>Last period started: <strong>April 1</strong></li><li>Average cycle length: <strong>28 days</strong></li><li>Luteal phase: <strong>14 days</strong></li><li>Estimated ovulation: April 1 + (28 − 14) = <strong>April 15</strong></li><li>Fertile window: <strong>April 10 – April 16</strong></li></ul>' . "\n\n";
        $html .= '<p>That is the power of an ovulation calculator in one simple equation. No apps to pay for, no invasive tests — just biology and basic maths.</p>' . "\n\n";

        $html .= '<h2>How to Use the OvuDay Ovulation Calculator</h2>' . "\n\n";
        $html .= '<p>Our free <a href="/">ovulation calculator</a> is designed to be the simplest, most private tool available. Here is how to use it:</p>' . "\n\n";
        $html .= '<ol><li><strong>Enter your last period date</strong> — the first day of your most recent menstrual bleeding.</li><li><strong>Set your average cycle length</strong> — if you are unsure, 28 days is a good starting point.</li><li><strong>Optionally adjust your luteal phase</strong> — 14 days is the default, but you can fine-tune this if you know yours is different.</li><li><strong>Click Calculate</strong> — and instantly see your estimated ovulation day, fertile window, and next period date.</li></ol>' . "\n\n";
        $html .= '<p>Everything happens in your browser. We do not store any of your data. <strong>No account, no sign-up, no tracking.</strong> Your fertility data stays yours.</p>' . "\n\n";

        $html .= '<h2>Tips to Improve Your Chances</h2>' . "\n\n";
        $html .= '<p>An ovulation calculator gives you the <em>when</em>. But here are a few things that can help with the <em>how</em>:</p>' . "\n\n";
        $html .= '<ul><li><strong>Have intercourse every 1–2 days during your fertile window.</strong> This ensures sperm are present when the egg is released.</li><li><strong>Track your basal body temperature (BBT).</strong> A slight rise (0.2–0.5°C) after ovulation confirms that you did ovulate. Over a few months, this helps you spot your pattern.</li><li><strong>Consider LH test strips.</strong> These detect the luteinising hormone surge that happens 24–36 hours before ovulation — giving you real-time confirmation.</li><li><strong>Stay consistent.</strong> Track your cycle for at least 3–6 months to find your true average. Bodies are not clockwork, and that is okay.</li><li><strong>Take care of yourself.</strong> Stress, poor sleep, and extreme dieting can all delay ovulation. Be gentle with your body during this journey.</li></ul>' . "\n\n";

        $html .= '<h2>When an Ovulation Calculator May Not Be Enough</h2>' . "\n\n";
        $html .= '<p>It is important to be honest about limitations. An ovulation calculator is an <em>estimation tool</em>, not a medical diagnosis. It may be less reliable if:</p>' . "\n\n";
        $html .= '<ul><li>Your cycles are highly irregular (varying by more than 7 days month to month).</li><li>You have a medical condition that affects ovulation, such as <strong>PCOS</strong> (polycystic ovary syndrome) or thyroid disorders.</li><li>You have recently stopped hormonal birth control — it can take a few months for your natural cycle to return.</li><li>You Are over 35 — fertility naturally declines with age, and an ovulation calculator cannot assess egg quality.</li></ul>' . "\n\n";
        $html .= '<p>If you have been trying for over 12 months (or 6 months if you are over 35) without success, please speak with a healthcare professional. There is no shame in asking for help — it is one of the bravest things you can do.</p>' . "\n\n";

        $html .= '<h2>A Note from Us</h2>' . "\n\n";
        $html .= '<p>We built OvuDay because we believe every woman deserves access to simple, private fertility tools — without paying for expensive apps or giving away personal health data. Whether you are just starting to think about trying, or you have been on this journey for a while, we hope this calculator makes the process a little less stressful and a little more empowering.</p>' . "\n\n";
        $html .= '<p>Your body is remarkable. And understanding it — even just a little better — is a beautiful first step.</p>' . "\n\n";
        $html .= '<p><strong><a href="/">→ Try the free OvuDay Ovulation Calculator now</a></strong></p>';

        return $html;
    }

    private static function build_blog2_content( array $images ): string {
        $journal = $images['journal'] ?? '';
        $compare = $images['comparison'] ?? '';
        $bbt     = $images['bbt'] ?? '';

        $journal_img = $journal ? '<figure class="wp-block-image size-large"><img src="' . esc_url($journal) . '" alt="Flat lay of a menstrual cycle tracking journal with a pink pen, phone calendar, and herbal tea on a marble desk" /><figcaption>Tracking your cycle — even when it is unpredictable — is the single most powerful thing you can do.</figcaption></figure>' : '';
        $compare_img = $compare ? '<figure class="wp-block-image size-large"><img src="' . esc_url($compare) . '" alt="Side-by-side comparison of a regular 28-day menstrual cycle versus an irregular cycle with varying lengths of 24 to 40 days" /><figcaption>Your cycle does not need to be "textbook" — it just needs to be understood.</figcaption></figure>' : '';
        $bbt_img     = $bbt     ? '<figure class="wp-block-image size-large"><img src="' . esc_url($bbt)     . '" alt="Digital basal body thermometer resting on a white pillow with a BBT chart notebook in soft morning sunrise light" /><figcaption>A basal body thermometer and 60 seconds each morning — that is all it takes to confirm ovulation.</figcaption></figure>' : '';

        $h = '';
        $h .= '<p>If your period shows up whenever it feels like it — sometimes 25 days, sometimes 40, sometimes somewhere in between — you have probably been told that <a href="/blog/ovulation-calculator-guide">an ovulation calculator</a> will not work for you. That it is only designed for women with perfect, textbook 28-day cycles.</p>' . "\n\n";
        $h .= '<p>That is not entirely true. While a standard <strong>ovulation calculator</strong> does assume regularity, there are practical, science-backed methods to <strong>calculate ovulation with irregular periods</strong> — and they work. You just need a slightly different approach.</p>' . "\n\n";
        $h .= '<p>This guide is for every woman who has Googled <em>"ovulation calculator irregular periods"</em> at 2 AM, feeling frustrated and unsure. We see you — and we are here to help.</p>' . "\n\n";
        $h .= $journal_img . "\n\n";

        $h .= '<h2>What Counts as an "Irregular" Period?</h2>' . "\n\n";
        $h .= '<p>Before we dive into solutions, let us define what irregular actually means. A menstrual cycle is considered <strong>irregular</strong> if:</p>' . "\n\n";
        $h .= '<ul><li>Your cycle length varies by <strong>more than 7–9 days</strong> from month to month</li><li>Your cycles are consistently <strong>shorter than 21 days</strong> or <strong>longer than 35 days</strong></li><li>You occasionally <strong>skip periods entirely</strong> (without pregnancy)</li><li>Your period length or flow changes significantly each month</li></ul>' . "\n\n";
        $h .= '<p>A cycle that is consistently 33 days is not irregular — it is just not 28 days. That is perfectly normal. Irregularity is about <em>unpredictability</em>, not length.</p>' . "\n\n";
        $h .= $compare_img . "\n\n";

        $h .= '<h2>Why Are Your Periods Irregular?</h2>' . "\n\n";
        $h .= '<p>Understanding the <em>why</em> can help you choose the right tracking method. Common causes include:</p>' . "\n\n";
        $h .= '<ul><li><strong>PCOS (Polycystic Ovary Syndrome)</strong> — the most common hormonal disorder in women of reproductive age. It disrupts ovulation, causing missed or delayed periods.</li>';
        $h .= '<li><strong>Thyroid disorders</strong> — both hypothyroidism and hyperthyroidism can throw your cycle off.</li>';
        $h .= '<li><strong>Stress and lifestyle factors</strong> — chronic stress, extreme exercise, significant weight changes, and poor sleep all affect the hypothalamus, which controls your cycle.</li>';
        $h .= '<li><strong>Perimenopause</strong> — in the years leading up to menopause, cycles become increasingly unpredictable.</li>';
        $h .= '<li><strong>Coming off birth control</strong> — it can take 3–6 months for your natural cycle to regulate after stopping the pill, IUD, or injection.</li></ul>' . "\n\n";
        $h .= '<p>If you suspect PCOS or a thyroid issue, please see a healthcare provider. These conditions are very manageable once diagnosed.</p>' . "\n\n";

        $h .= '<h2>Can You Still Use an Ovulation Calculator?</h2>' . "\n\n";
        $h .= '<p>Yes — with a caveat. A standard <strong>ovulation calculator for irregular periods</strong> gives you an <em>estimate</em> rather than a guarantee. The key is to use your <strong>shortest recent cycle</strong> as the cycle length input. Here is how:</p>' . "\n\n";
        $h .= '<ol><li>Look at your last 6 cycles and note the <strong>shortest one</strong> (e.g., 26 days).</li><li>Enter that as your cycle length in the <a href="/">OvuDay ovulation calculator</a>.</li><li>The calculator will estimate your earliest possible ovulation — giving you a <strong>starting point</strong> for your fertile window.</li><li>Then use the methods below to confirm ovulation in real time.</li></ol>' . "\n\n";
        $h .= '<p>Think of the calculator as your first clue — not your only one.</p>' . "\n\n";

        $h .= '<h2>5 Methods to Track Ovulation with Irregular Cycles</h2>' . "\n\n";
        $h .= '<p>When your cycle length changes each month, the calendar method alone is not enough. Here are five proven methods — ranked from simplest to most precise — that work regardless of cycle regularity.</p>' . "\n\n";

        $h .= '<h3>1. Cervical Mucus Monitoring (Free, Daily)</h3>' . "\n\n";
        $h .= '<p>Your body produces a visible sign when ovulation is approaching. Cervical mucus changes throughout your cycle:</p>' . "\n\n";
        $h .= '<ul><li><strong>After your period:</strong> Dry or minimal discharge</li><li><strong>Approaching ovulation:</strong> Sticky, then creamy white</li><li><strong>Peak fertility:</strong> Clear, stretchy, and slippery — like raw egg whites</li><li><strong>After ovulation:</strong> Returns to thick, sticky, or dry</li></ul>' . "\n\n";
        $h .= '<p>When you notice that clear, stretchy mucus, <strong>ovulation is likely within 1–2 days</strong>. This method costs nothing and works even with the most irregular cycles.</p>' . "\n\n";

        $h .= '<h3>2. Basal Body Temperature (BBT) Charting</h3>' . "\n\n";
        $h .= $bbt_img . "\n\n";
        $h .= '<p><strong>Basal body temperature</strong> is your resting temperature, taken first thing each morning before getting out of bed. Here is why it matters:</p>' . "\n\n";
        $h .= '<ul><li>Before ovulation, BBT is typically between <strong>36.1–36.4°C</strong> (97.0–97.7°F).</li><li>After ovulation, progesterone causes a sustained rise of <strong>0.2–0.5°C</strong> (0.4–1.0°F).</li><li>This elevated temperature persists until your next period (or continues if you are pregnant).</li></ul>' . "\n\n";
        $h .= '<p>BBT charting <strong>confirms ovulation after it happens</strong>. It will not predict the exact day in advance, but over 3–4 months, you will see your pattern emerge — even with irregular cycles.</p>' . "\n\n";
        $h .= '<p><strong>Tip:</strong> Use a dedicated BBT thermometer (accurate to 0.01°C) and take it at the same time each morning.</p>' . "\n\n";

        $h .= '<h3>3. OPK (Ovulation Predictor Kit) Test Strips</h3>' . "\n\n";
        $h .= '<p>OPKs detect the <strong>LH (luteinising hormone) surge</strong> that happens 24–36 hours before ovulation. They are simple urine test strips you can use at home.</p>' . "\n\n";
        $h .= '<p>For irregular cycles, the challenge is knowing <em>when to start testing</em>. Our recommendation:</p>' . "\n\n";
        $h .= '<ul><li>Start testing from <strong>day 10</strong> of your cycle (or earlier if your shortest cycle is under 25 days).</li><li>Test once daily until you see a faint line, then switch to <strong>twice daily</strong> to catch the surge.</li><li>A positive OPK means ovulation is likely <strong>within the next 12–36 hours</strong>.</li></ul>' . "\n\n";
        $h .= '<p>Budget-friendly tip: buy test strips in bulk online rather than branded kits — they use the same technology at a fraction of the price.</p>' . "\n\n";

        $h .= '<h3>4. Cervical Position Checks</h3>' . "\n\n";
        $h .= '<p>Your cervix changes position and texture throughout your cycle. During your fertile window:</p>' . "\n\n";
        $h .= '<ul><li>The cervix moves <strong>higher</strong>, becomes <strong>softer</strong>, and the opening becomes slightly <strong>more open</strong></li><li>After ovulation, it drops lower, firms up, and closes</li></ul>' . "\n\n";
        $h .= '<p>This method takes practice to learn, but combined with mucus monitoring, it provides a very reliable natural fertility signal.</p>' . "\n\n";

        $h .= '<h3>5. Cycle Tracking Apps + Our Calculator Combined</h3>' . "\n\n";
        $h .= '<p>The most effective approach combines multiple methods. Here is a practical workflow:</p>' . "\n\n";
        $h .= '<ol><li>Use the <a href="/">OvuDay ovulation calculator</a> with your shortest cycle to get a baseline estimate.</li><li>Start OPK testing a few days before that estimated date.</li><li>Monitor cervical mucus daily for that egg-white consistency.</li><li>Track BBT each morning to confirm ovulation occurred.</li><li>Log everything in a simple journal or app.</li></ol>' . "\n\n";
        $h .= '<p>After 3–4 months, you will have a personalised picture of your fertility pattern — even if your cycle never settles into a perfect rhythm.</p>' . "\n\n";

        $h .= '<h2>PCOS and Ovulation: What You Need to Know</h2>' . "\n\n";
        $h .= '<p>If you have <strong>PCOS</strong>, irregular ovulation is the core challenge. Your body may produce multiple LH surges without actually releasing an egg (called anovulatory cycles). This means:</p>' . "\n\n";
        $h .= '<ul><li>OPK results can show <strong>false positives</strong> — an LH surge without actual ovulation.</li><li>BBT charting becomes your most reliable confirmation tool (no temperature rise = no ovulation that cycle).</li><li>Cervical mucus may show fertile-type patterns multiple times per cycle.</li></ul>' . "\n\n";
        $h .= '<p>For women with PCOS, we strongly recommend combining BBT charting with OPK testing. If BBT confirms ovulation after an LH surge, you can trust that cycle. If it does not, you will know to keep trying.</p>' . "\n\n";
        $h .= '<p>Many women with PCOS do ovulate — just not every month. <strong>Understanding which cycles are ovulatory is the key to timing conception.</strong></p>' . "\n\n";

        $h .= '<h2>When to See a Doctor</h2>' . "\n\n";
        $h .= '<p>Self-tracking is empowering, but some situations call for professional help:</p>' . "\n\n";
        $h .= '<ul><li>You have not had a period in <strong>90 days or more</strong></li><li>You have been tracking and timing intercourse for <strong>12 months</strong> (or 6 months if over 35) without success</li><li>You suspect <strong>PCOS or a thyroid condition</strong> but have not been diagnosed</li><li>Your cycles are <strong>consistently shorter than 21 days</strong></li><li>You experience <strong>severe pain, heavy bleeding, or spotting</strong> between periods</li></ul>' . "\n\n";
        $h .= '<p>A reproductive endocrinologist can run blood tests (FSH, LH, AMH, thyroid panel) and imaging to understand exactly what is happening. Asking for help is not giving up — it is leveling up.</p>' . "\n\n";

        $h .= '<h2>You Are Not Broken — Your Cycle Is Just Unique</h2>' . "\n\n";
        $h .= '<p>Here is something nobody tells you enough: <strong>only about 13% of women have a consistent 28-day cycle</strong>. The rest of us? We are all somewhere on the spectrum of "irregular" — and that is completely, biologically normal.</p>' . "\n\n";
        $h .= '<p>An irregular cycle does not mean something is wrong with you. It does not mean you cannot conceive. It just means you need to listen a little more closely to your body — and use the right tools to decode what it is telling you.</p>' . "\n\n";
        $h .= '<p>Start with our <a href="/">free ovulation calculator</a> to get your baseline, then layer in the tracking methods that feel right for you. If you are new to all of this, our <a href="/blog/ovulation-calculator-guide">complete guide to ovulation calculators</a> is a great first read.</p>' . "\n\n";
        $h .= '<p>Your journey is valid. Your body is capable. And understanding your cycle — even the messy, unpredictable parts — is the most empowering step you can take.</p>' . "\n\n";
        $h .= '<p><strong><a href="/">→ Calculate your estimated fertile window now — free and private</a></strong></p>';

        return $h;
    }
}
