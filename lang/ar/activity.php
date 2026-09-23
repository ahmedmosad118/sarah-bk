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

    'setting_created' => 'تم إنشاء الإعداد :key',
    'setting_updated' => 'تم تحديث الإعداد :key',
    'setting_event' => 'إجراء :event على الإعداد :key',
];
