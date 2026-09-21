<?php

namespace Database\Seeders\Tenant;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class DefaultSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'company_legal_name', 'value' => 'شركة صرح للمقاولات العامة', 'group' => 'company', 'description' => 'الاسم القانوني للشركة'],
            ['key' => 'currency', 'value' => 'EGP', 'group' => 'general', 'description' => 'العملة الأساسية (ج.م)'],
            ['key' => 'currency_symbol', 'value' => 'ج.م', 'group' => 'general', 'description' => 'رمز العملة'],
            ['key' => 'timezone', 'value' => 'Africa/Cairo', 'group' => 'general', 'description' => 'المنطقة الزمنية الافتراضية'],
            ['key' => 'date_format', 'value' => 'Y-m-d', 'group' => 'general', 'description' => 'تنسيق التاريخ الافتراضي'],
            ['key' => 'vat_rate', 'value' => '14', 'group' => 'finance', 'description' => 'نسبة ضريبة القيمة المضافة الافتراضية %'],
            ['key' => 'work_days_per_week', 'value' => '6', 'group' => 'projects', 'description' => 'عدد أيام العمل الأسبوعية للمشروعات'],
            ['key' => 'allow_registration', 'value' => '0', 'group' => 'security', 'description' => 'السماح بالتسجيل الذاتي للمستخدمين'],
            ['key' => 'theme_mode', 'value' => 'light', 'group' => 'ui', 'description' => 'السمة الافتراضية للواجهة'],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'description' => $setting['description'],
                ]
            );
        }
    }
}
