/**
 * Zoro SEO - JavaScript Core Test Suite
 */

const assert = require('assert');
const { ZoroSeo, ZoroScanner, ZoroAnalyzer, ZoroExporter } = require('../src/index');

console.log('=== Running Zoro SEO JS Core Unit Tests ===\n');

const siteUrl = 'https://mysite.com';
const scanner = new ZoroScanner(siteUrl);

// Test 1: URL Normalization & Internal Check
console.log('Test 1: URL Normalization & Internal Check...');
assert.strictEqual(scanner.isInternalUrl('https://mysite.com/blog/seo-guide/'), true);
assert.strictEqual(scanner.isInternalUrl('https://www.mysite.com/blog/seo-guide/'), true);
assert.strictEqual(scanner.isInternalUrl('/blog/seo-guide/'), true);
assert.strictEqual(scanner.isInternalUrl('https://google.com'), false);
assert.strictEqual(scanner.isInternalUrl('#'), false);
assert.strictEqual(scanner.isInternalUrl('mailto:info@mysite.com'), false);

const norm1 = scanner.normalizeUrl('https://mysite.com/آموزش-سئو/');
const norm2 = scanner.normalizeUrl('/آموزش-سئو/');
assert.strictEqual(norm1, norm2, 'Normalized Persian URLs must match');
assert.strictEqual(norm1, 'https://mysite.com/آموزش-سئو');
console.log(' -> Passed!\n');

// Test 2: Link Extraction from HTML and Markdown
console.log('Test 2: Link Extraction (HTML & Markdown)...');
const sampleContent = `
<p>برای شروع سئو به <a href="https://mysite.com/آموزش-سئو/">راهنمای جامع سئو</a> سر بزنید.</p>
<p>همچنین مقاله <a href="/تحقیق-کلمات-کلیدی/" rel="nofollow">کلمات کلیدی</a> و عکس: <a href="/ابزارهای-سئو/"><img src="tool.jpg" alt="ابزارهای برتر" /></a></p>
<p>لینک خارجی: <a href="https://google.com">گوگل</a></p>
و لینک به سبک مارک‌داون: [سئو تکنیکال](/tech-seo/) در این پاراگراف.
`;

const extracted = scanner.extractLinks(sampleContent, 'https://mysite.com/مقاله-جاری');
assert.strictEqual(extracted.length, 4, `Expected 4 links, got ${extracted.length}`);
assert.strictEqual(extracted[0].anchor_text, 'راهنمای جامع سئو');
assert.strictEqual(extracted[1].anchor_text, 'کلمات کلیدی');
assert.strictEqual(extracted[1].is_nofollow, true);
assert.ok(extracted[2].anchor_text.includes('ابزارهای برتر'));
assert.strictEqual(extracted[3].anchor_text, 'سئو تکنیکال');
console.log(' -> Passed!\n');

// Test 3: Topic Cluster, Pillars & Orphan Detection
console.log('Test 3: Pillar-Cluster & Orphan Detection...');
const mockPosts = [
  {
    id: 1,
    title: 'راهنمای جامع سئو (پیلار اصلی)',
    url: 'https://mysite.com/seo-guide',
    content: `
      لینک به <a href="/keyword-research">تحقیق کلمات کلیدی</a>
      و <a href="/link-building">لینک‌سازی</a>
      و <a href="/tech-seo">سئو تکنیکال</a>
    `,
    categories: ['سئو و بهینه‌سازی'],
    postType: 'post',
  },
  {
    id: 2,
    title: 'آموزش تحقیق کلمات کلیدی',
    url: 'https://mysite.com/keyword-research',
    content: 'بازگشت به <a href="/seo-guide">آموزش سئو</a>',
    categories: ['سئو و بهینه‌سازی'],
    postType: 'post',
  },
  {
    id: 3,
    title: 'راهنمای لینک‌سازی داخلی و خارجی',
    url: 'https://mysite.com/link-building',
    content: 'بازگشت به <a href="/seo-guide">پیلار سئو</a>',
    categories: ['سئو و بهینه‌سازی'],
    postType: 'post',
  },
  {
    id: 4,
    title: 'اصول بازاریابی محتوایی (مقاله یتیم)',
    url: 'https://mysite.com/content-marketing',
    content: 'محتوای مقاله بدون هیچ لینک خروجی و ورودی',
    categories: ['تولید محتوا'],
    postType: 'post',
  },
  {
    id: 5,
    title: 'چک‌لیست سئو تکنیکال (بن‌بست)',
    url: 'https://mysite.com/tech-seo',
    content: 'صفحه بدون هیچ لینک خروجی',
    categories: ['سئو و بهینه‌سازی'],
    postType: 'post',
  },
];

