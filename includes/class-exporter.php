<?php
/**
 * Exporter class for EX SEO Cluster
 * Generates CSV files with UTF-8 BOM for perfect Excel compatibility with Persian characters.
 */

if (!defined('ABSPATH')) {
    // If accessed outside WordPress
}

class EX_SEO_Cluster_Exporter {

    /**
     * UTF-8 BOM bytes
     */
    const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * Generate CSV for Posts Summary
     *
     * @param array $posts Scanned & analyzed posts
     * @return string CSV content
     */
    public function generate_posts_summary_csv($posts) {
        $output = fopen('php://temp', 'r+');

        // Write UTF-8 BOM
        fwrite($output, self::UTF8_BOM);

        // Header row
        $headers = array(
            'شناسه',
            'عنوان مقاله',
            'آدرس مقاله (URL)',
            'دسته‌بندی‌ها',
            'نقش در ساختار پیلار-کلاستر',
            'تعداد لینک‌های ورودی داخلی (Inlinks)',
            'تعداد لینک‌های خروجی داخلی (Outlinks)',
            'تعداد مقالات ارجاع‌دهنده مجزا',
            'تعداد کلمات',
            'برترین انکر تکست‌های دریافتی',
            'تاریخ انتشار'
        );
        fputcsv($output, $headers);

        foreach ($posts as $p) {
            $categories_str = !empty($p['categories']) ? implode(' | ', $p['categories']) : 'دسته‌بندی‌نشده';
            
            // Build top anchor texts string
            $top_anchors = array();
            if (!empty($p['anchor_distribution'])) {
                $count = 0;
                foreach ($p['anchor_distribution'] as $anchor => $freq) {
                    $top_anchors[] = "{$anchor} ({$freq})";
                    $count++;
                    if ($count >= 5) break;
                }
            }
            $anchors_str = !empty($top_anchors) ? implode(' - ', $top_anchors) : '-';

            $row = array(
                $p['id'],
                $p['title'],
                $p['url'],
                $categories_str,
                isset($p['role_label']) ? $p['role_label'] : $p['role'],
                $p['inlinks_count'],
                $p['total_outlinks_count'],
                $p['unique_referring_count'],
                $p['word_count'],
                $anchors_str,
                isset($p['publish_date']) ? $p['publish_date'] : ''
            );
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate CSV for Full Links Matrix
     *
     * @param array $matrix Array of link items
     * @return string CSV content
     */
    public function generate_links_matrix_csv($matrix) {
        $output = fopen('php://temp', 'r+');

        // Write UTF-8 BOM
        fwrite($output, self::UTF8_BOM);

        // Header row
        $headers = array(
            'شناسه مقاله مبدأ',
            'عنوان مقاله مبدأ',
            'آدرس مقاله مبدأ',
            'عنوان مقاله مقصد',
            'آدرس مقاله مقصد',
            'متن پیوند (Anchor Text)',
            'نوع پیوند (Follow / Nofollow)'
        );
        fputcsv($output, $headers);

        foreach ($matrix as $link) {
            $follow_status = !empty($link['is_nofollow']) ? 'nofollow' : 'dofollow';
            $target_title = !empty($link['target_title']) ? $link['target_title'] : 'مقاله خارجی یا لینک نامشخص';

            $row = array(
                $link['source_id'],
                $link['source_title'],
                $link['source_url'],
                $target_title,
                $link['target_url'],
                $link['anchor_text'],
                $follow_status
            );
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Generate Standalone Interactive Tree HTML
     *
     * @param array $report Full report data
     * @return string Standalone HTML document
     */
    public function generate_tree_html($report) {
        $summary = isset($report['summary']) ? $report['summary'] : array();
        $categories = isset($report['categories']) ? $report['categories'] : array();
        $posts = isset($report['posts']) ? $report['posts'] : array();
        $posts_json = json_encode($posts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $categories_json = json_encode($categories, JSON_UNESCAPED_UNICODE);

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoro SEO | نقشه درختی پیلار-کلاستر و لینک‌های داخلی وبسایت</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Vazirmatn", Tahoma, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            padding: 24px;
        }
        .header {
            background: #ffffff;
            padding: 24px 30px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            border-right: 6px solid #2563eb;
        }
        .header h1 { font-size: 22px; color: #0f172a; margin-bottom: 6px; }
        .header p { color: #64748b; font-size: 13.5px; }
        .controls { display: flex; gap: 10px; align-items: center; }
        .btn {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover { background: #1d4ed8; }
        .btn-outline {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-outline:hover { background: #f1f5f9; }
        .search-box {
            padding: 8px 14px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            min-width: 250px;
        }
        /* Stats strip */
        .stats-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        .stat-card .val { font-size: 24px; font-weight: 800; color: #0f172a; }
        .stat-card .lbl { font-size: 12px; color: #64748b; }
        
        /* Tree Layout */
        .tree-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
            overflow-x: auto;
        }
        .tree ul {
            padding-right: 25px;
            position: relative;
            list-style: none;
        }
        .tree li {
            margin: 10px 0;
            position: relative;
        }
        .tree li::before {
            content: '';
            position: absolute;
            top: 18px;
            right: -18px;
            width: 16px;
            height: 1px;
            background: #cbd5e1;
        }
        .tree li::after {
            content: '';
            position: absolute;
            top: 0;
            right: -18px;
            bottom: 0;
            width: 1px;
            background: #cbd5e1;
        }
        .tree li:last-child::after { height: 18px; }
        
        /* Tree Nodes */
        .node {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13.5px;
            user-select: none;
        }
        .node:hover {
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07);
            border-color: #94a3b8;
        }
        .node-root {
            background: #1e293b;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            border-color: #1e293b;
        }
        .node-category {
            background: #eff6ff;
            color: #1e40af;
            border-color: #bfdbfe;
            font-weight: 700;
        }
        .node-pillar {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
            font-weight: 700;
        }
        .node-cluster {
            background: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }
        .node-orphan {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
            font-weight: 600;
        }
        .node-deadend {
            background: #f1f5f9;
            color: #475569;
            border-color: #e2e8f0;
        }
        .node-group {
            background: #f8fafc;
            color: #64748b;
            font-weight: 600;
            font-size: 12.5px;
            border: 1px dashed #cbd5e1;
        }
        .badge {
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 10px;
            font-weight: 700;
        }
        .toggle-icon {
            display: inline-block;
            width: 14px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
        }
        .hidden-children { display: none !important; }
        
        /* Detail Drawer */
        .drawer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #ffffff;
            box-shadow: 0 -5px 25px rgba(0, 0, 0, 0.15);
            padding: 24px 30px;
            border-radius: 18px 18px 0 0;
            display: none;
            z-index: 1000;
            max-height: 45vh;
            overflow-y: auto;
            border-top: 3px solid #2563eb;
        }
        .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 10px;
        }
        .drawer-close {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #64748b;
        }
        .drawer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .drawer-col h4 {
            font-size: 13.5px;
            margin-bottom: 8px;
            color: #0f172a;
        }
        .drawer-list {
            list-style: none;
            max-height: 180px;
            overflow-y: auto;
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px;
            font-size: 12.5px;
        }
        .drawer-list li {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
        }
        .drawer-list li:last-child { border-bottom: none; }
        .highlight {
            box-shadow: 0 0 0 3px #f59e0b !important;
            border-color: #d97706 !important;
        }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1 style="display:flex; align-items:center; gap:8px;">
                <span style="color:#2563eb; font-weight:800;">Zoro SEO</span>
                <span style="font-size:17px; color:#64748b; font-weight:normal;">| نقشه درختی پیلار-کلاستر و ساختار پیوندهای داخلی</span>
            </h1>
            <p>راهنمای بصری ارتباط مقالات، ستون‌های محتوا و کشف صفحات یتیم برای تدوین تقویم محتوایی</p>
        </div>
        <div class="controls">
            <input type="text" id="searchInput" class="search-box" placeholder="جستجوی مقاله در درخت...">
            <button type="button" class="btn btn-outline" id="btnExpandAll">باز کردن همه</button>
            <button type="button" class="btn btn-outline" id="btnCollapseAll">بستن همه</button>
            <button type="button" class="btn" onclick="window.print()">چاپ / ذخیره PDF</button>
        </div>
    </div>

    <div class="stats-strip">
        <div class="stat-card">
            <div class="val"><?php echo intval(isset($summary['total_posts']) ? $summary['total_posts'] : count($posts)); ?></div>
            <div class="lbl">کل مقالات سایت</div>
        </div>
        <div class="stat-card">
            <div class="val" style="color: #2563eb;"><?php echo intval(isset($summary['total_internal_links']) ? $summary['total_internal_links'] : 0); ?></div>
            <div class="lbl">کل لینک‌های داخلی شناسایی‌شده</div>
        </div>
        <div class="stat-card">
            <div class="val" style="color: #dc2626;"><?php echo intval(isset($summary['orphans_count']) ? $summary['orphans_count'] : 0); ?></div>
            <div class="lbl">صفحات یتیم (بدون ورودی)</div>
        </div>
        <div class="stat-card">
            <div class="val" style="color: #d97706;"><?php echo intval(isset($summary['pillars_count']) ? $summary['pillars_count'] : 0); ?></div>
            <div class="lbl">ستون‌های محتوا (Pillars)</div>
        </div>
    </div>

    <div class="tree-container">
        <div class="tree" id="treeRoot">
            <ul>
                <li>
                    <div class="node node-root" data-toggle>
                        <span class="toggle-icon">▼</span>
                        <span>🌐 پیلار-کلاستر وبسایت</span>
                        <span class="badge" style="background:#334155;"><?php echo count($categories); ?> خوشه دسته‌بندی</span>
                    </div>
                    <ul id="categoriesBranch">
                        <!-- Rendered by JS -->
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <!-- Detail Drawer -->
    <div class="drawer" id="nodeDrawer">
        <div class="drawer-header">
            <div>
                <h3 id="drawerTitle" style="font-size: 16px; color:#0f172a;">عنوان مقاله</h3>
                <a id="drawerUrl" href="#" target="_blank" style="font-size: 12px; color: #2563eb; text-decoration: none;">مشاهده مقاله در سایت</a>
            </div>
            <button class="drawer-close" id="drawerClose">&times;</button>
        </div>
        <div class="drawer-grid">
            <div class="drawer-col">
                <h4>🔗 لینک‌های ورودی به این مقاله (<span id="drawerInCount">0</span>)</h4>
                <ul class="drawer-list" id="drawerInlinks"></ul>
            </div>
            <div class="drawer-col">
                <h4>📤 لینک‌های خروجی از این مقاله (<span id="drawerOutCount">0</span>)</h4>
                <ul class="drawer-list" id="drawerOutlinks"></ul>
            </div>
        </div>
    </div>

    <script>
        const categoriesData = <?php echo $categories_json; ?>;
        const postsData = <?php echo $posts_json; ?>;
        const postsMap = {};
        postsData.forEach(p => { postsMap[p.id] = p; });

        const $categoriesBranch = document.getElementById('categoriesBranch');

        // Build Tree HTML dynamically
        Object.keys(categoriesData).forEach(catName => {
            const cat = categoriesData[catName];
            const catLi = document.createElement('li');

            // Find Pillar(s) in this category
            const pillarPosts = [];
            const clusterPosts = [];
            const orphanPosts = [];

            cat.posts.forEach(pRef => {
                const p = postsMap[pRef.id] || pRef;
                if (p.role === 'pillar') {
                    pillarPosts.push(p);
                } else if (p.role === 'orphan') {
                    orphanPosts.push(p);
                } else {
                    clusterPosts.push(p);
                }
            });

            // If no designated pillar but recommended pillar exists
            if (pillarPosts.length === 0 && cat.recommended_pillar) {
                const recId = cat.recommended_pillar.id;
                const recIdx = clusterPosts.findIndex(item => item.id === recId);
                if (recIdx !== -1) {
                    const promoted = clusterPosts.splice(recIdx, 1)[0];
                    promoted.is_recommended_pillar = true;
                    pillarPosts.push(promoted);
                }
            }

            let catHtml = `
                <div class="node node-category" data-toggle>
                    <span class="toggle-icon">▼</span>
                    <span>📁 دسته: ${escapeHtml(catName)}</span>
                    <span class="badge" style="background:#dbeafe; color:#1e40af;">${cat.posts_count} مقاله</span>
                </div>
                <ul>
            `;

            // 1. Pillar Nodes
            pillarPosts.forEach(pil => {
                catHtml += `
                    <li>
                        <div class="node node-pillar" data-post-id="${pil.id}">
                            <span class="toggle-icon">▼</span>
                            <span>⭐ ${escapeHtml(pil.title)}</span>
                            <span class="badge" style="background:#fef3c7; color:#92400e;">پیلار (${pil.inlinks_count} ورودی)</span>
                        </div>
                        <ul>
                `;

                // Add cluster posts under this pillar
                clusterPosts.forEach(cp => {
                    catHtml += `
                        <li>
                            <div class="node node-cluster" data-post-id="${cp.id}">
                                <span>📄 ${escapeHtml(cp.title)}</span>
                                <span class="badge" style="background:#f1f5f9; color:#475569;">${cp.inlinks_count} ورودی | ${cp.outlinks_count} خروجی</span>
                            </div>
                        </li>
                    `;
                });

                catHtml += `</ul></li>`;
            });

            // If there were no pillars at all
            if (pillarPosts.length === 0) {
                clusterPosts.forEach(cp => {
                    catHtml += `
                        <li>
                            <div class="node node-cluster" data-post-id="${cp.id}">
                                <span>📄 ${escapeHtml(cp.title)}</span>
                                <span class="badge" style="background:#f1f5f9; color:#475569;">${cp.inlinks_count} ورودی</span>
                            </div>
                        </li>
                    `;
                });
            }

            // 2. Orphan Posts branch (Highlighted for monthly content calendar)
            if (orphanPosts.length > 0) {
                catHtml += `
                    <li>
                        <div class="node node-group" data-toggle style="color: #dc2626; border-color: #fecaca; background: #fff5f5;">
                            <span class="toggle-icon">▼</span>
                            <span>⚠️ صفحات یتیم این دسته (نیاز به لینک‌سازی در تقویم محتوا)</span>
                            <span class="badge" style="background:#fee2e2; color:#dc2626;">${orphanPosts.length} مقاله</span>
                        </div>
                        <ul>
                `;
                orphanPosts.forEach(op => {
                    catHtml += `
                        <li>
                            <div class="node node-orphan" data-post-id="${op.id}">
                                <span>❌ ${escapeHtml(op.title)}</span>
                                <span class="badge" style="background:#fee2e2; color:#b91c1c;">۰ ورودی (یتیم)</span>
                            </div>
                        </li>
                    `;
                });
                catHtml += `</ul></li>`;
            }

            catHtml += `</ul>`;
            catLi.innerHTML = catHtml;
            $categoriesBranch.appendChild(catLi);
        });

        // Toggle Expand/Collapse
        document.addEventListener('click', function(e) {
            const toggleNode = e.target.closest('[data-toggle]');
            if (toggleNode) {
                const childUl = toggleNode.nextElementSibling;
                if (childUl && childUl.tagName === 'UL') {
                    childUl.classList.toggle('hidden-children');
                    const icon = toggleNode.querySelector('.toggle-icon');
                    if (icon) {
                        icon.textContent = childUl.classList.contains('hidden-children') ? '◀' : '▼';
                    }
                }
            }

            // Node Click - Open Details Drawer
            const postNode = e.target.closest('[data-post-id]');
            if (postNode) {
                const postId = parseInt(postNode.getAttribute('data-post-id'), 10);
                const post = postsMap[postId];
                if (post) {
                    showNodeDetails(post);
                }
            }
        });

        // Expand / Collapse All
        document.getElementById('btnExpandAll').addEventListener('click', () => {
            document.querySelectorAll('.tree ul').forEach(ul => ul.classList.remove('hidden-children'));
            document.querySelectorAll('.toggle-icon').forEach(icon => icon.textContent = '▼');
        });
        document.getElementById('btnCollapseAll').addEventListener('click', () => {
            document.querySelectorAll('.tree ul ul').forEach(ul => ul.classList.add('hidden-children'));
            document.querySelectorAll('.toggle-icon').forEach(icon => icon.textContent = '◀');
        });

        // Search Filter
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim().toLowerCase();
            document.querySelectorAll('.node').forEach(node => {
                node.classList.remove('highlight');
                if (q.length > 1 && node.textContent.toLowerCase().includes(q)) {
                    node.classList.add('highlight');
                    // Ensure parents are expanded
                    let parent = node.parentElement;
                    while (parent && parent.id !== 'treeRoot') {
                        if (parent.tagName === 'UL') {
                            parent.classList.remove('hidden-children');
                        }
                        parent = parent.parentElement;
                    }
                }
            });
        });

        // Drawer
        const drawer = document.getElementById('nodeDrawer');
        document.getElementById('drawerClose').addEventListener('click', () => {
            drawer.style.display = 'none';
        });

        function showNodeDetails(post) {
            drawer.style.display = 'block';
            document.getElementById('drawerTitle').textContent = post.title;
            const urlEl = document.getElementById('drawerUrl');
            urlEl.href = post.url;
            urlEl.textContent = post.url;

            const inlinks = post.inlinks || [];
            const outlinks = post.outlinks || [];

            document.getElementById('drawerInCount').textContent = inlinks.length;
            document.getElementById('drawerOutCount').textContent = outlinks.length;

            const inList = document.getElementById('drawerInlinks');
            if (inlinks.length > 0) {
                inList.innerHTML = inlinks.map(l => `
                    <li>
                        <strong>${escapeHtml(l.anchor_text || '[بدون انکر]')}</strong>
                        <div style="font-size:11px; color:#64748b;">از مقاله: ${escapeHtml(l.source_title)}</div>
                    </li>
                `).join('');
            } else {
                inList.innerHTML = '<li style="color:#dc2626;">هیچ مقاله دیگری به این صفحه لینک نداده است (صفحه یتیم).</li>';
            }

            const outList = document.getElementById('drawerOutlinks');
            if (outlinks.length > 0) {
                outList.innerHTML = outlinks.map(l => `
                    <li>
                        <strong>${escapeHtml(l.anchor_text || '[لینک]')}</strong>
                        <div style="font-size:11px; color:#64748b;">به مقصد: ${escapeHtml(l.raw_url)}</div>
                    </li>
                `).join('');
            } else {
                outList.innerHTML = '<li style="color:#64748b;">این مقاله به هیچ لینک داخلی دیگری اشاره نکرده است.</li>';
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Output file directly to browser for download
     *
     * @param string $content File content
     * @param string $filename Filename without extension
     * @param string $extension Extension (csv or html)
     */
    public function send_download($content, $filename, $extension = 'csv') {
        if (headers_sent()) {
            return;
        }

        $extension = ($extension === 'html') ? 'html' : 'csv';
        $mime_type = ($extension === 'html') ? 'text/html; charset=UTF-8' : 'text/csv; charset=UTF-8';
        $filename_clean = sanitize_file_name($filename . '-' . date('Y-m-d') . '.' . $extension);

        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $filename_clean . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    }
}

