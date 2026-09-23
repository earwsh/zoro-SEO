<?php
/**
 * Test script for EX SEO Cluster Engine
 */

require_once __DIR__ . '/../includes/class-scanner.php';
require_once __DIR__ . '/../includes/class-analyzer.php';
require_once __DIR__ . '/../includes/class-exporter.php';

// Mock helper functions if not in WP environment
if (!function_exists('untrailingslashit')) {
    function untrailingslashit($url) {
        return rtrim($url, '/\\');
    }
}
if (!function_exists('current_time')) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

echo "=== Running EX SEO Cluster Unit Tests ===\n\n";

$home_url = 'https://mysite.com';
$scanner = new EX_SEO_Cluster_Scanner($home_url);

// Test 1: URL Normalization and Internal Check
echo "Test 1: URL Normalization and Internal Check...\n";
assert($scanner->is_internal_url('https://mysite.com/blog/seo-guide/'));
assert($scanner->is_internal_url('https://www.mysite.com/blog/seo-guide/'));
assert($scanner->is_internal_url('/blog/seo-guide/'));
assert(!$scanner->is_internal_url('https://google.com'));
assert(!$scanner->is_internal_url('#'));
assert(!$scanner->is_internal_url('mailto:info@mysite.com'));

$norm1 = $scanner->normalize_url('https://mysite.com/آموزش-سئو/');
$norm2 = $scanner->normalize_url('/آموزش-سئو/');
assert($norm1 === $norm2, "Normalized Persian URLs should match!");
echo " -> Passed!\n\n";

// Test 2: Link Extraction from HTML Content
echo "Test 2: Link Extraction with Persian Anchors & Rel...\n";
$html = '
<p>برای شروع سئو بهتر است <a href="https://mysite.com/آموزش-سئو/">راهنمای جامع سئو</a> را بخوانید.</p>
<p>همچنین مقاله <a href="/تحقیق-کلمات-کلیدی/" rel="nofollow">کلمات کلیدی</a> و یک لینک به عکس: <a href="/ابزارهای-سئو/"><img src="img.jpg" alt="بهترین ابزارهای سئو" /></a></p>
<p>لینک خارجی: <a href="https://google.com">گوگل</a></p>
';

$extracted = $scanner->extract_links_from_html($html, 'https://mysite.com/مقاله-جاری');
assert(count($extracted) === 3, "Expected 3 internal links extracted, got " . count($extracted));
assert($extracted[0]['anchor_text'] === 'راهنمای جامع سئو');
assert($extracted[1]['anchor_text'] === 'کلمات کلیدی');
assert($extracted[1]['is_nofollow'] === true);
assert(strpos($extracted[2]['anchor_text'], 'بهترین ابزارهای سئو') !== false);
echo " -> Passed!\n\n";

// Test 3: Simulation of Posts and Cluster Analysis
echo "Test 3: Pillar-Cluster & Orphan Detection...\n";

// Mock posts:
// Post 1: Pillar candidate (Comprehensive SEO guide) - links to Post 2, Post 3
// Post 2: Cluster post (Keyword Research) - links back to Post 1
// Post 3: Cluster post (Link Building) - links back to Post 1
// Post 4: Orphan post (Content Marketing) - has no inlinks from anyone!
// Post 5: Dead-end post (Technical SEO) - receives inlinks from Post 1, but links to nothing!

$mock_posts = array(
    array(
        'id'                   => 1,
        'title'                => 'راهنمای جامع سئو (پیلار اصلی)',
        'url'                  => 'https://mysite.com/seo-guide/',
        'normalized_url'       => $scanner->normalize_url('https://mysite.com/seo-guide/'),
        'word_count'           => 3500,
        'categories'           => array('سئو و بهینه‌سازی'),
        'publish_date'         => '2026-01-01',
        'outlinks'             => array(
            array('raw_url' => 'https://mysite.com/keyword-research/', 'normalized_url' => $scanner->normalize_url('https://mysite.com/keyword-research/'), 'anchor_text' => 'تحقیق کلمات کلیدی', 'is_nofollow' => false),
            array('raw_url' => 'https://mysite.com/link-building/', 'normalized_url' => $scanner->normalize_url('https://mysite.com/link-building/'), 'anchor_text' => 'لینک‌سازی کلاستر', 'is_nofollow' => false),
            array('raw_url' => 'https://mysite.com/tech-seo/', 'normalized_url' => $scanner->normalize_url('https://mysite.com/tech-seo/'), 'anchor_text' => 'سئو تکنیکال', 'is_nofollow' => false),
        ),
        'total_outlinks_count' => 3
    ),
    array(
        'id'                   => 2,
        'title'                => 'آموزش تحقیق کلمات کلیدی',
        'url'                  => 'https://mysite.com/keyword-research/',
        'normalized_url'       => $scanner->normalize_url('https://mysite.com/keyword-research/'),
        'word_count'           => 1200,
        'categories'           => array('سئو و بهینه‌سازی'),
        'publish_date'         => '2026-01-05',
        'outlinks'             => array(
            array('raw_url' => 'https://mysite.com/seo-guide/', 'normalized_url' => $scanner->normalize_url('https://mysite.com/seo-guide/'), 'anchor_text' => 'آموزش سئو', 'is_nofollow' => false),
        ),
        'total_outlinks_count' => 1
    ),
    array(
        'id'                   => 3,
        'title'                => 'راهنمای لینک‌سازی داخلی و خارجی',
        'url'                  => 'https://mysite.com/link-building/',
        'normalized_url'       => $scanner->normalize_url('https://mysite.com/link-building/'),
        'word_count'           => 1500,
        'categories'           => array('سئو و بهینه‌سازی'),
        'publish_date'         => '2026-01-10',
        'outlinks'             => array(
            array('raw_url' => 'https://mysite.com/seo-guide/', 'normalized_url' => $scanner->normalize_url('https://mysite.com/seo-guide/'), 'anchor_text' => 'مقاله پیلار سئو', 'is_nofollow' => false),
        ),
        'total_outlinks_count' => 1
    ),
    array(
        'id'                   => 4,
        'title'                => 'اصول بازاریابی محتوایی (مقاله جدید)',
        'url'                  => 'https://mysite.com/content-marketing/',
        'normalized_url'       => $scanner->normalize_url('https://mysite.com/content-marketing/'),
        'word_count'           => 800,
        'categories'           => array('تولید محتوا'),
        'publish_date'         => '2026-02-01',
        'outlinks'             => array(),
        'total_outlinks_count' => 0
    ),
    array(
        'id'                   => 5,
        'title'                => 'چک‌لیست سئو تکنیکال',
        'url'                  => 'https://mysite.com/tech-seo/',
        'normalized_url'       => $scanner->normalize_url('https://mysite.com/tech-seo/'),
        'word_count'           => 900,
        'categories'           => array('سئو و بهینه‌سازی'),
        'publish_date'         => '2026-02-15',
        'outlinks'             => array(), // Dead end!
        'total_outlinks_count' => 0
    ),
);

