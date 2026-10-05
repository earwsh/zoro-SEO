/**
 * Zoro SEO - Analyzer
 * Computes internal PageRank metrics, detects Pillars, Clusters, Orphans, and Dead-Ends.
 * Groups by topic categories and recommends optimal pillar content.
 */

class ZoroAnalyzer {
  /**
   * @param {Object} [options={}]
   * @param {number} [options.pillarThreshold=4] Minimum inlinks to qualify as a Pillar
   * @param {number} [options.topPercentRatio=0.15] Top percentile for dynamic pillar threshold
   */
  constructor(options = {}) {
    this.defaultPillarThreshold = options.pillarThreshold ?? 4;
    this.topPercentRatio = options.topPercentRatio ?? 0.15;
  }

  /**
   * Analyze scanned posts and build complete cluster report
   * @param {Array<Object>} posts Scanned posts
   * @returns {Object} Full analysis result { summary, posts, categories, matrix }
   */
  analyze(posts) {
    if (!Array.isArray(posts) || posts.length === 0) {
      return {
        summary: this._getEmptySummary(),
        posts: [],
        categories: {},
        matrix: [],
      };
    }

    // 1. Build URL to ID index and Post ID map
    const urlToId = new Map();
    const postsById = new Map();

    for (const p of posts) {
      const id = p.id;
      const normUrl = p.normalized_url;
      urlToId.set(normUrl, id);

      postsById.set(id, {
        ...p,
        inlinks: [],
        inlinks_count: 0,
        unique_referring_count: 0,
        anchor_distribution: {},
      });
    }

    // 2. Build Inverted Index (Map Outlinks to Target Inlinks)
    const linkMatrix = [];
    let totalInternalLinks = 0;

    for (const [sourceId, sourcePost] of postsById.entries()) {
      const sourceTitle = sourcePost.title;
      const sourceUrl = sourcePost.url;
      const outlinks = sourcePost.outlinks || [];

      for (const link of outlinks) {
        const targetUrl = link.normalized_url;
        const anchorText = link.anchor_text;
        const isNofollow = link.is_nofollow;

        const targetId = urlToId.get(targetUrl) ?? 0;
        const targetPost = targetId ? postsById.get(targetId) : null;
        const targetTitle = targetPost ? targetPost.title : '';

        totalInternalLinks++;

        // Record in link matrix
        linkMatrix.push({
          source_id: sourceId,
          source_title: sourceTitle,
          source_url: sourceUrl,
          target_id: targetId,
          target_title: targetTitle,
          target_url: targetPost ? targetPost.url : link.raw_url,
          anchor_text: anchorText,
          is_nofollow: isNofollow,
        });

        // If target is one of our scanned articles, record inlink
        if (targetPost) {
          targetPost.inlinks.push({
            source_id: sourceId,
            source_title: sourceTitle,
            source_url: sourceUrl,
            anchor_text: anchorText,
            is_nofollow: isNofollow,
          });

          // Track anchor text count
          if (!targetPost.anchor_distribution[anchorText]) {
            targetPost.anchor_distribution[anchorText] = 0;
          }
          targetPost.anchor_distribution[anchorText]++;
        }
      }
    }

    // 3. Calculate metrics and classify role for each post
    const inlinksCounts = [];

    for (const p of postsById.values()) {
      const inCount = p.inlinks.length;
      p.inlinks_count = inCount;

      // Count unique referring posts
      const uniqueReferrers = new Set(p.inlinks.map(inl => inl.source_id));
      p.unique_referring_count = uniqueReferrers.size;

      // Sort anchor text frequency descending
      p.anchor_distribution = Object.fromEntries(
        Object.entries(p.anchor_distribution).sort(([, a], [, b]) => b - a)
      );

      inlinksCounts.push(inCount);
    }

    // Dynamic threshold for pillar candidates
    inlinksCounts.sort((a, b) => b - a);
    const totalPostsCount = postsById.size;
    let pillarThreshold = this.defaultPillarThreshold;

    if (totalPostsCount > 5) {
      const topIndex = Math.ceil(totalPostsCount * this.topPercentRatio) - 1;
      if (inlinksCounts[topIndex] && inlinksCounts[topIndex] >= 3) {
        pillarThreshold = inlinksCounts[topIndex];
      }
    }

    let orphansCount = 0;
    let deadEndsCount = 0;
    let pillarsCount = 0;
    let clustersCount = 0;
    const postTypesCounts = {};

    for (const p of postsById.values()) {
      const inCount = p.inlinks_count;
      const outCount = p.total_outlinks_count ?? p.outlinks?.length ?? 0;
      const postType = p.post_type ?? 'post';

      p.post_type = postType;
      p.post_type_label = this._getPostTypeLabel(postType);

      postTypesCounts[postType] = (postTypesCounts[postType] || 0) + 1;

      let role = 'cluster';
      let roleLabel = 'خوشه (Cluster)';
      let roleClass = 'badge-cluster';

      if (inCount === 0) {
        role = 'orphan';
        roleLabel = 'مقاله یتیم (بدون ورودی)';
        roleClass = 'badge-orphan';
        orphansCount++;
      } else if (inCount >= pillarThreshold && outCount > 0) {
        role = 'pillar';
        roleLabel = 'ستون محتوا (Pillar)';
        roleClass = 'badge-pillar';
        pillarsCount++;
      } else if (outCount === 0) {
        role = 'dead_end';
        roleLabel = 'بن‌بست (بدون خروجی)';
        roleClass = 'badge-deadend';
        deadEndsCount++;
      } else {
        clustersCount++;
      }

      p.role = role;
      p.role_label = roleLabel;
      p.role_class = roleClass;
    }

    // 4. Cluster Grouping by Category
    const categoriesData = {};

    for (const p of postsById.values()) {
      const cats = Array.isArray(p.categories) && p.categories.length > 0 ? p.categories : ['عمومی'];

      for (const cat of cats) {
        if (!categoriesData[cat]) {
          categoriesData[cat] = {
            name: cat,
            posts_count: 0,
            total_inlinks: 0,
            total_outlinks: 0,
            orphans_count: 0,
            posts: [],
            recommended_pillar: null,
          };
        }

        const catItem = categoriesData[cat];
        catItem.posts_count++;
        catItem.total_inlinks += p.inlinks_count;
        catItem.total_outlinks += p.total_outlinks_count;

        if (p.role === 'orphan') {
          catItem.orphans_count++;
        }

        catItem.posts.push({
          id: p.id,
          title: p.title,
          url: p.url,
          post_type: p.post_type,
          post_type_label: p.post_type_label,
          inlinks_count: p.inlinks_count,
          outlinks_count: p.total_outlinks_count,
          word_count: p.word_count,
          role: p.role,
        });
      }
    }

    // Recommend best Pillar for each category
    for (const catInfo of Object.values(categoriesData)) {
      catInfo.posts.sort((a, b) => {
        if (a.inlinks_count === b.inlinks_count) {
          return (b.word_count || 0) - (a.word_count || 0);
        }
        return b.inlinks_count - a.inlinks_count;
      });

      if (catInfo.posts.length > 0) {
        catInfo.recommended_pillar = catInfo.posts[0];
      }
    }

    // 5. Overall Site Summary
    const avgInlinks = totalPostsCount > 0 ? Number((totalInternalLinks / totalPostsCount).toFixed(1)) : 0;

    const summary = {
      total_posts: totalPostsCount,
      total_internal_links: totalInternalLinks,
      avg_links_per_post: avgInlinks,
      orphans_count: orphansCount,
      dead_ends_count: deadEndsCount,
      pillars_count: pillarsCount,
      clusters_count: clustersCount,
      categories_count: Object.keys(categoriesData).length,
      post_types: postTypesCounts,
      scan_time: new Date().toISOString(),
    };

    return {
      summary,
      posts: Array.from(postsById.values()),
      categories: categoriesData,
      matrix: linkMatrix,
    };
  }

