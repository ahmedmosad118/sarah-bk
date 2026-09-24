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
  - `MeasurementManagementTest`: (12 tests, 76 assertions)
  - `MeasurementDxfImportTest`: (6 tests, 34 assertions)
  - `SiteVisitManagementTest`: (10 tests, 54 assertions)
  - `LeadManagementTest`: (11 tests, 99 assertions)
- **حالة المرحلة:** تم إغلاق وتأكيد **Phase 7 (المقايسات وحصر الكميات)** رسمياً بنجاح تام.

---

### 🚀 9. Phase 7 Fixes & DXF Import Extension (تصحيحات الاستيراد ومصدر أوتوكاد DXF)

#### 🅰️ تصحيح استيراد المعاينات الميدانية (`importFromSiteVisit`):
1. **إلزامية تحديد الفرصة التجارية صراحة (`opportunity_id`):** تم إلغاء كافة محاولات التخمين التلقائي أو إنشاء الفرص الصامتة أو تعديل المعاينات التاريخية.
2. **فحص تطابق العميل (`Sanity Check`):** التأكد الصارم من أن الفرصة المختارة تتبع نفس عميل المعاينة، وإرجاع `422 Unprocessable Entity` مع رسالة خطأ واضحة عند عدم التطابق.
3. **عدم المساس بالبيانات التاريخية:** يبقى حقل `site_visit->opportunity_id` كما هو في قاعدة البيانات بدون أي تعديل ضمني، وتقوم المقايسة بالربط المزدوج المستقل.

#### 🅱️ استيراد أبعاد ومساحات الغرف من ملفات AutoCAD DXF:
1. **المعمارية التقنية للمحلل (`DxfParserService.php`):**
   - محلل متكامل ومكتوب بـ Pure PHP للتعامل مع ملفات ASCII DXF بسرعة فائقة بدون الحاجة لأي مكتبات خارجية أو خدمات بايثون إضافية.
   - قراءة الـ Polylines المغلقة (`LWPOLYLINE` / `POLYLINE`) مع فحص طبقة الغرف (`rooms_layer`).
   - قراءة الـ Texts/Labels (`TEXT` / `MTEXT`) مع فحص طبقة أسماء الغرف (`labels_layer`) وتنقية أكواد التنسيق التلقائي لـ AutoCAD.
2. **محرك المساحات الرياضي بمعادلة Shoelace:**
   $$\text{Area} = \frac{1}{2} \left| \sum_{i=0}^{n-1} (x_i y_{i+1} - x_{i+1} y_i) \right|$$
   - حساب دقيق للمضلعات غير المنتظمة والمكونة من أي عدد من الأضلاع (مثل الغرف على شكل حرف L والشقق غير المتماثلة).
   - إثبات دقة الحساب: تم اختبار حساب غرفة `RECEPTION` (20.0 م²)، وغرفة `BEDROOM 1` (14.0 م²)، وغرفة `KITCHEN (L-SHAPE)` غير المنتظمة (11.1 م² بالضبط).
3. **مطابقة أسماء الغرف جغرافياً:**
   - تطبيق خوارزمية `Ray-Casting Point-in-Polygon` لتحديد النصوص الواقعة داخل المضلع، والاعتماد على أقرب نص للمركز الهندسي (`Centroid`) كبديل.
4. **حالة المقايسة المستوردة:** المقايسات المستوردة من DXF تُنشأ دائماً بحالة مسودة (`Draft`) وتتطلب مراجعة واعتماد المهندس المسؤول قبل اعتمادها كحقيقة فنية.
5. **الواجهة الأمامية (`MeasurementsView.vue`):**
   - نافذة تفاعلية تتيح رفع ملفات `.dxf` فقط (مع رفض `.dwg` و `.pdf` برسائل إرشادية واضحة).
   - اختيار الـ Layers والفرصة التجارية عبر مكون `SearchableSelect`.
   - جدول معاينة تفاعلي حي يعرض الغرف والمساحات المحسوبة والمحيط مع إمكانية التعديل اليدوي قبل الحفظ النهائي.

---

### 📦 10. Phase 8 — Scope of Work Module (نطاق الأعمال والمواصفات الفنية)

