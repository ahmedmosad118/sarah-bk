# SARH ERP — Commercial Flow & Architecture Report
## تقرير المنظومة التجارية الشامل (Phases 3 — 6 + Executive Commercial Dashboard)

---

### 📋 1. Repository Review (مراجعة المستودع والتقنيات)
- **المنظومة التقنية:** Laravel 12 + PHP 8.3 + MySQL / SQLite (Multi-Database Per Tenant) + Vue 3 (Composition API / `<script setup>`) + Vite + TailwindCSS + Pinia + Vue-I18n + Lucide Icons.
- **الحزم المدمجة:** Spatie Laravel-Permission, Spatie Media Library, Spatie Activity Log.
- **المعمارية الأساسية:** `CRUDController`, `InputMaker`, `TenantUrlGenerator`, ونظام العزل الديناميكي لقواعد البيانات لكل مستأجر.

---

### 📜 2. Architectural Evolution & Git History (مسار التطوير)
1. **Phase 3 (Customer Management):** إدارة بيانات العملاء الشاملة (أفراد وشركات) والاتصالات والملاحظات.
2. **Security Hardening:** عزل المستأجرين التام، الحماية من IDOR، فحص مدخلات SQL، تنقية XSS، وحماية المرفقات.
3. **Phase 4 (Lead Management):** التقاط طلبات العملاء المحتملين وتتبع مصادر الوصول (Social, Ads, Walk-in).
4. **Phase 5 (Opportunity Foundation):** إدارة الفرص البيعية، القيمة التقديرية، التواريخ، ومراحل الإغلاق والتعاقد.
5. **Phase 6 (Site Visit Management):** إدارة المعاينات الميدانية، حجز وجدولة المواعيد، تسجيل المعاينات الفورية المنفذة، رفع المقاسات وتفكيك الفراغات/الغرف (`site_visit_rooms`)، ومعرض الصور التوثيقية الميدانية.
6. **Executive Commercial Dashboard (اللوحة التنفيذية التجارية):** عكس مسار الأعمال والـ Commercial Pipeline كاملاً على لوحة التحكم الرئيسية (`/dashboard`).

---

### 🔄 3. Complete Business Flow (دورة العمل التجارية المتكاملة)
```text
Customer (العميل)
   ↓
Lead (العميل المحتمل)
   ↓
Opportunity (الفرصة التجارية)
   ↓
Site Visit (المعاينة الميدانية)  ← [Phase 6 Completed]
   ↓
Measurement (المقايسات التفصيلية)  ← [Phase 7 Ready]
   ↓
Scope of Work (نطاق الأعمال)
   ↓
BOQ (جدول الكميات)
   ↓
Estimation (التسعير)
   ↓
Quotation (عرض السعر)
   ↓
Contract (التعاقد)
   ↓
Project (المشروع والتنفيذ)
```

---

### 📊 4. Executive Commercial Dashboard (لوحة التحكم التنفيذية الجديدة)

#### أ. شريط الإجراءات السريعة (Quick Primary Actions):
- `+ تسجيل / حجز معاينة موقع`
- `+ إضافة فرصة بيعية جديدة`
- `+ التقاط طلب عميل محتمل`
- `+ إضافة عميل جديد`

#### ب. بطاقات المؤشرات التجارية الأساسية (Commercial KPIs):
1. **العملاء (Customers):** إجمالي عدد العملاء مع تصنيفهم (أفراد وشركات).
2. **العملاء المحتملين (Leads):** إجمالي الطلبات وحالتها (جديد، تم التواصل، مؤهل).
3. **الفرص التجارية (Opportunities):** عدد الفرص النشطة + القيمة الإجمالية التقديرية لخط الأنابيب (`Pipeline Value`) بالجنيه المصري (ج.م).
4. **معاينات الموقع (Site Visits):** إجمالي المعاينات مع توضيح عدد معاينات اليوم المجدولة.

#### ج. مخطط مسار التدفق والتحويل التجاري (Commercial Funnel & Stepper):
- استعراض بصري للأرقام والمراحل:
  `العملاء (1) ➔ الطلبات (2) ➔ الفرص (3) ➔ المعاينات (4) ➔ المقايسات (5) ➔ عروض الأسعار (6) ➔ التعاقد والمشروع (7)`