  /**
   * Incrementally update or insert a single post and return recomputed report
   * @param {Array<Object>} existingPosts
   * @param {Object} updatedPost
   * @returns {Object}
   */
  updateSinglePost(existingPosts, updatedPost) {
    const list = Array.isArray(existingPosts) ? [...existingPosts] : [];
    const idx = list.findIndex(p => p.id === updatedPost.id);

    if (idx >= 0) {
      list[idx] = updatedPost;
    } else {
      list.push(updatedPost);
    }

    return this.analyze(list);
  }

  /**
   * Incrementally remove a single post and return recomputed report
   * @param {Array<Object>} existingPosts
   * @param {number|string} postId
   * @returns {Object}
   */
  removeSinglePost(existingPosts, postId) {
    if (!Array.isArray(existingPosts)) return this.analyze([]);
    const filtered = existingPosts.filter(p => p.id !== postId);
    return this.analyze(filtered);
  }

  _getPostTypeLabel(postType) {
    const map = {
      post: 'نوشته',
      page: 'برگه',
      product: 'محصول',
      service: 'خدمت / سرویس',
      portfolio: 'نمونه‌کار',
    };
    return map[postType] || postType;
  }

  _getEmptySummary() {
    return {
      total_posts: 0,
      total_internal_links: 0,
      avg_links_per_post: 0,
      orphans_count: 0,
      dead_ends_count: 0,
      pillars_count: 0,
      clusters_count: 0,
      categories_count: 0,
      post_types: {},
      scan_time: new Date().toISOString(),
    };
  }
}

module.exports = { ZoroAnalyzer };
