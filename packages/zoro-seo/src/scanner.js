/**
 * Zoro SEO - Scanner
 * Framework-agnostic link extraction, anchor parsing, and URL normalization.
 * Supports HTML and Markdown content, Persian/Unicode URLs, and dofollow/nofollow directives.
 */

class ZoroScanner {
  /**
   * @param {string} [siteUrl=''] Base website URL (e.g. 'https://example.com')
   */
  constructor(siteUrl = '') {
    this.siteUrl = siteUrl ? this._cleanTrailingSlash(siteUrl) : '';
    this.siteHost = this._extractHost(this.siteUrl);
    this.urlMap = new Map();
  }

  /**
   * Set pre-populated map of URL => Post ID
   * @param {Map<string, number|string>|Record<string, number|string>} map
   */
  setUrlMap(map) {
    if (map instanceof Map) {
      this.urlMap = map;
    } else if (map && typeof map === 'object') {
      this.urlMap = new Map(Object.entries(map));
    }
  }

  /**
   * Normalize URL for uniform comparison
   * Strips query params, fragments, trailing slashes, and decodes Unicode/Persian characters.
   * @param {string} url
   * @returns {string}
   */
  normalizeUrl(url) {
    if (!url || typeof url !== 'string') return '';
    let cleaned = url.trim();
    if (!cleaned) return '';

    // Handle protocol-relative URLs
    if (cleaned.startsWith('//')) {
      cleaned = 'https:' + cleaned;
    }

    // Handle relative paths
    if (cleaned.startsWith('/') && this.siteUrl) {
      cleaned = this.siteUrl + cleaned;
    }

    try {
      const parsed = new URL(cleaned, this.siteUrl || 'https://localhost');
      const scheme = parsed.protocol || 'https:';
      const host = parsed.hostname.toLowerCase();
      let pathname = parsed.pathname || '';

      // Clean trailing slash
      if (pathname.length > 1 && pathname.endsWith('/')) {
        pathname = pathname.slice(0, -1);
      }

      // Decode URL-encoded Persian / Unicode characters
      try {
        pathname = decodeURIComponent(pathname);
      } catch {
        // Fallback if malformed URI component
      }

      return `${scheme}//${host}${pathname}`;
    } catch {
      // Fallback for simple relative strings
      cleaned = cleaned.split('#')[0].split('?')[0];
      if (cleaned.endsWith('/') && cleaned.length > 1) {
        cleaned = cleaned.slice(0, -1);
      }
      try {
        cleaned = decodeURIComponent(cleaned);
      } catch {}
      return cleaned;
    }
  }

  /**
   * Check if a URL belongs to the internal website
   * @param {string} url
   * @returns {boolean}
   */
  isInternalUrl(url) {
    if (!url || typeof url !== 'string') return false;
    const clean = url.trim();
    if (!clean || clean === '#' || clean.startsWith('javascript:') || clean.startsWith('mailto:') || clean.startsWith('tel:')) {
      return false;
    }

    // Relative paths starting with /
    if (clean.startsWith('/') && !clean.startsWith('//')) {
      return true;
    }

    try {
      const parsed = new URL(clean, this.siteUrl || 'https://localhost');
      const linkHost = parsed.hostname.toLowerCase().replace(/^www\./, '');
      const siteHost = this.siteHost.replace(/^www\./, '');

      if (!siteHost) {
        return false;
      }

      return linkHost === siteHost;
    } catch {
      return false;
    }
  }

  /**
   * Scan post data and extract all internal links with their anchor text
   * @param {Object} post
   * @param {number|string} post.id Post ID
   * @param {string} post.title Post title
   * @param {string} post.url Post canonical URL
   * @param {string} [post.content=''] HTML or Markdown content
   * @param {string[]} [post.categories=[]] Array of category names
   * @param {string} [post.publishDate=''] Publication date
   * @param {string} [post.postType='post'] Post type (e.g. post, page, service, product)
   * @returns {Object} Scanned post summary and outlinks
   */
  scanPost(post) {
    if (!post) {
      throw new Error('Post object is required for scanning.');
    }

    const postId = post.id ?? 0;
    const title = post.title ?? '';
    const rawUrl = post.url ?? '';
    const content = post.content ?? '';
    const categories = Array.isArray(post.categories) ? post.categories : (post.category ? [post.category] : ['عمومی']);
    const postType = post.postType ?? 'post';
    const publishDate = post.publishDate ?? '';

    const normalizedUrl = this.normalizeUrl(rawUrl);
    const wordCount = this.calculateWordCount(content);
    const outlinks = this.extractLinks(content, normalizedUrl);

    return {
      id: postId,
      title,
      url: rawUrl,
      normalized_url: normalizedUrl,
      post_type: postType,
      word_count: wordCount,
      categories,
      publish_date: publishDate,
      outlinks,
      total_outlinks_count: outlinks.length,
    };
  }