#### د. البطاقات التشغيلية التفاعلية:
1. **معاينات الموقع اليوم والقادمة:** مع بطاقات العملاء، المواعيد، المهندس المكلف، وأزرار الاتصال والواتساب الفورية.
2. **أحدث الفرص البيعية الساخنة:** مع بيان مرحلة الفرصة وقيمتها وتاريخ الإغلاق المتوقع.
3. **أحدث طلبات العملاء المحتملين (Incoming Leads):** للرد والمتابعة السريعة.
4. **سجل الأنشطة والعمليات التجارية المباشرة.**

---

### 🗄️ 5. Data Model (هيكل قاعدة البيانات)
```text
Table: site_visits
├── id (BIGINT, Primary Key)
├── customer_id (BIGINT, Foreign Key -> customers.id)
├── opportunity_id (BIGINT, Nullable, Foreign Key -> opportunities.id)
├── lead_id (BIGINT, Nullable, Foreign Key -> leads.id)
├── status (VARCHAR 50: Requested, Scheduled, Completed, Cancelled, Rescheduled)
├── scheduled_date (DATE, Nullable)
├── scheduled_time (TIME, Nullable)
├── visit_date (DATE, Nullable)
├── assigned_to (BIGINT, Nullable, Foreign Key -> users.id)
├── general_assessment (TEXT, Nullable)
├── internal_notes (TEXT, Nullable)
├── created_by (BIGINT, Foreign Key -> users.id)
└── created_at, updated_at (TIMESTAMPS)

Table: site_visit_rooms
├── id (BIGINT, Primary Key)
├── site_visit_id (BIGINT, Foreign Key -> site_visits.id ON DELETE CASCADE)
├── room_name (VARCHAR 150)
├── estimated_area (DECIMAL 10,2, Nullable)
├── notes (TEXT, Nullable)
└── created_at, updated_at (TIMESTAMPS)
```

---

### 📉 6. Loss Reason Tracking & Win/Loss Analytics (تتبع وتوثيق أسباب فقدان الصفقات والليدز)
تم بناء منظومة متكاملة لحفظ وتحليل أسباب خسارة الفرص والعملاء المحتملين لخدمة التقارير المستقبلية والإدارة التنفيذية:
1. **قاعدة البيانات (`leads` & `opportunities`):**
   - `loss_reason`: التصنيف الأساسي لسبب الفقدان (`price_high`, `competitor_won`, `client_postponed`, `client_unresponsive`, `scope_mismatch`, `budget_insufficient`, `other`).
   - `competitor_name`: اسم الشركة المنافسة التي فازت بالعقد (مشروط باختيار شركة منافسة).
   - `loss_notes`: ملاحظات تفصيلية وتحليل ما بعد الفقدان (Post-Mortem Notes).
2. **تجربة المستخدم (UX / UI):**
   - في صفحة العملاء المحتملين (`LeadsView.vue`): نافذة التأهيل تعرض قسم أسباب الفقدان عند اختيار الحالة `Lost` أو `Unqualified`، مع بطاقة تحليل الخسارة عند معاينة تفاصيل الطلب.
   - في صفحة الفرص البيعية (`OpportunitiesView.vue`): عند تحويل المرحلة إلى `Lost`، تنبثق تلقائياً نافذة تسجيل سبب الخسارة، وتظهر بطاقة التحليل باللون الأحمر المميز في تفاصيل الفرصة.
3. **التوثيق وسجل الأنشطة (Activity Log):**
   - يتم تسجيل حدث تغيير المرحلة مع تفاصيل الخسارة واسم المنافس في `activity_log` لمتابعة التدقيق الأمني والإداري.

---

### 🔑 7. Permissions Matrix (منظومة الصلاحيات)
- `site_visits.view`
- `site_visits.create`
- `site_visits.update`
- `site_visits.delete`
- `site_visits.assign`
- `site_visits.complete`
- `site_visits.cancel`
- `site_visits.upload_photos`

---

### 🧪 8. Quality Assurance & Build Status (الجودة والبناء)
- **بناء الواجهة الأمامية (Vite Build):** ناجح بنسبة 100% بدون أي أخطاء (`✓ built in 13.82s`).
- **الاختبارات الآلية (Automated Tests):** تغطية شاملة لكافة عمليات الإنشاء، التحويل، دورة الحياة، وتتبع أسباب الفقدان للعملاء المحتملين والفرص التجارية.
- **جاهزية النظام:** كافة المراحل (العملاء، الليدز، الفرص، المعاينات، والداشبورد) متوافقة ومتصلة ببعضها بسلاسة.
