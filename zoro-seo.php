<?php
/**
 * Plugin Name: Zoro SEO
 * Plugin URI: https://github.com/earwsh/zoro-seo
 * Description: افزونه پیشرفته Zoro SEO برای تحلیل ساختار پیلار-کلاستر و استخراج کامل لینک‌های داخلی مقالات، برگه‌ها و محصولات جهت تدوین تقویم محتوای ماهانه
 * Version: 0.1.0
 * Author: earwsh
 * Author URI: https://github.com/earwsh
 * Text Domain: zoro-seo
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EX_SEO_CLUSTER_VERSION', '0.1.0');
define('EX_SEO_CLUSTER_PATH', plugin_dir_path(__FILE__));
define('EX_SEO_CLUSTER_URL', plugin_dir_url(__FILE__));

// Autoload / Include required classes
require_once EX_SEO_CLUSTER_PATH . 'includes/class-scanner.php';
require_once EX_SEO_CLUSTER_PATH . 'includes/class-analyzer.php';
require_once EX_SEO_CLUSTER_PATH . 'includes/class-exporter.php';

class EX_SEO_Cluster_Plugin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Meta Box in Post/Page/CPT Editor
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));

        // Real-time synchronization on Save / Delete
        add_action('save_post', array($this, 'handle_save_post'), 20, 2);
        add_action('before_delete_post', array($this, 'handle_delete_post'));
        add_action('wp_trash_post', array($this, 'handle_delete_post'));

        // AJAX Actions
        add_action('wp_ajax_ex_seo_cluster_start_scan', array($this, 'ajax_start_scan'));
        add_action('wp_ajax_ex_seo_cluster_scan_batch', array($this, 'ajax_scan_batch'));
        add_action('wp_ajax_ex_seo_cluster_finalize_scan', array($this, 'ajax_finalize_scan'));
        add_action('wp_ajax_ex_seo_cluster_get_details', array($this, 'ajax_get_details'));
        add_action('wp_ajax_ex_seo_cluster_export', array($this, 'ajax_export_csv'));
        add_action('wp_ajax_ex_seo_cluster_save_settings', array($this, 'ajax_save_settings'));
    }

    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        add_menu_page(
            'Zoro SEO | تحلیل پیلار کلاستر و لینک‌های داخلی',
            'Zoro SEO',
            'manage_options',
            'ex-seo-cluster',
            array($this, 'render_dashboard_page'),
            'dashicons-networking',
            28
        );
    }

    /**
     * Enqueue CSS and JS in plugin dashboard and post editor
     */
    public function enqueue_admin_assets($hook) {
        $is_dashboard = ($hook === 'toplevel_page_ex-seo-cluster');
        $is_editor    = ($hook === 'post.php' || $hook === 'post-new.php');

        if (!$is_dashboard && !$is_editor) {
            return;
        }

        wp_enqueue_style(
            'ex-seo-cluster-style',
            EX_SEO_CLUSTER_URL . 'admin/assets/style.css',
            array(),
            EX_SEO_CLUSTER_VERSION
        );

        if ($is_dashboard) {
            wp_enqueue_script(
                'ex-seo-cluster-script',
                EX_SEO_CLUSTER_URL . 'admin/assets/script.js',
                array('jquery'),
                EX_SEO_CLUSTER_VERSION,
                true
            );

            wp_localize_script('ex-seo-cluster-script', 'exSeoClusterData', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('ex_seo_cluster_nonce'),
            ));
        }
    }

    /**
     * Get active configured post types
     */
    public function get_allowed_post_types() {
        $saved = get_option('ex_seo_cluster_post_types', array('post', 'page'));
        if (!is_array($saved) || empty($saved)) {
            $saved = array('post', 'page');
        }
        return array_map('sanitize_key', $saved);
    }

    /**
     * Get all public post types available on site
     */
    public function get_available_post_types() {
        $args = array(
            'public' => true,
        );
        $types = get_post_types($args, 'objects');
        $filtered = array();

        // Exclude attachment and non-content types
        $ignored = array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request');
        foreach ($types as $key => $obj) {
            if (!in_array($key, $ignored, true)) {
                $filtered[$key] = array(
                    'label' => !empty($obj->labels->name) ? $obj->labels->name : ucfirst($key),
                    'slug'  => $key,
                );
            }
        }

        return $filtered;
    }

    /**
     * Register Meta Box for post types
     */
    public function register_meta_boxes() {
        $post_types = $this->get_allowed_post_types();
        foreach ($post_types as $pt) {
            add_meta_box(
                'zoro_seo_in_editor_box',
                '🗡️ Zoro SEO | تحلیل پیلار-کلاستر و لینک‌های داخلی',
                array($this, 'render_meta_box'),
                $pt,
                'normal',
                'high'
            );
        }
    }

    /**
     * Render Meta Box in Editor
     */
    public function render_meta_box($post) {
        if (!$post || empty($post->ID)) {
            return;
        }

        $report = get_option('ex_seo_cluster_report', array());
        $posts_data = isset($report['posts']) ? $report['posts'] : array();

        $current_data = null;
        foreach ($posts_data as $p) {
            if ($p['id'] === $post->ID) {
                $current_data = $p;
                break;
            }
        }

        // If post not scanned yet or no site data
        if (!$current_data) {
            ?>
            <div class="zoro-metabox-wrap zoro-metabox-empty">
                <div class="zoro-metabox-notice">
                    <span class="dashicons dashicons-info"></span>
                    <div>
                        <strong>این نوشته هنوز در شبکه سئوی Zoro تحلیل نشده است.</strong>
                        <p>پس از انتشار یا بروزرسانی، سیستم به طور خودکار لینک‌های داخلی آن را استخراج و جایگاه آن را در کلاستر موضوعی مشخص می‌کند.</p>
                    </div>
                </div>
            </div>
            <?php
            return;
        }

        $role_class = isset($current_data['role_class']) ? $current_data['role_class'] : 'badge-cluster';
        $role_label = isset($current_data['role_label']) ? $current_data['role_label'] : 'خوشه (Cluster)';
        $inlinks    = isset($current_data['inlinks']) ? $current_data['inlinks'] : array();
        $outlinks   = isset($current_data['outlinks']) ? $current_data['outlinks'] : array();
        $is_orphan  = ($current_data['role'] === 'orphan');
        $categories = isset($current_data['categories']) ? $current_data['categories'] : array();

        // Find candidate internal link suggestions from same category
        $suggestions = array();
        if (!empty($categories) && !empty($posts_data)) {
            $cat_primary = $categories[0];
            foreach ($posts_data as $p) {
                if ($p['id'] !== $post->ID && !empty($p['categories']) && in_array($cat_primary, $p['categories'], true)) {
                    $suggestions[] = $p;
                    if (count($suggestions) >= 4) {
                        break;
                    }
                }
            }
        }
        ?>
        <div class="zoro-metabox-wrap">
            <!-- Top Status Row -->
            <div class="zoro-metabox-status-bar">
                <div class="zoro-metabox-role">
                    <span class="zoro-metabox-label">نقش در کلاستر:</span>
                    <span class="badge-role <?php echo esc_attr($role_class); ?>">
                        <?php echo esc_html($role_label); ?>
                    </span>
                </div>
                <div class="zoro-metabox-counters">
                    <span class="count-pill count-inlinks <?php echo count($inlinks) === 0 ? 'zero' : ''; ?>">
                        <strong><?php echo count($inlinks); ?></strong> ورودی (Inlinks)
                    </span>
                    <span class="count-pill count-outlinks <?php echo count($outlinks) === 0 ? 'zero' : ''; ?>">
                        <strong><?php echo count($outlinks); ?></strong> خروجی (Outlinks)
                    </span>
                </div>
            </div>

            <!-- Orphan Page Warning Banner -->
            <?php if ($is_orphan): ?>
                <div class="zoro-metabox-alert alert-orphan">
                    <div class="zoro-alert-icon">⚠️</div>
                    <div class="zoro-alert-text">
                        <strong>هشدار صفحه یتیم (Orphan Page):</strong>
                        <span>هیچ مقاله یا برگه‌ای در سایت به این صفحه لینک نداده است! موتورهای جستجو دسترسی سخت‌تری به صفحات یتیم دارند. توصیه می‌شود حداقل از ۲ مقاله هم‌دسته به این صفحه لینک داخلی اضافه کنید.</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Inlinks Breakdown -->
            <div class="zoro-metabox-section">
                <h4>
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    پیوندهای ورودی به این صفحه (<?php echo count($inlinks); ?> مورد)
                </h4>
                <?php if (!empty($inlinks)): ?>
                    <ul class="zoro-metabox-link-list">
                        <?php foreach (array_slice($inlinks, 0, 5) as $inl): ?>
                            <li>
                                <div class="zoro-link-source">
                                    <a href="<?php echo esc_url($inl['source_url']); ?>" target="_blank">
                                        <?php echo esc_html($inl['source_title']); ?>
                                    </a>
                                </div>
                                <div class="zoro-link-anchor">
                                    انکر تکست: <code><?php echo esc_html($inl['anchor_text']); ?></code>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        <?php if (count($inlinks) > 5): ?>
                            <li class="zoro-link-more">
                                و <?php echo (count($inlinks) - 5); ?> پیوند ورودی دیگر...
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php else: ?>
                    <p class="zoro-empty-note">هیچ صفحه‌ای از سایت به این مطلب لینک نداده است.</p>
                <?php endif; ?>
            </div>

            <!-- Related Cluster Linking Opportunities -->
            <?php if (!empty($suggestions)): ?>
                <div class="zoro-metabox-section">
                    <h4>
                        <span class="dashicons dashicons-networking"></span>
                        پیشنهاد پیوند با مقالات هم‌کلاستر (فرصت‌های لینک‌سازی)
                    </h4>
                    <p class="zoro-hint-note">مقالات زیر در همان دسته‌بندی موضوعی قرار دارند و گزینه‌های مناسبی برای لینک‌سازی دوطرفه هستند:</p>
                    <ul class="zoro-metabox-suggestions-list">
                        <?php foreach ($suggestions as $sug): ?>
                            <li>
                                <div class="zoro-sug-title">
                                    <a href="<?php echo esc_url($sug['url']); ?>" target="_blank">
                                        <?php echo esc_html($sug['title']); ?>
                                    </a>
                                </div>
                                <div class="zoro-sug-meta">
                                    <span class="badge-role <?php echo esc_attr($sug['role_class']); ?>" style="font-size: 10.5px; padding: 2px 6px;">
                                        <?php echo esc_html($sug['role_label']); ?>
                                    </span>
                                    <span style="color: #64748b; font-size: 11px;">(<?php echo esc_html($sug['inlinks_count']); ?> ورودی)</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Real-time hook on save_post
     */
    public function handle_save_post($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (wp_is_post_revision($post_id)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $allowed_types = $this->get_allowed_post_types();
        $post_type = isset($post->post_type) ? $post->post_type : get_post_type($post_id);

        if (!in_array($post_type, $allowed_types, true)) {
            return;
        }

        // If post is not published, remove it from cluster index
        if ($post->post_status !== 'publish') {
            $this->handle_delete_post($post_id);
            return;
        }

        // Scan this single post
        $scanner = new EX_SEO_Cluster_Scanner();
        $scanned_post = $scanner->scan_post($post);

        // Fetch existing raw posts
        $raw_posts = get_option('ex_seo_cluster_raw_posts', null);

        // If no raw posts option exists, try to extract from current report
        if (!is_array($raw_posts)) {
            $report = get_option('ex_seo_cluster_report', array());
            $raw_posts = isset($report['posts']) ? $report['posts'] : array();
        }

        $analyzer = new EX_SEO_Cluster_Analyzer();
        $updated_report = $analyzer->update_single_post($raw_posts, $scanned_post);

        // Separate and update options
        if (!empty($updated_report['matrix'])) {
            update_option('ex_seo_cluster_matrix', $updated_report['matrix'], 'no');
        }

        // Update raw posts array
        $new_raw = array();
        foreach ($updated_report['posts'] as $p) {
            $new_raw[] = array(
                'id'                   => $p['id'],
                'title'                => $p['title'],
                'url'                  => $p['url'],
                'normalized_url'       => $p['normalized_url'],
                'post_type'            => isset($p['post_type']) ? $p['post_type'] : 'post',
                'word_count'           => $p['word_count'],
                'categories'           => $p['categories'],
                'publish_date'         => $p['publish_date'],
                'outlinks'             => $p['outlinks'],
                'total_outlinks_count' => $p['total_outlinks_count'],
            );
        }
        update_option('ex_seo_cluster_raw_posts', $new_raw, 'no');

        // Save lightweight report for dashboard
        unset($updated_report['matrix']);
        update_option('ex_seo_cluster_report', $updated_report, 'no');
    }

    /**
     * Real-time hook on post deletion or trash
     */
    public function handle_delete_post($post_id) {
        $raw_posts = get_option('ex_seo_cluster_raw_posts', null);
        if (!is_array($raw_posts)) {
            $report = get_option('ex_seo_cluster_report', array());
            $raw_posts = isset($report['posts']) ? $report['posts'] : array();
        }

        if (empty($raw_posts)) {
            return;
        }

        $analyzer = new EX_SEO_Cluster_Analyzer();
        $updated_report = $analyzer->remove_single_post($raw_posts, (int) $post_id);

        if (!empty($updated_report['matrix'])) {
            update_option('ex_seo_cluster_matrix', $updated_report['matrix'], 'no');
        }

        $new_raw = array();
        foreach ($updated_report['posts'] as $p) {
            $new_raw[] = array(
                'id'                   => $p['id'],
                'title'                => $p['title'],
                'url'                  => $p['url'],
                'normalized_url'       => $p['normalized_url'],
                'post_type'            => isset($p['post_type']) ? $p['post_type'] : 'post',
                'word_count'           => $p['word_count'],
                'categories'           => $p['categories'],
                'publish_date'         => $p['publish_date'],
                'outlinks'             => $p['outlinks'],
                'total_outlinks_count' => $p['total_outlinks_count'],
            );
        }
        update_option('ex_seo_cluster_raw_posts', $new_raw, 'no');

        unset($updated_report['matrix']);
        update_option('ex_seo_cluster_report', $updated_report, 'no');
    }

    /**
     * AJAX Action: Save Post Types Settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('ex_seo_cluster_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'عدم دسترسی کافی'));
        }

        $post_types = isset($_POST['post_types']) ? (array) $_POST['post_types'] : array('post');
        $sanitized  = array_map('sanitize_key', $post_types);

        if (empty($sanitized)) {
            $sanitized = array('post');
        }

        update_option('ex_seo_cluster_post_types', $sanitized, 'yes');

        wp_send_json_success(array('message' => 'تنظیمات با موفقیت ذخیره شد.'));
    }

    /**
     * Render Admin Dashboard
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این بخش را ندارید.', 'ex-seo-cluster'));
        }

        $data = get_option('ex_seo_cluster_report', array());
        $allowed_post_types = $this->get_allowed_post_types();
        $available_post_types = $this->get_available_post_types();

        include EX_SEO_CLUSTER_PATH . 'admin/views/dashboard.php';
    }

    /**
     * AJAX Step 1: Start Scan
     */
    public function ajax_start_scan() {
        check_ajax_referer('ex_seo_cluster_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'عدم دسترسی'));
        }

        $post_types = isset($_POST['post_types']) && !empty($_POST['post_types']) 
            ? (array) $_POST['post_types'] 
            : $this->get_allowed_post_types();

        $sanitized_types = array_map('sanitize_key', $post_types);
        if (empty($sanitized_types)) {
            $sanitized_types = array('post');
        }

        // Persist chosen post types
        update_option('ex_seo_cluster_post_types', $sanitized_types, 'yes');

        // Fetch all published posts
        $query_args = array(
            'post_type'      => $sanitized_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'DESC',
        );

        $posts_query = new WP_Query($query_args);
        $post_ids = $posts_query->posts;

        // Reset temporary cache
        delete_transient('ex_seo_cluster_temp_scan');
        set_transient('ex_seo_cluster_temp_scan', array(), HOUR_IN_SECONDS);

        // Build URL to ID mapping table
        $scanner = new EX_SEO_Cluster_Scanner();
        $url_map = array();
        foreach ($post_ids as $id) {
            $permalink = get_permalink($id);
            $normalized = $scanner->normalize_url($permalink);
            $url_map[$normalized] = $id;
        }
        set_transient('ex_seo_cluster_url_map', $url_map, HOUR_IN_SECONDS);

        wp_send_json_success(array(
            'total'    => count($post_ids),
            'post_ids' => $post_ids,
        ));
    }

    /**
     * AJAX Step 2: Scan Batch
     */
    public function ajax_scan_batch() {
        check_ajax_referer('ex_seo_cluster_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'عدم دسترسی'));
        }

        $batch_ids = isset($_POST['batch_ids']) ? array_map('intval', $_POST['batch_ids']) : array();
        if (empty($batch_ids)) {
            wp_send_json_success(array('processed' => 0));
        }

        $scanner = new EX_SEO_Cluster_Scanner();
        $url_map = get_transient('ex_seo_cluster_url_map');
        if (is_array($url_map)) {
            $scanner->set_url_map($url_map);
        }

        $batch_results = array();
        foreach ($batch_ids as $id) {
            $post = get_post($id);
            if ($post) {
                $batch_results[] = $scanner->scan_post($post);
            }
        }

        // Append to temporary transient
        $existing = get_transient('ex_seo_cluster_temp_scan');
        if (!is_array($existing)) {
            $existing = array();
        }

        $merged = array_merge($existing, $batch_results);
        set_transient('ex_seo_cluster_temp_scan', $merged, HOUR_IN_SECONDS);

        wp_send_json_success(array(
            'processed' => count($batch_results),
            'total_so_far' => count($merged),
        ));
    }

    /**
     * AJAX Step 3: Finalize Scan and Analyze
     */
    public function ajax_finalize_scan() {
        check_ajax_referer('ex_seo_cluster_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'عدم دسترسی'));
        }

        $all_posts = get_transient('ex_seo_cluster_temp_scan');
        if (!is_array($all_posts) || empty($all_posts)) {
            wp_send_json_error(array('message' => 'هیچ داده‌ای در مرحله اسکن یافت نشد.'));
        }

        $analyzer = new EX_SEO_Cluster_Analyzer();
        $report = $analyzer->analyze($all_posts);

        // Store full link matrix separately to keep main option ultra-lightweight
        if (!empty($report['matrix'])) {
            update_option('ex_seo_cluster_matrix', $report['matrix'], 'no');
        }

        // Save raw posts for instant incremental sync
        update_option('ex_seo_cluster_raw_posts', $all_posts, 'no');

        // Save lightweight report in option
        $report_summary = $report;
        unset($report_summary['matrix']);
        update_option('ex_seo_cluster_report', $report_summary, 'no');

        // Cleanup transients
        delete_transient('ex_seo_cluster_temp_scan');
        delete_transient('ex_seo_cluster_url_map');

        wp_send_json_success(array(
            'summary' => $report['summary'],
        ));
    }

    /**
     * AJAX Step 4: Get Post Details
     */
    public function ajax_get_details() {
        check_ajax_referer('ex_seo_cluster_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(array('message' => 'شناسه مقاله نامعتبر است'));
        }

        $report = get_option('ex_seo_cluster_report', array());
        $posts = isset($report['posts']) ? $report['posts'] : array();

        foreach ($posts as $p) {
            if ($p['id'] === $post_id) {
                wp_send_json_success($p);
            }
        }

        wp_send_json_error(array('message' => 'مقاله یافت نشد'));
    }

    /**
     * Export CSV / HTML
     */
    public function ajax_export_csv() {
        check_ajax_referer('ex_seo_cluster_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('عدم دسترسی');
        }

        $type = isset($_GET['type']) ? sanitize_key($_GET['type']) : 'summary';
        $report = get_option('ex_seo_cluster_report', array());

        if (empty($report) || empty($report['posts'])) {
            wp_die('داده‌ای برای برون‌بری یافت نشد.');
        }

        $exporter = new EX_SEO_Cluster_Exporter();

        if ($type === 'tree_html') {
            $html = $exporter->generate_tree_html($report);
            $exporter->send_download($html, 'seo-pillar-cluster-tree', 'html');
        } elseif ($type === 'matrix') {
            $matrix = get_option('ex_seo_cluster_matrix', array());
            if (empty($matrix) && isset($report['matrix'])) {
                $matrix = $report['matrix'];
            }
            $csv = $exporter->generate_links_matrix_csv($matrix);
            $exporter->send_download($csv, 'internal-links-matrix', 'csv');
        } else {
            $posts = isset($report['posts']) ? $report['posts'] : array();
            $csv = $exporter->generate_posts_summary_csv($posts);
            $exporter->send_download($csv, 'seo-pillar-cluster-summary', 'csv');
        }
        exit;
    }
}

// Initialize Plugin
add_action('plugins_loaded', array('EX_SEO_Cluster_Plugin', 'get_instance'));
