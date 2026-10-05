/**
 * Zoro SEO - Exporter
 * Generates CSV summaries with UTF-8 BOM (for Excel support) and interactive HTML tree dashboards.
 */

class ZoroExporter {
  static UTF8_BOM = '\uFEFF';

  /**
   * Generate CSV for Posts Summary
   * @param {Array<Object>} posts
   * @returns {string} CSV with UTF-8 BOM
   */
  generatePostsSummaryCsv(posts) {
    const headers = [
      'شناسه',
      'عنوان مطلب',
      'نوع محتوا',
      'آدرس (URL)',
      'دسته‌بندی‌ها',
      'نقش در کلاستر',
      'تعداد لینک ورودی (Inlinks)',
      'تعداد لینک خروجی (Outlinks)',
      'مقالات ارجاع‌دهنده مجزا',
      'تعداد کلمات',
      'برترین انکر تکست‌های دریافتی',
      'تاریخ انتشار',
    ];

    const rows = [headers.map(this._escapeCsvCell).join(',')];

    for (const p of posts || []) {
      const catsStr = Array.isArray(p.categories) ? p.categories.join(' | ') : 'عمومی';
      const postTypeStr = p.post_type_label || p.post_type || 'نوشته';

      let topAnchors = '-';
      if (p.anchor_distribution && typeof p.anchor_distribution === 'object') {
        const anchorsArr = Object.entries(p.anchor_distribution)
          .slice(0, 5)
          .map(([text, count]) => `${text} (${count})`);
        if (anchorsArr.length > 0) {
          topAnchors = anchorsArr.join(' - ');
        }
      }

      const row = [
        p.id ?? '',
        p.title ?? '',
        postTypeStr,
        p.url ?? '',
        catsStr,
        p.role_label || p.role || '',
        p.inlinks_count ?? 0,
        p.total_outlinks_count ?? 0,
        p.unique_referring_count ?? 0,
        p.word_count ?? 0,
        topAnchors,
        p.publish_date ?? '',
      ];

      rows.push(row.map(this._escapeCsvCell).join(','));
    }

    return ZoroExporter.UTF8_BOM + rows.join('\r\n');
  }

  /**
   * Generate CSV for Full Links Matrix
   * @param {Array<Object>} matrix
   * @returns {string} CSV with UTF-8 BOM
   */
  generateLinksMatrixCsv(matrix) {
    const headers = [
      'شناسه مبدأ',
      'عنوان مبدأ',
      'آدرس مبدأ',
      'شناسه مقصد',
      'عنوان مقصد',
      'آدرس مقصد',
      'متن پیوند (Anchor Text)',
      'وضعیت پیوند (Follow / Nofollow)',
    ];

    const rows = [headers.map(this._escapeCsvCell).join(',')];

    for (const item of matrix || []) {
      const followStatus = item.is_nofollow ? 'Nofollow' : 'Follow';
      const row = [
        item.source_id ?? '',
        item.source_title ?? '',
        item.source_url ?? '',
        item.target_id || 'لینک خارج از کلاستر',
        item.target_title || '—',
        item.target_url ?? '',
        item.anchor_text ?? '',
        followStatus,
      ];

      rows.push(row.map(this._escapeCsvCell).join(','));
    }

    return ZoroExporter.UTF8_BOM + rows.join('\r\n');
  }

