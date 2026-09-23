=== Zoro SEO ===
Contributors: earwsh
Donate link: https://github.com/earwsh
Tags: seo, internal links, pillar cluster, topic clusters, content planning
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Zoro SEO extracts internal links, analyzes Pillar-Cluster architectures, and identifies orphan articles to guide your monthly content strategy.

== Description ==

**Zoro SEO** is a powerful WordPress SEO tool designed to scan article contents, discover internal links and anchor texts, map topic clusters, and highlight orphaned pages to empower your monthly content production and internal link planning.

### Key Highlights

* **Automatic Internal Link Extractor:** Scans all published posts and parses content links, anchors, image alt attributes, and dofollow/nofollow directives.
* **Pillar & Topic Cluster Mapping:** Automatically detects strong pillar hubs and cluster articles.
* **Orphan Page Discovery:** Pinpoints articles with 0 incoming internal links so you can rescue them in your content calendar.
* **Dead-End Identification:** Highlights articles with 0 outgoing internal links.
* **Export Ready:** Download full reports in Excel/CSV (with UTF-8 BOM encoding for perfect Persian/Unicode support) and interactive standalone HTML trees.
* **Non-Blocking Performance:** Built with AJAX batch processing to handle sites with hundreds or thousands of articles smoothly.

== Installation ==

1. Upload the `zoro-seo` folder to the `/wp-content/plugins/` directory, or install the `.zip` file directly via **Plugins > Add New > Upload Plugin** in your WordPress dashboard.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Access the plugin dashboard via the **Zoro SEO** menu item in your WordPress admin sidebar.
4. Click **Start Website Scan** to crawl and analyze your posts.

== Frequently Asked Questions ==

= Does Zoro SEO slow down my website? =
No. Zoro SEO runs on-demand in the admin dashboard using background AJAX batching. It does not load any front-end scripts or impact page loading speed for your visitors.

= Does it support non-English / Persian characters? =
Yes. All internal URLs, anchor texts, and CSV exports fully support Unicode (UTF-8) with zero encoding issues in Microsoft Excel.

= Can I scan custom post types or pages? =
Yes, you can scan standard blog posts, pages, and public custom post types.

== Screenshots ==

1. Dashboard overview displaying KPI metrics, orphan articles count, and pillar candidates.
2. Filterable table of posts with real-time search by category and cluster role.
3. Modal drawer displaying incoming links, outgoing links, and anchor text distribution.
4. Interactive standalone HTML tree diagram.

== Changelog ==

= 0.0.1 =
* Initial public release.
* Internal link extraction and anchor text parsing.
* Topic cluster and pillar identification engine.
* Orphan article and dead-end detection.
* Excel CSV (UTF-8 BOM) and Interactive Tree HTML export.
