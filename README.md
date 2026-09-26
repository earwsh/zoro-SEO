# Zoro SEO 🗡️

> WordPress Plugin for Internal Link Extraction, Topic Clusters & Pillar Content Analysis.
> افزونه پیشرفته وردپرس برای استخراج لینک‌های داخلی، تحلیل ساختار پیلار-کلاستر و تدوین تقویم محتوایی.

[![Version](https://img.shields.io/badge/version-0.1.0-blue.svg)](https://github.com/earwsh/zoro-SEO)
[![Author](https://img.shields.io/badge/author-earwsh-green.svg)](https://github.com/earwsh)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://php.net)

[English](#english) | [فارسی](#فارسی)

---

<a name="english"></a>
## English

**Zoro SEO** is a specialized WordPress plugin engineered to extract article titles, map internal link architectures, detect **Pillar pages**, cluster articles, **Orphan pages** (0 incoming internal links), and dead ends. It provides actionable data and interactive visualizations to formulate effective monthly content calendars and internal linking strategies.

### Key Features

1. **Intelligent Internal Link Scanner & Page Builder Support:**
   - Automatically crawls and parses contents across **Posts, Pages, WooCommerce Products, and Custom Post Types (CPTs)**.
   - **Elementor Support:** Recursively extracts links and anchors from `_elementor_data` JSON widgets (Text Editor, Buttons, Headings, Icon Lists).
   - Extracts anchor texts, image `alt` texts, and link directives (`dofollow` / `nofollow`).
   - Handles absolute, relative, and non-ASCII / Unicode URLs seamlessly.
   - Filters out external links and self-referencing links.

2. **In-Editor Meta Box & Real-Time Sync:**
   - **Gutenberg & Classic Editor Meta Box:** Displays real-time cluster role, incoming/outgoing link counts, and cluster link suggestions directly while editing.
   - **Orphan Alert Banner:** Instant high-priority warning in the post editor if an article has 0 incoming links.
   - **Automated `save_post` Sync:** Incrementally scans published and updated posts without requiring a full site crawl.

3. **Pillar-Cluster & Internal SEO Engine:**
   - Calculates inlink and outlink counts for every post.
   - Detects **Orphan Pages** (posts with 0 incoming internal links) that need link coverage.
   - Detects **Pillar Posts** (top content hubs receiving the strongest internal equity).
   - Detects **Dead-End Pages** (posts that link out to 0 internal articles).
   - Topic cluster categorization and automatic recommendation of the best Pillar per category.

4. **Interactive Admin Dashboard (RTL & LTR Ready):**
   - Post types selection control (toggle Posts, Pages, Products, CPTs).
   - Live KPI overview cards (Total Posts, Internal Links, Orphan Count, Pillars, Average Links).
   - Real-time search and multi-criteria filtering (by Post Type, Category, and Pillar/Cluster/Orphan role).
   - Modal inspection drawer displaying inlinks, outlinks, and anchor text breakdowns per article.
   - AJAX Batch Processing to prevent server timeouts on large websites.

5. **Multi-Format Exports:**
   - **Excel / CSV Summary:** Includes UTF-8 BOM encoding for flawless rendering of non-English / Persian characters in Microsoft Excel.
   - **Full Internal Link Matrix:** Source post, target post, anchor text, and follow status.
   - **Standalone Interactive HTML Tree:** A self-contained, offline-compatible hierarchical tree visualization with folding branches, search, and detailed drawer inspection.

### Installation

1. Download [`zoro-seo.zip`](https://github.com/earwsh/zoro-SEO/raw/main/zoro-seo.zip) from this repository.
2. In your WordPress Admin panel, go to **Plugins > Add New > Upload Plugin**.
3. Select `zoro-seo.zip` and click **Install Now**, then click **Activate Plugin**.
4. Navigate to the new **Zoro SEO** menu item in your WordPress admin sidebar, select your preferred post types, and click **Start Website Scan**.

---

<a name="فارسی"></a>
## فارسی

**Zoro SEO** یک افزونه تخصصی وردپرس است که به طور خودکار عناوین مقالات، برگه‌ها و محصولات و تمام لینک‌های داخلی درون‌متنی را استخراج کرده و ساختار موضوعی **پیلار-کلاستر (Pillar-Cluster)**، مقالات یتیم (**Orphan Pages**) و صفحات بن‌بست را شناسایی می‌کند تا بتوانید تقویم محتوای ماهانه را هدفمند و مبتنی بر داده تدوین نمایید.

- **نام افزونه:** Zoro SEO
- **نسخه:** 0.1.0
- **توسعه‌دهنده:** [earwsh](https://github.com/earwsh)
- **مخزن گیت‌هاب:** [https://github.com/earwsh/zoro-SEO](https://github.com/earwsh/zoro-SEO)

### ویژگی‌های کلیدی (نسخه 0.1.0)

1. **استخراج هوشمند لینک‌های داخلی و پشتیبانی از صفحه‌سازها:**
   - اسکن کامل محتوا در **نوشته‌ها، برگه‌ها، محصولات ووکامرس و انواع پست‌های سفارشی (CPT)**.
   - **پشتیبانی کامل از المنتور (Elementor):** استخراج عمیق لینک‌ها و انکر تکست‌ها از متادیتاهای JSON المنتور (`_elementor_data`) شامل ویجت‌های ویرایشگر متن، دکمه‌ها، آیکون‌باکس و سربرگ‌ها.
   - اسکن هوشمند پیوندهای فارسی، کاراکترهای یونیکد و آدرس‌های نسبی/مطلق.
   - استخراج دقیق انکر تکست‌ها (Anchor Text) و متن جایگزین تصاویر (`alt`).
   - تشخیص وضعیت پیوند (`dofollow` یا `nofollow`).

2. **متاباکس اختصاصی در صفحه ویرایش نوشته و همگام‌سازی زنده:**
   - **متاباکس هوشمند در ویرایشگر (گوتنبرگ و کلاسیک):** نمایش زنده وضعیت مقاله (نقش پیلار/کلاستر، تعداد ورودی‌ها و خروجی‌ها) حین نگارش یا ویرایش.
   - **هشدار صفحه یتیم (Orphan Alert):** نمایش بنر هشدار واضح در صورت نداشتن ارجاع ورودی برای نجات فوری صفحه.
   - **پیشنهاد پیوند به مقالات هم‌کلاستر:** معرفی مقالات هم‌دسته برای لینک‌دهی دوطرفه آسان.
   - **همگام‌سازی آنی پس از ذخیره (`save_post`):** بروزرسانی خودکار شبکه لینک‌ها پس از انتشار یا ویرایش نوشته بدون نیاز به اسکن کل سایت.

3. **موتور تحلیل پیلار-کلاستر و سئو داخلی:**
   - محاسبه دقیق تعداد لینک‌های ورودی داخلی (Inlinks) و خروجی (Outlinks).
   - کشف صفحات یتیم (**Orphan Pages**): صفحاتی با ۰ لینک ورودی.
   - شناسایی ستون‌های محتوا (**Pillars**): صفحاتی که بیشترین ارجاع داخلی و عمق محتوایی را دارند.
   - شناسایی صفحات بن‌بست (**Dead Ends**): صفحاتی که به هیچ مقاله داخلی دیگری لینک نداده‌اند.
   - خوشه‌بندی دسته‌ها بر اساس تکسونومی‌ها و پیشنهاد خودکار ستون محتوای برتر برای هر دسته‌بندی موضوعی.

4. **پیشخوان تعاملی وردپرس (RTL):**
   - بخش تنظیم و انتخاب انواع پست‌تایپ‌ها برای اسکن.
   - کارت‌های آماری سریع همراه با تفکیک نوع محتوا.
   - جستجوی آنی و فیلتر سه‌گانه (بر اساس نوع محتوا، دسته‌بندی و نقش مقاله).
   - پنجره پاپ‌آپ (Modal) برای مشاهده ریز لینک‌های ورودی و خروجی با برچسب نوع محتوا.
   - پردازش دسته‌ای ناهمگام (AJAX Batching) برای جلوگیری از خطای Time-out در سایت‌های بزرگ.

5. **فرمت‌های برون‌بری دیتا (Export):**
   - **خروجی اکسل مقالات (CSV با UTF-8 BOM):** دارای ستون نوع محتوا، کاملاً خوانا در اکسل بدون به‌هم‌ریختگی حروف فارسی.
   - **خروجی ماتریس کامل لینک‌ها:** گزارش جزئیات هر پیوند (مبدأ، مقصد، انکر تکست و نوع لینک).
   - **نقشه درختی تعاملی (HTML Tree):** فایل HTML مستقل با قابلیت جمع و باز کردن شاخه‌ها و جستجوی زنده.

### نحوه نصب در وردپرس

1. فایل [`zoro-seo.zip`](https://github.com/earwsh/zoro-SEO/raw/main/zoro-seo.zip) را دانلود کنید.
2. در پیشخوان وردپرس، به مسیر **افزونه‌ها > افزودن افزونه جدید > بارگذاری افزونه** بروید.
3. فایل `zoro-seo.zip` را انتخاب کرده و روی **هم‌اکنون نصب کن** کلیک کرده و سپس افزونه را فعال کنید.
4. در منوی مدیریت وردپرس، روی گزینه **«Zoro SEO»** کلیک کرده و دکمه **«آغاز اسکن وبسایت»** را بزنید.

---

### License
GPLv2 or later.
Developed by [earwsh](https://github.com/earwsh).