  /**
   * Generate Standalone Interactive HTML Tree Dashboard
   * @param {Object} report Full analysis report from ZoroAnalyzer
   * @returns {string} Standalone HTML5 document
   */
  generateTreeHtml(report) {
    const summary = report.summary || {};
    const categories = report.categories || {};
    const posts = report.posts || [];

    const jsonReport = JSON.stringify(report).replace(/</g, '\\u003c');

    return `<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Zoro SEO | نقشه تعاملی پیلار-کلاستر و شبکه لینک‌های داخلی</title>
  <style>
    :root {
      --primary: #2563eb;
      --bg: #0f172a;
      --card-bg: #1e293b;
      --border: #334155;
      --text: #f8fafc;
      --text-muted: #94a3b8;
      --pillar: #f59e0b;
      --cluster: #38bdf8;
      --orphan: #ef4444;
      --deadend: #64748b;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Tahoma, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.6;
      padding: 24px;
    }
    .header {
      background: var(--card-bg);
      border: 1px solid var(--border);
      padding: 20px 24px;
      border-radius: 12px;
      margin-bottom: 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }
    .title h1 { font-size: 20px; font-weight: 800; color: #60a5fa; }
    .title p { font-size: 13px; color: var(--text-muted); margin-top: 4px; }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 24px;
    }
    .stat-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      padding: 16px 20px;
      border-radius: 10px;
    }
    .stat-card h3 { font-size: 26px; font-weight: 800; margin-bottom: 4px; }
    .stat-card p { font-size: 12px; color: var(--text-muted); }
    .controls {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .search-input {
      background: var(--card-bg);
      border: 1px solid var(--border);
      color: var(--text);
      padding: 10px 16px;
      border-radius: 8px;
      flex: 1;
      min-width: 260px;
      font-size: 14px;
    }
    .clusters-container {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    .category-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
    }
    .cat-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid var(--border);
      padding-bottom: 12px;
      margin-bottom: 16px;
    }
    .cat-title { font-size: 16px; font-weight: 700; color: #93c5fd; }
    .cat-meta { font-size: 12px; color: var(--text-muted); }
    .posts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 12px;
    }
    .post-node {
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid var(--border);
      padding: 12px 14px;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .post-node:hover {
      border-color: #60a5fa;
      transform: translateY(-2px);
    }
    .post-title { font-size: 13.5px; font-weight: 600; margin-bottom: 8px; }
    .badges-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .badge {
      display: inline-block;
      padding: 2px 8px;
      border-radius: 6px;
      font-size: 11px;
      font-weight: 600;
    }
    .badge-pillar { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.4); }
    .badge-cluster { background: rgba(56, 189, 248, 0.2); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.4); }
    .badge-orphan { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); }
    .badge-deadend { background: rgba(100, 116, 139, 0.2); color: #cbd5e1; border: 1px solid rgba(100, 116, 139, 0.4); }
    .badge-type { background: rgba(255, 255, 255, 0.1); color: #e2e8f0; }
    
    /* Modal / Drawer */
    .drawer-overlay {
      position: fixed; inset: 0; background: rgba(0, 0, 0, 0.7);
      backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 1000;
    }
    .drawer-content {
      background: var(--card-bg); border: 1px solid var(--border); border-radius: 14px;
      width: 90%; max-width: 700px; max-height: 85vh; overflow-y: auto; padding: 24px;
    }
    .drawer-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
    .drawer-close { background: none; border: none; color: var(--text-muted); font-size: 24px; cursor: pointer; }
    .link-list { list-style: none; margin-top: 12px; }
    .link-list li { background: rgba(15, 23, 42, 0.5); padding: 10px 14px; border-radius: 6px; margin-bottom: 8px; border: 1px solid var(--border); font-size: 13px; }
  </style>
</head>
<body>
  <div class="header">
    <div class="title">
      <h1>🗡️ Zoro SEO — نقشه تعاملی پیلار-کلاستر و لینک‌های داخلی</h1>
      <p>تحلیل ساختار موضوعی، پیلارها و کشف صفحات یتیم سایت | تاریخ اسکن: ${summary.scan_time || 'اکنون'}</p>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <h3>${summary.total_posts || 0}</h3>
      <p>کل مطالب بررسی‌شده</p>
    </div>
    <div class="stat-card">
      <h3 style="color: #60a5fa;">${summary.total_internal_links || 0}</h3>
      <p>کل پیوندهای داخلی</p>
    </div>
    <div class="stat-card">
      <h3 style="color: #ef4444;">${summary.orphans_count || 0}</h3>
      <p>صفحات یتیم (بدون ورودی)</p>
    </div>
    <div class="stat-card">
      <h3 style="color: #f59e0b;">${summary.pillars_count || 0}</h3>
      <p>ستون‌های محتوا (Pillars)</p>
    </div>
    <div class="stat-card">
      <h3>${summary.avg_links_per_post || 0}</h3>
      <p>میانگین لینک در هر مطلب</p>
    </div>
  </div>

  <div class="controls">
    <input type="text" id="searchInput" class="search-input" placeholder="جستجوی عنوان مطلب یا پیوند..." />
  </div>

  <div class="clusters-container" id="clustersContainer">
    ${Object.values(categories)
      .map(
        cat => `
      <div class="category-card" data-category="${cat.name}">
        <div class="cat-header">
          <div class="cat-title">📁 خوشه موضوعی: ${cat.name}</div>
          <div class="cat-meta">${cat.posts_count} مطلب | ${cat.total_inlinks} لینک داخلی ${
          cat.orphans_count > 0 ? `<span style="color:#ef4444;">(${cat.orphans_count} یتیم)</span>` : ''
        }</div>
        </div>
        ${
          cat.recommended_pillar
            ? `<div style="font-size: 13px; color: #fbbf24; margin-bottom: 14px;">⭐ پیلار پیشنهادی: <strong>${cat.recommended_pillar.title}</strong> (${cat.recommended_pillar.inlinks_count} ورودی)</div>`
            : ''
        }
        <div class="posts-grid">
          ${cat.posts
            .map(
              p => `
            <div class="post-node" data-id="${p.id}" data-title="${p.title}">
              <div class="post-title">${p.title}</div>
              <div class="badges-row">
                <span class="badge ${this._getRoleClass(p.role)}">${this._getRoleLabel(p.role)}</span>
                <span class="badge badge-type">${p.post_type_label || p.post_type || 'نوشته'}</span>
                <span style="font-size: 11px; color: var(--text-muted); margin-right: auto;">⬇ ${p.inlinks_count} ورودی | ⬆ ${p.outlinks_count} خروجی</span>
              </div>
            </div>
          `
            )
            .join('')}
        </div>
      </div>
    `
      )
      .join('')}
  </div>

  <div class="drawer-overlay" id="drawer">
    <div class="drawer-content">
      <div class="drawer-header">
        <h2 id="drawerTitle">جزئیات مطلب</h2>
        <button class="drawer-close" id="drawerClose">&times;</button>
      </div>
      <div id="drawerBody"></div>
    </div>
  </div>

  <script>
    const reportData = ${jsonReport};
    const postsMap = new Map((reportData.posts || []).map(p => [p.id, p]));

    const searchInput = document.getElementById('searchInput');
    const nodes = document.querySelectorAll('.post-node');

    searchInput.addEventListener('input', (e) => {
      const q = e.target.value.trim().toLowerCase();
      nodes.forEach(node => {
        const title = (node.getAttribute('data-title') || '').toLowerCase();
        node.style.display = (!q || title.includes(q)) ? 'block' : 'none';
      });
    });

    const drawer = document.getElementById('drawer');
    const drawerTitle = document.getElementById('drawerTitle');
    const drawerBody = document.getElementById('drawerBody');
    const drawerClose = document.getElementById('drawerClose');

    nodes.forEach(node => {
      node.addEventListener('click', () => {
        const id = Number(node.getAttribute('data-id'));
        const post = postsMap.get(id);
        if (!post) return;

        drawerTitle.textContent = post.title;
        let inlinksHtml = (post.inlinks && post.inlinks.length > 0)
          ? post.inlinks.map(inl => '<li><strong>مبدأ:</strong> ' + inl.source_title + ' <br><span style="color:#94a3b8; font-size:12px;">انکر تکست: «' + inl.anchor_text + '»</span></li>').join('')
          : '<li style="color:#ef4444;">هیچ صفحه‌ای از سایت به این مطلب لینک نداده است (صفحه یتیم).</li>';

        let outlinksHtml = (post.outlinks && post.outlinks.length > 0)
          ? post.outlinks.map(out => '<li><strong>مقصد:</strong> ' + out.raw_url + ' <br><span style="color:#94a3b8; font-size:12px;">انکر: «' + out.anchor_text + '»</span></li>').join('')
          : '<li style="color:#64748b;">این مطلب به هیچ مطلب داخلی دیگری لینک نداده است (بن‌بست).</li>';

        drawerBody.innerHTML = \`
          <p style="font-size:13px; color:#94a3b8; margin-bottom: 16px;">آدرس: <a href="\${post.url}" target="_blank" style="color:#60a5fa;">\${post.url}</a></p>
          <h4 style="margin: 12px 0 6px 0; color:#38bdf8;">پیوندهای ورودی (\${post.inlinks_count || 0}):</h4>
          <ul class="link-list">\${inlinksHtml}</ul>
          <h4 style="margin: 18px 0 6px 0; color:#38bdf8;">پیوندهای خروجی (\${post.total_outlinks_count || 0}):</h4>
          <ul class="link-list">\${outlinksHtml}</ul>
        \`;
        drawer.style.display = 'flex';
      });
    });

    drawerClose.addEventListener('click', () => drawer.style.display = 'none');
    drawer.addEventListener('click', (e) => { if (e.target === drawer) drawer.style.display = 'none'; });
  </script>
</body>
</html>`;
  }

  _escapeCsvCell(val) {
    if (val === null || val === undefined) return '""';
    const str = String(val).replace(/"/g, '""');
    return `"${str}"`;
  }

  _getRoleClass(role) {
    const map = {
      pillar: 'badge-pillar',
      cluster: 'badge-cluster',
      orphan: 'badge-orphan',
      dead_end: 'badge-deadend',
    };
    return map[role] || 'badge-cluster';
  }

  _getRoleLabel(role) {
    const map = {
      pillar: 'ستون محتوا (Pillar)',
      cluster: 'خوشه (Cluster)',
      orphan: 'مقاله یتیم',
      dead_end: 'بن‌بست',
    };
    return map[role] || 'خوشه';
  }
}

module.exports = { ZoroExporter };
