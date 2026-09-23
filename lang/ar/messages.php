<?php

return [
    'unauthorized' => 'غير مصرح بالدخول',
    'permission_denied' => 'ليس لديك الصلاحية المطلوبة (:permission).',
    'permissions_denied_any' => 'ليس لديك الصلاحية الكافية لتنفيذ هذا الإجراء (:permissions).',
    'validation_error' => 'البيانات المرسلة غير صحيحة.',
    'server_error' => 'حدث خطأ في النظام، يرجى المحاولة لاحقاً.',
    
    // Tenancy & Provisioning
    'tenant_inactive' => 'حساب الشركة / النطاق معطل حالياً. يرجى التواصل مع الدعم الفني.',
    'tenant_not_found' => 'تعذر التعرف على نطاق أو معرّف الشركة (Tenant). يرجى تمرير الدومين أو الهيدر X-Tenant-Slug.',
    'tenant_provisioned_success' => 'تم إنشاء وتهيئة منظومة الشركة وحساب المالك بنجاح',
    'tenant_provision_failed' => 'فشل تهيئة الشركة: :error',
    'tenant_identifier_required' => 'يرجى تحديد المعرف',

    // User Management
    'user_cannot_deactivate_owner' => 'لا يمكن تعطيل المالك الوحيد للمنظومة.',
    'user_status_updated' => 'تم :status الحساب بنجاح',
    'user_status_active' => 'تفعيل',
    'user_status_inactive' => 'تعطيل',

    // Job Titles
    'job_title_cannot_delete_has_users' => 'لا يمكن حذف المسميات الوظيفية لوجود موظفين مسندين إليها. يمكنك تعطيلها بدلاً من ذلك.',

    // Roles
    'role_owner_cannot_rename' => 'لا يمكن تغيير الاسم البرمجي لدور المالك (Owner).',
    'role_system_cannot_delete' => 'لا يمكن حذف الأدوار الأساسية للنظام (System Roles).',
    'role_cannot_delete_has_users' => 'لا يمكن حذف الدور لوجود مستخدمين مرتبطين به. قم بنقل المستخدمين أولاً.',
    'role_created_success' => 'تم إنشاء الدور بنجاح',
    'role_updated_success' => 'تم تحديث الدور والصلاحيات بنجاح',
    'role_deleted_success' => 'تم حذف الدور بنجاح',
    'role_save_failed' => 'حدث خطأ أثناء حفظ الدور: :error',
    'role_update_failed' => 'حدث خطأ أثناء تحديث الدور: :error',

    // Leads & Commercial
    'lead_converted_success' => 'تم تحويل العميل المحتمل بنجاح',
    'lead_assigned_success' => 'تم إسناد العميل المحتمل بنجاح',
    'lead_qualified_success' => 'تم تحديث وتأهيل العميل المحتمل بنجاح',
    'lead_already_converted' => 'تم تحويل هذا الطلب إلى فرصة بالفعل.',
    'lead_not_qualified_for_conversion' => 'يجب تأهيل الطلب أولاً قبل تحويله إلى فرصة.',

    // Opportunities
    'opportunity_created_success' => 'تم إضافة الفرصة بنجاح',
    'opportunity_updated_success' => 'تم تحديث بيانات الفرصة بنجاح',
    'opportunity_deleted_success' => 'تم حذف الفرصة بنجاح',
    'opportunity_assigned_success' => 'تم إسناد الفرصة بنجاح',
    'opportunity_stage_updated_success' => 'تم تحديث مرحلة الفرصة بنجاح',
    'opportunity_converted_success' => 'تم تحويل الطلب وإضافة الفرصة بنجاح',

    // Site Visits (Phase 6)
    'site_visit_created_success' => 'تم تسجيل وحجز موعد المعاينة بنجاح',
    'site_visit_updated_success' => 'تم تحديث بيانات المعاينة بنجاح',
    'site_visit_deleted_success' => 'تم حذف المعاينة بنجاح',
    'site_visit_assigned_success' => 'تم تكليف المهندس بالمعاينة بنجاح',
    'site_visit_scheduled_success' => 'تم جدولة موعد المعاينة بنجاح',
    'site_visit_completed_success' => 'تم اعتماد وإتمام المعاينة بنجاح',
    'site_visit_cancelled_success' => 'تم إلغاء موعد المعاينة بنجاح',
    'site_visit_photos_uploaded_success' => 'تم رفع صور الموقع بنجاح',

    // Measurements (Phase 7)
    'measurement_created_success' => 'تم إنشاء المقايسة بنجاح',
    'measurement_updated_success' => 'تم تحديث بيانات وبنود المقايسة بنجاح',
    'measurement_deleted_success' => 'تم حذف المقايسة بنجاح',
    'measurement_submitted_review_success' => 'تم إرسال المقايسة للمراجعة الفنية بنجاح',
    'measurement_approved_success' => 'تم اعتماد المقايسة الهندسية بنجاح واعتبارها مصدراً للكميات',
    'measurement_revision_created_success' => 'تم إنشاء مراجعة وإصدار جديد من المقايسة بنجاح',
    'site_visit_rooms_imported_success' => 'تم استيراد فراغات وغرف المعاينة بنجاح',
    'measurement_requires_opportunity' => 'المقايسة تتطلب ربطاً بفرصة تجارية صالحة.',
    'opportunity_customer_mismatch' => 'الفرصة التجارية المختارة لا تنتمي لنفس عميل المعاينة الميدانية.',
    'approved_measurement_cannot_be_edited' => 'المقايسة المعتمدة غير قابلة للتعديل. يرجى إنشاء مراجعة جديدة (Revision) للتعديل.',
    'approved_measurement_cannot_be_deleted' => 'لا يمكن حذف مقايسة معتمدة للحفاظ على تاريخ وحصر الكميات.',
    'only_draft_can_be_submitted_for_review' => 'يمكن فقط تقديم المقايسات في حالة المسودة للمراجعة.',
    'measurement_cannot_be_approved_in_current_state' => 'لا يمكن اعتماد المقايسة في حالتها الحالية.',
    'cannot_approve_empty_measurement' => 'لا يمكن اعتماد مقايسة فارغة بدون بنود حصر.',
    'measurement_items_missing_required_info' => 'جميع بنود المقايسة يجب أن تحتوي على اسم الفراغ واسم البند.',
    'dxf_imported_success' => 'تم استيراد أبعاد ومساحات الغرف من ملف DXF بنجاح وإنشاء مسودة المقايسة.',
    'dxf_invalid_format' => 'ملف DXF غير صالح أو تالف. الصيغة المدعومة حاليًا هي DXF فقط.',
    'dxf_unsupported_format' => 'الصيغة المدعومة حاليًا هي DXF فقط. لو عندك ملف DWG، افتحه بالأوتوكاد واعمل Save As → DXF ثم ارفعه هنا.',
    'dxf_no_rooms_found' => 'لم يتم العثور على أي غرف أو مضلعات مغلقة على الـ Layer المختار.',

    // Settings
    'settings_saved_success' => 'تم حفظ الإعدادات بنجاح',
];
