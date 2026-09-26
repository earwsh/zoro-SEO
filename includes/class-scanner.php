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
        $post_type = isset($post->post_type) ? $post->post_type : 'post';

        // Check for Elementor content in post meta
        if ($post_id && function_exists('get_post_meta')) {
            $elementor_data = get_post_meta($post_id, '_elementor_data', true);
            if (!empty($elementor_data)) {
                $elementor_html = $this->extract_content_from_elementor($elementor_data);
                if (!empty($elementor_html)) {
                    $content .= "\n" . $elementor_html;
                }
            }
        }

        // Expand safe shortcodes if present
        if (!empty($content) && function_exists('do_shortcode') && strpos($content, '[') !== false) {
            $content = do_shortcode($content);
        }

        $word_count = $this->calculate_word_count($content);
        $categories = $this->get_post_categories($post_id, $post_type);

        $outlinks = array();

        if (!empty($content)) {
            $outlinks = $this->extract_links_from_html($content, $normalized_permalink);
        }

        return array(
            'id'                   => $post_id,
            'title'                => $title,
            'url'                  => $permalink,
            'normalized_url'       => $normalized_permalink,
            'post_type'            => $post_type,
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
     * Get post category or taxonomy names based on post type
     */
    public function get_post_categories($post_id, $post_type = 'post') {
        if (!$post_id) {
            return array('عمومی');
        }

        if ($post_type === 'page') {
            return array('برگه‌های سایت');
        }

        // Determine relevant taxonomies for this post type
        $taxonomies = array();
        if ($post_type === 'post') {
            $taxonomies = array('category');
        } elseif ($post_type === 'product') {
            $taxonomies = array('product_cat');
        } else {
            // For custom post types, dynamically discover hierarchical public taxonomies
            if (function_exists('get_object_taxonomies')) {
                $all_tax = get_object_taxonomies($post_type, 'objects');
                if (is_array($all_tax)) {
                    foreach ($all_tax as $tax_name => $tax_obj) {
                        if (!empty($tax_obj->hierarchical) && !empty($tax_obj->public)) {
                            $taxonomies[] = $tax_name;
                        }
                    }
                }
            }
            if (empty($taxonomies)) {
                $taxonomies = array('category');
            }
        }

        $names = array();
        if (function_exists('get_the_terms')) {
            foreach ($taxonomies as $taxonomy) {
                $terms = get_the_terms($post_id, $taxonomy);
                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $t) {
                        $names[] = $t->name;
                    }
                }
            }
        }

        // Fallback for standard posts if get_the_terms returned nothing
        if (empty($names) && function_exists('get_the_category') && $post_type === 'post') {
            $cats = get_the_category($post_id);
            if ($cats && !is_wp_error($cats)) {
                foreach ($cats as $c) {
                    $names[] = $c->name;
                }
            }
        }

        if (empty($names)) {
            return array($post_type === 'post' ? 'دسته‌بندی‌نشده' : ($post_type === 'product' ? 'محصولات' : ucfirst($post_type)));
        }

        return array_values(array_unique($names));
    }

    /**
     * Extract HTML and links from Elementor builder JSON data
     */
    public function extract_content_from_elementor($elementor_data) {
        if (is_string($elementor_data)) {
            $data = json_decode($elementor_data, true);
        } else {
            $data = $elementor_data;
        }

        if (!is_array($data)) {
            return '';
        }

        $html_pieces = array();
        $this->parse_elementor_elements($data, $html_pieces);
        return implode("\n", $html_pieces);
    }

    /**
     * Recursively traverse Elementor elements array to extract text and links
     */
    private function parse_elementor_elements($elements, &$html_pieces) {
        if (!is_array($elements)) {
            return;
        }

        foreach ($elements as $el) {
            if (isset($el['settings']) && is_array($el['settings'])) {
                $settings = $el['settings'];

                // 1. Text Editor widget content
                if (!empty($settings['editor']) && is_string($settings['editor'])) {
                    $html_pieces[] = $settings['editor'];
                }

                // 2. Heading, Button, Call to Action, Icon Box with URL settings
                if (!empty($settings['link']) && is_array($settings['link']) && !empty($settings['link']['url'])) {
                    $url = $settings['link']['url'];
                    $text = '';
                    if (!empty($settings['text']) && is_string($settings['text'])) {
                        $text = $settings['text'];
                    } elseif (!empty($settings['title']) && is_string($settings['title'])) {
                        $text = $settings['title'];
                    } elseif (!empty($settings['title_text']) && is_string($settings['title_text'])) {
                        $text = $settings['title_text'];
                    } else {
                        $text = '[پیوند المنتور]';
                    }
                    $rel = !empty($settings['link']['is_external']) ? 'rel="external' : 'rel="';
                    $rel .= !empty($settings['link']['nofollow']) ? ' nofollow"' : '"';
                    $html_pieces[] = sprintf('<p><a href="%s" %s>%s</a></p>', htmlspecialchars($url, ENT_QUOTES, 'UTF-8'), $rel, htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
                }

                // 3. Icon list widget items
                if (!empty($settings['icon_list']) && is_array($settings['icon_list'])) {
                    foreach ($settings['icon_list'] as $item) {
                        if (!empty($item['link']) && is_array($item['link']) && !empty($item['link']['url'])) {
                            $item_text = !empty($item['text']) ? $item['text'] : '[آیتم لیست المنتور]';
                            $rel = !empty($item['link']['nofollow']) ? 'rel="nofollow"' : '';
                            $html_pieces[] = sprintf('<p><a href="%s" %s>%s</a></p>', htmlspecialchars($item['link']['url'], ENT_QUOTES, 'UTF-8'), $rel, htmlspecialchars($item_text, ENT_QUOTES, 'UTF-8'));
                        }
                    }
                }
            }

            // Recurse into child elements (containers, sections, columns)
            if (!empty($el['elements']) && is_array($el['elements'])) {
                $this->parse_elementor_elements($el['elements'], $html_pieces);
            }
        }
    }
}
