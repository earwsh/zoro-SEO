# Zoro SEO 🗡️

> WordPress Plugin for Internal Link Extraction, Topic Clusters & Pillar Content Analysis.
> افزونه پیشرفته وردپرس برای استخراج لینک‌های داخلی، تحلیل ساختار پیلار-کلاستر و تدوین تقویم محتوایی.

[![Version](https://img.shields.io/badge/version-0.0.1-blue.svg)](https://github.com/earwsh/zoro-SEO)
[![Author](https://img.shields.io/badge/author-earwsh-green.svg)](https://github.com/earwsh)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://php.net)

[English](#english) | [فارسی](#فارسی)

---

<a name="english"></a>
## English

**Zoro SEO** is a specialized WordPress plugin engineered to extract article titles, map internal link architectures, detect **Pillar pages**, cluster articles, **Orphan pages** (0 incoming internal links), and dead ends. It provides actionable data and interactive visualizations to formulate effective monthly content calendars and internal linking strategies.

### Key Features

1. **Intelligent Internal Link Scanner:**
   - Automatically crawls and parses all published article contents (`post_content`).
   - Extracts anchor texts, image `alt` texts, and link directives (`dofollow` / `nofollow`).
   - Handles absolute, relative, and non-ASCII / Unicode URLs seamlessly.
   - Filters out external links and self-referencing links.

2. **Pillar-Cluster & Internal SEO Engine:**
   - Calculates inlink and outlink counts for every post.
   - Detects **Orphan Pages** (posts with 0 incoming internal links) that need link coverage.
   - Detects **Pillar Posts** (top content hubs receiving the strongest internal equity).
   - Detects **Dead-End Pages** (posts that link out to 0 internal articles).
   - Topic cluster categorization and automatic recommendation of the best Pillar per category.

3. **Interactive Admin Dashboard (RTL & LTR Ready):**
   - Live KPI overview cards (Total Posts, Internal Links, Orphan Count, Pillars, Average Links).
   - Real-time search and multi-criteria filtering (by category and pillar/cluster/orphan role).
   - Modal inspection drawer displaying inlinks, outlinks, and anchor text breakdowns per article.
   - AJAX Batch Processing to prevent server timeouts on large websites with hundreds or thousands of articles.

4. **Multi-Format Exports:**
   - **Excel / CSV Summary:** Includes UTF-8 BOM encoding for flawless rendering of non-English / Persian characters in Microsoft Excel.
   - **Full Internal Link Matrix:** Source post, target post, anchor text, and follow status.
   - **Standalone Interactive HTML Tree:** A self-contained, offline-compatible hierarchical tree visualization with folding branches, search, and detailed drawer inspection.

### Installation

1. Download [`zoro-seo.zip`](https://github.com/earwsh/zoro-SEO/raw/main/zoro-seo.zip) from this repository.
2. In your WordPress Admin panel, go to **Plugins > Add New > Upload Plugin**.
3. Select `zoro-seo.zip` and click **Install Now**, then click **Activate Plugin**.
4. Navigate to the new **Zoro SEO** menu item in your WordPress admin sidebar and click **Start Website Scan**.

### Monthly Content Planning Workflow

- **Orphan Page Recovery:** Filter the dashboard table by `Orphan` and schedule internal links to these pages in this month's upcoming articles.
- **Reinforce Weak Clusters:** Review categories without strong pillars and plan new cluster topics that link to a designated pillar.
- **Anchor Text Optimization:** Ensure incoming internal links use primary target keywords rather than generic phrases.

---

<a name="فارسی"></a>
## فارسی

**Zoro SEO** یک افزونه تخصصی وردپرس است که به طور خودکار عناوین مقالات و تمام لینک‌های داخلی درون‌متنی را استخراج کرده و ساختار موضوعی **پیلار-کلاستر (Pillar-Cluster)**، مقالات یتیم (**Orphan Pages**) و صفحات بن‌بست را شناسایی می‌کند تا بتوانید تقویم محتوای ماهانه را هدفمند و مبتنی بر داده تدوین نمایید.

- **نام افزونه:** Zoro SEO
- **نسخه:** 0.0.1
- **توسعه‌دهنده:** [earwsh](https://github.com/earwsh)
- **مخزن گیت‌هاب:** [https://github.com/earwsh/zoro-SEO](https://github.com/earwsh/zoro-SEO)

### ویژگی‌های کلیدی

1. **استخراج هوشمند لینک‌های داخلی:**
   - اسکن کامل متن مقالات (`post_content`) با پشتیبانی از پیوندهای فارسی، کاراکترهای یونیکد و آدرس‌های نسبی/مطلق.
   - استخراج دقیق انکر تکست‌ها (Anchor Text) و متن جایگزین تصاویر (`alt`).
   - تشخیص وضعیت پیوند (`dofollow` یا `nofollow`).
   - نادیده‌گرفتن خودکار لینک‌های بیرونی، لینک‌های شکسته و خودارجاع.

2. **موتور تحلیل پیلار-کلاستر و سئو داخلی:**
   - محاسبه دقیق تعداد لینک‌های ورودی داخلی (Inlinks) و خروجی (Outlinks) برای هر مقاله.
   - کشف صفحات یتیم (**Orphan Pages**): مقالاتی با ۰ لینک ورودی که موتورهای جستجو و مخاطبان به سختی به آن‌ها دسترسی دارند.
   - شناسایی ستون‌های محتوا (**Pillars**): مقالاتی که بیشترین ارجاع داخلی و عمق محتوایی را دارند.
   - شناسایی صفحات بن‌بست (**Dead Ends**): مقالاتی که به هیچ مقاله داخلی دیگری لینک نداده‌اند.
   - خوشه‌بندی دسته‌ها و پیشنهاد خودکار ستون محتوای برتر برای هر دسته‌بندی موضوعی.

3. **پیشخوان تعاملی وردپرس (RTL):**
   - کارت‌های آماری سریع (کل مقالات، کل لینک‌های داخلی، مقالات یتیم، پیلارها و میانگین لینک).
   - جستجوی آنی و فیلتر چندگانه بر اساس دسته‌بندی و نقش مقاله (پیلار، کلاستر، یتیم، بن‌بست).
   - پنجره پاپ‌آپ (Modal) برای مشاهده ریز لینک‌های ورودی و خروجی هر مقاله با انکر تکست.
   - پردازش دسته‌ای ناهمگام (AJAX Batching) برای جلوگیری از خطای Time-out در سایت‌های بزرگ.

4. **فرمت‌های برون‌بری دیتا (Export):**
   - **خروجی اکسل مقالات (CSV با UTF-8 BOM):** کاملاً خوانا و سازگار با اکسل بدون به‌هم‌ریختگی حروف فارسی.
   - **خروجی ماتریس کامل لینک‌ها:** گزارش جزئیات هر پیوند (مبدأ، مقصد، انکر تکست و نوع لینک).
   - **نقشه درختی تعاملی (HTML Tree):** فایل HTML مستقل و بدون نیاز به اینترنت با قابلیت جمع و باز کردن شاخه‌ها، جستجوی زنده، کشوی جزئیات و چاپ PDF.

### نحوه نصب در وردپرس

1. فایل [`zoro-seo.zip`](https://github.com/earwsh/zoro-SEO/raw/main/zoro-seo.zip) را دانلود کنید.
2. در پیشخوان وردپرس، به مسیر **افزونه‌ها > افزودن افزونه جدید > بارگذاری افزونه** بروید.
3. فایل `zoro-seo.zip` را انتخاب کرده و روی **هم‌اکنون نصب کن** کلیک کرده و سپس افزونه را فعال کنید.
4. در منوی مدیریت وردپرس، روی گزینه **«Zoro SEO»** کلیک کرده و دکمه **«آغاز اسکن وبسایت»** را بزنید.

### راهنمای تدوین تقویم محتوای ماهانه با داده‌های Zoro SEO

1. **نجات مقالات یتیم (Orphan Pages):**
   - از فیلتر نقش‌ها در جدول، گزینه «فقط صفحات یتیم» را انتخاب کنید.
   - برای این ماه در تقویم محتوایی قرار دهید که مقالات جدید یا مقالات مرتبط قدیمی حتماً به این صفحات لینک دهند تا اعتبار و رتبه آن‌ها احیا شود.
2. **تکمیل زنجیره کلاسترها حول پیلارها:**
   - بخش خوشه‌های موضوعی را بررسی کنید؛ دسته‌هایی که پیلار مشخصی ندارند یا مقالات درون آنها به پیلار لینک نداده‌اند را تقویت کرده و عناوین جدید این ماه را متناسب با آن‌ها انتخاب کنید.
3. **بهینه‌سازی انکر تکست‌ها:**
   - انکر تکست‌های دریافتی مقالات مهم را چک کنید تا کلمات کلیدی هدف استفاده شده باشد و از متن‌های عمومی (مثل «اینجا کلیک کنید») پرهیز شود.

---

### License
GPLv2 or later.
Developed by [earwsh](https://github.com/earwsh).
