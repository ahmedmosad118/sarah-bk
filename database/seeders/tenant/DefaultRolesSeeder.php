<?php

namespace Database\Seeders\Tenant;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DefaultRolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Owner', 'display_name' => 'مالك المنشأة / الشريك المؤسس', 'description' => 'صاحب الصلاحيات الكاملة على حساب الشركة'],
            ['name' => 'Super Admin', 'display_name' => 'مدير نظام أعلى', 'description' => 'إدارة شاملة للمستخدمين والصلاحيات والإعدادات'],
            ['name' => 'Administrator', 'display_name' => 'مسؤول إداري', 'description' => 'إدارة المستخدمين والعمليات الإدارية اليومية'],
            ['name' => 'General Manager', 'display_name' => 'المدير العام', 'description' => 'إشراف استراتيجي وتقارير لكافة قطاعات الشركة'],
            ['name' => 'Project Manager', 'display_name' => 'مدير مشروعات', 'description' => 'إدارة ومتابعة المشروعات، الخطط الزمنية، والمهام'],
            ['name' => 'Project Team', 'display_name' => 'فريق المشروع', 'description' => 'أعضاء الطاقم الهندسي والتنفيذي بالمشروعات'],
            ['name' => 'Site Engineer', 'display_name' => 'مهندس موقع', 'description' => 'تنفيذ المهام الميدانية، المعاينات، ورفع المقاسات'],
            ['name' => 'Site Supervisor', 'display_name' => 'مشرف موقع', 'description' => 'متابعة سير الأعمال في الموقع وتوثيق المهام'],
            ['name' => 'Procurement', 'display_name' => 'مسؤول مشتريات', 'description' => 'إدارة عروض الأسعار، الموردين، وأوامر الشراء'],
            ['name' => 'Warehouse', 'display_name' => 'أمين مخازن', 'description' => 'إدارة المواد، أذون الاستلام، والصرف، والتحويل المخزني'],
            ['name' => 'Finance', 'display_name' => 'مدير مالي', 'description' => 'إشراف مالي كامل، سندات، فواتير، وتكاليف مشروعات'],
            ['name' => 'Accountant', 'display_name' => 'محاسب', 'description' => 'تسجيل السندات، المصروفات، والفواتير والتحصيل'],
            ['name' => 'Sales', 'display_name' => 'مسؤول مبيعات وتعاقدات', 'description' => 'إدارة العملاء، الفرص، المعاينات، وعروض الأسعار'],
            ['name' => 'Estimator', 'display_name' => 'مهندس تسعير ومقايسات', 'description' => 'دراسات التكلفة، جداول الكميات BOQ، ونطاق الأعمال'],
            ['name' => 'HR', 'display_name' => 'موارد بشرية', 'description' => 'إدارة شؤون الموظفين وبيانات فريق العمل'],
            ['name' => 'Quality', 'display_name' => 'مراقبة وتوكيد جودة', 'description' => 'فحص الجودة والمطابقة الفنية للأعمال'],
            ['name' => 'HSE', 'display_name' => 'سلامة وصحة مهنية', 'description' => 'متابعة اشتراطات الأمان والسلامة في المواقع'],
            ['name' => 'Document Controller', 'display_name' => 'مسؤول وثائق وأرشيف', 'description' => 'إدارة المخططات والمستندات ومرفقات المشروعات'],
            ['name' => 'Viewer', 'display_name' => 'مشاهد وتقارير', 'description' => 'صلاحية قراءة واطلاع فقط دون تعديل أو حذف'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                [
                    'name' => $roleData['name'],
                    'guard_name' => 'web',
                ],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                    'is_default' => true,
                ]
            );
        }
    }
}
