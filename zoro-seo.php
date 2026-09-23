<?php
/**
 * Plugin Name: Zoro SEO
 * Plugin URI: https://github.com/earwsh/zoro-seo
 * Description: افزونه پیشرفته Zoro SEO برای تحلیل ساختار پیلار-کلاستر و استخراج کامل لینک‌های داخلی مقالات جهت تدوین تقویم محتوای ماهانه
 * Version: 0.0.1
 * Author: earwsh
 * Author URI: https://github.com/earwsh
 * Text Domain: zoro-seo
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EX_SEO_CLUSTER_VERSION', '0.0.1');
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

        // AJAX Actions
        add_action('wp_ajax_ex_seo_cluster_start_scan', array($this, 'ajax_start_scan'));
        add_action('wp_ajax_ex_seo_cluster_scan_batch', array($this, 'ajax_scan_batch'));
        add_action('wp_ajax_ex_seo_cluster_finalize_scan', array($this, 'ajax_finalize_scan'));
        add_action('wp_ajax_ex_seo_cluster_get_details', array($this, 'ajax_get_details'));
        add_action('wp_ajax_ex_seo_cluster_export', array($this, 'ajax_export_csv'));
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
     * Enqueue CSS and JS in plugin dashboard
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_ex-seo-cluster') {
            return;
        }

        wp_enqueue_style(
            'ex-seo-cluster-style',
            EX_SEO_CLUSTER_URL . 'admin/assets/style.css',
            array(),
            EX_SEO_CLUSTER_VERSION
        );

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

    /**
     * Render Admin Dashboard
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما اجازه دسترسی به این بخش را ندارید.', 'ex-seo-cluster'));
        }

        $data = get_option('ex_seo_cluster_report', array());
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

        $post_types = isset($_POST['post_types']) ? (array) $_POST['post_types'] : array('post');
        $sanitized_types = array_map('sanitize_key', $post_types);

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

        // Save report in option
        update_option('ex_seo_cluster_report', $report, 'no');

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
     * Export CSV
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
            $matrix = isset($report['matrix']) ? $report['matrix'] : array();
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
