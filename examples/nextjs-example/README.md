# راهنمای استفاده از Zoro SEO در Next.js ⚡

این راهنما نحوه استفاده از کتابخانه **Zoro SEO** در پروژه‌های **Next.js** (نسخه‌های 13، 14 و 15 با App Router یا Pages Router) را توضیح می‌دهد.

---

## ۱. نصب پکیج

در پروژه Next.js خود می‌توانید پکیج را به صورت محلی یا از مخزن نصب کنید:

```bash
# نصب محلی از فولدر packages
npm install ../path-to/packages/zoro-seo

# یا پس از انتشار روی NPM:
# npm install zoro-seo
```

---

## ۲. استفاده در Next.js App Router (مسیر API)

یک فایل جدید در مسیر `app/api/seo/route.ts` ایجاد کنید:

```ts
import { NextResponse } from 'next/server';
import { ZoroSeo } from 'zoro-seo';
// ایمپورت کلاینت دیتابیس شما (مثلاً Prisma, Drizzle یا Sanity)
// import { prisma } from '@/lib/prisma';

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const format = searchParams.get('format') || 'json';

  // دریافت مقالات سایت شما
  // const posts = await prisma.post.findMany({ select: { id: true, title: true, slug: true, content: true } });
  const posts = [
    {
      id: 1,
      title: 'راهنمای جامع سئو',
      url: 'https://mysite.com/blog/seo-guide',
      content: '<p>مطالعه <a href="/services/seo">خدمات سئو</a> برای اطلاعات بیشتر.</p>',
      categories: ['سئو'],
      postType: 'post'
    },
    // ...سایر مقالات
  ];

  const zoro = new ZoroSeo({
    siteUrl: 'https://mysite.com',
    pillarThreshold: 3 // حداقل لینک برای شناخته شدن به عنوان پیلار
  });

  const report = zoro.analyze(posts);

  // خروجی نقشه درختی HTML مستقل
  if (format === 'tree') {
    return new NextResponse(zoro.exportTreeHtml(report), {
      headers: { 'Content-Type': 'text/html; charset=utf-8' }
    });
  }

  // خروجی فایل اکسل CSV مقالات
  if (format === 'csv') {
    return new NextResponse(zoro.exportPostsCsv(report.posts), {
      headers: {
        'Content-Type': 'text/csv; charset=utf-8',
        'Content-Disposition': 'attachment; filename="seo-summary.csv"'
      }
    });
  }

  // خروجی JSON برای رندر در فرانت‌اند ری‌اکت
  return NextResponse.json(report);
}
```

حالا در مرورگر آدرس‌های زیر در دسترس است:
* `http://localhost:3000/api/seo` -> دریافت ساختار JSON
* `http://localhost:3000/api/seo?format=tree` -> مشاهده داشبورد تعاملی نقشه درختی
* `http://localhost:3000/api/seo?format=csv` -> دانلود اکسل مقالات با حروف فارسی صحیح

---

## ۳. اسکریپت بیلد خودکار (Build-Time Audit)

اگر مقالات شما به صورت فایل‌های **Markdown / MDX** ذخیره می‌شوند، می‌توانید در زمان اجرای `npm run build` گزارش سلامت لینک‌ها را بسازید:

فایل `scripts/seo-audit.mjs` را ایجاد کنید و در `package.json` اضافه کنید:

```json
{
  "scripts": {
    "seo:audit": "node scripts/seo-audit.mjs",
    "build": "npm run seo:audit && next build"
  }
}
```

این اسکریپت خروجی گرافیکی را در `public/seo-audit.html` ذخیره می‌کند که پس از دیپلوی روی Vercel مستقیماً در آدرس `yoursite.com/seo-audit.html` قابل مشاهده خواهد بود!
