/**
 * Example Next.js App Router API Route Handler
 * Place this file in: app/api/seo/route.ts
 */

import { NextResponse } from 'next/server';
import { ZoroSeo } from 'zoro-seo';

// Mock function: replace this with your actual database query (Prisma, Drizzle, MongoDB, Strapi, etc.)
async function fetchWebsitePosts() {
  return [
    {
      id: 1,
      title: 'راهنمای جامع خدمات سئو سایت',
      url: 'https://mysite.com/services/seo',
      content: `
        <p>برای دریافت اطلاعات بیشتر به <a href="/services/web-design">طراحی سایت</a> مراجعه کنید.</p>
        <p>همچنین مقاله <a href="/blog/keyword-research">تحقیق کلمات کلیدی</a> را بخوانید.</p>
      `,
      categories: ['خدمات'],
      postType: 'service',
    },
    {
      id: 2,
      title: 'آموزش تحقیق کلمات کلیدی',
      url: 'https://mysite.com/blog/keyword-research',
      content: '<p>برای خرید خدمات به صفحه <a href="/services/seo">سئو سایت</a> بازگردید.</p>',
      categories: ['وبلاگ'],
      postType: 'post',
    },
    {
      id: 3,
      title: 'طراحی سایت و لندینگ پیج اختصاصی',
      url: 'https://mysite.com/services/web-design',
      content: '<p>صفحه خدمات بدون لینک خروجی.</p>',
      categories: ['خدمات'],
      postType: 'service',
    },
    {
      id: 4,
      title: 'درباره آژانس ما (صفحه یتیم)',
      url: 'https://mysite.com/about-us',
      content: '<p>صفحه‌ای که هیچ پیوند ورودی از سایر صفحات سایت ندارد.</p>',
      categories: ['درباره ما'],
      postType: 'page',
    },
  ];
}

export async function GET(request: Request) {
  const { searchParams } = new URL(request.url);
  const type = searchParams.get('type') || 'json';

  // 1. Fetch your posts from database or markdown files
  const posts = await fetchWebsitePosts();

  // 2. Initialize Zoro SEO with your site base URL
  const zoro = new ZoroSeo({
    siteUrl: 'https://mysite.com',
    pillarThreshold: 3,
  });

  // 3. Run full cluster and orphan analysis
  const report = zoro.analyze(posts);

  // 4. Return format according to query param: ?type=json | ?type=tree | ?type=csv_summary | ?type=csv_matrix
  if (type === 'tree') {
    const htmlTree = zoro.exportTreeHtml(report);
    return new NextResponse(htmlTree, {
      headers: {
        'Content-Type': 'text/html; charset=utf-8',
      },
    });
  }

  if (type === 'csv_summary') {
    const csv = zoro.exportPostsCsv(report.posts);
    return new NextResponse(csv, {
      headers: {
        'Content-Type': 'text/csv; charset=utf-8',
        'Content-Disposition': 'attachment; filename="zoro-seo-summary.csv"',
      },
    });
  }

  if (type === 'csv_matrix') {
    const csv = zoro.exportMatrixCsv(report.matrix);
    return new NextResponse(csv, {
      headers: {
        'Content-Type': 'text/csv; charset=utf-8',
        'Content-Disposition': 'attachment; filename="zoro-seo-matrix.csv"',
      },
    });
  }

  // Default: Return structured JSON for React dashboard / frontend components
  return NextResponse.json(report);
}