#### 🎯 1. المفهوم والقاعدة التجارية الجوهرية (Core Business Principles):
- **Scope = "إيه الشغل والمواصفات الفنية اللي هنلتزم بيها؟"** (مش "قد إيه الكمية؟" ومش "بكام؟").
- **Zero Pricing Standard الصارم:** خلو وثائق نطاق العمل تماماً من أي أسعار، تكاليف، أو هوامش ربح (مسؤولية BOQ & Estimation اللاحقة).
- **الارتباط بإصدار مقايسة معتمد محدد (`measurement_id` بحالة `Approved` إجبارياً):**
  - لا يمكن إنشاء Scope على مقايسة في حالة `Draft` أو `Under Review`.
  - كل نطاق عمل مربوط بـ ID المقايسة المعتمدة تحديداً، مما يضمن الحفاظ على التتبع التاريخي (`Traceability`) حتى في حال إصدار مراجعة لاحقة للمقايسة ($V_2$).
- **حماية النطاق المعتمد وعدم قابليته للتعديل المباشر (Immutability & Versioning):**
  - النطاق المعتمد (`Approved`) مغلق ضد التعديل أو الحذف المباشر (إرجاع `422 Unprocessable Entity`).
  - التعديل يتم فقط عبر دورة إصدارات صريحة (`createRevision`)، حيث يتم استنساخ $V_N \rightarrow V_{N+1}$ كمسودة جديدة، مع بقاء $V_N$ سارية حتى اعتماد $V_{N+1}$، وعندها تتحول السابقة إلى `Superseded`.
- **الربط المتعدد بين بنود النطاق وبنود المقايسة (Many-to-Many Linkage):**
  - بند نطاق العمل الواحد (مثل: "توريد وتركيب دهانات جوتن") يمكنه تغطية عدة بنود قياس (دهان الريسبشن، دهان الماستر روم، دهان الممر).
  - تحقق خادم صارم (`Cross-Measurement Integrity`): يمنع ربط أي `measurement_item` لا ينتمي لنفس الـ `measurement_id` الخاصة بالنطاق.
- **عزل المستأجرين الكامل (Tenant Isolation):** عزل تام ومحكم لكل جداول `scopes` و `scope_items` وجدول الربط `scope_item_measurement_item`.

#### 🗄️ 2. الهيكل البياني لقاعدة البيانات (Data Architecture):
```text
Table: scopes
├── id (BIGINT, Primary Key)
├── opportunity_id (BIGINT, Foreign Key -> opportunities.id ON DELETE CASCADE)
├── measurement_id (BIGINT, Foreign Key -> measurements.id ON DELETE RESTRICT) [Approved Measurement]
├── scope_number (VARCHAR 50, Indexed) [e.g. SC-OPP1-0001]
├── version (INT, Default 1)
├── status (VARCHAR 50: Draft, Under Review, Approved, Superseded)
├── title (VARCHAR 255)
├── general_inclusions (TEXT, Nullable)
├── general_exclusions (TEXT, Nullable) [مهم جداً لتفادي النزاعات التجارية]
├── prepared_by (BIGINT, Nullable, Foreign Key -> users.id)
├── prepared_at (DATETIME, Nullable)
├── reviewed_by (BIGINT, Nullable, Foreign Key -> users.id)
├── reviewed_at (DATETIME, Nullable)
├── approved_by (BIGINT, Nullable, Foreign Key -> users.id)
├── approved_at (DATETIME, Nullable)
├── notes (TEXT, Nullable)
├── created_by (BIGINT, Foreign Key -> users.id)
└── created_at, updated_at (TIMESTAMPS)

Table: scope_items
├── id (BIGINT, Primary Key)
├── scope_id (BIGINT, Foreign Key -> scopes.id ON DELETE CASCADE)
├── trade_category (VARCHAR 100) [تصنيف الحرفة/التخصص]
├── item_name (VARCHAR 255) [اسم بند العمل]
├── specification (LONGTEXT) [المواصفة الفنية التفصيلية وطريقة التنفيذ]
├── inclusions (TEXT, Nullable) [المتضمن في البند]
├── exclusions (TEXT, Nullable) [المستثنى من البند]
├── notes (TEXT, Nullable)
├── sort_order (INT, Default 0)
└── created_at, updated_at (TIMESTAMPS)

Table: scope_item_measurement_item (Many-to-Many Pivot)
├── scope_item_id (BIGINT, Foreign Key -> scope_items.id ON DELETE CASCADE)
├── measurement_item_id (BIGINT, Foreign Key -> measurement_items.id ON DELETE CASCADE)
└── UNIQUE INDEX (scope_item_id, measurement_item_id)
```

