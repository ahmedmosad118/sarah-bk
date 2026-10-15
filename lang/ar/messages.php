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
    'dxf_no_rooms_found' => 'لم يتم العثور على أي غرف أو مضلعات مغلقة على الـ Layer المختار.',
    'site_visit_belongs_to_another_opportunity' => 'المعاينة الميدانية مرتبطة بالفعل بفرصة تجارية أخرى.',
    'measurement_unit_type_mismatch' => 'وحدة القياس المحددة غير متوافقة مع نوع الحصر.',

    // Scope of Work (Phase 8)
    'scope_created_success' => 'تم إنشاء نطاق الأعمال وتوصيف البنود بنجاح',
    'scope_updated_success' => 'تم تحديث نطاق الأعمال والمواصفات بنجاح',
    'scope_deleted_success' => 'تم حذف نطاق الأعمال بنجاح',
    'scope_submitted_review_success' => 'تم إرسال نطاق الأعمال للمراجعة الفنية بنجاح',
    'scope_approved_success' => 'تم اعتماد نطاق الأعمال والمواصفات الفنية بنجاح',
    'scope_revision_created_success' => 'تم إنشاء مراجعة وإصدار جديد من نطاق الأعمال بنجاح',
    'scope_requires_approved_measurement' => 'نطاق الأعمال يتطلب ربطاً بمقايسة هندسية معتمدة (Approved Measurement).',
    'approved_scope_cannot_be_edited' => 'نطاق الأعمال المعتمد غير قابل للتعديل المباشر. يرجى إنشاء مراجعة جديدة (Revision).',
    'approved_scope_cannot_be_deleted' => 'لا يمكن حذف نطاق أعمال معتمد للحفاظ على تاريخ وحصر المواصفات الفنية.',
    'only_draft_scope_can_be_submitted_for_review' => 'يمكن فقط تقديم نطاق الأعمال في حالة المسودة للمراجعة.',
    'scope_cannot_be_approved_in_current_state' => 'لا يمكن اعتماد نطاق الأعمال في حالته الحالية.',
    'cannot_approve_empty_scope' => 'لا يمكن اعتماد نطاق أعمال فارغ بدون بنود عمل ومواصفات.',
    'scope_measurement_item_mismatch' => 'أحد بنود القياس المختارة لا ينتمي لنفس المقايسة الهندسية المعتمدة المرتبطة بنطاق الأعمال.',
    'measurement_opportunity_mismatch' => 'المقايسة الهندسية المختارة لا تنتمي لنفس الفرصة التجارية.',

    // BOQ (Phase 9)
    'boq_created_success' => 'تم إنشاء جدول الكميات وتوليد البنود بنجاح',
    'boq_updated_success' => 'تم تحديث جدول الكميات بنجاح',
    'boq_deleted_success' => 'تم حذف جدول الكميات بنجاح',
    'boq_submitted_review_success' => 'تم إرسال جدول الكميات للمراجعة الفنية بنجاح',
    'boq_approved_success' => 'تم اعتماد جدول الكميات بنجاح كوثيقة كميات قابلة للتسعير',
    'boq_revision_created_success' => 'تم إنشاء مراجعة وإصدار جديد من جدول الكميات بنجاح',
    'boq_requires_approved_scope' => 'جدول الكميات يتطلب ربطاً بنطاق أعمال معتمد (Approved Scope).',
    'approved_boq_cannot_be_edited' => 'جدول الكميات المعتمد غير قابل للتعديل المباشر. يرجى إنشاء مراجعة جديدة (Revision).',
    'approved_boq_cannot_be_deleted' => 'لا يمكن حذف جدول كميات معتمد للحفاظ على تاريخ وثائق الكميات.',
    'only_draft_boq_can_be_submitted_for_review' => 'يمكن فقط تقديم جدول الكميات في حالة المسودة للمراجعة.',
    'boq_cannot_be_approved_in_current_state' => 'لا يمكن اعتماد جدول الكميات في حالته الحالية.',
    'cannot_approve_empty_boq' => 'لا يمكن اعتماد جدول كميات فارغ بدون بنود كميات.',
    'boq_scope_item_mismatch' => 'أحد بنود نطاق الأعمال المختارة لا ينتمي لنفس نطاق الأعمال المعتمد المرتبط بجدول الكميات.',
    'scope_opportunity_mismatch' => 'نطاق الأعمال المختار لا ينتمي لنفس الفرصة التجارية.',
    'boq_scope_item_has_no_quantities' => 'بند الشغل ":item" غير مرتبط بأي بنود قياس معتمدة — لا يمكن توليد كمية له تلقائياً.',
    'boq_scope_item_mixed_units' => 'بند الشغل ":item" مرتبط بوحدات قياس مختلفة — افصله لأكتر من بند Scope قبل توليد BOQ.',
    'only_approved_boq_can_be_revised' => 'يمكن إنشاء مراجعة جديدة من جدول كميات معتمد فقط. المسودة أو قيد المراجعة تُعدّل مباشرة.',

    // Estimation (Phase 10)
    'estimation_created_success' => 'تم إنشاء دراسة التسعير والتكلفة بنجاح',
    'estimation_updated_success' => 'تم تحديث دراسة التسعير وحسابات التكلفة بنجاح',
    'estimation_deleted_success' => 'تم حذف دراسة التسعير بنجاح',
    'estimation_submitted_review_success' => 'تم إرسال دراسة التسعير للمراجعة والتدقيق بنجاح',
    'estimation_approved_success' => 'تم اعتماد دراسة التسعير والتكلفة بنجاح',
    'estimation_revision_created_success' => 'تم إنشاء مراجعة وإصدار جديد من دراسة التسعير بنجاح',
    'estimation_requires_approved_boq' => 'دراسة التسعير تتطلب ربطاً بجدول كميات معتمد (Approved BOQ).',
    'boq_opportunity_mismatch' => 'جدول الكميات المختار لا ينتمي لنفس الفرصة التجارية.',
    'approved_estimation_cannot_be_edited' => 'دراسة التسعير المعتمدة غير قابلة للتعديل المباشر. يرجى إنشاء مراجعة جديدة (Revision).',
    'approved_estimation_cannot_be_deleted' => 'لا يمكن حذف دراسة تسعير معتمدة للحفاظ على تاريخ التكاليف.',
    'only_draft_estimation_can_be_submitted_for_review' => 'يمكن فقط تقديم دراسة التسعير في حالة المسودة للمراجعة.',
    'estimation_cannot_be_approved_in_current_state' => 'لا يمكن اعتماد دراسة التسعير في حالتها الحالية.',
    'cannot_approve_empty_estimation' => 'لا يمكن اعتماد دراسة تسعير فارغة بدون بنود.',
    'cannot_approve_zero_cost_estimation' => 'لا يمكن اعتماد دراسة تسعير بدون إدخال أسعار تكلفة للبنود.',
    'cannot_estimate_empty_boq' => 'جدول الكميات المختار فارغ ولا يحتوي على بنود للتسعير.',
    'estimation_boq_item_mismatch' => 'أحد بنود جدول الكميات المختارة لا ينتمي لجدول الكميات المرتبط بدراسة التسعير.',
    'only_approved_estimation_can_be_revised' => 'يمكن إنشاء مراجعة جديدة من دراسة تسعير معتمدة فقط. المسودة أو قيد المراجعة تُعدّل مباشرة.',

    // Settings
    'settings_saved_success' => 'تم حفظ الإعدادات بنجاح',
];
