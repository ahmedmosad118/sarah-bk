<?php

return [
    'login' => 'تم تسجيل الدخول بنجاح',
    'logout' => 'تم تسجيل الخروج',
    'password_changed' => 'تم تغيير كلمة المرور بنجاح',
    'profile_updated' => 'تم تحديث بيانات الملف الشخصي',
    'avatar_updated' => 'تم تحديث الصورة الشخصية',
    'role_created' => 'تم إنشاء دور مخصص جديد (:role)',
    'role_updated' => 'تم تحديث صلاحيات وبيانات الدور (:role)',
    'role_deleted' => 'تم حذف الدور (:role)',
    'lead_converted' => 'تم تحويل العميل المحتمل (:title) إلى عميل/مشروع معتمد',
    'lead_assigned' => 'تم إسناد العميل المحتمل (:title) إلى المسؤول (:user)',
    'lead_qualified' => 'تم تحديث بيانات تأهيل العميل المحتمل (:title) وتعديل الحالة إلى (:status)',
    'settings_updated' => 'تم تحديث إعدادات المنظومة',
    'user_status_changed' => 'تم :status حساب المستخدم :user',
    'user_roles_updated' => 'تم تحديث أدوار المستخدم :user',
    'status_activated' => 'تفعيل',
    'status_deactivated' => 'تعطيل',
    'tenant_provisioned' => 'تم إنشاء وتهيئة منظومة الشركة (:tenant) وحساب المالك (:owner) بنجاح.',

    // Model Lifecycle Activity Logs
    'user_created' => 'تم إنشاء المستخدم :name',
    'user_updated' => 'تم تحديث بيانات المستخدم :name',
    'user_deleted' => 'تم حذف المستخدم :name',
    'user_event' => 'إجراء :event على المستخدم :name',

    'customer_created' => 'تم تسجيل العميل الجديد (:name)',
    'customer_updated' => 'تم تحديث بيانات العميل (:name)',
    'customer_deleted' => 'تم حذف بيانات العميل (:name)',
    'customer_event' => 'إجراء :event على العميل :name',

    'lead_model_created' => 'تم تسجيل العميل المحتمل الجديد (:title)',
    'lead_model_updated' => 'تم تحديث بيانات العميل المحتمل (:title)',
    'lead_model_deleted' => 'تم حذف العميل المحتمل (:title)',
    'lead_event' => 'إجراء :event على العميل المحتمل :title',

    'job_title_created' => 'تم إنشاء المسمى الوظيفي :name',
    'job_title_updated' => 'تم تحديث المسمى الوظيفي :name',
    'job_title_deleted' => 'تم حذف المسمى الوظيفي :name',
    'job_title_event' => 'إجراء :event على المسمى الوظيفي :name',

    'opportunity_converted_from_lead' => 'تم تحويل الطلب (:lead) إلى فرصة (:title)',
    'opportunity_assigned' => 'تم إسناد الفرصة (:title) إلى (:user)',
    'opportunity_stage_changed' => 'تم تغيير مرحلة الفرصة (:title) إلى (:stage)',
    'opportunity_created' => 'تم إضافة الفرصة (:title)',
    'opportunity_updated' => 'تم تحديث بيانات الفرصة (:title)',
    'opportunity_deleted' => 'تم حذف الفرصة (:title)',
    'opportunity_event' => 'إجراء :event على الفرصة :title',

    // Site Visits Activity Logs
    'site_visit_created' => 'تم تسجيل وحجز معاينة موقع جديدة (#:id)',
    'site_visit_updated' => 'تم تحديث بيانات معاينة الموقع (#:id)',
    'site_visit_deleted' => 'تم حذف معاينة الموقع (#:id)',
    'site_visit_event' => 'إجراء :event على معاينة الموقع (#:id)',
    'site_visit_created_from_opportunity' => 'تم إنشاء معاينة موقع (#:id) مرتبطة بالفرصة (:opportunity)',
    'site_visit_assigned' => 'تم تكليف المهندس (:user) بمعاينة الموقع (#:id)',
    'site_visit_scheduled' => 'تم جدولة موعد معاينة الموقع (#:id) بتاريخ (:date)',
    'site_visit_completed' => 'تم اعتماد وإتمام معاينة الموقع (#:id) وتسجيل التقييم والمقايسات',
    'site_visit_cancelled' => 'تم إلغاء معاينة الموقع (#:id)',
    'site_visit_photos_uploaded' => 'تم رفع (:count) صور فوتوغرافية لمعاينة الموقع (#:id)',

    // Measurements (Phase 7)
    'measurement_created' => 'تم إنشاء المقايسة الهندسية (#:number)',
    'measurement_updated' => 'تم تحديث بيانات وحصر المقايسة (#:number)',
    'measurement_deleted' => 'تم حذف المقايسة (#:number)',
    'measurement_event' => 'إجراء :event على المقايسة (#:number)',
    'measurement_submitted_for_review' => 'تم إرسال المقايسة (#:number) للمراجعة الهندسية',
    'measurement_approved' => 'تم اعتماد المقايسة (#:number) رسمياً كمصدر حقيقة للكميات',

    // Scopes (Phase 8)
    'scope_created' => 'تم إنشاء نطاق الأعمال وتوصيف البنود (#:number)',
    'scope_updated' => 'تم تحديث نطاق الأعمال والمواصفات (#:number)',
    'scope_deleted' => 'تم حذف نطاق الأعمال (#:number)',
    'scope_event' => 'إجراء :event على نطاق الأعمال (#:number)',
    'scope_submitted_for_review' => 'تم إرسال نطاق الأعمال (#:number) للمراجعة الفنية',
    'scope_approved' => 'تم اعتماد نطاق الأعمال (#:number) والمواصفات الفنية رسمياً',

    // BOQ (Phase 9)
    'boq_created' => 'تم إنشاء جدول الكميات (#:number)',
    'boq_updated' => 'تم تحديث بنود جدول الكميات (#:number)',
    'boq_deleted' => 'تم حذف جدول الكميات (#:number)',
    'boq_event' => 'إجراء :event على جدول الكميات (#:number)',
    'boq_submitted_for_review' => 'تم إرسال جدول الكميات (#:number) للمراجعة الفنية',
    'boq_approved' => 'تم اعتماد جدول الكميات (#:number) رسمياً كوثيقة كميات قابلة للتسعير',

    // Estimations (Phase 10)
    'estimation_created' => 'تم إنشاء دراسة التسعير والتكلفة (#:number)',
    'estimation_updated' => 'تم تحديث دراسة التسعير وحسابات التكلفة (#:number)',
    'estimation_deleted' => 'تم حذف دراسة التسعير (#:number)',
    'estimation_event' => 'إجراء :event على دراسة التسعير (#:number)',
    'estimation_submitted_for_review' => 'تم إرسال دراسة التسعير (#:number) للمراجعة الفنية والإدارية',
    'estimation_approved' => 'تم اعتماد دراسة التسعير والتكلفة (#:number) رسمياً',

    // Quotations (Phase 11)
    'quotation_created' => 'تم إنشاء عرض السعر (#:number)',
    'quotation_updated' => 'تم تحديث عرض السعر والأسعار (#:number)',
    'quotation_deleted' => 'تم حذف عرض السعر (#:number)',
    'quotation_submitted_for_review' => 'تم تقديم عرض السعر (#:number) للمراجعة الداخلية',
    'quotation_approved' => 'تم اعتماد عرض السعر (#:number) داخلياً',
    'quotation_revision_created' => 'تم إنشاء إصدار جديد (#:number) من عرض السعر (#:from)',
    'quotation_sent_to_client' => 'تم تسجيل إرسال عرض السعر (#:number) رسمياً للعميل',
    'quotation_client_responded' => 'تم تسجيل رد العميل على عرض السعر (#:number) بـ :response',

    'setting_created' => 'تم إنشاء الإعداد :key',
    'setting_updated' => 'تم تحديث الإعداد :key',
    'setting_event' => 'إجراء :event على الإعداد :key',
];