$analyzer = new EX_SEO_Cluster_Analyzer();
$analysis = $analyzer->analyze($mock_posts);

$posts_res = array();
foreach ($analysis['posts'] as $p) {
    $posts_res[$p['id']] = $p;
}

// Assert Post 1 has 2 incoming links
assert($posts_res[1]['inlinks_count'] === 2, "Post 1 should have 2 inlinks, got " . $posts_res[1]['inlinks_count']);
// Assert Post 4 is Orphan (0 inlinks)
assert($posts_res[4]['role'] === 'orphan', "Post 4 should be classified as orphan, got " . $posts_res[4]['role']);
assert($analysis['summary']['orphans_count'] === 1, "Expected 1 orphan, got " . $analysis['summary']['orphans_count']);

// Assert Post 5 is Dead End (0 outlinks but has inlinks)
assert($posts_res[5]['role'] === 'dead_end', "Post 5 should be classified as dead_end, got " . $posts_res[5]['role']);

// Assert Category analysis
assert(isset($analysis['categories']['سئو و بهینه‌سازی']));
$seo_cat = $analysis['categories']['سئو و بهینه‌سازی'];
assert($seo_cat['recommended_pillar']['id'] === 1, "Recommended pillar for SEO category should be Post 1");

echo " -> Analysis Summary:\n";
echo "    Total posts: " . $analysis['summary']['total_posts'] . "\n";
echo "    Total internal links: " . $analysis['summary']['total_internal_links'] . "\n";
echo "    Orphans: " . $analysis['summary']['orphans_count'] . "\n";
echo "    Dead ends: " . $analysis['summary']['dead_ends_count'] . "\n";
echo "    Pillar recommendations confirmed!\n";
echo " -> Passed!\n\n";

// Test 4: CSV Export with UTF-8 BOM
echo "Test 4: CSV Export & UTF-8 BOM Verification...\n";
$exporter = new EX_SEO_Cluster_Exporter();
$csv_summary = $exporter->generate_posts_summary_csv($analysis['posts']);
$csv_matrix  = $exporter->generate_links_matrix_csv($analysis['matrix']);

// Verify UTF-8 BOM (\xEF\xBB\xBF)
assert(substr($csv_summary, 0, 3) === "\xEF\xBB\xBF", "Summary CSV must start with UTF-8 BOM");
assert(substr($csv_matrix, 0, 3) === "\xEF\xBB\xBF", "Matrix CSV must start with UTF-8 BOM");
assert(strpos($csv_summary, 'راهنمای جامع سئو') !== false, "Persian title should be in CSV");
assert(strpos($csv_matrix, 'تحقیق کلمات کلیدی') !== false, "Anchor text should be in Matrix CSV");
echo " -> Passed!\n\n";

// Test 5: Standalone Interactive Tree HTML
echo "Test 5: Standalone Interactive Tree HTML Verification...\n";
$tree_html = $exporter->generate_tree_html($analysis);
assert(!empty($tree_html), "Tree HTML should not be empty");
assert(strpos($tree_html, '<!DOCTYPE html>') !== false, "Must be valid HTML5 document");
assert(strpos($tree_html, 'نقشه درختی پیلار-کلاستر') !== false, "Must contain Persian title");
assert(strpos($tree_html, 'سئو و بهینه‌سازی') !== false, "Must contain category name");
assert(strpos($tree_html, 'اصول بازاریابی محتوایی') !== false, "Must contain orphan post name");
assert(strpos($tree_html, 'nodeDrawer') !== false, "Must contain interactive drawer");
echo " -> Passed!\n\n";

echo "=== All Tests Passed Successfully! ===\n";