  /**
   * Extract links from HTML and Markdown content
   * @param {string} content HTML or Markdown text
   * @param {string} [currentPageUrl=''] Current page normalized URL to exclude self-links
   * @returns {Array<{raw_url: string, normalized_url: string, anchor_text: string, is_nofollow: boolean, rel: string}>}
   */
  extractLinks(content, currentPageUrl = '') {
    if (!content || typeof content !== 'string') {
      return [];
    }

    const links = [];

    // 1. Extract HTML <a> tags
    const htmlAnchorRegex = /<a\s+([^>]*?)href=(["'])(.*?)\2([^>]*?)>(.*?)<\/a>/gis;
    let match;

    while ((match = htmlAnchorRegex.exec(content)) !== null) {
      const beforeAttrs = match[1] || '';
      const href = match[3] || '';
      const afterAttrs = match[4] || '';
      const innerHtml = match[5] || '';
      const allAttrs = beforeAttrs + ' ' + afterAttrs;

      if (!this.isInternalUrl(href)) {
        continue;
      }

      const normalizedTarget = this.normalizeUrl(href);

      // Skip self-referencing links
      if (currentPageUrl && normalizedTarget === currentPageUrl) {
        continue;
      }

      // Extract anchor text or img alt
      let anchorText = this._cleanText(innerHtml);
      if (!anchorText) {
        const imgAltMatch = /<img\s+[^>]*?alt=(["'])(.*?)\1/is.exec(innerHtml);
        if (imgAltMatch && imgAltMatch[2]) {
          anchorText = `[تصویر: ${imgAltMatch[2].trim()}]`;
        } else {
          anchorText = '[لینک تصویر/آیکون]';
        }
      }

      const relMatch = /rel=(["'])(.*?)\1/i.exec(allAttrs);
      const rel = relMatch ? relMatch[2] : '';
      const isNofollow = /nofollow/i.test(rel);

      links.push({
        raw_url: href,
        normalized_url: normalizedTarget,
        anchor_text: anchorText,
        is_nofollow: isNofollow,
        rel: rel.trim(),
      });
    }

    // 2. Extract Markdown links: [anchor](url) - ignore images ![alt](url)
    const markdownLinkRegex = /(?<!\!)\[([^\]]+)\]\(([^)\s]+)(?:\s+["'][^"']*["'])?\)/g;
    let mdMatch;

    while ((mdMatch = markdownLinkRegex.exec(content)) !== null) {
      const anchorText = mdMatch[1] ? mdMatch[1].trim() : '';
      const href = mdMatch[2] ? mdMatch[2].trim() : '';

      if (!this.isInternalUrl(href)) {
        continue;
      }

      const normalizedTarget = this.normalizeUrl(href);
      if (currentPageUrl && normalizedTarget === currentPageUrl) {
        continue;
      }

      // Check if this exact href was already extracted by HTML parser
      const alreadyFound = links.some(l => l.raw_url === href && l.anchor_text === anchorText);
      if (alreadyFound) {
        continue;
      }

      links.push({
        raw_url: href,
        normalized_url: normalizedTarget,
        anchor_text: anchorText || '[لینک بدون متن]',
        is_nofollow: false,
        rel: '',
      });
    }

    return links;
  }

  /**
   * Word count for multilingual, Persian, and English text
   * @param {string} content
   * @returns {number}
   */
  calculateWordCount(content) {
    if (!content || typeof content !== 'string') return 0;

    // Strip HTML tags and markdown link targets
    let clean = content.replace(/<[^>]+>/g, ' ');
    clean = clean.replace(/\[([^\]]+)\]\([^)]+\)/g, '$1');
    clean = clean.replace(/[#*_~`>-]/g, ' ');
    clean = clean.trim();

    if (!clean) return 0;

    // Split on unicode whitespace and punctuation marks
    const words = clean.match(/[\p{L}\p{N}]+/gu);
    return words ? words.length : 0;
  }

  _cleanTrailingSlash(url) {
    return url.replace(/\/+$/, '');
  }

  _extractHost(url) {
    if (!url) return '';
    try {
      const parsed = new URL(url);
      return parsed.hostname.toLowerCase();
    } catch {
      return '';
    }
  }

  _cleanText(html) {
    return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
  }
}

module.exports = { ZoroScanner };