const zoro = new ZoroSeo({ siteUrl });
const report = zoro.analyze(mockPosts);

const postsMap = new Map(report.posts.map(p => [p.id, p]));

// Post 1 has 2 inlinks
assert.strictEqual(postsMap.get(1).inlinks_count, 2, 'Post 1 should have 2 inlinks');

// Post 4 is Orphan (0 inlinks)
assert.strictEqual(postsMap.get(4).role, 'orphan', 'Post 4 should be orphan');
assert.strictEqual(report.summary.orphans_count, 1, 'Should find 1 orphan');

// Post 5 is Dead End (0 outlinks, has inlinks)
assert.strictEqual(postsMap.get(5).role, 'dead_end', 'Post 5 should be dead_end');

// Pillar recommendation for category
const seoCat = report.categories['سئو و بهینه‌سازی'];
assert.ok(seoCat, 'Category must exist');
assert.strictEqual(seoCat.recommended_pillar.id, 1, 'Recommended pillar should be Post 1');

console.log(' -> Summary Metrics:');
console.log(`    Total posts: ${report.summary.total_posts}`);
console.log(`    Total links: ${report.summary.total_internal_links}`);
console.log(`    Orphans: ${report.summary.orphans_count}`);
console.log(`    Pillars: ${report.summary.pillars_count}`);
console.log(' -> Passed!\n');

// Test 4: Incremental Update (Next.js save/edit action)
console.log('Test 4: Incremental Update (e.g. Next.js Server Action / API)...');
const newServicePage = {
  id: 6,
  title: 'خدمات سئو اختصاصی (صفحه خدمات)',
  url: 'https://mysite.com/services/seo',
  content: 'پیوند به <a href="/content-marketing">اصول بازاریابی محتوا</a> جهت پشتیبانی.',
  categories: ['خدمات'],
  postType: 'service',
};

// Now Post 6 links to Post 4 (so Post 4 is no longer an orphan!)
const updatedReport = zoro.updatePost(report.posts, newServicePage);
const updatedMap = new Map(updatedReport.posts.map(p => [p.id, p]));

assert.strictEqual(updatedReport.summary.total_posts, 6, 'Total posts should be 6');
assert.strictEqual(updatedMap.get(4).inlinks_count, 1, 'Post 4 should now have 1 inlink');
assert.notStrictEqual(updatedMap.get(4).role, 'orphan', 'Post 4 is no longer an orphan');
assert.strictEqual(updatedMap.get(6).post_type, 'service', 'Post 6 post type should be service');
console.log(' -> Passed!\n');

// Test 5: CSV & HTML Tree Exports
console.log('Test 5: UTF-8 BOM CSV & HTML Tree Exports...');
const csvSummary = zoro.exportPostsCsv(updatedReport.posts);
const csvMatrix = zoro.exportMatrixCsv(updatedReport.matrix);
const htmlTree = zoro.exportTreeHtml(updatedReport);

assert.strictEqual(csvSummary.startsWith('\uFEFF'), true, 'Summary CSV must contain UTF-8 BOM');
assert.strictEqual(csvMatrix.startsWith('\uFEFF'), true, 'Matrix CSV must contain UTF-8 BOM');
assert.ok(csvSummary.includes('خدمات سئو اختصاصی'), 'Persian title must exist in CSV');
assert.ok(htmlTree.includes('<!DOCTYPE html>'), 'HTML tree must be valid HTML5');
assert.ok(htmlTree.includes('Zoro SEO'), 'HTML tree must contain title');
console.log(' -> Passed!\n');

console.log('=== All Zoro SEO JS Core Tests Passed Successfully! ===\n');
