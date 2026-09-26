=== Zoro SEO ===
Contributors: earwsh
Donate link: https://github.com/earwsh
Tags: seo, internal links, pillar cluster, topic clusters, content planning
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Zoro SEO extracts internal links, analyzes Pillar-Cluster architectures, and identifies orphan articles to guide your monthly content strategy.

== Description ==

**Zoro SEO** is a powerful WordPress SEO tool designed to scan contents across posts, pages, and products, discover internal links and anchor texts, map topic clusters, and highlight orphaned pages to empower your monthly content production and internal link planning.

### Key Highlights

* **Automatic Internal Link Extractor:** Scans published posts, pages, WooCommerce products, and custom post types.
* **Elementor Support:** Deep JSON parsing of `_elementor_data` to extract links from buttons, text widgets, headings, and icon lists.
* **In-Editor Meta Box (Gutenberg & Classic):** Live feedback in post edit screen showing cluster role, inlink/outlink counts, orphan warning, and internal link suggestions.
* **Real-Time Save_Post Sync:** Automatically updates the internal linking matrix upon post publication and editing without crawling the full site.
* **Pillar & Topic Cluster Mapping:** Automatically detects strong pillar hubs and cluster articles.
* **Orphan Page Discovery:** Pinpoints articles with 0 incoming internal links so you can rescue them in your content calendar.
* **Dead-End Identification:** Highlights articles with 0 outgoing internal links.
* **Export Ready:** Download full reports in Excel/CSV (with UTF-8 BOM encoding for perfect Persian/Unicode support) and interactive standalone HTML trees.
* **Non-Blocking Performance:** Built with AJAX batch processing and optimized data storage to handle large websites smoothly.

== Installation ==

1. Upload the `zoro-seo` folder to the `/wp-content/plugins/` directory, or install the `.zip` file directly via **Plugins > Add New > Upload Plugin** in your WordPress dashboard.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Access the plugin dashboard via the **Zoro SEO** menu item in your WordPress admin sidebar.
4. Select your desired content types (Posts, Pages, Products) and click **Start Website Scan**.

== Frequently Asked Questions ==

= Does Zoro SEO slow down my website? =
No. Zoro SEO runs on-demand in the admin dashboard using background AJAX batching and real-time lightweight incremental updates. It does not load any front-end scripts or impact page loading speed for your visitors.

= Does it support Elementor and page builders? =
Yes! Version 0.1.0 includes dedicated recursive parsing for Elementor JSON content, extracting links from text editors, buttons, headings, and icon lists.

= Does it support non-English / Persian characters? =
Yes. All internal URLs, anchor texts, and CSV exports fully support Unicode (UTF-8) with zero encoding issues in Microsoft Excel.

= Can I scan custom post types, pages, or products? =
Yes! You can configure any public post type (including Pages and WooCommerce products) directly in the Zoro SEO dashboard.

== Changelog ==

= 0.1.0 =
* Added support for Pages, WooCommerce Products, and Custom Post Types.
* Added Elementor page builder JSON parsing for internal links.
* Added In-Editor Meta Box for Gutenberg and Classic Editor with live status and orphan warning banner.
* Added real-time incremental synchronization on `save_post` and post deletion.
* Optimized database storage separating matrix data from main dashboard payload.
* Added Post Type filters and badges in dashboard table.

= 0.0.1 =
* Initial public release.
* Internal link extraction and anchor text parsing.
* Topic cluster and pillar identification engine.
* Orphan article and dead-end detection.
* Excel CSV (UTF-8 BOM) and Interactive Tree HTML export.
