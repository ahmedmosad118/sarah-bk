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
5. **Phase 6 (Site Visit Management):** إدارة المعاينات الميدانية، حجز وجدولة المواعيد، تسجيل المعاينات الفورية المنفذة، وتوثيق صور الموقع.
6. **Phase 7 (Measurement / Quantity Surveying Module):** إدارة المقايسات وحصر الكميات الهندسية، محرك الحسابات الآلية المعتمد، استيراد فراغات المعاينة، وإدارة المراجعات والاعتمادات.
7. **Executive Commercial Dashboard (اللوحة التنفيذية التجارية):** عكس مسار الأعمال والـ Commercial Pipeline كاملاً على لوحة التحكم الرئيسية (`/dashboard`).

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
Measurement (المقايسات وحصر الكميات)  ← [Phase 7 Completed]
   ↓
Scope of Work (نطاق الأعمال)  ← [Phase 8 Next]
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

### 🗄️ 5. Measurement Data Model (هيكل المقايسات الهندسية — Phase 7)
```text
Table: measurements
├── id (BIGINT, Primary Key)
├── opportunity_id (BIGINT, Foreign Key -> opportunities.id ON DELETE CASCADE)
├── site_visit_id (BIGINT, Nullable, Foreign Key -> site_visits.id ON DELETE SET NULL)
├── measurement_number (VARCHAR 50, Indexed)
├── version (INT, Default 1)
├── status (VARCHAR 50: Draft, Under Review, Approved, Superseded)
├── measured_by (BIGINT, Nullable, Foreign Key -> users.id)
├── measured_at (DATE, Nullable)
├── reviewed_by (BIGINT, Nullable, Foreign Key -> users.id)
├── reviewed_at (DATETIME, Nullable)
├── approved_by (BIGINT, Nullable, Foreign Key -> users.id)
├── approved_at (DATETIME, Nullable)
├── total_area, total_volume, total_linear, total_count (DECIMAL 15,2)
├── notes (TEXT, Nullable)
├── created_by (BIGINT, Foreign Key -> users.id)
└── created_at, updated_at (TIMESTAMPS)

Table: measurement_items
├── id (BIGINT, Primary Key)
├── measurement_id (BIGINT, Foreign Key -> measurements.id ON DELETE CASCADE)
├── room_name (VARCHAR 150)
├── item_name (VARCHAR 250)
├── unit (VARCHAR 20: m2, m3, lm, pcs)
├── measurement_type (VARCHAR 30: area, volume, linear, count)
├── count (DECIMAL 10,2, Default 1.00)
├── length, width, height (DECIMAL 10,2, Nullable)
├── deductions (DECIMAL 10,2, Default 0.00)
├── gross_quantity (DECIMAL 15,2)
├── net_quantity (DECIMAL 15,2)
├── notes (TEXT, Nullable)
├── sort_order (INT, Default 0)
└── created_at, updated_at (TIMESTAMPS)
```

---

### 📐 6. Deterministic Calculation Engine (محرك الحسابات الهندسية)
تم بناء خدمة مستقلة [`MeasurementCalculationService.php`](file:///c:/xampp/htdocs/sarah-bk/app/Services/MeasurementCalculationService.php) لتطبيق القواعد الرياضية القطعية:
1. **المسطحات (Area - $\text{m}^2$):** $\text{Gross} = \text{Count} \times \text{Length} \times \text{Width}$ ، $\text{Net} = \max(0, \text{Gross} - \text{Deductions})$
2. **المكعبات (Volume - $\text{m}^3$):** $\text{Gross} = \text{Count} \times \text{Length} \times \text{Width} \times \text{Height}$ ، $\text{Net} = \max(0, \text{Gross} - \text{Deductions})$
3. **الأطوال (Linear - $\text{lm}$):** $\text{Gross} = \text{Count} \times \text{Length}$ ، $\text{Net} = \max(0, \text{Gross} - \text{Deductions})$
4. **العدد (Count - $\text{pcs}$):** $\text{Gross} = \text{Count}$ ، $\text{Net} = \max(0, \text{Gross} - \text{Deductions})$

---

### 🔑 7. Permissions Matrix (منظومة الصلاحيات)
- `site_visits.view`, `site_visits.create`, `site_visits.update`, `site_visits.delete`, `site_visits.assign`, `site_visits.complete`, `site_visits.cancel`, `site_visits.upload_photos`
- `measurements.view`, `measurements.create`, `measurements.update`, `measurements.delete`, `measurements.approve`

---

### 🧪 8. Quality Assurance & Phase 7 Final Sign-off (الجودة والإغلاق النهائي)
- **إصلاحات وتدقيق Phase 6 و Phase 7:**
  1. سد ثغرة التحويل المزدوج للعميل المحتمل وربط المعاينات في كلاً من `LeadController::convertToOpportunity` و `OpportunityController::convertFromLead`.
  2. بناء موديول المقايسات كاملاً ككيان مستقل يرتبط بـ `Opportunity` اختيارياً من `SiteVisit`.
  3. استيراد الغرف باتجاه واحد (One-Way Copy) دون التعديل على بيانات المعاينة التاريخية.
  4. منع تعديل أو حذف المقايسة المعتمدة (Approved) وحمايتها بنظام الإصدارات والمراجعات (Revisions).
  5. عزل المستأجرين بنسبة 100% ومنع الوصول بين الشركات في الاستعراض، التعديل، الاستيراد، والاعتماد.
- **الاختبارات الآلية (Automated Tests):** 100% نجاح لكافة الاختبارات:
  - `MeasurementManagementTest`: (8 tests, 64 assertions)
  - `SiteVisitManagementTest`: (10 tests, 54 assertions)
  - `LeadManagementTest`: (11 tests, 99 assertions)
- **حالة المرحلة:** تم إغلاق وتأكيد **Phase 7 (المقايسات وحصر الكميات)** رسمياً بنجاح تام.