#### 🌐 3. واجهات الـ API ومسارات النظام (REST API Endpoints):
```text
GET    /api/scopes                                  # استعراض نطاقات الأعمال مع الفلاتر والإحصائيات
POST   /api/scopes                                  # إنشاء نطاق أعمال جديد مربوط بمقايسة معتمدة
GET    /api/scopes/{id}                             # استعراض تفاصيل نطاق العمل وبنوده وبنود الحصر المرتبطة
PUT    /api/scopes/{id}                             # تعديل نطاق العمل (فقط للمسودات وقيد المراجعة)
DELETE /api/scopes/{id}                             # حذف نطاق العمل (فقط للمسودات وقيد المراجعة)
POST   /api/scopes/{id}/submit-review               # تحويل حالة النطاق إلى قيد المراجعة (Under Review)
POST   /api/scopes/{id}/approve                     # اعتماد النطاق رسمياً وقفل التعديل عليه (Approved)
POST   /api/scopes/{id}/create-revision             # إنشاء إصدار جديد قابل للتعديل (Draft Revision)
POST   /api/scopes/from-measurement/{measurementId} # إنشاء نطاق أعمال مهيأ ومربوط بمقايسة معتمدة محددة
```

#### 🖥️ 4. واجهة المستخدم (Vue 3 / ScopesView.vue):
- **الجدول والإحصائيات التفاعلية:** كروت KPI لعرض إجمالي النطاقات، المسودات، قيد المراجعة، المعتمدة، وبنود الأعمال.
- **تصفية سياقية للفرصة البيعية (`?opportunity_id=...`):** تصفية تلقائية لنطاقات الفرصة المحددة مع زر إنشاء فوري.
- **مُنشئ حزم الأعمال (Work Packages Builder):**
  - اختيار التخصص (`Trade Category`) من قائمة مهنية موحدة للتشطيبات والمقاولات.
  - محرر المواصفات الفنية التفصيلية وطريقة التنفيذ والاشتمالات والاستثناءات لكل بند.
  - أداة ربط تفاعلية مع بنود المقايسة المعتمدة (`Measurement Items Linker`) متعددة الاختيار.
- **نافذة عرض واعتماد وطباعة وثيقة نطاق العمل (`Printable Sheet`):** عرض رسمي متكامل للمواصفات والاشتمالات والاستثناءات وتوقيعات مهندسي الإعداد والمراجعة والاعتماد.

#### 🧪 5. مصفوفة الاختبارات الآلية والتحقق (Automated Test Suite):
تم تنفيذ ملف الاختبارات الشامل `tests/Feature/ScopeManagementTest.php` بـ 7 سيناريوهات اختبارية دقيقة:
1. **Scenario 1:** دورة العمل الكاملة (إنشاء نطاق على مقايسة معتمدة $\rightarrow$ ربط بنود $\rightarrow$ تقديم للمراجعة $\rightarrow$ اعتماد) $\rightarrow$ **PASS**.
2. **Scenario 2:** الرفض الصارم لإنشاء Scope على مقايسة في حالة Draft أو غير معتمدة $\rightarrow$ **PASS (422)**.
3. **Scenario 3:** الرفض الصارم لربط بند Scope ببند قياس يتبع مقايسة أخرى (Cross-Measurement Integrity) $\rightarrow$ **PASS (422)**.
4. **Scenario 4:** حماية النطاق المعتمد من التعديل أو الحذف المباشر $\rightarrow$ **PASS (422)**.
5. **Scenario 5:** دورة الإصدارات (Revisions) وحفظ النسخ التاريخية السابقة كـ `Superseded` $\rightarrow$ **PASS**.
6. **Scenario 6:** عزل المستأجرين الكامل ومنع ثغرات IDOR بين المستأجرين $\rightarrow$ **PASS (404)**.
7. **Scenario 7:** التحقق الصارم من انعدام حقول التسعير في جداول النطاق (Zero Pricing Standard) $\rightarrow$ **PASS**.

**نتيجة الفحص النهائي:** `OK (7 tests, 75 assertions) — 100% Success`.



