/**
 * Next.js Build-Time SEO Audit Script
 * Run this during build: `node scripts/seo-audit.mjs`
 * Generates an interactive audit report in `public/seo-audit.html`
 */

import fs from 'node:fs';
import path from 'node:path';
import { ZoroSeo } from 'zoro-seo';

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://mysite.com';
const OUTPUT_FILE = path.join(process.cwd(), 'public', 'seo-audit.html');

console.log('🔍 Running Zoro SEO Content & Link Audit for Next.js...');

// Example: You can read markdown/mdx files from content directory or fetch via API
const sampleArticles = [
  {
    id: 'post-1',
    title: 'راهنمای جامع سئو و بهینه‌سازی سایت',
    url: `${SITE_URL}/blog/seo-guide`,
    content: `
      برای اطلاعات بیشتر به [خدمات سئو](/services/seo) سر بزنید.
      همچنین مطالعه [سئو تکنیکال](/blog/tech-seo) ضروری است.
    `,
    categories: ['سئو'],
    postType: 'post',
  },
  {
    id: 'service-1',
    title: 'خدمات سئو تخصصی برای کسب‌وکارها',
    url: `${SITE_URL}/services/seo`,
    content: 'بازگشت به [وبلاگ سئو](/blog/seo-guide)',
    categories: ['خدمات'],
    postType: 'service',
  },
  {
    id: 'post-2',
    title: 'چک‌لیست سئو تکنیکال',
    url: `${SITE_URL}/blog/tech-seo`,
    content: 'مطلب بدون لینک خروجی (بن‌بست).',
    categories: ['سئو'],
    postType: 'post',
  },
  {
    id: 'page-1',
    title: 'صفحه درباره ما (صفحه یتیم)',
    url: `${SITE_URL}/about-us`,
    content: 'صفحه‌ای که هیچ پیوندی به آن ارجاع نداده است.',
    categories: ['عمومی'],
    postType: 'page',
  },
];

const zoro = new ZoroSeo({ siteUrl: SITE_URL, pillarThreshold: 2 });
const report = zoro.analyze(sampleArticles);

console.log('--------------------------------------------------');
console.log(`📊 کل مطالب: ${report.summary.total_posts}`);
console.log(`🔗 پیوندهای داخلی: ${report.summary.total_internal_links}`);
console.log(`⚠️ صفحات یتیم (Orphans): ${report.summary.orphans_count}`);
console.log(`⭐ ستون‌های محتوا (Pillars): ${report.summary.pillars_count}`);
console.log('--------------------------------------------------');

// Write standalone HTML dashboard to Next.js public directory
const htmlTree = zoro.exportTreeHtml(report);

const publicDir = path.dirname(OUTPUT_FILE);
if (!fs.existsSync(publicDir)) {
  fs.mkdirSync(publicDir, { recursive: true });
}

fs.writeFileSync(OUTPUT_FILE, htmlTree, 'utf-8');
console.log(`✅ گزارش تعاملی در آدرس مقابل ایجاد شد: ${OUTPUT_FILE}`);
console.log(`🌐 در سرور Next.js در آدرس /seo-audit.html در دسترس است.\n`);
