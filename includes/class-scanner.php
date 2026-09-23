<?php
/**
 * Scanner class for EX SEO Cluster
 * Scans posts, extracts content links, and normalizes URLs.
 */

if (!defined('ABSPATH')) {
    // If accessed outside WordPress (e.g. CLI testing)
}

class EX_SEO_Cluster_Scanner {

    /**
     * Site home URL and host
     */
    private $home_url;
    private $home_host;
    private $site_url_map = array();

    public function __construct($home_url = '') {
        if (!empty($home_url)) {
            $this->home_url = untrailingslashit($home_url);
        } elseif (function_exists('home_url')) {
            $this->home_url = untrailingslashit(home_url());
        } else {
            $this->home_url = '';
        }

        $this->home_host = !empty($this->home_url) ? parse_url($this->home_url, PHP_URL_HOST) : '';
    }

    /**
     * Set pre-populated map of URL => Post ID
     */
    public function set_url_map($map) {
        $this->site_url_map = $map;
    }

    /**
     * Normalize URL for comparison
     * Strips query params, fragments, and trailing slashes.
     */
    public function normalize_url($url) {
        $url = trim($url);
        if (empty($url)) {
            return '';
        }

        // Handle protocol-relative URLs
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        // Handle relative URLs
        if (strpos($url, '/') === 0 && !empty($this->home_url)) {
            $url = $this->home_url . $url;
        }

        // Parse components
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['host'])) {
            return $url;
        }

        $scheme = isset($parsed['scheme']) ? $parsed['scheme'] : 'https';
        $host = strtolower($parsed['host']);
        $path = isset($parsed['path']) ? rtrim($parsed['path'], '/') : '';

        // Decode URL-encoded Persian characters in path so URLs match uniformly
        $path = urldecode($path);

        return $scheme . '://' . $host . $path;
    }

    /**
     * Check if a URL is internal to the website
     */
    public function is_internal_url($url) {
        $url = trim($url);
        if (empty($url) || $url === '#' || strpos($url, 'javascript:') === 0 || strpos($url, 'mailto:') === 0 || strpos($url, 'tel:') === 0) {
            return false;
        }

        // Relative paths starting with /
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            return true;
        }

        $parsed = parse_url($url);
        if (!isset($parsed['host'])) {
            return false;
        }

        $link_host = strtolower($parsed['host']);
        $home_host = strtolower($this->home_host);

        // Normalize www vs non-www
        $link_host_clean = preg_replace('/^www\./', '', $link_host);
        $home_host_clean = preg_replace('/^www\./', '', $home_host);

        return ($link_host_clean === $home_host_clean);
    }

    /**
     * Scan post content and extract all internal links with their anchor text
     *
     * @param int|object $post WordPress post or object with ID, post_title, post_content
     * @return array Extracted post data and internal links
     */
    public function scan_post($post) {
        if (is_numeric($post) && function_exists('get_post')) {
            $post = get_post($post);
        }

        if (!$post || empty($post->post_content)) {
            $content = $post ? $post->post_content : '';
        } else {
            $content = $post->post_content;
        }

        $post_id = isset($post->ID) ? $post->ID : 0;
        $title = isset($post->post_title) ? $post->post_title : '';
        $permalink = function_exists('get_permalink') && $post_id ? get_permalink($post_id) : (isset($post->guid) ? $post->guid : '');
        $normalized_permalink = $this->normalize_url($permalink);

        $word_count = $this->calculate_word_count($content);
        $categories = $this->get_post_categories($post_id);

        $outlinks = array();

        if (!empty($content)) {
            $outlinks = $this->extract_links_from_html($content, $normalized_permalink);
        }

        return array(
            'id'                   => $post_id,
            'title'                => $title,
            'url'                  => $permalink,
            'normalized_url'       => $normalized_permalink,
            'word_count'           => $word_count,
            'categories'           => $categories,
            'publish_date'         => isset($post->post_date) ? $post->post_date : '',
            'outlinks'             => $outlinks,
            'total_outlinks_count' => count($outlinks),
        );
    }

    /**
     * Extract links from HTML content
     */
    public function extract_links_from_html($html, $current_page_url = '') {
        $links = array();

        if (empty($html)) {
            return $links;
        }

        // Use DOMDocument with UTF-8 handling
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        // Prepend utf-8 hack for proper Persian character decoding in DOMDocument
        $html_encoded = '<?xml encoding="utf-8" ?>' . $html;
        $loaded = @$dom->loadHTML($html_encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        if (!$loaded) {
            // Regex fallback if HTML is severely malformed
            return $this->extract_links_regex_fallback($html, $current_page_url);
        }

        $anchors = $dom->getElementsByTagName('a');

        foreach ($anchors as $a) {
            $href = $a->getAttribute('href');
            if (empty($href)) {
                continue;
            }

            if (!$this->is_internal_url($href)) {
                continue;
            }

            $normalized_target = $this->normalize_url($href);

            // Skip self-referencing links
            if (!empty($current_page_url) && $normalized_target === $current_page_url) {
                continue;
            }

            // Extract anchor text (clean up whitespace)
            $anchor_text = trim($a->textContent);

            // If empty text (e.g. image link), check for <img> alt
            if (empty($anchor_text)) {
                $images = $a->getElementsByTagName('img');
                if ($images->length > 0) {
                    $alt = $images->item(0)->getAttribute('alt');
                    $anchor_text = !empty($alt) ? '[تصویر: ' . trim($alt) . ']' : '[لینک تصویر]';
                } else {
                    $anchor_text = '[بدون متن / آیکون]';
                }
            }

            $rel = $a->getAttribute('rel');
            $is_nofollow = (strpos(strtolower($rel), 'nofollow') !== false);

            $links[] = array(
                'raw_url'        => $href,
                'normalized_url' => $normalized_target,
                'anchor_text'    => $anchor_text,
                'is_nofollow'    => $is_nofollow,
                'rel'            => $rel,
            );
        }

        libxml_clear_errors();
        return $links;
    }

    /**
     * Fallback link extractor using regex
     */
    private function extract_links_regex_fallback($html, $current_page_url = '') {
        $links = array();
        if (preg_match_all('/<a\s+[^>]*href=([\'"])(.*?)\1[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $href = $match[2];
                if (!$this->is_internal_url($href)) {
                    continue;
                }

                $normalized_target = $this->normalize_url($href);
                if (!empty($current_page_url) && $normalized_target === $current_page_url) {
                    continue;
                }

                $raw_text = strip_tags($match[3]);
                $anchor_text = trim(preg_replace('/\s+/', ' ', $raw_text));
                if (empty($anchor_text)) {
                    $anchor_text = '[لینک بدون متن]';
                }

                $is_nofollow = (preg_match('/rel=([\'"])[^\'"]*nofollow[^\'"]*\1/i', $match[0]) === 1);

                $links[] = array(
                    'raw_url'        => $href,
                    'normalized_url' => $normalized_target,
                    'anchor_text'    => $anchor_text,
                    'is_nofollow'    => $is_nofollow,
                    'rel'            => '',
                );
            }
        }
        return $links;
    }

    /**
     * Count words in Persian and English content
     */
    private function calculate_word_count($content) {
        $clean_text = strip_tags($content);
        if (function_exists('strip_shortcodes')) {
            $clean_text = strip_shortcodes($clean_text);
        }
        $clean_text = trim(preg_replace('/\s+/u', ' ', $clean_text));
        if (empty($clean_text)) {
            return 0;
        }

        $words = preg_split('/[\s\p{P}]+/u', $clean_text, -1, PREG_SPLIT_NO_EMPTY);
        return is_array($words) ? count($words) : 0;
    }

    /**
     * Get post category names
     */
    private function get_post_categories($post_id) {
        if (!$post_id || !function_exists('get_the_category')) {
            return array();
        }

        $cats = get_the_category($post_id);
        if (empty($cats) || is_wp_error($cats)) {
            return array('دسته‌بندی‌نشده');
        }

        $names = array();
        foreach ($cats as $cat) {
            $names[] = $cat->name;
        }
        return $names;
    }
}
