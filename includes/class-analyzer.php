<?php
/**
 * Analyzer class for EX SEO Cluster
 * Analyzes internal link structure, calculates pillar-cluster roles,
 * identifies orphan and dead-end posts, and groups by topic/category.
 */

if (!defined('ABSPATH')) {
    // If accessed outside WordPress
}

class EX_SEO_Cluster_Analyzer {

    /**
     * Analyze scanned posts and build complete cluster report
     *
     * @param array $posts Scanned posts from scanner
     * @return array Comprehensive analysis result
     */
    public function analyze($posts) {
        if (empty($posts)) {
            return array(
                'summary'    => $this->get_empty_summary(),
                'posts'      => array(),
                'categories' => array(),
                'matrix'     => array(),
            );
        }

        // 1. Build URL to Post ID index and Post ID to Post index
        $url_to_id = array();
        $posts_by_id = array();

        foreach ($posts as $p) {
            $id = $p['id'];
            $norm_url = $p['normalized_url'];
            $url_to_id[$norm_url] = $id;
            $posts_by_id[$id] = $p;
            // Initialize inlinks array and counters
            $posts_by_id[$id]['inlinks'] = array();
            $posts_by_id[$id]['inlinks_count'] = 0;
            $posts_by_id[$id]['unique_referring_count'] = 0;
            $posts_by_id[$id]['anchor_distribution'] = array();
        }

        // 2. Build Inverted Index (Map Outlinks to Target Inlinks)
        $link_matrix = array();
        $total_internal_links = 0;

        foreach ($posts_by_id as $source_id => $source_post) {
            $source_title = $source_post['title'];
            $source_url   = $source_post['url'];
            $outlinks     = isset($source_post['outlinks']) ? $source_post['outlinks'] : array();

            foreach ($outlinks as $link) {
                $target_url = $link['normalized_url'];
                $anchor_text = $link['anchor_text'];
                $is_nofollow = $link['is_nofollow'];

                $target_id = isset($url_to_id[$target_url]) ? $url_to_id[$target_url] : 0;
                $target_title = $target_id ? $posts_by_id[$target_id]['title'] : '';

                $total_internal_links++;

                // Record in link matrix
                $matrix_item = array(
                    'source_id'    => $source_id,
                    'source_title' => $source_title,
                    'source_url'   => $source_url,
                    'target_id'    => $target_id,
                    'target_title' => $target_title,
                    'target_url'   => !empty($target_id) ? $posts_by_id[$target_id]['url'] : $link['raw_url'],
                    'anchor_text'  => $anchor_text,
                    'is_nofollow'  => $is_nofollow,
                );
                $link_matrix[] = $matrix_item;

                // If target is one of our scanned articles, record inlink
                if ($target_id && isset($posts_by_id[$target_id])) {
                    $posts_by_id[$target_id]['inlinks'][] = array(
                        'source_id'    => $source_id,
                        'source_title' => $source_title,
                        'source_url'   => $source_url,
                        'anchor_text'  => $anchor_text,
                        'is_nofollow'  => $is_nofollow,
                    );

                    // Track anchor text count
                    if (!isset($posts_by_id[$target_id]['anchor_distribution'][$anchor_text])) {
                        $posts_by_id[$target_id]['anchor_distribution'][$anchor_text] = 0;
                    }
                    $posts_by_id[$target_id]['anchor_distribution'][$anchor_text]++;
                }
            }
        }

        // 3. Calculate metrics and classify role for each post
        $inlinks_counts = array();
        foreach ($posts_by_id as $id => &$p) {
            $in_count = count($p['inlinks']);
            $p['inlinks_count'] = $in_count;

            // Count unique referring posts
            $unique_referrers = array();
            foreach ($p['inlinks'] as $inl) {
                $unique_referrers[$inl['source_id']] = true;
            }
            $p['unique_referring_count'] = count($unique_referrers);

            // Sort anchor text frequency descending
            arsort($p['anchor_distribution']);

            $inlinks_counts[] = $in_count;
        }
        unset($p);

        // Threshold for pillar candidate (e.g. top 20% or at least 4 inlinks)
        rsort($inlinks_counts);
        $total_posts_count = count($posts_by_id);
        $pillar_threshold = 4;
        if ($total_posts_count > 5) {
            $top_20_index = intval(ceil($total_posts_count * 0.15)) - 1;
            if (isset($inlinks_counts[$top_20_index]) && $inlinks_counts[$top_20_index] >= 3) {
                $pillar_threshold = $inlinks_counts[$top_20_index];
            }
        }

        $orphans_count = 0;
        $dead_ends_count = 0;
        $pillars_count = 0;
        $clusters_count = 0;

        foreach ($posts_by_id as $id => &$p) {
            $in = $p['inlinks_count'];
            $out = $p['total_outlinks_count'];

            if ($in === 0) {
                $role = 'orphan'; // یتیم
                $role_label = 'مقاله یتیم (بدون ورودی)';
                $role_class = 'badge-orphan';
                $orphans_count++;
            } elseif ($in >= $pillar_threshold && $out > 0) {
                $role = 'pillar'; // پیلار
                $role_label = 'ستون محتوا (Pillar)';
                $role_class = 'badge-pillar';
                $pillars_count++;
            } elseif ($out === 0) {
                $role = 'dead_end'; // بن‌بست
                $role_label = 'بن‌بست (بدون خروجی)';
                $role_class = 'badge-deadend';
                $dead_ends_count++;
            } else {
                $role = 'cluster'; // کلاستر
                $role_label = 'خوشه (Cluster)';
                $role_class = 'badge-cluster';
                $clusters_count++;
            }

            $p['role'] = $role;
            $p['role_label'] = $role_label;
            $p['role_class'] = $role_class;
        }
        unset($p);

        // 4. Cluster Grouping by Category
        $categories_data = array();
        foreach ($posts_by_id as $id => $p) {
            $cats = !empty($p['categories']) ? $p['categories'] : array('دسته‌بندی‌نشده');
            foreach ($cats as $cat) {
                if (!isset($categories_data[$cat])) {
                    $categories_data[$cat] = array(
                        'name'              => $cat,
                        'posts_count'       => 0,
                        'total_inlinks'     => 0,
                        'total_outlinks'    => 0,
                        'orphans_count'     => 0,
                        'posts'             => array(),
                        'recommended_pillar'=> null,
                    );
                }
                $categories_data[$cat]['posts_count']++;
                $categories_data[$cat]['total_inlinks'] += $p['inlinks_count'];
                $categories_data[$cat]['total_outlinks'] += $p['total_outlinks_count'];
                if ($p['role'] === 'orphan') {
                    $categories_data[$cat]['orphans_count']++;
                }
                $categories_data[$cat]['posts'][] = array(
                    'id'            => $p['id'],
                    'title'         => $p['title'],
                    'url'           => $p['url'],
                    'inlinks_count' => $p['inlinks_count'],
                    'outlinks_count'=> $p['total_outlinks_count'],
                    'word_count'    => $p['word_count'],
                    'role'          => $p['role'],
                );
            }
        }

        // Recommend best Pillar for each category
        foreach ($categories_data as $cat_name => &$cat_info) {
            // Sort category posts by inlinks descending, then word count descending
            usort($cat_info['posts'], function($a, $b) {
                if ($a['inlinks_count'] === $b['inlinks_count']) {
                    return $b['word_count'] - $a['word_count'];
                }
                return $b['inlinks_count'] - $a['inlinks_count'];
            });

            if (!empty($cat_info['posts'])) {
                $cat_info['recommended_pillar'] = $cat_info['posts'][0];
            }
        }
        unset($cat_info);

        // 5. Overall Site Summary
        $avg_inlinks = $total_posts_count > 0 ? round($total_internal_links / $total_posts_count, 1) : 0;

        $summary = array(
            'total_posts'          => $total_posts_count,
            'total_internal_links' => $total_internal_links,
            'avg_links_per_post'   => $avg_inlinks,
            'orphans_count'        => $orphans_count,
            'dead_ends_count'      => $dead_ends_count,
            'pillars_count'        => $pillars_count,
            'clusters_count'       => $clusters_count,
            'categories_count'     => count($categories_data),
            'scan_time'            => current_time('mysql'),
        );

        return array(
            'summary'    => $summary,
            'posts'      => array_values($posts_by_id),
            'categories' => $categories_data,
            'matrix'     => $link_matrix,
        );
    }

    private function get_empty_summary() {
        return array(
            'total_posts'          => 0,
            'total_internal_links' => 0,
            'avg_links_per_post'   => 0,
            'orphans_count'        => 0,
            'dead_ends_count'      => 0,
            'pillars_count'        => 0,
            'clusters_count'       => 0,
            'categories_count'     => 0,
            'scan_time'            => '',
        );
    }
}
