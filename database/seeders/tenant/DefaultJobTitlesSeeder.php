<?php

namespace Database\Seeders\Tenant;

use App\Models\JobTitle;
use Illuminate\Database\Seeder;

class DefaultJobTitlesSeeder extends Seeder
{
    public function run(): void
    {
        $jobTitles = [
            ['name' => 'Owner', 'name_ar' => 'مالك المنشأة', 'name_en' => 'Owner', 'code' => 'OWN', 'description' => 'مالك / شريك مؤسس للشركة'],
            ['name' => 'General Manager', 'name_ar' => 'المدير العام', 'name_en' => 'General Manager', 'code' => 'GM', 'description' => 'المدير العام والتنفيذي للشركة'],
            ['name' => 'Admin', 'name_ar' => 'مدير النظام', 'name_en' => 'Admin', 'code' => 'ADM', 'description' => 'مدير النظام التقني والإداري'],
            ['name' => 'Project Manager', 'name_ar' => 'مدير المشروعات', 'name_en' => 'Project Manager', 'code' => 'PM', 'description' => 'مدير المشروعات والتنفيذ'],
            ['name' => 'Project Coordinator', 'name_ar' => 'منسق مشروعات', 'name_en' => 'Project Coordinator', 'code' => 'PC', 'description' => 'منسق المشروعات والعمليات'],
            ['name' => 'Site Engineer', 'name_ar' => 'مهندس موقع', 'name_en' => 'Site Engineer', 'code' => 'SE', 'description' => 'مهندس موقع تنفيذي'],
            ['name' => 'Civil Engineer', 'name_ar' => 'مهندس مدني / إنشائي', 'name_en' => 'Civil Engineer', 'code' => 'CE', 'description' => 'مهندس إنشائي / مدني'],
            ['name' => 'Architect', 'name_ar' => 'مهندس معماري', 'name_en' => 'Architect', 'code' => 'ARC', 'description' => 'مهندس معماري وتصميم'],
            ['name' => 'Electrical Engineer', 'name_ar' => 'مهندس كهرباء', 'name_en' => 'Electrical Engineer', 'code' => 'EE', 'description' => 'مهندس كهرباء وميكانيكا (MEP)'],
            ['name' => 'Mechanical Engineer', 'name_ar' => 'مهندس ميكانيكا', 'name_en' => 'Mechanical Engineer', 'code' => 'ME', 'description' => 'مهندس ميكانيكا'],
            ['name' => 'Interior Designer', 'name_ar' => 'مصمم ديكور وتشطيبات', 'name_en' => 'Interior Designer', 'code' => 'ID', 'description' => 'مهندس / مصمم ديكور وتشطيبات داخلية'],
            ['name' => 'Site Supervisor', 'name_ar' => 'مشرف موقع', 'name_en' => 'Site Supervisor', 'code' => 'SS', 'description' => 'مشرف موقع عام'],
            ['name' => 'Foreman', 'name_ar' => 'ملاحظ عمال', 'name_en' => 'Foreman', 'code' => 'FOR', 'description' => 'ملاحظ عمال ورئيس طاقم'],
            ['name' => 'Technician', 'name_ar' => 'فني تخصصي', 'name_en' => 'Technician', 'code' => 'TEC', 'description' => 'فني تخصصي (كهرباء / سباكة / تشطيب)'],
            ['name' => 'Worker', 'name_ar' => 'عامل موقع', 'name_en' => 'Worker', 'code' => 'WOR', 'description' => 'عامل موقع وتجهيز'],
            ['name' => 'Estimator', 'name_ar' => 'مهندس تسعير وحصر', 'name_en' => 'Estimator', 'code' => 'EST', 'description' => 'مهندس تسعير وحصر'],
            ['name' => 'Quantity Surveyor', 'name_ar' => 'مهندس حصر كميات', 'name_en' => 'Quantity Surveyor', 'code' => 'QS', 'description' => 'مهندس حصر كميات ومستخلصات'],
            ['name' => 'Procurement Officer', 'name_ar' => 'مسؤول مشتريات', 'name_en' => 'Procurement Officer', 'code' => 'PRO', 'description' => 'مسؤول مشتريات وتوريدات'],
            ['name' => 'Purchasing Officer', 'name_ar' => 'أخصائي مشتريات', 'name_en' => 'Purchasing Officer', 'code' => 'PUO', 'description' => 'أخصائي مشتريات ومفاضلة عروض'],
            ['name' => 'Storekeeper', 'name_ar' => 'أمين مخزن', 'name_en' => 'Storekeeper', 'code' => 'SK', 'description' => 'أمين مخزن ومستودعات'],
            ['name' => 'Warehouse Manager', 'name_ar' => 'مدير المستودعات', 'name_en' => 'Warehouse Manager', 'code' => 'WM', 'description' => 'مدير المستودعات والمخازن'],
            ['name' => 'Accountant', 'name_ar' => 'محاسب مالي', 'name_en' => 'Accountant', 'code' => 'ACC', 'description' => 'محاسب مالي'],
            ['name' => 'Finance Manager', 'name_ar' => 'مدير مالي', 'name_en' => 'Finance Manager', 'code' => 'FM', 'description' => 'مدير مالي'],
            ['name' => 'Sales Representative', 'name_ar' => 'مسؤول مبيعات', 'name_en' => 'Sales Representative', 'code' => 'SR', 'description' => 'مسؤول مبيعات وتعاقدات'],
            ['name' => 'Sales Manager', 'name_ar' => 'مدير مبيعات', 'name_en' => 'Sales Manager', 'code' => 'SM', 'description' => 'مدير مبيعات وتطوير أعمال'],
            ['name' => 'Customer Service', 'name_ar' => 'خدمة عملاء', 'name_en' => 'Customer Service', 'code' => 'CS', 'description' => 'خدمة عملاء ودعم ومتابعة'],
            ['name' => 'HR Officer', 'name_ar' => 'مسؤول موارد بشرية', 'name_en' => 'HR Officer', 'code' => 'HR', 'description' => 'مسؤول موارد بشرية'],
            ['name' => 'Operations Manager', 'name_ar' => 'مدير تشغيل', 'name_en' => 'Operations Manager', 'code' => 'OM', 'description' => 'مدير تشغيل وعمليات'],
            ['name' => 'Quality Control', 'name_ar' => 'مراقب جودة', 'name_en' => 'Quality Control', 'code' => 'QC', 'description' => 'مهندس مراقبة جودة'],
            ['name' => 'Quality Assurance', 'name_ar' => 'توكيد جودة', 'name_en' => 'Quality Assurance', 'code' => 'QA', 'description' => 'أخصائي توكيد الجودة'],
            ['name' => 'HSE / Safety Officer', 'name_ar' => 'مسؤول سلامة وبيئة', 'name_en' => 'HSE / Safety Officer', 'code' => 'HSE', 'description' => 'مسؤول سلامة وصحة مهنية وبيئة'],
            ['name' => 'Document Controller', 'name_ar' => 'مسؤول وثائق وأرشيف', 'name_en' => 'Document Controller', 'code' => 'DC', 'description' => 'مسؤول أرشفة وتحكم بالوثائق'],
            ['name' => 'Driver', 'name_ar' => 'سائق خدمات', 'name_en' => 'Driver', 'code' => 'DRV', 'description' => 'سائق خدمات ونقل موقع'],
            ['name' => 'Other', 'name_ar' => 'مسمى وظيفي آخر', 'name_en' => 'Other', 'code' => 'OTH', 'description' => 'مسمى وظيفي آخر'],
        ];

        foreach ($jobTitles as $title) {
            JobTitle::updateOrCreate(
                ['name' => $title['name']],
                [
                    'name_ar' => $title['name_ar'],
                    'name_en' => $title['name_en'],
                    'code' => $title['code'],
                    'description' => $title['description'],
                    'is_default' => true,
                    'is_active' => true,
                ]
            );
        }
    }
}
