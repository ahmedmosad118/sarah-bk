<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class DefaultPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = [
            'System' => [
                'dashboard.view' => 'عرض لوحة التحكم الرئيسية',
                'settings.view' => 'عرض إعدادات النظام',
                'settings.update' => 'تحديث إعدادات النظام',
                'users.view' => 'عرض قائمة المستخدمين',
                'users.create' => 'إضافة مستخدم جديد',
                'users.update' => 'تعديل بيانات المستخدم',
                'users.delete' => 'حذف مستخدم',
                'users.activate' => 'تفعيل حساب مستخدم',
                'users.deactivate' => 'تعطيل حساب مستخدم',
                'roles.view' => 'عرض الأدوار والصلاحيات',
                'roles.create' => 'إنشاء دور جديد',
                'roles.update' => 'تعديل الأدوار والصلاحيات',
                'roles.delete' => 'حذف دور',
                'permissions.view' => 'استعراض مصفوفة الصلاحيات',
            ],
            'Company' => [
                'company.view' => 'عرض بيانات الشركة',
                'company.update' => 'تحديث بيانات الشركة',
                'company.settings' => 'إدارة إعدادات الشركة',
            ],
            'Customers' => [
                'customers.view' => 'عرض بيانات العملاء',
                'customers.create' => 'إضافة عميل جديد',
                'customers.update' => 'تعديل بيانات العميل',
                'customers.delete' => 'حذف عميل',
            ],
            'Leads' => [
                'leads.view' => 'عرض العملاء المحتملين (Leads)',
                'leads.create' => 'إضافة عميل محتمل',
                'leads.update' => 'تعديل بيانات العميل المحتمل',
                'leads.delete' => 'حذف عميل محتمل',
                'leads.assign' => 'تعيين مسؤول للعميل المحتمل',
                'leads.convert' => 'تحويل العميل المحتمل لفرصة/عميل',
                'leads.export' => 'تصدير بيانات العملاء المحتملين',
            ],
            'Opportunities' => [
                'opportunities.view' => 'عرض الفرص البيعية',
                'opportunities.create' => 'إضافة فرصة بيعية جديدة',
                'opportunities.update' => 'تعديل فرصة بيعية',
                'opportunities.delete' => 'حذف فرصة بيعية',
                'opportunities.assign' => 'إسناد الفرصة لمسؤول',
                'opportunities.convert' => 'تحويل الفرصة لمشروع',
                'opportunities.export' => 'تصدير الفرص البيعية',
            ],
            'Site Visits' => [
                'site_visits.view' => 'عرض المعاينات الموقعية',
                'site_visits.create' => 'حجز وإضافة معاينة موقع',
                'site_visits.update' => 'تعديل تقرير المعاينة',
                'site_visits.delete' => 'حذف معاينة موقع',
                'site_visits.assign' => 'تكليف مهندس بالمعاينة',
                'site_visits.complete' => 'اعتماد إتمام المعاينة',
            ],
            'Measurements' => [
                'measurements.view' => 'عرض مقاسات ورفوعات الموقع',
                'measurements.create' => 'تسجيل مقاسات جديدة',
                'measurements.update' => 'تعديل المقاسات والرفوعات',
                'measurements.delete' => 'حذف مقاسات',
                'measurements.approve' => 'اعتماد المقاسات الفنية',
            ],
            'Scope of Work' => [
                'scope.view' => 'عرض نطاق الأعمال (Scope of Work)',
                'scope.create' => 'إضافة بنود نطاق الأعمال',
                'scope.update' => 'تعديل نطاق الأعمال',
                'scope.delete' => 'حذف بند نطاق أعمال',
                'scope.approve' => 'اعتماد نطاق الأعمال',
            ],
            'BOQ' => [
                'boq.view' => 'عرض جداول الكميات (BOQ)',
                'boq.create' => 'إنشاء جدول كميات ومقايسة',
                'boq.update' => 'تعديل بنود المقايسة',
                'boq.delete' => 'حذف جدول كميات',
                'boq.import' => 'استيراد المقايسة من Excel',
                'boq.export' => 'تصدير المقايسة إلى Excel / PDF',
                'boq.approve' => 'اعتماد جدول الكميات النهائي',
            ],
            'Estimation' => [
                'estimations.view' => 'عرض دراسات التسعير والتكلفة',
                'estimations.create' => 'إعداد دراسة تسعير جديدة',
                'estimations.update' => 'تعديل أسعار التكلفة وهامش الربح',
                'estimations.delete' => 'حذف دراسة تسعير',
                'estimations.approve' => 'اعتماد التسعير الفني والمالي',
                'estimations.export' => 'تصدير شيتات التسعير',
            ],
            'Quotations' => [
                'quotations.view' => 'عرض عروض الأسعار للعملاء',
                'quotations.create' => 'إنشاء عرض سعر جديد',
                'quotations.update' => 'تعديل عرض السعر',
                'quotations.delete' => 'حذف عرض سعر',
                'quotations.send' => 'إرسال عرض السعر للعميل',
                'quotations.approve' => 'الموافقة على عرض السعر',
                'quotations.reject' => 'رفض عرض السعر',
                'quotations.export' => 'طباعة وتصدير عرض السعر PDF',
            ],
            'Contracts' => [
                'contracts.view' => 'عرض العقود والاتفاقيات',
                'contracts.create' => 'تحرير عقد مشروع جديد',
                'contracts.update' => 'تعديل بنود العقد',
                'contracts.delete' => 'حذف عقد',
                'contracts.approve' => 'اعتماد صيغة العقد',
                'contracts.sign' => 'توقيع وتفعيل العقد',
                'contracts.export' => 'تصدير وطباعة العقد',
            ],
            'Projects' => [
                'projects.view' => 'عرض المشروعات',
                'projects.create' => 'إنشاء مشروع جديد',
                'projects.update' => 'تعديل بيانات المشروع',
                'projects.delete' => 'حذف مشروع',
                'projects.archive' => 'أرشفة المشروع',
                'projects.assign' => 'تعيين فريق عمل المشروع',
                'projects.approve' => 'اعتماد المشروع وتسليمه',
                'projects.export' => 'تصدير بيانات المشروعات',
            ],
            'Project Planning' => [
                'planning.view' => 'عرض المخطط الزمني للمشروع',
                'planning.create' => 'إنشاء خطة زمنية',
                'planning.update' => 'تحديث الخطة والجدول الزمني',
                'planning.delete' => 'حذف خطة زمنية',
                'wbs.view' => 'عرض هيكل تفكيك الأعمال (WBS)',
                'wbs.create' => 'إنشاء بنود WBS',
                'wbs.update' => 'تعديل بنود WBS',
                'wbs.delete' => 'حذف بنود WBS',
            ],
            'Tasks' => [
                'tasks.view' => 'عرض المهام اليومية والتنفيذية',
                'tasks.create' => 'إضافة مهمة جديدة',
                'tasks.update' => 'تعديل المهمة وحالتها',
                'tasks.delete' => 'حذف مهمة',
                'tasks.assign' => 'إسناد المهمة لفني/مهندس',
                'tasks.complete' => 'إنهاء المهمة وتسليمها',
                'tasks.approve' => 'اعتماد إنجاز المهمة',
            ],
            'Procurement' => [
                'procurement.view' => 'عرض طلبات وأوامر الشراء',
                'procurement.create' => 'إنشاء أمر شراء (PO)',
                'procurement.update' => 'تعديل أمر الشراء',
                'procurement.delete' => 'إلغاء وحذف أمر شراء',
                'procurement.approve' => 'اعتماد أمر الشراء والتعميد',
                'procurement.request' => 'تقديم طلب شراء خامات (PR)',
                'procurement.export' => 'تصدير أوامر الشراء',
            ],
            'Suppliers' => [
                'suppliers.view' => 'عرض سجل الموردين والمقاولين',
                'suppliers.create' => 'إضافة مورد جديد',
                'suppliers.update' => 'تعديل بيانات المورد',
                'suppliers.delete' => 'حذف مورد',
            ],
            'Materials' => [
                'materials.view' => 'عرض دليل المواد والخامات',
                'materials.create' => 'إضافة مادة/خامة جديدة',
                'materials.update' => 'تعديل بيانات وأسعار المادة',
                'materials.delete' => 'حذف مادة',
                'materials.import' => 'استيراد المواد من Excel',
                'materials.export' => 'تصدير دليل المواد',
            ],
            'Inventory' => [
                'inventory.view' => 'عرض أرصدة وحركة المخازن',
                'inventory.create' => 'إنشاء إذن مخزني',
                'inventory.update' => 'تعديل حركة المخزن',
                'inventory.delete' => 'حذف إذن مخزني',
                'inventory.receive' => 'استلام خامات بالمخزن (إذن إضافة)',
                'inventory.issue' => 'صرف خامات للموقع (إذن صرف)',
                'inventory.transfer' => 'تحويل خامات بين المواقع/المخازن',
                'inventory.adjust' => 'تسوية جردية للمخزن',
            ],
            'Finance' => [
                'finance.view' => 'عرض الحركات والتقارير المالية',
                'finance.create' => 'إضافة معاملة مالية',
                'finance.update' => 'تعديل معاملة مالية',
                'finance.delete' => 'حذف معاملة مالية',
                'payments.view' => 'عرض سندات القبض والدفعات',
                'payments.create' => 'تسجيل سند قبض / دفعة من عميل',
                'payments.update' => 'تعديل سند القبض',
                'payments.delete' => 'إلغاء سند القبض',
                'expenses.view' => 'عرض المصروفات وسندات الصرف',
                'expenses.create' => 'تسجيل مصروف موقع/إداري',
                'expenses.update' => 'تعديل سند الصرف والمصروف',
                'expenses.delete' => 'حذف سند صرف',
                'invoices.view' => 'عرض الفواتير والمستخلصات',
                'invoices.create' => 'إصدار فاتورة / مستخلص',
                'invoices.update' => 'تعديل الفاتورة',
                'invoices.delete' => 'إلغاء الفاتورة',
                'invoices.approve' => 'اعتماد الفاتورة والمستخلص',
            ],
            'Project Cost' => [
                'project_cost.view' => 'عرض تكلفة وربحية المشروعات',
                'project_cost.create' => 'تسجيل بنود تكاليف فعلية',
                'project_cost.update' => 'تعديل بنود التكلفة',
                'project_cost.approve' => 'اعتماد تقرير التكلفة النهائي',
                'project_cost.export' => 'تصدير تحليل تكاليف المشروع',
            ],
            'Change Requests' => [
                'change_requests.view' => 'عرض طلبات التغيير والأعمال الإضافية',
                'change_requests.create' => 'تقديم طلب تغيير (Variation Order)',
                'change_requests.update' => 'تعديل طلب التغيير',
                'change_requests.delete' => 'حذف طلب تغيير',
                'change_requests.approve' => 'الموافقة على أمر التغيير والزيادة',
                'change_requests.reject' => 'رفض أمر التغيير',
            ],
            'Reports' => [
                'reports.view' => 'عرض التقارير والإحصائيات',
                'reports.export' => 'تصدير التقارير Excel / PDF',
                'reports.print' => 'طباعة التقارير',
            ],
            'Activity Log' => [
                'activity_log.view' => 'عرض سجل الأنشطة والتدقيق (Audit Log)',
                'activity_log.export' => 'تصدير سجل الأنشطة',
            ],
            'Media' => [
                'media.view' => 'استعراض مكتبة الميديا والملفات',
                'media.upload' => 'رفع صور ومستندات وملفات',
                'media.update' => 'تعديل خصائص الملفات',
                'media.delete' => 'حذف ملفات وميديا',
                'media.download' => 'تحميل الملفات والمرفقات',
            ],
        ];

        foreach ($modules as $moduleName => $perms) {
            foreach ($perms as $permName => $displayName) {
                Permission::firstOrCreate(
                    [
                        'name' => $permName,
                        'guard_name' => 'web',
                    ],
                    [
                        'module' => $moduleName,
                        'display_name' => $displayName,
                    ]
                );
            }
        }
    }
}
