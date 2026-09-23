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

    // Settings
    'settings_saved_success' => 'تم حفظ الإعدادات بنجاح',
];
