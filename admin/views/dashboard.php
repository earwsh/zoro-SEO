<?php
/**
 * Admin Dashboard View for EX SEO Cluster
 */

if (!defined('ABSPATH')) {
    exit;
}

$summary = isset($data['summary']) ? $data['summary'] : array();
$posts = isset($data['posts']) ? $data['posts'] : array();
$categories = isset($data['categories']) ? $data['categories'] : array();
$has_data = !empty($posts);
?>

<div class="wrap ex-seo-cluster-wrap">
    <!-- Header -->
    <div class="ex-seo-cluster-header">
        <div class="ex-seo-cluster-title">
            <h1 style="display: flex; align-items: center; gap: 8px;">
                <span style="color: #2271b1; font-weight: 800;">Zoro SEO</span>
                <span style="font-size: 16px; color: #64748b; font-weight: 500;">| تحلیل پیلار-کلاستر و شبکه لینک‌های داخلی</span>
            </h1>
            <p>استخراج ساختار موضوعی، کشف صفحات یتیم (Orphan Pages) و راهنمای لینک‌سازی برای تدوین تقویم محتوای ماهانه</p>
        </div>
        <div class="ex-seo-cluster-actions">
            <?php if ($has_data): ?>
                <button type="button" class="button button-secondary ex-seo-btn-export" data-export-type="summary">
                    <span class="dashicons dashicons-media-spreadsheet"></span> خروجی اکسل مقالات
                </button>
                <button type="button" class="button button-secondary ex-seo-btn-export" data-export-type="matrix">
                    <span class="dashicons dashicons-list-view"></span> خروجی ماتریس لینک‌ها
                </button>
                <button type="button" class="button button-secondary ex-seo-btn-export" data-export-type="tree_html" style="background: #eff6ff; color: #1e40af; border-color: #bfdbfe;">
                    <span class="dashicons dashicons-networking"></span> نقشه درختی تعاملی (HTML)
                </button>
            <?php endif; ?>
            <button type="button" id="ex-seo-btn-start-scan" class="button button-primary">
                <span class="dashicons dashicons-update"></span> <?php echo $has_data ? 'بروزرسانی و اسکن مجدد' : 'آغاز اولین اسکن وبسایت'; ?>
            </button>
        </div>
    </div>

    <!-- Scan Progress Box (Hidden by default) -->
    <div id="ex-seo-scan-progress" class="ex-seo-scan-progress-box" style="display: none;">
        <div class="ex-seo-scan-status-text">
            <span id="ex-seo-progress-text">در حال آماده‌سازی برای اسکن...</span>
            <span id="ex-seo-progress-percent">0%</span>
        </div>
        <div class="ex-seo-progress-bar-bg">
            <div id="ex-seo-progress-fill" class="ex-seo-progress-bar-fill"></div>
        </div>
    </div>

    <?php if (!$has_data): ?>
        <!-- Empty State Prompt -->
        <div class="notice notice-info" style="padding: 24px; border-radius: 8px;">
            <h3 style="margin-top:0;">هنوز داده‌ای اسکن نشده است</h3>
            <p>برای استخراج پیوندها، شناخت پیلارها و کشف صفحات یتیم سایتتان، روی دکمه <strong>«آغاز اولین اسکن وبسایت»</strong> کلیک کنید.</p>
        </div>
    <?php else: ?>

        <!-- Stats Overview Cards -->
        <div class="ex-seo-stats-grid">
            <div class="ex-seo-stat-card">
                <div class="ex-seo-stat-icon stat-icon-posts">
                    <span class="dashicons dashicons-admin-post"></span>
                </div>
                <div class="ex-seo-stat-content">
                    <h3><?php echo number_format_i18n($summary['total_posts']); ?></h3>
                    <p>کل مقالات بررسی‌شده</p>
                </div>
            </div>

            <div class="ex-seo-stat-card">
                <div class="ex-seo-stat-icon stat-icon-links">
                    <span class="dashicons dashicons-admin-links"></span>
                </div>
                <div class="ex-seo-stat-content">
                    <h3><?php echo number_format_i18n($summary['total_internal_links']); ?></h3>
                    <p>کل لینک‌های داخلی کشف‌شده</p>
                </div>
            </div>

            <div class="ex-seo-stat-card">
                <div class="ex-seo-stat-icon stat-icon-orphans">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="ex-seo-stat-content">
                    <h3 style="color: #dc2626;"><?php echo number_format_i18n($summary['orphans_count']); ?></h3>
                    <p>صفحات یتیم (بدون ورودی)</p>
                </div>
            </div>

            <div class="ex-seo-stat-card">
                <div class="ex-seo-stat-icon stat-icon-pillars">
                    <span class="dashicons dashicons-star-filled"></span>
                </div>
                <div class="ex-seo-stat-content">
                    <h3><?php echo number_format_i18n($summary['pillars_count']); ?></h3>
                    <p>ستون‌های محتوا (Pillars)</p>
                </div>
            </div>

            <div class="ex-seo-stat-card">
                <div class="ex-seo-stat-icon stat-icon-deadends">
                    <span class="dashicons dashicons-chart-pie"></span>
                </div>
                <div class="ex-seo-stat-content">
                    <h3><?php echo esc_html($summary['avg_links_per_post']); ?></h3>
                    <p>میانگین لینک در هر مقاله</p>
                </div>
            </div>
        </div>

        <!-- Category Clusters Overview -->
        <?php if (!empty($categories)): ?>
            <div style="background: #ffffff; padding: 20px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #e2e8f0;">
                <h3 style="margin-top: 0; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-category"></span> ساختار خوشه‌های موضوعی (دسته‌بندی‌ها)
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 15px;">
                    <?php foreach ($categories as $cat): ?>
                        <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; background: #f8fafc;">
                            <div style="font-weight: 700; font-size: 14px; color: #0f172a; margin-bottom: 8px; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">
                                <?php echo esc_html($cat['name']); ?>
                                <span style="font-size: 12px; color: #64748b; font-weight: normal; float: left;">(<?php echo esc_html($cat['posts_count']); ?> مقاله)</span>
                            </div>
                            <div style="font-size: 12.5px; color: #475569; line-height: 1.8;">
                                <div>تعداد لینک‌های داخلی: <strong><?php echo esc_html($cat['total_inlinks']); ?></strong></div>
                                <div>صفحات یتیم در این دسته: <strong style="color: <?php echo $cat['orphans_count'] > 0 ? '#dc2626' : '#16a34a'; ?>;"><?php echo esc_html($cat['orphans_count']); ?></strong></div>
                                <?php if (!empty($cat['recommended_pillar'])): ?>
                                    <div style="margin-top: 6px; padding-top: 6px; border-top: 1px dashed #cbd5e1;">
                                        ⭐ پیلار پیشنهادی:
                                        <a href="<?php echo esc_url($cat['recommended_pillar']['url']); ?>" target="_blank" style="color: #2271b1; font-weight: 600; text-decoration: none;">
                                            <?php echo esc_html($cat['recommended_pillar']['title']); ?>
                                        </a>
                                        <span style="font-size: 11px; color: #64748b;">(<?php echo esc_html($cat['recommended_pillar']['inlinks_count']); ?> ورودی)</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Table Filters & Search -->
        <div class="ex-seo-controls">
            <div class="ex-seo-filters">
                <input type="text" id="ex-seo-search-input" placeholder="جستجو بر اساس عنوان یا آدرس مقاله..." />
                
                <select id="ex-seo-category-filter">
                    <option value="all">تمام دسته‌بندی‌ها</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat['name']); ?>"><?php echo esc_html($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="ex-seo-role-filter">
                    <option value="all">تمام نقش‌ها</option>
                    <option value="pillar">فقط پیلارها (ستون محتوا)</option>
                    <option value="cluster">فقط خوشه‌ها (Cluster)</option>
                    <option value="orphan">فقط صفحات یتیم (Orphan)</option>
                    <option value="dead_end">فقط صفحات بن‌بست (Dead End)</option>
                </select>
            </div>

            <div style="font-size: 13px; color: #64748b;">
                نمایش <strong><?php echo count($posts); ?></strong> مقاله
            </div>
        </div>

        <!-- Posts Table -->
        <div class="ex-seo-table-container">
            <table class="ex-seo-table">
                <thead>
                    <tr>
                        <th style="width: 32%;">عنوان مقاله و پیوند</th>
                        <th style="width: 14%;">دسته‌بندی</th>
                        <th style="width: 12%;">نقش در کلاستر</th>
                        <th style="width: 9%; text-align: center;">لینک ورودی</th>
                        <th style="width: 9%; text-align: center;">لینک خروجی</th>
                        <th style="width: 14%;">انکر تکست برتر</th>
                        <th style="width: 10%; text-align: center;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $p): ?>
                        <?php
                            $cat_string = !empty($p['categories']) ? implode(', ', $p['categories']) : '';
                            $first_anchor = '';
                            if (!empty($p['anchor_distribution'])) {
                                $anchors_keys = array_keys($p['anchor_distribution']);
                                $first_anchor = $anchors_keys[0] . ' (' . $p['anchor_distribution'][$anchors_keys[0]] . ')';
                            }
                        ?>
                        <tr data-title="<?php echo esc_attr($p['title']); ?>"
                            data-category="<?php echo esc_attr($cat_string); ?>"
                            data-role="<?php echo esc_attr($p['role']); ?>">
                            <td class="ex-seo-post-title-cell">
                                <a href="<?php echo esc_url($p['url']); ?>" target="_blank" title="مشاهده مقاله در تب جدید">
                                    <?php echo esc_html($p['title']); ?>
                                </a>
                                <span class="ex-seo-post-meta">
                                    کلمات: <?php echo number_format_i18n($p['word_count']); ?> | ارجاع‌دهندگان: <?php echo esc_html($p['unique_referring_count']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html($cat_string ? $cat_string : '—'); ?>
                            </td>
                            <td>
                                <span class="badge-role <?php echo esc_attr($p['role_class']); ?>">
                                    <?php echo esc_html($p['role_label']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span class="count-pill count-inlinks <?php echo $p['inlinks_count'] === 0 ? 'zero' : ''; ?>">
                                    <?php echo esc_html($p['inlinks_count']); ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <span class="count-pill count-outlinks <?php echo $p['total_outlinks_count'] === 0 ? 'zero' : ''; ?>">
                                    <?php echo esc_html($p['total_outlinks_count']); ?>
                                </span>
                            </td>
                            <td style="font-size: 12px; color: #475569;">
                                <?php echo esc_html($first_anchor ? $first_anchor : '—'); ?>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="button button-small ex-seo-view-details" data-post-id="<?php echo esc_attr($p['id']); ?>">
                                    مشاهده لینک‌ها
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>

    <!-- Modal for Detailed Links -->
    <div id="ex-seo-modal" class="ex-seo-modal-overlay" style="display: none;">
        <div class="ex-seo-modal-content">
            <div class="ex-seo-modal-header">
                <h2 id="ex-seo-modal-post-title">جزئیات لینک‌های مقاله</h2>
                <button type="button" class="ex-seo-modal-close">&times;</button>
            </div>
            <div class="ex-seo-modal-body">
                <div class="ex-seo-modal-tabs">
                    <button type="button" class="ex-seo-modal-tab-btn active" data-tab="inlinks">
                        لینک‌های ورودی به این مقاله <span id="modal-tab-inlinks-count"></span>
                    </button>
                    <button type="button" class="ex-seo-modal-tab-btn" data-tab="outlinks">
                        لینک‌های خروجی از این مقاله <span id="modal-tab-outlinks-count"></span>
                    </button>
                </div>
                <div id="ex-seo-tab-inlinks" class="ex-seo-modal-tab-pane">
                    <ul id="ex-seo-inlinks-list" class="ex-seo-links-list"></ul>
                </div>
                <div id="ex-seo-tab-outlinks" class="ex-seo-modal-tab-pane" style="display: none;">
                    <ul id="ex-seo-outlinks-list" class="ex-seo-links-list"></ul>
                </div>
            </div>
        </div>
    </div>

</div>
