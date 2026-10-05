# Zoro SEO 🗡️

> WordPress Plugin & Universal JavaScript/TypeScript Library for Internal Link Extraction, Topic Clusters & Pillar Content Analysis.
> افزونه پیشرفته وردپرس و کتابخانه جاوااسکریپت/تایپ‌اسکریپت برای تحلیل لینک‌های داخلی، ساختار پیلار-کلاستر و صفحات یتیم در وردپرس و Next.js.

[![Version](https://img.shields.io/badge/version-0.1.0-blue.svg)](https://github.com/earwsh/zoro-SEO)
[![Next.js](https://img.shields.io/badge/Next.js-13%2B%20%7C%2014%20%7C%2015-black.svg)](https://nextjs.org)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-Ready-3178c6.svg)](https://www.typescriptlang.org)
[![Author](https://img.shields.io/badge/author-earwsh-green.svg)](https://github.com/earwsh)

[English](#english) | [فارسی](#فارسی)

---

<a name="english"></a>
## English

**Zoro SEO** is a dual-purpose SEO architecture tool:
1. **WordPress Plugin:** Seamless admin dashboard, in-editor Gutenberg meta box, Elementor deep parsing, and automatic real-time sync.
2. **Universal JavaScript/TypeScript Library (`packages/zoro-seo`):** Zero-dependency NPM package designed for **Next.js (App & Pages Router)**, Node.js, Jamstack, and custom headless CMS architectures.

---

### ⚡ Quick Start for Next.js & Custom Websites

Install or import the core library from `packages/zoro-seo`:

```ts
import { ZoroSeo } from './packages/zoro-seo/src/index.js';
// or: import { ZoroSeo } from 'zoro-seo';

const zoro = new ZoroSeo({ siteUrl: 'https://mysite.com' });

// Pass your posts from Prisma, database, or Markdown files
const report = zoro.analyze([
  {
    id: 1,
    title: 'SEO Services Guide',
    url: 'https://mysite.com/services/seo',
    content: '<p>Read our <a href="/blog/keyword-research">Keyword Research</a> guide.</p>',
    categories: ['Services'],
    postType: 'service'
  },
  {
    id: 2,
    title: 'Keyword Research',
    url: 'https://mysite.com/blog/keyword-research',
    content: '<p>Learn keyword research fundamentals.</p>',
    categories: ['Blog'],
    postType: 'post'
  }
]);

console.log(report.summary.orphans_count); // 0
console.log(report.summary.pillars_count); // detected content hubs
const treeHtml = zoro.exportTreeHtml(report); // Standalone interactive HTML dashboard
```

See [examples/nextjs-example](examples/nextjs-example) for complete Next.js App Router Route Handler (`app/api/seo/route.ts`) and build-time audit scripts.

---

### 🗡️ WordPress Plugin Features

1. **Intelligent Internal Link Scanner & Page Builder Support:**
   - Automatically crawls and parses contents across **Posts, Pages, WooCommerce Products, and Custom Post Types (CPTs)**.
   - **Elementor Support:** Recursively extracts links and anchors from `_elementor_data` JSON widgets (Text Editor, Buttons, Headings, Icon Lists).
   - Extracts anchor texts, image `alt` texts, and link directives (`dofollow` / `nofollow`).

2. **In-Editor Meta Box & Real-Time Sync:**
   - **Gutenberg & Classic Editor Meta Box:** Displays real-time cluster role, incoming/outgoing link counts, and cluster link suggestions directly while editing.
   - **Orphan Alert Banner:** Instant high-priority warning in the post editor if an article has 0 incoming links.
   - **Automated `save_post` Sync:** Incrementally scans published and updated posts without requiring a full site crawl.

3. **Multi-Format Exports:**
   - **Excel / CSV Summary:** Includes UTF-8 BOM encoding for flawless rendering of non-English / Persian characters in Microsoft Excel.
   - **Full Internal Link Matrix:** Source post, target post, anchor text, and follow status.
   - **Standalone Interactive HTML Tree:** A self-contained, offline-compatible hierarchical tree visualization with folding branches, search, and detailed drawer inspection.

#### WordPress Installation
1. Download [`zoro-seo.zip`](https://github.com/earwsh/zoro-SEO/raw/main/zoro-seo.zip).
2. Go to **Plugins > Add New > Upload Plugin** in WordPress admin.
3. Upload `zoro-seo.zip` and click **Activate**.

---

<a name="فارسی"></a>
## فارسی

**Zoro SEO** یک ابزار تخصصی و دوگانه برای سئوی داخلی، مهندسی پیلار-کلاستر و کشف صفحات یتیم است:
1. **افزونه وردپرس:** پیشخوان اختصاصی، متاباکس درون ویرایشگر گوتنبرگ، پشتیبانی کامل از المنتور، برگه‌ها و محصولات.
2. **کتابخانه جاوااسکریپت / تایپ‌اسکریپت (`packages/zoro-seo`):** پکیج مستقل و مدرن با پشتیبانی از **Next.js**، نود جی‌اس، ری‌اکت و سایت‌های کدنویسی اختصاصی بدون نیاز به PHP.

---

### ⚡ راهنمای سریع استفاده در Next.js و سایت‌های اختصاصی

کتابخانه مستقل را در پروژه Next.js خود فراخوانی کنید:

```ts
import { ZoroSeo } from 'zoro-seo'; // یا از فولدر packages/zoro-seo

const zoro = new ZoroSeo({ siteUrl: 'https://mysite.com' });

// ارسال مقالات از دیتابیس (Prisma / Drizzle) یا فایل‌های Markdown:
const report = zoro.analyze(posts);

// نتایج فوری:
console.log('تعداد صفحات یتیم:', report.summary.orphans_count);
console.log('ستون‌های محتوا (Pillars):', report.summary.pillars_count);

// خروجی نقشه تعاملی HTML مستقل:
const htmlDashboard = zoro.exportTreeHtml(report);
```

نمونه کامل پیاده‌سازی مسیر API در Next.js App Router در پوشه [examples/nextjs-example](examples/nextjs-example) قرار دارد.

---

### ویژگی‌های کلیدی در وردپرس (نسخه 0.1.0)

1. **استخراج هوشمند لینک‌های داخلی و پشتیبانی از صفحه‌سازها:**
   - اسکن کامل محتوا در **نوشته‌ها، برگه‌ها، محصولات ووکامرس و انواع پست‌های سفارشی (CPT)**.
   - **پشتیبانی کامل از المنتور (Elementor):** استخراج عمیق لینک‌ها و انکر تکست‌ها از متادیتاهای JSON المنتور (`_elementor_data`).
2. **متاباکس اختصاصی در صفحه ویرایش نوشته و همگام‌سازی زنده:**
   - **متاباکس هوشمند در ویرایشگر (گوتنبرگ و کلاسیک):** نمایش زنده وضعیت مقاله و هشدار یتیم بودن حین نگارش.
   - **همگام‌سازی آنی پس از ذخیره (`save_post`):** بروزرسانی خودکار بدون نیاز به اسکن مجدد کل سایت.
3. **فرمت‌های برون‌بری دیتا (Export):**
   - **خروجی اکسل مقالات (CSV با UTF-8 BOM):** بدون به‌هم‌ریختگی حروف فارسی در اکسل.
   - **خروجی ماتریس کامل لینک‌ها:** گزارش جزئیات هر پیوند (مبدأ، مقصد، انکر تکست و نوع لینک).
   - **نقشه درختی تعاملی (HTML Tree):** فایل HTML مستقل با قابلیت جستجوی زنده و باز و بسته کردن شاخه‌ها.

---

### License
GPLv2 or later.
Developed by [earwsh](https://github.com/earwsh).
