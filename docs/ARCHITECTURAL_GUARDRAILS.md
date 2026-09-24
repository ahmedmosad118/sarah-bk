# SARH ERP — Architectural Guardrails & Technical Standards
**دليل المعايير المعمارية والضوابط الهندسية للمنظومة**

---

## 🛡️ قاعدة: عزل انتقالات الحالة الرسمية (Status Transition Isolation)

أي حقل يمثل انتقال حالة رسمي (`status` بقيم مثل `Approved`, `Sent`, `Signed`, `Won`, `Superseded`...) أو حقول توثيق الانتقال والاعتماد (`approved_by`, `approved_at`, `reviewed_by`, `reviewed_at`, `signed_by`, `sent_at`...) **يجب أن يكون Read-Only تماماً** في validation rules الخاصة بـ `store()` و `update()`.

### 1. القواعد الإلزامية لكل مرحلة تجارية (Core Rules):
1. **الحالة الافتراضية عند الإنشاء ثابتة دائماً في الكود (`'Draft'` / `'New'` / `'Scheduled'`):**
   - لا تُقبل الحالة كمدخل من المستخدم في `store()` إطلاقاً، حتى كـ optional أو nullable.
   - لا يُقبل رقم الإصدار `version` كمدخل مباشر من المستخدم (يبدأ دائماً من `1`).
2. **انتقال الحالة يمر حصرياً عبر Endpoint مخصص ومحمي:**
   - كل انتقال حالة له Endpoint مخصص ومستقل (`/submit-review`, `/approve`, `/create-revision`, `/complete`, `/cancel`, `/qualify`, `/convert`...).
   - الـ Endpoint المخصص ينفذ دورة التحقق الكاملة، الصلاحية المناسبة (`.approve` / `.complete`)، التسجيل في الـ Activity Log، والآثار الجانبية المترتبة مثل استبدال الإصدارات القديمة (`Superseded`).
3. **منع التعديل أو الحذف المباشر للسجلات المعتمدة (Immutability of Approved Records):**
   - أي سجل في حالة `Approved` لا يجوز تعديله أو حذفه مباشرة (إرجاع `422 Unprocessable Entity`).
   - التعديل يتم فقط عبر دورة المراجعات والإصدارات (`createRevision`).
4. **استخدام Trait دورة الاعتماد المشترك (`HasApprovalWorkflow`):**
   - تستخدم كافة النماذج ذات دورة الحياة المعتمدة (Measurement, Scope, BOQ, Estimation, Quotation, Contract) الـ Trait الموحد `App\Core\Concerns\HasApprovalWorkflow`.
   - توليد أرقام الإصدارات المتتالية يتم عبر `$model->nextRevisionNumber()` وفق صيغة `{base}-V{n}` المعتمدة.
5. **معيار انعدام الأسعار قبل الـ BOQ (Zero Pricing Standard):**
   - يمنع منعاً باتاً إضافة أي حقول أسعار أو تكاليف أو هوامش في مراحل ما قبل الـ BOQ (مثل Site Visit و Measurement و Scope).

---

## 🔍 قائمة تدقيق الـ Pull Request ومراجعة الكود (PR Checklist):
عند مراجعة أي Pull Request لمرحلة جديدة (BOQ, Estimation, Quotation, Contract, Project):
- [ ] فحص `store()`: التأكد من عدم وجود حقل `status` أو `version` أو `approved_by` في validation rules.
- [ ] فحص `update()`: التأكد من عدم إمكانية تعديل `status` أو بيانات الاعتماد مباشرة.
- [ ] فحص الحماية: التأكد من منع تعديل وحذف السجلات في حالة `Approved`.
- [ ] فحص الـ Foreign Keys: التأكد من استخدام `onDelete('restrict')` على السجلات المعتمدة المرجعية لمنع الحذف الصامت المتتالي.
- [ ] فحص عزل المستأجرين (Tenant Isolation): التأكد من عدم وجود أي ثغرة IDOR واختبار العزل بين المستأجرين.
