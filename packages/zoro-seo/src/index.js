/**
 * Zoro SEO - Core Package
 * Framework-agnostic internal link analyzer and topic cluster engine.
 * Ideal for Next.js, Node.js, Jamstack, Headless CMS, and custom web applications.
 */

const { ZoroScanner } = require('./scanner');
const { ZoroAnalyzer } = require('./analyzer');
const { ZoroExporter } = require('./exporter');

class ZoroSeo {
  /**
   * @param {Object} [options={}]
   * @param {string} [options.siteUrl=''] Website base URL (e.g. 'https://mysite.com')
   * @param {number} [options.pillarThreshold=4] Minimum inlinks to qualify as a Pillar
   * @param {number} [options.topPercentRatio=0.15] Top percentile for dynamic pillar threshold
   */
  constructor(options = {}) {
    this.siteUrl = options.siteUrl || '';
    this.scanner = new ZoroScanner(this.siteUrl);
    this.analyzer = new ZoroAnalyzer({
      pillarThreshold: options.pillarThreshold,
      topPercentRatio: options.topPercentRatio,
    });
    this.exporter = new ZoroExporter();
  }

  /**
   * Scan a single post (extract links, anchors, word count)
   * @param {Object} post Raw post with { id, title, url, content, categories, publishDate, postType }
   * @returns {Object} Scanned post data
   */
  scan(post) {
    return this.scanner.scanPost(post);
  }

  /**
   * Scan an array of raw posts and run full cluster analysis
   * @param {Array<Object>} posts Array of raw post objects
   * @returns {Object} Complete report { summary, posts, categories, matrix }
   */
  analyze(posts = []) {
    if (!Array.isArray(posts)) {
      throw new TypeError('Posts parameter must be an array.');
    }

    // If posts are not yet scanned (have 'content' property and no 'outlinks'), scan them
    const scannedPosts = posts.map(p => {
      if (p.outlinks !== undefined && p.normalized_url !== undefined) {
        return p; // Already scanned
      }
      return this.scanner.scanPost(p);
    });

    return this.analyzer.analyze(scannedPosts);
  }

  /**
   * Incrementally update or insert a single post and return updated report
   * @param {Array<Object>} existingPosts Current scanned posts list
   * @param {Object} updatedPost Raw or scanned post being updated
   * @returns {Object} Recomputed analysis report
   */
  updatePost(existingPosts, updatedPost) {
    const scanned = (updatedPost.outlinks !== undefined && updatedPost.normalized_url !== undefined)
      ? updatedPost
      : this.scanner.scanPost(updatedPost);

    return this.analyzer.updateSinglePost(existingPosts, scanned);
  }

  /**
   * Incrementally remove a deleted post and return updated report
   * @param {Array<Object>} existingPosts Current scanned posts list
   * @param {number|string} postId ID of post to remove
   * @returns {Object} Recomputed analysis report
   */
  removePost(existingPosts, postId) {
    return this.analyzer.removeSinglePost(existingPosts, postId);
  }

  /**
   * Export posts summary CSV with UTF-8 BOM
   * @param {Array<Object>} posts Analyzed posts array
   * @returns {string} CSV string
   */
  exportPostsCsv(posts) {
    return this.exporter.generatePostsSummaryCsv(posts);
  }

  /**
   * Export full link matrix CSV with UTF-8 BOM
   * @param {Array<Object>} matrix Analyzed link matrix array
   * @returns {string} CSV string
   */
  exportMatrixCsv(matrix) {
    return this.exporter.generateLinksMatrixCsv(matrix);
  }

  /**
   * Export standalone interactive HTML tree map
   * @param {Object} report Full report from analyze()
   * @returns {string} HTML document string
   */
  exportTreeHtml(report) {
    return this.exporter.generateTreeHtml(report);
  }
}

module.exports = {
  ZoroSeo,
  ZoroScanner,
  ZoroAnalyzer,
  ZoroExporter,
};
